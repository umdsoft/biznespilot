<?php

namespace App\Services\Algorithm;

use App\Services\Algorithm\Math\StatisticalTests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * A/B Testing Engine
 *
 * Scientific A/B testing with statistical significance testing.
 * No AI required - uses proven statistical methods.
 *
 * Algorithms:
 * - Two-sample T-Test for continuous metrics
 * - Chi-Square Test for categorical metrics
 * - Confidence intervals
 * - Sample size calculations
 * - Bayesian probability of superiority
 *
 * Research:
 * - Student (1908) - T-Test
 * - Pearson (1900) - Chi-Square
 * - Neyman-Pearson (1933) - Hypothesis testing framework
 * - Kohavi et al. (2009) - Controlled experiments on the web
 *
 * @version 1.0.0
 * @package App\Services\Algorithm
 */
class ABTestingEngine extends AlgorithmEngine
{
    /**
     * Algorithm version
     */
    protected string $version = '1.0.0';

    /**
     * Cache TTL (5 minutes - frequently updated)
     */
    protected int $cacheTTL = 300;

    /**
     * Minimum sample size per variant
     */
    protected const MIN_SAMPLE_SIZE = 30;

    /**
     * Default significance level (alpha)
     */
    protected const DEFAULT_ALPHA = 0.05; // 95% confidence

    /**
     * Minimum detectable effect (MDE) - default 10%
     */
    protected const MIN_DETECTABLE_EFFECT = 0.10;

    /**
     * Analyze A/B test results
     *
     * @param array $variantA Data for variant A (control)
     * @param array $variantB Data for variant B (treatment)
     * @param array $options Test options
     * @return array Test results and recommendations
     */
    public function analyze(
        array $variantA,
        array $variantB,
        array $options = []
    ): array {
        try {
            $startTime = microtime(true);

            $metricType = $options['metric_type'] ?? 'continuous'; // continuous or conversion
            $alpha = $options['alpha'] ?? self::DEFAULT_ALPHA;

            // Perform appropriate statistical test
            if ($metricType === 'conversion') {
                $result = $this->analyzeConversionRate($variantA, $variantB, $alpha);
            } else {
                $result = $this->analyzeContinuousMetric($variantA, $variantB, $alpha);
            }

            // Calculate additional metrics
            $sampleSizeAnalysis = $this->analyzeSampleSize($variantA, $variantB, $options);
            $recommendation = $this->generateRecommendation($result, $sampleSizeAnalysis);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => $this->version,
                'test_type' => $metricType,
                'statistical_test' => $result,
                'sample_size_analysis' => $sampleSizeAnalysis,
                'recommendation' => $recommendation,
                'metadata' => [
                    'calculated_at' => Carbon::now()->toIso8601String(),
                    'execution_time_ms' => $executionTime,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('ABTestingEngine failed', [
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
     * Analyze conversion rate test (Chi-Square Test)
     *
     * @param array $variantA ['conversions' => int, 'visitors' => int]
     * @param array $variantB ['conversions' => int, 'visitors' => int]
     * @param float $alpha Significance level
     * @return array Test results
     */
    protected function analyzeConversionRate(
        array $variantA,
        array $variantB,
        float $alpha
    ): array {
        $conversionsA = $variantA['conversions'] ?? 0;
        $visitorsA = $variantA['visitors'] ?? 0;
        $conversionsB = $variantB['conversions'] ?? 0;
        $visitorsB = $variantB['visitors'] ?? 0;

        // Calculate conversion rates
        $convRateA = $visitorsA > 0 ? $conversionsA / $visitorsA : 0;
        $convRateB = $visitorsB > 0 ? $conversionsB / $visitorsB : 0;

        // Construct contingency table
        // [[conversions_A, non_conversions_A],
        //  [conversions_B, non_conversions_B]]
        $contingencyTable = [
            [$conversionsA, $visitorsA - $conversionsA],
            [$conversionsB, $visitorsB - $conversionsB],
        ];

        // Perform Chi-Square test
        $chiSquareResult = StatisticalTests::chiSquareTest($contingencyTable, $alpha);

        // Calculate confidence intervals
        $ciA = StatisticalTests::proportionConfidenceInterval($conversionsA, $visitorsA);
        $ciB = StatisticalTests::proportionConfidenceInterval($conversionsB, $visitorsB);

        // Calculate relative lift
        $lift = $convRateA > 0 ? (($convRateB - $convRateA) / $convRateA) * 100 : 0;
        $absoluteDiff = ($convRateB - $convRateA) * 100;

        // Bayesian probability that B > A (simplified approximation)
        $probBBetterThanA = $this->calculateBayesianProbability($convRateB, $convRateA);

        return [
            'method' => 'chi_square_test',
            'variant_a' => [
                'conversions' => $conversionsA,
                'visitors' => $visitorsA,
                'conversion_rate' => round($convRateA * 100, 2) . '%',
                'confidence_interval' => [
                    'lower' => round($ciA['lower'] * 100, 2) . '%',
                    'upper' => round($ciA['upper'] * 100, 2) . '%',
                ],
            ],
            'variant_b' => [
                'conversions' => $conversionsB,
                'visitors' => $visitorsB,
                'conversion_rate' => round($convRateB * 100, 2) . '%',
                'confidence_interval' => [
                    'lower' => round($ciB['lower'] * 100, 2) . '%',
                    'upper' => round($ciB['upper'] * 100, 2) . '%',
                ],
            ],
            'comparison' => [
                'absolute_difference' => round($absoluteDiff, 2) . '%',
                'relative_lift' => round($lift, 2) . '%',
                'winner' => $convRateB > $convRateA ? 'B' : ($convRateB < $convRateA ? 'A' : 'tie'),
            ],
            'statistical_significance' => [
                'chi_square' => $chiSquareResult['chi_square'] ?? 0,
                'p_value' => $chiSquareResult['p_value'] ?? 1,
                'significant' => $chiSquareResult['significant'] ?? false,
                'alpha' => $alpha,
                'confidence_level' => ((1 - $alpha) * 100) . '%',
            ],
            'bayesian' => [
                'prob_b_better_than_a' => round($probBBetterThanA * 100, 1) . '%',
            ],
        ];
    }

    /**
     * Analyze continuous metric test (T-Test)
     *
     * @param array $variantA Array of values
     * @param array $variantB Array of values
     * @param float $alpha Significance level
     * @return array Test results
     */
    protected function analyzeContinuousMetric(
        array $variantA,
        array $variantB,
        float $alpha
    ): array {
        // Extract values
        $valuesA = $variantA['values'] ?? [];
        $valuesB = $variantB['values'] ?? [];

        if (empty($valuesA) || empty($valuesB)) {
            return [
                'error' => 'Insufficient data',
                'significant' => false,
            ];
        }

        // Perform T-Test
        $tTestResult = StatisticalTests::tTest($valuesA, $valuesB, $alpha);

        // Calculate descriptive statistics
        $statsA = $this->calculateDescriptiveStats($valuesA);
        $statsB = $this->calculateDescriptiveStats($valuesB);

        // Calculate lift
        $meanA = $statsA['mean'];
        $meanB = $statsB['mean'];
        $lift = $meanA > 0 ? (($meanB - $meanA) / $meanA) * 100 : 0;

        return [
            'method' => 't_test',
            'variant_a' => array_merge(['sample_size' => count($valuesA)], $statsA),
            'variant_b' => array_merge(['sample_size' => count($valuesB)], $statsB),
            'comparison' => [
                'mean_difference' => $tTestResult['mean_difference'] ?? 0,
                'relative_lift' => round($lift, 2) . '%',
                'winner' => $meanB > $meanA ? 'B' : ($meanB < $meanA ? 'A' : 'tie'),
            ],
            'statistical_significance' => [
                't_statistic' => $tTestResult['t_statistic'] ?? 0,
                'p_value' => $tTestResult['p_value'] ?? 1,
                'significant' => $tTestResult['significant'] ?? false,
                'alpha' => $alpha,
                'confidence_level' => ((1 - $alpha) * 100) . '%',
                'degrees_of_freedom' => $tTestResult['degrees_of_freedom'] ?? 0,
            ],
            'effect_size' => [
                'cohens_d' => $tTestResult['cohens_d'] ?? 0,
                'interpretation' => $tTestResult['effect_size'] ?? 'unknown',
            ],
        ];
    }

    /**
     * Calculate descriptive statistics
     *
     * @param array $values Array of numeric values
     * @return array Statistics
     */
    protected function calculateDescriptiveStats(array $values): array
    {
        if (empty($values)) {
            return [
                'mean' => 0,
                'median' => 0,
                'std_dev' => 0,
                'min' => 0,
                'max' => 0,
            ];
        }

        $n = count($values);
        $mean = array_sum($values) / $n;

        // Standard deviation
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= ($n - 1);
        $stdDev = sqrt($variance);

        // Median
        sort($values);
        $mid = floor($n / 2);
        if ($n % 2 == 0) {
            $median = ($values[$mid - 1] + $values[$mid]) / 2;
        } else {
            $median = $values[$mid];
        }

        return [
            'mean' => round($mean, 2),
            'median' => round($median, 2),
            'std_dev' => round($stdDev, 2),
            'min' => round(min($values), 2),
            'max' => round(max($values), 2),
        ];
    }

    /**
     * Analyze sample size adequacy
     *
     * @param array $variantA Variant A data
     * @param array $variantB Variant B data
     * @param array $options Test options
     * @return array Sample size analysis
     */
    protected function analyzeSampleSize(
        array $variantA,
        array $variantB,
        array $options
    ): array {
        // Get actual sample sizes
        $nA = $variantA['visitors'] ?? count($variantA['values'] ?? []);
        $nB = $variantB['visitors'] ?? count($variantB['values'] ?? []);

        // Calculate required sample size
        $expectedEffect = $options['expected_effect'] ?? self::MIN_DETECTABLE_EFFECT;
        $power = $options['power'] ?? 0.80;
        $alpha = $options['alpha'] ?? self::DEFAULT_ALPHA;

        $requiredN = StatisticalTests::calculateSampleSize($expectedEffect, $alpha, $power);

        // Check if sample size is adequate
        $isAdequateA = $nA >= $requiredN;
        $isAdequateB = $nB >= $requiredN;
        $isAdequate = $isAdequateA && $isAdequateB;

        return [
            'current_sample_sizes' => [
                'variant_a' => $nA,
                'variant_b' => $nB,
                'total' => $nA + $nB,
            ],
            'required_sample_size_per_variant' => $requiredN,
            'is_adequate' => $isAdequate,
            'adequacy_details' => [
                'variant_a' => [
                    'adequate' => $isAdequateA,
                    'percentage' => round(($nA / $requiredN) * 100, 1) . '%',
                ],
                'variant_b' => [
                    'adequate' => $isAdequateB,
                    'percentage' => round(($nB / $requiredN) * 100, 1) . '%',
                ],
            ],
            'assumptions' => [
                'expected_effect' => round($expectedEffect * 100, 1) . '%',
                'power' => round($power * 100) . '%',
                'alpha' => $alpha,
            ],
        ];
    }

    /**
     * Calculate Bayesian probability that B > A
     *
     * Simplified approximation using normal distribution.
     *
     * @param float $rateB Conversion rate B
     * @param float $rateA Conversion rate A
     * @return float Probability (0-1)
     */
    protected function calculateBayesianProbability(float $rateB, float $rateA): float
    {
        if ($rateB == $rateA) {
            return 0.5;
        }

        // Simplified: assume probability is proportional to difference
        $diff = $rateB - $rateA;
        $maxDiff = max(abs($diff), 0.01);

        // Approximate probability (sigmoid function)
        $k = 20; // Steepness
        $prob = 1 / (1 + exp(-$k * $diff / $maxDiff));

        return $prob;
    }

    /**
     * Generate recommendation based on test results
     *
     * @param array $testResult Statistical test result
     * @param array $sampleSizeAnalysis Sample size analysis
     * @return array Recommendation
     */
    protected function generateRecommendation(
        array $testResult,
        array $sampleSizeAnalysis
    ): array {
        $isSignificant = $testResult['statistical_significance']['significant'] ?? false;
        $pValue = $testResult['statistical_significance']['p_value'] ?? 1;
        $isAdequateSample = $sampleSizeAnalysis['is_adequate'] ?? false;
        $winner = $testResult['comparison']['winner'] ?? 'tie';

        // Determine action
        if (!$isAdequateSample) {
            $action = 'continue_testing';
            $decision = 'Testni davom ettiring - yetarli sample mavjud emas';
            $confidence = 'low';
        } elseif ($isSignificant && $winner !== 'tie') {
            $action = 'implement_winner';
            $decision = "Variant {$winner} yutdi! Uni implement qiling.";
            $confidence = 'high';
        } elseif ($pValue < 0.10) {
            $action = 'borderline';
            $decision = 'Variant farq qiladi lekin ancha emas. Testni uzaytiring yoki kichik farq bilan implement qiling.';
            $confidence = 'medium';
        } else {
            $action = 'no_difference';
            $decision = 'Variantlar orasida significant farq yo\'q. Eng oson/arzon variantni qo\'llang.';
            $confidence = 'high';
        }

        // Calculate estimated impact
        $lift = floatval(str_replace('%', '', $testResult['comparison']['relative_lift'] ?? '0'));
        $estimatedImpact = $this->estimateBusinessImpact($lift, $winner);

        return [
            'action' => $action,
            'decision' => $decision,
            'confidence' => $confidence,
            'winner' => $winner,
            'estimated_impact' => $estimatedImpact,
            'next_steps' => $this->getNextSteps($action, $sampleSizeAnalysis),
        ];
    }

    /**
     * Estimate business impact
     *
     * @param float $lift Relative lift (percentage)
     * @param string $winner Winner variant
     * @return array Impact estimate
     */
    protected function estimateBusinessImpact(float $lift, string $winner): array
    {
        if ($winner === 'tie' || abs($lift) < 1) {
            return [
                'description' => 'Minimal impact kutilmoqda',
                'category' => 'negligible',
            ];
        }

        $absLift = abs($lift);

        if ($absLift >= 20) {
            $category = 'huge';
            $description = "Juda katta ta'sir: {$lift}% o'sish";
        } elseif ($absLift >= 10) {
            $category = 'large';
            $description = "Katta ta'sir: {$lift}% o'sish";
        } elseif ($absLift >= 5) {
            $category = 'medium';
            $description = "O'rtacha ta'sir: {$lift}% o'sish";
        } else {
            $category = 'small';
            $description = "Kichik ta'sir: {$lift}% o'sish";
        }

        return [
            'description' => $description,
            'category' => $category,
            'lift_percentage' => round($lift, 1) . '%',
        ];
    }

    /**
     * Get next steps based on recommendation
     *
     * @param string $action Recommended action
     * @param array $sampleSizeAnalysis Sample size analysis
     * @return array Next steps
     */
    protected function getNextSteps(string $action, array $sampleSizeAnalysis): array
    {
        switch ($action) {
            case 'continue_testing':
                $requiredN = $sampleSizeAnalysis['required_sample_size_per_variant'];
                return [
                    "Har bir variant uchun kamida {$requiredN} sample to'plang",
                    'Test davom etar ekan, boshqa variantlarni sinab ko\'ring',
                    'Sample size calculator ishlatib optimal davomiylikni hisoblang',
                ];

            case 'implement_winner':
                return [
                    'Yutgan variantni 100% trafficga roll out qiling',
                    'Natijalarni monitoring qiling (A/A test bilan)',
                    'Keyingi iteratsiya uchun yangi testlar rejalashtiring',
                    'Success metrics va ROI ni hisoblang',
                ];

            case 'borderline':
                return [
                    'Testni 1-2 hafta davom ettiring',
                    'Segment analiz qiling - ba\'zi segmentlarda farq bormi?',
                    'Risk assessment qiling - kichik yaxshilanish arziyaptimi?',
                ];

            case 'no_difference':
                return [
                    'Testni to\'xtating',
                    'Eng oson implement qilinadigan variantni tanlang',
                    'Yangi, kuchli hypothesis bilan test boshlang',
                    'Boshqa metrikalarni sinab ko\'ring',
                ];

            default:
                return [
                    'Natijalarni diqqat bilan tahlil qiling',
                    'Stakeholder bilan consultation qiling',
                ];
        }
    }
}
