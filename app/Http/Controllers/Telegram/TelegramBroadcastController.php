<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTelegramBroadcast;
use App\Models\TelegramBot;
use App\Models\TelegramBroadcast;
use App\Models\TelegramUser;
use App\Services\Telegram\TelegramApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TelegramBroadcastController extends Controller
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

        return Inertia::render('Business/Telegram/Broadcasts/Index', [
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

        return Inertia::render('Business/Telegram/Broadcasts/Create', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'availableTags' => $tags,
            'totalUsers' => $totalUsers,
        ]);
    }

    /**
     * Store new broadcast
     */
    public function store(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|array',
            'content.type' => 'required|in:text,photo,video,document',
            'content.text' => 'required_if:content.type,text|nullable|string|max:4096',
            'content.caption' => 'nullable|string|max:1024',
            'content.file_id' => 'required_unless:content.type,text|nullable|string',
            'keyboard' => 'nullable|array',
            'target_filter' => 'nullable|array',
            'target_filter.tags' => 'nullable|array',
            'target_filter.exclude_blocked' => 'nullable|boolean',
            'target_filter.active_after' => 'nullable|date',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Calculate recipients count
        $recipientsQuery = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('is_blocked', false);

        $filter = $request->target_filter ?? [];

        if (!empty($filter['tags'])) {
            $recipientsQuery->where(function ($q) use ($filter) {
                foreach ($filter['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        if (!empty($filter['active_after'])) {
            $recipientsQuery->where('last_active_at', '>=', $filter['active_after']);
        }

        $totalRecipients = $recipientsQuery->count();

        $broadcast = TelegramBroadcast::create([
            'business_id' => $business->id,
            'telegram_bot_id' => $bot->id,
            'created_by' => $request->user()->id,
            'name' => $request->name,
            'content' => $request->content,
            'keyboard' => $request->keyboard,
            'target_filter' => $request->target_filter,
            'total_recipients' => $totalRecipients,
            'status' => $request->scheduled_at ? 'scheduled' : 'draft',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return response()->json([
            'success' => true,
            'broadcast' => [
                'id' => $broadcast->id,
                'name' => $broadcast->name,
                'total_recipients' => $broadcast->total_recipients,
            ],
            'message' => 'Broadcast yaratildi',
        ]);
    }

    /**
     * Show broadcast details
     */
    public function show(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->with('creator:id,name')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'broadcast' => [
                'id' => $broadcast->id,
                'name' => $broadcast->name,
                'content' => $broadcast->content,
                'keyboard' => $broadcast->keyboard,
                'target_filter' => $broadcast->target_filter,
                'filter_description' => $broadcast->getFilterDescription(),
                'status' => $broadcast->status,
                'total_recipients' => $broadcast->total_recipients,
                'sent_count' => $broadcast->sent_count,
                'delivered_count' => $broadcast->delivered_count,
                'failed_count' => $broadcast->failed_count,
                'blocked_count' => $broadcast->blocked_count,
                'progress' => $broadcast->getProgressPercentage(),
                'delivery_rate' => $broadcast->getDeliveryRate(),
                'blocked_rate' => $broadcast->getBlockedRate(),
                'scheduled_at' => $broadcast->scheduled_at?->format('d.m.Y H:i'),
                'started_at' => $broadcast->started_at?->format('d.m.Y H:i'),
                'completed_at' => $broadcast->completed_at?->format('d.m.Y H:i'),
                'creator' => $broadcast->creator?->name,
                'created_at' => $broadcast->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Update broadcast (only draft/scheduled)
     */
    public function update(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->firstOrFail();

        if (!in_array($broadcast->status, ['draft', 'scheduled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Faqat draft yoki scheduled broadcastni o\'zgartirish mumkin',
            ], 400);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'content' => 'sometimes|array',
            'keyboard' => 'nullable|array',
            'target_filter' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Recalculate recipients if filter changed
        if ($request->has('target_filter')) {
            $recipientsQuery = TelegramUser::where('telegram_bot_id', $bot->id)
                ->where('is_blocked', false);

            $filter = $request->target_filter ?? [];

            if (!empty($filter['tags'])) {
                $recipientsQuery->where(function ($q) use ($filter) {
                    foreach ($filter['tags'] as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            if (!empty($filter['active_after'])) {
                $recipientsQuery->where('last_active_at', '>=', $filter['active_after']);
            }

            $broadcast->total_recipients = $recipientsQuery->count();
        }

        $broadcast->fill($request->only(['name', 'content', 'keyboard', 'target_filter', 'scheduled_at']));

        if ($request->has('scheduled_at') && $request->scheduled_at) {
            $broadcast->status = 'scheduled';
        }

        $broadcast->save();

        return response()->json([
            'success' => true,
            'total_recipients' => $broadcast->total_recipients,
            'message' => 'Broadcast yangilandi',
        ]);
    }

    /**
     * Start broadcast
     */
    public function start(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->firstOrFail();

        if (!$broadcast->canStart()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu broadcastni boshlash mumkin emas',
            ], 400);
        }

        $broadcast->start();

        // For small broadcasts (< 50 users), process synchronously
        // For larger broadcasts, use queue
        if ($broadcast->total_recipients < 50) {
            ProcessTelegramBroadcast::dispatchSync($broadcast->id);
        } else {
            ProcessTelegramBroadcast::dispatch($broadcast->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Broadcast boshlandi',
        ]);
    }

    /**
     * Pause broadcast
     */
    public function pause(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->firstOrFail();

        if (!$broadcast->canPause()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu broadcastni to\'xtatib bo\'lmaydi',
            ], 400);
        }

        $broadcast->pause();

        return response()->json([
            'success' => true,
            'message' => 'Broadcast to\'xtatildi',
        ]);
    }

    /**
     * Resume broadcast
     */
    public function resume(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->firstOrFail();

        if ($broadcast->status !== 'paused') {
            return response()->json([
                'success' => false,
                'message' => 'Faqat to\'xtatilgan broadcastni davom ettirish mumkin',
            ], 400);
        }

        $broadcast->resume();

        // For small broadcasts (< 50 users), process synchronously
        // For larger broadcasts, use queue
        $remaining = $broadcast->total_recipients - $broadcast->sent_count;
        if ($remaining < 50) {
            ProcessTelegramBroadcast::dispatchSync($broadcast->id);
        } else {
            ProcessTelegramBroadcast::dispatch($broadcast->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Broadcast davom ettirildi',
        ]);
    }

    /**
     * Cancel broadcast
     */
    public function cancel(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->firstOrFail();

        if (!$broadcast->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu broadcastni bekor qilib bo\'lmaydi',
            ], 400);
        }

        $broadcast->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Broadcast bekor qilindi',
        ]);
    }

    /**
     * Delete broadcast
     */
    public function destroy(Request $request, string $botId, string $broadcastId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $broadcast = TelegramBroadcast::where('telegram_bot_id', $bot->id)
            ->where('id', $broadcastId)
            ->firstOrFail();

        if ($broadcast->status === 'sending') {
            return response()->json([
                'success' => false,
                'message' => 'Yuborilayotgan broadcastni o\'chirish mumkin emas',
            ], 400);
        }

        $broadcast->delete();

        return response()->json([
            'success' => true,
            'message' => 'Broadcast o\'chirildi',
        ]);
    }

    /**
     * Preview recipients count
     */
    public function previewRecipients(Request $request, string $botId): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $botId)
            ->firstOrFail();

        $request->validate([
            'target_filter' => 'nullable|array',
        ]);

        $recipientsQuery = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('is_blocked', false);

        $filter = $request->target_filter ?? [];

        if (!empty($filter['tags'])) {
            $recipientsQuery->where(function ($q) use ($filter) {
                foreach ($filter['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        if (!empty($filter['active_after'])) {
            $recipientsQuery->where('last_active_at', '>=', $filter['active_after']);
        }

        return response()->json([
            'success' => true,
            'count' => $recipientsQuery->count(),
        ]);
    }
}
