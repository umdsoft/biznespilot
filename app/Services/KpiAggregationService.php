<?php

namespace App\Services;

use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Models\KpiWeeklySummary;
use App\Models\KpiMonthlySummary;
use App\Models\KpiTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KpiAggregationService
{
    /**
     * Aggregate daily actuals into weekly summary
     */
    public function aggregateWeekly(
        int $businessId,
        string $kpiCode,
        string $weekStartDate
    ): ?KpiWeeklySummary {
        $business = Business::find($businessId);
        if (!$business) {
            Log::error("Business not found: $businessId");
            return null;
        }

        $template = KpiTemplate::where('kpi_code', $kpiCode)->first();
        if (!$template) {
            Log::error("KPI template not found: $kpiCode");
            return null;
        }

        $weekStart = Carbon::parse($weekStartDate)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        // Get or create weekly summary
        $weeklySummary = KpiWeeklySummary::firstOrNew([
            'business_id' => $businessId,
            'kpi_code' => $kpiCode,
            'week_start_date' => $weekStart,
        ]);

        // Set basic info
        $weeklySummary->week_end_date = $weekEnd;
        $weeklySummary->week_number = $weekStart->weekOfYear;
        $weeklySummary->year = $weekStart->year;
        $weeklySummary->week_label = "Week {$weekStart->weekOfYear}, {$weekStart->year}";
        $weeklySummary->unit = $template->default_unit;
        $weeklySummary->aggregation_method = $template->aggregation_method ?? 'sum';
        $weeklySummary->total_days = 7;

        // Get daily actuals
        $dailyActuals = KpiDailyActual::where('business_id', $businessId)
            ->where('kpi_code', $kpiCode)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->get();

        if ($dailyActuals->isEmpty()) {
            $weeklySummary->actual_value = 0;
            $weeklySummary->planned_value = 0;
            $weeklySummary->achievement_percentage = 0;
            $weeklySummary->status = 'grey';
            $weeklySummary->completed_days = 0;
            $weeklySummary->is_week_complete = false;
            $weeklySummary->save();
            return $weeklySummary;
        }

        // Aggregate from daily data
        $weeklySummary->aggregateFromDaily();

        // Find and link previous week
        $previousWeek = KpiWeeklySummary::where('business_id', $businessId)
            ->where('kpi_code', $kpiCode)
            ->where('week_start_date', '<', $weekStart)
            ->orderBy('week_start_date', 'desc')
            ->first();

        if ($previousWeek) {
            $weeklySummary->previous_week_id = $previousWeek->id;
        }

        $weeklySummary->save();

        return $weeklySummary;
    }

    /**
     * Aggregate weekly summaries into monthly summary
     */
    public function aggregateMonthly(
        int $businessId,
        string $kpiCode,
        int $year,
        int $month
    ): ?KpiMonthlySummary {
        $business = Business::find($businessId);
        if (!$business) {
            Log::error("Business not found: $businessId");
            return null;
        }

        $template = KpiTemplate::where('kpi_code', $kpiCode)->first();
        if (!$template) {
            Log::error("KPI template not found: $kpiCode");
            return null;
        }

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        // Get or create monthly summary
        $monthlySummary = KpiMonthlySummary::firstOrNew([
            'business_id' => $businessId,
            'kpi_code' => $kpiCode,
            'year' => $year,
            'month' => $month,
        ]);

        // Set basic info
        $monthlySummary->month_start_date = $monthStart;
        $monthlySummary->month_end_date = $monthEnd;
        $monthlySummary->month_label = $monthStart->format('F Y');
        $monthlySummary->unit = $template->default_unit;
        $monthlySummary->aggregation_method = $template->aggregation_method ?? 'sum';
        $monthlySummary->total_days = $monthStart->daysInMonth;

        // Get weekly summaries for this month
        $weeklySummaries = KpiWeeklySummary::where('business_id', $businessId)
            ->where('kpi_code', $kpiCode)
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('week_start_date', [$monthStart, $monthEnd])
                    ->orWhereBetween('week_end_date', [$monthStart, $monthEnd]);
            })
            ->orderBy('week_start_date')
            ->get();

        if ($weeklySummaries->isEmpty()) {
            // Try to aggregate from daily if no weekly summaries exist
            $dailyActuals = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $kpiCode)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get();

            if ($dailyActuals->isEmpty()) {
                $monthlySummary->actual_value = 0;
                $monthlySummary->planned_value = 0;
                $monthlySummary->achievement_percentage = 0;
                $monthlySummary->status = 'grey';
                $monthlySummary->completed_days = 0;
                $monthlySummary->is_month_complete = false;
                $monthlySummary->save();
                return $monthlySummary;
            }

            // Aggregate from daily
            $monthlySummary->aggregateFromDaily();
        } else {
            // Aggregate from weekly
            $monthlySummary->aggregateFromWeekly();
        }

        // Find and link previous month
        $previousMonth = KpiMonthlySummary::where('business_id', $businessId)
            ->where('kpi_code', $kpiCode)
            ->where(function ($query) use ($year, $month) {
                $query->where('year', '<', $year)
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->where('year', $year)->where('month', '<', $month);
                    });
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        if ($previousMonth) {
            $monthlySummary->previous_month_id = $previousMonth->id;
        }

        // Find same month last year
        $sameMonthLastYear = KpiMonthlySummary::where('business_id', $businessId)
            ->where('kpi_code', $kpiCode)
            ->where('year', $year - 1)
            ->where('month', $month)
            ->first();

        if ($sameMonthLastYear) {
            $monthlySummary->same_month_last_year_id = $sameMonthLastYear->id;
        }

        $monthlySummary->save();

        return $monthlySummary;
    }

    /**
     * Aggregate all KPIs for a business for a specific week
     */
    public function aggregateAllKpisWeekly(int $businessId, string $weekStartDate): array
    {
        $business = Business::find($businessId);
        if (!$business || !$business->kpiConfiguration) {
            return ['success' => false, 'message' => 'Business or KPI configuration not found'];
        }

        $kpiCodes = $business->kpiConfiguration->selected_kpis ?? [];
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($kpiCodes as $kpiCode) {
            try {
                $summary = $this->aggregateWeekly($businessId, $kpiCode, $weekStartDate);
                if ($summary) {
                    $results[$kpiCode] = [
                        'success' => true,
                        'summary_id' => $summary->id,
                        'actual_value' => $summary->actual_value,
                        'status' => $summary->status,
                    ];
                    $successCount++;
                } else {
                    $results[$kpiCode] = [
                        'success' => false,
                        'error' => 'Failed to create summary',
                    ];
                    $errorCount++;
                }
            } catch (\Exception $e) {
                Log::error("Error aggregating weekly KPI $kpiCode for business $businessId: " . $e->getMessage());
                $results[$kpiCode] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $errorCount++;
            }
        }

        return [
            'success' => true,
            'total' => count($kpiCodes),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results,
        ];
    }

    /**
     * Aggregate all KPIs for a business for a specific month
     */
    public function aggregateAllKpisMonthly(int $businessId, int $year, int $month): array
    {
        $business = Business::find($businessId);
        if (!$business || !$business->kpiConfiguration) {
            return ['success' => false, 'message' => 'Business or KPI configuration not found'];
        }

        $kpiCodes = $business->kpiConfiguration->selected_kpis ?? [];
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($kpiCodes as $kpiCode) {
            try {
                $summary = $this->aggregateMonthly($businessId, $kpiCode, $year, $month);
                if ($summary) {
                    $results[$kpiCode] = [
                        'success' => true,
                        'summary_id' => $summary->id,
                        'actual_value' => $summary->actual_value,
                        'status' => $summary->status,
                    ];
                    $successCount++;
                } else {
                    $results[$kpiCode] = [
                        'success' => false,
                        'error' => 'Failed to create summary',
                    ];
                    $errorCount++;
                }
            } catch (\Exception $e) {
                Log::error("Error aggregating monthly KPI $kpiCode for business $businessId: " . $e->getMessage());
                $results[$kpiCode] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $errorCount++;
            }
        }

        return [
            'success' => true,
            'total' => count($kpiCodes),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results,
        ];
    }

    /**
     * Recalculate all aggregations for a KPI (useful after data corrections)
     */
    public function recalculateKpi(int $businessId, string $kpiCode, Carbon $startDate, Carbon $endDate): array
    {
        $results = [
            'weekly' => [],
            'monthly' => [],
        ];

        // Recalculate weekly summaries
        $weekStart = $startDate->copy()->startOfWeek();
        while ($weekStart->lte($endDate)) {
            try {
                $summary = $this->aggregateWeekly($businessId, $kpiCode, $weekStart->format('Y-m-d'));
                $results['weekly'][] = [
                    'week_start' => $weekStart->format('Y-m-d'),
                    'success' => true,
                    'summary_id' => $summary?->id,
                ];
            } catch (\Exception $e) {
                $results['weekly'][] = [
                    'week_start' => $weekStart->format('Y-m-d'),
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
            $weekStart->addWeek();
        }

        // Recalculate monthly summaries
        $monthStart = $startDate->copy()->startOfMonth();
        while ($monthStart->lte($endDate)) {
            try {
                $summary = $this->aggregateMonthly(
                    $businessId,
                    $kpiCode,
                    $monthStart->year,
                    $monthStart->month
                );
                $results['monthly'][] = [
                    'year' => $monthStart->year,
                    'month' => $monthStart->month,
                    'success' => true,
                    'summary_id' => $summary?->id,
                ];
            } catch (\Exception $e) {
                $results['monthly'][] = [
                    'year' => $monthStart->year,
                    'month' => $monthStart->month,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
            $monthStart->addMonth();
        }

        return $results;
    }

    /**
     * Get aggregation status for a business
     */
    public function getAggregationStatus(int $businessId, Carbon $date): array
    {
        $business = Business::find($businessId);
        if (!$business || !$business->kpiConfiguration) {
            return ['success' => false, 'message' => 'Business or KPI configuration not found'];
        }

        $kpiCodes = $business->kpiConfiguration->selected_kpis ?? [];
        $weekStart = $date->copy()->startOfWeek();
        $year = $date->year;
        $month = $date->month;

        $status = [
            'business_id' => $businessId,
            'date' => $date->format('Y-m-d'),
            'week_start' => $weekStart->format('Y-m-d'),
            'year' => $year,
            'month' => $month,
            'kpis' => [],
        ];

        foreach ($kpiCodes as $kpiCode) {
            // Check daily data
            $dailyCount = KpiDailyActual::where('business_id', $businessId)
                ->where('kpi_code', $kpiCode)
                ->whereBetween('date', [$weekStart, $weekStart->copy()->addDays(6)])
                ->count();

            // Check weekly summary
            $weeklySummary = KpiWeeklySummary::where('business_id', $businessId)
                ->where('kpi_code', $kpiCode)
                ->where('week_start_date', $weekStart)
                ->first();

            // Check monthly summary
            $monthlySummary = KpiMonthlySummary::where('business_id', $businessId)
                ->where('kpi_code', $kpiCode)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            $status['kpis'][$kpiCode] = [
                'daily_count' => $dailyCount,
                'has_weekly_summary' => $weeklySummary !== null,
                'weekly_complete' => $weeklySummary?->is_week_complete ?? false,
                'weekly_calculated_at' => $weeklySummary?->calculated_at?->format('Y-m-d H:i:s'),
                'has_monthly_summary' => $monthlySummary !== null,
                'monthly_complete' => $monthlySummary?->is_month_complete ?? false,
                'monthly_calculated_at' => $monthlySummary?->calculated_at?->format('Y-m-d H:i:s'),
                'needs_weekly_aggregation' => $dailyCount > 0 && !$weeklySummary,
                'needs_monthly_aggregation' => $weeklySummary && !$monthlySummary,
            ];
        }

        return $status;
    }

    /**
     * Auto-aggregate missing summaries
     */
    public function autoAggregate(int $businessId, Carbon $upToDate): array
    {
        $results = [
            'weekly' => [],
            'monthly' => [],
        ];

        $business = Business::find($businessId);
        if (!$business || !$business->kpiConfiguration) {
            return [
                'success' => false,
                'message' => 'Business or KPI configuration not found',
            ];
        }

        // Find the earliest daily data
        $earliestDaily = KpiDailyActual::where('business_id', $businessId)
            ->orderBy('date')
            ->first();

        if (!$earliestDaily) {
            return [
                'success' => true,
                'message' => 'No daily data found',
                'weekly' => [],
                'monthly' => [],
            ];
        }

        $startDate = Carbon::parse($earliestDaily->date)->startOfWeek();
        $endDate = $upToDate->copy()->endOfWeek();

        // Aggregate all weeks
        $weekStart = $startDate->copy();
        while ($weekStart->lte($endDate)) {
            $weeklyResult = $this->aggregateAllKpisWeekly($businessId, $weekStart->format('Y-m-d'));
            $results['weekly'][] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'result' => $weeklyResult,
            ];
            $weekStart->addWeek();
        }

        // Aggregate all months
        $monthStart = $startDate->copy()->startOfMonth();
        $monthEnd = $upToDate->copy()->endOfMonth();

        while ($monthStart->lte($monthEnd)) {
            $monthlyResult = $this->aggregateAllKpisMonthly(
                $businessId,
                $monthStart->year,
                $monthStart->month
            );
            $results['monthly'][] = [
                'year' => $monthStart->year,
                'month' => $monthStart->month,
                'result' => $monthlyResult,
            ];
            $monthStart->addMonth();
        }

        return [
            'success' => true,
            'message' => 'Auto-aggregation completed',
            'weekly' => $results['weekly'],
            'monthly' => $results['monthly'],
        ];
    }

    /**
     * Get KPI trend analysis
     */
    public function getTrendAnalysis(int $businessId, string $kpiCode, int $weeks = 12): array
    {
        $weeklySummaries = KpiWeeklySummary::where('business_id', $businessId)
            ->where('kpi_code', $kpiCode)
            ->orderBy('week_start_date', 'desc')
            ->take($weeks)
            ->get()
            ->reverse();

        if ($weeklySummaries->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No weekly data available',
            ];
        }

        $values = $weeklySummaries->pluck('actual_value')->toArray();
        $weeks_data = $weeklySummaries->map(function ($summary) {
            return [
                'week_label' => $summary->week_label,
                'actual_value' => $summary->actual_value,
                'achievement_percentage' => $summary->achievement_percentage,
                'status' => $summary->status,
                'trend' => $summary->trend,
            ];
        })->toArray();

        // Calculate trend
        $firstHalf = array_slice($values, 0, (int)ceil(count($values) / 2));
        $secondHalf = array_slice($values, (int)floor(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $trendDirection = 'stable';
        $trendPercentage = 0;

        if ($firstAvg > 0) {
            $trendPercentage = (($secondAvg - $firstAvg) / $firstAvg) * 100;
            if ($trendPercentage > 10) {
                $trendDirection = 'improving';
            } elseif ($trendPercentage < -10) {
                $trendDirection = 'declining';
            }
        }

        // Calculate volatility
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function ($v) use ($mean) {
            return pow($v - $mean, 2);
        }, $values)) / count($values);
        $stdDev = sqrt($variance);
        $coefficientOfVariation = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        if ($coefficientOfVariation > 30) {
            $trendDirection = 'volatile';
        }

        return [
            'success' => true,
            'kpi_code' => $kpiCode,
            'weeks_analyzed' => count($values),
            'trend_direction' => $trendDirection,
            'trend_percentage' => round($trendPercentage, 2),
            'average_value' => round($mean, 2),
            'std_deviation' => round($stdDev, 2),
            'coefficient_of_variation' => round($coefficientOfVariation, 2),
            'min_value' => min($values),
            'max_value' => max($values),
            'weekly_data' => $weeks_data,
        ];
    }
}
