<?php

namespace App\Services\KPI;

use App\Models\Business;
use App\Models\KpiDailyEntry;
use App\Models\KpiPlan;
use App\Models\KpiWeeklySummary;
use App\Models\KpiMonthlySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiAggregationService
{
    /**
     * Aggregate weekly data from daily entries
     */
    public function aggregateWeekly(Business $business, int $year, int $week): ?KpiWeeklySummary
    {
        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        // Get all daily entries for the week
        $entries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        // Calculate totals
        $data = [
            'business_id' => $business->id,
            'year' => $year,
            'week_number' => $week,
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'days_with_data' => $entries->where('is_complete', true)->count(),
            // Leads
            'leads_total' => $entries->sum('leads_total'),
            'leads_digital' => $entries->sum('leads_digital'),
            'leads_offline' => $entries->sum('leads_offline'),
            'leads_referral' => $entries->sum('leads_referral'),
            'leads_organic' => $entries->sum('leads_organic'),
            // Spend
            'spend_total' => $entries->sum('spend_total'),
            'spend_digital' => $entries->sum('spend_digital'),
            'spend_offline' => $entries->sum('spend_offline'),
            // Sales
            'sales_total' => $entries->sum('sales_total'),
            'sales_new' => $entries->sum('sales_new'),
            'sales_repeat' => $entries->sum('sales_repeat'),
            // Revenue
            'revenue_total' => $entries->sum('revenue_total'),
            'revenue_new' => $entries->sum('revenue_new'),
            'revenue_repeat' => $entries->sum('revenue_repeat'),
        ];

        // Get plan data for this week
        $plan = $this->getWeeklyPlan($business, $startOfWeek);
        if ($plan) {
            $data['plan_leads'] = $plan['leads'];
            $data['plan_sales'] = $plan['sales'];
            $data['plan_revenue'] = $plan['revenue'];
            $data['plan_spend'] = $plan['spend'];
        }

        // Create or update weekly summary
        $summary = KpiWeeklySummary::updateOrCreate(
            [
                'business_id' => $business->id,
                'year' => $year,
                'week_number' => $week,
            ],
            $data
        );

        // Calculate metrics
        $summary->calculateMetrics();
        $summary->save();

        return $summary;
    }

    /**
     * Get weekly plan from monthly plan
     */
    protected function getWeeklyPlan(Business $business, Carbon $weekStart): ?array
    {
        $plan = KpiPlan::where('business_id', $business->id)
            ->where('year', $weekStart->year)
            ->where('month', $weekStart->month)
            ->where('status', 'active')
            ->first();

        if (!$plan) {
            return null;
        }

        // Get weekly breakdown if available
        if ($plan->weekly_breakdown) {
            return [
                'leads' => $plan->weekly_breakdown['total_leads'] ?? 0,
                'sales' => $plan->weekly_breakdown['new_sales'] ?? 0,
                'revenue' => $plan->weekly_breakdown['total_revenue'] ?? 0,
                'spend' => $plan->weekly_breakdown['ad_costs'] ?? 0,
            ];
        }

        // Calculate weekly from monthly (divide by ~4.3 weeks)
        $weeksInMonth = ceil($plan->working_days / 7);

        return [
            'leads' => round($plan->total_leads / $weeksInMonth),
            'sales' => round($plan->new_sales / $weeksInMonth),
            'revenue' => round($plan->total_revenue / $weeksInMonth, 0),
            'spend' => round($plan->ad_costs / $weeksInMonth, 0),
        ];
    }

    /**
     * Aggregate monthly data
     */
    public function aggregateMonthly(Business $business, int $year, int $month): ?array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get all daily entries for the month
        $entries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        // Get the plan for this month
        $plan = KpiPlan::where('business_id', $business->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        // Calculate totals
        $totals = [
            'leads_total' => $entries->sum('leads_total'),
            'leads_digital' => $entries->sum('leads_digital'),
            'leads_offline' => $entries->sum('leads_offline'),
            'leads_referral' => $entries->sum('leads_referral'),
            'leads_organic' => $entries->sum('leads_organic'),
            'spend_total' => $entries->sum('spend_total'),
            'spend_digital' => $entries->sum('spend_digital'),
            'spend_offline' => $entries->sum('spend_offline'),
            'sales_total' => $entries->sum('sales_total'),
            'sales_new' => $entries->sum('sales_new'),
            'sales_repeat' => $entries->sum('sales_repeat'),
            'revenue_total' => $entries->sum('revenue_total'),
            'revenue_new' => $entries->sum('revenue_new'),
            'revenue_repeat' => $entries->sum('revenue_repeat'),
            'days_with_data' => $entries->where('is_complete', true)->count(),
            'total_days' => $startOfMonth->daysInMonth,
        ];

        // Calculate metrics
        $metrics = $this->calculateMetrics($totals);

        // Calculate achievements
        $achievements = [];
        if ($plan) {
            $achievements = $this->calculateAchievements($totals, $plan);
        }

        return array_merge($totals, $metrics, $achievements, [
            'year' => $year,
            'month' => $month,
            'plan' => $plan,
        ]);
    }

    /**
     * Calculate metrics from totals
     */
    protected function calculateMetrics(array $totals): array
    {
        $metrics = [];

        // Average check
        if ($totals['sales_total'] > 0) {
            $metrics['avg_check'] = round($totals['revenue_total'] / $totals['sales_total'], 0);
        } else {
            $metrics['avg_check'] = 0;
        }

        // Conversion rate
        if ($totals['leads_total'] > 0) {
            $metrics['conversion_rate'] = round(($totals['sales_total'] / $totals['leads_total']) * 100, 2);
        } else {
            $metrics['conversion_rate'] = 0;
        }

        // CPL
        if ($totals['leads_total'] > 0 && $totals['spend_total'] > 0) {
            $metrics['cpl'] = round($totals['spend_total'] / $totals['leads_total'], 0);
        } else {
            $metrics['cpl'] = 0;
        }

        // CAC
        if ($totals['sales_new'] > 0 && $totals['spend_total'] > 0) {
            $metrics['cac'] = round($totals['spend_total'] / $totals['sales_new'], 0);
        } else {
            $metrics['cac'] = 0;
        }

        // CLV (estimate as 3x avg check)
        $metrics['clv'] = $metrics['avg_check'] * 3;

        // LTV/CAC ratio
        if ($metrics['cac'] > 0) {
            $metrics['ltv_cac_ratio'] = round($metrics['clv'] / $metrics['cac'], 2);
        } else {
            $metrics['ltv_cac_ratio'] = 0;
        }

        // ROI
        if ($totals['spend_total'] > 0) {
            $metrics['roi'] = round((($totals['revenue_total'] - $totals['spend_total']) / $totals['spend_total']) * 100, 1);
        } else {
            $metrics['roi'] = 0;
        }

        // ROAS
        if ($totals['spend_total'] > 0) {
            $metrics['roas'] = round($totals['revenue_total'] / $totals['spend_total'], 1);
        } else {
            $metrics['roas'] = 0;
        }

        return $metrics;
    }

    /**
     * Calculate achievements vs plan
     */
    protected function calculateAchievements(array $totals, KpiPlan $plan): array
    {
        $achievements = [];

        // Leads achievement
        if ($plan->total_leads > 0) {
            $achievements['leads_achievement'] = round(($totals['leads_total'] / $plan->total_leads) * 100, 1);
        } else {
            $achievements['leads_achievement'] = 0;
        }

        // Sales achievement
        if ($plan->new_sales > 0) {
            $achievements['sales_achievement'] = round(($totals['sales_new'] / $plan->new_sales) * 100, 1);
        } else {
            $achievements['sales_achievement'] = 0;
        }

        // Revenue achievement
        if ($plan->total_revenue > 0) {
            $achievements['revenue_achievement'] = round(($totals['revenue_total'] / $plan->total_revenue) * 100, 1);
        } else {
            $achievements['revenue_achievement'] = 0;
        }

        // Spend achievement (for spend, lower is better)
        if ($plan->ad_costs > 0) {
            $achievements['spend_achievement'] = round(($totals['spend_total'] / $plan->ad_costs) * 100, 1);
        } else {
            $achievements['spend_achievement'] = 0;
        }

        return $achievements;
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData(Business $business): array
    {
        $today = Carbon::now();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfPreviousWeek = $today->copy()->subWeek()->startOfWeek();
        $endOfPreviousWeek = $startOfPreviousWeek->copy()->endOfWeek();

        // Get current month entries
        $currentMonthEntries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfMonth, $today])
            ->get();

        // Get previous week entries for comparison
        $previousWeekEntries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfPreviousWeek, $endOfPreviousWeek])
            ->get();

        // Current week entries
        $startOfCurrentWeek = $today->copy()->startOfWeek();
        $currentWeekEntries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startOfCurrentWeek, $today])
            ->get();

        // Calculate totals
        $totals = [
            'leads' => $currentMonthEntries->sum('leads_total'),
            'leads_digital' => $currentMonthEntries->sum('leads_digital'),
            'leads_offline' => $currentMonthEntries->sum('leads_offline'),
            'leads_referral' => $currentMonthEntries->sum('leads_referral'),
            'leads_organic' => $currentMonthEntries->sum('leads_organic'),
            'spend' => $currentMonthEntries->sum('spend_total'),
            'spend_digital' => $currentMonthEntries->sum('spend_digital'),
            'spend_offline' => $currentMonthEntries->sum('spend_offline'),
            'sales' => $currentMonthEntries->sum('sales_total'),
            'sales_new' => $currentMonthEntries->sum('sales_new'),
            'sales_repeat' => $currentMonthEntries->sum('sales_repeat'),
            'revenue' => $currentMonthEntries->sum('revenue_total'),
        ];

        // Calculate metrics
        $totalLeads = $totals['leads'] ?: 1;
        $totalSales = $totals['sales'] ?: 1;
        $totalSpend = $totals['spend'] ?: 1;

        $metrics = [
            'cpl' => $totals['leads'] > 0 ? round($totals['spend'] / $totals['leads'], 0) : 0,
            'cac' => $totals['sales'] > 0 ? round($totals['spend'] / $totals['sales'], 0) : 0,
            'avg_check' => $totals['sales'] > 0 ? round($totals['revenue'] / $totals['sales'], 0) : 0,
            'conversion_rate' => $totals['leads'] > 0 ? round(($totals['sales'] / $totals['leads']) * 100, 1) : 0,
            'roi' => $totals['spend'] > 0 ? round((($totals['revenue'] - $totals['spend']) / $totals['spend']) * 100, 1) : 0,
        ];

        // Calculate trends (compare with previous week)
        $prevLeads = $previousWeekEntries->sum('leads_total') ?: 1;
        $currLeads = $currentWeekEntries->sum('leads_total');
        $leadsChange = round((($currLeads - $prevLeads) / $prevLeads) * 100, 1);

        $trends = [
            'leads' => [
                'percent' => abs($leadsChange),
                'direction' => $leadsChange >= 0 ? 'up' : 'down',
            ],
        ];

        // Recent entries (last 7)
        $recentEntries = KpiDailyEntry::where('business_id', $business->id)
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        return [
            'totals' => $totals,
            'metrics' => $metrics,
            'trends' => $trends,
            'recent_entries' => $recentEntries,
            'today' => $today->format('Y-m-d'),
        ];
    }

    /**
     * Get trend data for last N days
     */
    public function getTrendData(Business $business, int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days - 1);

        $entries = KpiDailyEntry::where('business_id', $business->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $trend = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $entry = $entries->firstWhere('date', $currentDate->format('Y-m-d'));

            $trend[] = [
                'date' => $dateStr,
                'day' => $currentDate->format('d'),
                'leads' => $entry ? $entry->leads_total : 0,
                'sales' => $entry ? $entry->sales_total : 0,
                'revenue' => $entry ? $entry->revenue_total : 0,
                'spend' => $entry ? $entry->spend_total : 0,
            ];

            $currentDate->addDay();
        }

        return $trend;
    }
}
