<?php

namespace App\Services;

use App\Models\ContentCalendar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Centralized Content Statistics Service
 * Marketing panel va Analytics uchun yagona content statistika
 */
class ContentStatisticsService
{
    protected int $cacheTTL = 300; // 5 daqiqa

    /**
     * Get content statistics for a business
     */
    public function getContentStats(string $businessId, ?Carbon $startDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $cacheKey = "content_stats_{$businessId}_{$startDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($businessId, $startDate) {
            $stats = DB::table('content_calendars')
                ->where('business_id', $businessId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as drafts,
                    SUM(CASE WHEN status = "published" THEN COALESCE(reach, 0) ELSE 0 END) as total_reach,
                    SUM(CASE WHEN status = "published" THEN COALESCE(views, 0) ELSE 0 END) as total_views,
                    SUM(CASE WHEN status = "published" THEN COALESCE(likes, 0) ELSE 0 END) as total_likes,
                    SUM(CASE WHEN status = "published" THEN COALESCE(comments, 0) ELSE 0 END) as total_comments,
                    SUM(CASE WHEN status = "published" THEN COALESCE(shares, 0) ELSE 0 END) as total_shares
                ')
                ->first();

            $published = (int) $stats->published;

            return [
                'total' => (int) $stats->total,
                'published' => $published,
                'scheduled' => (int) $stats->scheduled,
                'drafts' => (int) $stats->drafts,
                'reach' => (int) $stats->total_reach,
                'views' => (int) $stats->total_views,
                'likes' => (int) $stats->total_likes,
                'comments' => (int) $stats->total_comments,
                'shares' => (int) $stats->total_shares,
                'avg_reach' => $published > 0 ? round($stats->total_reach / $published) : 0,
                'engagement_rate' => $stats->total_reach > 0
                    ? round((($stats->total_likes + $stats->total_comments + $stats->total_shares) / $stats->total_reach) * 100, 2)
                    : 0,
            ];
        });
    }

    /**
     * Get social media engagement stats
     */
    public function getSocialStats(string $businessId): array
    {
        return Cache::remember("social_stats_{$businessId}", $this->cacheTTL, function () use ($businessId) {
            $stats = DB::table('content_calendars')
                ->where('business_id', $businessId)
                ->where('status', 'published')
                ->selectRaw('
                    SUM(COALESCE(likes, 0)) as total_likes,
                    SUM(COALESCE(comments, 0)) as total_comments,
                    SUM(COALESCE(shares, 0)) as total_shares,
                    SUM(COALESCE(reach, 0)) as total_reach,
                    SUM(COALESCE(views, 0)) as total_views,
                    COUNT(*) as total_posts
                ')
                ->first();

            $totalEngagement = $stats->total_likes + $stats->total_comments + $stats->total_shares;
            $totalPosts = (int) $stats->total_posts;

            return [
                'likes' => (int) $stats->total_likes,
                'comments' => (int) $stats->total_comments,
                'shares' => (int) $stats->total_shares,
                'reach' => (int) $stats->total_reach,
                'views' => (int) $stats->total_views,
                'total_engagement' => (int) $totalEngagement,
                'avg_engagement_per_post' => $totalPosts > 0 ? round($totalEngagement / $totalPosts, 1) : 0,
                'engagement_rate' => $stats->total_reach > 0
                    ? round(($totalEngagement / $stats->total_reach) * 100, 2)
                    : 0,
            ];
        });
    }

    /**
     * Get content by platform
     */
    public function getContentByPlatform(string $businessId): array
    {
        return Cache::remember("content_by_platform_{$businessId}", $this->cacheTTL, function () use ($businessId) {
            return DB::table('content_calendars')
                ->where('business_id', $businessId)
                ->where('status', 'published')
                ->selectRaw('
                    platform,
                    COUNT(*) as count,
                    SUM(COALESCE(reach, 0)) as reach,
                    SUM(COALESCE(likes, 0)) as likes,
                    SUM(COALESCE(comments, 0)) as comments
                ')
                ->groupBy('platform')
                ->get()
                ->map(function ($row) {
                    return [
                        'platform' => $row->platform,
                        'count' => (int) $row->count,
                        'reach' => (int) $row->reach,
                        'likes' => (int) $row->likes,
                        'comments' => (int) $row->comments,
                        'engagement' => (int) ($row->likes + $row->comments),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get upcoming scheduled content
     */
    public function getUpcomingContent(string $businessId, int $limit = 5): array
    {
        return ContentCalendar::where('business_id', $businessId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', Carbon::now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get(['id', 'title', 'platform', 'scheduled_at', 'content_type'])
            ->toArray();
    }

    /**
     * Get content performance trends
     */
    public function getPerformanceTrends(string $businessId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        return Cache::remember("content_trends_{$businessId}_{$days}", $this->cacheTTL, function () use ($businessId, $startDate) {
            return DB::table('content_calendars')
                ->where('business_id', $businessId)
                ->where('status', 'published')
                ->where('published_at', '>=', $startDate)
                ->selectRaw('
                    DATE(published_at) as date,
                    COUNT(*) as posts,
                    SUM(COALESCE(reach, 0)) as reach,
                    SUM(COALESCE(likes, 0) + COALESCE(comments, 0) + COALESCE(shares, 0)) as engagement
                ')
                ->groupBy(DB::raw('DATE(published_at)'))
                ->orderBy('date')
                ->get()
                ->map(function ($row) {
                    return [
                        'date' => $row->date,
                        'posts' => (int) $row->posts,
                        'reach' => (int) $row->reach,
                        'engagement' => (int) $row->engagement,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Clear cache for a business
     */
    public function clearCache(string $businessId): void
    {
        $patterns = [
            "content_stats_{$businessId}*",
            "social_stats_{$businessId}",
            "content_by_platform_{$businessId}",
            "content_trends_{$businessId}*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
