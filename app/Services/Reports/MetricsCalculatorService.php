<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\Customer;
use App\Models\IndustryBenchmark;
use App\Models\KpiDailyActual;
use App\Models\KpiPlan;
use App\Models\Lead;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * MetricsCalculatorService
 *
 * Calculates all business metrics for reports
 * Uses pure algorithmic approach without AI
 */
class MetricsCalculatorService
{
    protected Business $business;

    protected Carbon $startDate;

    protected Carbon $endDate;

    // Metric categories
    public const CATEGORY_SALES = 'sales';

    public const CATEGORY_MARKETING = 'marketing';

    public const CATEGORY_FINANCIAL = 'financial';

    public const CATEGORY_CUSTOMER = 'customer';

    public const CATEGORY_EFFICIENCY = 'efficiency';

    /**
     * Calculate all metrics for a business in given period
     */
    public function calculate(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $this->business = $business;
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'sales' => $this->calculateSalesMetrics(),
            'marketing' => $this->calculateMarketingMetrics(),
            'financial' => $this->calculateFinancialMetrics(),
            'customer' => $this->calculateCustomerMetrics(),
            'efficiency' => $this->calculateEfficiencyMetrics(),
            'kpi_progress' => $this->calculateKpiProgress(),
        ];
    }

    /**
     * Calculate sales metrics
     */
    protected function calculateSalesMetrics(): array
    {
        // Get sales data from KPI daily actuals
        $dailyData = KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $totalSales = $dailyData->sum('actual_new_sales') + $dailyData->sum('actual_repeat_sales');
        $newSales = $dailyData->sum('actual_new_sales');
        $repeatSales = $dailyData->sum('actual_repeat_sales');
        $totalRevenue = $dailyData->sum('actual_revenue');
        $avgCheck = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Calculate from sales table if KPI data is empty
        if ($totalSales == 0) {
            $salesQuery = Sale::where('business_id', $this->business->id)
                ->whereBetween('created_at', [$this->startDate, $this->endDate]);

            $totalSales = $salesQuery->count();
            $totalRevenue = $salesQuery->sum('amount');
            $avgCheck = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        }

        // Calculate daily average
        $days = max(1, $this->startDate->diffInDays($this->endDate) + 1);
        $dailyAvgSales = $totalSales / $days;
        $dailyAvgRevenue = $totalRevenue / $days;

        return [
            'total_sales' => $totalSales,
            'new_sales' => $newSales,
            'repeat_sales' => $repeatSales,
            'repeat_rate' => $totalSales > 0 ? round(($repeatSales / $totalSales) * 100, 1) : 0,
            'total_revenue' => round($totalRevenue, 0),
            'avg_check' => round($avgCheck, 0),
            'daily_avg_sales' => round($dailyAvgSales, 1),
            'daily_avg_revenue' => round($dailyAvgRevenue, 0),
        ];
    }

    /**
     * Calculate marketing metrics
     */
    protected function calculateMarketingMetrics(): array
    {
        $dailyData = KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $totalLeads = $dailyData->sum('actual_leads');
        $totalAdCosts = $dailyData->sum('actual_ad_costs');
        $totalSales = $dailyData->sum('actual_new_sales');

        // Fallback to leads table
        if ($totalLeads == 0) {
            $totalLeads = Lead::where('business_id', $this->business->id)
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->count();
        }

        // Calculate derived metrics
        $leadCost = $totalLeads > 0 ? $totalAdCosts / $totalLeads : 0;
        $conversionRate = $totalLeads > 0 ? ($totalSales / $totalLeads) * 100 : 0;

        // Get marketing channel breakdown
        $channelBreakdown = $this->getChannelBreakdown();

        return [
            'total_leads' => $totalLeads,
            'total_ad_costs' => round($totalAdCosts, 0),
            'lead_cost' => round($leadCost, 0),
            'conversion_rate' => round($conversionRate, 1),
            'channel_breakdown' => $channelBreakdown,
        ];
    }

    /**
     * Calculate financial metrics
     */
    protected function calculateFinancialMetrics(): array
    {
        $dailyData = KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $totalRevenue = $dailyData->sum('actual_revenue');
        $totalAdCosts = $dailyData->sum('actual_ad_costs');
        $totalNewSales = $dailyData->sum('actual_new_sales');

        // Calculate ROI
        $roi = $totalAdCosts > 0 ? (($totalRevenue - $totalAdCosts) / $totalAdCosts) * 100 : 0;

        // Calculate ROAS
        $roas = $totalAdCosts > 0 ? $totalRevenue / $totalAdCosts : 0;

        // Calculate CAC (Customer Acquisition Cost)
        $cac = $totalNewSales > 0 ? $totalAdCosts / $totalNewSales : 0;

        // Get average check for CLV calculation
        $avgCheck = $totalNewSales > 0 ? $totalRevenue / $totalNewSales : 0;

        // Calculate CLV (assuming 3x first purchase)
        $clv = $avgCheck * 3;

        // Calculate LTV/CAC ratio
        $ltvCacRatio = $cac > 0 ? $clv / $cac : 0;

        // Estimate gross margin (default 50%)
        $grossMarginPercent = 50;
        $grossMargin = $totalRevenue * ($grossMarginPercent / 100);

        // Calculate profit
        $profit = $totalRevenue - $totalAdCosts;

        return [
            'total_revenue' => round($totalRevenue, 0),
            'total_costs' => round($totalAdCosts, 0),
            'gross_margin' => round($grossMargin, 0),
            'gross_margin_percent' => $grossMarginPercent,
            'profit' => round($profit, 0),
            'roi' => round($roi, 1),
            'roas' => round($roas, 2),
            'cac' => round($cac, 0),
            'clv' => round($clv, 0),
            'ltv_cac_ratio' => round($ltvCacRatio, 2),
        ];
    }

    /**
     * Calculate customer metrics
     */
    protected function calculateCustomerMetrics(): array
    {
        // New customers in period
        $newCustomers = Customer::where('business_id', $this->business->id)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();

        // Total customers
        $totalCustomers = Customer::where('business_id', $this->business->id)
            ->where('created_at', '<=', $this->endDate)
            ->count();

        // Active customers (had sales in period)
        $activeCustomers = DB::table('sales')
            ->where('business_id', $this->business->id)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->distinct('customer_id')
            ->count('customer_id');

        // Repeat customers (customers with more than 1 sale in period)
        $repeatCustomers = DB::table('sales')
            ->select('customer_id')
            ->where('business_id', $this->business->id)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Calculate retention rate
        $retentionRate = $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0;

        // Estimate churn rate (from benchmark or default)
        $churnRate = $this->getIndustryBenchmark('Monthly Churn Rate', 8.0);

        return [
            'new_customers' => $newCustomers,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'repeat_customers' => $repeatCustomers,
            'retention_rate' => round($retentionRate, 1),
            'churn_rate' => round($churnRate, 1),
        ];
    }

    /**
     * Calculate efficiency metrics
     */
    protected function calculateEfficiencyMetrics(): array
    {
        $dailyData = KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $totalLeads = $dailyData->sum('actual_leads');
        $totalSales = $dailyData->sum('actual_new_sales');
        $totalRevenue = $dailyData->sum('actual_revenue');
        $totalAdCosts = $dailyData->sum('actual_ad_costs');

        // Lead to sale conversion
        $leadToSaleRate = $totalLeads > 0 ? ($totalSales / $totalLeads) * 100 : 0;

        // Cost per sale
        $costPerSale = $totalSales > 0 ? $totalAdCosts / $totalSales : 0;

        // Revenue per lead
        $revenuePerLead = $totalLeads > 0 ? $totalRevenue / $totalLeads : 0;

        // Marketing efficiency ratio (Revenue / Ad Costs)
        $marketingEfficiency = $totalAdCosts > 0 ? $totalRevenue / $totalAdCosts : 0;

        return [
            'lead_to_sale_rate' => round($leadToSaleRate, 1),
            'cost_per_sale' => round($costPerSale, 0),
            'revenue_per_lead' => round($revenuePerLead, 0),
            'marketing_efficiency' => round($marketingEfficiency, 2),
        ];
    }

    /**
     * Calculate KPI progress vs plan
     */
    protected function calculateKpiProgress(): array
    {
        // Get active KPI plan
        $plan = KpiPlan::where('business_id', $this->business->id)
            ->where('year', $this->startDate->year)
            ->where('month', $this->startDate->month)
            ->where('status', 'active')
            ->first();

        if (! $plan) {
            return [
                'has_plan' => false,
                'message' => 'Bu davr uchun KPI plan mavjud emas',
            ];
        }

        // Get actual totals
        $dailyData = KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $actualSales = $dailyData->sum('actual_new_sales');
        $actualRevenue = $dailyData->sum('actual_revenue');
        $actualLeads = $dailyData->sum('actual_leads');

        // Calculate progress percentages
        $salesProgress = $plan->planned_new_sales > 0
            ? ($actualSales / $plan->planned_new_sales) * 100
            : 0;

        $revenueProgress = $plan->planned_revenue > 0
            ? ($actualRevenue / $plan->planned_revenue) * 100
            : 0;

        $leadsProgress = $plan->planned_leads > 0
            ? ($actualLeads / $plan->planned_leads) * 100
            : 0;

        // Calculate days progress
        $totalDays = $plan->start_date->diffInDays($plan->end_date) + 1;
        $passedDays = $plan->start_date->diffInDays(min($this->endDate, now())) + 1;
        $expectedProgress = ($passedDays / $totalDays) * 100;

        return [
            'has_plan' => true,
            'plan_id' => $plan->id,
            'expected_progress' => round($expectedProgress, 1),
            'sales' => [
                'planned' => $plan->planned_new_sales,
                'actual' => $actualSales,
                'progress' => round($salesProgress, 1),
                'status' => $this->getProgressStatus($salesProgress, $expectedProgress),
            ],
            'revenue' => [
                'planned' => $plan->planned_revenue,
                'actual' => round($actualRevenue, 0),
                'progress' => round($revenueProgress, 1),
                'status' => $this->getProgressStatus($revenueProgress, $expectedProgress),
            ],
            'leads' => [
                'planned' => $plan->planned_leads,
                'actual' => $actualLeads,
                'progress' => round($leadsProgress, 1),
                'status' => $this->getProgressStatus($leadsProgress, $expectedProgress),
            ],
        ];
    }

    /**
     * Get progress status based on actual vs expected
     */
    protected function getProgressStatus(float $actual, float $expected): string
    {
        if ($expected <= 0) {
            return 'neutral';
        }

        $ratio = $actual / $expected;

        return match (true) {
            $ratio >= 1.1 => 'excellent',    // 10%+ above expected
            $ratio >= 0.9 => 'on_track',     // Within 10% of expected
            $ratio >= 0.7 => 'warning',      // 10-30% behind
            default => 'critical',            // 30%+ behind
        };
    }

    /**
     * Get channel breakdown for marketing
     */
    protected function getChannelBreakdown(): array
    {
        // Get leads by source - join through kpi_daily_entries to filter by business_id
        $breakdown = DB::table('kpi_daily_source_details')
            ->join('kpi_daily_entries', 'kpi_daily_source_details.daily_entry_id', '=', 'kpi_daily_entries.id')
            ->join('lead_sources', 'kpi_daily_source_details.lead_source_id', '=', 'lead_sources.id')
            ->where('kpi_daily_entries.business_id', $this->business->id)
            ->whereBetween('kpi_daily_entries.date', [$this->startDate, $this->endDate])
            ->select(
                'lead_sources.name',
                DB::raw('SUM(kpi_daily_source_details.leads_count) as total_leads'),
                DB::raw('SUM(kpi_daily_source_details.conversions) as total_sales'),
                DB::raw('SUM(kpi_daily_source_details.revenue) as total_revenue')
            )
            ->groupBy('lead_sources.id', 'lead_sources.name')
            ->orderByDesc('total_leads')
            ->get();

        return $breakdown->map(function ($item) {
            return [
                'name' => $item->name,
                'leads' => (int) ($item->total_leads ?? 0),
                'sales' => (int) ($item->total_sales ?? 0),
                'revenue' => round((float) ($item->total_revenue ?? 0), 0),
                'conversion' => $item->total_leads > 0
                    ? round(($item->total_sales / $item->total_leads) * 100, 1)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get industry benchmark value
     */
    protected function getIndustryBenchmark(string $metricName, float $default): float
    {
        $industry = $this->business->industry ?? 'default';

        try {
            $benchmark = IndustryBenchmark::where('industry', 'LIKE', "%{$industry}%")->first();

            if (! $benchmark) {
                $benchmark = IndustryBenchmark::where('industry', 'default')->first();
            }

            if ($benchmark) {
                $fieldMap = [
                    'Lead Conversion Rate' => 'avg_conversion_rate',
                    'Monthly Churn Rate' => 'churn_rate',
                    'Click Through Rate' => 'ctr',
                ];

                $field = $fieldMap[$metricName] ?? null;
                if ($field && isset($benchmark->$field)) {
                    return (float) $benchmark->$field;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('IndustryBenchmark query failed: '.$e->getMessage());
        }

        return $default;
    }

    /**
     * Calculate single metric value
     */
    public function calculateMetric(Business $business, string $metricCode, Carbon $startDate, Carbon $endDate): float
    {
        $this->business = $business;
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return match ($metricCode) {
            'total_sales' => $this->calculateSalesMetrics()['total_sales'],
            'total_revenue' => $this->calculateSalesMetrics()['total_revenue'],
            'avg_check' => $this->calculateSalesMetrics()['avg_check'],
            'total_leads' => $this->calculateMarketingMetrics()['total_leads'],
            'conversion_rate' => $this->calculateMarketingMetrics()['conversion_rate'],
            'roi' => $this->calculateFinancialMetrics()['roi'],
            'roas' => $this->calculateFinancialMetrics()['roas'],
            'cac' => $this->calculateFinancialMetrics()['cac'],
            'clv' => $this->calculateFinancialMetrics()['clv'],
            default => 0,
        };
    }
}
