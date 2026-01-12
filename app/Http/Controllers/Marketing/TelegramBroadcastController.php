<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Telegram\TelegramBroadcastController as BaseTelegramBroadcastController;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\Request;
use App\Models\TelegramBot;
use App\Models\TelegramBroadcast;
use App\Models\TelegramUser;

class TelegramBroadcastController extends BaseTelegramBroadcastController
{
    /**
     * List all broadcasts for a bot
     */
    public function index(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcasts = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($broadcast) => [
                'id' => $broadcast->id,
                'name' => $broadcast->name,
                'status' => $broadcast->status,
                'total_recipients' => $broadcast->total_recipients,
                'sent_count' => $broadcast->sent_count,
                'delivered_count' => $broadcast->delivered_count,
                'failed_count' => $broadcast->failed_count,
                'progress' => $broadcast->getProgressPercentage(),
                'delivery_rate' => $broadcast->getDeliveryRate(),
                'scheduled_at' => $broadcast->scheduled_at?->format('d.m.Y H:i'),
                'started_at' => $broadcast->started_at?->format('d.m.Y H:i'),
                'completed_at' => $broadcast->completed_at?->format('d.m.Y H:i'),
                'creator' => $broadcast->creator?->name,
                'created_at' => $broadcast->created_at->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Marketing/Telegram/Broadcasts/Index', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'broadcasts' => $broadcasts,
        ]);
    }

    /**
     * Show broadcast creation form
     */
    public function create(Request $request, string $botId): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        // Get user tags for filtering
        $tags = TelegramUser::where('telegram_bot_id', $bot->id)
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values();

        // Get user count
        $totalUsers = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('is_blocked', false)
            ->count();

        return Inertia::render('Marketing/Telegram/Broadcasts/Create', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'availableTags' => $tags,
            'totalUsers' => $totalUsers,
        ]);
    }
}
