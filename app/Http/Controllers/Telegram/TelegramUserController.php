<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TelegramUserController extends Controller
{
    /**
     * List all users for a bot
     */
    public function index(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $query = TelegramUser::where('telegram_bot_id', $bot->id);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'blocked') {
                $query->where('is_blocked', true);
            } elseif ($request->status === 'active') {
                $query->where('is_blocked', false);
            }
        }

        // Filter by tag
        if ($request->has('tag') && $request->tag) {
            $query->whereJsonContains('tags', $request->tag);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('last_interaction_at', 'desc')
            ->paginate(50)
            ->through(fn($user) => [
                'id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->getFullName(),
                'phone' => $user->phone,
                'is_blocked' => $user->is_blocked,
                'tags' => $user->tags ?? [],
                'last_active_at' => $user->last_interaction_at?->format('d.m.Y H:i'),
                'created_at' => $user->created_at->format('d.m.Y'),
            ]);

        // Get all tags for filtering
        $allTags = TelegramUser::where('telegram_bot_id', $bot->id)
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values();

        // Get stats
        $stats = [
            'total' => TelegramUser::where('telegram_bot_id', $bot->id)->count(),
            'active' => TelegramUser::where('telegram_bot_id', $bot->id)->where('is_blocked', false)->count(),
            'blocked' => TelegramUser::where('telegram_bot_id', $bot->id)->where('is_blocked', true)->count(),
        ];

        return Inertia::render('Business/Telegram/Users/Index', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'users' => $users,
            'allTags' => $allTags,
            'stats' => $stats,
            'filters' => $request->only(['status', 'tag', 'search']),
        ]);
    }

    /**
     * Show user details
     */
    public function show(Request $request, string $botId, string $userId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('id', $userId)
            ->with(['state', 'lead'])
            ->firstOrFail();

        // Get recent messages
        $recentMessages = $user->conversations()
            ->with(['messages' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(20);
            }])
            ->first()
            ?->messages
            ->map(fn($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction,
                'sender_type' => $msg->sender_type,
                'content_type' => $msg->content_type,
                'content' => $msg->content,
                'created_at' => $msg->created_at->format('d.m.Y H:i'),
            ]) ?? [];

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->getFullName(),
                'phone' => $user->phone,
                'language_code' => $user->language_code,
                'is_blocked' => $user->is_blocked,
                'blocked_at' => $user->blocked_at?->format('d.m.Y H:i'),
                'tags' => $user->tags ?? [],
                'custom_data' => $user->custom_data ?? [],
                'last_active_at' => $user->last_active_at?->format('d.m.Y H:i'),
                'created_at' => $user->created_at->format('d.m.Y H:i'),
                'state' => $user->state ? [
                    'current_funnel_id' => $user->state->current_funnel_id,
                    'current_step_id' => $user->state->current_step_id,
                    'waiting_for' => $user->state->waiting_for,
                    'collected_data' => $user->state->collected_data,
                ] : null,
                'lead' => $user->lead ? [
                    'id' => $user->lead->id,
                    'name' => $user->lead->name,
                    'status' => $user->lead->status,
                ] : null,
            ],
            'recentMessages' => $recentMessages,
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, string $botId, string $userId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('id', $userId)
            ->firstOrFail();

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'custom_data' => 'nullable|array',
        ]);

        $user->update($request->only(['phone', 'tags', 'custom_data']));

        return response()->json([
            'success' => true,
            'message' => 'Foydalanuvchi yangilandi',
        ]);
    }

    /**
     * Add tag to user
     */
    public function addTag(Request $request, string $botId, string $userId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('id', $userId)
            ->firstOrFail();

        $request->validate([
            'tag' => 'required|string|max:50',
        ]);

        $user->addTag($request->tag);

        return response()->json([
            'success' => true,
            'tags' => $user->fresh()->tags,
            'message' => 'Teg qo\'shildi',
        ]);
    }

    /**
     * Remove tag from user
     */
    public function removeTag(Request $request, string $botId, string $userId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('id', $userId)
            ->firstOrFail();

        $request->validate([
            'tag' => 'required|string|max:50',
        ]);

        $user->removeTag($request->tag);

        return response()->json([
            'success' => true,
            'tags' => $user->fresh()->tags,
            'message' => 'Teg o\'chirildi',
        ]);
    }

    /**
     * Bulk add tags
     */
    public function bulkAddTags(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'uuid|exists:telegram_users,id',
            'tag' => 'required|string|max:50',
        ]);

        $users = TelegramUser::where('telegram_bot_id', $bot->id)
            ->whereIn('id', $request->user_ids)
            ->get();

        foreach ($users as $user) {
            $user->addTag($request->tag);
        }

        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'message' => $users->count() . ' ta foydalanuvchiga teg qo\'shildi',
        ]);
    }

    /**
     * Reset user state
     */
    public function resetState(Request $request, string $botId, string $userId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('id', $userId)
            ->firstOrFail();

        if ($user->state) {
            $user->state->reset();
        }

        return response()->json([
            'success' => true,
            'message' => 'Foydalanuvchi holati tozalandi',
        ]);
    }

    /**
     * Export users
     */
    public function export(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $query = TelegramUser::where('telegram_bot_id', $bot->id);

        // Apply filters
        if ($request->has('status')) {
            if ($request->status === 'blocked') {
                $query->where('is_blocked', true);
            } elseif ($request->status === 'active') {
                $query->where('is_blocked', false);
            }
        }

        if ($request->has('tag') && $request->tag) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $users = $query->get()->map(fn($user) => [
            'telegram_id' => $user->telegram_id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'language' => $user->language_code,
            'is_blocked' => $user->is_blocked ? 'Ha' : 'Yo\'q',
            'tags' => implode(', ', $user->tags ?? []),
            'last_active' => $user->last_active_at?->format('Y-m-d H:i:s'),
            'joined' => $user->created_at->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => $users->count(),
        ]);
    }
}
