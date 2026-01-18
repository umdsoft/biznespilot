<?php

namespace App\Services\Integration;

use App\Models\Business;
use App\Models\FacebookAd;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacebookKpiSyncService extends BaseKpiSyncService
{
    /**
     * KPI codes that can be synced from Facebook
     */
    protected array $supportedKpis = [
        'engagement_rate',           // From page posts interactions
        'reach_rate',                // From posts/ads reach
        'facebook_ctr',              // Click-through rate from ads
        'cost_per_click',            // CPC from ads
        'cost_per_lead',             // CPL from lead ads
        'roas',                      // Return on ad spend
        'conversion_rate',           // From ads/pixel tracking
        'social_response_time',      // Response time to messages
        'page_growth_rate',          // Page likes growth
        'content_engagement',        // Overall content engagement
        'ad_frequency',              // Ad frequency metric
        'video_completion_rate',     // Video view completion
    ];

    /**
     * Get integration name
     */
    public function getIntegrationName(): string
    {
        return 'facebook_api';
    }

    /**
     * Get supported KPIs
     */
    public function getSupportedKpis(): array
    {
        return $this->supportedKpis;
    }

    /**
     * Check if Facebook integration is available for business
     */
    public function isAvailable(int $businessId): bool
    {
        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        // Check if business has connected Facebook page or ad account
        $facebookPage = FacebookPage::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        return $facebookPage !== null;
    }

    /**
     * Sync specific KPI
     */
    public function syncKpi(int $businessId, string $kpiCode, string $date): array
    {
        if (! in_array($kpiCode, $this->supportedKpis)) {
            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => 'KPI not supported by Facebook integration',
            ];
        }

        $facebookPage = FacebookPage::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if (! $facebookPage) {
            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => 'Facebook page not connected',
            ];
        }

        try {
            $value = match ($kpiCode) {
                'engagement_rate' => $this->calculateEngagementRate($facebookPage, $date),
                'reach_rate' => $this->calculateReachRate($facebookPage, $date),
                'facebook_ctr' => $this->calculateClickThroughRate($facebookPage, $date),
                'cost_per_click' => $this->calculateCostPerClick($facebookPage, $date),
                'cost_per_lead' => $this->calculateCostPerLead($facebookPage, $date),
                'roas' => $this->calculateROAS($facebookPage, $date),
                'conversion_rate' => $this->calculateConversionRate($facebookPage, $date),
                'social_response_time' => $this->calculateResponseTime($facebookPage, $date),
                'page_growth_rate' => $this->calculatePageGrowthRate($facebookPage, $date),
                'content_engagement' => $this->calculateContentEngagement($facebookPage, $date),
                'ad_frequency' => $this->calculateAdFrequency($facebookPage, $date),
                'video_completion_rate' => $this->calculateVideoCompletionRate($facebookPage, $date),
                default => null,
            };

            if ($value === null) {
                return [
                    'success' => false,
                    'kpi_code' => $kpiCode,
                    'value' => null,
                    'message' => 'Insufficient data to calculate KPI',
                ];
            }

            // Save the KPI value
            $this->saveKpiValue($businessId, $kpiCode, $date, $value, [
                'facebook_page_id' => $facebookPage->id,
                'page_name' => $facebookPage->name,
            ]);

            return [
                'success' => true,
                'kpi_code' => $kpiCode,
                'value' => $value,
                'message' => 'KPI synced successfully',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync Facebook KPI: {$kpiCode}", [
                'business_id' => $businessId,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate engagement rate from page posts
     */
    protected function calculateEngagementRate(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $posts = FacebookPost::where('facebook_page_id', $page->id)
            ->whereDate('published_at', $dateObj)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $totalEngagements = 0;
        $totalReach = 0;

        foreach ($posts as $post) {
            $totalEngagements += ($post->likes ?? 0) + ($post->comments ?? 0) + ($post->shares ?? 0);
            $totalReach += $post->reach ?? 0;
        }

        if ($totalReach === 0) {
            $totalReach = $page->followers_count ?? 0;
        }

        if ($totalReach === 0) {
            return null;
        }

        return round(($totalEngagements / $totalReach) * 100, 2);
    }

    /**
     * Calculate reach rate
     */
    protected function calculateReachRate(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $posts = FacebookPost::where('facebook_page_id', $page->id)
            ->whereDate('published_at', $dateObj)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $totalReach = $posts->sum('reach') ?? 0;
        $followersCount = $page->followers_count ?? 0;

        if ($followersCount === 0 || $totalReach === 0) {
            return null;
        }

        return round(($totalReach / $followersCount) * 100, 2);
    }

    /**
     * Calculate click-through rate from ads
     */
    protected function calculateClickThroughRate(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $ads = FacebookAd::whereHas('campaign', function ($query) use ($page) {
            $query->where('facebook_page_id', $page->id);
        })
            ->whereDate('date', $dateObj)
            ->get();

        if ($ads->isEmpty()) {
            return null;
        }

        $totalClicks = $ads->sum('clicks') ?? 0;
        $totalImpressions = $ads->sum('impressions') ?? 0;

        if ($totalImpressions === 0) {
            return null;
        }

        return round(($totalClicks / $totalImpressions) * 100, 2);
    }

    /**
     * Calculate cost per click
     */
    protected function calculateCostPerClick(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $ads = FacebookAd::whereHas('campaign', function ($query) use ($page) {
            $query->where('facebook_page_id', $page->id);
        })
            ->whereDate('date', $dateObj)
            ->get();

        if ($ads->isEmpty()) {
            return null;
        }

        $totalSpend = $ads->sum('spend') ?? 0;
        $totalClicks = $ads->sum('clicks') ?? 0;

        if ($totalClicks === 0) {
            return null;
        }

        return round($totalSpend / $totalClicks, 2);
    }

    /**
     * Calculate cost per lead
     */
    protected function calculateCostPerLead(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $ads = FacebookAd::whereHas('campaign', function ($query) use ($page) {
            $query->where('facebook_page_id', $page->id)
                ->where('objective', 'LEAD_GENERATION');
        })
            ->whereDate('date', $dateObj)
            ->get();

        if ($ads->isEmpty()) {
            return null;
        }

        $totalSpend = $ads->sum('spend') ?? 0;
        $totalLeads = $ads->sum('leads') ?? 0;

        if ($totalLeads === 0) {
            return null;
        }

        return round($totalSpend / $totalLeads, 2);
    }

    /**
     * Calculate return on ad spend
     */
    protected function calculateROAS(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $ads = FacebookAd::whereHas('campaign', function ($query) use ($page) {
            $query->where('facebook_page_id', $page->id);
        })
            ->whereDate('date', $dateObj)
            ->get();

        if ($ads->isEmpty()) {
            return null;
        }

        $totalSpend = $ads->sum('spend') ?? 0;
        $totalRevenue = $ads->sum('revenue') ?? 0;

        if ($totalSpend === 0) {
            return null;
        }

        return round($totalRevenue / $totalSpend, 2);
    }

    /**
     * Calculate conversion rate
     */
    protected function calculateConversionRate(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $ads = FacebookAd::whereHas('campaign', function ($query) use ($page) {
            $query->where('facebook_page_id', $page->id);
        })
            ->whereDate('date', $dateObj)
            ->get();

        if ($ads->isEmpty()) {
            return null;
        }

        $totalConversions = $ads->sum('conversions') ?? 0;
        $totalClicks = $ads->sum('clicks') ?? 0;

        if ($totalClicks === 0) {
            return null;
        }

        return round(($totalConversions / $totalClicks) * 100, 2);
    }

    /**
     * Calculate response time
     */
    protected function calculateResponseTime(FacebookPage $page, string $date): ?float
    {
        // This would require message response tracking
        // TODO: Implement when message tracking is available
        return null;
    }

    /**
     * Calculate page growth rate
     */
    protected function calculatePageGrowthRate(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $currentFollowers = $page->followers_count ?? 0;

        $previousDate = $dateObj->copy()->subDay();
        $previousFollowers = DB::table('kpi_daily_actuals')
            ->where('business_id', $page->business_id)
            ->where('kpi_code', 'page_growth_rate')
            ->where('record_date', $previousDate->format('Y-m-d'))
            ->value('actual_value');

        if (! $previousFollowers) {
            $previousFollowers = $currentFollowers > 100 ? $currentFollowers - 10 : $currentFollowers;
        }

        if ($previousFollowers === 0) {
            return null;
        }

        return round((($currentFollowers - $previousFollowers) / $previousFollowers) * 100, 2);
    }

    /**
     * Calculate content engagement
     */
    protected function calculateContentEngagement(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $posts = FacebookPost::where('facebook_page_id', $page->id)
            ->whereDate('published_at', $dateObj)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        $postCount = $posts->count();

        foreach ($posts as $post) {
            $score = ($post->likes ?? 0) + ($post->comments ?? 0) * 2 + ($post->shares ?? 0) * 3;
            $totalScore += $score;
        }

        return round($totalScore / $postCount, 2);
    }

    /**
     * Calculate ad frequency
     */
    protected function calculateAdFrequency(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $ads = FacebookAd::whereHas('campaign', function ($query) use ($page) {
            $query->where('facebook_page_id', $page->id);
        })
            ->whereDate('date', $dateObj)
            ->get();

        if ($ads->isEmpty()) {
            return null;
        }

        $totalImpressions = $ads->sum('impressions') ?? 0;
        $totalReach = $ads->sum('reach') ?? 0;

        if ($totalReach === 0) {
            return null;
        }

        return round($totalImpressions / $totalReach, 2);
    }

    /**
     * Calculate video completion rate
     */
    protected function calculateVideoCompletionRate(FacebookPage $page, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $posts = FacebookPost::where('facebook_page_id', $page->id)
            ->where('post_type', 'video')
            ->whereDate('published_at', $dateObj)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $totalViews = $posts->sum('video_views') ?? 0;
        $totalCompletions = $posts->sum('video_completions') ?? 0;

        if ($totalViews === 0) {
            return null;
        }

        return round(($totalCompletions / $totalViews) * 100, 2);
    }
}
