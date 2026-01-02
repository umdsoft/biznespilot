<?php

namespace App\Services;

use App\Models\KpiDailyEntry;
use App\Models\MarketingChannel;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class KPICalculator
{
    /**
     * Calculate CAC (Customer Acquisition Cost)
     * Formula: Total Marketing Spend / New Customers
     */
    public function calculateCAC($businessId, $startDate, $endDate)
    {
        // Total marketing spend from channels
        $totalSpend = MarketingChannel::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('monthly_budget');

        // Count new unique customers in this period (using customer_id instead of customer_name)
        $newCustomers = Sale::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        return $newCustomers > 0 ? round($totalSpend / $newCustomers, 2) : 0;
    }

    /**
     * Calculate CLV (Customer Lifetime Value)
     * Formula: AOV × Purchase Frequency × Customer Lifespan × Gross Margin
     */
    public function calculateCLV($businessId)
    {
        // Average Order Value
        $avgOrderValue = Sale::where('business_id', $businessId)
            ->avg('amount') ?? 0;

        // Configurable parameters (can be moved to settings later)
        $purchaseFrequencyPerYear = 4; // Average purchases per year
        $customerLifespanYears = 2.5; // Average customer lifetime in years
        $grossMarginPercent = 0.4; // 40% gross margin

        $clv = $avgOrderValue * $purchaseFrequencyPerYear * $customerLifespanYears * $grossMarginPercent;

        return round($clv, 2);
    }

    /**
     * Calculate ROAS (Return on Ad Spend)
     * Formula: Revenue from Ads / Ad Spend
     */
    public function calculateROAS($businessId, $startDate, $endDate)
    {
        // Total ad spend
        $adSpend = MarketingChannel::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('monthly_budget');

        // Total revenue from sales
        $revenue = Sale::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        return $adSpend > 0 ? round($revenue / $adSpend, 2) : 0;
    }

    /**
     * Calculate ROI (Return on Investment)
     * Formula: ((Revenue - Cost) / Cost) × 100
     */
    public function calculateROI($businessId, $startDate, $endDate)
    {
        // Total revenue
        $revenue = Sale::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Total marketing cost
        $cost = MarketingChannel::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('monthly_budget');

        return $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 2) : 0;
    }

    /**
     * Calculate Churn Rate
     * Formula: (Lost Customers / Start Customers) × 100
     */
    public function calculateChurnRate($businessId, $startDate, $endDate)
    {
        // Customers at start of period
        $startCustomers = Sale::where('business_id', $businessId)
            ->where('created_at', '<', $startDate)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        // Note: Since sales table doesn't have status, we can't track cancelled/churned
        // For now, return 0 or implement churn tracking through customers table
        return 0; // TODO: Implement proper churn tracking via customers table
    }

    /**
     * Calculate LTV/CAC Ratio
     * Benchmark: 3-5 is good, >5 is excellent
     */
    public function calculateLTVCACRatio($businessId, $startDate, $endDate)
    {
        $clv = $this->calculateCLV($businessId);
        $cac = $this->calculateCAC($businessId, $startDate, $endDate);

        return $cac > 0 ? round($clv / $cac, 2) : 0;
    }

    /**
     * Get all KPIs at once
     */
    public function getAllKPIs($businessId, $startDate, $endDate)
    {
        // Get data from kpi_daily_entries (priority) or fallback to sales
        $dailyData = $this->getDailyEntriesData($businessId, $startDate, $endDate);

        // If we have daily entries data, use it
        if ($dailyData['has_data']) {
            $clv = $dailyData['clv'] > 0 ? $dailyData['clv'] : $this->calculateCLV($businessId);

            return [
                'cac' => $dailyData['cac'],
                'clv' => $clv,
                'roas' => $dailyData['roas'],
                'roi' => $dailyData['roi'],
                'churn_rate' => $this->calculateChurnRate($businessId, $startDate, $endDate),
                'ltv_cac_ratio' => $clv > 0 && $dailyData['cac'] > 0
                    ? round($clv / $dailyData['cac'], 2)
                    : $this->calculateLTVCACRatio($businessId, $startDate, $endDate),
                // Additional KPIs from daily entries
                'total_leads' => $dailyData['leads_total'],
                'total_revenue' => $dailyData['revenue_total'],
                'total_spend' => $dailyData['spend_total'],
                'ad_costs' => $dailyData['spend_total'], // Reklama xarajati = jami xarajat
                'lead_cost' => $dailyData['cpl'], // Lid narxi = CPL
                'new_sales' => $dailyData['sales_new'],
                'repeat_sales' => $dailyData['sales_repeat'],
                'total_sales' => $dailyData['sales_total'],
                'total_customers' => $dailyData['sales_total'], // Mijozlar soni = sotuvlar soni
                'conversion_rate' => $dailyData['conversion_rate'],
                'avg_check' => $dailyData['avg_check'],
                'cpl' => $dailyData['cpl'],
                'gross_margin' => $dailyData['gross_margin'],
                'ctr' => 0, // Not tracked in daily entries yet
            ];
        }

        // Fallback to old Sales-based calculation
        $totalRevenue = $this->getTotalRevenue($businessId, $startDate, $endDate);
        $clv = $this->calculateCLV($businessId);

        return [
            'cac' => $this->calculateCAC($businessId, $startDate, $endDate),
            'clv' => $clv,
            'roas' => $this->calculateROAS($businessId, $startDate, $endDate),
            'roi' => $this->calculateROI($businessId, $startDate, $endDate),
            'churn_rate' => $this->calculateChurnRate($businessId, $startDate, $endDate),
            'ltv_cac_ratio' => $this->calculateLTVCACRatio($businessId, $startDate, $endDate),
            'total_leads' => 0,
            'total_revenue' => $totalRevenue,
            'total_spend' => 0,
            'ad_costs' => 0,
            'lead_cost' => 0,
            'new_sales' => 0,
            'repeat_sales' => 0,
            'total_sales' => 0,
            'total_customers' => $this->getTotalCustomers($businessId),
            'conversion_rate' => 0,
            'avg_check' => 0,
            'cpl' => 0,
            'gross_margin' => 0,
            'ctr' => 0,
        ];
    }

    /**
     * Get aggregated data from kpi_daily_entries
     */
    protected function getDailyEntriesData($businessId, $startDate, $endDate)
    {
        $entries = KpiDailyEntry::where('business_id', $businessId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        if ($entries->isEmpty()) {
            return ['has_data' => false];
        }

        $leadsTotal = $entries->sum('leads_total');
        $spendTotal = $entries->sum('spend_total');
        $salesNew = $entries->sum('sales_new');
        $salesRepeat = $entries->sum('sales_repeat');
        $salesTotal = $entries->sum('sales_total');
        $revenueTotal = $entries->sum('revenue_total');

        // Calculated metrics
        $conversionRate = $leadsTotal > 0 ? round(($salesTotal / $leadsTotal) * 100, 2) : 0;
        $avgCheck = $salesTotal > 0 ? round($revenueTotal / $salesTotal, 2) : 0;
        $cpl = $leadsTotal > 0 ? round($spendTotal / $leadsTotal, 2) : 0;
        $cac = $salesNew > 0 ? round($spendTotal / $salesNew, 2) : 0;
        $roi = $spendTotal > 0 ? round((($revenueTotal - $spendTotal) / $spendTotal) * 100, 2) : 0;
        $roas = $spendTotal > 0 ? round($revenueTotal / $spendTotal, 2) : 0;
        $grossMargin = $revenueTotal > 0 ? round((($revenueTotal - $spendTotal) / $revenueTotal) * 100, 2) : 0;

        // CLV approximation (simplified)
        $clv = $avgCheck * 4 * 2.5 * 0.4; // AOV * purchases/year * years * margin

        return [
            'has_data' => true,
            'leads_total' => $leadsTotal,
            'spend_total' => $spendTotal,
            'sales_new' => $salesNew,
            'sales_repeat' => $salesRepeat,
            'sales_total' => $salesTotal,
            'revenue_total' => $revenueTotal,
            'conversion_rate' => $conversionRate,
            'avg_check' => $avgCheck,
            'cpl' => $cpl,
            'cac' => $cac,
            'roi' => $roi,
            'roas' => $roas,
            'gross_margin' => $grossMargin,
            'clv' => round($clv, 2),
        ];
    }

    /**
     * Get conversion rate
     * Formula: (Completed Sales / Total Leads) × 100
     */
    public function getConversionRate($businessId, $startDate, $endDate)
    {
        $totalSales = Sale::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // All sales count as conversions since we don't have status
        return $totalSales > 0 ? 100 : 0;
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue($businessId, $startDate, $endDate)
    {
        return Sale::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get total customers
     */
    public function getTotalCustomers($businessId)
    {
        return Sale::where('business_id', $businessId)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');
    }

    /**
     * Get benchmark color for ROAS
     */
    public function getROASBenchmark($roas)
    {
        if ($roas >= 5)
            return ['color' => 'blue', 'label' => 'Ajoyib'];
        if ($roas >= 3)
            return ['color' => 'green', 'label' => 'Yaxshi'];
        if ($roas >= 2)
            return ['color' => 'yellow', 'label' => 'Foydali'];
        if ($roas >= 1)
            return ['color' => 'orange', 'label' => 'Break-even'];
        return ['color' => 'red', 'label' => 'Zarar'];
    }

    /**
     * Get benchmark color for LTV/CAC Ratio
     */
    public function getLTVCACBenchmark($ratio)
    {
        if ($ratio >= 5)
            return ['color' => 'blue', 'label' => 'Ajoyib'];
        if ($ratio >= 3)
            return ['color' => 'green', 'label' => 'Yaxshi'];
        if ($ratio >= 1)
            return ['color' => 'yellow', 'label' => 'O\'rta'];
        return ['color' => 'red', 'label' => 'Xavfli'];
    }
}
