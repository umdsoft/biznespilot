<?php

namespace App\Services;

use App\Models\Business;
use App\Models\MarketingBonus;
use App\Models\MarketingPenalty;
use App\Models\MarketingTarget;
use App\Models\MarketingUserKpi;
use App\Models\User;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MarketingBonusCalculatorService - Marketing bonuslar hisoblash
 *
 * DRY: HasPeriodCalculation va HasKpiCalculation traitlardan foydalanadi
 */
class MarketingBonusCalculatorService
{
    use HasPeriodCalculation;
    use HasKpiCalculation;
    // Bonus coefficients
    private const LEAD_BONUS_PER_LEAD = 5000; // 5,000 so'm per lead
    private const CPL_BONUS_PERCENT = 0.10; // 10% of saved budget
    private const ROAS_BONUS_PERCENT = 0.05; // 5% of extra revenue
    private const ACCELERATOR_THRESHOLD = 1.2; // 120% target completion
    private const ACCELERATOR_MULTIPLIER = 1.5; // 50% extra bonus

    public function calculateMonthlyBonus(Business $business, User $user, Carbon $month): ?MarketingBonus
    {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        // Get user KPI for the period
        $kpi = MarketingUserKpi::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('period_start', $periodStart)
            ->where('period_type', 'monthly')
            ->first();

        if (!$kpi) {
            Log::warning('No KPI found for bonus calculation', [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'period' => $periodStart->format('Y-m'),
            ]);
            return null;
        }

        // Get targets
        $targets = MarketingTarget::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->where('period_type', 'monthly')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->get()
            ->keyBy('target_type');

        return DB::transaction(function () use ($business, $user, $kpi, $targets, $periodStart, $periodEnd) {
            // Calculate bonus components
            $leadBonus = $this->calculateLeadBonus($kpi, $targets->get('leads'));
            $cplBonus = $this->calculateCplBonus($kpi, $targets->get('cpl'));
            $roasBonus = $this->calculateRoasBonus($kpi, $targets->get('roas'));
            $acceleratorBonus = $this->calculateAcceleratorBonus($kpi, $targets);

            $baseAmount = $leadBonus + $cplBonus + $roasBonus;
            $totalBonus = $baseAmount + $acceleratorBonus;

            // Get penalties for the period
            $penalties = MarketingPenalty::where('business_id', $business->id)
                ->where('user_id', $user->id)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->applied()
                ->sum('amount');

            $finalAmount = max(0, $totalBonus - $penalties);

            // Create or update bonus record
            $bonus = MarketingBonus::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'period_start' => $periodStart,
                    'period_type' => 'monthly',
                ],
                [
                    'period_end' => $periodEnd,
                    'base_amount' => $baseAmount,
                    'lead_bonus' => $leadBonus,
                    'cpl_bonus' => $cplBonus,
                    'roas_bonus' => $roasBonus,
                    'accelerator_bonus' => $acceleratorBonus,
                    'penalty_deduction' => $penalties,
                    'final_amount' => $finalAmount,
                    'performance_data' => [
                        'leads_count' => $kpi->leads_count,
                        'qualified_leads' => $kpi->qualified_leads,
                        'converted_leads' => $kpi->converted_leads,
                        'cpl_actual' => $kpi->cpl_actual,
                        'roas_actual' => $kpi->roas_actual,
                        'roi_actual' => $kpi->roi_actual,
                        'total_spend' => $kpi->total_spend,
                        'total_revenue' => $kpi->total_revenue,
                    ],
                    'targets_data' => $targets->map(fn($t) => [
                        'type' => $t->target_type,
                        'value' => $t->target_value,
                    ])->values()->toArray(),
                    'status' => 'pending',
                ]
            );

            Log::info('Bonus calculated', [
                'bonus_id' => $bonus->id,
                'user_id' => $user->id,
                'final_amount' => $finalAmount,
            ]);

            return $bonus;
        });
    }

    public function calculateLeadBonus(MarketingUserKpi $kpi, ?MarketingTarget $target): float
    {
        if (!$target) {
            // No target set, calculate based on converted leads only
            return $kpi->converted_leads * self::LEAD_BONUS_PER_LEAD;
        }

        $targetValue = $target->target_value;
        $actualValue = $kpi->leads_count;

        if ($actualValue < $targetValue) {
            // Below target - reduced bonus
            $completionRate = $actualValue / $targetValue;
            return ($kpi->converted_leads * self::LEAD_BONUS_PER_LEAD) * $completionRate;
        }

        // Met or exceeded target
        $extraLeads = max(0, $actualValue - $targetValue);
        $baseBonus = $kpi->converted_leads * self::LEAD_BONUS_PER_LEAD;
        $extraBonus = $extraLeads * (self::LEAD_BONUS_PER_LEAD * 0.5); // 50% bonus for extra leads

        return $baseBonus + $extraBonus;
    }

    public function calculateCplBonus(MarketingUserKpi $kpi, ?MarketingTarget $target): float
    {
        if (!$target || $kpi->cpl_actual <= 0) {
            return 0;
        }

        $targetCpl = $target->target_value;
        $actualCpl = $kpi->cpl_actual;

        if ($actualCpl >= $targetCpl) {
            // CPL is higher than target - no bonus
            return 0;
        }

        // CPL is lower than target - calculate savings
        $savedPerLead = $targetCpl - $actualCpl;
        $totalSaved = $savedPerLead * $kpi->leads_count;

        return $totalSaved * self::CPL_BONUS_PERCENT;
    }

    public function calculateRoasBonus(MarketingUserKpi $kpi, ?MarketingTarget $target): float
    {
        if (!$target || $kpi->total_spend <= 0) {
            return 0;
        }

        $targetRoas = $target->target_value;
        $actualRoas = $kpi->roas_actual;

        if ($actualRoas <= $targetRoas) {
            // ROAS is at or below target - no bonus
            return 0;
        }

        // Calculate extra revenue from exceeding ROAS target
        $expectedRevenue = $kpi->total_spend * $targetRoas;
        $actualRevenue = $kpi->total_revenue;
        $extraRevenue = $actualRevenue - $expectedRevenue;

        return max(0, $extraRevenue * self::ROAS_BONUS_PERCENT);
    }

    public function calculateAcceleratorBonus(MarketingUserKpi $kpi, Collection $targets): float
    {
        $leadsTarget = $targets->get('leads');
        $revenueTarget = $targets->get('revenue');

        if (!$leadsTarget && !$revenueTarget) {
            return 0;
        }

        $completionRates = [];

        if ($leadsTarget && $leadsTarget->target_value > 0) {
            $completionRates[] = $kpi->leads_count / $leadsTarget->target_value;
        }

        if ($revenueTarget && $revenueTarget->target_value > 0) {
            $completionRates[] = $kpi->total_revenue / $revenueTarget->target_value;
        }

        if (empty($completionRates)) {
            return 0;
        }

        $avgCompletion = array_sum($completionRates) / count($completionRates);

        if ($avgCompletion < self::ACCELERATOR_THRESHOLD) {
            return 0;
        }

        // Calculate accelerator bonus as percentage of base bonus
        $baseBonus = $this->calculateLeadBonus($kpi, $leadsTarget) +
                     $this->calculateCplBonus($kpi, $targets->get('cpl')) +
                     $this->calculateRoasBonus($kpi, $targets->get('roas'));

        return $baseBonus * (self::ACCELERATOR_MULTIPLIER - 1);
    }

    public function calculateAllBonuses(Business $business, Carbon $month): Collection
    {
        $users = $business->users()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'marketing');
            })
            ->get();

        $bonuses = collect();

        foreach ($users as $user) {
            $bonus = $this->calculateMonthlyBonus($business, $user, $month);
            if ($bonus) {
                $bonuses->push($bonus);
            }
        }

        return $bonuses;
    }

    public function approveBonus(MarketingBonus $bonus, ?string $approvedBy = null): void
    {
        $bonus->approve($approvedBy);
    }

    public function markAsPaid(MarketingBonus $bonus, ?string $notes = null): void
    {
        $bonus->markAsPaid($notes);
    }

    public function getBonusSummary(Business $business, Carbon $month): array
    {
        $periodStart = $month->copy()->startOfMonth();

        $bonuses = MarketingBonus::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->with('user')
            ->get();

        return [
            'period' => $periodStart->format('Y-m'),
            'total_bonuses' => $bonuses->sum('final_amount'),
            'total_penalties' => $bonuses->sum('penalty_deduction'),
            'pending_count' => $bonuses->where('status', 'pending')->count(),
            'approved_count' => $bonuses->where('status', 'approved')->count(),
            'paid_count' => $bonuses->where('status', 'paid')->count(),
            'by_user' => $bonuses->map(fn($b) => [
                'user_id' => $b->user_id,
                'user_name' => $b->user->name ?? 'Unknown',
                'base_amount' => $b->base_amount,
                'penalty_deduction' => $b->penalty_deduction,
                'final_amount' => $b->final_amount,
                'status' => $b->status,
            ])->values()->toArray(),
        ];
    }

    public function createPenalty(
        Business $business,
        User $user,
        string $type,
        string $reason,
        float $amount,
        ?string $description = null,
        ?MarketingBonus $bonus = null
    ): MarketingPenalty {
        return MarketingPenalty::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'bonus_id' => $bonus?->id,
            'date' => now(),
            'type' => $type,
            'reason' => $reason,
            'description' => $description,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }

    public function getPenaltySummary(Business $business, User $user, Carbon $month): array
    {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        $penalties = MarketingPenalty::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get();

        return [
            'total' => $penalties->sum('amount'),
            'pending' => $penalties->where('status', 'pending')->sum('amount'),
            'applied' => $penalties->where('status', 'applied')->sum('amount'),
            'disputed' => $penalties->where('status', 'disputed')->sum('amount'),
            'waived' => $penalties->where('status', 'waived')->sum('amount'),
            'by_type' => $penalties->groupBy('type')->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ])->toArray(),
        ];
    }
}
