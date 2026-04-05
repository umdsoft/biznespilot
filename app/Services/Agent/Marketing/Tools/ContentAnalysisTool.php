<?php

namespace App\Services\Agent\Marketing\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kontent natijalarini bazadan olish vositasi (bepul, AI chaqirilmaydi).
 * instagram_media jadvali orqali — account_id bilan bog'lanadi.
 */
class ContentAnalysisTool
{
    /**
     * Biznesning instagram account id larini olish
     */
    private function getAccountIds(string $businessId): array
    {
        return DB::table('instagram_accounts')
            ->where('business_id', $businessId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Oxirgi postlar natijalarini olish
     */
    public function getRecentPostsPerformance(string $businessId, int $days = 7): array
    {
        try {
            $accountIds = $this->getAccountIds($businessId);
            if (empty($accountIds)) {
                return ['success' => true, 'posts' => [], 'summary' => [
                    'post_count' => 0, 'avg_engagement' => 0, 'total_reach' => 0,
                    'total_likes' => 0, 'total_comments' => 0,
                ]];
            }

            $posts = DB::table('instagram_media')
                ->whereIn('account_id', $accountIds)
                ->where('posted_at', '>=', now()->subDays($days))
                ->select(['id', 'caption', 'media_type', 'like_count', 'comments_count',
                    'shares', 'reach', 'impressions', 'engagement_rate', 'posted_at'])
                ->orderByDesc('posted_at')
                ->limit(20)
                ->get()
                ->toArray();

            $totals = DB::table('instagram_media')
                ->whereIn('account_id', $accountIds)
                ->where('posted_at', '>=', now()->subDays($days))
                ->selectRaw('
                    COUNT(*) as post_count,
                    COALESCE(AVG(engagement_rate), 0) as avg_engagement,
                    COALESCE(SUM(reach), 0) as total_reach,
                    COALESCE(SUM(like_count), 0) as total_likes,
                    COALESCE(SUM(comments_count), 0) as total_comments
                ')
                ->first();

            return [
                'success' => true,
                'period_days' => $days,
                'posts' => $posts,
                'summary' => [
                    'post_count' => (int) $totals->post_count,
                    'avg_engagement' => round((float) $totals->avg_engagement, 2),
                    'total_reach' => (int) $totals->total_reach,
                    'total_likes' => (int) $totals->total_likes,
                    'total_comments' => (int) $totals->total_comments,
                ],
            ];
        } catch (\Exception $e) {
            Log::warning('ContentAnalysisTool: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eng yaxshi va eng yomon postlarni aniqlash
     */
    public function getTopAndWorstPosts(string $businessId, int $days = 30, int $limit = 3): array
    {
        try {
            $accountIds = $this->getAccountIds($businessId);
            if (empty($accountIds)) return ['success' => true, 'top_posts' => [], 'worst_posts' => []];

            $top = DB::table('instagram_media')
                ->whereIn('account_id', $accountIds)
                ->where('posted_at', '>=', now()->subDays($days))
                ->orderByDesc('engagement_rate')
                ->limit($limit)
                ->get(['id', 'caption', 'media_type', 'engagement_rate', 'reach', 'posted_at']);

            $worst = DB::table('instagram_media')
                ->whereIn('account_id', $accountIds)
                ->where('posted_at', '>=', now()->subDays($days))
                ->orderBy('engagement_rate')
                ->limit($limit)
                ->get(['id', 'caption', 'media_type', 'engagement_rate', 'reach', 'posted_at']);

            return ['success' => true, 'top_posts' => $top->toArray(), 'worst_posts' => $worst->toArray()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kontent turi bo'yicha samaradorlik
     */
    public function getPerformanceByContentType(string $businessId, int $days = 30): array
    {
        try {
            $accountIds = $this->getAccountIds($businessId);
            if (empty($accountIds)) return ['success' => true, 'by_type' => []];

            $byType = DB::table('instagram_media')
                ->whereIn('account_id', $accountIds)
                ->where('posted_at', '>=', now()->subDays($days))
                ->select('media_type')
                ->selectRaw('COUNT(*) as count, COALESCE(AVG(engagement_rate), 0) as avg_engagement, COALESCE(AVG(reach), 0) as avg_reach')
                ->groupBy('media_type')
                ->get()
                ->toArray();

            return ['success' => true, 'by_type' => $byType];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
