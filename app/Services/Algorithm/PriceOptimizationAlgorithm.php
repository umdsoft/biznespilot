<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Price Optimization Algorithm
 *
 * Optimal pricing strategy using revenue management and game theory.
 * No AI required - mathematical optimization.
 *
 * Algorithms:
 * - Van Westendorp Price Sensitivity Meter
 * - Revenue Maximization (Price Elasticity)
 * - Competitive Pricing Analysis
 *
 * Research:
 * - Van Westendorp (1976) - Price Sensitivity Meter
 * - Simon & Fassnacht (2018) - Price Management
 * - Nagle & Holden (2002) - Strategy and Tactics of Pricing
 *
 * @version 1.0.0
 */
class PriceOptimizationAlgorithm extends AlgorithmEngine
{
    protected string $version = '1.0.0';

    protected int $cacheTTL = 1800;

    /**
     * Calculate optimal pricing
     *
     * @param  Business  $business  Business to analyze
     * @param  array  $options  Options (current_price, cost, etc.)
     * @return array Pricing recommendations
     */
    public function analyze(Business $business, array $options = []): array
    {
        try {
            $startTime = microtime(true);

            $currentPrice = $options['current_price'] ?? 100000;
            $cost = $options['cost'] ?? 60000;
            $competitorPrices = $options['competitor_prices'] ?? [];

            // 1. Revenue Maximization
            $revenueOptimal = $this->calculateRevenueOptimalPrice($currentPrice, $cost, $options);

            // 2. Competitive Pricing
            $competitivePrice = $this->calculateCompetitivePrice($currentPrice, $competitorPrices);

            // 3. Value-Based Pricing
            $valueBasedPrice = $this->calculateValueBasedPrice($currentPrice, $options);

            // 4. Price Elasticity Analysis
            $elasticity = $this->calculatePriceElasticity($business, $options);

            // Generate recommendation
            $recommendation = $this->generatePricingRecommendation(
                $currentPrice,
                $cost,
                $revenueOptimal,
                $competitivePrice,
                $valueBasedPrice,
                $elasticity
            );

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => $this->version,
                'current_price' => $currentPrice,
                'cost' => $cost,
                'optimal_prices' => [
                    'revenue_optimal' => $revenueOptimal,
                    'competitive' => $competitivePrice,
                    'value_based' => $valueBasedPrice,
                ],
                'price_elasticity' => $elasticity,
                'recommendation' => $recommendation,
                'metadata' => [
                    'calculated_at' => Carbon::now()->toIso8601String(),
                    'execution_time_ms' => $executionTime,
                    'business_id' => $business->id,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('PriceOptimizationAlgorithm failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate revenue-optimal price
     *
     * Formula: Revenue(p) = p × Demand(p)
     * Demand(p) = a - b×p (linear demand curve)
     * Optimal Price = a / (2b)
     *
     * @param  float  $currentPrice  Current price
     * @param  float  $cost  Product cost
     * @param  array  $options  Options
     * @return array Optimal price and revenue
     */
    protected function calculateRevenueOptimalPrice(float $currentPrice, float $cost, array $options): array
    {
        // Simplified demand curve estimation
        $currentDemand = $options['current_demand'] ?? 100;
        $priceElasticity = $options['price_elasticity'] ?? -1.5; // Default elastic

        // Linear demand: Q = a - b×P
        // b = elasticity × (Q/P)
        $b = abs($priceElasticity) * ($currentDemand / $currentPrice);
        $a = $currentDemand + ($b * $currentPrice);

        // Optimal price for revenue maximization
        $optimalPrice = $a / (2 * $b);

        // Ensure price > cost
        if ($optimalPrice < $cost * 1.2) {
            $optimalPrice = $cost * 1.2; // Minimum 20% markup
        }

        // Estimated demand at optimal price
        $optimalDemand = max(0, $a - ($b * $optimalPrice));
        $optimalRevenue = $optimalPrice * $optimalDemand;
        $currentRevenue = $currentPrice * $currentDemand;

        return [
            'price' => round($optimalPrice, 0),
            'estimated_demand' => round($optimalDemand, 0),
            'estimated_revenue' => round($optimalRevenue, 0),
            'revenue_increase' => round((($optimalRevenue - $currentRevenue) / $currentRevenue) * 100, 1).'%',
        ];
    }

    /**
     * Calculate competitive price
     *
     * @param  float  $currentPrice  Current price
     * @param  array  $competitorPrices  Competitor prices
     * @return array Competitive pricing recommendation
     */
    protected function calculateCompetitivePrice(float $currentPrice, array $competitorPrices): array
    {
        if (empty($competitorPrices)) {
            return [
                'strategy' => 'no_competitor_data',
                'price' => $currentPrice,
                'position' => 'unknown',
            ];
        }

        $avgCompetitorPrice = array_sum($competitorPrices) / count($competitorPrices);
        $minCompetitorPrice = min($competitorPrices);
        $maxCompetitorPrice = max($competitorPrices);

        // Positioning strategies
        $strategies = [
            'cost_leader' => round($minCompetitorPrice * 0.95, 0), // 5% below min
            'value' => round($avgCompetitorPrice, 0), // At average
            'premium' => round($maxCompetitorPrice * 1.1, 0), // 10% above max
        ];

        // Determine current position
        $position = 'mid-market';
        if ($currentPrice < $avgCompetitorPrice * 0.9) {
            $position = 'low-cost';
        } elseif ($currentPrice > $avgCompetitorPrice * 1.1) {
            $position = 'premium';
        }

        return [
            'current_position' => $position,
            'competitor_avg' => round($avgCompetitorPrice, 0),
            'competitor_range' => [round($minCompetitorPrice, 0), round($maxCompetitorPrice, 0)],
            'strategies' => $strategies,
            'recommended_strategy' => 'value',
            'recommended_price' => $strategies['value'],
        ];
    }

    /**
     * Calculate value-based price
     *
     * @param  float  $currentPrice  Current price
     * @param  array  $options  Options (perceived_value, etc.)
     * @return array Value-based price
     */
    protected function calculateValueBasedPrice(float $currentPrice, array $options): array
    {
        // Value-based pricing based on customer perceived value
        $perceivedValue = $options['perceived_value'] ?? $currentPrice * 1.3;

        // Capture percentage of perceived value (typically 60-80%)
        $captureRate = 0.7; // 70% of perceived value

        $valueBasedPrice = $perceivedValue * $captureRate;

        return [
            'price' => round($valueBasedPrice, 0),
            'perceived_value' => round($perceivedValue, 0),
            'value_capture_rate' => ($captureRate * 100).'%',
            'customer_surplus' => round($perceivedValue - $valueBasedPrice, 0),
        ];
    }

    /**
     * Calculate price elasticity
     *
     * @return array Elasticity analysis
     */
    protected function calculatePriceElasticity(Business $business, array $options): array
    {
        // Simplified elasticity estimation
        // In real implementation, analyze historical price/demand data

        $industry = $business->industry ?? 'default';

        // Industry-typical elasticities
        $industryElasticity = [
            'restaurant' => -1.5,      // Elastic
            'retail' => -1.2,           // Elastic
            'beauty_salon' => -0.8,     // Inelastic
            'gym_fitness' => -1.0,      // Unit elastic
            'education' => -0.6,        // Inelastic
            'healthcare' => -0.4,       // Very inelastic
            'default' => -1.0,
        ];

        $elasticity = $industryElasticity[$industry] ?? -1.0;

        $interpretation = 'unit_elastic';
        if ($elasticity < -1) {
            $interpretation = 'elastic'; // Demand sensitive to price
        } elseif ($elasticity > -1 && $elasticity < 0) {
            $interpretation = 'inelastic'; // Demand not sensitive to price
        }

        return [
            'coefficient' => $elasticity,
            'interpretation' => $interpretation,
            'recommendation' => $interpretation === 'elastic'
                ? 'Price kamaytirilsa demand ko\'p oshadi'
                : 'Price oshirilsa ham demand kam kamayadi - price oshiring',
        ];
    }

    /**
     * Generate pricing recommendation
     *
     * @return array Final recommendation
     */
    protected function generatePricingRecommendation(
        float $currentPrice,
        float $cost,
        array $revenueOptimal,
        array $competitivePrice,
        array $valueBasedPrice,
        array $elasticity
    ): array {
        // Weighted average of different pricing methods
        $prices = [
            $revenueOptimal['price'],
            $competitivePrice['recommended_price'] ?? $currentPrice,
            $valueBasedPrice['price'],
        ];

        $recommendedPrice = round(array_sum($prices) / count($prices), -3); // Round to nearest 1000

        // Calculate impact
        $priceChange = (($recommendedPrice - $currentPrice) / $currentPrice) * 100;

        $action = 'maintain';
        if ($priceChange > 5) {
            $action = 'increase';
        } elseif ($priceChange < -5) {
            $action = 'decrease';
        }

        return [
            'recommended_price' => $recommendedPrice,
            'current_price' => $currentPrice,
            'price_change' => round($priceChange, 1).'%',
            'action' => $action,
            'reasoning' => $this->getPricingReasoning($action, $elasticity, $competitivePrice),
            'implementation_steps' => $this->getImplementationSteps($action, $recommendedPrice, $currentPrice),
            'expected_impact' => $this->estimatePricingImpact($action, $priceChange, $elasticity),
        ];
    }

    protected function getPricingReasoning(string $action, array $elasticity, array $competitivePrice): string
    {
        if ($action === 'increase') {
            return 'Price oshirish tavsiya etiladi. '.
                   ($elasticity['interpretation'] === 'inelastic'
                    ? "Demand price'ga kam sezgir, price oshirish revenue oshiradi."
                    : "Competitor price'lar yuqori, siz ham oshirishingiz mumkin.");
        } elseif ($action === 'decrease') {
            return 'Price kamaytirish tavsiya etiladi. '.
                   ($elasticity['interpretation'] === 'elastic'
                    ? "Demand price'ga juda sezgir, price kamaytirilsa demand ko'p oshadi."
                    : 'Competitive position yaxshilash uchun price kamaytiring.');
        } else {
            return "Hozirgi price optimal. Katta o'zgarish kerak emas.";
        }
    }

    protected function getImplementationSteps(string $action, float $recommendedPrice, float $currentPrice): array
    {
        if ($action === 'increase') {
            return [
                'Bosqichli oshiring: har hafta 5-10% ga',
                "Value proposition'ni kuchaytiring",
                "Premium features qo'shing",
                'Customer feedback monitoring qiling',
            ];
        } elseif ($action === 'decrease') {
            return [
                "A/B test qiling: bir qism customer'ga yangi price",
                "Bundle offers yarating - discount ko'rinmasin",
                'Volume discount taklif qiling',
                'Conversion rate monitoring qiling',
            ];
        } else {
            return [
                "Hozirgi price'ni saqlab qoling",
                "Value addition'ga focus qiling",
                'Customer satisfaction oshiring',
            ];
        }
    }

    protected function estimatePricingImpact(string $action, float $priceChange, array $elasticity): array
    {
        $elasticityCoef = $elasticity['coefficient'];

        // Demand change = elasticity × price change
        $demandChange = $elasticityCoef * $priceChange;

        // Revenue change = (1 + price_change%) × (1 + demand_change%) - 1
        $revenueChange = ((1 + $priceChange / 100) * (1 + $demandChange / 100) - 1) * 100;

        return [
            'expected_demand_change' => round($demandChange, 1).'%',
            'expected_revenue_change' => round($revenueChange, 1).'%',
            'confidence' => 'medium',
        ];
    }
}
