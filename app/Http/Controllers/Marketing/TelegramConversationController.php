<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Telegram\TelegramConversationController as BaseTelegramConversationController;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\Request;
use App\Models\TelegramBot;
use App\Models\TelegramConversation;
use App\Models\TelegramMessage;

class TelegramConversationController extends BaseTelegramConversationController
{
    /**
     * List all conversations for a bot
     */
    public function index(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $query = TelegramConversation::where('telegram_bot_id', $bot->id)
            ->with(['user:id,telegram_id,username,first_name,last_name', 'assignedOperator:id,name']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by assigned operator
        if ($request->has('operator_id')) {
            if ($request->operator_id === 'unassigned') {
                $query->whereNull('assigned_operator_id');
            } else {
                $query->where('assigned_operator_id', $request->operator_id);
            }
        }

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->paginate(50)
            ->through(fn($conv) => [
                'id' => $conv->id,
                'user' => $conv->user ? [
                    'id' => $conv->user->id,
                    'username' => $conv->user->username,
                    'full_name' => $conv->user->getFullName(),
                ] : null,
                'status' => $conv->status,
                'assigned_operator' => $conv->assignedOperator?->name,
                'unread_count' => $conv->getUnreadMessagesCount(),
                'last_message_at' => $conv->last_message_at?->format('d.m.Y H:i'),
                'handoff_at' => $conv->handoff_at?->format('d.m.Y H:i'),
                'handoff_reason' => $conv->handoff_reason,
                'tags' => $conv->tags ?? [],
                'started_at' => $conv->started_at->format('d.m.Y H:i'),
            ]);

        // Get stats
        $stats = [
            'total' => TelegramConversation::where('telegram_bot_id', $bot->id)->count(),
            'active' => TelegramConversation::where('telegram_bot_id', $bot->id)->where('status', 'active')->count(),
            'handoff' => TelegramConversation::where('telegram_bot_id', $bot->id)->where('status', 'handoff')->count(),
            'unassigned_handoff' => TelegramConversation::where('telegram_bot_id', $bot->id)
                ->where('status', 'handoff')
                ->whereNull('assigned_operator_id')
                ->count(),
        ];

        return Inertia::render('Marketing/Telegram/Conversations/Index', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'conversations' => $conversations,
            'stats' => $stats,
            'filters' => $request->only(['status', 'operator_id']),
        ]);
    }

    /**
     * Show conversation with messages
     */
    public function show(Request $request, string $botId, string $conversationId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $conversation = TelegramConversation::where('telegram_bot_id', $bot->id)
            ->where('id', $conversationId)
            ->with(['user', 'assignedOperator:id,name', 'lead'])
            ->firstOrFail();

        // Get messages
        $messages = TelegramMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction,
                'sender_type' => $msg->sender_type,
                'content_type' => $msg->content_type,
                'content' => $msg->content,
                'keyboard' => $msg->keyboard,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at->format('d.m.Y H:i:s'),
            ]);

        // Mark messages as read
        $conversation->markAllAsRead();

        return Inertia::render('Marketing/Telegram/Conversations/Show', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'assigned_operator' => $conversation->assignedOperator?->name,
                'handoff_reason' => $conversation->handoff_reason,
                'tags' => $conversation->tags ?? [],
                'started_at' => $conversation->started_at->format('d.m.Y H:i'),
                'last_message_at' => $conversation->last_message_at?->format('d.m.Y H:i'),
            ],
            'user' => [
                'id' => $conversation->user->id,
                'telegram_id' => $conversation->user->telegram_id,
                'username' => $conversation->user->username,
                'full_name' => $conversation->user->getFullName(),
                'phone' => $conversation->user->phone,
                'tags' => $conversation->user->tags ?? [],
            ],
            'messages' => $messages,
            'lead' => $conversation->lead ? [
                'id' => $conversation->lead->id,
                'name' => $conversation->lead->name,
                'status' => $conversation->lead->status,
            ] : null,
        ]);
    }
}
