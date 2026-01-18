<?php

namespace App\Services\Integration;

use App\Models\Business;
use App\Models\InstagramBusinessAccount;
use App\Models\InstagramPost;
use App\Models\InstagramStory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstagramKpiSyncService extends BaseKpiSyncService
{
    /**
     * KPI codes that can be synced from Instagram
     */
    protected array $supportedKpis = [
        'engagement_rate',           // From posts/stories interactions
        'follower_growth',           // From account followers tracking
        'reach_rate',                // From posts/stories reach
        'instagram_ctr',             // Click-through rate
        'content_engagement',        // Overall content engagement
        'social_response_time',      // Response time to DMs/comments
        'user_generated_content',    // UGC mentions and tags
        'brand_mention_frequency',   // Brand mentions
    ];

    /**
     * Get integration name
     */
    public function getIntegrationName(): string
    {
        return 'instagram_api';
    }

    /**
     * Get supported KPIs
     */
    public function getSupportedKpis(): array
    {
        return $this->supportedKpis;
    }

    /**
     * Check if Instagram integration is available for business
     */
    public function isAvailable(int $businessId): bool
    {
        $business = Business::find($businessId);
        if (! $business) {
            return false;
        }

        // Check if business has connected Instagram account
        $instagramAccount = InstagramBusinessAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        return $instagramAccount !== null;
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
                'message' => 'KPI not supported by Instagram integration',
            ];
        }

        $instagram = InstagramBusinessAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if (! $instagram) {
            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => 'Instagram account not connected',
            ];
        }

        try {
            $value = match ($kpiCode) {
                'engagement_rate' => $this->calculateEngagementRate($instagram, $date),
                'follower_growth' => $this->calculateFollowerGrowth($instagram, $date),
                'reach_rate' => $this->calculateReachRate($instagram, $date),
                'instagram_ctr' => $this->calculateClickThroughRate($instagram, $date),
                'content_engagement' => $this->calculateContentEngagement($instagram, $date),
                'social_response_time' => $this->calculateResponseTime($instagram, $date),
                'user_generated_content' => $this->calculateUGC($instagram, $date),
                'brand_mention_frequency' => $this->calculateBrandMentions($instagram, $date),
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
                'instagram_account_id' => $instagram->id,
                'instagram_username' => $instagram->username,
            ]);

            return [
                'success' => true,
                'kpi_code' => $kpiCode,
                'value' => $value,
                'message' => 'KPI synced successfully',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync Instagram KPI: {$kpiCode}", [
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
     * Calculate engagement rate from posts and stories
     */
    protected function calculateEngagementRate(InstagramBusinessAccount $instagram, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get posts for the date
        $posts = InstagramPost::where('instagram_business_account_id', $instagram->id)
            ->whereDate('published_at', $dateObj)
            ->get();

        // Get stories for the date
        $stories = InstagramStory::where('instagram_business_account_id', $instagram->id)
            ->whereDate('created_at', $dateObj)
            ->get();

        if ($posts->isEmpty() && $stories->isEmpty()) {
            return null;
        }

        $totalEngagements = 0;
        $totalReach = 0;

        // Calculate from posts
        foreach ($posts as $post) {
            $totalEngagements += ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->saves_count ?? 0);
            $totalReach += $post->reach ?? 0;
        }

        // Calculate from stories
        foreach ($stories as $story) {
            $totalEngagements += ($story->replies_count ?? 0) + ($story->taps_forward ?? 0) + ($story->taps_back ?? 0);
            $totalReach += $story->reach ?? 0;
        }

        if ($totalReach === 0) {
            // Use follower count if reach is not available
            $totalReach = $instagram->followers_count ?? 0;
        }

        if ($totalReach === 0) {
            return null;
        }

        // Engagement rate = (Total Engagements / Total Reach) * 100
        return round(($totalEngagements / $totalReach) * 100, 2);
    }

    /**
     * Calculate follower growth rate
     */
    protected function calculateFollowerGrowth(InstagramBusinessAccount $instagram, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get follower count at end of date
        $currentFollowers = $instagram->followers_count ?? 0;

        // Get follower count from previous day
        $previousDate = $dateObj->copy()->subDay();
        $previousFollowers = DB::table('kpi_daily_actuals')
            ->where('business_id', $instagram->business_id)
            ->where('kpi_code', 'follower_growth')
            ->where('record_date', $previousDate->format('Y-m-d'))
            ->value('actual_value');

        if (! $previousFollowers) {
            // Try to get from instagram account history or use current as baseline
            $previousFollowers = $currentFollowers > 100 ? $currentFollowers - 10 : $currentFollowers;
        }

        if ($previousFollowers === 0) {
            return null;
        }

        // Growth rate = ((Current - Previous) / Previous) * 100
        $growthRate = (($currentFollowers - $previousFollowers) / $previousFollowers) * 100;

        return round($growthRate, 2);
    }

    /**
     * Calculate reach rate
     */
    protected function calculateReachRate(InstagramBusinessAccount $instagram, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get posts and stories for the date
        $posts = InstagramPost::where('instagram_business_account_id', $instagram->id)
            ->whereDate('published_at', $dateObj)
            ->get();

        $stories = InstagramStory::where('instagram_business_account_id', $instagram->id)
            ->whereDate('created_at', $dateObj)
            ->get();

        if ($posts->isEmpty() && $stories->isEmpty()) {
            return null;
        }

        $totalReach = 0;
        foreach ($posts as $post) {
            $totalReach += $post->reach ?? 0;
        }
        foreach ($stories as $story) {
            $totalReach += $story->reach ?? 0;
        }

        $followersCount = $instagram->followers_count ?? 0;
        if ($followersCount === 0 || $totalReach === 0) {
            return null;
        }

        // Reach rate = (Total Reach / Followers) * 100
        return round(($totalReach / $followersCount) * 100, 2);
    }

    /**
     * Calculate click-through rate from bio link and story links
     */
    protected function calculateClickThroughRate(InstagramBusinessAccount $instagram, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get stories with links for the date
        $stories = InstagramStory::where('instagram_business_account_id', $instagram->id)
            ->whereDate('created_at', $dateObj)
            ->whereNotNull('link_clicks')
            ->get();

        if ($stories->isEmpty()) {
            return null;
        }

        $totalClicks = $stories->sum('link_clicks') ?? 0;
        $totalReach = $stories->sum('reach') ?? 0;

        if ($totalReach === 0) {
            return null;
        }

        // CTR = (Total Clicks / Total Reach) * 100
        return round(($totalClicks / $totalReach) * 100, 2);
    }

    /**
     * Calculate overall content engagement
     */
    protected function calculateContentEngagement(InstagramBusinessAccount $instagram, string $date): ?float
    {
        // Content engagement is similar to engagement rate but includes more metrics
        $dateObj = Carbon::parse($date);

        $posts = InstagramPost::where('instagram_business_account_id', $instagram->id)
            ->whereDate('published_at', $dateObj)
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        $postCount = $posts->count();

        foreach ($posts as $post) {
            $likes = $post->likes_count ?? 0;
            $comments = $post->comments_count ?? 0;
            $saves = $post->saves_count ?? 0;
            $shares = $post->shares_count ?? 0;

            // Weighted engagement score
            $score = ($likes * 1) + ($comments * 2) + ($saves * 3) + ($shares * 4);
            $totalScore += $score;
        }

        return round($totalScore / $postCount, 2);
    }

    /**
     * Calculate average response time to DMs and comments
     */
    protected function calculateResponseTime(InstagramBusinessAccount $instagram, string $date): ?float
    {
        // This would require DM and comment response tracking
        // For now, return null as this data might not be available
        // TODO: Implement when DM/comment tracking is available
        return null;
    }

    /**
     * Calculate user-generated content mentions
     */
    protected function calculateUGC(InstagramBusinessAccount $instagram, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Count posts where business is tagged/mentioned
        // This would require tracking mentions and tags
        // For now, return null as this data might not be available
        // TODO: Implement when mention tracking is available
        return null;
    }

    /**
     * Calculate brand mention frequency
     */
    protected function calculateBrandMentions(InstagramBusinessAccount $instagram, string $date): ?float
    {
        // Similar to UGC, requires mention tracking
        // TODO: Implement when mention tracking is available
        return null;
    }
}
