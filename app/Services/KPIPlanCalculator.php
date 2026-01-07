<?php

namespace App\Services;

use App\Models\Business;
use App\Models\IndustryBenchmark;
use Illuminate\Support\Facades\DB;

/**
 * KPI Plan Calculator
 *
 * Calculates all KPI metrics based on user input (New Sales, Average Check)
 * Uses historical data or industry benchmarks for calculations
 *
 * Default Conversion Rate: 20% (1 out of 5 leads converts to sale)
 * This means: 100 sales = 500 leads needed
 */
class KPIPlanCalculator
{
    // Default conversion rate: 20% (realistic for B2C businesses)
    const DEFAULT_CONVERSION_RATE = 20.0;

    // Default lead cost in UZS (approximately $2 USD)
    const DEFAULT_LEAD_COST = 25000;

    // Default gross margin percentage
    const DEFAULT_GROSS_MARGIN = 50;

    // Default repeat customer rate
    const DEFAULT_REPEAT_RATE = 0.30;

    /**
     * Calculate all KPIs for next month based on minimal user input
     *
     * @param Business $business
     * @param int $newSales Number of new sales planned
     * @param float $avgCheck Average check amount
     * @return array All calculated KPI metrics
     */
    public function calculateNextMonthPlan(Business $business, int $newSales, float $avgCheck): array
    {
        // Get conversion rate (default 20%)
        $conversionRate = self::DEFAULT_CONVERSION_RATE;

        // Get lead cost from benchmark or default
        $leadCost = $this->getLeadCost($business);

        // Calculate leads needed: newSales / (conversionRate / 100)
        // Example: 100 sales / 0.20 = 500 leads
        $leads = ceil($newSales / ($conversionRate / 100));

        // Calculate total revenue
        $revenue = $newSales * $avgCheck;

        // Calculate advertising costs (leads × lead cost)
        $adCosts = $leads * $leadCost;

        // Ensure ad costs don't exceed 30% of revenue (realistic marketing budget)
        $maxAdCosts = $revenue * 0.30;
        if ($adCosts > $maxAdCosts) {
            $adCosts = $maxAdCosts;
            // Recalculate lead cost based on budget
            $leadCost = $leads > 0 ? $adCosts / $leads : self::DEFAULT_LEAD_COST;
        }

        // Calculate ROI: (Revenue - AdCosts) / AdCosts × 100
        $roi = $adCosts > 0 ? (($revenue - $adCosts) / $adCosts) * 100 : 0;

        // Calculate ROAS (Return on Ad Spend): Revenue / AdCosts
        $roas = $adCosts > 0 ? $revenue / $adCosts : 0;

        // Calculate CTR (Click Through Rate) - industry average
        $ctr = $this->getCTR($business);

        // Calculate repeat sales (30% of new sales)
        $repeatSales = ceil($newSales * self::DEFAULT_REPEAT_RATE);

        // Calculate total customers
        $totalCustomers = $newSales + $repeatSales;

        // Calculate CAC (Customer Acquisition Cost)
        // Only new customers count for acquisition cost
        $cac = $newSales > 0 ? $adCosts / $newSales : 0;

        // Calculate CLV (Customer Lifetime Value) - average 3x of first purchase
        $clv = $avgCheck * 3;

        // Calculate LTV/CAC ratio (healthy ratio is > 3)
        $ltvCacRatio = $cac > 0 ? $clv / $cac : 0;

        // Calculate gross margin
        $grossMarginPercent = self::DEFAULT_GROSS_MARGIN;
        $grossMargin = $revenue * ($grossMarginPercent / 100);

        // Calculate churn rate (industry average)
        $churnRate = $this->getChurnRate($business);

        return [
            // Sales metrics
            'new_sales' => $newSales,
            'repeat_sales' => $repeatSales,
            'total_customers' => $totalCustomers,
            'avg_check' => $avgCheck,

            // Financial metrics
            'total_revenue' => round($revenue, 0),
            'ad_costs' => round($adCosts, 0),
            'gross_margin' => round($grossMargin, 0),
            'gross_margin_percent' => $grossMarginPercent,
            'roi' => round($roi, 1),
            'roas' => round($roas, 1),
            'cac' => round($cac, 0),
            'clv' => round($clv, 0),
            'ltv_cac_ratio' => round($ltvCacRatio, 2),

            // Marketing metrics
            'total_leads' => $leads,
            'lead_cost' => round($leadCost, 0),
            'conversion_rate' => $conversionRate,
            'ctr' => $ctr,

            // Retention metrics
            'churn_rate' => $churnRate,

            // Metadata
            'calculation_method' => 'industry_benchmark',
        ];
    }

    /**
     * Get conversion rate from historical data or benchmark
     */
    protected function getConversionRate(Business $business): float
    {
        // Try to get from last 3 months average
        $historicalRate = $this->getHistoricalConversionRate($business);

        if ($historicalRate > 0) {
            return $historicalRate;
        }

        // Fall back to industry benchmark
        return $this->getIndustryBenchmark($business, 'Lead Conversion Rate', 'avg_value', 20.0);
    }

    /**
     * Get historical conversion rate from business data
     */
    protected function getHistoricalConversionRate(Business $business): float
    {
        // Get last 3 months sales and leads data
        $startDate = now()->subMonths(3);

        $totalSales = DB::table('sales')
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Assuming we track leads somewhere (could be from marketing channels)
        // For now, estimate based on sales if no leads table exists
        if ($totalSales > 0) {
            // Estimate: assume current conversion is around industry average
            // This will be improved when we have actual leads tracking
            return 20.0; // Default assumption (20%)
        }

        return 0;
    }

    /**
     * Get lead cost from historical data or benchmark
     */
    protected function getLeadCost(Business $business): float
    {
        // Use default lead cost: 15,000 UZS (~$1.20 USD)
        // This is realistic for social media advertising in Uzbekistan
        return self::DEFAULT_LEAD_COST;
    }

    /**
     * Get historical lead cost from business data
     */
    protected function getHistoricalLeadCost(Business $business): float
    {
        $startDate = now()->subMonths(3);

        // Get total marketing spend
        $totalSpend = DB::table('marketing_channels')
            ->where('business_id', $business->id)
            ->sum('monthly_budget') * 3; // 3 months

        if ($totalSpend > 0) {
            $totalLeads = $this->getHistoricalLeadCount($business);
            if ($totalLeads > 0) {
                return $totalSpend / $totalLeads;
            }
        }

        return 0;
    }

    /**
     * Get historical lead count
     */
    protected function getHistoricalLeadCount(Business $business): int
    {
        // This would come from actual leads tracking
        // For now, estimate from sales with conversion rate
        $startDate = now()->subMonths(3);

        $totalSales = DB::table('sales')
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        if ($totalSales > 0) {
            // Estimate leads based on 30% conversion
            return ceil($totalSales / 0.3);
        }

        return 0;
    }

    /**
     * Get CTR from historical or benchmark
     */
    protected function getCTR(Business $business): float
    {
        // Fall back to industry benchmark
        return $this->getIndustryBenchmark($business, 'Click Through Rate', 'avg_value', 1.5);
    }

    /**
     * Get churn rate from historical or benchmark
     */
    protected function getChurnRate(Business $business): float
    {
        // Fall back to industry benchmark
        return $this->getIndustryBenchmark($business, 'Monthly Churn Rate', 'avg_value', 8.0);
    }

    /**
     * Get industry benchmark value
     */
    protected function getIndustryBenchmark(Business $business, string $metricName, string $valueField, float $default): float
    {
        // Get business industry
        $industry = $business->industry ?? 'default';

        try {
            // Try to find benchmark for specific industry first
            $benchmark = IndustryBenchmark::where('industry', 'LIKE', "%{$industry}%")->first();

            // Fallback to default industry if not found
            if (!$benchmark) {
                $benchmark = IndustryBenchmark::where('industry', 'default')->first();
            }

            if (!$benchmark) {
                return $default;
            }

            // Map metric names to benchmark fields
            $fieldMap = [
                'Lead Conversion Rate' => 'avg_conversion_rate',
                'Cost Per Lead' => null, // Not in algorithm benchmarks, use default
                'Click Through Rate' => null,
                'Monthly Churn Rate' => null,
            ];

            $field = $fieldMap[$metricName] ?? null;

            if ($field && isset($benchmark->$field)) {
                return (float) $benchmark->$field;
            }
        } catch (\Exception $e) {
            // If database error occurs, return default value
            \Log::warning('IndustryBenchmark query failed: ' . $e->getMessage());
        }

        return $default;
    }

    /**
     * Get calculation method used
     */
    protected function getCalculationMethod(Business $business): string
    {
        $hasHistoricalData = $this->getHistoricalLeadCount($business) > 0;

        return $hasHistoricalData
            ? 'historical_data'
            : 'industry_benchmark';
    }

    /**
     * Calculate daily and weekly plans from monthly
     */
    public function breakdownMonthlyPlan(array $monthlyMetrics, int $workingDays = 30): array
    {
        // Metrics that should NOT be divided (percentages, ratios)
        $nonDivisibleMetrics = [
            'conversion_rate', 'ctr', 'roi', 'roas', 'churn_rate',
            'gross_margin_percent', 'ltv_cac_ratio', 'calculation_method'
        ];

        $breakdown = [
            'monthly' => $monthlyMetrics,
            'weekly' => [],
            'daily' => [],
        ];

        // Calculate number of weeks (approximately)
        $weeks = max(1, ceil($workingDays / 7));

        // Calculate weekly (divide by number of weeks)
        foreach ($monthlyMetrics as $key => $value) {
            if (is_numeric($value) && !in_array($key, $nonDivisibleMetrics)) {
                $breakdown['weekly'][$key] = round($value / $weeks);
            } else {
                $breakdown['weekly'][$key] = $value;
            }
        }

        // Calculate daily (divide by working days)
        foreach ($monthlyMetrics as $key => $value) {
            if (is_numeric($value) && !in_array($key, $nonDivisibleMetrics)) {
                $breakdown['daily'][$key] = round($value / $workingDays);
            } else {
                $breakdown['daily'][$key] = $value;
            }
        }

        return $breakdown;
    }

    /**
     * Determine target month based on remaining days
     * If less than 10 days remain in current month, use next month
     *
     * @return array [year, month, start_date, end_date, working_days]
     */
    public function determineTargetMonth(): array
    {
        $today = now();
        $endOfMonth = $today->copy()->endOfMonth();
        $daysRemaining = $today->diffInDays($endOfMonth);

        // Agar 10 kundan kam qolgan bo'lsa, keyingi oy
        if ($daysRemaining < 10) {
            $targetDate = $today->copy()->addMonth()->startOfMonth();
            $startDate = $targetDate->copy();
            $endDate = $targetDate->copy()->endOfMonth();
        } else {
            // Joriy oy (bugundan oy oxirigacha)
            $startDate = $today->copy();
            $endDate = $endOfMonth;
        }

        $workingDays = $startDate->diffInDays($endDate) + 1;

        return [
            'year' => $startDate->year,
            'month' => $startDate->month,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'working_days' => $workingDays,
        ];
    }

    /**
     * Create a full KPI plan with all breakdowns
     *
     * @param Business $business
     * @param int $newSales
     * @param float $avgCheck
     * @param int|null $leads (optional, calculated if not provided)
     * @param float|null $leadCost (optional, uses default if not provided)
     * @return array
     */
    public function createFullPlan(
        Business $business,
        int $newSales,
        float $avgCheck,
        ?int $leads = null,
        ?float $leadCost = null
    ): array {
        // Determine target month
        $targetMonth = $this->determineTargetMonth();

        // Get conversion rate
        $conversionRate = self::DEFAULT_CONVERSION_RATE;

        // Calculate or use provided lead cost
        $leadCost = $leadCost ?? self::DEFAULT_LEAD_COST;

        // Calculate leads if not provided
        if ($leads === null) {
            $leads = (int) ceil($newSales / ($conversionRate / 100));
        } else {
            // Recalculate conversion rate based on provided leads
            $conversionRate = $leads > 0 ? round(($newSales / $leads) * 100, 1) : self::DEFAULT_CONVERSION_RATE;
        }

        // Calculate all metrics
        $revenue = $newSales * $avgCheck;
        $adCosts = $leads * $leadCost;

        // Cap ad costs at 30% of revenue
        $maxAdCosts = $revenue * 0.30;
        if ($adCosts > $maxAdCosts) {
            $adCosts = $maxAdCosts;
            $leadCost = $leads > 0 ? $adCosts / $leads : self::DEFAULT_LEAD_COST;
        }

        // Calculate ROI and ROAS
        $roi = $adCosts > 0 ? (($revenue - $adCosts) / $adCosts) * 100 : 0;
        $roas = $adCosts > 0 ? $revenue / $adCosts : 0;

        // Calculate other metrics
        $ctr = $this->getCTR($business);
        $repeatSales = (int) ceil($newSales * self::DEFAULT_REPEAT_RATE);
        $totalCustomers = $newSales + $repeatSales;
        $cac = $newSales > 0 ? $adCosts / $newSales : 0;
        $clv = $avgCheck * 3;
        $ltvCacRatio = $cac > 0 ? $clv / $cac : 0;
        $grossMarginPercent = self::DEFAULT_GROSS_MARGIN;
        $grossMargin = $revenue * ($grossMarginPercent / 100);
        $churnRate = $this->getChurnRate($business);

        // Build monthly metrics
        $monthlyMetrics = [
            'new_sales' => $newSales,
            'repeat_sales' => $repeatSales,
            'total_customers' => $totalCustomers,
            'avg_check' => round($avgCheck, 0),
            'total_revenue' => round($revenue, 0),
            'ad_costs' => round($adCosts, 0),
            'gross_margin' => round($grossMargin, 0),
            'gross_margin_percent' => $grossMarginPercent,
            'roi' => round($roi, 1),
            'roas' => round($roas, 1),
            'cac' => round($cac, 0),
            'clv' => round($clv, 0),
            'ltv_cac_ratio' => round($ltvCacRatio, 2),
            'total_leads' => $leads,
            'lead_cost' => round($leadCost, 0),
            'conversion_rate' => $conversionRate,
            'ctr' => $ctr,
            'churn_rate' => $churnRate,
            'calculation_method' => 'industry_benchmark',
        ];

        // Create breakdowns
        $breakdowns = $this->breakdownMonthlyPlan($monthlyMetrics, $targetMonth['working_days']);

        return [
            'year' => $targetMonth['year'],
            'month' => $targetMonth['month'],
            'start_date' => $targetMonth['start_date'],
            'end_date' => $targetMonth['end_date'],
            'working_days' => $targetMonth['working_days'],
            'metrics' => $monthlyMetrics,
            'daily' => $breakdowns['daily'],
            'weekly' => $breakdowns['weekly'],
        ];
    }
}
