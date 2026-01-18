<?php

namespace App\Services\Algorithm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Base Algorithm Engine
 *
 * Provides fast, accurate calculations with intelligent caching
 * and data validation mechanisms.
 */
abstract class AlgorithmEngine
{
    protected string $cachePrefix = 'algo_';

    protected int $cacheTTL = 3600; // 1 hour default

    /**
     * Execute algorithm with caching
     */
    protected function cached(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->cachePrefix.$key;
        $ttl = $ttl ?? $this->cacheTTL;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Invalidate cache for specific key
     */
    protected function invalidateCache(string $key): bool
    {
        return Cache::forget($this->cachePrefix.$key);
    }

    /**
     * Invalidate all caches for this engine
     */
    public function invalidateAllCaches(): void
    {
        // Laravel doesn't have prefix-based cache clearing by default
        // This would need Redis or custom implementation
        Log::info("Cache invalidation requested for: {$this->cachePrefix}");
    }

    /**
     * Calculate standard deviation
     */
    protected function standardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn ($v) => pow($v - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / (count($values) - 1);

        return sqrt($variance);
    }

    /**
     * Calculate percentile
     */
    protected function percentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return $values[(int) $lower];
        }

        return $values[(int) $lower] + ($values[(int) $upper] - $values[(int) $lower]) * ($index - $lower);
    }

    /**
     * Calculate moving average
     */
    protected function movingAverage(array $values, int $period = 7): array
    {
        if (count($values) < $period) {
            return $values;
        }

        $result = [];
        for ($i = $period - 1; $i < count($values); $i++) {
            $slice = array_slice($values, $i - $period + 1, $period);
            $result[] = array_sum($slice) / $period;
        }

        return $result;
    }

    /**
     * Calculate exponential moving average (EMA)
     * More responsive to recent changes
     */
    protected function exponentialMovingAverage(array $values, int $period = 7): array
    {
        if (empty($values)) {
            return [];
        }

        $multiplier = 2 / ($period + 1);
        $ema = [$values[0]];

        for ($i = 1; $i < count($values); $i++) {
            $ema[] = ($values[$i] * $multiplier) + ($ema[$i - 1] * (1 - $multiplier));
        }

        return $ema;
    }

    /**
     * Detect outliers using IQR method
     */
    protected function detectOutliers(array $values): array
    {
        if (count($values) < 4) {
            return ['outliers' => [], 'clean_data' => $values];
        }

        $q1 = $this->percentile($values, 25);
        $q3 = $this->percentile($values, 75);
        $iqr = $q3 - $q1;

        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);

        $outliers = [];
        $cleanData = [];

        foreach ($values as $index => $value) {
            if ($value < $lowerBound || $value > $upperBound) {
                $outliers[$index] = $value;
            } else {
                $cleanData[] = $value;
            }
        }

        return [
            'outliers' => $outliers,
            'clean_data' => $cleanData,
            'bounds' => ['lower' => $lowerBound, 'upper' => $upperBound],
        ];
    }

    /**
     * Calculate Z-score for anomaly detection
     */
    protected function zScore(float $value, float $mean, float $stdDev): float
    {
        if ($stdDev === 0.0) {
            return 0.0;
        }

        return ($value - $mean) / $stdDev;
    }

    /**
     * Linear regression for trend analysis
     */
    protected function linearRegression(array $values): array
    {
        $n = count($values);
        if ($n < 2) {
            return ['slope' => 0, 'intercept' => $values[0] ?? 0, 'r_squared' => 0];
        }

        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $values[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $values[$i] * $values[$i];
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator === 0) {
            return ['slope' => 0, 'intercept' => $sumY / $n, 'r_squared' => 0];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Calculate R-squared
        $meanY = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;

        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $ssTotal += pow($values[$i] - $meanY, 2);
            $ssResidual += pow($values[$i] - $predicted, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'slope' => round($slope, 6),
            'intercept' => round($intercept, 6),
            'r_squared' => round($rSquared, 4),
            'trend' => $slope > 0.01 ? 'up' : ($slope < -0.01 ? 'down' : 'stable'),
        ];
    }

    /**
     * Calculate confidence interval
     */
    protected function confidenceInterval(array $values, float $confidence = 0.95): array
    {
        $n = count($values);
        if ($n < 2) {
            $mean = $values[0] ?? 0;

            return ['mean' => $mean, 'lower' => $mean, 'upper' => $mean, 'margin' => 0];
        }

        $mean = array_sum($values) / $n;
        $stdDev = $this->standardDeviation($values);

        // Z-scores for common confidence levels
        $zScores = [
            0.90 => 1.645,
            0.95 => 1.96,
            0.99 => 2.576,
        ];

        $z = $zScores[$confidence] ?? 1.96;
        $margin = $z * ($stdDev / sqrt($n));

        return [
            'mean' => round($mean, 2),
            'lower' => round($mean - $margin, 2),
            'upper' => round($mean + $margin, 2),
            'margin' => round($margin, 2),
            'confidence' => $confidence,
        ];
    }

    /**
     * Normalize values to 0-100 scale
     */
    protected function normalize(array $values, ?float $min = null, ?float $max = null): array
    {
        if (empty($values)) {
            return [];
        }

        $min = $min ?? min($values);
        $max = $max ?? max($values);

        if ($min === $max) {
            return array_fill(0, count($values), 50);
        }

        return array_map(function ($v) use ($min, $max) {
            return round((($v - $min) / ($max - $min)) * 100, 2);
        }, $values);
    }

    /**
     * Calculate correlation coefficient (Pearson)
     */
    protected function correlation(array $x, array $y): float
    {
        $n = min(count($x), count($y));
        if ($n < 2) {
            return 0.0;
        }

        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $numerator = 0;
        $denomX = 0;
        $denomY = 0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $x[$i] - $meanX;
            $diffY = $y[$i] - $meanY;
            $numerator += $diffX * $diffY;
            $denomX += $diffX * $diffX;
            $denomY += $diffY * $diffY;
        }

        $denominator = sqrt($denomX * $denomY);

        return $denominator > 0 ? round($numerator / $denominator, 4) : 0.0;
    }

    /**
     * Calculate growth rate
     */
    protected function growthRate(float $current, float $previous): float
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }

    /**
     * Calculate compound growth rate
     */
    protected function compoundGrowthRate(float $startValue, float $endValue, int $periods): float
    {
        if ($startValue <= 0 || $periods <= 0) {
            return 0.0;
        }

        return round((pow($endValue / $startValue, 1 / $periods) - 1) * 100, 2);
    }
}
