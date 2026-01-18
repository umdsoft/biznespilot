<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Telegram\TelegramUserController as BaseTelegramUserController;
use App\Models\TelegramBot;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TelegramUserController extends BaseTelegramUserController
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
            ->through(fn ($user) => [
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

        return Inertia::render('Marketing/Telegram/Users/Index', [
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
}
