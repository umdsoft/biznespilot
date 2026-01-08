<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Customer;
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
            if (!empty($filters['channel']) && $filters['channel'] !== 'telegram') {
                $query->where('platform', $filters['channel']);
            } elseif (empty($filters['channel'])) {
                $query->whereIn('platform', ['instagram', 'facebook']);
            }

            // Filter by status
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Search
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $chatbotConversations = $query->orderBy('last_message_at', 'desc')
                ->limit($filters['limit'] ?? 50)
                ->get()
                ->map(fn($conv) => $this->formatConversation($conv));

            $conversations = $conversations->merge($chatbotConversations);
        }

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
        if (!empty($filters['status'])) {
            $statusMap = [
                'open' => 'active',
                'pending' => 'handoff',
                'closed' => 'closed'
            ];
            $query->where('status', $statusMap[$filters['status']] ?? $filters['status']);
        }

        // Search
        if (!empty($filters['search'])) {
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
            ->map(fn($conv) => $this->formatTelegramConversation($conv));
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
            'id' => 'tg_' . $conversation->id,
            'original_id' => $conversation->id,
            'channel' => 'telegram',
            'customer_name' => $user ? trim($user->first_name . ' ' . ($user->last_name ?? '')) : 'Noma\'lum',
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
            'messages' => $conversation->messages->map(fn($msg) => [
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
            'id' => 'tg_' . $conversation->id,
            'original_id' => $conversation->id,
            'channel' => 'telegram',
            'customer' => [
                'id' => $user?->id,
                'name' => $user ? trim($user->first_name . ' ' . ($user->last_name ?? '')) : 'Noma\'lum',
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
            'messages' => $conversation->messages->sortBy('created_at')->values()->map(fn($msg) => [
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
                return "📱 Kontakt: " . $content['phone_number'];
            }
            if (isset($content['latitude'])) {
                return "📍 Lokatsiya yuborildi";
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
            'success' => (bool)$result,
            'message' => $result ? 'Message sent' : 'Failed to send',
        ];
    }

    /**
     * Send Telegram message
     */
    protected function sendTelegramMessage(string $conversationId, string $message, string $userId): array
    {
        $conversation = TelegramConversation::with(['user', 'bot'])->findOrFail($conversationId);

        if (!$conversation->bot || !$conversation->user) {
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
                'message' => 'Xatolik: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get inbox statistics
     */
    public function getInboxStats(Business $business): array
    {
        $chatbotQuery = fn() => ChatbotConversation::where('business_id', $business->id);
        $telegramQuery = fn() => TelegramConversation::where('business_id', $business->id);

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
            'is_unread' => $lastMessage?->direction === 'inbound' && !$lastMessage?->read_at,
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
        $total = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })->where('direction', 'inbound')->count();

        $responded = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })->where('direction', 'inbound')
            ->whereNotNull('response_time_ms')
            ->count();

        return $total > 0 ? ($responded / $total) * 100 : 0;
    }
}
