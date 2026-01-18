<?php

namespace App\Services;

use App\Models\AnnualStrategy;
use App\Models\BudgetAllocation;
use App\Models\Business;
use App\Models\MonthlyPlan;
use App\Models\QuarterlyPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BudgetAllocationService
{
    /**
     * Default budget categories with allocation percentages
     */
    private const DEFAULT_ALLOCATION = [
        'marketing' => ['percent' => 30, 'description' => 'Umumiy marketing faoliyati'],
        'advertising' => ['percent' => 40, 'description' => 'Reklama xarajatlari'],
        'content' => ['percent' => 15, 'description' => 'Kontent yaratish'],
        'tools' => ['percent' => 10, 'description' => 'Marketing asboblari'],
        'other' => ['percent' => 5, 'description' => 'Boshqa xarajatlar'],
    ];

    /**
     * Create budget allocations from annual strategy
     */
    public function createAllocationsFromStrategy(AnnualStrategy $annual): Collection
    {
        $allocations = collect();

        // Create annual allocations
        $annualAllocations = $this->createAnnualAllocations($annual);
        $allocations = $allocations->merge($annualAllocations);

        // Create quarterly allocations
        foreach ($annual->quarterlyPlans as $quarterly) {
            $quarterlyAllocations = $this->createQuarterlyAllocations($quarterly);
            $allocations = $allocations->merge($quarterlyAllocations);
        }

        return $allocations;
    }

    /**
     * Create annual budget allocations
     */
    public function createAnnualAllocations(AnnualStrategy $annual): Collection
    {
        $allocations = collect();
        $totalBudget = $annual->annual_budget ?? 0;

        // Get custom allocation from strategy or use default
        $allocationPercents = $annual->channel_budget_allocation ?? self::DEFAULT_ALLOCATION;

        foreach (self::DEFAULT_ALLOCATION as $category => $info) {
            $percent = $allocationPercents[$category]['percent'] ?? $info['percent'];
            $amount = $totalBudget * ($percent / 100);

            $allocation = BudgetAllocation::create([
                'uuid' => Str::uuid(),
                'business_id' => $annual->business_id,
                'period_type' => 'annual',
                'annual_strategy_id' => $annual->id,
                'year' => $annual->year,
                'category' => $category,
                'planned_budget' => $amount,
                'allocation_percent' => $percent,
                'description' => $info['description'],
                'status' => 'planned',
                'overspend_threshold_percent' => 100,
            ]);

            $allocations->push($allocation);
        }

        // Create channel-specific allocations if advertising budget exists
        $adBudget = $allocations->where('category', 'advertising')->first()?->planned_budget ?? 0;
        if ($adBudget > 0 && $annual->primary_channels) {
            $channelAllocations = $this->createChannelAllocations(
                $annual->business_id,
                'annual',
                $annual->id,
                null,
                $annual->year,
                null,
                null,
                $adBudget,
                $annual->primary_channels
            );
            $allocations = $allocations->merge($channelAllocations);
        }

        return $allocations;
    }

    /**
     * Create quarterly budget allocations
     */
    public function createQuarterlyAllocations(QuarterlyPlan $quarterly): Collection
    {
        $allocations = collect();
        $totalBudget = $quarterly->budget ?? 0;

        foreach (self::DEFAULT_ALLOCATION as $category => $info) {
            $percent = $info['percent'];
            $amount = $totalBudget * ($percent / 100);

            $allocation = BudgetAllocation::create([
                'uuid' => Str::uuid(),
                'business_id' => $quarterly->business_id,
                'period_type' => 'quarterly',
                'annual_strategy_id' => $quarterly->annual_strategy_id,
                'quarterly_plan_id' => $quarterly->id,
                'year' => $quarterly->year,
                'quarter' => $quarterly->quarter,
                'category' => $category,
                'planned_budget' => $amount,
                'allocation_percent' => $percent,
                'description' => $info['description'],
                'status' => 'planned',
                'overspend_threshold_percent' => 100,
            ]);

            $allocations->push($allocation);
        }

        // Create channel-specific allocations
        $adBudget = $allocations->where('category', 'advertising')->first()?->planned_budget ?? 0;
        if ($adBudget > 0 && $quarterly->channel_priorities) {
            $channelAllocations = $this->createChannelAllocations(
                $quarterly->business_id,
                'quarterly',
                $quarterly->annual_strategy_id,
                $quarterly->id,
                $quarterly->year,
                $quarterly->quarter,
                null,
                $adBudget,
                $quarterly->channel_priorities
            );
            $allocations = $allocations->merge($channelAllocations);
        }

        return $allocations;
    }

    /**
     * Create monthly budget allocations
     */
    public function createMonthlyAllocations(MonthlyPlan $monthly): Collection
    {
        $allocations = collect();
        $totalBudget = $monthly->budget ?? 0;

        foreach (self::DEFAULT_ALLOCATION as $category => $info) {
            $percent = $info['percent'];
            $amount = $totalBudget * ($percent / 100);

            $allocation = BudgetAllocation::create([
                'uuid' => Str::uuid(),
                'business_id' => $monthly->business_id,
                'period_type' => 'monthly',
                'quarterly_plan_id' => $monthly->quarterly_plan_id,
                'monthly_plan_id' => $monthly->id,
                'year' => $monthly->year,
                'month' => $monthly->month,
                'category' => $category,
                'planned_budget' => $amount,
                'allocation_percent' => $percent,
                'description' => $info['description'],
                'status' => 'planned',
                'overspend_threshold_percent' => 100,
            ]);

            $allocations->push($allocation);
        }

        return $allocations;
    }

    /**
     * Create channel-specific allocations
     */
    private function createChannelAllocations(
        int $businessId,
        string $periodType,
        ?int $annualId,
        ?int $quarterlyId,
        int $year,
        ?int $quarter,
        ?int $month,
        float $totalAdBudget,
        array $channels
    ): Collection {
        $allocations = collect();

        // Default channel distribution
        $channelPercents = [
            'instagram' => 40,
            'telegram' => 30,
            'facebook' => 20,
            'google' => 10,
        ];

        // Normalize to available channels
        $availableChannels = array_intersect_key($channelPercents, array_flip($channels));
        $total = array_sum($availableChannels);

        foreach ($availableChannels as $channel => $percent) {
            $normalizedPercent = ($percent / $total) * 100;
            $amount = $totalAdBudget * ($normalizedPercent / 100);

            $allocation = BudgetAllocation::create([
                'uuid' => Str::uuid(),
                'business_id' => $businessId,
                'period_type' => $periodType,
                'annual_strategy_id' => $annualId,
                'quarterly_plan_id' => $quarterlyId,
                'year' => $year,
                'quarter' => $quarter,
                'month' => $month,
                'category' => 'advertising',
                'subcategory' => 'channel',
                'channel' => $channel,
                'planned_budget' => $amount,
                'allocation_percent' => $normalizedPercent,
                'description' => ucfirst($channel).' reklama byudjeti',
                'status' => 'planned',
                'overspend_threshold_percent' => 100,
            ]);

            $allocations->push($allocation);
        }

        return $allocations;
    }

    /**
     * Record spending for a budget allocation
     */
    public function recordSpending(BudgetAllocation $allocation, float $amount, ?string $description = null): BudgetAllocation
    {
        $allocation->addSpending($amount, $description);

        return $allocation->fresh();
    }

    /**
     * Record results (leads, revenue) for an allocation
     */
    public function recordResults(BudgetAllocation $allocation, int $leads = 0, float $revenue = 0): BudgetAllocation
    {
        $allocation->addResult($leads, $revenue);

        return $allocation->fresh();
    }

    /**
     * Get budget summary for dashboard
     */
    public function getBudgetSummary(Business $business, string $periodType = 'monthly', ?int $year = null, ?int $period = null): array
    {
        $query = BudgetAllocation::where('business_id', $business->id)
            ->where('period_type', $periodType);

        if ($year) {
            $query->where('year', $year);
        }

        if ($period) {
            $periodColumn = match ($periodType) {
                'quarterly' => 'quarter',
                'monthly' => 'month',
                'weekly' => 'week',
                default => null,
            };

            if ($periodColumn) {
                $query->where($periodColumn, $period);
            }
        }

        $allocations = $query->get();

        $totalPlanned = $allocations->sum('planned_budget');
        $totalSpent = $allocations->sum('spent_amount');
        $totalRemaining = $allocations->sum('remaining_amount');

        return [
            'total_planned' => $totalPlanned,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalRemaining,
            'spent_percent' => $totalPlanned > 0 ? round(($totalSpent / $totalPlanned) * 100, 1) : 0,
            'by_category' => $allocations->where('subcategory', null)->groupBy('category')->map(function ($group) {
                return [
                    'planned' => $group->sum('planned_budget'),
                    'spent' => $group->sum('spent_amount'),
                    'remaining' => $group->sum('remaining_amount'),
                ];
            }),
            'by_channel' => $allocations->whereNotNull('channel')->groupBy('channel')->map(function ($group) {
                return [
                    'planned' => $group->sum('planned_budget'),
                    'spent' => $group->sum('spent_amount'),
                    'leads' => $group->sum('actual_leads'),
                    'revenue' => $group->sum('actual_revenue'),
                    'roi' => $group->avg('actual_roi'),
                ];
            }),
            'overspent_count' => $allocations->where('overspend_alert', true)->count(),
            'total_leads' => $allocations->sum('actual_leads'),
            'total_revenue' => $allocations->sum('actual_revenue'),
            'avg_roi' => round($allocations->whereNotNull('actual_roi')->avg('actual_roi') ?? 0, 1),
        ];
    }

    /**
     * Get allocations that are overspent or at risk
     */
    public function getOverspentAllocations(Business $business): Collection
    {
        return BudgetAllocation::where('business_id', $business->id)
            ->where('overspend_alert', true)
            ->orderByRaw('spent_amount / planned_budget DESC')
            ->get();
    }

    /**
     * Get channel performance comparison
     */
    public function getChannelPerformance(Business $business, string $periodType = 'monthly', ?int $year = null): array
    {
        $query = BudgetAllocation::where('business_id', $business->id)
            ->where('period_type', $periodType)
            ->whereNotNull('channel');

        if ($year) {
            $query->where('year', $year);
        }

        $allocations = $query->get();

        return $allocations->groupBy('channel')->map(function ($group, $channel) {
            $totalSpent = $group->sum('spent_amount');
            $totalLeads = $group->sum('actual_leads');
            $totalRevenue = $group->sum('actual_revenue');

            return [
                'channel' => $channel,
                'total_spent' => $totalSpent,
                'total_leads' => $totalLeads,
                'total_revenue' => $totalRevenue,
                'cpl' => $totalLeads > 0 ? round($totalSpent / $totalLeads, 0) : 0,
                'roi' => $totalSpent > 0 ? round((($totalRevenue - $totalSpent) / $totalSpent) * 100, 1) : 0,
                'efficiency_score' => $this->calculateEfficiencyScore($totalSpent, $totalLeads, $totalRevenue),
            ];
        })->sortByDesc('efficiency_score')->values()->toArray();
    }

    /**
     * Calculate efficiency score for a channel (0-100)
     */
    private function calculateEfficiencyScore(float $spent, int $leads, float $revenue): int
    {
        if ($spent <= 0) {
            return 0;
        }

        $roi = (($revenue - $spent) / $spent) * 100;
        $cpl = $leads > 0 ? $spent / $leads : PHP_INT_MAX;

        // Normalize ROI (0-50 points, max at 200% ROI)
        $roiScore = min(50, max(0, ($roi / 200) * 50));

        // Normalize CPL (0-50 points, better if lower)
        // Assume good CPL is 50000 so'm, bad is 500000 so'm
        $cplScore = max(0, min(50, 50 - (($cpl - 50000) / 450000 * 50)));

        return (int) round($roiScore + $cplScore);
    }
}
