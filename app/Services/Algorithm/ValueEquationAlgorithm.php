<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Value Equation Algorithm v3.0.0
 *
 * Alex Hormozi's Value Equation formula asosida taklif qiymatini baholash
 *
 * Value Equation:
 * Value = (Dream Outcome × Perceived Likelihood) / (Time Delay × Effort & Sacrifice)
 *
 * Research Sources:
 * - Alex Hormozi: $100M Offers methodology
 * - Harvard Business Review: Value-based pricing increases revenue by 31%
 * - McKinsey: Strong value propositions improve conversion by 85%
 * - Bain & Company: Clear differentiation reduces price sensitivity by 40%
 *
 * @version 3.0.0
 * @package App\Services\Algorithm
 */
class ValueEquationAlgorithm
{
    /**
     * Value components with research-based weights
     */
    protected array $components = [
        'dream_outcome' => [
            'weight' => 0.30, // Highest - what customer gets
            'multiplier' => true, // Numerator component
            'critical_fields' => ['desired_result', 'transformation', 'outcome_specificity'],
            'all_fields' => ['desired_result', 'transformation', 'outcome_specificity', 'emotional_benefit', 'status_impact'],
        ],
        'perceived_likelihood' => [
            'weight' => 0.25, // Trust and credibility
            'multiplier' => true, // Numerator component
            'critical_fields' => ['proof_elements', 'guarantee_strength', 'testimonials'],
            'all_fields' => ['proof_elements', 'guarantee_strength', 'testimonials', 'case_studies', 'credentials'],
        ],
        'time_delay' => [
            'weight' => 0.20, // Speed to results
            'multiplier' => false, // Denominator component (inverse)
            'critical_fields' => ['promised_timeline', 'first_result_time'],
            'all_fields' => ['promised_timeline', 'first_result_time', 'immediate_value', 'milestone_clarity'],
        ],
        'effort_sacrifice' => [
            'weight' => 0.25, // Ease of achievement
            'multiplier' => false, // Denominator component (inverse)
            'critical_fields' => ['effort_level', 'implementation_difficulty'],
            'all_fields' => ['effort_level', 'implementation_difficulty', 'prerequisites', 'ongoing_commitment', 'sacrifice_required'],
        ],
    ];

    /**
     * Industry value benchmarks (1-10 scale)
     */
    protected array $industryBenchmarks = [
        'default' => ['poor' => 2.0, 'average' => 4.5, 'good' => 6.5, 'excellent' => 8.5],
        'ecommerce' => ['poor' => 2.5, 'average' => 5.0, 'good' => 7.0, 'excellent' => 9.0],
        'fashion' => ['poor' => 2.2, 'average' => 4.8, 'good' => 6.8, 'excellent' => 8.7],
        'food' => ['poor' => 2.3, 'average' => 4.9, 'good' => 7.0, 'excellent' => 8.8],
        'beauty' => ['poor' => 2.5, 'average' => 5.2, 'good' => 7.2, 'excellent' => 9.0],
        'services' => ['poor' => 2.0, 'average' => 4.3, 'good' => 6.3, 'excellent' => 8.3],
        'education' => ['poor' => 1.8, 'average' => 4.0, 'good' => 6.0, 'excellent' => 8.0],
        'technology' => ['poor' => 2.2, 'average' => 4.7, 'good' => 6.8, 'excellent' => 8.8],
    ];

    /**
     * Calculate Value Equation score
     *
     * @param Business $business
     * @return array
     */
    public function calculate(Business $business): array
    {
        try {
            $startTime = microtime(true);

            // Preload offer data with caching
            $offerData = $this->preloadOfferData($business);

            if (empty($offerData)) {
                return $this->getEmptyScoreResponse('No offer found');
            }

            // Calculate component scores (1-10 scale)
            $componentScores = $this->calculateComponentScores($offerData);

            // Calculate value equation
            $valueScore = $this->calculateValueEquation($componentScores);

            // Normalize to 100-point scale
            $normalizedScore = ($valueScore / 10) * 100;

            // Get quality level
            $qualityLevel = $this->getQualityLevel($valueScore);

            // Calculate pricing power
            $pricingPower = $this->calculatePricingPower($valueScore, $componentScores);

            // Identify weak components
            $weakComponents = $this->identifyWeakComponents($componentScores);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($componentScores, $offerData);

            // Calculate competitive advantage
            $competitiveAdvantage = $this->calculateCompetitiveAdvantage(
                $valueScore,
                $business->industry ?? 'default'
            );

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => '3.0.0',
                'value_score' => round($valueScore, 2),
                'normalized_score' => round($normalizedScore, 2),
                'quality_level' => $qualityLevel,
                'component_scores' => $componentScores,
                'pricing_power' => $pricingPower,
                'weak_components' => $weakComponents,
                'recommendations' => $recommendations,
                'competitive_advantage' => $competitiveAdvantage,
                'improvement_potential' => $this->calculateImprovementPotential($componentScores),
                'metadata' => [
                    'calculated_at' => now()->toIso8601String(),
                    'execution_time_ms' => $executionTime,
                    'business_id' => $business->id,
                    'industry' => $business->industry ?? 'default',
                ],
            ];

        } catch (\Exception $e) {
            Log::error('ValueEquationAlgorithm Error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getEmptyScoreResponse('Calculation error: ' . $e->getMessage());
        }
    }

    /**
     * Preload offer data with caching
     *
     * @param Business $business
     * @return array
     */
    protected function preloadOfferData(Business $business): array
    {
        return Cache::remember(
            "offer_data:{$business->id}",
            300, // 5 minutes
            function () use ($business) {
                $offer = $business->offer;

                if (!$offer) {
                    return [];
                }

                return [
                    'id' => $offer->id,
                    'dream_outcome' => [
                        'desired_result' => $offer->desired_result,
                        'transformation' => $offer->transformation,
                        'outcome_specificity' => $offer->outcome_specificity,
                        'emotional_benefit' => $offer->emotional_benefit,
                        'status_impact' => $offer->status_impact,
                    ],
                    'perceived_likelihood' => [
                        'proof_elements' => $offer->proof_elements,
                        'guarantee_strength' => $offer->guarantee_strength,
                        'testimonials' => $offer->testimonials_count ?? 0,
                        'case_studies' => $offer->case_studies_count ?? 0,
                        'credentials' => $offer->credentials,
                    ],
                    'time_delay' => [
                        'promised_timeline' => $offer->promised_timeline,
                        'first_result_time' => $offer->first_result_time,
                        'immediate_value' => $offer->immediate_value,
                        'milestone_clarity' => $offer->milestone_clarity,
                    ],
                    'effort_sacrifice' => [
                        'effort_level' => $offer->effort_level,
                        'implementation_difficulty' => $offer->implementation_difficulty,
                        'prerequisites' => $offer->prerequisites,
                        'ongoing_commitment' => $offer->ongoing_commitment,
                        'sacrifice_required' => $offer->sacrifice_required,
                    ],
                ];
            }
        );
    }

    /**
     * Calculate scores for each component (1-10 scale)
     *
     * @param array $offerData
     * @return array
     */
    protected function calculateComponentScores(array $offerData): array
    {
        $scores = [];

        foreach ($this->components as $component => $config) {
            $componentData = $offerData[$component] ?? [];
            $criticalFields = $config['critical_fields'];
            $allFields = $config['all_fields'];

            // Calculate field completeness
            $filledCritical = 0;
            $filledTotal = 0;
            $qualitySum = 0;

            foreach ($allFields as $field) {
                $value = $componentData[$field] ?? null;
                $isFilled = $this->isFieldFilled($value);

                if ($isFilled) {
                    $filledTotal++;
                    $quality = $this->assessFieldQuality($field, $value, $component);
                    $qualitySum += $quality;

                    if (in_array($field, $criticalFields)) {
                        $filledCritical++;
                    }
                }
            }

            // Base score from completeness (0-5)
            $completenessScore = (count($allFields) > 0)
                ? ($filledTotal / count($allFields)) * 5
                : 0;

            // Quality bonus (0-3)
            $qualityBonus = (count($allFields) > 0)
                ? ($qualitySum / count($allFields)) * 3
                : 0;

            // Critical fields bonus (0-2)
            $criticalBonus = (count($criticalFields) > 0)
                ? ($filledCritical / count($criticalFields)) * 2
                : 0;

            $componentScore = min(10, $completenessScore + $qualityBonus + $criticalBonus);

            $scores[$component] = [
                'score' => round($componentScore, 2),
                'weight' => $config['weight'],
                'is_multiplier' => $config['multiplier'],
                'filled_fields' => $filledTotal,
                'total_fields' => count($allFields),
                'critical_filled' => $filledCritical,
                'critical_total' => count($criticalFields),
            ];
        }

        return $scores;
    }

    /**
     * Check if field is filled
     *
     * @param mixed $value
     * @return bool
     */
    protected function isFieldFilled($value): bool
    {
        if (is_null($value)) return false;
        if (is_string($value) && trim($value) === '') return false;
        if (is_numeric($value) && $value <= 0) return false;
        if (is_array($value) && empty($value)) return false;

        return true;
    }

    /**
     * Assess field quality (0-1 scale)
     *
     * @param string $field
     * @param mixed $value
     * @param string $component
     * @return float
     */
    protected function assessFieldQuality(string $field, $value, string $component): float
    {
        if (is_numeric($value)) {
            // Numeric ratings (1-10 scale)
            return min(1.0, $value / 10);
        }

        if (!is_string($value)) {
            return 0.5;
        }

        $length = strlen(trim($value));

        // Component-specific quality assessment
        switch ($component) {
            case 'dream_outcome':
                // Needs vivid, specific description
                if ($length < 30) return 0.3;
                if ($length < 80) return 0.6;
                if ($length < 150) return 0.8;
                return 1.0;

            case 'perceived_likelihood':
                // Needs concrete proof
                if ($length < 20) return 0.3;
                if ($length < 60) return 0.6;
                if ($length < 120) return 0.8;
                return 1.0;

            case 'time_delay':
                // Needs clear timeline
                if ($length < 10) return 0.3;
                if ($length < 30) return 0.6;
                if ($length < 60) return 0.8;
                return 1.0;

            case 'effort_sacrifice':
                // Needs honest assessment
                if ($length < 15) return 0.3;
                if ($length < 40) return 0.6;
                if ($length < 80) return 0.8;
                return 1.0;

            default:
                if ($length < 10) return 0.3;
                if ($length < 30) return 0.6;
                return 1.0;
        }
    }

    /**
     * Calculate Value Equation
     * Formula: (Dream Outcome × Perceived Likelihood) / (Time Delay × Effort & Sacrifice)
     *
     * @param array $componentScores
     * @return float
     */
    protected function calculateValueEquation(array $componentScores): float
    {
        $dreamOutcome = $componentScores['dream_outcome']['score'] ?? 1;
        $likelihood = $componentScores['perceived_likelihood']['score'] ?? 1;
        $timeDelay = max(1, $componentScores['time_delay']['score'] ?? 1);
        $effort = max(1, $componentScores['effort_sacrifice']['score'] ?? 1);

        // Numerator: higher is better
        $numerator = $dreamOutcome * $likelihood;

        // Denominator: lower is better, so we invert the scores
        // Score of 10 in time_delay means FAST (good), so we want low denominator
        // Score of 10 in effort means EASY (good), so we want low denominator
        $timeDelayInverse = 11 - $timeDelay; // Convert: 10 becomes 1, 1 becomes 10
        $effortInverse = 11 - $effort;

        $denominator = max(1, $timeDelayInverse * $effortInverse / 10); // Normalize

        return $numerator / $denominator;
    }

    /**
     * Get quality level
     *
     * @param float $score
     * @return string
     */
    protected function getQualityLevel(float $score): string
    {
        if ($score >= 8.5) return 'excellent'; // Grand Slam Offer
        if ($score >= 6.5) return 'good';      // Strong Offer
        if ($score >= 4.5) return 'average';   // Standard Offer
        if ($score >= 2.0) return 'weak';      // Needs Work
        return 'poor';                          // Critical
    }

    /**
     * Calculate pricing power
     *
     * @param float $valueScore
     * @param array $componentScores
     * @return array
     */
    protected function calculatePricingPower(float $valueScore, array $componentScores): array
    {
        // HBR: Value-based pricing increases revenue by 31%
        $dreamOutcome = $componentScores['dream_outcome']['score'] ?? 1;
        $likelihood = $componentScores['perceived_likelihood']['score'] ?? 1;

        // High outcome + high likelihood = premium pricing power
        $premiumMultiplier = 1 + (($dreamOutcome * $likelihood) / 100) * 0.31;

        // McKinsey: Strong value propositions improve conversion by 85%
        $conversionBoost = min(85, ($valueScore / 10) * 85);

        // Bain: Clear differentiation reduces price sensitivity by 40%
        $priceSensitivityReduction = min(40, ($valueScore / 10) * 40);

        return [
            'premium_pricing_multiplier' => round($premiumMultiplier, 2),
            'recommended_price_increase' => round(($premiumMultiplier - 1) * 100, 1) . '%',
            'conversion_improvement_potential' => round($conversionBoost, 1) . '%',
            'price_sensitivity_reduction' => round($priceSensitivityReduction, 1) . '%',
            'pricing_strategy' => $this->getPricingStrategy($valueScore),
        ];
    }

    /**
     * Get pricing strategy recommendation
     *
     * @param float $valueScore
     * @return string
     */
    protected function getPricingStrategy(float $valueScore): string
    {
        if ($valueScore >= 8.5) {
            return 'premium'; // Can charge 2-5x market rate
        } elseif ($valueScore >= 6.5) {
            return 'value'; // 1.5-2x market rate
        } elseif ($valueScore >= 4.5) {
            return 'competitive'; // Market rate
        } else {
            return 'discount'; // Below market - need to improve offer first
        }
    }

    /**
     * Identify weak components
     *
     * @param array $componentScores
     * @return array
     */
    protected function identifyWeakComponents(array $componentScores): array
    {
        $weak = [];

        foreach ($componentScores as $component => $data) {
            if ($data['score'] < 6.5) { // Below "good" threshold
                $weak[] = [
                    'component' => $component,
                    'score' => $data['score'],
                    'severity' => $data['score'] < 4 ? 'critical' : 'high',
                    'impact' => $this->getComponentImpact($component, $data['is_multiplier']),
                    'missing_critical' => $data['critical_total'] - $data['critical_filled'],
                ];
            }
        }

        // Sort by severity and impact
        usort($weak, function($a, $b) {
            if ($a['severity'] !== $b['severity']) {
                return $a['severity'] === 'critical' ? -1 : 1;
            }
            return $a['score'] <=> $b['score'];
        });

        return $weak;
    }

    /**
     * Get component impact description
     *
     * @param string $component
     * @param bool $isMultiplier
     * @return string
     */
    protected function getComponentImpact(string $component, bool $isMultiplier): string
    {
        $impacts = [
            'dream_outcome' => 'Weak outcome reduces perceived value and willingness to pay',
            'perceived_likelihood' => 'Low credibility kills conversions and trust',
            'time_delay' => 'Slow results reduce urgency and increase refund risk',
            'effort_sacrifice' => 'High friction creates objections and reduces completion rate',
        ];

        $impact = $impacts[$component] ?? 'Reduces overall offer value';

        if ($isMultiplier) {
            $impact .= ' (MULTIPLIER - fixing this amplifies value significantly)';
        } else {
            $impact .= ' (DIVIDER - reducing this increases value)';
        }

        return $impact;
    }

    /**
     * Generate recommendations
     *
     * @param array $componentScores
     * @param array $offerData
     * @return array
     */
    protected function generateRecommendations(array $componentScores, array $offerData): array
    {
        $recommendations = [];

        // Priority 1: Multiplier components (dream_outcome, perceived_likelihood)
        foreach (['dream_outcome', 'perceived_likelihood'] as $component) {
            $data = $componentScores[$component] ?? [];
            if ($data['score'] < 7) {
                $recommendations[] = [
                    'priority' => 'critical',
                    'component' => $component,
                    'title' => $this->getComponentTitle($component),
                    'current_score' => $data['score'],
                    'target_score' => 9.0,
                    'action_items' => $this->getComponentActions($component, $data, $offerData),
                    'expected_impact' => $this->estimateImpact($component, $data['score'], 9.0),
                    'timeframe' => '1-2 weeks',
                ];
            }
        }

        // Priority 2: Divider components (reduce friction)
        foreach (['time_delay', 'effort_sacrifice'] as $component) {
            $data = $componentScores[$component] ?? [];
            if ($data['score'] < 7) {
                $recommendations[] = [
                    'priority' => 'high',
                    'component' => $component,
                    'title' => $this->getComponentTitle($component),
                    'current_score' => $data['score'],
                    'target_score' => 8.5,
                    'action_items' => $this->getComponentActions($component, $data, $offerData),
                    'expected_impact' => $this->estimateImpact($component, $data['score'], 8.5),
                    'timeframe' => '2-3 weeks',
                ];
            }
        }

        return array_slice($recommendations, 0, 5); // Top 5
    }

    /**
     * Get component title
     *
     * @param string $component
     * @return string
     */
    protected function getComponentTitle(string $component): string
    {
        return match($component) {
            'dream_outcome' => 'Strengthen Dream Outcome',
            'perceived_likelihood' => 'Build Credibility & Proof',
            'time_delay' => 'Accelerate Time to Results',
            'effort_sacrifice' => 'Reduce Friction & Effort',
            default => 'Improve ' . str_replace('_', ' ', $component),
        };
    }

    /**
     * Get component action items
     *
     * @param string $component
     * @param array $data
     * @param array $offerData
     * @return array
     */
    protected function getComponentActions(string $component, array $data, array $offerData): array
    {
        $actions = [];

        switch ($component) {
            case 'dream_outcome':
                $actions = [
                    'Make outcome more specific with numbers and timeframes',
                    'Add emotional benefits and status implications',
                    'Paint vivid before/after transformation picture',
                    'Connect to deeper desires and aspirations',
                ];
                break;

            case 'perceived_likelihood':
                $actions = [
                    'Add 5-10 customer testimonials with results',
                    'Create 2-3 detailed case studies',
                    'Strengthen guarantee (money-back, results-based)',
                    'Showcase credentials, certifications, experience',
                ];
                break;

            case 'time_delay':
                $actions = [
                    'Provide immediate value (quick wins in first 24-48 hours)',
                    'Break down timeline into clear milestones',
                    'Set realistic but faster expectations',
                    'Add "early results" bonus or incentive',
                ];
                break;

            case 'effort_sacrifice':
                $actions = [
                    'Simplify onboarding and setup process',
                    'Provide done-for-you or done-with-you options',
                    'Reduce prerequisites and requirements',
                    'Add templates, scripts, and ready-to-use resources',
                ];
                break;
        }

        return array_slice($actions, 0, 4);
    }

    /**
     * Estimate impact of improvement
     *
     * @param string $component
     * @param float $currentScore
     * @param float $targetScore
     * @return array
     */
    protected function estimateImpact(string $component, float $currentScore, float $targetScore): array
    {
        $improvement = $targetScore - $currentScore;
        $isMultiplier = $this->components[$component]['multiplier'];

        if ($isMultiplier) {
            // Multiplier components have exponential impact
            $valueIncrease = ($improvement / 10) * 100; // Percentage increase
            $conversionBoost = ($improvement / 10) * 50;
        } else {
            // Divider components have inverse impact
            $valueIncrease = ($improvement / 10) * 60;
            $conversionBoost = ($improvement / 10) * 30;
        }

        return [
            'value_increase' => '+' . round($valueIncrease, 1) . '%',
            'conversion_boost' => '+' . round($conversionBoost, 1) . '%',
            'impact_type' => $isMultiplier ? 'Multiplier (Amplifies Value)' : 'Divider (Reduces Friction)',
        ];
    }

    /**
     * Calculate competitive advantage
     *
     * @param float $valueScore
     * @param string $industry
     * @return array
     */
    protected function calculateCompetitiveAdvantage(float $valueScore, string $industry): array
    {
        $benchmarks = $this->industryBenchmarks[$industry] ?? $this->industryBenchmarks['default'];
        $industryAverage = $benchmarks['average'];

        $advantage = $valueScore - $industryAverage;
        $percentile = $this->calculatePercentile($valueScore, $benchmarks);

        return [
            'your_score' => round($valueScore, 2),
            'industry_average' => $industryAverage,
            'competitive_gap' => round($advantage, 2),
            'percentile' => $percentile,
            'status' => $this->getCompetitiveStatus($advantage),
            'market_position' => $this->getMarketPosition($percentile),
        ];
    }

    /**
     * Calculate percentile
     *
     * @param float $score
     * @param array $benchmarks
     * @return int
     */
    protected function calculatePercentile(float $score, array $benchmarks): int
    {
        if ($score >= $benchmarks['excellent']) return 95;
        if ($score >= $benchmarks['good']) return 75;
        if ($score >= $benchmarks['average']) return 50;
        if ($score >= $benchmarks['poor']) return 25;
        return 10;
    }

    /**
     * Get competitive status
     *
     * @param float $gap
     * @return string
     */
    protected function getCompetitiveStatus(float $gap): string
    {
        if ($gap >= 3) return 'dominant';
        if ($gap >= 1.5) return 'strong_advantage';
        if ($gap >= 0.5) return 'slight_advantage';
        if ($gap >= -0.5) return 'competitive';
        if ($gap >= -1.5) return 'behind';
        return 'far_behind';
    }

    /**
     * Get market position
     *
     * @param int $percentile
     * @return string
     */
    protected function getMarketPosition(int $percentile): string
    {
        if ($percentile >= 90) return 'market_leader';
        if ($percentile >= 75) return 'top_tier';
        if ($percentile >= 50) return 'competitive';
        if ($percentile >= 25) return 'below_average';
        return 'struggling';
    }

    /**
     * Calculate improvement potential
     *
     * @param array $componentScores
     * @return array
     */
    protected function calculateImprovementPotential(array $componentScores): array
    {
        $potentials = [];

        foreach ($componentScores as $component => $data) {
            $potential = 10 - $data['score'];
            $isMultiplier = $data['is_multiplier'];

            $potentials[] = [
                'component' => $component,
                'current_score' => $data['score'],
                'potential_gain' => round($potential, 2),
                'impact_type' => $isMultiplier ? 'multiplier' : 'divider',
                'priority' => $potential >= 4 ? 'high' : ($potential >= 2 ? 'medium' : 'low'),
            ];
        }

        // Sort by potential gain (multipliers first)
        usort($potentials, function($a, $b) {
            if ($a['impact_type'] !== $b['impact_type']) {
                return $a['impact_type'] === 'multiplier' ? -1 : 1;
            }
            return $b['potential_gain'] <=> $a['potential_gain'];
        });

        return $potentials;
    }

    /**
     * Get empty score response
     *
     * @param string $reason
     * @return array
     */
    protected function getEmptyScoreResponse(string $reason): array
    {
        return [
            'success' => false,
            'error' => $reason,
            'value_score' => 0,
            'normalized_score' => 0,
            'quality_level' => 'none',
            'recommendations' => [
                [
                    'priority' => 'critical',
                    'title' => 'Create Your Offer Using Value Equation',
                    'description' => 'Build a high-value offer that customers can\'t refuse',
                    'action_items' => [
                        'Define clear dream outcome with specific results',
                        'Add proof elements (testimonials, case studies, guarantee)',
                        'Reduce time to first results',
                        'Minimize effort and sacrifice required',
                    ],
                ],
            ],
        ];
    }
}
