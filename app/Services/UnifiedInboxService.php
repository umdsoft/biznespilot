<?php

namespace App\Services;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\TelegramConversation;
use App\Models\TelegramMessage;
use App\Services\Telegram\TelegramApiService;
use Illuminate\Support\Collection;

/**
 * Unified Inbox Service
 *
 * Aggregates conversations from all channels (WhatsApp, Instagram, Telegram, Facebook)
 * into a single unified inbox
 */
class UnifiedInboxService
{
    /**
     * Get all conversations across channels
     */
    public function getAllConversations(Business $business, array $filters = []): Collection
    {
        $conversations = collect();

        // Get Telegram conversations if not filtering by other channels
        if (empty($filters['channel']) || $filters['channel'] === 'telegram') {
            $telegramConversations = $this->getTelegramConversations($business, $filters);
            $conversations = $conversations->merge($telegramConversations);
        }

        // Get Instagram/Facebook conversations from ChatbotConversation
        if (empty($filters['channel']) || in_array($filters['channel'], ['instagram', 'facebook'])) {
            $query = ChatbotConversation::where('business_id', $business->id)
                ->with(['customer', 'messages' => function ($q) {
                    $q->latest()->limit(1);
                }]);

            // Filter by channel (uses 'platform' column in database)
            if (! empty($filters['channel']) && $filters['channel'] !== 'telegram') {
                $query->where('platform', $filters['channel']);
            } elseif (empty($filters['channel'])) {
                $query->whereIn('platform', ['instagram', 'facebook']);
            }

            // Filter by status
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Search
            if (! empty($filters['search'])) {
                $search = $filters['search'];
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $chatbotConversations = $query->orderBy('last_message_at', 'desc')
                ->limit($filters['limit'] ?? 50)
                ->get()
                ->map(fn ($conv) => $this->formatConversation($conv));

            $conversations = $conversations->merge($chatbotConversations);
        }

        // Phone calls are NOT included in Yagona Inbox
        // They have their own dedicated Call Center section
        // if (empty($filters['channel']) || $filters['channel'] === 'phone') {
        //     $phoneConversations = $this->getPhoneConversations($business, $filters);
        //     $conversations = $conversations->merge($phoneConversations);
        // }

        // Sort by last message time
        return $conversations->sortByDesc('last_message_at_raw')
            ->values()
            ->take($filters['limit'] ?? 50);
    }

    /**
     * Get Telegram conversations
     */
    protected function getTelegramConversations(Business $business, array $filters = []): Collection
    {
        $query = TelegramConversation::where('business_id', $business->id)
            ->with(['user', 'bot', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('direction', 'incoming')->where('is_read', false);
            }]);

        // Filter by status
        if (! empty($filters['status'])) {
            $statusMap = [
                'open' => 'active',
                'pending' => 'handoff',
                'closed' => 'closed',
            ];
            $query->where('status', $statusMap[$filters['status']] ?? $filters['status']);
        }

        // Search
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('last_message_at', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn ($conv) => $this->formatTelegramConversation($conv));
    }

    /**
     * Get phone call conversations (grouped by lead/phone number)
     */
    protected function getPhoneConversations(Business $business, array $filters = []): Collection
    {
        // Get recent calls grouped by lead or phone number
        $query = CallLog::where('business_id', $business->id)
            ->with(['lead', 'user'])
            ->orderBy('created_at', 'desc');

        // Search
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('from_number', 'like', "%{$search}%")
                    ->orWhere('to_number', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Get unique conversations by lead_id or phone number
        $calls = $query->limit(100)->get();

        // Group calls by lead_id or phone number to create "conversations"
        $grouped = $calls->groupBy(function ($call) {
            if ($call->lead_id) {
                return 'lead_'.$call->lead_id;
            }
            // Group by phone number for calls without lead
            $phone = $call->direction === 'inbound' ? $call->from_number : $call->to_number;

            return 'phone_'.preg_replace('/[^0-9]/', '', $phone);
        });

        return $grouped->map(function ($leadCalls, $key) {
            $lastCall = $leadCalls->first();
            $lead = $lastCall->lead;

            $phoneNumber = $lastCall->direction === 'inbound'
                ? $lastCall->from_number
                : $lastCall->to_number;

            $name = $lead?->name ?? $this->formatPhoneNumber($phoneNumber);

            // Calculate stats
            $totalCalls = $leadCalls->count();
            $answeredCalls = $leadCalls->where('status', 'completed')->count();
            $totalDuration = $leadCalls->sum('duration');

            // Determine status based on last call
            $status = match ($lastCall->status) {
                'completed' => 'closed',
                'missed', 'no_answer' => 'pending',
                'initiated', 'ringing' => 'open',
                default => 'closed',
            };

            return [
                'id' => 'call_'.$key,
                'original_id' => $lead?->id ?? $key,
                'lead_id' => $lead?->id,
                'channel' => 'phone',
                'customer_name' => $name,
                'customer_phone' => $phoneNumber,
                'customer_avatar' => '📞',
                'last_message' => $this->formatCallSummary($lastCall),
                'last_message_time' => $lastCall->created_at->diffForHumans(),
                'last_message_at_raw' => $lastCall->created_at,
                'status' => $status,
                'is_unread' => $lastCall->status === 'missed' || $lastCall->status === 'no_answer',
                'unread_count' => $leadCalls->whereIn('status', ['missed', 'no_answer'])->count(),
                'message_count' => $totalCalls,
                'call_stats' => [
                    'total_calls' => $totalCalls,
                    'answered_calls' => $answeredCalls,
                    'total_duration' => $totalDuration,
                    'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100) : 0,
                ],
            ];
        })->values()->take($filters['limit'] ?? 50);
    }

    /**
     * Format call summary for display
     */
    protected function formatCallSummary(CallLog $call): string
    {
        $direction = $call->direction === 'inbound' ? '📥' : '📤';
        $status = match ($call->status) {
            'completed' => '✅',
            'missed' => '❌ O\'tkazildi',
            'no_answer' => '❌ Javob yo\'q',
            'busy' => '🔴 Band',
            'failed' => '⚠️ Xato',
            default => '',
        };

        if ($call->status === 'completed' && $call->duration > 0) {
            $duration = $this->formatCallDuration($call->duration);

            return "{$direction} {$status} {$duration}";
        }

        return "{$direction} {$status}";
    }

    /**
     * Format call duration
     */
    protected function formatCallDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' sek';
        }
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return $minutes.':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Format phone number for display
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Uzbekistan format
        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            return '+998 ('.substr($digits, 3, 2).') '.substr($digits, 5, 3).'-'.substr($digits, 8, 2).'-'.substr($digits, 10, 2);
        }

        return $phone;
    }

    /**
     * Format Telegram conversation for display
     */
    protected function formatTelegramConversation(TelegramConversation $conversation): array
    {
        $lastMessage = $conversation->messages->first();
        $user = $conversation->user;

        $lastMessageText = '';
        if ($lastMessage) {
            $content = $lastMessage->content;
            if (is_array($content)) {
                $lastMessageText = $content['text'] ?? $content['command'] ?? '[Xabar]';
            } else {
                $lastMessageText = $content;
            }
        }

        return [
            'id' => 'tg_'.$conversation->id,
            'original_id' => $conversation->id,
            'channel' => 'telegram',
            'customer_name' => $user ? trim($user->first_name.' '.($user->last_name ?? '')) : 'Noma\'lum',
            'customer_username' => $user?->username,
            'customer_phone' => $user?->phone,
            'customer_avatar' => '✈️',
            'last_message' => $lastMessageText,
            'last_message_time' => $conversation->last_message_at?->diffForHumans(),
            'last_message_at_raw' => $conversation->last_message_at,
            'status' => $this->mapTelegramStatus($conversation->status),
            'is_unread' => ($conversation->unread_count ?? 0) > 0,
            'unread_count' => $conversation->unread_count ?? 0,
            'message_count' => $conversation->messages_count ?? $conversation->messages()->count(),
            'bot_name' => $conversation->bot?->first_name ?? 'Bot',
        ];
    }

    /**
     * Map Telegram status to unified status
     */
    protected function mapTelegramStatus(string $status): string
    {
        return match ($status) {
            'active' => 'open',
            'handoff' => 'pending',
            'closed' => 'closed',
            default => 'open',
        };
    }

    /**
     * Get conversation details with full message history
     */
    public function getConversationDetails(string $conversationId): array
    {
        // Check if it's a Telegram conversation
        if (str_starts_with($conversationId, 'tg_')) {
            return $this->getTelegramConversationDetails(substr($conversationId, 3));
        }

        // Check if it's a phone call conversation
        if (str_starts_with($conversationId, 'call_')) {
            return $this->getPhoneConversationDetails(substr($conversationId, 5));
        }

        $conversation = ChatbotConversation::with(['customer', 'messages'])->findOrFail($conversationId);

        return [
            'id' => $conversation->id,
            'channel' => $conversation->platform,
            'customer' => [
                'id' => $conversation->customer->id,
                'name' => $conversation->customer->name,
                'phone' => $conversation->customer->phone,
                'source' => $conversation->customer->source,
                'tags' => $conversation->customer->tags,
            ],
            'status' => $conversation->status,
            'current_stage' => $conversation->current_stage,
            'messages' => $conversation->messages->map(fn ($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction,
                'content' => $msg->message_content,
                'timestamp' => $msg->created_at->format('Y-m-d H:i:s'),
                'human_time' => $msg->created_at->diffForHumans(),
            ]),
            'created_at' => $conversation->created_at->format('Y-m-d H:i:s'),
            'last_message_at' => $conversation->last_message_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get Telegram conversation details
     */
    protected function getTelegramConversationDetails(string $conversationId): array
    {
        $conversation = TelegramConversation::with(['user', 'bot', 'messages'])
            ->findOrFail($conversationId);

        $user = $conversation->user;

        // Mark messages as read
        $conversation->markAllAsRead();

        return [
            'id' => 'tg_'.$conversation->id,
            'original_id' => $conversation->id,
            'channel' => 'telegram',
            'customer' => [
                'id' => $user?->id,
                'name' => $user ? trim($user->first_name.' '.($user->last_name ?? '')) : 'Noma\'lum',
                'phone' => $user?->phone,
                'username' => $user?->username,
                'telegram_id' => $user?->telegram_id,
                'tags' => $user?->tags ?? [],
            ],
            'bot' => [
                'id' => $conversation->bot?->id,
                'name' => $conversation->bot?->first_name,
                'username' => $conversation->bot?->bot_username,
            ],
            'status' => $this->mapTelegramStatus($conversation->status),
            'messages' => $conversation->messages->sortBy('created_at')->values()->map(fn ($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction === 'incoming' ? 'inbound' : 'outbound',
                'content' => $this->formatTelegramMessageContent($msg->content),
                'content_type' => $msg->content_type,
                'timestamp' => $msg->created_at->format('Y-m-d H:i:s'),
                'human_time' => $msg->created_at->diffForHumans(),
            ]),
            'created_at' => $conversation->created_at->format('Y-m-d H:i:s'),
            'last_message_at' => $conversation->last_message_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get phone call conversation details (all calls for a lead/phone number)
     */
    protected function getPhoneConversationDetails(string $conversationKey): array
    {
        // Parse the conversation key (lead_123 or phone_998901234567)
        $isLead = str_starts_with($conversationKey, 'lead_');
        $identifier = $isLead ? substr($conversationKey, 5) : substr($conversationKey, 6);

        if ($isLead) {
            $lead = Lead::with(['calls' => function ($q) {
                $q->with('user')->orderBy('created_at', 'desc');
            }])->findOrFail($identifier);

            $calls = $lead->calls;
            $phoneNumber = $lead->phone;
            $name = $lead->name;
        } else {
            // Find calls by phone number
            $phoneNumber = $identifier;
            $calls = CallLog::where(function ($q) use ($phoneNumber) {
                $q->where('from_number', 'like', "%{$phoneNumber}%")
                    ->orWhere('to_number', 'like', "%{$phoneNumber}%");
            })
                ->with(['lead', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();

            $name = $this->formatPhoneNumber($phoneNumber);
            $lead = $calls->first()?->lead;
        }

        // Calculate call statistics
        $totalCalls = $calls->count();
        $inboundCalls = $calls->where('direction', 'inbound')->count();
        $outboundCalls = $calls->where('direction', 'outbound')->count();
        $answeredCalls = $calls->where('status', 'completed')->count();
        $missedCalls = $calls->whereIn('status', ['missed', 'no_answer'])->count();
        $totalDuration = $calls->sum('duration');

        return [
            'id' => 'call_'.$conversationKey,
            'original_id' => $lead?->id ?? $conversationKey,
            'lead_id' => $lead?->id,
            'channel' => 'phone',
            'customer' => [
                'id' => $lead?->id,
                'name' => $name,
                'phone' => $phoneNumber,
                'email' => $lead?->email,
                'source' => $lead?->source,
                'status' => $lead?->status,
                'tags' => $lead?->tags ?? [],
            ],
            'status' => $missedCalls > 0 && $calls->first()?->status !== 'completed' ? 'pending' : 'closed',
            'call_stats' => [
                'total_calls' => $totalCalls,
                'inbound_calls' => $inboundCalls,
                'outbound_calls' => $outboundCalls,
                'answered_calls' => $answeredCalls,
                'missed_calls' => $missedCalls,
                'total_duration' => $totalDuration,
                'total_duration_formatted' => $this->formatCallDuration($totalDuration),
                'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100) : 0,
            ],
            'messages' => $calls->map(fn ($call) => [
                'id' => $call->id,
                'direction' => $call->direction,
                'content' => $this->formatCallDetails($call),
                'content_type' => 'call',
                'status' => $call->status,
                'status_label' => $this->getCallStatusLabel($call->status),
                'duration' => $call->duration,
                'duration_formatted' => $call->duration ? $this->formatCallDuration($call->duration) : null,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'recording_url' => $call->recording_url,
                'operator' => $call->user?->name,
                'timestamp' => $call->created_at->format('Y-m-d H:i:s'),
                'human_time' => $call->created_at->diffForHumans(),
            ])->values(),
            'created_at' => $calls->last()?->created_at?->format('Y-m-d H:i:s'),
            'last_message_at' => $calls->first()?->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format call details for display in conversation
     */
    protected function formatCallDetails(CallLog $call): string
    {
        $direction = $call->direction === 'inbound' ? 'Kiruvchi qo\'ng\'iroq' : 'Chiquvchi qo\'ng\'iroq';
        $status = $this->getCallStatusLabel($call->status);

        $text = "{$direction} - {$status}";

        if ($call->status === 'completed' && $call->duration > 0) {
            $duration = $this->formatCallDuration($call->duration);
            $text .= " ({$duration})";
        }

        if ($call->user) {
            $text .= " - {$call->user->name}";
        }

        return $text;
    }

    /**
     * Get readable call status label
     */
    protected function getCallStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Tugallangan',
            'missed' => 'O\'tkazildi',
            'no_answer' => 'Javob yo\'q',
            'busy' => 'Band',
            'failed' => 'Xato',
            'initiated' => 'Boshlangan',
            'ringing' => 'Jiringlayapti',
            default => ucfirst($status),
        };
    }

    /**
     * Format Telegram message content for display
     */
    protected function formatTelegramMessageContent($content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            if (isset($content['text'])) {
                return $content['text'];
            }
            if (isset($content['command'])) {
                return $content['command'];
            }
            if (isset($content['phone_number'])) {
                return '📱 Kontakt: '.$content['phone_number'];
            }
            if (isset($content['latitude'])) {
                return '📍 Lokatsiya yuborildi';
            }
            if (isset($content['caption'])) {
                return $content['caption'];
            }
        }

        return '[Xabar]';
    }

    /**
     * Send message from unified inbox
     */
    public function sendMessage(string $conversationId, string $message, string $userId): array
    {
        // Check if it's a Telegram conversation
        if (str_starts_with($conversationId, 'tg_')) {
            return $this->sendTelegramMessage(substr($conversationId, 3), $message, $userId);
        }

        $conversation = ChatbotConversation::findOrFail($conversationId);
        $customer = $conversation->customer;

        // Send via appropriate channel
        $result = $this->sendViaChannel($conversation->platform, $customer, $message);

        if ($result) {
            // Log message
            ChatbotMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outgoing',
                'message_content' => $message,
                'sent_by_user_id' => $userId,
                'is_from_human' => true,
            ]);

            $conversation->update(['last_message_at' => now()]);
        }

        return [
            'success' => (bool) $result,
            'message' => $result ? 'Message sent' : 'Failed to send',
        ];
    }

    /**
     * Send Telegram message
     */
    protected function sendTelegramMessage(string $conversationId, string $message, string $userId): array
    {
        $conversation = TelegramConversation::with(['user', 'bot'])->findOrFail($conversationId);

        if (! $conversation->bot || ! $conversation->user) {
            return [
                'success' => false,
                'message' => 'Bot yoki foydalanuvchi topilmadi',
            ];
        }

        try {
            $api = new TelegramApiService($conversation->bot);
            $result = $api->sendMessage($conversation->user->telegram_id, $message);

            if ($result['success']) {
                // Log message
                TelegramMessage::create([
                    'conversation_id' => $conversation->id,
                    'telegram_message_id' => $result['result']['message_id'] ?? null,
                    'telegram_chat_id' => $conversation->user->telegram_id,
                    'direction' => 'outgoing',
                    'sender_type' => 'operator',
                    'content_type' => 'text',
                    'content' => ['text' => $message],
                ]);

                $conversation->update(['last_message_at' => now()]);

                return [
                    'success' => true,
                    'message' => 'Xabar yuborildi',
                ];
            }

            return [
                'success' => false,
                'message' => $result['description'] ?? 'Xabar yuborilmadi',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Xatolik: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get inbox statistics
     */
    public function getInboxStats(Business $business): array
    {
        $chatbotQuery = fn () => ChatbotConversation::where('business_id', $business->id);
        $telegramQuery = fn () => TelegramConversation::where('business_id', $business->id);

        // Telegram stats
        $telegramTotal = $telegramQuery()->count();
        $telegramOpen = $telegramQuery()->where('status', 'active')->count();
        $telegramPending = $telegramQuery()->where('status', 'handoff')->count();
        $telegramClosed = $telegramQuery()->where('status', 'closed')->count();

        // Telegram unread count
        $telegramUnread = TelegramMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })->where('direction', 'incoming')
            ->where('is_read', false)
            ->count();

        // Chatbot stats
        $chatbotTotal = $chatbotQuery()->count();
        $chatbotOpen = $chatbotQuery()->where('status', 'open')->count();
        $chatbotPending = $chatbotQuery()->where('status', 'pending')->count();
        $chatbotClosed = $chatbotQuery()->where('status', 'closed')->count();

        // Instagram/Facebook unread
        $instagramUnread = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id)->where('platform', 'instagram');
        })->where('direction', 'inbound')
            ->whereNull('read_at')
            ->count();

        // Phone calls are excluded from Yagona Inbox stats
        // They have their own dedicated Call Center section
        return [
            'total' => $chatbotTotal + $telegramTotal,
            'open' => $chatbotOpen + $telegramOpen,
            'pending' => $chatbotPending + $telegramPending,
            'closed' => $chatbotClosed + $telegramClosed,
            'by_channel' => [
                'instagram' => $chatbotQuery()->where('platform', 'instagram')->count(),
                'telegram' => $telegramTotal,
                'facebook' => $chatbotQuery()->where('platform', 'facebook')->count(),
            ],
            'unread' => [
                'total' => $telegramUnread + $instagramUnread,
                'instagram' => $instagramUnread,
                'telegram' => $telegramUnread,
                'facebook' => 0,
            ],
            'response_rate' => $this->calculateResponseRate($business),
        ];
    }

    /**
     * Format conversation for display
     */
    protected function formatConversation(ChatbotConversation $conversation): array
    {
        $lastMessage = $conversation->messages->first();

        return [
            'id' => $conversation->id,
            'channel' => $conversation->platform,
            'customer_name' => $conversation->customer?->name ?? $conversation->customer_name,
            'customer_avatar' => $this->getChannelAvatar($conversation->platform),
            'last_message' => $lastMessage?->content ?? $lastMessage?->message_content,
            'last_message_time' => $conversation->last_message_at?->diffForHumans(),
            'status' => $conversation->status,
            'is_unread' => $lastMessage?->direction === 'inbound' && ! $lastMessage?->read_at,
            'message_count' => $conversation->messages_count,
        ];
    }

    /**
     * Send message via appropriate channel service
     */
    protected function sendViaChannel(string $channel, Customer $customer, string $message): ?array
    {
        switch ($channel) {
            case 'instagram':
                // Instagram DM implementation
                return null;

            case 'telegram':
                // Telegram implementation
                return null;

            case 'facebook':
                // Facebook implementation
                return null;

            default:
                return null;
        }
    }

    /**
     * Get avatar/icon for channel
     */
    protected function getChannelAvatar(string $channel): string
    {
        return match ($channel) {
            'instagram' => '📸',
            'telegram' => '✈️',
            'facebook' => '👥',
            default => '💬',
        };
    }

    /**
     * Calculate response rate
     */
    protected function calculateResponseRate(Business $business): float
    {
        try {
            $total = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('direction', 'inbound')->count();

            if ($total === 0) {
                return 0;
            }

            $responded = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('direction', 'inbound')
                ->whereNotNull('response_time_ms')
                ->count();

            return ($responded / $total) * 100;
        } catch (\Exception $e) {
            // Column may not exist yet, return 0 as fallback
            return 0;
        }
    }
}
