<?php

namespace App\Services;

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

        // Count new unique customers in this period
        $newCustomers = Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('customer_name') // Using customer_name as proxy for unique customers
            ->count('customer_name');

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
            ->where('status', 'completed')
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
            ->where('status', 'completed')
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
            ->where('status', 'completed')
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
            ->where('status', 'completed')
            ->where('created_at', '<', $startDate)
            ->distinct('customer_name')
            ->count('customer_name');

        // Lost customers (cancelled sales in period)
        $lostCustomers = Sale::where('business_id', $businessId)
            ->where('status', 'cancelled')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->distinct('customer_name')
            ->count('customer_name');

        return $startCustomers > 0 ? round(($lostCustomers / $startCustomers) * 100, 2) : 0;
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
        return [
            'cac' => $this->calculateCAC($businessId, $startDate, $endDate),
            'clv' => $this->calculateCLV($businessId),
            'roas' => $this->calculateROAS($businessId, $startDate, $endDate),
            'roi' => $this->calculateROI($businessId, $startDate, $endDate),
            'churn_rate' => $this->calculateChurnRate($businessId, $startDate, $endDate),
            'ltv_cac_ratio' => $this->calculateLTVCACRatio($businessId, $startDate, $endDate),
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

        $completedSales = Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return $totalSales > 0 ? round(($completedSales / $totalSales) * 100, 2) : 0;
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue($businessId, $startDate, $endDate)
    {
        return Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get total customers
     */
    public function getTotalCustomers($businessId)
    {
        return Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->distinct('customer_name')
            ->count('customer_name');
    }

    /**
     * Get benchmark color for ROAS
     */
    public function getROASBenchmark($roas)
    {
        if ($roas >= 5) return ['color' => 'blue', 'label' => 'Ajoyib'];
        if ($roas >= 3) return ['color' => 'green', 'label' => 'Yaxshi'];
        if ($roas >= 2) return ['color' => 'yellow', 'label' => 'Foydali'];
        if ($roas >= 1) return ['color' => 'orange', 'label' => 'Break-even'];
        return ['color' => 'red', 'label' => 'Zarar'];
    }

    /**
     * Get benchmark color for LTV/CAC Ratio
     */
    public function getLTVCACBenchmark($ratio)
    {
        if ($ratio >= 5) return ['color' => 'blue', 'label' => 'Ajoyib'];
        if ($ratio >= 3) return ['color' => 'green', 'label' => 'Yaxshi'];
        if ($ratio >= 1) return ['color' => 'yellow', 'label' => 'O\'rta'];
        return ['color' => 'red', 'label' => 'Xavfli'];
    }
}
