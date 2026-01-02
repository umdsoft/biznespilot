<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Anomaly Detection Algorithm
 *
 * Detects unusual patterns, outliers, and anomalies in business metrics
 * using statistical methods (no AI required).
 *
 * Algorithms Used:
 * 1. Z-Score Analysis - Standard deviation based outlier detection
 * 2. Statistical Process Control (SPC) - Control charts with UCL/LCL
 * 3. Interquartile Range (IQR) Method - Box plot outliers
 * 4. Moving Average Deviation - Trend-based anomalies
 *
 * Research:
 * - Shewhart (1931) - Statistical Process Control
 * - Tukey (1977) - Exploratory Data Analysis, IQR method
 * - Grubbs (1969) - Procedures for detecting outlying observations
 * - NIST Engineering Statistics Handbook (2024)
 *
 * Use Cases:
 * - Sudden engagement drops
 * - Unusual revenue spikes/drops
 * - Follower count anomalies
 * - Response time outliers
 * - Conversion rate deviations
 *
 * @version 1.0.0
 * @package App\Services\Algorithm
 */
class AnomalyDetectionAlgorithm extends AlgorithmEngine
{
    /**
     * Algorithm version
     */
    protected string $version = '1.0.0';

    /**
     * Cache TTL (15 minutes - real-time monitoring)
     */
    protected int $cacheTTL = 900;

    /**
     * Z-score thresholds
     */
    protected const Z_THRESHOLD_CRITICAL = 3.0;  // 99.7% confidence
    protected const Z_THRESHOLD_WARNING = 2.0;   // 95% confidence
    protected const Z_THRESHOLD_NOTABLE = 1.5;   // 86.6% confidence

    /**
     * IQR multiplier for outlier detection
     */
    protected const IQR_MULTIPLIER = 1.5;

    /**
     * Minimum data points required for analysis
     */
    protected const MIN_DATA_POINTS = 10;

    /**
     * Metrics to monitor
     */
    protected array $metricsToMonitor = [
        'engagement_rate' => ['type' => 'percentage', 'direction' => 'both'],
        'follower_count' => ['type' => 'count', 'direction' => 'both'],
        'revenue' => ['type' => 'amount', 'direction' => 'both'],
        'conversion_rate' => ['type' => 'percentage', 'direction' => 'both'],
        'response_time' => ['type' => 'duration', 'direction' => 'increase'],
        'churn_rate' => ['type' => 'percentage', 'direction' => 'increase'],
    ];

    /**
     * Analyze business metrics for anomalies
     *
     * @param Business $business Business to analyze
     * @param array $options Additional options
     * @return array Detected anomalies and analysis
     */
    public function analyze(Business $business, array $options = []): array
    {
        try {
            $startTime = microtime(true);

            // Collect time series data for various metrics
            $timeSeriesData = $this->collectTimeSeriesData($business, $options);

            // Detect anomalies using multiple methods
            $anomalies = [];
            foreach ($timeSeriesData as $metric => $data) {
                if (count($data) >= self::MIN_DATA_POINTS) {
                    $anomalies[$metric] = $this->detectAnomalies($metric, $data);
                }
            }

            // Calculate overall anomaly score
            $overallScore = $this->calculateAnomalyScore($anomalies);

            // Generate alerts
            $alerts = $this->generateAlerts($anomalies);

            // Create recommendations
            $recommendations = $this->generateRecommendations($anomalies, $business);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => $this->version,
                'anomaly_score' => $overallScore,
                'status' => $this->getStatusLevel($overallScore),
                'anomalies' => $anomalies,
                'alerts' => $alerts,
                'recommendations' => $recommendations,
                'metadata' => [
                    'calculated_at' => Carbon::now()->toIso8601String(),
                    'execution_time_ms' => $executionTime,
                    'business_id' => $business->id,
                    'metrics_analyzed' => count($timeSeriesData),
                    'anomalies_found' => count($alerts),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('AnomalyDetectionAlgorithm failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => $this->version,
            ];
        }
    }

    /**
     * Collect time series data for metrics
     *
     * @param Business $business
     * @param array $options
     * @return array Time series data by metric
     */
    protected function collectTimeSeriesData(Business $business, array $options = []): array
    {
        $data = [];

        // Get engagement rate data
        $instagramAccounts = $business->instagramAccounts ?? collect();
        $engagementRates = [];
        $followerCounts = [];

        foreach ($instagramAccounts as $account) {
            $posts = $account->posts ?? collect();

            foreach ($posts as $post) {
                if (!empty($post->engagement_rate)) {
                    $engagementRates[] = [
                        'value' => $post->engagement_rate,
                        'timestamp' => $post->posted_at ?? now(),
                    ];
                }
            }

            // Follower growth tracking
            if (!empty($account->followers_count)) {
                $followerCounts[] = [
                    'value' => $account->followers_count,
                    'timestamp' => $account->updated_at ?? now(),
                ];
            }
        }

        if (!empty($engagementRates)) {
            $data['engagement_rate'] = $engagementRates;
        }

        if (!empty($followerCounts)) {
            $data['follower_count'] = $followerCounts;
        }

        // Get revenue data
        $sales = $business->sales ?? collect();
        $revenueData = [];
        $conversionData = [];

        foreach ($sales as $sale) {
            if (!empty($sale->amount)) {
                $revenueData[] = [
                    'value' => $sale->amount,
                    'timestamp' => $sale->created_at ?? now(),
                ];
            }

            if (!empty($sale->conversion_rate)) {
                $conversionData[] = [
                    'value' => $sale->conversion_rate,
                    'timestamp' => $sale->created_at ?? now(),
                ];
            }
        }

        if (!empty($revenueData)) {
            $data['revenue'] = $revenueData;
        }

        if (!empty($conversionData)) {
            $data['conversion_rate'] = $conversionData;
        }

        // If no real data, generate synthetic data for demo
        if (empty($data) && ($options['use_synthetic'] ?? true)) {
            $data = $this->generateSyntheticData();
        }

        return $data;
    }

    /**
     * Detect anomalies in a metric's time series data
     *
     * Uses multiple detection methods for robust analysis
     *
     * @param string $metric Metric name
     * @param array $data Time series data
     * @return array Detected anomalies
     */
    protected function detectAnomalies(string $metric, array $data): array
    {
        $values = array_column($data, 'value');

        // Calculate statistics
        $stats = $this->calculateStatistics($values);

        // Method 1: Z-Score Analysis
        $zScoreAnomalies = $this->detectZScoreAnomalies($data, $stats);

        // Method 2: IQR Method
        $iqrAnomalies = $this->detectIQRAnomalies($data, $stats);

        // Method 3: Control Charts (SPC)
        $spcAnomalies = $this->detectSPCAnomalies($data, $stats);

        // Method 4: Moving Average Deviation
        $maAnomalies = $this->detectMovingAverageAnomalies($data, $stats);

        // Combine anomalies (union of all methods)
        $allAnomalies = array_merge(
            $zScoreAnomalies,
            $iqrAnomalies,
            $spcAnomalies,
            $maAnomalies
        );

        // Remove duplicates and sort by severity
        $uniqueAnomalies = $this->deduplicateAnomalies($allAnomalies);

        return [
            'metric' => $metric,
            'statistics' => $stats,
            'anomalies' => $uniqueAnomalies,
            'anomaly_count' => count($uniqueAnomalies),
            'detection_methods' => [
                'z_score' => count($zScoreAnomalies),
                'iqr' => count($iqrAnomalies),
                'spc' => count($spcAnomalies),
                'moving_average' => count($maAnomalies),
            ],
        ];
    }

    /**
     * Calculate statistical measures
     *
     * @param array $values Array of numeric values
     * @return array Statistical measures
     */
    protected function calculateStatistics(array $values): array
    {
        $count = count($values);
        $mean = array_sum($values) / $count;

        // Standard deviation
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= $count;
        $stdDev = sqrt($variance);

        // Quartiles
        sort($values);
        $q1 = $this->calculatePercentile($values, 25);
        $q2 = $this->calculatePercentile($values, 50); // Median
        $q3 = $this->calculatePercentile($values, 75);
        $iqr = $q3 - $q1;

        // Min/Max
        $min = min($values);
        $max = max($values);

        return [
            'count' => $count,
            'mean' => round($mean, 2),
            'median' => round($q2, 2),
            'std_dev' => round($stdDev, 2),
            'variance' => round($variance, 2),
            'q1' => round($q1, 2),
            'q2' => round($q2, 2),
            'q3' => round($q3, 2),
            'iqr' => round($iqr, 2),
            'min' => round($min, 2),
            'max' => round($max, 2),
            'range' => round($max - $min, 2),
        ];
    }

    /**
     * Calculate percentile value
     *
     * @param array $sortedValues Sorted array of values
     * @param float $percentile Percentile (0-100)
     * @return float Percentile value
     */
    protected function calculatePercentile(array $sortedValues, float $percentile): float
    {
        $count = count($sortedValues);
        $index = ($percentile / 100) * ($count - 1);

        $lower = floor($index);
        $upper = ceil($index);

        if ($lower == $upper) {
            return $sortedValues[$lower];
        }

        // Linear interpolation
        $weight = $index - $lower;
        return $sortedValues[$lower] * (1 - $weight) + $sortedValues[$upper] * $weight;
    }

    /**
     * Detect anomalies using Z-Score method
     *
     * Z = (X - μ) / σ
     * Z > 3: Critical anomaly (99.7% confidence)
     * Z > 2: Warning (95% confidence)
     *
     * @param array $data Time series data
     * @param array $stats Statistics
     * @return array Anomalies detected
     */
    protected function detectZScoreAnomalies(array $data, array $stats): array
    {
        $anomalies = [];
        $mean = $stats['mean'];
        $stdDev = $stats['std_dev'];

        if ($stdDev == 0) {
            return []; // No variation, no anomalies
        }

        foreach ($data as $point) {
            $value = $point['value'];
            $zScore = abs(($value - $mean) / $stdDev);

            $severity = null;
            if ($zScore >= self::Z_THRESHOLD_CRITICAL) {
                $severity = 'critical';
            } elseif ($zScore >= self::Z_THRESHOLD_WARNING) {
                $severity = 'warning';
            } elseif ($zScore >= self::Z_THRESHOLD_NOTABLE) {
                $severity = 'notable';
            }

            if ($severity) {
                $anomalies[] = [
                    'value' => $value,
                    'timestamp' => $point['timestamp'],
                    'method' => 'z_score',
                    'severity' => $severity,
                    'z_score' => round($zScore, 2),
                    'deviation' => round(abs($value - $mean), 2),
                    'direction' => $value > $mean ? 'above' : 'below',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect anomalies using IQR (Interquartile Range) method
     *
     * Lower Fence = Q1 - 1.5 × IQR
     * Upper Fence = Q3 + 1.5 × IQR
     *
     * @param array $data Time series data
     * @param array $stats Statistics
     * @return array Anomalies detected
     */
    protected function detectIQRAnomalies(array $data, array $stats): array
    {
        $anomalies = [];
        $q1 = $stats['q1'];
        $q3 = $stats['q3'];
        $iqr = $stats['iqr'];

        $lowerFence = $q1 - (self::IQR_MULTIPLIER * $iqr);
        $upperFence = $q3 + (self::IQR_MULTIPLIER * $iqr);

        foreach ($data as $point) {
            $value = $point['value'];

            if ($value < $lowerFence || $value > $upperFence) {
                $anomalies[] = [
                    'value' => $value,
                    'timestamp' => $point['timestamp'],
                    'method' => 'iqr',
                    'severity' => 'warning',
                    'lower_fence' => round($lowerFence, 2),
                    'upper_fence' => round($upperFence, 2),
                    'direction' => $value < $lowerFence ? 'below' : 'above',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect anomalies using Statistical Process Control (SPC)
     *
     * Control Chart with 3-sigma limits:
     * UCL = μ + 3σ (Upper Control Limit)
     * LCL = μ - 3σ (Lower Control Limit)
     *
     * @param array $data Time series data
     * @param array $stats Statistics
     * @return array Anomalies detected
     */
    protected function detectSPCAnomalies(array $data, array $stats): array
    {
        $anomalies = [];
        $mean = $stats['mean'];
        $stdDev = $stats['std_dev'];

        $ucl = $mean + (3 * $stdDev); // Upper Control Limit
        $lcl = $mean - (3 * $stdDev); // Lower Control Limit
        $uwl = $mean + (2 * $stdDev); // Upper Warning Limit
        $lwl = $mean - (2 * $stdDev); // Lower Warning Limit

        foreach ($data as $point) {
            $value = $point['value'];

            if ($value > $ucl || $value < $lcl) {
                $anomalies[] = [
                    'value' => $value,
                    'timestamp' => $point['timestamp'],
                    'method' => 'spc',
                    'severity' => 'critical',
                    'ucl' => round($ucl, 2),
                    'lcl' => round($lcl, 2),
                    'direction' => $value > $ucl ? 'above' : 'below',
                ];
            } elseif ($value > $uwl || $value < $lwl) {
                $anomalies[] = [
                    'value' => $value,
                    'timestamp' => $point['timestamp'],
                    'method' => 'spc',
                    'severity' => 'warning',
                    'uwl' => round($uwl, 2),
                    'lwl' => round($lwl, 2),
                    'direction' => $value > $uwl ? 'above' : 'below',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect anomalies using Moving Average Deviation
     *
     * Compares current value to moving average
     *
     * @param array $data Time series data
     * @param array $stats Statistics
     * @return array Anomalies detected
     */
    protected function detectMovingAverageAnomalies(array $data, array $stats): array
    {
        $anomalies = [];
        $window = min(7, floor(count($data) / 3)); // 7-day window or 1/3 of data

        if ($window < 3) {
            return []; // Not enough data for moving average
        }

        for ($i = $window; $i < count($data); $i++) {
            // Calculate moving average
            $windowData = array_slice($data, $i - $window, $window);
            $windowValues = array_column($windowData, 'value');
            $ma = array_sum($windowValues) / count($windowValues);

            // Calculate moving standard deviation
            $maVariance = 0;
            foreach ($windowValues as $val) {
                $maVariance += pow($val - $ma, 2);
            }
            $maStdDev = sqrt($maVariance / count($windowValues));

            // Check current point
            $currentValue = $data[$i]['value'];
            $deviation = abs($currentValue - $ma);

            if ($maStdDev > 0 && ($deviation / $maStdDev) > 2.5) {
                $anomalies[] = [
                    'value' => $currentValue,
                    'timestamp' => $data[$i]['timestamp'],
                    'method' => 'moving_average',
                    'severity' => 'notable',
                    'moving_average' => round($ma, 2),
                    'deviation' => round($deviation, 2),
                    'direction' => $currentValue > $ma ? 'above' : 'below',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Remove duplicate anomalies (same timestamp)
     *
     * @param array $anomalies All anomalies
     * @return array Unique anomalies
     */
    protected function deduplicateAnomalies(array $anomalies): array
    {
        $unique = [];
        $seen = [];

        foreach ($anomalies as $anomaly) {
            $key = $anomaly['timestamp'] . '_' . $anomaly['value'];

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $anomaly;
            }
        }

        // Sort by severity (critical > warning > notable)
        usort($unique, function($a, $b) {
            $severityOrder = ['critical' => 3, 'warning' => 2, 'notable' => 1];
            $aLevel = $severityOrder[$a['severity']] ?? 0;
            $bLevel = $severityOrder[$b['severity']] ?? 0;
            return $bLevel <=> $aLevel;
        });

        return $unique;
    }

    /**
     * Calculate overall anomaly score (0-100)
     *
     * @param array $anomalies All anomalies by metric
     * @return float Overall score
     */
    protected function calculateAnomalyScore(array $anomalies): float
    {
        $totalAnomalies = 0;
        $criticalCount = 0;
        $warningCount = 0;

        foreach ($anomalies as $metricData) {
            foreach ($metricData['anomalies'] as $anomaly) {
                $totalAnomalies++;
                if ($anomaly['severity'] === 'critical') {
                    $criticalCount++;
                } elseif ($anomaly['severity'] === 'warning') {
                    $warningCount++;
                }
            }
        }

        // Score: higher = more anomalies (worse)
        $score = ($criticalCount * 10) + ($warningCount * 5) + ($totalAnomalies * 2);

        return min(100, $score);
    }

    /**
     * Get status level based on anomaly score
     *
     * @param float $score Anomaly score
     * @return string Status level
     */
    protected function getStatusLevel(float $score): string
    {
        if ($score >= 50) return 'critical';
        if ($score >= 25) return 'warning';
        if ($score >= 10) return 'notable';
        return 'normal';
    }

    /**
     * Generate alerts for anomalies
     *
     * @param array $anomalies Anomalies by metric
     * @return array Alerts
     */
    protected function generateAlerts(array $anomalies): array
    {
        $alerts = [];

        foreach ($anomalies as $metric => $metricData) {
            foreach ($metricData['anomalies'] as $anomaly) {
                if ($anomaly['severity'] === 'critical' || $anomaly['severity'] === 'warning') {
                    $alerts[] = [
                        'metric' => $metric,
                        'severity' => $anomaly['severity'],
                        'value' => $anomaly['value'],
                        'timestamp' => $anomaly['timestamp'],
                        'message' => $this->getAlertMessage($metric, $anomaly),
                        'recommended_action' => $this->getRecommendedAction($metric, $anomaly),
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Get alert message for anomaly
     *
     * @param string $metric Metric name
     * @param array $anomaly Anomaly data
     * @return string Alert message
     */
    protected function getAlertMessage(string $metric, array $anomaly): string
    {
        $direction = $anomaly['direction'] === 'above' ? 'yuqori' : 'past';
        $value = $anomaly['value'];

        return "{$metric} metrikasi ({$value}) kutilganidan ancha {$direction}! " .
               "Severity: {$anomaly['severity']}, Method: {$anomaly['method']}";
    }

    /**
     * Get recommended action for anomaly
     *
     * @param string $metric Metric name
     * @param array $anomaly Anomaly data
     * @return string Recommended action
     */
    protected function getRecommendedAction(string $metric, array $anomaly): string
    {
        $actions = [
            'engagement_rate' => "Engagement anomaliyasini tekshiring - content quality yoki timing o'zgarganmi?",
            'follower_count' => "Follower anomaliyasi - bot activity yoki viral content bo'lishi mumkin",
            'revenue' => "Revenue anomaliyasi - transaction log'larni tekshiring",
            'conversion_rate' => "Conversion anomaliyasi - funnel'da muammo bo'lishi mumkin",
        ];

        return $actions[$metric] ?? "Ushbu metrikani diqqat bilan monitoring qiling";
    }

    /**
     * Generate recommendations based on anomalies
     *
     * @param array $anomalies Anomalies by metric
     * @param Business $business
     * @return array Recommendations
     */
    protected function generateRecommendations(array $anomalies, Business $business): array
    {
        $recommendations = [];

        $totalCritical = 0;
        foreach ($anomalies as $metricData) {
            foreach ($metricData['anomalies'] as $anomaly) {
                if ($anomaly['severity'] === 'critical') {
                    $totalCritical++;
                }
            }
        }

        if ($totalCritical > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'title' => 'Critical anomaliyalar topildi',
                'description' => "{$totalCritical} ta critical anomaliya aniqlandi. Tezda tekshiring!",
                'action_items' => [
                    'Har bir critical anomaliyaning root cause ini aniqlang',
                    'Data collection processlarini tekshiring',
                    'Fraud yoki bot activity mavjudligini tekshiring',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Generate synthetic data for demo
     *
     * @return array Synthetic time series data
     */
    protected function generateSyntheticData(): array
    {
        $data = [];

        // Generate engagement rate data with some anomalies
        $engagementData = [];
        for ($i = 0; $i < 30; $i++) {
            $baseValue = 4.5 + (rand(-50, 50) / 100); // Normal: 4.0-5.0%

            // Add anomalies
            if ($i == 10) $baseValue = 1.5; // Sudden drop
            if ($i == 20) $baseValue = 8.0; // Sudden spike

            $engagementData[] = [
                'value' => round($baseValue, 2),
                'timestamp' => Carbon::now()->subDays(30 - $i)->toIso8601String(),
            ];
        }
        $data['engagement_rate'] = $engagementData;

        // Generate revenue data
        $revenueData = [];
        for ($i = 0; $i < 30; $i++) {
            $baseValue = 100000 + (rand(-10000, 10000));

            // Add anomaly
            if ($i == 15) $baseValue = 200000; // Big sale day

            $revenueData[] = [
                'value' => $baseValue,
                'timestamp' => Carbon::now()->subDays(30 - $i)->toIso8601String(),
            ];
        }
        $data['revenue'] = $revenueData;

        return $data;
    }
}
