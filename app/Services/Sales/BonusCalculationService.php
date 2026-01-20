<?php

namespace App\Services\Sales;

use App\Models\BusinessUser;
use App\Models\SalesBonusCalculation;
use App\Models\SalesBonusSetting;
use App\Models\SalesKpiPeriodSummary;
use App\Models\SalesPenalty;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BonusCalculationService - Sales bonuslar hisoblash
 *
 * DRY: HasPeriodCalculation va HasKpiCalculation traitlardan foydalanadi
 */
class BonusCalculationService
{
    use HasPeriodCalculation;
    use HasKpiCalculation;
    public function __construct(
        protected KpiCalculationService $kpiCalculator
    ) {}

    /**
     * Biznes uchun oylik bonuslarni hisoblash
     */
    public function calculateMonthlyBonuses(
        string $businessId,
        Carbon $month,
        ?string $calculatedBy = null
    ): Collection {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        // Faol bonus sozlamalarini olish
        $bonusSettings = SalesBonusSetting::forBusiness($businessId)
            ->active()
            ->forPeriod('monthly')
            ->autoCalculate()
            ->get();

        if ($bonusSettings->isEmpty()) {
            Log::info('No active bonus settings for business', [
                'business_id' => $businessId,
                'period' => $month->format('Y-m'),
            ]);

            return collect();
        }

        // Sotuv xodimlarini olish
        $operators = BusinessUser::where('business_id', $businessId)
            ->whereIn('department', ['sales_operator', 'sales_head'])
            ->whereNotNull('accepted_at')
            ->get();

        $calculations = collect();

        foreach ($operators as $operator) {
            foreach ($bonusSettings as $bonusSetting) {
                // Rol tekshirish
                if (! $bonusSetting->appliesTo($operator->department)) {
                    continue;
                }

                try {
                    $calculation = $this->calculateUserBonus(
                        $businessId,
                        $operator->user_id,
                        $bonusSetting,
                        $periodStart,
                        $periodEnd,
                        $calculatedBy
                    );

                    $calculations->push($calculation);
                } catch (\Exception $e) {
                    Log::error('Failed to calculate bonus for user', [
                        'business_id' => $businessId,
                        'user_id' => $operator->user_id,
                        'bonus_setting_id' => $bonusSetting->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('Monthly bonuses calculated', [
            'business_id' => $businessId,
            'period' => $month->format('Y-m'),
            'calculations_count' => $calculations->count(),
        ]);

        return $calculations;
    }

    /**
     * Bitta foydalanuvchi uchun bonus hisoblash
     */
    public function calculateUserBonus(
        string $businessId,
        string $userId,
        SalesBonusSetting $bonusSetting,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?string $calculatedBy = null
    ): SalesBonusCalculation {
        // KPI summaryni olish
        $kpiSummary = SalesKpiPeriodSummary::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriodType('monthly')
            ->forPeriodStart($periodStart)
            ->first();

        $kpiScore = $kpiSummary?->overall_score ?? 0;

        // Revenue hisoblash
        $kpiResults = $this->kpiCalculator->calculateForUser($businessId, $userId, 'monthly', $periodStart);
        $revenueKpi = collect($kpiResults['kpis'])->firstWhere('kpi_type', 'revenue');
        $totalRevenue = $revenueKpi['actual_value'] ?? 0;

        // Ish kunlari
        $workingDays = $kpiSummary?->working_days ?? $periodStart->diffInWeekdays($periodEnd);

        // Qualification tekshirish
        $qualification = $bonusSetting->checkQualification($kpiScore, $workingDays);

        // Bonus hisoblash
        $bonusCalc = $bonusSetting->calculateBonus($kpiScore, $totalRevenue);

        // Calculation yaratish yoki yangilash
        return SalesBonusCalculation::updateOrCreate(
            [
                'business_id' => $businessId,
                'bonus_setting_id' => $bonusSetting->id,
                'user_id' => $userId,
                'period_start' => $periodStart->format('Y-m-d'),
            ],
            [
                'period_type' => 'monthly',
                'period_end' => $periodEnd->format('Y-m-d'),
                'kpi_score' => $kpiScore,
                'total_revenue' => $totalRevenue,
                'working_days' => $workingDays,
                'is_qualified' => $qualification['qualified'],
                'disqualification_reason' => $qualification['disqualification_reason'],
                'base_amount' => $qualification['qualified'] ? $bonusCalc['base_amount'] : 0,
                'tier_multiplier' => $qualification['qualified'] ? $bonusCalc['multiplier'] : 1,
                'applied_tier' => $qualification['qualified'] ? $bonusCalc['applied_tier'] : null,
                'final_amount' => $qualification['qualified'] ? $bonusCalc['final_amount'] : 0,
                'calculation_breakdown' => [
                    'kpi_score' => $kpiScore,
                    'revenue' => $totalRevenue,
                    'working_days' => $workingDays,
                    'qualification' => $qualification,
                    'bonus_calculation' => $bonusCalc,
                    'calculated_at' => now()->toISOString(),
                ],
                'status' => 'calculated',
                'calculated_by' => $calculatedBy,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Bonusni tasdiqlash
     */
    public function approveBonus(string $bonusId, string $approverId, ?string $notes = null): SalesBonusCalculation
    {
        $bonus = SalesBonusCalculation::findOrFail($bonusId);

        if (! $bonus->canBeApproved()) {
            throw new \Exception("Bu bonus tasdiqlab bo'lmaydi. Status: {$bonus->status}");
        }

        $bonus->approve($approverId, $notes);

        Log::info('Bonus approved', [
            'bonus_id' => $bonusId,
            'user_id' => $bonus->user_id,
            'amount' => $bonus->final_amount,
            'approved_by' => $approverId,
        ]);

        return $bonus->fresh();
    }

    /**
     * Bonusni rad etish
     */
    public function rejectBonus(string $bonusId, string $rejectedBy, string $reason): SalesBonusCalculation
    {
        $bonus = SalesBonusCalculation::findOrFail($bonusId);

        if (! $bonus->canBeRejected()) {
            throw new \Exception("Bu bonus rad etib bo'lmaydi. Status: {$bonus->status}");
        }

        $bonus->reject($rejectedBy, $reason);

        Log::info('Bonus rejected', [
            'bonus_id' => $bonusId,
            'user_id' => $bonus->user_id,
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
        ]);

        return $bonus->fresh();
    }

    /**
     * Bonusni to'langan deb belgilash
     */
    public function markAsPaid(string $bonusId, ?string $paymentReference = null): SalesBonusCalculation
    {
        $bonus = SalesBonusCalculation::findOrFail($bonusId);

        if (! $bonus->canBePaid()) {
            throw new \Exception("Bu bonus to'lab bo'lmaydi. Status: {$bonus->status}");
        }

        $bonus->markAsPaid($paymentReference);

        Log::info('Bonus marked as paid', [
            'bonus_id' => $bonusId,
            'user_id' => $bonus->user_id,
            'amount' => $bonus->final_amount,
            'payment_reference' => $paymentReference,
        ]);

        return $bonus->fresh();
    }

    /**
     * Jarimalarni bonusga bog'lash
     */
    public function deductPenaltiesFromBonus(string $bonusId): SalesBonusCalculation
    {
        $bonus = SalesBonusCalculation::findOrFail($bonusId);

        // Confirmed jarimalarni olish
        $penalties = SalesPenalty::forBusiness($bonus->business_id)
            ->forUser($bonus->user_id)
            ->confirmed()
            ->whereNull('deducted_from_bonus_id')
            ->triggeredBetween($bonus->period_start, $bonus->period_end)
            ->get();

        DB::transaction(function () use ($bonus, $penalties) {
            foreach ($penalties as $penalty) {
                $penalty->deductFromBonus($bonus->id);
            }
        });

        Log::info('Penalties deducted from bonus', [
            'bonus_id' => $bonusId,
            'penalties_count' => $penalties->count(),
            'total_deducted' => $penalties->sum('penalty_amount'),
        ]);

        return $bonus->fresh();
    }

    /**
     * Foydalanuvchi uchun bonus summaryni olish
     */
    public function getUserBonusSummary(
        string $businessId,
        string $userId,
        int $monthsBack = 6
    ): array {
        $bonuses = SalesBonusCalculation::forBusiness($businessId)
            ->forUser($userId)
            ->where('period_start', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->orderByDesc('period_start')
            ->get();

        $totalEarned = $bonuses->where('status', 'paid')->sum('final_amount');
        $totalPending = $bonuses->whereIn('status', ['pending', 'calculated', 'approved'])->sum('final_amount');
        $totalRejected = $bonuses->where('status', 'rejected')->sum('final_amount');

        return [
            'total_earned' => $totalEarned,
            'total_pending' => $totalPending,
            'total_rejected' => $totalRejected,
            'bonuses_count' => $bonuses->count(),
            'average_bonus' => $bonuses->where('status', 'paid')->avg('final_amount') ?? 0,
            'history' => $bonuses->map(fn ($b) => [
                'id' => $b->id,
                'period' => $b->period_label,
                'amount' => $b->final_amount,
                'net_amount' => $b->net_amount,
                'status' => $b->status,
                'status_label' => $b->status_label,
                'kpi_score' => $b->kpi_score,
                'tier' => $b->applied_tier,
            ]),
        ];
    }

    /**
     * Tasdiqlash kutayotgan bonuslar
     */
    public function getAwaitingApproval(string $businessId): Collection
    {
        return SalesBonusCalculation::forBusiness($businessId)
            ->awaitingApproval()
            ->qualified()
            ->with(['user:id,name', 'bonusSetting:id,name'])
            ->orderByDesc('period_start')
            ->get();
    }

    // ==================== ORCHESTRATOR HELPER METODLARI ====================

    /**
     * KPI score asosida bonus multiplier ni olish
     * SalesOrchestrator dan chaqiriladi
     */
    public function getMultiplier(float $kpiScore): float
    {
        // Standard tiered multiplier
        return match (true) {
            $kpiScore >= 150 => 2.0,   // Super Performer
            $kpiScore >= 120 => 1.5,   // High Performer
            $kpiScore >= 100 => 1.2,   // Target Achieved
            $kpiScore >= 80 => 1.0,    // Standard
            $kpiScore >= 60 => 0.75,   // Below Target
            default => 0.5,            // Minimum
        };
    }
}
