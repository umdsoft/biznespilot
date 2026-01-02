<?php

namespace App\Services\Integration;

use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosKpiSyncService extends BaseKpiSyncService
{
    /**
     * KPI codes that can be synced from POS systems (iiko, Poster, etc.)
     */
    protected array $supportedKpis = [
        'daily_revenue',                  // Total revenue for the day
        'average_check',                  // Average transaction value
        'table_turnover_rate',            // For restaurants - table occupancy
        'order_fulfillment_time',         // Average time from order to delivery
        'customer_count',                 // Number of customers
        'repeat_customer_rate',           // Percentage of repeat customers
        'product_return_rate',            // Return/refund rate
        'revenue_per_customer',           // Revenue per customer
        'peak_hours_revenue',             // Revenue during peak hours
        'menu_item_sales',                // Popular menu items performance
        'inventory_turnover',             // Stock turnover rate
        'waste_percentage',               // Food waste percentage
        'labor_cost_percentage',          // Labor costs as % of revenue
        'gross_profit_margin',            // Gross margin
    ];

    /**
     * Get integration name
     */
    public function getIntegrationName(): string
    {
        return 'pos_system';
    }

    /**
     * Get supported KPIs
     */
    public function getSupportedKpis(): array
    {
        return $this->supportedKpis;
    }

    /**
     * Check if POS integration is available for business
     */
    public function isAvailable(int $businessId): bool
    {
        $business = Business::find($businessId);
        if (!$business) {
            return false;
        }

        // Check if business has connected POS system
        // This checks for any POS integration (iiko, Poster, custom POS)
        $hasPosIntegration = DB::table('integrations')
            ->where('business_id', $businessId)
            ->where('integration_type', 'pos')
            ->where('is_active', true)
            ->exists();

        return $hasPosIntegration;
    }

    /**
     * Sync specific KPI
     */
    public function syncKpi(int $businessId, string $kpiCode, string $date): array
    {
        if (!in_array($kpiCode, $this->supportedKpis)) {
            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => 'KPI not supported by POS integration',
            ];
        }

        // Get POS integration details
        $posIntegration = DB::table('integrations')
            ->where('business_id', $businessId)
            ->where('integration_type', 'pos')
            ->where('is_active', true)
            ->first();

        if (!$posIntegration) {
            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => 'POS system not connected',
            ];
        }

        try {
            $value = match ($kpiCode) {
                'daily_revenue' => $this->calculateDailyRevenue($businessId, $date),
                'average_check' => $this->calculateAverageCheck($businessId, $date),
                'table_turnover_rate' => $this->calculateTableTurnover($businessId, $date),
                'order_fulfillment_time' => $this->calculateFulfillmentTime($businessId, $date),
                'customer_count' => $this->calculateCustomerCount($businessId, $date),
                'repeat_customer_rate' => $this->calculateRepeatCustomerRate($businessId, $date),
                'product_return_rate' => $this->calculateReturnRate($businessId, $date),
                'revenue_per_customer' => $this->calculateRevenuePerCustomer($businessId, $date),
                'peak_hours_revenue' => $this->calculatePeakHoursRevenue($businessId, $date),
                'menu_item_sales' => $this->calculateMenuItemSales($businessId, $date),
                'inventory_turnover' => $this->calculateInventoryTurnover($businessId, $date),
                'waste_percentage' => $this->calculateWastePercentage($businessId, $date),
                'labor_cost_percentage' => $this->calculateLaborCostPercentage($businessId, $date),
                'gross_profit_margin' => $this->calculateGrossProfitMargin($businessId, $date),
                default => null,
            };

            if ($value === null) {
                return [
                    'success' => false,
                    'kpi_code' => $kpiCode,
                    'value' => null,
                    'message' => 'Insufficient data to calculate KPI',
                ];
            }

            // Save the KPI value
            $this->saveKpiValue($businessId, $kpiCode, $date, $value, [
                'pos_integration_id' => $posIntegration->id,
                'pos_system' => $posIntegration->provider ?? 'unknown',
            ]);

            return [
                'success' => true,
                'kpi_code' => $kpiCode,
                'value' => $value,
                'message' => 'KPI synced successfully',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync POS KPI: {$kpiCode}", [
                'business_id' => $businessId,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'kpi_code' => $kpiCode,
                'value' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate daily revenue from POS transactions
     */
    protected function calculateDailyRevenue(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get revenue from pos_transactions or orders table
        $revenue = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->sum('total_amount');

        return $revenue > 0 ? round($revenue, 2) : null;
    }

    /**
     * Calculate average check/transaction value
     */
    protected function calculateAverageCheck(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $data = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->selectRaw('SUM(total_amount) as total_revenue, COUNT(*) as transaction_count')
            ->first();

        if (!$data || $data->transaction_count === 0) {
            return null;
        }

        return round($data->total_revenue / $data->transaction_count, 2);
    }

    /**
     * Calculate table turnover rate
     */
    protected function calculateTableTurnover(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get total number of tables served vs available tables
        $tablesServed = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->whereNotNull('table_number')
            ->distinct('table_number')
            ->count('table_number');

        $totalTables = DB::table('restaurant_tables')
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->count();

        if ($totalTables === 0) {
            return null;
        }

        return round(($tablesServed / $totalTables) * 100, 2);
    }

    /**
     * Calculate average order fulfillment time
     */
    protected function calculateFulfillmentTime(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $avgTime = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->whereNotNull('preparation_time')
            ->avg('preparation_time');

        return $avgTime > 0 ? round($avgTime, 2) : null;
    }

    /**
     * Calculate total customer count
     */
    protected function calculateCustomerCount(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $count = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->sum('customer_count');

        return $count > 0 ? $count : null;
    }

    /**
     * Calculate repeat customer rate
     */
    protected function calculateRepeatCustomerRate(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Get customers who made more than one purchase
        $repeatCustomers = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->select('customer_id', DB::raw('COUNT(*) as visit_count'))
            ->groupBy('customer_id')
            ->having('visit_count', '>', 1)
            ->count();

        $totalCustomers = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count();

        if ($totalCustomers === 0) {
            return null;
        }

        return round(($repeatCustomers / $totalCustomers) * 100, 2);
    }

    /**
     * Calculate product return/refund rate
     */
    protected function calculateReturnRate(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $returns = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'refunded')
            ->count();

        $totalTransactions = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->whereIn('status', ['completed', 'refunded'])
            ->count();

        if ($totalTransactions === 0) {
            return null;
        }

        return round(($returns / $totalTransactions) * 100, 2);
    }

    /**
     * Calculate revenue per customer
     */
    protected function calculateRevenuePerCustomer(int $businessId, string $date): ?float
    {
        $dailyRevenue = $this->calculateDailyRevenue($businessId, $date);
        $customerCount = $this->calculateCustomerCount($businessId, $date);

        if (!$dailyRevenue || !$customerCount || $customerCount === 0) {
            return null;
        }

        return round($dailyRevenue / $customerCount, 2);
    }

    /**
     * Calculate peak hours revenue
     */
    protected function calculatePeakHoursRevenue(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Peak hours typically 12:00-14:00 and 19:00-21:00
        $peakRevenue = DB::table('pos_transactions')
            ->where('business_id', $businessId)
            ->whereDate('transaction_date', $dateObj)
            ->where('status', 'completed')
            ->where(function ($query) {
                $query->whereTime('transaction_date', '>=', '12:00:00')
                    ->whereTime('transaction_date', '<=', '14:00:00')
                    ->orWhere(function ($q) {
                        $q->whereTime('transaction_date', '>=', '19:00:00')
                            ->whereTime('transaction_date', '<=', '21:00:00');
                    });
            })
            ->sum('total_amount');

        return $peakRevenue > 0 ? round($peakRevenue, 2) : null;
    }

    /**
     * Calculate menu item sales performance
     */
    protected function calculateMenuItemSales(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        // Average sales per menu item
        $avgSales = DB::table('pos_transaction_items')
            ->join('pos_transactions', 'pos_transaction_items.transaction_id', '=', 'pos_transactions.id')
            ->where('pos_transactions.business_id', $businessId)
            ->whereDate('pos_transactions.transaction_date', $dateObj)
            ->where('pos_transactions.status', 'completed')
            ->avg('pos_transaction_items.quantity');

        return $avgSales > 0 ? round($avgSales, 2) : null;
    }

    /**
     * Calculate inventory turnover
     */
    protected function calculateInventoryTurnover(int $businessId, string $date): ?float
    {
        // This would require inventory tracking
        // TODO: Implement when inventory data is available
        return null;
    }

    /**
     * Calculate waste percentage
     */
    protected function calculateWastePercentage(int $businessId, string $date): ?float
    {
        // This would require waste tracking
        // TODO: Implement when waste tracking is available
        return null;
    }

    /**
     * Calculate labor cost percentage
     */
    protected function calculateLaborCostPercentage(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $revenue = $this->calculateDailyRevenue($businessId, $date);

        $laborCost = DB::table('staff_shifts')
            ->where('business_id', $businessId)
            ->whereDate('shift_date', $dateObj)
            ->sum('total_cost');

        if (!$revenue || $revenue === 0 || !$laborCost) {
            return null;
        }

        return round(($laborCost / $revenue) * 100, 2);
    }

    /**
     * Calculate gross profit margin
     */
    protected function calculateGrossProfitMargin(int $businessId, string $date): ?float
    {
        $dateObj = Carbon::parse($date);

        $revenue = $this->calculateDailyRevenue($businessId, $date);

        $cogs = DB::table('pos_transaction_items')
            ->join('pos_transactions', 'pos_transaction_items.transaction_id', '=', 'pos_transactions.id')
            ->where('pos_transactions.business_id', $businessId)
            ->whereDate('pos_transactions.transaction_date', $dateObj)
            ->where('pos_transactions.status', 'completed')
            ->sum('pos_transaction_items.cost');

        if (!$revenue || $revenue === 0) {
            return null;
        }

        $grossProfit = $revenue - ($cogs ?? 0);
        return round(($grossProfit / $revenue) * 100, 2);
    }
}
