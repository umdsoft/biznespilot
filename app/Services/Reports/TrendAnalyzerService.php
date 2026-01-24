<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrendAnalyzerService
{
    public function analyze(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'sales_trend' => $this->analyzeSalesTrend($business, $startDate, $endDate),
            'leads_trend' => $this->analyzeLeadsTrend($business, $startDate, $endDate),
            'period_comparison' => $this->comparePeriods($business, $startDate, $endDate),
        ];
    }

    protected function analyzeSalesTrend(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        return Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    protected function analyzeLeadsTrend(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        return Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
            ])
            ->toArray();
    }

    protected function comparePeriods(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $daysDiff = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($daysDiff + 1);
        $previousEnd = $startDate->copy()->subDay();

        $currentSales = Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $previousSales = Sale::where('business_id', $business->id)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount');

        $change = $previousSales > 0
            ? round((($currentSales - $previousSales) / $previousSales) * 100, 2)
            : 0;

        return [
            'current_period' => (float) $currentSales,
            'previous_period' => (float) $previousSales,
            'change_percent' => $change,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }
}
