<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\KpiDailyActual;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * TrendAnalyzerService
 *
 * Analyzes trends in business metrics using statistical methods
 * - Simple Moving Average (SMA)
 * - Week over Week (WoW) comparison
 * - Month over Month (MoM) comparison
 * - Year over Year (YoY) comparison
 * - Z-Score anomaly detection
 */
class TrendAnalyzerService
{
    protected Business $business;

    // Trend direction constants
    public const TREND_UP = 'up';

    public const TREND_DOWN = 'down';

    public const TREND_STABLE = 'stable';

    // Trend strength constants
    public const STRENGTH_STRONG = 'strong';

    public const STRENGTH_MODERATE = 'moderate';

    public const STRENGTH_WEAK = 'weak';

    /**
     * Analyze all trends for a business
     */
    public function analyze(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $this->business = $business;

        // Get daily data for analysis
        $dailyData = $this->getDailyData($startDate, $endDate);

        if ($dailyData->isEmpty()) {
            return [
                'has_data' => false,
                'message' => 'Trend tahlili uchun yetarli ma\'lumot mavjud emas',
            ];
        }

        return [
            'has_data' => true,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'sales_trend' => $this->analyzeSalesTrend($dailyData),
            'revenue_trend' => $this->analyzeRevenueTrend($dailyData),
            'leads_trend' => $this->analyzeLeadsTrend($dailyData),
            'conversion_trend' => $this->analyzeConversionTrend($dailyData),
            'comparisons' => $this->calculateComparisons($startDate, $endDate),
            'anomalies' => $this->detectAnomalies($dailyData),
            'forecasts' => $this->generateForecasts($dailyData, $endDate),
        ];
    }

    /**
     * Get daily data for the period
     */
    protected function getDailyData(Carbon $startDate, Carbon $endDate): Collection
    {
        return KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Analyze sales trend
     */
    protected function analyzeSalesTrend(Collection $data): array
    {
        $values = $data->pluck('actual_new_sales')->toArray();

        return $this->analyzeTrend($values, 'Sotuvlar');
    }

    /**
     * Analyze revenue trend
     */
    protected function analyzeRevenueTrend(Collection $data): array
    {
        $values = $data->pluck('actual_revenue')->toArray();

        return $this->analyzeTrend($values, 'Daromad');
    }

    /**
     * Analyze leads trend
     */
    protected function analyzeLeadsTrend(Collection $data): array
    {
        $values = $data->pluck('actual_leads')->toArray();

        return $this->analyzeTrend($values, 'Lidlar');
    }

    /**
     * Analyze conversion trend
     */
    protected function analyzeConversionTrend(Collection $data): array
    {
        // Calculate daily conversion rates
        $conversions = $data->map(function ($day) {
            $leads = $day->actual_leads ?? 0;
            $sales = $day->actual_new_sales ?? 0;

            return $leads > 0 ? ($sales / $leads) * 100 : 0;
        })->toArray();

        return $this->analyzeTrend($conversions, 'Konversiya');
    }

    /**
     * Core trend analysis using SMA
     */
    protected function analyzeTrend(array $values, string $metricName): array
    {
        $count = count($values);

        if ($count < 3) {
            return [
                'metric' => $metricName,
                'direction' => self::TREND_STABLE,
                'strength' => self::STRENGTH_WEAK,
                'change_percent' => 0,
                'message' => 'Trend tahlili uchun yetarli kun mavjud emas',
            ];
        }

        // Calculate Simple Moving Average (3-day and 7-day)
        $sma3 = $this->calculateSMA($values, 3);
        $sma7 = $count >= 7 ? $this->calculateSMA($values, 7) : $sma3;

        // Get first and last SMA values for trend direction
        $firstSma = $sma3[0] ?? 0;
        $lastSma = $sma3[count($sma3) - 1] ?? 0;

        // Calculate percentage change
        $changePercent = $firstSma > 0
            ? (($lastSma - $firstSma) / $firstSma) * 100
            : 0;

        // Determine trend direction
        $direction = match (true) {
            $changePercent > 5 => self::TREND_UP,
            $changePercent < -5 => self::TREND_DOWN,
            default => self::TREND_STABLE,
        };

        // Determine trend strength
        $strength = match (true) {
            abs($changePercent) > 20 => self::STRENGTH_STRONG,
            abs($changePercent) > 10 => self::STRENGTH_MODERATE,
            default => self::STRENGTH_WEAK,
        };

        // Calculate statistics
        $stats = $this->calculateStatistics($values);

        return [
            'metric' => $metricName,
            'direction' => $direction,
            'direction_label' => $this->getDirectionLabel($direction),
            'strength' => $strength,
            'strength_label' => $this->getStrengthLabel($strength),
            'change_percent' => round($changePercent, 1),
            'statistics' => $stats,
            'sma_3day' => array_map(fn ($v) => round($v, 2), $sma3),
            'sma_7day' => array_map(fn ($v) => round($v, 2), $sma7),
            'daily_values' => $values,
        ];
    }

    /**
     * Calculate Simple Moving Average
     */
    protected function calculateSMA(array $values, int $period): array
    {
        $sma = [];
        $count = count($values);

        for ($i = $period - 1; $i < $count; $i++) {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $values[$i - $j];
            }
            $sma[] = $sum / $period;
        }

        return $sma;
    }

    /**
     * Calculate basic statistics
     */
    protected function calculateStatistics(array $values): array
    {
        if (empty($values)) {
            return ['mean' => 0, 'std' => 0, 'min' => 0, 'max' => 0];
        }

        $count = count($values);
        $mean = array_sum($values) / $count;

        // Calculate standard deviation
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $std = sqrt($variance / $count);

        return [
            'mean' => round($mean, 2),
            'std' => round($std, 2),
            'min' => min($values),
            'max' => max($values),
            'count' => $count,
        ];
    }

    /**
     * Calculate period comparisons (WoW, MoM, YoY)
     */
    protected function calculateComparisons(Carbon $startDate, Carbon $endDate): array
    {
        $periodDays = $startDate->diffInDays($endDate) + 1;

        // Current period totals
        $current = $this->getPeriodTotals($startDate, $endDate);

        // Week over Week
        $prevWeekStart = $startDate->copy()->subWeek();
        $prevWeekEnd = $endDate->copy()->subWeek();
        $prevWeek = $this->getPeriodTotals($prevWeekStart, $prevWeekEnd);
        $wow = $this->calculateComparison($current, $prevWeek);

        // Month over Month
        $prevMonthStart = $startDate->copy()->subMonth();
        $prevMonthEnd = $endDate->copy()->subMonth();
        $prevMonth = $this->getPeriodTotals($prevMonthStart, $prevMonthEnd);
        $mom = $this->calculateComparison($current, $prevMonth);

        // Year over Year
        $prevYearStart = $startDate->copy()->subYear();
        $prevYearEnd = $endDate->copy()->subYear();
        $prevYear = $this->getPeriodTotals($prevYearStart, $prevYearEnd);
        $yoy = $this->calculateComparison($current, $prevYear);

        return [
            'wow' => $wow,
            'mom' => $mom,
            'yoy' => $yoy,
        ];
    }

    /**
     * Get period totals
     */
    protected function getPeriodTotals(Carbon $startDate, Carbon $endDate): array
    {
        $data = KpiDailyActual::where('business_id', $this->business->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                SUM(actual_new_sales) as sales,
                SUM(actual_revenue) as revenue,
                SUM(actual_leads) as leads,
                SUM(actual_ad_costs) as ad_costs
            ')
            ->first();

        return [
            'sales' => (int) ($data->sales ?? 0),
            'revenue' => (float) ($data->revenue ?? 0),
            'leads' => (int) ($data->leads ?? 0),
            'ad_costs' => (float) ($data->ad_costs ?? 0),
        ];
    }

    /**
     * Calculate comparison between two periods
     */
    protected function calculateComparison(array $current, array $previous): array
    {
        $result = [];

        foreach (['sales', 'revenue', 'leads', 'ad_costs'] as $metric) {
            $curr = $current[$metric] ?? 0;
            $prev = $previous[$metric] ?? 0;

            $change = $curr - $prev;
            $changePercent = $prev > 0 ? (($curr - $prev) / $prev) * 100 : ($curr > 0 ? 100 : 0);

            $result[$metric] = [
                'current' => round($curr, 0),
                'previous' => round($prev, 0),
                'change' => round($change, 0),
                'change_percent' => round($changePercent, 1),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            ];
        }

        return $result;
    }

    /**
     * Detect anomalies using Z-Score
     */
    protected function detectAnomalies(Collection $data): array
    {
        $anomalies = [];

        // Define metrics to check
        $metrics = [
            'actual_new_sales' => 'Sotuvlar',
            'actual_revenue' => 'Daromad',
            'actual_leads' => 'Lidlar',
            'actual_ad_costs' => 'Reklama xarajatlari',
        ];

        foreach ($metrics as $field => $label) {
            $values = $data->pluck($field)->filter()->toArray();

            if (count($values) < 5) {
                continue;
            }

            $stats = $this->calculateStatistics($values);

            // Skip if no variation
            if ($stats['std'] == 0) {
                continue;
            }

            // Check each day for anomalies
            foreach ($data as $day) {
                $value = $day->$field;
                if ($value === null) {
                    continue;
                }

                // Calculate Z-Score
                $zScore = ($value - $stats['mean']) / $stats['std'];

                // Flag as anomaly if |Z| > 2 (outside ~95% of normal distribution)
                if (abs($zScore) > 2) {
                    $anomalies[] = [
                        'date' => $day->date->format('Y-m-d'),
                        'metric' => $label,
                        'metric_code' => $field,
                        'value' => $value,
                        'expected' => round($stats['mean'], 0),
                        'z_score' => round($zScore, 2),
                        'type' => $zScore > 0 ? 'spike' : 'drop',
                        'severity' => abs($zScore) > 3 ? 'high' : 'medium',
                        'message' => $this->getAnomalyMessage($label, $zScore, $value, $stats['mean']),
                    ];
                }
            }
        }

        // Sort by date
        usort($anomalies, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $anomalies;
    }

    /**
     * Generate simple forecasts
     */
    protected function generateForecasts(Collection $data, Carbon $endDate): array
    {
        // Use last 7 days average for simple forecast
        $last7Days = $data->sortByDesc('date')->take(7);

        if ($last7Days->count() < 3) {
            return [
                'available' => false,
                'message' => 'Prognoz uchun yetarli ma\'lumot mavjud emas',
            ];
        }

        $avgSales = $last7Days->avg('actual_new_sales');
        $avgRevenue = $last7Days->avg('actual_revenue');
        $avgLeads = $last7Days->avg('actual_leads');

        // Calculate trend factor
        $salesValues = $last7Days->pluck('actual_new_sales')->toArray();
        $salesTrend = $this->calculateTrendFactor(array_reverse($salesValues));

        // Forecast for next 7 days
        $nextWeekSales = round($avgSales * 7 * $salesTrend);
        $nextWeekRevenue = round($avgRevenue * 7 * $salesTrend);
        $nextWeekLeads = round($avgLeads * 7 * $salesTrend);

        // Forecast for rest of month
        $daysRemaining = $endDate->copy()->endOfMonth()->diffInDays($endDate);
        $restOfMonthSales = round($avgSales * $daysRemaining * $salesTrend);
        $restOfMonthRevenue = round($avgRevenue * $daysRemaining * $salesTrend);

        return [
            'available' => true,
            'method' => 'sma_7day_trend',
            'confidence' => 'moderate',
            'next_week' => [
                'sales' => $nextWeekSales,
                'revenue' => $nextWeekRevenue,
                'leads' => $nextWeekLeads,
            ],
            'rest_of_month' => [
                'days_remaining' => $daysRemaining,
                'sales' => $restOfMonthSales,
                'revenue' => $restOfMonthRevenue,
            ],
            'daily_average' => [
                'sales' => round($avgSales, 1),
                'revenue' => round($avgRevenue, 0),
                'leads' => round($avgLeads, 1),
            ],
        ];
    }

    /**
     * Calculate trend factor for forecasting
     */
    protected function calculateTrendFactor(array $values): float
    {
        if (count($values) < 3) {
            return 1.0;
        }

        // Simple linear regression slope
        $n = count($values);
        $sumX = ($n * ($n - 1)) / 2;
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $i * $values[$i];
            $sumXX += $i * $i;
        }

        $denominator = $n * $sumXX - $sumX * $sumX;
        if ($denominator == 0) {
            return 1.0;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $avgY = $sumY / $n;

        // Convert slope to growth factor
        if ($avgY == 0) {
            return 1.0;
        }

        $growthRate = $slope / $avgY;

        // Limit growth factor to reasonable range (0.5 to 1.5)
        return max(0.5, min(1.5, 1 + $growthRate));
    }

    /**
     * Get direction label in Uzbek
     */
    protected function getDirectionLabel(string $direction): string
    {
        return match ($direction) {
            self::TREND_UP => 'O\'sish',
            self::TREND_DOWN => 'Pasayish',
            self::TREND_STABLE => 'Barqaror',
            default => $direction,
        };
    }

    /**
     * Get strength label in Uzbek
     */
    protected function getStrengthLabel(string $strength): string
    {
        return match ($strength) {
            self::STRENGTH_STRONG => 'Kuchli',
            self::STRENGTH_MODERATE => 'O\'rtacha',
            self::STRENGTH_WEAK => 'Zaif',
            default => $strength,
        };
    }

    /**
     * Get anomaly message
     */
    protected function getAnomalyMessage(string $metric, float $zScore, float $value, float $mean): string
    {
        $direction = $zScore > 0 ? 'yuqori' : 'past';
        $percent = round(abs(($value - $mean) / $mean) * 100);

        return sprintf(
            '%s %d%% %s bo\'ldi (kutilgan: %s, haqiqiy: %s)',
            $metric,
            $percent,
            $direction,
            number_format($mean, 0, '.', ' '),
            number_format($value, 0, '.', ' ')
        );
    }
}
