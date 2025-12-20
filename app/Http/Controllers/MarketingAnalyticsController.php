<?php

namespace App\Http\Controllers;

use App\Models\MarketingChannel;
use App\Models\InstagramMetric;
use App\Models\TelegramMetric;
use App\Models\FacebookMetric;
use App\Models\GoogleAdsMetric;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MarketingAnalyticsController extends Controller
{
    /**
     * Display the marketing analytics dashboard.
     */
    public function index(Request $request)
    {
        $businessId = session('current_business_id');

        // Get all channels for current business
        $channels = MarketingChannel::where('business_id', $businessId)
            ->with(['instagramMetrics' => function ($query) {
                $query->latest('metric_date')->limit(1);
            }])
            ->get();

        // Calculate aggregate metrics for the last 30 days
        $startDate = Carbon::now()->subDays(30);

        $analytics = [
            'total_channels' => $channels->count(),
            'active_channels' => $channels->where('is_active', true)->count(),
            'total_reach' => 0,
            'total_engagement' => 0,
            'average_engagement_rate' => 0,
        ];

        // Aggregate metrics from each channel type
        foreach ($channels as $channel) {
            $latestMetric = $channel->latestMetrics();

            if ($latestMetric) {
                if ($channel->type === 'instagram') {
                    $analytics['total_reach'] += $latestMetric->reach ?? 0;
                    $analytics['total_engagement'] += $latestMetric->getTotalEngagementAttribute();
                } elseif ($channel->type === 'telegram') {
                    $analytics['total_reach'] += $latestMetric->total_views ?? 0;
                    $analytics['total_engagement'] += $latestMetric->getTotalEngagementAttribute();
                } elseif ($channel->type === 'facebook') {
                    $analytics['total_reach'] += $latestMetric->reach ?? 0;
                    $analytics['total_engagement'] += $latestMetric->getTotalEngagementAttribute();
                }
            }
        }

        // Calculate average engagement rate
        if ($analytics['total_reach'] > 0) {
            $analytics['average_engagement_rate'] = round(
                ($analytics['total_engagement'] / $analytics['total_reach']) * 100,
                2
            );
        }

        return Inertia::render('Business/Marketing/Dashboard', [
            'channels' => $channels,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Display all marketing channels.
     */
    public function channels(Request $request)
    {
        $businessId = session('current_business_id');

        $query = MarketingChannel::where('business_id', $businessId);

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $channels = $query->with(['instagramMetrics' => function ($query) {
            $query->latest('metric_date')->limit(7);
        }, 'telegramMetrics' => function ($query) {
            $query->latest('metric_date')->limit(7);
        }])->paginate(20);

        return Inertia::render('Business/Marketing/Channels', [
            'channels' => $channels,
            'filters' => $request->only(['type', 'is_active']),
        ]);
    }

    /**
     * Display detailed analytics for a specific channel.
     */
    public function channelDetail(Request $request, MarketingChannel $channel)
    {
        // Ensure channel belongs to current business
        if ($channel->business_id !== session('current_business_id')) {
            abort(403, 'Unauthorized access to channel');
        }

        $period = $request->input('period', 30); // Days
        $startDate = Carbon::now()->subDays($period);

        // Get metrics based on channel type
        $metrics = [];
        $chartData = [];

        switch ($channel->type) {
            case 'instagram':
                $metrics = $channel->instagramMetrics()
                    ->where('metric_date', '>=', $startDate)
                    ->orderBy('metric_date', 'desc')
                    ->get();

                $chartData = $this->prepareInstagramChartData($metrics);
                break;

            case 'telegram':
                $metrics = $channel->telegramMetrics()
                    ->where('metric_date', '>=', $startDate)
                    ->orderBy('metric_date', 'desc')
                    ->get();

                $chartData = $this->prepareTelegramChartData($metrics);
                break;

            case 'facebook':
                $metrics = $channel->facebookMetrics()
                    ->where('metric_date', '>=', $startDate)
                    ->orderBy('metric_date', 'desc')
                    ->get();

                $chartData = $this->prepareFacebookChartData($metrics);
                break;

            case 'google_ads':
                $metrics = $channel->googleAdsMetrics()
                    ->where('metric_date', '>=', $startDate)
                    ->orderBy('metric_date', 'desc')
                    ->get();

                $chartData = $this->prepareGoogleAdsChartData($metrics);
                break;
        }

        // Calculate summary statistics
        $summary = $this->calculateSummaryStats($metrics, $channel->type);

        return Inertia::render('Business/Marketing/ChannelDetail', [
            'channel' => $channel,
            'metrics' => $metrics,
            'chartData' => $chartData,
            'summary' => $summary,
            'period' => $period,
        ]);
    }

    /**
     * Prepare Instagram chart data.
     */
    private function prepareInstagramChartData($metrics)
    {
        return [
            'labels' => $metrics->pluck('metric_date')->map(fn($date) => $date->format('M d'))->reverse()->values(),
            'datasets' => [
                [
                    'label' => 'Followers',
                    'data' => $metrics->pluck('followers_count')->reverse()->values(),
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Reach',
                    'data' => $metrics->pluck('reach')->reverse()->values(),
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Engagement',
                    'data' => $metrics->map(fn($m) => $m->getTotalEngagementAttribute())->reverse()->values(),
                    'borderColor' => 'rgb(54, 162, 235)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }

    /**
     * Prepare Telegram chart data.
     */
    private function prepareTelegramChartData($metrics)
    {
        return [
            'labels' => $metrics->pluck('metric_date')->map(fn($date) => $date->format('M d'))->reverse()->values(),
            'datasets' => [
                [
                    'label' => 'Members',
                    'data' => $metrics->pluck('members_count')->reverse()->values(),
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Views',
                    'data' => $metrics->pluck('total_views')->reverse()->values(),
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Engagement',
                    'data' => $metrics->map(fn($m) => $m->getTotalEngagementAttribute())->reverse()->values(),
                    'borderColor' => 'rgb(54, 162, 235)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }

    /**
     * Prepare Facebook chart data.
     */
    private function prepareFacebookChartData($metrics)
    {
        return [
            'labels' => $metrics->pluck('metric_date')->map(fn($date) => $date->format('M d'))->reverse()->values(),
            'datasets' => [
                [
                    'label' => 'Page Followers',
                    'data' => $metrics->pluck('page_followers')->reverse()->values(),
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Reach',
                    'data' => $metrics->pluck('reach')->reverse()->values(),
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Engagement',
                    'data' => $metrics->map(fn($m) => $m->getTotalEngagementAttribute())->reverse()->values(),
                    'borderColor' => 'rgb(54, 162, 235)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }

    /**
     * Prepare Google Ads chart data.
     */
    private function prepareGoogleAdsChartData($metrics)
    {
        return [
            'labels' => $metrics->pluck('metric_date')->map(fn($date) => $date->format('M d'))->reverse()->values(),
            'datasets' => [
                [
                    'label' => 'Impressions',
                    'data' => $metrics->pluck('impressions')->reverse()->values(),
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Clicks',
                    'data' => $metrics->pluck('clicks')->reverse()->values(),
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Conversions',
                    'data' => $metrics->pluck('conversions')->reverse()->values(),
                    'borderColor' => 'rgb(54, 162, 235)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }

    /**
     * Calculate summary statistics.
     */
    private function calculateSummaryStats($metrics, $channelType)
    {
        if ($metrics->isEmpty()) {
            return [
                'total_reach' => 0,
                'total_engagement' => 0,
                'avg_engagement_rate' => 0,
                'growth' => 0,
            ];
        }

        $latest = $metrics->first();
        $oldest = $metrics->last();

        $summary = [
            'total_reach' => 0,
            'total_engagement' => 0,
            'avg_engagement_rate' => 0,
            'growth' => 0,
        ];

        switch ($channelType) {
            case 'instagram':
                $summary['total_reach'] = $metrics->sum('reach');
                $summary['total_engagement'] = $metrics->sum(fn($m) => $m->getTotalEngagementAttribute());
                $summary['avg_engagement_rate'] = $metrics->avg('engagement_rate');
                $summary['growth'] = $latest->followers_count - $oldest->followers_count;
                break;

            case 'telegram':
                $summary['total_reach'] = $metrics->sum('total_views');
                $summary['total_engagement'] = $metrics->sum(fn($m) => $m->getTotalEngagementAttribute());
                $summary['avg_engagement_rate'] = $metrics->avg('engagement_rate');
                $summary['growth'] = $latest->members_count - $oldest->members_count;
                break;

            case 'facebook':
                $summary['total_reach'] = $metrics->sum('reach');
                $summary['total_engagement'] = $metrics->sum(fn($m) => $m->getTotalEngagementAttribute());
                $summary['avg_engagement_rate'] = $metrics->avg('engagement_rate');
                $summary['growth'] = $latest->page_followers - $oldest->page_followers;
                break;

            case 'google_ads':
                $summary['total_impressions'] = $metrics->sum('impressions');
                $summary['total_clicks'] = $metrics->sum('clicks');
                $summary['total_conversions'] = $metrics->sum('conversions');
                $summary['total_cost'] = $metrics->sum('cost') / 100; // Convert from kopeks
                $summary['avg_ctr'] = $metrics->avg('ctr');
                $summary['avg_roas'] = $metrics->avg('roas');
                break;
        }

        return $summary;
    }

    /**
     * Store or update a marketing channel.
     */
    public function store(Request $request)
    {
        $businessId = session('current_business_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:social_media,email,seo,ppc,content,affiliate,direct,referral,other',
            'platform' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'config' => 'nullable|array',
        ]);

        $channel = MarketingChannel::create([
            'business_id' => $businessId,
            ...$validated,
            'is_active' => true,
        ]);

        return redirect()->route('marketing.channels')
            ->with('success', 'Kanal muvaffaqiyatli qo\'shildi');
    }

    /**
     * Update a marketing channel.
     */
    public function update(Request $request, MarketingChannel $channel)
    {
        // Ensure channel belongs to current business
        if ($channel->business_id !== session('current_business_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);

        $channel->update($validated);

        return back()->with('success', 'Kanal yangilandi');
    }

    /**
     * Delete a marketing channel.
     */
    public function destroy(MarketingChannel $channel)
    {
        // Ensure channel belongs to current business
        if ($channel->business_id !== session('current_business_id')) {
            abort(403);
        }

        $channel->delete();

        return redirect()->route('marketing.channels')
            ->with('success', 'Kanal o\'chirildi');
    }
}
