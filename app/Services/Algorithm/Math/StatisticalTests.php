<?php

namespace App\Services\Algorithm\Math;

/**
 * Statistical Hypothesis Testing
 *
 * Implements common statistical tests for A/B testing and experimentation.
 * No AI required - pure mathematical formulas.
 *
 * Research:
 * - Student (1908) - T-Test
 * - Pearson (1900) - Chi-Square Test
 * - Welch (1947) - Welch's T-Test (unequal variances)
 * - Fisher (1925) - Analysis of Variance (ANOVA)
 *
 * @version 1.0.0
 */
class StatisticalTests
{
    /**
     * Two-Sample T-Test (Independent samples)
     *
     * Tests if two samples have significantly different means.
     *
     * Formula:
     * t = (X̄₁ - X̄₂) / √(s²pooled × (1/n₁ + 1/n₂))
     *
     * @param  array  $sample1  First sample
     * @param  array  $sample2  Second sample
     * @param  float  $alpha  Significance level (default 0.05)
     * @return array Test results
     */
    public static function tTest(
        array $sample1,
        array $sample2,
        float $alpha = 0.05
    ): array {
        $n1 = count($sample1);
        $n2 = count($sample2);

        if ($n1 < 2 || $n2 < 2) {
            return [
                'error' => 'Insufficient sample size (minimum 2 per group)',
                'significant' => false,
            ];
        }

        // Calculate means
        $mean1 = array_sum($sample1) / $n1;
        $mean2 = array_sum($sample2) / $n2;

        // Calculate variances
        $var1 = self::variance($sample1, $mean1);
        $var2 = self::variance($sample2, $mean2);

        // Pooled standard deviation
        $pooledVar = (($n1 - 1) * $var1 + ($n2 - 1) * $var2) / ($n1 + $n2 - 2);
        $pooledStdDev = sqrt($pooledVar * (1 / $n1 + 1 / $n2));

        if ($pooledStdDev == 0) {
            return [
                'error' => 'No variation in samples',
                'significant' => false,
            ];
        }

        // Calculate t-statistic
        $tStat = ($mean1 - $mean2) / $pooledStdDev;

        // Degrees of freedom
        $df = $n1 + $n2 - 2;

        // Calculate p-value (two-tailed)
        $pValue = 2 * (1 - self::tDistributionCDF(abs($tStat), $df));

        // Effect size (Cohen's d)
        $cohensD = ($mean1 - $mean2) / sqrt($pooledVar);

        return [
            't_statistic' => round($tStat, 4),
            'degrees_of_freedom' => $df,
            'p_value' => round($pValue, 4),
            'significant' => $pValue < $alpha,
            'alpha' => $alpha,
            'mean_difference' => round($mean1 - $mean2, 4),
            'cohens_d' => round($cohensD, 3),
            'effect_size' => self::interpretCohenD($cohensD),
            'sample_sizes' => [$n1, $n2],
            'means' => [round($mean1, 2), round($mean2, 2)],
        ];
    }

    /**
     * Chi-Square Test for Independence
     *
     * Tests if two categorical variables are independent.
     *
     * Formula:
     * χ² = Σ (Observed - Expected)² / Expected
     *
     * @param  array  $observed  2x2 contingency table [[a,b],[c,d]]
     * @param  float  $alpha  Significance level
     * @return array Test results
     */
    public static function chiSquareTest(
        array $observed,
        float $alpha = 0.05
    ): array {
        // Expecting 2x2 table
        if (count($observed) != 2 || count($observed[0]) != 2) {
            return [
                'error' => 'Expected 2x2 contingency table',
                'significant' => false,
            ];
        }

        $a = $observed[0][0];
        $b = $observed[0][1];
        $c = $observed[1][0];
        $d = $observed[1][1];

        // Row and column totals
        $row1Total = $a + $b;
        $row2Total = $c + $d;
        $col1Total = $a + $c;
        $col2Total = $b + $d;
        $grandTotal = $row1Total + $row2Total;

        if ($grandTotal == 0) {
            return [
                'error' => 'Empty table',
                'significant' => false,
            ];
        }

        // Calculate expected frequencies
        $expected = [
            [
                ($row1Total * $col1Total) / $grandTotal,
                ($row1Total * $col2Total) / $grandTotal,
            ],
            [
                ($row2Total * $col1Total) / $grandTotal,
                ($row2Total * $col2Total) / $grandTotal,
            ],
        ];

        // Calculate chi-square statistic
        $chiSquare = 0;
        for ($i = 0; $i < 2; $i++) {
            for ($j = 0; $j < 2; $j++) {
                if ($expected[$i][$j] > 0) {
                    $chiSquare += pow($observed[$i][$j] - $expected[$i][$j], 2) / $expected[$i][$j];
                }
            }
        }

        // Degrees of freedom for 2x2 table
        $df = 1;

        // Calculate p-value
        $pValue = 1 - self::chiSquareCDF($chiSquare, $df);

        return [
            'chi_square' => round($chiSquare, 4),
            'degrees_of_freedom' => $df,
            'p_value' => round($pValue, 4),
            'significant' => $pValue < $alpha,
            'alpha' => $alpha,
            'observed' => $observed,
            'expected' => $expected,
        ];
    }

    /**
     * Calculate variance
     *
     * @param  array  $sample  Sample data
     * @param  float|null  $mean  Mean (optional, will calculate if not provided)
     * @return float Variance
     */
    protected static function variance(array $sample, ?float $mean = null): float
    {
        $n = count($sample);
        if ($n < 2) {
            return 0;
        }

        if ($mean === null) {
            $mean = array_sum($sample) / $n;
        }

        $sumSquares = 0;
        foreach ($sample as $value) {
            $sumSquares += pow($value - $mean, 2);
        }

        return $sumSquares / ($n - 1); // Sample variance (Bessel's correction)
    }

    /**
     * Approximate T-Distribution CDF
     *
     * Uses approximation for t-distribution cumulative distribution function.
     * Sufficient accuracy for most A/B testing use cases.
     *
     * @param  float  $t  T-statistic
     * @param  int  $df  Degrees of freedom
     * @return float Cumulative probability
     */
    protected static function tDistributionCDF(float $t, int $df): float
    {
        // For large df (>30), t-distribution approaches normal distribution
        if ($df > 30) {
            return self::normalCDF($t);
        }

        // Hill's approximation
        $a = $t / sqrt($df);
        $b = $df / ($df + $t * $t);
        $probability = 0.5 + ($a * sqrt($b) * (1 + (5 * $b - 1) / (16 * $df))) / 2;

        return max(0, min(1, $probability));
    }

    /**
     * Standard Normal Distribution CDF
     *
     * Approximation using error function.
     *
     * @param  float  $z  Z-score
     * @return float Cumulative probability
     */
    protected static function normalCDF(float $z): float
    {
        return 0.5 * (1 + self::erf($z / sqrt(2)));
    }

    /**
     * Error function approximation
     *
     * Abramowitz and Stegun approximation (maximum error: 1.5e-7)
     *
     * @param  float  $x  Input value
     * @return float Error function value
     */
    protected static function erf(float $x): float
    {
        // Constants
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        // Save the sign of x
        $sign = ($x < 0) ? -1 : 1;
        $x = abs($x);

        // A&S formula 7.1.26
        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    /**
     * Chi-Square Distribution CDF
     *
     * Approximation for chi-square cumulative distribution.
     *
     * @param  float  $x  Chi-square statistic
     * @param  int  $df  Degrees of freedom
     * @return float Cumulative probability
     */
    protected static function chiSquareCDF(float $x, int $df): float
    {
        if ($x <= 0) {
            return 0;
        }
        if ($df <= 0) {
            return 0;
        }

        // Wilson-Hilferty approximation
        $z = pow($x / $df, 1 / 3) - (1 - 2 / (9 * $df)) / sqrt(2 / (9 * $df));

        return self::normalCDF($z);
    }

    /**
     * Interpret Cohen's d effect size
     *
     * @param  float  $d  Cohen's d value
     * @return string Effect size interpretation
     */
    protected static function interpretCohenD(float $d): string
    {
        $absD = abs($d);

        if ($absD < 0.2) {
            return 'negligible';
        }
        if ($absD < 0.5) {
            return 'small';
        }
        if ($absD < 0.8) {
            return 'medium';
        }

        return 'large';
    }

    /**
     * Calculate required sample size for t-test
     *
     * Power analysis for independent samples t-test.
     *
     * @param  float  $effectSize  Expected Cohen's d
     * @param  float  $alpha  Significance level (default 0.05)
     * @param  float  $power  Desired statistical power (default 0.80)
     * @return int Required sample size per group
     */
    public static function calculateSampleSize(
        float $effectSize,
        float $alpha = 0.05,
        float $power = 0.80
    ): int {
        // Simplified formula (assumes equal group sizes)
        // n ≈ 2(Zα/2 + Zβ)² / δ²

        // Critical values
        $zAlpha = self::normalInverse(1 - $alpha / 2); // Two-tailed
        $zBeta = self::normalInverse($power);

        $n = 2 * pow($zAlpha + $zBeta, 2) / pow($effectSize, 2);

        return (int) ceil($n);
    }

    /**
     * Inverse normal distribution (approximation)
     *
     * @param  float  $p  Probability (0 to 1)
     * @return float Z-score
     */
    protected static function normalInverse(float $p): float
    {
        if ($p <= 0 || $p >= 1) {
            return 0;
        }

        // Beasley-Springer-Moro approximation
        $a0 = 2.50662823884;
        $a1 = -18.61500062529;
        $a2 = 41.39119773534;
        $a3 = -25.44106049637;

        $b0 = -8.47351093090;
        $b1 = 23.08336743743;
        $b2 = -21.06224101826;
        $b3 = 3.13082909833;

        $c0 = 0.3374754822726147;
        $c1 = 0.9761690190917186;
        $c2 = 0.1607979714918209;
        $c3 = 0.0276438810333863;
        $c4 = 0.0038405729373609;
        $c5 = 0.0003951896511919;
        $c6 = 0.0000321767881768;
        $c7 = 0.0000002888167364;
        $c8 = 0.0000003960315187;

        $x = $p - 0.5;

        if (abs($x) < 0.42) {
            $r = $x * $x;
            $result = $x * ((($a3 * $r + $a2) * $r + $a1) * $r + $a0) /
                      (((($b3 * $r + $b2) * $r + $b1) * $r + $b0) * $r + 1);
        } else {
            $r = $p;
            if ($x > 0) {
                $r = 1 - $p;
            }

            $r = log(-log($r));
            $result = $c0 + $r * ($c1 + $r * ($c2 + $r * ($c3 + $r * ($c4 + $r *
                      ($c5 + $r * ($c6 + $r * ($c7 + $r * $c8)))))));

            if ($x < 0) {
                $result = -$result;
            }
        }

        return $result;
    }

    /**
     * Calculate confidence interval for proportion
     *
     * @param  int  $successes  Number of successes
     * @param  int  $trials  Number of trials
     * @param  float  $confidenceLevel  Confidence level (default 0.95)
     * @return array [lower_bound, upper_bound]
     */
    public static function proportionConfidenceInterval(
        int $successes,
        int $trials,
        float $confidenceLevel = 0.95
    ): array {
        if ($trials == 0) {
            return [0, 0];
        }

        $p = $successes / $trials;
        $z = self::normalInverse((1 + $confidenceLevel) / 2);
        $se = sqrt($p * (1 - $p) / $trials);

        return [
            'lower' => max(0, round($p - $z * $se, 4)),
            'upper' => min(1, round($p + $z * $se, 4)),
            'proportion' => round($p, 4),
        ];
    }
}
