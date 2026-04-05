<?php

namespace App\Services\Agent\HealthMonitor\Calculators;

use Illuminate\Support\Facades\DB;

/**
 * Marketing sog'ligi kalkulyatori (bazadan, bepul).
 * Ball = post_regularity(30%) + engagement_trend(25%) + reach_growth(25%) + competitor(20%)
 */
class MarketingHealthCalculator
{
    public function calculate(string $businessId): array
    {
        $postRegularity = $this->getPostRegularity($businessId);
        $engagementTrend = $this->getEngagementTrend($businessId);
        $reachGrowth = $this->getReachGrowth($businessId);
        $competitorPosition = $this->getCompetitorPosition($businessId);

        $score = (int) round(
            $postRegularity * 0.30
            + $engagementTrend * 0.25
            + $reachGrowth * 0.25
            + $competitorPosition * 0.20
        );

        return [
            'score' => min(100, max(0, $score)),
            'details' => [
                'post_regularity' => $postRegularity,
                'engagement_trend' => $engagementTrend,
                'reach_growth' => $reachGrowth,
                'competitor_position' => $competitorPosition,
            ],
        ];
    }

    /**
     * Biznesning instagram account id larini olish (instagram_media bilan bog'lash uchun)
     */
    private function getAccountIds(string $businessId): array
    {
        return DB::table('instagram_accounts')
            ->where('business_id', $businessId)
            ->pluck('id')
            ->toArray();
    }

    private function getPostRegularity(string $businessId, int $target = 5): int
    {
        $accountIds = $this->getAccountIds($businessId);
        if (empty($accountIds)) return 50;

        $count = DB::table('instagram_media')
            ->whereIn('account_id', $accountIds)
            ->where('posted_at', '>=', now()->subDays(7))
            ->count();

        return min(100, (int) round(($count / max($target, 1)) * 100));
    }

    private function getEngagementTrend(string $businessId): int
    {
        $accountIds = $this->getAccountIds($businessId);
        if (empty($accountIds)) return 50;

        $thisWeek = (float) DB::table('instagram_media')
            ->whereIn('account_id', $accountIds)
            ->whereBetween('posted_at', [now()->subDays(7), now()])
            ->avg('engagement_rate') ?? 0;

        $lastWeek = (float) DB::table('instagram_media')
            ->whereIn('account_id', $accountIds)
            ->whereBetween('posted_at', [now()->subDays(14), now()->subDays(7)])
            ->avg('engagement_rate') ?? 0;

        if ($lastWeek == 0) return $thisWeek > 0 ? 100 : 50;
        return min(100, max(0, (int) round(($thisWeek / $lastWeek) * 100)));
    }

    private function getReachGrowth(string $businessId): int
    {
        $accountIds = $this->getAccountIds($businessId);
        if (empty($accountIds)) return 50;

        $thisWeek = (int) DB::table('instagram_media')
            ->whereIn('account_id', $accountIds)
            ->whereBetween('posted_at', [now()->subDays(7), now()])
            ->sum('reach');

        $lastWeek = (int) DB::table('instagram_media')
            ->whereIn('account_id', $accountIds)
            ->whereBetween('posted_at', [now()->subDays(14), now()->subDays(7)])
            ->sum('reach');

        if ($lastWeek == 0) return $thisWeek > 0 ? 100 : 50;
        return min(100, max(0, (int) round(($thisWeek / $lastWeek) * 100)));
    }

    private function getCompetitorPosition(string $businessId): int
    {
        return 60; // Default — to'liq ma'lumot kerak bo'lganda kengaytiriladi
    }
}
