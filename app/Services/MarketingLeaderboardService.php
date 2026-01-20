<?php

namespace App\Services;

use App\Models\Business;
use App\Models\MarketingLeaderboard;
use App\Models\MarketingUserKpi;
use App\Models\User;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasLeaderboardRanking;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MarketingLeaderboardService - Marketing leaderboard boshqarish
 *
 * DRY: HasPeriodCalculation, HasLeaderboardRanking va HasKpiCalculation traitlardan foydalanadi
 */
class MarketingLeaderboardService
{
    use HasPeriodCalculation;
    use HasLeaderboardRanking;
    use HasKpiCalculation;
    // XP rewards
    private const XP_PER_LEAD = 10;
    private const XP_PER_CONVERSION = 50;
    private const XP_BONUS_TOP_3 = 100;
    private const XP_STREAK_BONUS = 25;

    // Coin rewards
    private const COINS_PER_TARGET_MET = 50;
    private const COINS_TOP_1 = 200;
    private const COINS_TOP_2 = 100;
    private const COINS_TOP_3 = 50;

    public function updateWeeklyLeaderboard(Business $business): Collection
    {
        $periodStart = now()->startOfWeek();
        $periodEnd = now()->endOfWeek();

        return $this->calculateLeaderboard($business, $periodStart, $periodEnd, 'weekly');
    }

    public function updateMonthlyLeaderboard(Business $business): Collection
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        return $this->calculateLeaderboard($business, $periodStart, $periodEnd, 'monthly');
    }

    public function calculateLeaderboard(
        Business $business,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $periodType
    ): Collection {
        // Get all marketing users with their KPIs
        $users = $business->users()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'marketing');
            })
            ->get();

        if ($users->isEmpty()) {
            return collect();
        }

        // Calculate scores for each user
        $scores = [];
        foreach ($users as $user) {
            $kpi = MarketingUserKpi::where('business_id', $business->id)
                ->where('user_id', $user->id)
                ->where('period_start', $periodStart)
                ->where('period_type', $periodType)
                ->first();

            if (!$kpi) {
                continue;
            }

            $scores[$user->id] = [
                'user' => $user,
                'kpi' => $kpi,
                'overall_score' => $this->calculateOverallScore($kpi),
                'leads_score' => $kpi->leads_count,
                'conversion_score' => $kpi->converted_leads,
                'roi_score' => $kpi->getRoiAttribute(),
            ];
        }

        // Sort and rank
        $ranked = collect($scores)->sortByDesc('overall_score')->values();

        // Calculate individual category ranks
        $leadsRanked = collect($scores)->sortByDesc('leads_score')->values();
        $conversionRanked = collect($scores)->sortByDesc('conversion_score')->values();
        $roiRanked = collect($scores)->sortByDesc('roi_score')->values();

        $leaderboardEntries = collect();

        DB::transaction(function () use (
            $business,
            $ranked,
            $leadsRanked,
            $conversionRanked,
            $roiRanked,
            $periodStart,
            $periodEnd,
            $periodType,
            &$leaderboardEntries
        ) {
            foreach ($ranked as $rank => $data) {
                $userId = $data['user']->id;
                $overallRank = $rank + 1;

                // Find category ranks
                $leadsRank = $leadsRanked->search(fn($d) => $d['user']->id === $userId) + 1;
                $conversionRank = $conversionRanked->search(fn($d) => $d['user']->id === $userId) + 1;
                $roiRank = $roiRanked->search(fn($d) => $d['user']->id === $userId) + 1;

                // Get previous period for streak calculation
                $previousEntry = $this->getPreviousEntry($business, $userId, $periodType, $periodStart);

                // Calculate achievements, XP, and coins
                $achievements = $this->calculateAchievements($data, $overallRank, $leadsRank, $roiRank);
                $xpEarned = $this->calculateXp($data, $overallRank, $achievements);
                $coinsEarned = $this->calculateCoins($data, $overallRank);

                // Calculate streak
                $currentStreak = $this->calculateStreak($previousEntry, $overallRank);
                $bestStreak = max($currentStreak, $previousEntry?->best_streak ?? 0);

                $entry = MarketingLeaderboard::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'user_id' => $userId,
                        'period_start' => $periodStart,
                        'period_type' => $periodType,
                    ],
                    [
                        'period_end' => $periodEnd,
                        'overall_rank' => $overallRank,
                        'leads_rank' => $leadsRank,
                        'conversion_rank' => $conversionRank,
                        'roi_rank' => $roiRank,
                        'overall_score' => $data['overall_score'],
                        'leads_score' => $data['leads_score'],
                        'conversion_score' => $data['conversion_score'],
                        'roi_score' => $data['roi_score'],
                        'achievements' => $achievements,
                        'xp_earned' => $xpEarned,
                        'coins_earned' => $coinsEarned,
                        'current_streak' => $currentStreak,
                        'best_streak' => $bestStreak,
                    ]
                );

                $leaderboardEntries->push($entry);
            }
        });

        Log::info('Leaderboard updated', [
            'business_id' => $business->id,
            'period_type' => $periodType,
            'entries_count' => $leaderboardEntries->count(),
        ]);

        return $leaderboardEntries;
    }

    public function calculateOverallScore(MarketingUserKpi $kpi): float
    {
        // Weighted score calculation
        $leadsWeight = 0.30;
        $conversionWeight = 0.30;
        $roiWeight = 0.25;
        $revenueWeight = 0.15;

        // Normalize scores (assuming max values)
        $leadsScore = min(100, ($kpi->leads_count / 100) * 100);
        $conversionScore = $kpi->getConversionRateAttribute();
        $roiScore = min(100, max(0, $kpi->getRoiAttribute() + 50)); // Shift ROI to 0-100 range
        $revenueScore = min(100, ($kpi->total_revenue / 100000000) * 100); // Assuming 100M max

        return ($leadsScore * $leadsWeight) +
               ($conversionScore * $conversionWeight) +
               ($roiScore * $roiWeight) +
               ($revenueScore * $revenueWeight);
    }

    public function calculateAchievements(array $data, int $overallRank, int $leadsRank, int $roiRank): array
    {
        $achievements = [];

        // Top Performer
        if ($overallRank === 1) {
            $achievements[] = MarketingLeaderboard::ACHIEVEMENT_TOP_PERFORMER;
        }

        // Lead Generator
        if ($leadsRank === 1) {
            $achievements[] = MarketingLeaderboard::ACHIEVEMENT_LEAD_GENERATOR;
        }

        // ROI Champion
        if ($roiRank === 1) {
            $achievements[] = MarketingLeaderboard::ACHIEVEMENT_ROI_CHAMPION;
        }

        // Consistent performer (top 5 for 3+ periods)
        // This would need historical data check
        if ($overallRank <= 5) {
            $achievements[] = MarketingLeaderboard::ACHIEVEMENT_CONSISTENT;
        }

        return $achievements;
    }

    public function calculateXp(array $data, int $rank, array $achievements): int
    {
        $xp = 0;

        // XP from leads and conversions
        $xp += $data['leads_score'] * self::XP_PER_LEAD;
        $xp += $data['conversion_score'] * self::XP_PER_CONVERSION;

        // Rank bonus
        if ($rank <= 3) {
            $xp += self::XP_BONUS_TOP_3;
        }

        // Achievement XP
        foreach ($achievements as $achievement) {
            $xp += MarketingLeaderboard::ACHIEVEMENTS[$achievement]['xp'] ?? 0;
        }

        return $xp;
    }

    public function calculateCoins(array $data, int $rank): int
    {
        $coins = 0;

        // Target completion bonus
        if ($data['kpi']->target_completion >= 100) {
            $coins += self::COINS_PER_TARGET_MET;
        }

        // Rank bonus
        $coins += match ($rank) {
            1 => self::COINS_TOP_1,
            2 => self::COINS_TOP_2,
            3 => self::COINS_TOP_3,
            default => 0,
        };

        return $coins;
    }

    public function calculateStreak(?MarketingLeaderboard $previousEntry, int $currentRank): int
    {
        if (!$previousEntry) {
            return $currentRank <= 3 ? 1 : 0;
        }

        // Streak continues if user stays in top 3
        if ($currentRank <= 3 && $previousEntry->overall_rank <= 3) {
            return $previousEntry->current_streak + 1;
        }

        return $currentRank <= 3 ? 1 : 0;
    }

    public function getPreviousEntry(
        Business $business,
        string $userId,
        string $periodType,
        Carbon $currentPeriodStart
    ): ?MarketingLeaderboard {
        $previousPeriodStart = $periodType === 'weekly'
            ? $currentPeriodStart->copy()->subWeek()
            : $currentPeriodStart->copy()->subMonth();

        return MarketingLeaderboard::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->where('period_type', $periodType)
            ->where('period_start', $previousPeriodStart)
            ->first();
    }

    public function getLeaderboard(
        Business $business,
        string $periodType = 'weekly',
        ?Carbon $periodStart = null,
        int $limit = 10
    ): Collection {
        $periodStart = $periodStart ?? ($periodType === 'weekly' ? now()->startOfWeek() : now()->startOfMonth());

        return MarketingLeaderboard::where('business_id', $business->id)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->topPerformers($limit)
            ->with('user')
            ->get();
    }

    public function getUserRanking(Business $business, User $user, string $periodType = 'monthly'): ?MarketingLeaderboard
    {
        $periodStart = $periodType === 'weekly' ? now()->startOfWeek() : now()->startOfMonth();

        return MarketingLeaderboard::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->first();
    }

    public function getUserHistory(Business $business, User $user, int $periods = 6): Collection
    {
        return MarketingLeaderboard::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderBy('period_start', 'desc')
            ->limit($periods)
            ->get();
    }

    public function getAchievementStats(Business $business, ?string $periodType = null): array
    {
        $query = MarketingLeaderboard::where('business_id', $business->id);

        if ($periodType) {
            $query->where('period_type', $periodType);
        }

        $entries = $query->get();

        $stats = [];
        foreach (MarketingLeaderboard::ACHIEVEMENTS as $key => $info) {
            $count = $entries->filter(fn($e) => in_array($key, $e->achievements ?? []))->count();
            $stats[$key] = [
                'name' => $info['name'],
                'icon' => $info['icon'],
                'count' => $count,
            ];
        }

        return $stats;
    }

    public function getTotalXpAndCoins(Business $business, User $user): array
    {
        $totals = MarketingLeaderboard::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->selectRaw('SUM(xp_earned) as total_xp, SUM(coins_earned) as total_coins')
            ->first();

        return [
            'total_xp' => $totals->total_xp ?? 0,
            'total_coins' => $totals->total_coins ?? 0,
            'level' => $this->calculateLevel($totals->total_xp ?? 0),
        ];
    }

    public function calculateLevel(int $totalXp): int
    {
        // XP requirements per level (exponential growth)
        $xpPerLevel = [
            1 => 0,
            2 => 500,
            3 => 1500,
            4 => 3500,
            5 => 7000,
            6 => 12000,
            7 => 20000,
            8 => 32000,
            9 => 50000,
            10 => 75000,
        ];

        $level = 1;
        foreach ($xpPerLevel as $lvl => $required) {
            if ($totalXp >= $required) {
                $level = $lvl;
            }
        }

        return $level;
    }

    public function getStreakLeaders(Business $business, int $limit = 5): Collection
    {
        return MarketingLeaderboard::where('business_id', $business->id)
            ->where('current_streak', '>', 0)
            ->orderBy('current_streak', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();
    }
}
