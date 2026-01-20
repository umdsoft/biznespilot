<?php

namespace App\Services\Sales;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\SalesKpiDailySnapshot;
use App\Models\SalesKpiPeriodSummary;
use App\Models\SalesKpiSetting;
use App\Models\SalesKpiUserTarget;
use App\Models\Task;
use App\Models\User;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KpiCalculationService - Sales KPI hisoblash va snapshot yaratish
 *
 * DRY: HasPeriodCalculation va HasKpiCalculation traitlardan foydalanadi
 */
class KpiCalculationService
{
    use HasPeriodCalculation;
    use HasKpiCalculation;
    /**
     * Cache TTL (5 daqiqa)
     */
    protected int $cacheTTL = 300;

    /**
     * Pipeline stages cache (N+1 query oldini olish)
     */
    protected array $pipelineStagesCache = [];

    public function __construct(
        protected KpiSettingsService $settingsService
    ) {}

    /**
     * Won stage ni cache bilan olish
     */
    protected function getWonStage(string $businessId): ?PipelineStage
    {
        $cacheKey = "won_stage_{$businessId}";

        if (! isset($this->pipelineStagesCache[$cacheKey])) {
            $this->pipelineStagesCache[$cacheKey] = PipelineStage::where('business_id', $businessId)
                ->where('is_won', true)
                ->first();
        }

        return $this->pipelineStagesCache[$cacheKey];
    }

    /**
     * Lost stage ni cache bilan olish
     */
    protected function getLostStage(string $businessId): ?PipelineStage
    {
        $cacheKey = "lost_stage_{$businessId}";

        if (! isset($this->pipelineStagesCache[$cacheKey])) {
            $this->pipelineStagesCache[$cacheKey] = PipelineStage::where('business_id', $businessId)
                ->where('is_lost', true)
                ->first();
        }

        return $this->pipelineStagesCache[$cacheKey];
    }

    /**
     * Foydalanuvchi uchun barcha KPIlarni hisoblash (real-time)
     */
    public function calculateForUser(
        string $businessId,
        string $userId,
        string $periodType = 'monthly',
        ?Carbon $date = null
    ): array {
        $date = $date ?? now();
        $periodDates = $this->getPeriodDates($periodType, $date);

        $kpiSettings = $this->settingsService->getActiveKpisForBusiness($businessId);

        $results = [];
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($kpiSettings as $kpiSetting) {
            $actualValue = $this->calculateKpiValue(
                $businessId,
                $userId,
                $kpiSetting,
                $periodDates['start'],
                $periodDates['end']
            );

            // Maqsadni olish (user target yoki default)
            $target = $this->getTargetValue($businessId, $userId, $kpiSetting, $periodType, $date);

            $achievementPercent = $target > 0 ? round(($actualValue / $target) * 100, 1) : 0;
            $score = $kpiSetting->calculateScore($actualValue);

            $results[] = [
                'kpi_setting_id' => $kpiSetting->id,
                'kpi_type' => $kpiSetting->kpi_type,
                'name' => $kpiSetting->name,
                'category' => $kpiSetting->category,
                'measurement_unit' => $kpiSetting->measurement_unit,
                'actual_value' => $actualValue,
                'target_value' => $target,
                'achievement_percent' => $achievementPercent,
                'score' => $score,
                'weight' => $kpiSetting->weight,
                'weighted_score' => $score * ($kpiSetting->weight / 100),
                'performance_level' => $kpiSetting->getPerformanceLevel($actualValue),
                'formatted_actual' => $kpiSetting->formatValue($actualValue),
                'formatted_target' => $kpiSetting->formatValue($target),
            ];

            $totalScore += $score * ($kpiSetting->weight / 100);
            $totalWeight += $kpiSetting->weight;
        }

        $overallScore = $totalWeight > 0 ? round($totalScore / ($totalWeight / 100), 1) : 0;

        return [
            'user_id' => $userId,
            'business_id' => $businessId,
            'period_type' => $periodType,
            'period_start' => $periodDates['start']->format('Y-m-d'),
            'period_end' => $periodDates['end']->format('Y-m-d'),
            'kpis' => $results,
            'overall_score' => (int) round($overallScore),
            'total_weight' => $totalWeight,
            'performance_tier' => SalesKpiPeriodSummary::determinePerformanceTier((int) $overallScore),
            'kpis_count' => count($results),
        ];
    }

    /**
     * Bitta KPI qiymatini hisoblash
     */
    public function calculateKpiValue(
        string $businessId,
        string $userId,
        SalesKpiSetting $kpiSetting,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $type = $kpiSetting->kpi_type;

        return match ($type) {
            // Natija KPIlar
            'leads_converted' => $this->calculateLeadsConverted($businessId, $userId, $startDate, $endDate),
            'revenue' => $this->calculateRevenue($businessId, $userId, $startDate, $endDate),
            'deals_count' => $this->calculateDealsCount($businessId, $userId, $startDate, $endDate),
            'conversion_rate' => $this->calculateConversionRate($businessId, $userId, $startDate, $endDate),
            'avg_deal_size' => $this->calculateAvgDealSize($businessId, $userId, $startDate, $endDate),

            // Faoliyat KPIlar
            'calls_made' => $this->calculateCallsMade($businessId, $userId, $startDate, $endDate),
            'calls_answered' => $this->calculateCallsAnswered($businessId, $userId, $startDate, $endDate),
            'call_duration' => $this->calculateCallDuration($businessId, $userId, $startDate, $endDate),
            'tasks_completed' => $this->calculateTasksCompleted($businessId, $userId, $startDate, $endDate),
            'meetings_held' => $this->calculateMeetingsHeld($businessId, $userId, $startDate, $endDate),
            'proposals_sent' => $this->calculateProposalsSent($businessId, $userId, $startDate, $endDate),

            // Sifat KPIlar
            'response_time' => $this->calculateResponseTime($businessId, $userId, $startDate, $endDate),
            'crm_compliance' => $this->calculateCrmCompliance($businessId, $userId, $startDate, $endDate),
            'lead_touch_rate' => $this->calculateLeadTouchRate($businessId, $userId, $startDate, $endDate),
            'lost_rate' => $this->calculateLostRate($businessId, $userId, $startDate, $endDate),

            default => 0,
        };
    }

    // ==================== NATIJA KPILAR ====================

    /**
     * Sotuvga o'tgan lidlar soni
     */
    protected function calculateLeadsConverted(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $wonStage = $this->getWonStage($businessId);

        if (! $wonStage) {
            return 0;
        }

        return (float) Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', $wonStage->slug)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Umumiy sotuv summasi
     */
    protected function calculateRevenue(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $wonStage = $this->getWonStage($businessId);

        if (! $wonStage) {
            return 0;
        }

        return (float) Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', $wonStage->slug)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('estimated_value') ?? 0;
    }

    /**
     * Yopilgan bitimlar soni (revenue > 0 bo'lgan won lidlar)
     */
    protected function calculateDealsCount(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $wonStage = $this->getWonStage($businessId);

        if (! $wonStage) {
            return 0;
        }

        return (float) Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', $wonStage->slug)
            ->where('estimated_value', '>', 0)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Konversiya foizi (won / total assigned)
     */
    protected function calculateConversionRate(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $totalAssigned = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalAssigned === 0) {
            return 0;
        }

        $wonStage = $this->getWonStage($businessId);

        if (! $wonStage) {
            return 0;
        }

        $converted = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', $wonStage->slug)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return round(($converted / $totalAssigned) * 100, 1);
    }

    /**
     * O'rtacha bitim summasi
     */
    protected function calculateAvgDealSize(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $wonStage = $this->getWonStage($businessId);

        if (! $wonStage) {
            return 0;
        }

        return (float) Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', $wonStage->slug)
            ->where('estimated_value', '>', 0)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->avg('estimated_value') ?? 0;
    }

    // ==================== FAOLIYAT KPILAR ====================

    /**
     * Qilingan qo'ng'iroqlar soni
     */
    protected function calculateCallsMade(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        return (float) CallLog::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('direction', 'outbound')
            ->whereBetween('started_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Javob berilgan qo'ng'iroqlar
     */
    protected function calculateCallsAnswered(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        return (float) CallLog::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('user_id', $userId)
            ->whereIn('status', [CallLog::STATUS_ANSWERED, CallLog::STATUS_COMPLETED])
            ->whereBetween('started_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Umumiy suhbat vaqti (daqiqalarda)
     */
    protected function calculateCallDuration(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $totalSeconds = CallLog::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('user_id', $userId)
            ->whereIn('status', [CallLog::STATUS_ANSWERED, CallLog::STATUS_COMPLETED])
            ->whereBetween('started_at', [$startDate, $endDate])
            ->sum('duration') ?? 0;

        return round($totalSeconds / 60, 1); // Daqiqalarga o'girish
    }

    /**
     * Bajarilgan vazifalar
     */
    protected function calculateTasksCompleted(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        return (float) Task::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * O'tkazilgan uchrashuvlar
     */
    protected function calculateMeetingsHeld(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        return (float) Task::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('type', 'meeting')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Yuborilgan takliflar
     */
    protected function calculateProposalsSent(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        return (float) Task::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereIn('type', ['proposal', 'email'])
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();
    }

    // ==================== SIFAT KPILAR ====================

    /**
     * O'rtacha javob vaqti (soatlarda)
     */
    protected function calculateResponseTime(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        // Lidning yaratilgan vaqtidan birinchi faoliyatgacha bo'lgan vaqt
        $leads = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['activities' => function ($q) {
                $q->whereIn('type', ['call', 'meeting', 'email', 'message'])
                    ->orderBy('created_at')
                    ->limit(1);
            }])
            ->get();

        $responseTimes = [];

        foreach ($leads as $lead) {
            $firstActivity = $lead->activities->first();
            if ($firstActivity) {
                $hours = $lead->created_at->diffInHours($firstActivity->created_at);
                $responseTimes[] = $hours;
            }
        }

        if (empty($responseTimes)) {
            return 0;
        }

        return round(array_sum($responseTimes) / count($responseTimes), 1);
    }

    /**
     * CRM to'ldirilishi (kerakli maydonlar to'ldirilgan lidlar foizi)
     */
    protected function calculateCrmCompliance(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $requiredFields = ['name', 'phone', 'region', 'source_id'];

        $leads = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get($requiredFields);

        if ($leads->isEmpty()) {
            return 100; // Lid yo'q bo'lsa 100%
        }

        $compliantCount = 0;

        foreach ($leads as $lead) {
            $filled = 0;
            foreach ($requiredFields as $field) {
                if (! empty($lead->$field)) {
                    $filled++;
                }
            }

            if ($filled === count($requiredFields)) {
                $compliantCount++;
            }
        }

        return round(($compliantCount / $leads->count()) * 100, 1);
    }

    /**
     * Lidlar bilan aloqa chastotasi
     */
    protected function calculateLeadTouchRate(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $totalLeads = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalLeads === 0) {
            return 0;
        }

        $contactedLeads = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('last_contacted_at')
            ->count();

        return round(($contactedLeads / $totalLeads) * 100, 1);
    }

    /**
     * Yo'qotilgan lidlar foizi
     */
    protected function calculateLostRate(
        string $businessId,
        string $userId,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $lostStage = $this->getLostStage($businessId);

        if (! $lostStage) {
            return 0;
        }

        $totalClosed = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->whereHas('stage', fn ($q) => $q->where('is_won', true)->orWhere('is_lost', true))
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        if ($totalClosed === 0) {
            return 0;
        }

        $lost = Lead::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('assigned_to', $userId)
            ->where('status', $lostStage->slug)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        return round(($lost / $totalClosed) * 100, 1);
    }

    // ==================== YORDAMCHI METODLAR ====================

    /**
     * Maqsad qiymatini olish
     */
    protected function getTargetValue(
        string $businessId,
        string $userId,
        SalesKpiSetting $kpiSetting,
        string $periodType,
        Carbon $date
    ): float {
        // Avval foydalanuvchi uchun maxsus maqsadni tekshirish
        $userTarget = SalesKpiUserTarget::forBusiness($businessId)
            ->forUser($userId)
            ->where('kpi_setting_id', $kpiSetting->id)
            ->forPeriod($periodType, $date)
            ->active()
            ->first();

        if ($userTarget) {
            return $userTarget->effective_target;
        }

        // Standart maqsad
        return $kpiSetting->target_min ?? 0;
    }

    /**
     * Davr sanalarini hisoblash
     */
    protected function getPeriodDates(string $periodType, ?Carbon $date = null): array
    {
        $date = $date ?? now();

        return match ($periodType) {
            'daily' => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
            default => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Kunlik snapshot yaratish/yangilash
     */
    public function createDailySnapshot(
        string $businessId,
        string $userId,
        Carbon $date
    ): Collection {
        $kpiSettings = $this->settingsService->getActiveKpisForBusiness($businessId);
        $snapshots = collect();

        $periodDates = [
            'start' => $date->copy()->startOfDay(),
            'end' => $date->copy()->endOfDay(),
        ];

        DB::transaction(function () use ($businessId, $userId, $date, $kpiSettings, $periodDates, &$snapshots) {
            foreach ($kpiSettings as $kpiSetting) {
                $actualValue = $this->calculateKpiValue(
                    $businessId,
                    $userId,
                    $kpiSetting,
                    $periodDates['start'],
                    $periodDates['end']
                );

                $targetValue = $this->getTargetValue($businessId, $userId, $kpiSetting, 'daily', $date);
                $achievementPercent = $targetValue > 0 ? round(($actualValue / $targetValue) * 100, 1) : 0;
                $score = $kpiSetting->calculateScore($actualValue);

                $snapshot = SalesKpiDailySnapshot::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'kpi_setting_id' => $kpiSetting->id,
                        'snapshot_date' => $date->format('Y-m-d'),
                    ],
                    [
                        'actual_value' => $actualValue,
                        'target_value' => $targetValue,
                        'achievement_percent' => $achievementPercent,
                        'score' => $score,
                        'calculation_details' => [
                            'kpi_type' => $kpiSetting->kpi_type,
                            'calculated_at' => now()->toISOString(),
                        ],
                    ]
                );

                $snapshots->push($snapshot);
            }
        });

        return $snapshots;
    }

    /**
     * Davr yig'indisini hisoblash
     */
    public function calculatePeriodSummary(
        string $businessId,
        string $userId,
        string $periodType,
        Carbon $periodStart
    ): SalesKpiPeriodSummary {
        $periodDates = $this->getPeriodDates($periodType, $periodStart);

        // Davr uchun barcha snapshotlarni olish
        $snapshots = SalesKpiDailySnapshot::forBusiness($businessId)
            ->forUser($userId)
            ->forDateRange($periodDates['start'], $periodDates['end'])
            ->with('kpiSetting:id,kpi_type,name,weight')
            ->get();

        // KPI bo'yicha guruhlash va o'rtacha hisoblash
        $kpiScores = [];
        $groupedSnapshots = $snapshots->groupBy('kpi_setting_id');

        foreach ($groupedSnapshots as $kpiSettingId => $kpiSnapshots) {
            $kpiSetting = $kpiSnapshots->first()->kpiSetting;

            $kpiScores[] = [
                'kpi_setting_id' => $kpiSettingId,
                'kpi_type' => $kpiSetting->kpi_type,
                'name' => $kpiSetting->name,
                'actual' => round($kpiSnapshots->avg('actual_value'), 2),
                'target' => round($kpiSnapshots->avg('target_value'), 2),
                'achievement_percent' => round($kpiSnapshots->avg('achievement_percent'), 1),
                'score' => (int) round($kpiSnapshots->avg('score')),
                'weight' => $kpiSetting->weight,
            ];
        }

        // Umumiy ballni hisoblash
        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($kpiScores as $kpi) {
            $totalWeight += $kpi['weight'];
            $weightedScore += $kpi['score'] * ($kpi['weight'] / 100);
        }

        $overallScore = $totalWeight > 0 ? (int) round($weightedScore / ($totalWeight / 100)) : 0;

        // Oldingi davr rankini olish
        $previousRank = null;
        $previousSummary = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriodType($periodType)
            ->where('period_start', '<', $periodStart)
            ->orderByDesc('period_start')
            ->first();

        if ($previousSummary) {
            $previousRank = $previousSummary->rank_in_team;
        }

        // Summary yaratish
        return SalesKpiPeriodSummary::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'period_type' => $periodType,
                'period_start' => $periodDates['start']->format('Y-m-d'),
            ],
            [
                'period_end' => $periodDates['end']->format('Y-m-d'),
                'overall_score' => $overallScore,
                'total_weight' => $totalWeight,
                'weighted_score' => $weightedScore,
                'kpi_scores' => $kpiScores,
                'previous_rank' => $previousRank,
                'performance_tier' => SalesKpiPeriodSummary::determinePerformanceTier($overallScore),
                'working_days' => $periodDates['start']->diffInWeekdays($periodDates['end']),
                'active_kpis_count' => count($kpiScores),
            ]
        );
    }

    /**
     * Jamoa reytingini yangilash
     */
    public function updateTeamRankings(string $businessId, string $periodType, Carbon $periodStart): void
    {
        $summaries = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forPeriodType($periodType)
            ->forPeriodStart($periodStart)
            ->orderByDesc('overall_score')
            ->get();

        foreach ($summaries as $index => $summary) {
            $newRank = $index + 1;
            $rankChange = $summary->previous_rank ? $summary->previous_rank - $newRank : 0;

            $summary->update([
                'rank_in_team' => $newRank,
                'rank_change' => $rankChange,
            ]);
        }
    }

    // ==================== ORCHESTRATOR HELPER METODLARI ====================

    /**
     * KPI ni increment qilish (real-time update)
     * SalesOrchestrator tomonidan chaqiriladi
     */
    public function incrementKpi(string $businessId, string $userId, string $kpiType, float $amount = 1): void
    {
        $date = now();
        $kpiSetting = SalesKpiSetting::forBusiness($businessId)
            ->where('kpi_type', $kpiType)
            ->active()
            ->first();

        if (! $kpiSetting) {
            Log::debug('KpiCalculationService: KPI setting not found for increment', [
                'business_id' => $businessId,
                'kpi_type' => $kpiType,
            ]);

            return;
        }

        // Kunlik snapshot ni yangilash
        $snapshot = SalesKpiDailySnapshot::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'kpi_setting_id' => $kpiSetting->id,
                'snapshot_date' => $date->format('Y-m-d'),
            ],
            [
                'actual_value' => 0,
                'target_value' => $this->getTargetValue($businessId, $userId, $kpiSetting, 'daily', $date),
                'achievement_percent' => 0,
                'score' => 0,
            ]
        );

        $snapshot->increment('actual_value', $amount);

        // Achievement percent va score ni qayta hisoblash
        $achievementPercent = $snapshot->target_value > 0
            ? round(($snapshot->actual_value / $snapshot->target_value) * 100, 1)
            : 0;
        $score = $kpiSetting->calculateScore($snapshot->actual_value);

        $snapshot->update([
            'achievement_percent' => $achievementPercent,
            'score' => $score,
        ]);

        Log::debug('KpiCalculationService: KPI incremented', [
            'business_id' => $businessId,
            'user_id' => $userId,
            'kpi_type' => $kpiType,
            'new_value' => $snapshot->actual_value,
        ]);
    }

    /**
     * User target ni olish
     */
    public function getUserTarget(string $businessId, string $userId, string $kpiType): float
    {
        $kpiSetting = SalesKpiSetting::forBusiness($businessId)
            ->where('kpi_type', $kpiType)
            ->active()
            ->first();

        if (! $kpiSetting) {
            return 0;
        }

        return $this->getTargetValue($businessId, $userId, $kpiSetting, 'monthly', now());
    }

    /**
     * Joriy KPI qiymatini olish
     */
    public function getCurrentValue(string $businessId, string $userId, string $kpiType): float
    {
        $kpiSetting = SalesKpiSetting::forBusiness($businessId)
            ->where('kpi_type', $kpiType)
            ->active()
            ->first();

        if (! $kpiSetting) {
            return 0;
        }

        $periodDates = $this->getPeriodDates('monthly', now());

        return $this->calculateKpiValue(
            $businessId,
            $userId,
            $kpiSetting,
            $periodDates['start'],
            $periodDates['end']
        );
    }

    /**
     * Conversion rate ni qayta hisoblash
     */
    public function recalculateConversionRate(string $businessId, string $userId): void
    {
        $kpiSetting = SalesKpiSetting::forBusiness($businessId)
            ->where('kpi_type', 'conversion_rate')
            ->active()
            ->first();

        if (! $kpiSetting) {
            return;
        }

        $periodDates = $this->getPeriodDates('monthly', now());
        $actualValue = $this->calculateConversionRate($businessId, $userId, $periodDates['start'], $periodDates['end']);

        SalesKpiDailySnapshot::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'kpi_setting_id' => $kpiSetting->id,
                'snapshot_date' => now()->format('Y-m-d'),
            ],
            [
                'actual_value' => $actualValue,
                'target_value' => $this->getTargetValue($businessId, $userId, $kpiSetting, 'monthly', now()),
                'achievement_percent' => $actualValue,
                'score' => $kpiSetting->calculateScore($actualValue),
            ]
        );
    }
}
