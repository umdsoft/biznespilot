<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ChatbotHandoffService
{
    protected NotificationService $notificationService;
    protected TelegramBotService $telegramService;

    /**
     * Conversation statuses.
     */
    public const STATUS_BOT = 'bot';
    public const STATUS_HANDOFF_REQUESTED = 'handoff_requested';
    public const STATUS_HANDOFF_PENDING = 'handoff_pending';
    public const STATUS_LIVE = 'live';
    public const STATUS_CLOSED = 'closed';

    /**
     * Handoff trigger keywords.
     */
    protected array $handoffKeywords = [
        'operator',
        'оператор',
        'человек',
        'менеджер',
        'menejer',
        'menejir',
        'manager',
        'jonli',
        'живой',
        'support',
        'yordam',
        'помощь',
        'человеком',
        'operatorga',
        'bog\'lash',
        'bog\'lang',
        'call',
        'qo\'ng\'iroq',
        'звонок',
    ];

    public function __construct(
        NotificationService $notificationService,
        TelegramBotService $telegramService
    ) {
        $this->notificationService = $notificationService;
        $this->telegramService = $telegramService;
    }

    /**
     * Check if message triggers handoff.
     */
    public function shouldTriggerHandoff(string $message): bool
    {
        $lowerMessage = mb_strtolower($message);

        foreach ($this->handoffKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Request handoff to live operator.
     */
    public function requestHandoff(
        ChatbotConversation $conversation,
        string $reason = 'user_requested'
    ): array {
        // Check if already in live mode
        if ($conversation->status === self::STATUS_LIVE) {
            return [
                'success' => false,
                'message' => 'Suhbat allaqachon operatorga ulangan',
            ];
        }

        // Update conversation status
        $conversation->update([
            'status' => self::STATUS_HANDOFF_REQUESTED,
            'metadata' => array_merge($conversation->metadata ?? [], [
                'handoff_requested_at' => now()->toIso8601String(),
                'handoff_reason' => $reason,
            ]),
        ]);

        // Find available operator
        $operator = $this->findAvailableOperator($conversation->business);

        if ($operator) {
            return $this->assignToOperator($conversation, $operator);
        }

        // No operator available - add to queue
        $queuePosition = $this->getQueuePosition($conversation);

        $conversation->update([
            'status' => self::STATUS_HANDOFF_PENDING,
        ]);

        // Notify admins about pending handoff
        $this->notifyAdminsAboutPendingHandoff($conversation);

        Log::info('Handoff requested but no operators available', [
            'conversation_id' => $conversation->id,
            'queue_position' => $queuePosition,
        ]);

        return [
            'success' => true,
            'status' => 'queued',
            'queue_position' => $queuePosition,
            'message' => $this->getQueueMessage($queuePosition),
        ];
    }

    /**
     * Assign conversation to operator.
     */
    public function assignToOperator(
        ChatbotConversation $conversation,
        User $operator
    ): array {
        $conversation->update([
            'status' => self::STATUS_LIVE,
            'assigned_to' => $operator->id,
            'metadata' => array_merge($conversation->metadata ?? [], [
                'handoff_completed_at' => now()->toIso8601String(),
                'operator_name' => $operator->name,
            ]),
        ]);

        // Create system message about handoff
        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => "Suhbat {$operator->name} ga o'tkazildi.",
            'is_processed' => true,
        ]);

        // Notify operator
        $this->notifyOperator($conversation, $operator);

        Log::info('Conversation assigned to operator', [
            'conversation_id' => $conversation->id,
            'operator_id' => $operator->id,
        ]);

        return [
            'success' => true,
            'status' => 'assigned',
            'operator' => [
                'id' => $operator->id,
                'name' => $operator->name,
            ],
            'message' => $this->getHandoffSuccessMessage($operator),
        ];
    }

    /**
     * Transfer conversation to another operator.
     */
    public function transferToOperator(
        ChatbotConversation $conversation,
        User $newOperator,
        ?string $note = null
    ): array {
        $previousOperator = $conversation->assignedAgent;

        $conversation->update([
            'assigned_to' => $newOperator->id,
            'metadata' => array_merge($conversation->metadata ?? [], [
                'transferred_at' => now()->toIso8601String(),
                'transferred_from' => $previousOperator?->id,
                'transfer_note' => $note,
            ]),
        ]);

        // Create system message about transfer
        $transferMessage = "Suhbat {$previousOperator?->name} dan {$newOperator->name} ga o'tkazildi.";
        if ($note) {
            $transferMessage .= " Izoh: {$note}";
        }

        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => $transferMessage,
            'is_processed' => true,
        ]);

        // Notify new operator
        $this->notifyOperator($conversation, $newOperator);

        return [
            'success' => true,
            'message' => 'Suhbat muvaffaqiyatli o\'tkazildi',
        ];
    }

    /**
     * Return conversation to bot.
     */
    public function returnToBot(
        ChatbotConversation $conversation,
        ?string $closingMessage = null
    ): array {
        $operator = $conversation->assignedAgent;

        $conversation->update([
            'status' => self::STATUS_BOT,
            'assigned_to' => null,
            'metadata' => array_merge($conversation->metadata ?? [], [
                'returned_to_bot_at' => now()->toIso8601String(),
                'returned_by' => $operator?->id,
            ]),
        ]);

        // Create system message
        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => 'Suhbat botga qaytarildi.',
            'is_processed' => true,
        ]);

        return [
            'success' => true,
            'message' => 'Suhbat botga qaytarildi',
        ];
    }

    /**
     * Close conversation.
     */
    public function closeConversation(
        ChatbotConversation $conversation,
        ?string $resolution = null
    ): array {
        $conversation->update([
            'status' => self::STATUS_CLOSED,
            'ended_at' => now(),
            'metadata' => array_merge($conversation->metadata ?? [], [
                'closed_at' => now()->toIso8601String(),
                'closed_by' => auth()->id(),
                'resolution' => $resolution,
            ]),
        ]);

        // Create system message
        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => 'Suhbat yakunlandi.',
            'is_processed' => true,
        ]);

        return [
            'success' => true,
            'message' => 'Suhbat yakunlandi',
        ];
    }

    /**
     * Send message as operator.
     */
    public function sendOperatorMessage(
        ChatbotConversation $conversation,
        User $operator,
        string $content,
        array $attachments = []
    ): ChatbotMessage {
        // Create message
        $message = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'operator',
            'content' => $content,
            'sent_by' => $operator->id,
            'is_processed' => true,
            'metadata' => [
                'operator_name' => $operator->name,
                'attachments' => $attachments,
            ],
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
        ]);

        // Send to customer via appropriate channel
        $this->deliverMessageToCustomer($conversation, $content, $attachments);

        return $message;
    }

    /**
     * Find available operator for handoff.
     */
    protected function findAvailableOperator(Business $business): ?User
    {
        // Get all operators for the business
        $operators = $business->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['operator', 'admin', 'owner', 'manager']);
            })
            ->get();

        if ($operators->isEmpty()) {
            return null;
        }

        // Find operator with least active conversations
        $operatorLoads = [];

        foreach ($operators as $operator) {
            $activeConversations = ChatbotConversation::where('business_id', $business->id)
                ->where('status', self::STATUS_LIVE)
                ->where('assigned_to', $operator->id)
                ->count();

            $operatorLoads[$operator->id] = [
                'user' => $operator,
                'load' => $activeConversations,
            ];
        }

        // Sort by load (ascending)
        uasort($operatorLoads, fn ($a, $b) => $a['load'] <=> $b['load']);

        // Return operator with least load (if load is below threshold)
        $leastLoaded = reset($operatorLoads);

        // Max 5 concurrent conversations per operator
        if ($leastLoaded['load'] < 5) {
            return $leastLoaded['user'];
        }

        return null;
    }

    /**
     * Get queue position for pending handoff.
     */
    protected function getQueuePosition(ChatbotConversation $conversation): int
    {
        return ChatbotConversation::where('business_id', $conversation->business_id)
            ->where('status', self::STATUS_HANDOFF_PENDING)
            ->where('id', '<=', $conversation->id)
            ->count();
    }

    /**
     * Get queue message for customer.
     */
    protected function getQueueMessage(int $position): string
    {
        if ($position === 1) {
            return "Siz navbatda birinchisiz. Operator tez orada sizga javob beradi.";
        }

        return "Siz navbatda {$position}-o'rindasz. Operator tez orada sizga javob beradi.";
    }

    /**
     * Get handoff success message.
     */
    protected function getHandoffSuccessMessage(User $operator): string
    {
        return "Siz {$operator->name} bilan ulangansiz. Qanday yordam bera olaman?";
    }

    /**
     * Notify operator about new conversation.
     */
    protected function notifyOperator(ChatbotConversation $conversation, User $operator): void
    {
        try {
            $business = $conversation->business;

            $this->notificationService->sendToUser(
                $operator,
                'chat_handoff',
                'Yangi suhbat',
                "{$conversation->customer_name} dan yangi suhbat o'tkazildi.",
                [
                    'icon' => 'chat-bubble-left-right',
                    'action_url' => "/chat/conversations/{$conversation->id}",
                    'action_text' => 'Ochish',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify operator about handoff', [
                'conversation_id' => $conversation->id,
                'operator_id' => $operator->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins about pending handoff (no operators available).
     */
    protected function notifyAdminsAboutPendingHandoff(ChatbotConversation $conversation): void
    {
        try {
            $business = $conversation->business;

            $this->notificationService->sendSystemNotification(
                $business,
                'Operator kerak',
                "Mijoz operator bilan bog'lanishni so'ramoqda, lekin hech bir operator mavjud emas."
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify admins about pending handoff', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deliver message to customer via channel.
     */
    protected function deliverMessageToCustomer(
        ChatbotConversation $conversation,
        string $content,
        array $attachments = []
    ): void {
        $config = $conversation->config;
        $channel = $config?->channel ?? 'telegram';

        try {
            switch ($channel) {
                case 'telegram':
                    $chatId = $conversation->external_id;
                    if ($chatId) {
                        $this->telegramService->sendMessage($chatId, $content);
                    }
                    break;

                case 'instagram':
                    // Implement Instagram message sending
                    break;

                case 'web':
                    // Web chat is handled via websockets/polling
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to deliver message to customer', [
                'conversation_id' => $conversation->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get pending handoffs for a business.
     */
    public function getPendingHandoffs(Business $business): Collection
    {
        return ChatbotConversation::where('business_id', $business->id)
            ->whereIn('status', [self::STATUS_HANDOFF_REQUESTED, self::STATUS_HANDOFF_PENDING])
            ->orderBy('updated_at', 'asc')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->get();
    }

    /**
     * Get active live conversations for a business.
     */
    public function getActiveConversations(Business $business, ?User $operator = null): Collection
    {
        $query = ChatbotConversation::where('business_id', $business->id)
            ->where('status', self::STATUS_LIVE);

        if ($operator) {
            $query->where('assigned_to', $operator->id);
        }

        return $query->orderBy('last_message_at', 'desc')
            ->with(['messages' => fn ($q) => $q->latest()->limit(5), 'assignedAgent'])
            ->get();
    }

    /**
     * Get handoff statistics.
     */
    public function getHandoffStats(Business $business, int $days = 30): array
    {
        $since = now()->subDays($days);

        $conversations = ChatbotConversation::where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->get();

        $totalHandoffs = $conversations->whereIn('status', [
            self::STATUS_HANDOFF_REQUESTED,
            self::STATUS_HANDOFF_PENDING,
            self::STATUS_LIVE,
            self::STATUS_CLOSED,
        ])->filter(function ($c) {
            return isset($c->metadata['handoff_requested_at']);
        })->count();

        $successfulHandoffs = $conversations->filter(function ($c) {
            return isset($c->metadata['handoff_completed_at']);
        })->count();

        return [
            'total_handoffs' => $totalHandoffs,
            'successful_handoffs' => $successfulHandoffs,
            'pending_handoffs' => $conversations->where('status', self::STATUS_HANDOFF_PENDING)->count(),
            'active_live_chats' => $conversations->where('status', self::STATUS_LIVE)->count(),
            'success_rate' => $totalHandoffs > 0
                ? round(($successfulHandoffs / $totalHandoffs) * 100, 1)
                : 0,
            'average_wait_time' => $this->calculateAverageWaitTime($conversations),
        ];
    }

    /**
     * Calculate average wait time for handoffs.
     */
    protected function calculateAverageWaitTime(Collection $conversations): ?string
    {
        $waitTimes = $conversations->filter(function ($c) {
            return isset($c->metadata['handoff_requested_at'])
                && isset($c->metadata['handoff_completed_at']);
        })->map(function ($c) {
            $requested = \Carbon\Carbon::parse($c->metadata['handoff_requested_at']);
            $completed = \Carbon\Carbon::parse($c->metadata['handoff_completed_at']);
            return $requested->diffInMinutes($completed);
        });

        if ($waitTimes->isEmpty()) {
            return null;
        }

        $avgMinutes = round($waitTimes->average());

        if ($avgMinutes < 60) {
            return "{$avgMinutes} daqiqa";
        }

        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;

        return "{$hours} soat {$minutes} daqiqa";
    }
}
