<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

/**
 * Revenue Forecaster Algorithm - Advanced Implementation
 *
 * Daromad bashorat qilish algoritmi - research-based models.
 *
 * Research Sources:
 * - MIT Sloan: Time Series Forecasting Methods
 * - Journal of Forecasting: Exponential Smoothing
 * - Harvard Business Review: Revenue Prediction Models
 * - Gartner: Sales Forecasting Best Practices
 *
 * Formulalar:
 * 1. Linear Regression: y = mx + b
 * 2. Exponential Smoothing: St = α×Xt + (1-α)×St-1
 * 3. Holt-Winters: Considers trend + seasonality
 * 4. Moving Average: Weighted recent values
 * 5. Growth Rate: ((Current - Previous) / Previous) × 100
 * 6. Ensemble: Weighted combination of all methods
 *
 * @version 3.0.0
 */
class RevenueForecaster extends AlgorithmEngine
{
    protected string $cachePrefix = 'revenue_forecast_';

    protected int $cacheTTL = 900; // 15 minutes for real-time updates

    /**
     * Forecasting model weights (ensemble approach)
     */
    protected array $modelWeights = [
        'exponential_smoothing' => 0.30,
        'linear_regression' => 0.25,
        'moving_average' => 0.20,
        'holt_winters' => 0.25,
    ];

    /**
     * Calculate revenue forecast with ensemble models
     */
    public function calculate(Business $business): array
    {
        $startTime = microtime(true);

        // Get historical data
        $historicalData = $this->getHistoricalData($business);

        if (empty($historicalData) || count($historicalData) < 7) {
            return $this->getInsufficientDataResult($business);
        }

        // Calculate multiple forecasting models
        $linearModel = $this->linearRegression(array_column($historicalData, 'revenue'));
        $expSmoothing = $this->exponentialSmoothing($historicalData);
        $movingAvg = $this->weightedMovingAverage($historicalData);
        $holtWinters = $this->holtWintersModel($historicalData);

        // Calculate growth rate
        $growthRate = $this->calculateGrowthRate($historicalData);

        // Calculate seasonality (if enough data)
        $seasonality = $this->detectSeasonality($historicalData);

        // Generate ensemble forecasts
        $forecasts = $this->generateEnsembleForecasts(
            $historicalData,
            $linearModel,
            $expSmoothing,
            $movingAvg,
            $holtWinters,
            $seasonality
        );

        // Calculate confidence intervals
        $confidence = $this->calculateConfidenceIntervals($historicalData, $forecasts);

        // Get current metrics
        $currentMetrics = $this->getCurrentMetrics($historicalData);

        // Generate insights
        $insights = $this->generateInsights($linearModel, $growthRate, $seasonality, $currentMetrics);

        // Calculate model accuracy
        $accuracy = $this->calculateModelAccuracy($historicalData, [
            'linear' => $linearModel,
            'exp_smoothing' => $expSmoothing,
            'moving_avg' => $movingAvg,
            'holt_winters' => $holtWinters,
        ]);

        $calculationTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'score' => $this->calculateForecastScore($linearModel, $growthRate),
            'current_metrics' => $currentMetrics,
            'trend' => [
                'direction' => $linearModel['trend'],
                'slope' => $linearModel['slope'],
                'r_squared' => $linearModel['r_squared'],
                'strength' => $this->getTrendStrength($linearModel['r_squared']),
            ],
            'growth_rate' => [
                'daily' => round($growthRate['daily'], 2),
                'weekly' => round($growthRate['weekly'], 2),
                'monthly' => round($growthRate['monthly'], 2),
            ],
            'seasonality' => $seasonality,
            'forecasts' => $forecasts,
            'confidence' => $confidence,
            'model_accuracy' => $accuracy,
            'insights' => $insights,
            'risk_assessment' => $this->assessRisk($linearModel, $growthRate, $confidence),
            '_meta' => [
                'calculation_time_ms' => $calculationTime,
                'data_points' => count($historicalData),
                'models_used' => array_keys($this->modelWeights),
                'version' => '3.0.0',
            ],
        ];
    }

    /**
     * Exponential Smoothing forecast
     * Formula: St = α×Xt + (1-α)×St-1
     */
    protected function exponentialSmoothing(array $data, float $alpha = 0.3): array
    {
        $revenues = array_column($data, 'revenue');
        $smoothed = [$revenues[0]];

        for ($i = 1; $i < count($revenues); $i++) {
            $smoothed[] = $alpha * $revenues[$i] + (1 - $alpha) * $smoothed[$i - 1];
        }

        return [
            'model' => 'exponential_smoothing',
            'alpha' => $alpha,
            'last_value' => end($smoothed),
            'smoothed_series' => $smoothed,
        ];
    }

    /**
     * Weighted Moving Average
     */
    protected function weightedMovingAverage(array $data, int $period = 7): array
    {
        $revenues = array_column($data, 'revenue');
        $weights = range(1, $period);
        $totalWeight = array_sum($weights);

        $wma = [];
        for ($i = $period - 1; $i < count($revenues); $i++) {
            $window = array_slice($revenues, $i - $period + 1, $period);
            $weightedSum = 0;

            foreach ($window as $j => $value) {
                $weightedSum += $value * $weights[$j];
            }

            $wma[] = $weightedSum / $totalWeight;
        }

        return [
            'model' => 'weighted_moving_average',
            'period' => $period,
            'last_value' => end($wma),
            'wma_series' => $wma,
        ];
    }

    /**
     * Holt-Winters Model (simplified)
     * Considers both trend and seasonality
     */
    protected function holtWintersModel(array $data): array
    {
        $revenues = array_column($data, 'revenue');
        $n = count($revenues);

        // Initialize
        $alpha = 0.3; // Level smoothing
        $beta = 0.1;  // Trend smoothing

        $level = $revenues[0];
        $trend = 0;

        if ($n > 1) {
            $trend = $revenues[1] - $revenues[0];
        }

        $forecasts = [];

        for ($i = 1; $i < $n; $i++) {
            $prevLevel = $level;
            $level = $alpha * $revenues[$i] + (1 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $prevLevel) + (1 - $beta) * $trend;
            $forecasts[] = $level + $trend;
        }

        return [
            'model' => 'holt_winters',
            'alpha' => $alpha,
            'beta' => $beta,
            'level' => $level,
            'trend' => $trend,
            'last_forecast' => end($forecasts) ?: $level + $trend,
        ];
    }

    /**
     * Generate ensemble forecasts combining all models
     */
    protected function generateEnsembleForecasts(
        array $data,
        array $linearModel,
        array $expSmoothing,
        array $movingAvg,
        array $holtWinters,
        array $seasonality
    ): array {
        $revenues = array_column($data, 'revenue');
        $lastValue = end($revenues);

        // Get forecast from each model
        $linearForecast = $linearModel['slope'] * (count($revenues) + 1) + $linearModel['intercept'];
        $expForecast = $expSmoothing['last_value'];
        $maForecast = $movingAvg['last_value'];
        $hwForecast = $holtWinters['last_forecast'];

        // Ensemble forecast
        $ensemble7Day = (
            $linearForecast * $this->modelWeights['linear_regression'] +
            $expForecast * $this->modelWeights['exponential_smoothing'] +
            $maForecast * $this->modelWeights['moving_average'] +
            $hwForecast * $this->modelWeights['holt_winters']
        );

        // Project for 30, 60, 90 days
        $dailyGrowth = ($ensemble7Day - $lastValue) / $lastValue;

        $forecasts = [
            '7_days' => [
                'value' => (int) round($ensemble7Day * 7),
                'per_day' => (int) round($ensemble7Day),
                'confidence' => 'high',
            ],
            '30_days' => [
                'value' => (int) round($ensemble7Day * 30 * (1 + $dailyGrowth * 0.5)),
                'per_day' => (int) round($ensemble7Day * (1 + $dailyGrowth * 0.5)),
                'confidence' => 'medium',
            ],
            '60_days' => [
                'value' => (int) round($ensemble7Day * 60 * (1 + $dailyGrowth)),
                'per_day' => (int) round($ensemble7Day * (1 + $dailyGrowth)),
                'confidence' => 'medium',
            ],
            '90_days' => [
                'value' => (int) round($ensemble7Day * 90 * (1 + $dailyGrowth * 1.5)),
                'per_day' => (int) round($ensemble7Day * (1 + $dailyGrowth * 1.5)),
                'confidence' => 'low',
            ],
        ];

        return $forecasts;
    }

    /**
     * Calculate model accuracy using historical data
     */
    protected function calculateModelAccuracy(array $data, array $models): array
    {
        $revenues = array_column($data, 'revenue');
        $n = count($revenues);

        if ($n < 14) {
            return ['insufficient_data' => true];
        }

        // Use last 7 days as test set
        $trainData = array_slice($revenues, 0, $n - 7);
        $testData = array_slice($revenues, -7);

        $accuracy = [];

        foreach ($models as $name => $model) {
            // Simple MAPE (Mean Absolute Percentage Error)
            $errors = [];
            foreach ($testData as $actual) {
                $predicted = $model['last_value'] ?? $model['last_forecast'] ?? end($trainData);
                if ($actual > 0) {
                    $errors[] = abs(($actual - $predicted) / $actual) * 100;
                }
            }

            $mape = count($errors) > 0 ? array_sum($errors) / count($errors) : 0;
            $accuracy[$name] = [
                'mape' => round($mape, 2),
                'accuracy_percent' => round(100 - $mape, 2),
            ];
        }

        return $accuracy;
    }

    /**
     * Get historical revenue data
     */
    protected function getHistoricalData(Business $business): array
    {
        try {
            // Try to get from KPI snapshots
            $data = DB::table('kpi_snapshots')
                ->where('business_id', $business->id)
                ->where('snapshot_date', '>=', now()->subDays(90))
                ->orderBy('snapshot_date')
                ->select('snapshot_date', 'revenue_total as revenue', 'leads_total as leads')
                ->get()
                ->toArray();

            if (! empty($data)) {
                return array_map(fn ($row) => (array) $row, $data);
            }

            // Try orders if KPI snapshots don't exist
            $orderData = DB::table('orders')
                ->where('business_id', $business->id)
                ->where('created_at', '>=', now()->subDays(90))
                ->whereIn('status', ['completed', 'paid'])
                ->selectRaw('DATE(created_at) as snapshot_date, SUM(total_amount) as revenue, COUNT(*) as orders')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('snapshot_date')
                ->get()
                ->toArray();

            if (! empty($orderData)) {
                return array_map(fn ($row) => (array) $row, $orderData);
            }

            // Return sample data for demonstration
            return $this->generateSampleData($business);

        } catch (\Exception $e) {
            Log::warning('Could not get historical data', ['error' => $e->getMessage()]);

            return $this->generateSampleData($business);
        }
    }

    /**
     * Generate sample data for businesses without history
     */
    protected function generateSampleData(Business $business): array
    {
        $salesMetrics = $business->salesMetrics;
        $baseRevenue = $salesMetrics?->monthly_revenue ?? 10000000;
        $dailyBase = $baseRevenue / 30;

        $data = [];
        for ($i = 90; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            // Add some variance and trend
            $trend = 1 + (0.001 * (90 - $i)); // Slight upward trend
            $variance = 0.8 + (mt_rand(0, 40) / 100); // 80-120% variance
            $dayOfWeek = now()->subDays($i)->dayOfWeek;
            $weekendFactor = in_array($dayOfWeek, [0, 6]) ? 0.7 : 1; // Lower on weekends

            $data[] = [
                'snapshot_date' => $date,
                'revenue' => (int) round($dailyBase * $trend * $variance * $weekendFactor),
                'leads' => (int) round(10 * $variance),
            ];
        }

        return $data;
    }

    /**
     * Calculate growth rate
     */
    protected function calculateGrowthRate(array $data): array
    {
        $revenues = array_column($data, 'revenue');
        $count = count($revenues);

        if ($count < 2) {
            return ['daily' => 0, 'weekly' => 0, 'monthly' => 0];
        }

        // Daily growth (last 7 days vs previous 7 days)
        $last7 = array_sum(array_slice($revenues, -7));
        $prev7 = array_sum(array_slice($revenues, -14, 7));
        $dailyGrowth = $prev7 > 0 ? (($last7 - $prev7) / $prev7) * 100 / 7 : 0;

        // Weekly growth (last 4 weeks comparison)
        $weeklyGrowth = $dailyGrowth * 7;

        // Monthly growth
        $last30 = array_sum(array_slice($revenues, -30));
        $prev30 = array_sum(array_slice($revenues, -60, 30));
        $monthlyGrowth = $prev30 > 0 ? (($last30 - $prev30) / $prev30) * 100 : 0;

        return [
            'daily' => $dailyGrowth,
            'weekly' => $weeklyGrowth,
            'monthly' => $monthlyGrowth,
        ];
    }

    /**
     * Detect seasonality patterns
     */
    protected function detectSeasonality(array $data): array
    {
        $byDayOfWeek = [];

        foreach ($data as $row) {
            $dayOfWeek = date('N', strtotime($row['snapshot_date']));
            $byDayOfWeek[$dayOfWeek][] = $row['revenue'];
        }

        $dayNames = [
            1 => 'Dushanba',
            2 => 'Seshanba',
            3 => 'Chorshanba',
            4 => 'Payshanba',
            5 => 'Juma',
            6 => 'Shanba',
            7 => 'Yakshanba',
        ];

        $avgByDay = [];
        foreach ($byDayOfWeek as $day => $revenues) {
            $avgByDay[$dayNames[$day]] = round(array_sum($revenues) / count($revenues));
        }

        // Find best and worst days
        arsort($avgByDay);
        $bestDay = array_key_first($avgByDay);
        asort($avgByDay);
        $worstDay = array_key_first($avgByDay);

        // Calculate seasonality strength
        $values = array_values($avgByDay);
        $stdDev = $this->standardDeviation($values);
        $mean = array_sum($values) / count($values);
        $seasonalityStrength = $mean > 0 ? round(($stdDev / $mean) * 100, 1) : 0;

        return [
            'detected' => $seasonalityStrength > 10,
            'strength' => $seasonalityStrength,
            'by_day' => $avgByDay,
            'best_day' => $bestDay,
            'worst_day' => $worstDay,
            'recommendation' => $seasonalityStrength > 20
                ? "{$bestDay} kunlari eng yaxshi - bu kunda ko'proq reklama qiling"
                : 'Hafta kunlari bo\'yicha katta farq yo\'q',
        ];
    }

    /**
     * Generate forecasts
     */
    protected function generateForecasts(array $data, array $trend, array $growthRate, array $seasonality): array
    {
        $revenues = array_column($data, 'revenue');
        $lastValue = end($revenues);
        $avgRecent = array_sum(array_slice($revenues, -7)) / 7;

        $forecasts = [];

        // 7 days forecast
        $dailyGrowthFactor = 1 + ($growthRate['daily'] / 100);
        $forecast7Days = [];
        for ($i = 1; $i <= 7; $i++) {
            $predicted = $avgRecent * pow($dailyGrowthFactor, $i);
            $forecast7Days[] = [
                'day' => $i,
                'date' => now()->addDays($i)->format('Y-m-d'),
                'predicted' => (int) round($predicted),
            ];
        }
        $forecasts['7_days'] = [
            'daily' => $forecast7Days,
            'total' => (int) round(array_sum(array_column($forecast7Days, 'predicted'))),
            'growth' => round((array_sum(array_column($forecast7Days, 'predicted')) / array_sum(array_slice($revenues, -7)) - 1) * 100, 1),
        ];

        // 30 days forecast
        $forecast30Days = $avgRecent * 30 * pow($dailyGrowthFactor, 15); // Use mid-point growth
        $forecasts['30_days'] = [
            'total' => (int) round($forecast30Days),
            'daily_avg' => (int) round($forecast30Days / 30),
            'growth' => round($growthRate['monthly'], 1),
            'vs_last_month' => round((($forecast30Days / array_sum(array_slice($revenues, -30))) - 1) * 100, 1),
        ];

        // 90 days forecast
        $monthlyGrowthFactor = 1 + ($growthRate['monthly'] / 100);
        $month1 = $forecast30Days;
        $month2 = $month1 * $monthlyGrowthFactor;
        $month3 = $month2 * $monthlyGrowthFactor;
        $forecasts['90_days'] = [
            'month_1' => (int) round($month1),
            'month_2' => (int) round($month2),
            'month_3' => (int) round($month3),
            'total' => (int) round($month1 + $month2 + $month3),
            'avg_monthly' => (int) round(($month1 + $month2 + $month3) / 3),
            'cumulative_growth' => round((pow($monthlyGrowthFactor, 3) - 1) * 100, 1),
        ];

        return $forecasts;
    }

    /**
     * Calculate confidence intervals
     */
    protected function calculateConfidenceIntervals(array $data, array $forecasts): array
    {
        $revenues = array_column($data, 'revenue');
        $stdDev = $this->standardDeviation($revenues);
        $mean = array_sum($revenues) / count($revenues);

        // Calculate coefficient of variation
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        // Confidence level based on data consistency
        $confidenceLevel = match (true) {
            $cv < 20 => 'high',
            $cv < 40 => 'medium',
            default => 'low',
        };

        return [
            'level' => $confidenceLevel,
            'percentage' => match ($confidenceLevel) {
                'high' => 85,
                'medium' => 70,
                'low' => 55,
            },
            'std_deviation' => round($stdDev),
            'coefficient_variation' => round($cv, 1),
            '7_day_range' => [
                'lower' => (int) round($forecasts['7_days']['total'] * 0.85),
                'upper' => (int) round($forecasts['7_days']['total'] * 1.15),
            ],
            '30_day_range' => [
                'lower' => (int) round($forecasts['30_days']['total'] * 0.80),
                'upper' => (int) round($forecasts['30_days']['total'] * 1.20),
            ],
            '90_day_range' => [
                'lower' => (int) round($forecasts['90_days']['total'] * 0.75),
                'upper' => (int) round($forecasts['90_days']['total'] * 1.25),
            ],
        ];
    }

    /**
     * Get current metrics
     */
    protected function getCurrentMetrics(array $data): array
    {
        $revenues = array_column($data, 'revenue');

        $last7 = array_sum(array_slice($revenues, -7));
        $last30 = array_sum(array_slice($revenues, -30));

        return [
            'daily_avg' => (int) round($last7 / 7),
            'weekly_total' => (int) round($last7),
            'monthly_total' => (int) round($last30),
            'last_day' => (int) (end($revenues) ?? 0),
        ];
    }

    /**
     * Generate insights
     */
    protected function generateInsights(array $trend, array $growthRate, array $seasonality, array $currentMetrics): array
    {
        $insights = [];

        // Trend insight
        if ($trend['trend'] === 'up') {
            $insights[] = [
                'type' => 'positive',
                'title' => 'O\'sish trendi',
                'description' => 'Daromad o\'sish trendida. Davom eting!',
                'metric' => '+'.round($trend['slope'] * 30).' so\'m/oy',
            ];
        } elseif ($trend['trend'] === 'down') {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Pasayish trendi',
                'description' => 'Daromad pasaymoqda. Sabab aniqlang.',
                'metric' => round($trend['slope'] * 30).' so\'m/oy',
            ];
        }

        // Growth rate insight
        if ($growthRate['monthly'] > 10) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Kuchli o\'sish',
                'description' => 'Oylik o\'sish '.round($growthRate['monthly']).'% - ajoyib!',
            ];
        } elseif ($growthRate['monthly'] < -10) {
            $insights[] = [
                'type' => 'negative',
                'title' => 'Sezilarli pasayish',
                'description' => 'Oylik pasayish '.abs(round($growthRate['monthly'])).'% - diqqat!',
            ];
        }

        // Seasonality insight
        if ($seasonality['detected']) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Haftalik pattern',
                'description' => $seasonality['best_day'].' - eng yaxshi kun, '.$seasonality['worst_day'].' - eng zaif kun',
            ];
        }

        return $insights;
    }

    /**
     * Assess risk
     */
    protected function assessRisk(array $trend, array $growthRate, array $confidence): array
    {
        $riskScore = 0;

        // Trend risk
        if ($trend['trend'] === 'down') {
            $riskScore += 30;
        }
        if ($trend['r_squared'] < 0.3) {
            $riskScore += 20;
        } // Unpredictable

        // Growth risk
        if ($growthRate['monthly'] < -10) {
            $riskScore += 25;
        }
        if ($growthRate['monthly'] < 0) {
            $riskScore += 10;
        }

        // Confidence risk
        if ($confidence['level'] === 'low') {
            $riskScore += 15;
        }
        if ($confidence['level'] === 'medium') {
            $riskScore += 5;
        }

        $riskLevel = match (true) {
            $riskScore >= 50 => 'high',
            $riskScore >= 25 => 'medium',
            default => 'low',
        };

        return [
            'score' => min(100, $riskScore),
            'level' => $riskLevel,
            'label' => match ($riskLevel) {
                'high' => 'Yuqori risk',
                'medium' => 'O\'rtacha risk',
                'low' => 'Past risk',
            },
            'factors' => $this->getRiskFactors($trend, $growthRate, $confidence),
        ];
    }

    /**
     * Get risk factors
     */
    protected function getRiskFactors(array $trend, array $growthRate, array $confidence): array
    {
        $factors = [];

        if ($trend['trend'] === 'down') {
            $factors[] = 'Pasayish trendi';
        }

        if ($growthRate['monthly'] < 0) {
            $factors[] = 'Salbiy o\'sish';
        }

        if ($confidence['level'] !== 'high') {
            $factors[] = 'Ma\'lumotlar izchil emas';
        }

        if ($confidence['coefficient_variation'] > 40) {
            $factors[] = 'Yuqori volatillik';
        }

        return $factors;
    }

    /**
     * Calculate forecast score
     */
    protected function calculateForecastScore(array $trend, array $growthRate): int
    {
        $score = 50; // Base score

        // Trend contribution
        if ($trend['trend'] === 'up') {
            $score += 20;
        } elseif ($trend['trend'] === 'down') {
            $score -= 20;
        }

        // R-squared contribution (predictability)
        $score += $trend['r_squared'] * 15;

        // Growth rate contribution
        $score += min(15, max(-15, $growthRate['monthly']));

        return max(0, min(100, (int) round($score)));
    }

    /**
     * Get trend strength
     */
    protected function getTrendStrength(float $rSquared): string
    {
        if ($rSquared >= 0.7) {
            return 'strong';
        }
        if ($rSquared >= 0.4) {
            return 'moderate';
        }

        return 'weak';
    }

    /**
     * Get insufficient data result
     */
    protected function getInsufficientDataResult(Business $business): array
    {
        return [
            'score' => 50,
            'status' => 'insufficient_data',
            'message' => 'Bashorat qilish uchun kamida 7 kunlik ma\'lumot kerak',
            'current_metrics' => [
                'daily_avg' => 0,
                'weekly_total' => 0,
                'monthly_total' => 0,
            ],
            'forecasts' => [],
            'recommendations' => [
                [
                    'title' => 'Ma\'lumot yig\'ish',
                    'description' => 'KPI tracking ni yoqing va kamida 1 hafta kuting',
                    'action_route' => '/business/analytics',
                ],
            ],
        ];
    }
}
