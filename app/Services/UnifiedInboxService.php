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

        // Filter by channel (uses 'platform' column in database)
        if (!empty($filters['channel'])) {
            $query->where('platform', $filters['channel']);
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
     * Send message from unified inbox
     */
    public function sendMessage(int $conversationId, string $message, string $userId): array
    {
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
     * Get inbox statistics
     */
    public function getInboxStats(Business $business): array
    {
        $baseQuery = fn() => ChatbotConversation::where('business_id', $business->id);

        return [
            'total' => $baseQuery()->count(),
            'open' => $baseQuery()->where('status', 'open')->count(),
            'pending' => $baseQuery()->where('status', 'pending')->count(),
            'closed' => $baseQuery()->where('status', 'closed')->count(),
            'by_channel' => [
                'instagram' => $baseQuery()->where('platform', 'instagram')->count(),
                'telegram' => $baseQuery()->where('platform', 'telegram')->count(),
                'facebook' => $baseQuery()->where('platform', 'facebook')->count(),
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
