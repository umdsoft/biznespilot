<?php

namespace App\Services;

use App\Models\AIDiagnostic;
use App\Models\AnnualStrategy;
use App\Models\Business;
use App\Models\MonthlyPlan;
use App\Models\QuarterlyPlan;
use App\Models\StrategyTemplate;
use App\Models\WeeklyPlan;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StrategyBuilderService
{
    public function __construct(
        private AIStrategyService $aiService,
        private KPITargetService $kpiService,
        private BudgetAllocationService $budgetService,
        private ContentStrategyService $contentService
    ) {}

    /**
     * Create annual strategy from diagnostic results
     */
    public function createAnnualStrategy(Business $business, int $year, ?AIDiagnostic $diagnostic = null): AnnualStrategy
    {
        // Get template
        $template = StrategyTemplate::active()
            ->forType('annual')
            ->forIndustry($business->industry)
            ->default()
            ->first() ?? StrategyTemplate::active()->forType('annual')->default()->first();

        // Get AI recommendations if diagnostic exists
        $aiData = [];
        if ($diagnostic && $diagnostic->isCompleted()) {
            $aiData = $this->aiService->generateAnnualStrategy($business, $diagnostic, $year);
        }

        $strategy = AnnualStrategy::create([
            'uuid' => Str::uuid(),
            'business_id' => $business->id,
            'diagnostic_id' => $diagnostic?->id,
            'year' => $year,
            'title' => "{$year}-yil strategiyasi",
            'status' => 'draft',
            'vision_statement' => $aiData['vision'] ?? null,
            'executive_summary' => $aiData['summary'] ?? null,
            'strategic_goals' => $aiData['goals'] ?? $template?->getGoalsForPlan(),
            'focus_areas' => $aiData['focus_areas'] ?? [],
            'growth_drivers' => $aiData['growth_drivers'] ?? [],
            'risk_factors' => $aiData['risks'] ?? [],
            'revenue_target' => $aiData['revenue_target'] ?? $this->calculateRevenueTarget($business),
            'annual_budget' => $aiData['budget'] ?? $this->calculateAnnualBudget($business),
            'primary_channels' => $aiData['channels'] ?? $this->getPrimaryChannels($business),
            'ai_recommendations' => $aiData['recommendations'] ?? [],
            'ai_summary' => $aiData['ai_summary'] ?? null,
            'confidence_score' => $aiData['confidence'] ?? null,
        ]);

        // Update template usage
        $template?->incrementUsage();

        return $strategy;
    }

    /**
     * Create quarterly plan from annual strategy
     */
    public function createQuarterlyPlan(AnnualStrategy $annualStrategy, int $quarter): QuarterlyPlan
    {
        $template = StrategyTemplate::active()->forType('quarterly')->default()->first();

        $months = $this->getQuarterMonths($quarter);
        $quarterBudget = $annualStrategy->getQuarterBudget($quarter);

        // Get AI suggestions
        $aiData = $this->aiService->generateQuarterlyPlan(
            $annualStrategy->business,
            $annualStrategy,
            $quarter
        );

        $plan = QuarterlyPlan::create([
            'uuid' => Str::uuid(),
            'business_id' => $annualStrategy->business_id,
            'annual_strategy_id' => $annualStrategy->id,
            'year' => $annualStrategy->year,
            'quarter' => $quarter,
            'title' => "Q{$quarter} {$annualStrategy->year}",
            'status' => 'draft',
            'theme' => $aiData['theme'] ?? $this->getQuarterTheme($quarter),
            'executive_summary' => $aiData['summary'] ?? null,
            'quarterly_objectives' => $aiData['objectives'] ?? [],
            'goals' => $aiData['goals'] ?? $this->breakdownGoalsForQuarter($annualStrategy->strategic_goals, $quarter),
            'revenue_target' => ($annualStrategy->revenue_target ?? 0) / 4,
            'budget' => $quarterBudget,
            'lead_target' => ceil(($annualStrategy->lead_target ?? 0) / 4),
            'customer_target' => ceil(($annualStrategy->customer_target ?? 0) / 4),
            'initiatives' => $aiData['initiatives'] ?? [],
            'campaigns' => $aiData['campaigns'] ?? [],
            'channel_priorities' => $annualStrategy->primary_channels,
            'channel_budget' => $this->calculateChannelBudget($quarterBudget, $annualStrategy->channel_budget_allocation),
            'ai_recommendations' => $aiData['recommendations'] ?? [],
            'ai_summary' => $aiData['ai_summary'] ?? null,
            'confidence_score' => $aiData['confidence'] ?? null,
        ]);

        $template?->incrementUsage();

        return $plan;
    }

    /**
     * Create monthly plan from quarterly plan
     */
    public function createMonthlyPlan(QuarterlyPlan $quarterlyPlan, int $month): MonthlyPlan
    {
        $template = StrategyTemplate::active()->forType('monthly')->default()->first();

        $monthBudget = ($quarterlyPlan->budget ?? 0) / 3;

        // Get AI suggestions
        $aiData = $this->aiService->generateMonthlyPlan(
            $quarterlyPlan->business,
            $quarterlyPlan,
            $month
        );

        $plan = MonthlyPlan::create([
            'uuid' => Str::uuid(),
            'business_id' => $quarterlyPlan->business_id,
            'quarterly_plan_id' => $quarterlyPlan->id,
            'year' => $quarterlyPlan->year,
            'month' => $month,
            'title' => MonthlyPlan::MONTHS[$month].' '.$quarterlyPlan->year,
            'status' => 'draft',
            'theme' => $aiData['theme'] ?? null,
            'executive_summary' => $aiData['summary'] ?? null,
            'monthly_objectives' => $aiData['objectives'] ?? [],
            'goals' => $aiData['goals'] ?? $this->breakdownGoalsForMonth($quarterlyPlan->goals),
            'revenue_target' => ($quarterlyPlan->revenue_target ?? 0) / 3,
            'budget' => $monthBudget,
            'lead_target' => ceil(($quarterlyPlan->lead_target ?? 0) / 3),
            'customer_target' => ceil(($quarterlyPlan->customer_target ?? 0) / 3),
            'content_pieces_target' => $aiData['content_target'] ?? 20,
            'posts_target' => $aiData['posts_target'] ?? 30,
            'week_1_plan' => $aiData['week_1'] ?? [],
            'week_2_plan' => $aiData['week_2'] ?? [],
            'week_3_plan' => $aiData['week_3'] ?? [],
            'week_4_plan' => $aiData['week_4'] ?? [],
            'content_themes' => $aiData['content_themes'] ?? [],
            'content_types' => $aiData['content_types'] ?? ['post', 'story', 'reel'],
            'campaigns' => $quarterlyPlan->campaigns,
            'channel_focus' => $quarterlyPlan->channel_priorities,
            'channel_budget' => $this->calculateChannelBudget($monthBudget, $quarterlyPlan->channel_budget),
            'ai_recommendations' => $aiData['recommendations'] ?? [],
            'ai_content_suggestions' => $aiData['content_suggestions'] ?? [],
            'ai_summary' => $aiData['ai_summary'] ?? null,
            'confidence_score' => $aiData['confidence'] ?? null,
        ]);

        $template?->incrementUsage();

        return $plan;
    }

    /**
     * Create weekly plan from monthly plan
     */
    public function createWeeklyPlan(MonthlyPlan $monthlyPlan, int $weekOfMonth): WeeklyPlan
    {
        $template = StrategyTemplate::active()->forType('weekly')->default()->first();

        // Calculate week dates
        $monthStart = Carbon::create($monthlyPlan->year, $monthlyPlan->month, 1);
        $weekStart = $monthStart->copy()->addWeeks($weekOfMonth - 1)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $weekBudget = ($monthlyPlan->budget ?? 0) / 4;

        // Get week plan from monthly
        $weekPlanData = $monthlyPlan->getWeekPlan($weekOfMonth) ?? [];

        // Get AI suggestions
        $aiData = $this->aiService->generateWeeklyPlan(
            $monthlyPlan->business,
            $monthlyPlan,
            $weekOfMonth
        );

        $plan = WeeklyPlan::create([
            'uuid' => Str::uuid(),
            'business_id' => $monthlyPlan->business_id,
            'monthly_plan_id' => $monthlyPlan->id,
            'year' => $monthlyPlan->year,
            'week_number' => $weekStart->weekOfYear,
            'month' => $monthlyPlan->month,
            'week_of_month' => $weekOfMonth,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'title' => "Hafta {$weekOfMonth}, ".MonthlyPlan::MONTHS[$monthlyPlan->month],
            'status' => 'draft',
            'weekly_focus' => $weekPlanData['focus'] ?? $aiData['focus'] ?? null,
            'priorities' => $aiData['priorities'] ?? [],
            'goals' => $aiData['goals'] ?? $this->breakdownGoalsForWeek($monthlyPlan->goals),
            'monday' => $aiData['monday'] ?? [],
            'tuesday' => $aiData['tuesday'] ?? [],
            'wednesday' => $aiData['wednesday'] ?? [],
            'thursday' => $aiData['thursday'] ?? [],
            'friday' => $aiData['friday'] ?? [],
            'saturday' => $aiData['saturday'] ?? [],
            'sunday' => $aiData['sunday'] ?? [],
            'tasks' => $aiData['tasks'] ?? [],
            'total_tasks' => count($aiData['tasks'] ?? []),
            'posts_planned' => $aiData['posts_count'] ?? ceil(($monthlyPlan->posts_target ?? 30) / 4),
            'revenue_target' => ($monthlyPlan->revenue_target ?? 0) / 4,
            'spend_budget' => $weekBudget,
            'lead_target' => ceil(($monthlyPlan->lead_target ?? 0) / 4),
            'marketing_activities' => $aiData['marketing'] ?? [],
            'sales_activities' => $aiData['sales'] ?? [],
            'ai_suggestions' => $aiData['suggestions'] ?? [],
            'ai_content_ideas' => $aiData['content_ideas'] ?? [],
        ]);

        $template?->incrementUsage();

        return $plan;
    }

    /**
     * Generate all quarterly plans for an annual strategy
     */
    public function generateAllQuarters(AnnualStrategy $annualStrategy): array
    {
        $plans = [];
        for ($q = 1; $q <= 4; $q++) {
            $plans[$q] = $this->createQuarterlyPlan($annualStrategy, $q);
        }

        return $plans;
    }

    /**
     * Generate all monthly plans for a quarter
     */
    public function generateAllMonths(QuarterlyPlan $quarterlyPlan): array
    {
        $plans = [];
        $months = $this->getQuarterMonths($quarterlyPlan->quarter);

        foreach ($months as $month) {
            $plans[$month] = $this->createMonthlyPlan($quarterlyPlan, $month);
        }

        return $plans;
    }

    /**
     * Generate all weekly plans for a month
     */
    public function generateAllWeeks(MonthlyPlan $monthlyPlan): array
    {
        $plans = [];
        $weeksInMonth = $monthlyPlan->getWeeksInMonth();

        for ($w = 1; $w <= min($weeksInMonth, 5); $w++) {
            $plans[$w] = $this->createWeeklyPlan($monthlyPlan, $w);
        }

        return $plans;
    }

    /**
     * Build complete strategy cascade from annual to weekly
     */
    public function buildCompleteStrategy(Business $business, int $year, ?AIDiagnostic $diagnostic = null): array
    {
        $result = [
            'annual' => null,
            'quarters' => [],
            'months' => [],
            'weeks' => [],
        ];

        // Create annual strategy
        $annual = $this->createAnnualStrategy($business, $year, $diagnostic);
        $result['annual'] = $annual;

        // Create all quarters
        $result['quarters'] = $this->generateAllQuarters($annual);

        // Create all months for each quarter
        foreach ($result['quarters'] as $quarter => $quarterPlan) {
            $months = $this->generateAllMonths($quarterPlan);
            $result['months'] = array_merge($result['months'], $months);

            // Create weeks for current quarter only to save resources
            if ($quarter === ceil(now()->month / 3)) {
                foreach ($months as $month => $monthPlan) {
                    if ($month === now()->month) {
                        $result['weeks'] = $this->generateAllWeeks($monthPlan);
                    }
                }
            }
        }

        // Create KPI targets
        $this->kpiService->createKPIsFromStrategy($annual);

        // Create budget allocations
        $this->budgetService->createAllocationsFromStrategy($annual);

        return $result;
    }

    // Helper methods
    private function getQuarterMonths(int $quarter): array
    {
        $start = (($quarter - 1) * 3) + 1;

        return [$start, $start + 1, $start + 2];
    }

    private function getQuarterTheme(int $quarter): string
    {
        return match ($quarter) {
            1 => 'Yangi yil - Yangi imkoniyatlar',
            2 => 'Bahor - O\'sish fasli',
            3 => 'Yoz - Faollik davri',
            4 => 'Yil yakunlash va rejalashtirish',
        };
    }

    private function calculateRevenueTarget(Business $business): float
    {
        // Get last year's revenue and add 30% growth target
        // TODO: Integrate with actual financial data
        return 100000000; // Default 100M so'm
    }

    private function calculateAnnualBudget(Business $business): float
    {
        // Typically 10-20% of target revenue for marketing
        // TODO: Based on business maturity and industry
        return 10000000; // Default 10M so'm
    }

    private function getPrimaryChannels(Business $business): array
    {
        // Get from business marketing channels
        // TODO: Integrate with MarketingChannel model
        return ['instagram', 'telegram', 'facebook'];
    }

    private function calculateChannelBudget(float $totalBudget, ?array $allocation): array
    {
        if (! $allocation) {
            // Default equal distribution
            return [
                'instagram' => $totalBudget * 0.4,
                'telegram' => $totalBudget * 0.3,
                'facebook' => $totalBudget * 0.2,
                'other' => $totalBudget * 0.1,
            ];
        }

        $result = [];
        foreach ($allocation as $channel => $percent) {
            $result[$channel] = $totalBudget * ($percent / 100);
        }

        return $result;
    }

    private function breakdownGoalsForQuarter(array $annualGoals, int $quarter): array
    {
        return collect($annualGoals)->map(function ($goal) use ($quarter) {
            $target = $goal['target'] ?? 0;

            return [
                ...$goal,
                'target' => is_numeric($target) ? ceil($target / 4) : $target,
                'quarter' => $quarter,
            ];
        })->toArray();
    }

    private function breakdownGoalsForMonth(array $quarterlyGoals): array
    {
        return collect($quarterlyGoals)->map(function ($goal) {
            $target = $goal['target'] ?? 0;

            return [
                ...$goal,
                'target' => is_numeric($target) ? ceil($target / 3) : $target,
            ];
        })->toArray();
    }

    private function breakdownGoalsForWeek(array $monthlyGoals): array
    {
        return collect($monthlyGoals)->map(function ($goal) {
            $target = $goal['target'] ?? 0;

            return [
                ...$goal,
                'target' => is_numeric($target) ? ceil($target / 4) : $target,
            ];
        })->toArray();
    }
}
