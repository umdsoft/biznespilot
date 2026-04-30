<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelDailyStat;
use App\Models\TelegramChannelPost;
use App\Services\Telegram\SystemBotService;
use App\Services\Telegram\TelegramChannelAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TelegramChannelsController — System Bot orqali kuzatiladigan Telegram
 * kanallari uchun web UI.
 *
 * Pattern: Instagram Analysis'ga mos keladi (Shared/InstagramAnalysis).
 * Har biznes bir nechta kanalni ulash mumkin, har birida obunachilar
 * dinamikasi, postlar vyu/reaksiyalari, engagement rate ko'rinadi.
 */
class TelegramChannelsController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected SystemBotService $bot,
        protected TelegramChannelAnalyticsService $analytics,
    ) {}

    /**
     * Kanallar ro'yxati sahifasi.
     */
    public function index(): Response
    {
        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        $channels = collect();
        if ($business) {
            $channels = TelegramChannel::query()
                ->where('business_id', $business->id)
                ->orderByDesc('is_active')
                ->orderByDesc('subscriber_count')
                ->get()
                ->map(fn (TelegramChannel $c) => $this->toListRow($c));
        }

        return Inertia::render('Business/TelegramChannels/Index', [
            'channels' => $channels,
            'botUsername' => $this->bot->getBotUsername(),
            'isSystemBotConfigured' => $this->bot->isConfigured(),
            'userTelegramLinked' => (bool) $user?->telegram_chat_id,
            'panelType' => $business ? $this->detectPanelType($business) : 'business',
        ]);
    }

    /**
     * Bitta kanal batafsil sahifasi (grafiklar + postlar jadvali).
     */
    public function show(string $id): Response
    {
        $business = $this->getCurrentBusiness();

        abort_unless($business, 404);

        $channel = TelegramChannel::query()
            ->where('business_id', $business->id)
            ->findOrFail($id);

        $last30Days = TelegramChannelDailyStat::query()
            ->where('telegram_channel_id', $channel->id)
            ->where('stat_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('stat_date')
            ->get()
            ->map(fn ($s) => [
                'date' => $s->stat_date,
                'subscriber_count' => (int) $s->subscriber_count,
                'new_subscribers' => (int) $s->new_subscribers,
                'left_subscribers' => (int) $s->left_subscribers,
                'net_growth' => (int) $s->net_growth,
                'posts_count' => (int) $s->posts_count,
                'total_views' => (int) $s->total_views,
                'engagement_rate' => (float) $s->engagement_rate,
            ])
            ->values();

        $recentPosts = TelegramChannelPost::query()
            ->where('telegram_channel_id', $channel->id)
            ->orderByDesc('posted_at')
            ->limit(25)
            ->get()
            ->map(fn (TelegramChannelPost $p) => [
                'id' => $p->id,
                'message_id' => $p->message_id,
                'posted_at' => $p->posted_at?->toIso8601String(),
                'posted_at_human' => $p->posted_at?->diffForHumans(),
                'content_type' => $p->content_type,
                'text_preview' => $p->text_preview,
                'media_url' => $p->media_url,
                'views' => (int) $p->views,
                'reactions_count' => (int) $p->reactions_count,
                'forwards_count' => (int) $p->forwards_count,
                'views_delta_24h' => (int) $p->views_delta_24h,
                'engagement_rate' => $p->engagementRate(),
                'telegram_link' => $p->telegramLink(),
            ]);

        $summary = $this->computeSummary($channel);

        return Inertia::render('Business/TelegramChannels/Show', [
            'channel' => [
                'id' => $channel->id,
                'title' => $channel->title,
                'chat_username' => $channel->chat_username,
                'public_link' => $channel->publicLink(),
                'description' => $channel->description,
                'photo_url' => $channel->photo_url,
                'subscriber_count' => (int) $channel->subscriber_count,
                'type' => $channel->type,
                'admin_status' => $channel->admin_status,
                'is_active' => (bool) $channel->is_active,
                'connected_at' => $channel->connected_at?->toIso8601String(),
                'connected_at_human' => $channel->connected_at?->diffForHumans(),
                'last_synced_at' => $channel->last_synced_at?->diffForHumans(),
            ],
            'dailyStats' => $last30Days,
            'recentPosts' => $recentPosts,
            'summary' => $summary,
            'panelType' => $this->detectPanelType($business),
        ]);
    }

    /**
     * Connect deep-link generator (JSON).
     */
    public function connectLink(): JsonResponse
    {
        $user = Auth::user();

        if (!$user?->telegram_chat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Avval Sozlamalar → Telegram orqali hisobingizni ulang.',
                'requires_telegram_link' => true,
            ], 400);
        }

        $link = $this->bot->generateChannelDeepLink();

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'System Bot sozlanmagan. Administratorga murojaat qiling.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'link' => $link,
            'bot_username' => $this->bot->getBotUsername(),
            'steps' => [
                'Quyidagi havolani bosing.',
                "Ochilgan ro'yxatdan kanalni tanlang.",
                "Botga faqat «Kanalni boshqarish» huquqini bering — post yozish shart emas.",
                "Bot avtomatik ulanadi — tasdiq xabari Telegram orqali keladi.",
            ],
        ]);
    }

    /**
     * Channel'ni uzish — bot chatdan chiqadi va local status = left bo'ladi.
     */
    public function disconnect(string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        abort_unless($business, 404);

        $channel = TelegramChannel::query()
            ->where('business_id', $business->id)
            ->findOrFail($id);

        $this->bot->leaveChat($channel->telegram_chat_id);
        $channel->markDisconnected(TelegramChannel::STATUS_LEFT);

        return response()->json([
            'success' => true,
            'message' => "«{$channel->title}» kanali uzildi.",
        ]);
    }

    /**
     * Manual refresh — admin "Yangilash" tugmasini bossa ishlatiladi.
     */
    public function refresh(string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        abort_unless($business, 404);

        $channel = TelegramChannel::query()
            ->where('business_id', $business->id)
            ->findOrFail($id);

        $this->analytics->syncChannelCore($channel);
        $this->analytics->snapshotRecentPosts($channel);

        $channel->refresh();

        return response()->json([
            'success' => true,
            'subscriber_count' => (int) $channel->subscriber_count,
            'last_synced_at' => $channel->last_synced_at?->diffForHumans(),
        ]);
    }

    // =================================================================
    // Helpers
    // =================================================================

    private function toListRow(TelegramChannel $c): array
    {
        // Har kanal uchun oxirgi kunlik stat (agar bor bo'lsa — UI'da badge)
        $todayStat = Cache::remember(
            "tgch:row:{$c->id}",
            60,
            function () use ($c) {
                $today = now()->toDateString();
                return TelegramChannelDailyStat::where('telegram_channel_id', $c->id)
                    ->where('stat_date', $today)
                    ->first();
            }
        );

        // Oxirgi 7 kun — net_growth sum (UI trend uchun)
        $weekGrowth = (int) TelegramChannelDailyStat::where('telegram_channel_id', $c->id)
            ->where('stat_date', '>=', now()->subDays(7)->toDateString())
            ->sum('net_growth');

        return [
            'id' => $c->id,
            'title' => $c->title,
            'username' => $c->chat_username,
            'public_link' => $c->publicLink(),
            'photo_url' => $c->photo_url,
            'subscriber_count' => (int) $c->subscriber_count,
            'type' => $c->type,
            'admin_status' => $c->admin_status,
            'is_active' => (bool) $c->is_active,
            'connected_at' => $c->connected_at?->diffForHumans(),
            'last_synced_at' => $c->last_synced_at?->diffForHumans(),
            'today_new_subscribers' => (int) ($todayStat->new_subscribers ?? 0),
            'today_net_growth' => (int) ($todayStat->net_growth ?? 0),
            'today_posts' => (int) ($todayStat->posts_count ?? 0),
            'today_views' => (int) ($todayStat->total_views ?? 0),
            'today_engagement_rate' => (float) ($todayStat->engagement_rate ?? 0),
            'week_net_growth' => $weekGrowth,
        ];
    }

    private function computeSummary(TelegramChannel $channel): array
    {
        $now = Carbon::now();
        $weekAgo = $now->copy()->subDays(7)->toDateString();
        $monthAgo = $now->copy()->subDays(30)->toDateString();

        $weekStats = TelegramChannelDailyStat::query()
            ->where('telegram_channel_id', $channel->id)
            ->where('stat_date', '>=', $weekAgo)
            ->selectRaw('
                SUM(new_subscribers) as total_new,
                SUM(left_subscribers) as total_left,
                SUM(net_growth) as total_net,
                SUM(posts_count) as total_posts,
                SUM(total_views) as total_views,
                SUM(total_reactions) as total_reactions,
                AVG(engagement_rate) as avg_engagement
            ')
            ->first();

        $monthStats = TelegramChannelDailyStat::query()
            ->where('telegram_channel_id', $channel->id)
            ->where('stat_date', '>=', $monthAgo)
            ->selectRaw('
                SUM(new_subscribers) as total_new,
                SUM(left_subscribers) as total_left,
                SUM(net_growth) as total_net,
                SUM(posts_count) as total_posts,
                SUM(total_views) as total_views,
                AVG(engagement_rate) as avg_engagement
            ')
            ->first();

        $topPost = TelegramChannelPost::query()
            ->where('telegram_channel_id', $channel->id)
            ->where('posted_at', '>=', $now->copy()->subDays(30))
            ->orderByDesc('views')
            ->first();

        return [
            'week' => [
                'new_subscribers' => (int) ($weekStats->total_new ?? 0),
                'left_subscribers' => (int) ($weekStats->total_left ?? 0),
                'net_growth' => (int) ($weekStats->total_net ?? 0),
                'posts' => (int) ($weekStats->total_posts ?? 0),
                'views' => (int) ($weekStats->total_views ?? 0),
                'reactions' => (int) ($weekStats->total_reactions ?? 0),
                'engagement_rate' => round((float) ($weekStats->avg_engagement ?? 0), 2),
            ],
            'month' => [
                'new_subscribers' => (int) ($monthStats->total_new ?? 0),
                'left_subscribers' => (int) ($monthStats->total_left ?? 0),
                'net_growth' => (int) ($monthStats->total_net ?? 0),
                'posts' => (int) ($monthStats->total_posts ?? 0),
                'views' => (int) ($monthStats->total_views ?? 0),
                'engagement_rate' => round((float) ($monthStats->avg_engagement ?? 0), 2),
            ],
            'top_post_30d' => $topPost ? [
                'id' => $topPost->id,
                'text_preview' => $topPost->text_preview,
                'content_type' => $topPost->content_type,
                'media_url' => $topPost->media_url,
                'views' => (int) $topPost->views,
                'reactions_count' => (int) $topPost->reactions_count,
                'telegram_link' => $topPost->telegramLink(),
                'posted_at_human' => $topPost->posted_at?->diffForHumans(),
            ] : null,
        ];
    }
}
