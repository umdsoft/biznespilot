<?php

namespace App\Services\BusinessSystematization;

use App\Models\EmployeeMotivation;
use App\Models\MotivationCalculation;
use App\Models\MotivationScheme;
use App\Models\SalesTarget;
use App\Models\KeyTaskMap;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Motivation Calculator Service
 * Implements Denis Shenukov's motivation methodology:
 * - Two-parameter: Fix + Bonus
 * - Three-parameter: Fix + Soft Salary + Bonus
 * - KPI-based calculations: (Fact - Base) / (Plan - Base)
 * - Progressive and regressive scales
 */
class MotivationCalculatorService
{
    /**
     * Calculate motivation for an employee for a given period
     */
    public function calculateForEmployee(
        EmployeeMotivation $employeeMotivation,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $context = []
    ): MotivationCalculation {
        $scheme = $employeeMotivation->motivationScheme;

        // Create or get existing calculation record
        $calculation = MotivationCalculation::firstOrNew([
            'business_id' => $employeeMotivation->business_id,
            'user_id' => $employeeMotivation->user_id,
            'employee_motivation_id' => $employeeMotivation->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $calculation->period_type = $scheme->bonus_period;

        // Calculate each component
        $details = [];

        // 1. Fixed Salary
        $fixedSalary = $employeeMotivation->effective_fixed_salary;
        $calculation->fixed_salary = $fixedSalary;
        $details['fixed_salary'] = [
            'amount' => $fixedSalary,
            'description' => 'Qattiq oklad',
        ];

        // 2. Soft Salary (if three-parameter scheme)
        if ($scheme->scheme_type === 'three_parameter') {
            $softSalaryResult = $this->calculateSoftSalary($scheme, $context);
            $calculation->soft_salary_earned = $softSalaryResult['earned'];
            $calculation->soft_salary_max = $softSalaryResult['max'];
            $calculation->soft_salary_completion = $softSalaryResult['completion_percent'];
            $details['soft_salary'] = $softSalaryResult;
        }

        // 3. Bonus calculation
        $bonusResult = $this->calculateBonus($scheme, $context);
        $calculation->bonus_earned = $bonusResult['earned'];
        $calculation->bonus_max = $bonusResult['max'];
        $calculation->kpi_score = $bonusResult['kpi_score'] ?? 0;
        $details['bonus'] = $bonusResult;

        // 4. Penalties (if any)
        $penalties = $this->calculatePenalties($scheme, $context);
        $calculation->penalties = $penalties['total'];
        $details['penalties'] = $penalties;

        // Calculate total
        $calculation->total_earned = $calculation->fixed_salary
            + $calculation->soft_salary_earned
            + $calculation->bonus_earned
            - $calculation->penalties;

        $calculation->calculation_details = $details;
        $calculation->status = 'calculated';
        $calculation->save();

        return $calculation;
    }

    /**
     * Calculate Soft Salary based on function requirements
     */
    protected function calculateSoftSalary(MotivationScheme $scheme, array $context): array
    {
        $softSalaryComponents = $scheme->components()
            ->where('component_type', 'soft_salary')
            ->get();

        $totalMax = 0;
        $totalEarned = 0;
        $details = [];

        $completedRequirements = $context['completed_requirements'] ?? [];

        foreach ($softSalaryComponents as $component) {
            $totalMax += $component->base_amount;
            $result = $component->calculateSoftSalaryCompletion($completedRequirements);
            $totalEarned += $result['earned'];

            $details[] = [
                'component' => $component->name,
                'max' => $component->base_amount,
                'earned' => $result['earned'],
                'percent' => $result['percent'],
            ];
        }

        $completionPercent = $totalMax > 0 ? ($totalEarned / $totalMax) * 100 : 0;

        return [
            'earned' => round($totalEarned, 2),
            'max' => round($totalMax, 2),
            'completion_percent' => round($completionPercent, 2),
            'components' => $details,
        ];
    }

    /**
     * Calculate Bonus based on KPI scores
     * KPI = (Fact - Base) / (Plan - Base)
     */
    protected function calculateBonus(MotivationScheme $scheme, array $context): array
    {
        $bonusComponents = $scheme->components()
            ->where('component_type', 'bonus')
            ->get();

        $totalMax = 0;
        $totalEarned = 0;
        $details = [];
        $avgKpiScore = 0;
        $kpiCount = 0;

        foreach ($bonusComponents as $component) {
            $totalMax += $component->max_amount ?? $component->base_amount;

            // Get KPI linkage settings
            $kpiLinkage = $component->kpi_linkage ?? [];
            $metric = $kpiLinkage['metric'] ?? 'sales_plan';

            // Get values from context
            $plan = $context['plan_' . $metric] ?? $kpiLinkage['plan'] ?? 0;
            $fact = $context['fact_' . $metric] ?? 0;
            $base = $context['base_' . $metric] ?? $kpiLinkage['base'] ?? 0;

            // Calculate KPI score
            $kpiScore = $this->calculateKpiScore($plan, $fact, $base);
            $avgKpiScore += $kpiScore;
            $kpiCount++;

            // Calculate amount based on component settings
            $earnedAmount = $component->calculateAmount([
                'kpi_score' => $kpiScore,
                'revenue' => $context['revenue'] ?? 0,
                'profit' => $context['profit'] ?? 0,
                'plan_completion' => $kpiScore * 100,
            ]);

            $totalEarned += $earnedAmount;

            $details[] = [
                'component' => $component->name,
                'max' => $component->max_amount ?? $component->base_amount,
                'earned' => $earnedAmount,
                'kpi_score' => $kpiScore,
                'plan' => $plan,
                'fact' => $fact,
                'base' => $base,
            ];
        }

        return [
            'earned' => round($totalEarned, 2),
            'max' => round($totalMax, 2),
            'kpi_score' => $kpiCount > 0 ? round($avgKpiScore / $kpiCount, 4) : 0,
            'components' => $details,
        ];
    }

    /**
     * Calculate KPI Score using formula from the book:
     * KPI = (Fact - Base) / (Plan - Base)
     */
    public function calculateKpiScore(float $plan, float $fact, float $base): float
    {
        $denominator = $plan - $base;

        if ($denominator == 0) {
            return 0;
        }

        $score = ($fact - $base) / $denominator;

        // Score can be negative (bad), 0-1 (not great), or >1 (excellent)
        return round($score, 4);
    }

    /**
     * Calculate Penalties
     */
    protected function calculatePenalties(MotivationScheme $scheme, array $context): array
    {
        $penaltyComponents = $scheme->components()
            ->where('component_type', 'penalty')
            ->get();

        $totalPenalties = 0;
        $details = [];

        foreach ($penaltyComponents as $component) {
            // Check if penalty conditions are met
            $kpiLinkage = $component->kpi_linkage ?? [];
            $metric = $kpiLinkage['metric'] ?? null;

            if ($metric) {
                $value = $context[$metric] ?? 0;
                $threshold = $kpiLinkage['threshold'] ?? 100;

                if ($value < $threshold) {
                    // Apply penalty
                    $penaltyPercent = $component->percentage_value ?? 0;
                    $bonusEarned = $context['bonus_earned'] ?? 0;
                    $penaltyAmount = $bonusEarned * ($penaltyPercent / 100);

                    $totalPenalties += $penaltyAmount;

                    $details[] = [
                        'component' => $component->name,
                        'reason' => "{$metric} = {$value}% (talab: {$threshold}%)",
                        'penalty_percent' => $penaltyPercent,
                        'amount' => $penaltyAmount,
                    ];
                }
            }
        }

        return [
            'total' => round($totalPenalties, 2),
            'items' => $details,
        ];
    }

    /**
     * Calculate motivation for sales team (ROP example from book)
     */
    public function calculateSalesTeamMotivation(
        string $businessId,
        SalesTarget $salesTarget,
        array $receivablesData = []
    ): array {
        $results = [];

        // Get all individual targets linked to this department target
        $individualTargets = SalesTarget::where('business_id', $businessId)
            ->where('department_id', $salesTarget->department_id)
            ->where('target_type', 'individual')
            ->where('period_start', $salesTarget->period_start)
            ->get();

        foreach ($individualTargets as $target) {
            // Get employee motivation settings
            $employeeMotivation = EmployeeMotivation::where('user_id', $target->user_id)
                ->active()
                ->currentlyValid()
                ->first();

            if (!$employeeMotivation) continue;

            // Calculate with sales and receivables context
            $context = [
                'plan_sales_plan' => $target->plan_revenue,
                'fact_sales_plan' => $target->fact_revenue,
                'base_sales_plan' => $target->base_revenue,
                'revenue' => $target->fact_revenue,
                // Receivables data
                'receivables_collection_rate' => $receivablesData[$target->user_id]['collection_rate'] ?? 100,
            ];

            $calculation = $this->calculateForEmployee(
                $employeeMotivation,
                $target->period_start,
                $target->period_end,
                $context
            );

            $results[$target->user_id] = $calculation;
        }

        return $results;
    }

    /**
     * Calculate Key Task Map bonus
     */
    public function calculateKeyTaskMapBonus(KeyTaskMap $taskMap): array
    {
        $totalWeight = $taskMap->tasks()->sum('weight');
        $completedWeight = $taskMap->completedTasks()->sum('weight');

        $completionPercent = $totalWeight > 0
            ? ($completedWeight / $totalWeight) * 100
            : 0;

        // Check minimum threshold
        if ($completionPercent < $taskMap->min_completion_percent) {
            return [
                'earned' => 0,
                'max' => $taskMap->total_bonus_fund,
                'completion_percent' => $completionPercent,
                'status' => 'below_threshold',
                'message' => "Minimal chegara ({$taskMap->min_completion_percent}%) ga yetilmadi",
            ];
        }

        // Calculate proportional bonus
        $earnedBonus = $taskMap->total_bonus_fund * ($completionPercent / 100);

        return [
            'earned' => round($earnedBonus, 2),
            'max' => $taskMap->total_bonus_fund,
            'completion_percent' => round($completionPercent, 2),
            'status' => $completionPercent >= $taskMap->full_bonus_percent ? 'full' : 'partial',
            'message' => null,
        ];
    }

    /**
     * Generate scale table (progressive/regressive) for bonus calculation
     */
    public static function generateScaleTable(
        float $basePercent = 80,
        float $maxPercent = 120,
        float $step = 10,
        bool $progressive = true
    ): array {
        $table = [];
        $coefficient = $progressive ? 0.5 : 1.5;

        for ($percent = $basePercent; $percent <= $maxPercent; $percent += $step) {
            $table[] = [
                'min' => $percent,
                'max' => $percent + $step,
                'coefficient' => round($coefficient, 2),
            ];

            $coefficient = $progressive
                ? min(1.5, $coefficient + 0.2)
                : max(0.5, $coefficient - 0.2);
        }

        return $table;
    }
}
