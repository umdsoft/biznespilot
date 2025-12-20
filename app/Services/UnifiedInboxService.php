<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Customer;
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
        $query = ChatbotConversation::where('business_id', $business->id)
            ->with(['customer', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }]);

        // Filter by channel
        if (!empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
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

        return $query->orderBy('last_message_at', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn($conv) => $this->formatConversation($conv));
    }

    /**
     * Get conversation details with full message history
     */
    public function getConversationDetails(int $conversationId): array
    {
        $conversation = ChatbotConversation::with(['customer', 'messages'])->findOrFail($conversationId);

        return [
            'id' => $conversation->id,
            'channel' => $conversation->channel,
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
     * Send message from unified inbox
     */
    public function sendMessage(int $conversationId, string $message, string $userId): array
    {
        $conversation = ChatbotConversation::findOrFail($conversationId);
        $customer = $conversation->customer;

        // Send via appropriate channel
        $result = $this->sendViaChannel($conversation->channel, $customer, $message);

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
     * Get inbox statistics
     */
    public function getInboxStats(Business $business): array
    {
        $conversations = ChatbotConversation::where('business_id', $business->id);

        return [
            'total' => $conversations->count(),
            'open' => $conversations->where('status', 'open')->count(),
            'pending' => $conversations->where('status', 'pending')->count(),
            'closed' => $conversations->where('status', 'closed')->count(),
            'by_channel' => [
                'whatsapp' => $conversations->where('channel', 'whatsapp')->count(),
                'instagram' => $conversations->where('channel', 'instagram')->count(),
                'telegram' => $conversations->where('channel', 'telegram')->count(),
                'facebook' => $conversations->where('channel', 'facebook')->count(),
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
            'channel' => $conversation->channel,
            'customer_name' => $conversation->customer->name,
            'customer_avatar' => $this->getChannelAvatar($conversation->channel),
            'last_message' => $lastMessage?->message_content,
            'last_message_time' => $conversation->last_message_at?->diffForHumans(),
            'status' => $conversation->status,
            'is_unread' => $lastMessage?->direction === 'incoming' && !$lastMessage?->is_read,
            'message_count' => $conversation->message_count,
        ];
    }

    /**
     * Send message via appropriate channel service
     */
    protected function sendViaChannel(string $channel, Customer $customer, string $message): ?array
    {
        switch ($channel) {
            case 'whatsapp':
                $service = app(WhatsAppService::class);
                return $service->sendTextMessage($customer->phone, $message);

            case 'instagram':
                $service = app(InstagramDMService::class);
                return $service->sendMessage($customer->phone, $message);

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
            'whatsapp' => '💬',
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
        })->where('direction', 'incoming')->count();

        $responded = ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })->where('direction', 'incoming')
            ->whereNotNull('response_time_seconds')
            ->count();

        return $total > 0 ? ($responded / $total) * 100 : 0;
    }
}
