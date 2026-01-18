<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Campaign;
use App\Models\ContentCalendar;
use App\Models\Lead;
use App\Services\ExportService;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    use HasCurrentBusiness;

    protected SalesAnalyticsService $analyticsService;

    protected ExportService $exportService;

    protected int $cacheTTL = 300;

    public function __construct(
        SalesAnalyticsService $analyticsService,
        ExportService $exportService
    ) {
        $this->analyticsService = $analyticsService;
        $this->exportService = $exportService;
    }

    /**
     * Detect panel type from route prefix
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) {
            return 'marketing';
        }
        if (str_contains($prefix, 'finance')) {
            return 'finance';
        }
        if (str_contains($prefix, 'operator')) {
            return 'operator';
        }
        if (str_contains($prefix, 'saleshead')) {
            return 'saleshead';
        }

        return 'business';
    }

    /**
     * Get route prefix for redirects
     */
    protected function getRoutePrefix(string $panelType): string
    {
        return match ($panelType) {
            'marketing' => 'marketing',
            'finance' => 'finance',
            'operator' => 'operator',
            'saleshead' => 'saleshead',
            default => 'business',
        };
    }

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        // Period filter
        $period = $request->get('period', '30'); // days

        // Overview metrics from real data
        $overview = $this->getOverviewStats($business->id, $period);

        // Channel performance from content calendar
        $channels = $this->getChannelStats($business->id, $period);

        // Top performing content
        $topContent = $this->getTopContent($business->id, $period);

        // Campaign performance
        $campaignPerformance = $this->getCampaignPerformance($business->id, $period);

        // Trends data for charts
        $trends = $this->getTrends($business->id, $period);

        // Lead sources
        $leadSources = $this->getLeadSources($business->id, $period);

        return Inertia::render('Shared/Analytics/Index', [
            'overview' => $overview,
            'channels' => $channels,
            'topContent' => $topContent,
            'campaignPerformance' => $campaignPerformance,
            'trends' => $trends,
            'leadSources' => $leadSources,
            'period' => $period,
            'panelType' => $panelType,
        ]);
    }

    private function getOverviewStats($businessId, $period): array
    {
        $startDate = now()->subDays((int) $period);

        // Content metrics
        $totalReach = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->sum('reach') ?? 0;

        $totalViews = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->sum('views') ?? 0;

        $totalLikes = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->sum('likes') ?? 0;

        $totalComments = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->sum('comments') ?? 0;

        $totalShares = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->sum('shares') ?? 0;

        $totalEngagement = $totalLikes + $totalComments + $totalShares;

        // Average engagement rate
        $avgEngagement = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('engagement_rate')
            ->avg('engagement_rate') ?? 0;

        // Content published
        $contentPublished = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Leads generated
        $leadsGenerated = Lead::where('business_id', $businessId)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Previous period comparison
        $prevStartDate = now()->subDays((int) $period * 2);
        $prevEndDate = now()->subDays((int) $period);

        $prevReach = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('reach') ?? 0;

        $prevLeads = Lead::where('business_id', $businessId)
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->count();

        $reachGrowth = $prevReach > 0 ? round((($totalReach - $prevReach) / $prevReach) * 100, 1) : 0;
        $leadsGrowth = $prevLeads > 0 ? round((($leadsGenerated - $prevLeads) / $prevLeads) * 100, 1) : 0;

        return [
            'total_reach' => $totalReach,
            'total_views' => $totalViews,
            'total_engagement' => $totalEngagement,
            'engagement_rate' => round($avgEngagement, 2),
            'content_published' => $contentPublished,
            'leads_generated' => $leadsGenerated,
            'likes' => $totalLikes,
            'comments' => $totalComments,
            'shares' => $totalShares,
            'reach_growth' => $reachGrowth,
            'leads_growth' => $leadsGrowth,
        ];
    }

    private function getChannelStats($businessId, $period): array
    {
        $startDate = now()->subDays((int) $period);

        $channels = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->select('channel')
            ->selectRaw('COUNT(*) as posts')
            ->selectRaw('SUM(reach) as reach')
            ->selectRaw('SUM(views) as views')
            ->selectRaw('SUM(likes) as likes')
            ->selectRaw('SUM(comments) as comments')
            ->selectRaw('SUM(shares) as shares')
            ->selectRaw('AVG(engagement_rate) as engagement_rate')
            ->groupBy('channel')
            ->orderByDesc('reach')
            ->get()
            ->map(function ($channel) {
                return [
                    'name' => $this->getChannelName($channel->channel),
                    'key' => $channel->channel,
                    'posts' => $channel->posts ?? 0,
                    'reach' => $channel->reach ?? 0,
                    'views' => $channel->views ?? 0,
                    'likes' => $channel->likes ?? 0,
                    'comments' => $channel->comments ?? 0,
                    'shares' => $channel->shares ?? 0,
                    'engagement_rate' => round($channel->engagement_rate ?? 0, 2),
                ];
            })
            ->toArray();

        return $channels;
    }

    private function getChannelName(string $key): string
    {
        $names = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'telegram' => 'Telegram',
            'youtube' => 'YouTube',
            'twitter' => 'Twitter/X',
            'linkedin' => 'LinkedIn',
            'email' => 'Email',
            'blog' => 'Blog',
        ];

        return $names[$key] ?? ucfirst($key);
    }

    private function getTopContent($businessId, $period): array
    {
        $startDate = now()->subDays((int) $period);

        return ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->orderByRaw('(COALESCE(likes, 0) + COALESCE(comments, 0) + COALESCE(shares, 0)) DESC')
            ->limit(5)
            ->get()
            ->map(function ($content) {
                $engagement = ($content->likes ?? 0) + ($content->comments ?? 0) + ($content->shares ?? 0);

                return [
                    'id' => $content->id,
                    'title' => $content->title,
                    'platform' => $content->channel,
                    'type' => $content->content_type,
                    'engagement' => $engagement,
                    'reach' => $content->reach ?? 0,
                    'views' => $content->views ?? 0,
                    'likes' => $content->likes ?? 0,
                    'comments' => $content->comments ?? 0,
                    'shares' => $content->shares ?? 0,
                    'engagement_rate' => $content->engagement_rate ?? 0,
                    'published_at' => $content->created_at?->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    private function getCampaignPerformance($businessId, $period): array
    {
        $startDate = now()->subDays((int) $period);

        $campaigns = Campaign::where('business_id', $businessId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                $settings = $campaign->settings ?? [];
                $budget = $settings['budget'] ?? 0;
                $spent = $settings['spent'] ?? 0;
                $revenue = $settings['revenue'] ?? 0;
                $leads = $campaign->sent_count ?? 0;

                // Calculate ROI
                $roi = $spent > 0 ? round((($revenue - $spent) / $spent) * 100, 1) : 0;
                $costPerLead = $leads > 0 ? round($spent / $leads) : 0;

                return [
                    'id' => $campaign->id,
                    'uuid' => $campaign->uuid,
                    'name' => $campaign->name,
                    'channel' => $campaign->channel,
                    'status' => $campaign->status,
                    'budget' => $budget,
                    'spent' => $spent,
                    'revenue' => $revenue,
                    'leads' => $leads,
                    'roi' => $roi,
                    'cost_per_lead' => $costPerLead,
                ];
            })
            ->toArray();

        // Summary stats
        $totalCampaigns = Campaign::where('business_id', $businessId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalSpent = 0;
        $totalLeads = 0;
        $totalRevenue = 0;

        foreach ($campaigns as $c) {
            $totalSpent += $c['spent'];
            $totalLeads += $c['leads'];
            $totalRevenue += $c['revenue'];
        }

        return [
            'campaigns' => $campaigns,
            'summary' => [
                'total_campaigns' => $totalCampaigns,
                'total_spent' => $totalSpent,
                'total_leads' => $totalLeads,
                'total_revenue' => $totalRevenue,
                'avg_cost_per_lead' => $totalLeads > 0 ? round($totalSpent / $totalLeads) : 0,
                'avg_roi' => $totalSpent > 0 ? round((($totalRevenue - $totalSpent) / $totalSpent) * 100, 1) : 0,
            ],
        ];
    }

    private function getTrends($businessId, $period): array
    {
        $startDate = now()->subDays((int) $period);

        // Daily content metrics (using created_at as date reference)
        $dailyMetrics = ContentCalendar::where('business_id', $businessId)
            ->where('status', 'published')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'))
            ->selectRaw('SUM(COALESCE(reach, 0)) as reach')
            ->selectRaw('SUM(COALESCE(views, 0)) as views')
            ->selectRaw('SUM(COALESCE(likes, 0) + COALESCE(comments, 0) + COALESCE(shares, 0)) as engagement')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'reach' => $item->reach ?? 0,
                    'views' => $item->views ?? 0,
                    'engagement' => $item->engagement ?? 0,
                ];
            })
            ->toArray();

        // Daily leads
        $dailyLeads = Lead::where('business_id', $businessId)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'))
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        return [
            'metrics' => $dailyMetrics,
            'leads' => $dailyLeads,
        ];
    }

    private function getLeadSources($businessId, $period): array
    {
        $startDate = now()->subDays((int) $period);

        // Try to get lead sources from campaigns
        $sources = Campaign::where('business_id', $businessId)
            ->whereNotNull('channel')
            ->select('channel')
            ->selectRaw('SUM(sent_count) as leads')
            ->groupBy('channel')
            ->orderByDesc('leads')
            ->get()
            ->map(function ($source) {
                return [
                    'name' => $this->getChannelName($source->channel),
                    'key' => $source->channel,
                    'leads' => $source->leads ?? 0,
                ];
            })
            ->toArray();

        // If no campaign data, return empty
        if (empty($sources)) {
            // Get lead count by general status
            $totalLeads = Lead::where('business_id', $businessId)
                ->where('created_at', '>=', $startDate)
                ->count();

            if ($totalLeads > 0) {
                $sources = [
                    ['name' => 'To\'g\'ridan-to\'g\'ri', 'key' => 'direct', 'leads' => $totalLeads],
                ];
            }
        }

        return $sources;
    }

    public function social(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        $period = $request->get('period', '30');
        $startDate = now()->subDays((int) $period);

        // Get social media stats grouped by platform
        $platforms = ['instagram', 'facebook', 'telegram', 'youtube', 'twitter', 'linkedin'];
        $socialAnalytics = [];

        foreach ($platforms as $platform) {
            $stats = ContentCalendar::where('business_id', $business->id)
                ->where('channel', $platform)
                ->where('status', 'published')
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('COUNT(*) as posts'),
                    DB::raw('SUM(reach) as reach'),
                    DB::raw('SUM(views) as views'),
                    DB::raw('SUM(likes) as likes'),
                    DB::raw('SUM(comments) as comments'),
                    DB::raw('SUM(shares) as shares'),
                    DB::raw('AVG(engagement_rate) as engagement_rate')
                )
                ->first();

            if ($stats && $stats->posts > 0) {
                $socialAnalytics[$platform] = [
                    'name' => $this->getChannelName($platform),
                    'posts' => $stats->posts ?? 0,
                    'reach' => $stats->reach ?? 0,
                    'views' => $stats->views ?? 0,
                    'likes' => $stats->likes ?? 0,
                    'comments' => $stats->comments ?? 0,
                    'shares' => $stats->shares ?? 0,
                    'engagement_rate' => round($stats->engagement_rate ?? 0, 2),
                ];
            }
        }

        return Inertia::render('Shared/Analytics/Social', [
            'socialAnalytics' => $socialAnalytics,
            'period' => $period,
            'panelType' => $panelType,
        ]);
    }

    public function campaigns(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        $period = $request->get('period', '30');
        $performance = $this->getCampaignPerformance($business->id, $period);

        return Inertia::render('Shared/Analytics/Campaigns', [
            'campaignAnalytics' => $performance,
            'period' => $period,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Conversion page
     */
    public function conversion(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        return Inertia::render('Shared/Analytics/Conversion', [
            'panelType' => $panelType,
        ]);
    }

    /**
     * Revenue page
     */
    public function revenue(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        return Inertia::render('Shared/Analytics/Revenue', [
            'panelType' => $panelType,
        ]);
    }

    /**
     * API: Get initial analytics data
     */
    public function getInitialData(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'source_id']);
        $period = $request->get('period', '30');
        $filterKey = md5(json_encode($filters).$period);
        $cacheKey = "analytics_initial_{$business->id}_{$filterKey}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $period) {
            return [
                'overview' => $this->getOverviewStats($business->id, $period),
                'channels' => $this->getChannelStats($business->id, $period),
                'trends' => $this->getTrends($business->id, $period),
                'topContent' => $this->getTopContent($business->id, $period),
            ];
        });

        return response()->json($data);
    }

    /**
     * Conversion funnel page
     */
    public function funnel(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        $filters = $request->only(['date_from', 'date_to']);

        return Inertia::render('Shared/Analytics/Funnel', [
            'filters' => $filters,
            'lazyLoad' => true,
            'panelType' => $panelType,
        ]);
    }

    /**
     * API: Get funnel data
     */
    public function getFunnelData(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to']);
        $cacheKey = "funnel_{$business->id}_".md5(json_encode($filters));

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $filters) {
            return $this->analyticsService->getFunnelData($business->id, $filters);
        });

        return response()->json($data);
    }

    /**
     * Dream Buyer performance
     */
    public function getDreamBuyerPerformance(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to']);
        $cacheKey = "dream_buyer_{$business->id}_".md5(json_encode($filters));

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $filters) {
            return $this->analyticsService->getDreamBuyerPerformance($business->id, $filters);
        });

        return response()->json($data);
    }

    /**
     * Offer performance
     */
    public function getOfferPerformance(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to']);
        $cacheKey = "offer_{$business->id}_".md5(json_encode($filters));

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $filters) {
            return $this->analyticsService->getOfferPerformance($business->id, $filters);
        });

        return response()->json($data);
    }

    /**
     * Lead source analysis
     */
    public function getLeadSourceAnalysis(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to']);
        $cacheKey = "lead_source_{$business->id}_".md5(json_encode($filters));

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $filters) {
            return $this->analyticsService->getLeadSourceAnalysis($business->id, $filters);
        });

        return response()->json($data);
    }

    /**
     * Revenue trends
     */
    public function getRevenueTrends(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $granularity = $request->input('granularity', 'daily');
        $days = $request->input('days', 30);
        $cacheKey = "revenue_trends_{$business->id}_{$granularity}_{$days}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $granularity, $days) {
            return $this->analyticsService->getRevenueTrends($business->id, $granularity, $days);
        });

        return response()->json($data);
    }

    /**
     * Content performance analytics
     */
    public function contentPerformance(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        if (! $business) {
            return redirect()->route('login');
        }

        $period = $request->get('period', '30');
        $startDate = now()->subDays((int) $period);

        $cacheKey = "content_performance_{$business->id}_{$period}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $startDate) {
            // Content type performance
            $byType = ContentCalendar::where('business_id', $business->id)
                ->where('status', 'published')
                ->where('created_at', '>=', $startDate)
                ->select('content_type')
                ->selectRaw('COUNT(*) as posts')
                ->selectRaw('SUM(reach) as reach')
                ->selectRaw('AVG(engagement_rate) as engagement_rate')
                ->groupBy('content_type')
                ->get();

            // Best posting times
            $bestTimes = ContentCalendar::where('business_id', $business->id)
                ->where('status', 'published')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('HOUR(scheduled_time) as hour')
                ->selectRaw('AVG(engagement_rate) as engagement_rate')
                ->selectRaw('COUNT(*) as posts')
                ->groupBy('hour')
                ->orderByDesc('engagement_rate')
                ->limit(5)
                ->get();

            // Hashtag performance
            $hashtagPerformance = ContentCalendar::where('business_id', $business->id)
                ->where('status', 'published')
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('hashtags')
                ->get()
                ->flatMap(fn ($c) => $c->hashtags ?? [])
                ->countBy()
                ->sortDesc()
                ->take(10);

            return [
                'by_type' => $byType,
                'best_times' => $bestTimes,
                'hashtag_performance' => $hashtagPerformance,
            ];
        });

        return Inertia::render('Shared/Analytics/ContentPerformance', [
            'data' => $data,
            'period' => $period,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Export PDF report
     */
    public function exportPDF(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $period = $request->get('period', '30');

        $data = [
            'overview' => $this->getOverviewStats($business->id, $period),
            'channels' => $this->getChannelStats($business->id, $period),
            'topContent' => $this->getTopContent($business->id, $period),
            'campaignPerformance' => $this->getCampaignPerformance($business->id, $period),
        ];

        return $this->exportService->exportMarketingPDF($business, $data);
    }

    /**
     * Export Excel report
     */
    public function exportExcel(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $period = $request->get('period', '30');

        $data = [
            'overview' => $this->getOverviewStats($business->id, $period),
            'channels' => $this->getChannelStats($business->id, $period),
            'topContent' => $this->getTopContent($business->id, $period),
            'campaignPerformance' => $this->getCampaignPerformance($business->id, $period),
        ];

        return $this->exportService->exportMarketingExcel($business, $data);
    }
}
