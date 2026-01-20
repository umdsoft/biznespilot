<?php

namespace App\Services\Sales;

use App\Models\BusinessUser;
use App\Models\SalesKpiPeriodSummary;
use App\Models\SalesLeaderboardEntry;
use App\Models\SalesLeaderboardRecord;
use App\Models\SalesUserPoints;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasLeaderboardRanking;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaderboardService - Sales leaderboard boshqarish
 *
 * DRY: HasPeriodCalculation, HasLeaderboardRanking va HasKpiCalculation traitlardan foydalanadi
 */
class LeaderboardService
{
    use HasPeriodCalculation;
    use HasLeaderboardRanking;
    use HasKpiCalculation;
    /**
     * Cache muddati (daqiqa)
     */
    protected const CACHE_TTL = 15;

    /**
     * Leaderboard yangilash
     */
    public function updateLeaderboard(string $businessId, string $periodType, ?Carbon $date = null): Collection
    {
        $date = $date ?? now();
        [$periodStart, $periodEnd] = $this->getPeriodDates($periodType, $date);

        Log::info('Updating leaderboard', [
            'business_id' => $businessId,
            'period_type' => $periodType,
            'period_start' => $periodStart->format('Y-m-d'),
        ]);

        // Sotuv xodimlarini olish
        $userIds = $this->getSalesTeamUserIds($businessId);

        if ($userIds->isEmpty()) {
            return collect();
        }

        // KPI summarylardan ma'lumot olish
        $summaries = SalesKpiPeriodSummary::forBusiness($businessId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        // Leaderboard yozuvlarini yaratish/yangilash
        $entries = collect();
        $scores = [];

        foreach ($userIds as $userId) {
            $summary = $summaries->get($userId);

            $entry = $this->createOrUpdateEntry(
                $businessId,
                $userId,
                $periodType,
                $periodStart,
                $periodEnd,
                $summary
            );

            $entries->push($entry);
            $scores[$userId] = $entry->weighted_score;
        }

        // Reytinglarni hisoblash
        arsort($scores);
        $rank = 0;
        $previousScore = null;
        $sameRankCount = 0;

        foreach ($scores as $userId => $score) {
            if ($score !== $previousScore) {
                $rank += $sameRankCount + 1;
                $sameRankCount = 0;
            } else {
                $sameRankCount++;
            }

            $entry = $entries->firstWhere('user_id', $userId);
            $this->updateEntryRank($entry, $rank);

            $previousScore = $score;
        }

        // Cache yangilash
        $this->clearLeaderboardCache($businessId, $periodType);

        Log::info('Leaderboard updated', [
            'business_id' => $businessId,
            'period_type' => $periodType,
            'entries_count' => $entries->count(),
        ]);

        return $entries;
    }

    /**
     * Leaderboard yozuvi yaratish/yangilash
     */
    protected function createOrUpdateEntry(
        string $businessId,
        string $userId,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?SalesKpiPeriodSummary $summary
    ): SalesLeaderboardEntry {
        // Oldingi davrdagi reytingni olish
        $previousRank = $this->getPreviousRank($businessId, $userId, $periodType, $periodStart);

        $data = [
            'total_score' => $summary?->overall_score ?? 0,
            'weighted_score' => $summary?->weighted_score ?? 0,
            'kpi_scores' => $summary?->kpi_scores ?? [],
            'leads_converted' => $summary?->kpi_scores['leads_converted']['value'] ?? 0,
            'revenue' => $summary?->kpi_scores['revenue']['value'] ?? 0,
            'calls_made' => $summary?->kpi_scores['calls_made']['value'] ?? 0,
            'tasks_completed' => $summary?->kpi_scores['tasks_completed']['value'] ?? 0,
            'conversion_rate' => $summary?->kpi_scores['conversion_rate']['value'] ?? 0,
            'avg_deal_size' => $summary?->kpi_scores['avg_deal_size']['value'] ?? 0,
            'previous_rank' => $previousRank,
        ];

        return SalesLeaderboardEntry::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'period_type' => $periodType,
                'period_start' => $periodStart->format('Y-m-d'),
            ],
            array_merge($data, [
                'period_end' => $periodEnd->format('Y-m-d'),
            ])
        );
    }

    /**
     * Reyting yangilash
     */
    protected function updateEntryRank(SalesLeaderboardEntry $entry, int $rank): void
    {
        $rankChange = 0;
        $medal = null;
        $isTopPerformer = false;

        if ($entry->previous_rank) {
            $rankChange = $entry->previous_rank - $rank;
        }

        // Medal belgilash (top 3)
        if ($rank === 1) {
            $medal = 'gold';
            $isTopPerformer = true;
        } elseif ($rank === 2) {
            $medal = 'silver';
            $isTopPerformer = true;
        } elseif ($rank === 3) {
            $medal = 'bronze';
            $isTopPerformer = true;
        }

        $entry->update([
            'rank' => $rank,
            'rank_change' => $rankChange,
            'medal' => $medal,
            'is_top_performer' => $isTopPerformer,
        ]);

        // Medal uchun ball berish (haftalik va oylik uchun)
        if ($medal && in_array($entry->period_type, ['weekly', 'monthly'])) {
            SalesUserPoints::addMedal($entry->business_id, $entry->user_id, $medal);

            // Rekord tekshirish
            $this->checkAndCreateRecords($entry);
        }
    }

    /**
     * Oldingi davrdagi reyting
     */
    protected function getPreviousRank(
        string $businessId,
        string $userId,
        string $periodType,
        Carbon $currentPeriodStart
    ): ?int {
        $previousStart = match ($periodType) {
            'daily' => $currentPeriodStart->copy()->subDay(),
            'weekly' => $currentPeriodStart->copy()->subWeek(),
            'monthly' => $currentPeriodStart->copy()->subMonth(),
            default => $currentPeriodStart->copy()->subDay(),
        };

        return SalesLeaderboardEntry::forBusiness($businessId)
            ->forUser($userId)
            ->where('period_type', $periodType)
            ->where('period_start', $previousStart->format('Y-m-d'))
            ->value('rank');
    }

    /**
     * Rekordlarni tekshirish va yaratish
     */
    protected function checkAndCreateRecords(SalesLeaderboardEntry $entry): void
    {
        $recordTypes = [
            'highest_'.$entry->period_type.'_score' => $entry->total_score,
            'highest_revenue_'.str_replace('ly', '', $entry->period_type) => $entry->revenue,
            'most_leads_'.str_replace('ly', '', $entry->period_type) => $entry->leads_converted,
        ];

        foreach ($recordTypes as $recordType => $value) {
            if ($value > 0) {
                SalesLeaderboardRecord::checkAndCreateRecord(
                    $entry->business_id,
                    $entry->user_id,
                    $recordType,
                    $entry->period_type,
                    $value,
                    ['period_start' => $entry->period_start->format('Y-m-d')]
                );
            }
        }
    }

    /**
     * Leaderboard olish
     */
    public function getLeaderboard(
        string $businessId,
        string $periodType,
        ?Carbon $date = null,
        int $limit = 10
    ): Collection {
        $date = $date ?? now();
        [$periodStart] = $this->getPeriodDates($periodType, $date);

        $cacheKey = "leaderboard:{$businessId}:{$periodType}:{$periodStart->format('Y-m-d')}:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL * 60, function () use ($businessId, $periodType, $periodStart, $limit) {
            return SalesLeaderboardEntry::forBusiness($businessId)
                ->where('period_type', $periodType)
                ->where('period_start', $periodStart->format('Y-m-d'))
                ->with('user:id,name')
                ->ranked()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Foydalanuvchi reytingi
     */
    public function getUserRanking(
        string $businessId,
        string $userId,
        string $periodType,
        ?Carbon $date = null
    ): ?SalesLeaderboardEntry {
        $date = $date ?? now();
        [$periodStart] = $this->getPeriodDates($periodType, $date);

        return SalesLeaderboardEntry::forBusiness($businessId)
            ->forUser($userId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->first();
    }

    /**
     * Foydalanuvchi atrofidagi reyting
     */
    public function getUserNeighborhood(
        string $businessId,
        string $userId,
        string $periodType,
        int $neighbors = 2
    ): array {
        $userEntry = $this->getUserRanking($businessId, $userId, $periodType);

        if (! $userEntry) {
            return [
                'user' => null,
                'above' => collect(),
                'below' => collect(),
            ];
        }

        [$periodStart] = $this->getPeriodDates($periodType, now());

        $above = SalesLeaderboardEntry::forBusiness($businessId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->where('rank', '<', $userEntry->rank)
            ->with('user:id,name')
            ->orderByDesc('rank')
            ->limit($neighbors)
            ->get()
            ->reverse()
            ->values();

        $below = SalesLeaderboardEntry::forBusiness($businessId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->where('rank', '>', $userEntry->rank)
            ->with('user:id,name')
            ->ranked()
            ->limit($neighbors)
            ->get();

        return [
            'user' => $userEntry,
            'above' => $above,
            'below' => $below,
        ];
    }

    /**
     * Barcha davrlar uchun leaderboard summary
     */
    public function getLeaderboardSummary(string $businessId, string $userId): array
    {
        $periods = ['daily', 'weekly', 'monthly'];
        $result = [];

        foreach ($periods as $periodType) {
            $entry = $this->getUserRanking($businessId, $userId, $periodType);
            $leaderboard = $this->getLeaderboard($businessId, $periodType, null, 5);

            $result[$periodType] = [
                'user_rank' => $entry?->rank,
                'user_score' => $entry?->total_score ?? 0,
                'rank_change' => $entry?->rank_change ?? 0,
                'medal' => $entry?->medal,
                'total_participants' => $leaderboard->count(),
                'top_5' => $leaderboard,
            ];
        }

        return $result;
    }

    /**
     * Medal statistikasi
     */
    public function getMedalStats(string $businessId, ?Carbon $startDate = null): array
    {
        $startDate = $startDate ?? now()->startOfYear();

        $stats = SalesLeaderboardEntry::forBusiness($businessId)
            ->whereNotNull('medal')
            ->where('period_start', '>=', $startDate->format('Y-m-d'))
            ->select('user_id', 'medal', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'medal')
            ->get();

        $userMedals = [];
        foreach ($stats as $stat) {
            if (! isset($userMedals[$stat->user_id])) {
                $userMedals[$stat->user_id] = [
                    'gold' => 0,
                    'silver' => 0,
                    'bronze' => 0,
                    'total' => 0,
                ];
            }
            $userMedals[$stat->user_id][$stat->medal] = $stat->count;
            $userMedals[$stat->user_id]['total'] += $stat->count;
        }

        return $userMedals;
    }

    /**
     * Top performerlar (ko'p medallar olganlari)
     */
    public function getTopPerformers(string $businessId, int $limit = 5): Collection
    {
        return SalesUserPoints::forBusiness($businessId)
            ->with('user:id,name')
            ->orderByRaw('gold_medals * 3 + silver_medals * 2 + bronze_medals DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Sotuv jamoa a'zolarini olish
     */
    protected function getSalesTeamUserIds(string $businessId): Collection
    {
        return BusinessUser::where('business_id', $businessId)
            ->whereIn('department', ['sales_operator', 'sales_head'])
            ->whereNotNull('accepted_at')
            ->pluck('user_id');
    }

    /**
     * Davr sanalarini hisoblash
     */
    protected function getPeriodDates(string $periodType, Carbon $date): array
    {
        return match ($periodType) {
            'daily' => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
            'weekly' => [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
            default => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Cache tozalash
     */
    protected function clearLeaderboardCache(string $businessId, string $periodType): void
    {
        // Eng ko'p ishlatiladigan limitlar uchun cache tozalash
        $commonLimits = [5, 10, 20, 50];
        [$periodStart] = $this->getPeriodDates($periodType, now());
        $periodKey = $periodStart->format('Y-m-d');

        foreach ($commonLimits as $limit) {
            Cache::forget("leaderboard:{$businessId}:{$periodType}:{$periodKey}:{$limit}");
        }
    }

    /**
     * Biznes rekordlarini olish
     */
    public function getBusinessRecords(string $businessId): Collection
    {
        return SalesLeaderboardRecord::forBusiness($businessId)
            ->with('user:id,name')
            ->orderByDesc('achieved_at')
            ->limit(10)
            ->get();
    }

    /**
     * Barcha davrlar uchun leaderboard yangilash
     */
    public function updateAllLeaderboards(string $businessId): void
    {
        $periods = ['daily', 'weekly', 'monthly'];

        foreach ($periods as $periodType) {
            try {
                $this->updateLeaderboard($businessId, $periodType);
            } catch (\Exception $e) {
                Log::error("Failed to update {$periodType} leaderboard", [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Foydalanuvchi ballini yangilash (observer lardan chaqiriladi)
     *
     * @param string $businessId
     * @param string $userId
     * @param string $actionType - lead_converted, task_completed, call_completed
     * @param float $value - qo'shimcha qiymat (masalan, deal summasi)
     */
    public function updateUserScore(
        string $businessId,
        string $userId,
        string $actionType,
        float $value = 0
    ): void {
        try {
            // Action type ga qarab ball hisoblash
            $points = $this->calculateActionPoints($actionType, $value);

            // User points jadvaliga qo'shish
            SalesUserPoints::addPoints($businessId, $userId, $points, $actionType);

            // Kunlik leaderboard ni yangilash (cache orqali)
            $cacheKey = "leaderboard_update:{$businessId}:daily";
            $lastUpdate = Cache::get($cacheKey);

            // Har 5 daqiqada bir marta yangilash
            if (!$lastUpdate || $lastUpdate < now()->subMinutes(5)->timestamp) {
                $this->updateLeaderboard($businessId, 'daily');
                Cache::put($cacheKey, now()->timestamp, 300);
            }

            Log::debug('User score updated', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'action' => $actionType,
                'points' => $points,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user score', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'action' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Action turiga qarab ball hisoblash
     */
    protected function calculateActionPoints(string $actionType, float $value): int
    {
        return match ($actionType) {
            'lead_converted' => 100 + (int) ($value / 100000), // 100 ball + har 100K uchun 1 ball
            'task_completed' => 15,
            'call_completed' => (int) $value, // Qo'ng'iroq davomiyligi asosida
            'meeting_completed' => 50,
            'proposal_sent' => 30,
            default => 10,
        };
    }

    /**
     * Leaderboard ni qayta hisoblash (Job dan chaqiriladi)
     */
    public function recalculateLeaderboard(string $businessId, string $periodType): void
    {
        $this->updateLeaderboard($businessId, $periodType);
    }

    // ==================== ORCHESTRATOR HELPER METODLARI ====================

    /**
     * Kunlik leaderboard ni hisoblash va top performers qaytarish
     * SalesOrchestrator dan chaqiriladi
     */
    public function calculateDaily(string $businessId): array
    {
        $this->updateLeaderboard($businessId, 'daily');

        $leaderboard = $this->getLeaderboard($businessId, 'daily', null, 10);

        return $leaderboard->map(fn ($entry) => [
            'user_id' => $entry->user_id,
            'name' => $entry->user?->name ?? 'Unknown',
            'score' => $entry->total_score,
            'rank' => $entry->rank,
            'medal' => $entry->medal,
        ])->toArray();
    }
}
