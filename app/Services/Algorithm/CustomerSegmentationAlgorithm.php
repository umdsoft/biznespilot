<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use App\Services\Algorithm\Math\Clustering;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Customer Segmentation Algorithm
 *
 * Segments customers using K-Means clustering (no AI required).
 * Groups customers by behavior, value, and engagement patterns.
 *
 * Algorithm: K-Means Clustering
 * Research:
 * - MacQueen (1967) - K-Means clustering
 * - RFM Analysis - Recency, Frequency, Monetary (Hughes, 1994)
 * - Customer Lifetime Value segmentation (Gupta et al., 2006)
 *
 * Segmentation Features:
 * 1. RFM Score (Recency, Frequency, Monetary)
 * 2. Customer Lifetime Value (CLV)
 * 3. Engagement Score
 * 4. Purchase Behavior
 * 5. Churn Risk
 *
 * @version 1.0.0
 * @package App\Services\Algorithm
 */
class CustomerSegmentationAlgorithm extends AlgorithmEngine
{
    /**
     * Algorithm version
     */
    protected string $version = '1.0.0';

    /**
     * Cache TTL (30 minutes)
     */
    protected int $cacheTTL = 1800;

    /**
     * Number of customer segments
     */
    protected int $numberOfSegments = 4;

    /**
     * Segment labels based on characteristics
     */
    protected array $segmentLabels = [
        'champions' => 'Champions - Eng yaxshi mijozlar',
        'loyal' => 'Loyal Customers - Sodiq mijozlar',
        'potential' => 'Potential Loyalists - Potentsial sodiq mijozlar',
        'at_risk' => 'At Risk - Xavf ostida',
        'hibernating' => 'Hibernating - Faol emas',
        'lost' => 'Lost - Yo\'qotilgan mijozlar',
    ];

    /**
     * Perform customer segmentation
     *
     * @param Business $business Business to analyze
     * @param array $options Additional options
     * @return array Segmentation results
     */
    public function analyze(Business $business, array $options = []): array
    {
        try {
            $startTime = microtime(true);

            // Collect customer data
            $customers = $this->getCustomerData($business, $options);

            if (empty($customers)) {
                return $this->emptyResult();
            }

            // Extract features for clustering
            $features = $this->extractFeatures($customers);

            // Normalize features
            $normalization = Clustering::normalize($features);
            $normalizedFeatures = $normalization['normalized'];

            // Perform K-Means clustering
            $k = $options['k'] ?? $this->numberOfSegments;
            $clusteringResult = Clustering::kMeans($normalizedFeatures, $k);

            // Label segments based on characteristics
            $labeledSegments = $this->labelSegments($clusteringResult, $customers, $features);

            // Calculate segment statistics
            $statistics = $this->calculateSegmentStatistics($labeledSegments);

            // Generate recommendations per segment
            $recommendations = $this->generateRecommendations($labeledSegments);

            // Calculate segmentation quality
            $silhouetteScore = Clustering::silhouetteScore(
                $normalizedFeatures,
                $clusteringResult['assignments']
            );

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => $this->version,
                'segments' => $labeledSegments,
                'statistics' => $statistics,
                'recommendations' => $recommendations,
                'quality_metrics' => [
                    'silhouette_score' => round($silhouetteScore, 3),
                    'quality_level' => $this->getQualityLevel($silhouetteScore),
                    'iterations' => $clusteringResult['iterations'],
                    'converged' => $clusteringResult['converged'],
                ],
                'metadata' => [
                    'calculated_at' => Carbon::now()->toIso8601String(),
                    'execution_time_ms' => $executionTime,
                    'business_id' => $business->id,
                    'customers_analyzed' => count($customers),
                    'number_of_segments' => $k,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('CustomerSegmentationAlgorithm failed', [
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
     * Get customer data
     *
     * @param Business $business
     * @param array $options
     * @return array Customer records
     */
    protected function getCustomerData(Business $business, array $options = []): array
    {
        $customers = [];

        // Get sales data
        $sales = $business->sales ?? collect();

        // Group by customer
        $customerMap = [];

        foreach ($sales as $sale) {
            $customerId = $sale->customer_id ?? 'GUEST_' . $sale->id;

            if (!isset($customerMap[$customerId])) {
                $customerMap[$customerId] = [
                    'customer_id' => $customerId,
                    'purchases' => [],
                    'total_revenue' => 0,
                    'first_purchase' => null,
                    'last_purchase' => null,
                ];
            }

            $amount = $sale->amount ?? 0;
            $purchaseDate = $sale->created_at ?? now();

            $customerMap[$customerId]['purchases'][] = [
                'amount' => $amount,
                'date' => $purchaseDate,
            ];

            $customerMap[$customerId]['total_revenue'] += $amount;

            // Track first and last purchase
            if (empty($customerMap[$customerId]['first_purchase']) ||
                $purchaseDate < $customerMap[$customerId]['first_purchase']) {
                $customerMap[$customerId]['first_purchase'] = $purchaseDate;
            }

            if (empty($customerMap[$customerId]['last_purchase']) ||
                $purchaseDate > $customerMap[$customerId]['last_purchase']) {
                $customerMap[$customerId]['last_purchase'] = $purchaseDate;
            }
        }

        $customers = array_values($customerMap);

        // If no real data, use synthetic
        if (empty($customers) && ($options['use_synthetic'] ?? true)) {
            $customers = $this->generateSyntheticCustomers();
        }

        return $customers;
    }

    /**
     * Extract features from customer data
     *
     * Features: [Recency, Frequency, Monetary, Engagement, Tenure]
     *
     * @param array $customers Customer data
     * @return array Feature vectors
     */
    protected function extractFeatures(array $customers): array
    {
        $features = [];
        $now = Carbon::now();

        foreach ($customers as $customer) {
            // Recency: Days since last purchase (lower is better)
            $recency = 0;
            if (!empty($customer['last_purchase'])) {
                $lastPurchase = Carbon::parse($customer['last_purchase']);
                $recency = $now->diffInDays($lastPurchase);
            }

            // Frequency: Number of purchases
            $frequency = count($customer['purchases'] ?? []);

            // Monetary: Total revenue
            $monetary = $customer['total_revenue'] ?? 0;

            // Engagement: Purchase frequency per month active
            $tenure = 1;
            if (!empty($customer['first_purchase'])) {
                $firstPurchase = Carbon::parse($customer['first_purchase']);
                $tenure = max(1, $now->diffInMonths($firstPurchase));
            }
            $engagement = $frequency / $tenure;

            // Tenure: Months as customer
            $tenureScore = $tenure;

            $features[] = [
                $recency,           // Feature 0
                $frequency,         // Feature 1
                $monetary,          // Feature 2
                $engagement,        // Feature 3
                $tenureScore,       // Feature 4
            ];
        }

        return $features;
    }

    /**
     * Label segments based on cluster characteristics
     *
     * @param array $clusteringResult Clustering result
     * @param array $customers Customer data
     * @param array $features Feature vectors
     * @return array Labeled segments
     */
    protected function labelSegments(
        array $clusteringResult,
        array $customers,
        array $features
    ): array {
        $segments = [];

        foreach ($clusteringResult['clusters'] as $cluster) {
            $clusterId = $cluster['cluster_id'];
            $pointIndices = $cluster['points'];

            // Calculate cluster characteristics
            $characteristics = $this->calculateClusterCharacteristics(
                $pointIndices,
                $customers,
                $features
            );

            // Assign label based on characteristics
            $label = $this->assignLabel($characteristics);

            // Get customer list
            $customerList = [];
            foreach ($pointIndices as $idx) {
                $customerList[] = [
                    'customer_id' => $customers[$idx]['customer_id'],
                    'total_revenue' => $customers[$idx]['total_revenue'],
                    'purchase_count' => count($customers[$idx]['purchases']),
                    'last_purchase' => $customers[$idx]['last_purchase'] ?? null,
                ];
            }

            $segments[] = [
                'segment_id' => $clusterId,
                'label' => $label,
                'description' => $this->segmentLabels[$label] ?? $label,
                'size' => $cluster['size'],
                'characteristics' => $characteristics,
                'customers' => array_slice($customerList, 0, 10), // Top 10 per segment
            ];
        }

        // Sort segments by value (monetary)
        usort($segments, function($a, $b) {
            return $b['characteristics']['avg_monetary'] <=> $a['characteristics']['avg_monetary'];
        });

        return $segments;
    }

    /**
     * Calculate cluster characteristics
     *
     * @param array $pointIndices Indices of points in cluster
     * @param array $customers Customer data
     * @param array $features Feature vectors
     * @return array Characteristics
     */
    protected function calculateClusterCharacteristics(
        array $pointIndices,
        array $customers,
        array $features
    ): array {
        if (empty($pointIndices)) {
            return [
                'avg_recency' => 0,
                'avg_frequency' => 0,
                'avg_monetary' => 0,
                'avg_engagement' => 0,
                'avg_tenure' => 0,
            ];
        }

        $totalRecency = 0;
        $totalFrequency = 0;
        $totalMonetary = 0;
        $totalEngagement = 0;
        $totalTenure = 0;

        foreach ($pointIndices as $idx) {
            $feature = $features[$idx];
            $totalRecency += $feature[0];
            $totalFrequency += $feature[1];
            $totalMonetary += $feature[2];
            $totalEngagement += $feature[3];
            $totalTenure += $feature[4];
        }

        $count = count($pointIndices);

        return [
            'avg_recency' => round($totalRecency / $count, 1),
            'avg_frequency' => round($totalFrequency / $count, 1),
            'avg_monetary' => round($totalMonetary / $count, 2),
            'avg_engagement' => round($totalEngagement / $count, 2),
            'avg_tenure' => round($totalTenure / $count, 1),
        ];
    }

    /**
     * Assign label based on RFM characteristics
     *
     * @param array $characteristics Cluster characteristics
     * @return string Segment label
     */
    protected function assignLabel(array $characteristics): string
    {
        $recency = $characteristics['avg_recency'];
        $frequency = $characteristics['avg_frequency'];
        $monetary = $characteristics['avg_monetary'];
        $engagement = $characteristics['avg_engagement'];

        // Champions: Low recency, high frequency, high monetary
        if ($recency < 30 && $frequency >= 10 && $monetary >= 50000) {
            return 'champions';
        }

        // Loyal: Low recency, medium+ frequency
        if ($recency < 60 && $frequency >= 5) {
            return 'loyal';
        }

        // Potential: Low recency, low frequency (new or occasional)
        if ($recency < 60 && $frequency < 5) {
            return 'potential';
        }

        // At Risk: Medium recency, was frequent
        if ($recency >= 60 && $recency < 120 && $frequency >= 5) {
            return 'at_risk';
        }

        // Hibernating: High recency, low frequency
        if ($recency >= 120 && $recency < 180) {
            return 'hibernating';
        }

        // Lost: Very high recency
        if ($recency >= 180) {
            return 'lost';
        }

        // Default: potential
        return 'potential';
    }

    /**
     * Calculate segment statistics
     *
     * @param array $segments Labeled segments
     * @return array Statistics
     */
    protected function calculateSegmentStatistics(array $segments): array
    {
        $totalCustomers = array_sum(array_column($segments, 'size'));
        $totalRevenue = 0;

        foreach ($segments as $segment) {
            $totalRevenue += $segment['characteristics']['avg_monetary'] * $segment['size'];
        }

        $statistics = [];

        foreach ($segments as $segment) {
            $segmentRevenue = $segment['characteristics']['avg_monetary'] * $segment['size'];

            $statistics[$segment['label']] = [
                'customer_count' => $segment['size'],
                'percentage' => $totalCustomers > 0 ? round(($segment['size'] / $totalCustomers) * 100, 1) . '%' : '0%',
                'total_revenue' => round($segmentRevenue, 2),
                'revenue_percentage' => $totalRevenue > 0 ? round(($segmentRevenue / $totalRevenue) * 100, 1) . '%' : '0%',
                'avg_customer_value' => round($segment['characteristics']['avg_monetary'], 2),
            ];
        }

        return [
            'segments' => $statistics,
            'total_customers' => $totalCustomers,
            'total_revenue' => round($totalRevenue, 2),
        ];
    }

    /**
     * Generate recommendations per segment
     *
     * @param array $segments Labeled segments
     * @return array Recommendations
     */
    protected function generateRecommendations(array $segments): array
    {
        $recommendations = [];

        foreach ($segments as $segment) {
            $label = $segment['label'];
            $size = $segment['size'];

            switch ($label) {
                case 'champions':
                    $recommendations[] = [
                        'segment' => 'Champions',
                        'priority' => 'high',
                        'title' => 'VIP dasturini yarating',
                        'description' => "{$size} ta top mijozingiz bor. Ularni saqlab qoling!",
                        'action_items' => [
                            'Maxsus VIP rewards program yarating',
                            'Exclusive early access bering',
                            'Personal account manager tayinlang',
                            'Referral bonuses taklif qiling',
                        ],
                    ];
                    break;

                case 'loyal':
                    $recommendations[] = [
                        'segment' => 'Loyal Customers',
                        'priority' => 'high',
                        'title' => 'Loyalty dasturini kuchaytiring',
                        'description' => "{$size} ta sodiq mijoz - ularni Champions ga o'tkazing",
                        'action_items' => [
                            'Loyalty points program boshlang',
                            'Upsell va cross-sell qiling',
                            'Premium tier taklif qiling',
                        ],
                    ];
                    break;

                case 'potential':
                    $recommendations[] = [
                        'segment' => 'Potential Loyalists',
                        'priority' => 'medium',
                        'title' => 'Nurturing campaign',
                        'description' => "{$size} ta potentsial mijoz - ularni rivojlantiring",
                        'action_items' => [
                            'Onboarding emaillar yuboring',
                            'Educational content taqdim eting',
                            'Second purchase incentive bering',
                        ],
                    ];
                    break;

                case 'at_risk':
                    $recommendations[] = [
                        'segment' => 'At Risk',
                        'priority' => 'critical',
                        'title' => 'Win-back campaign zarur',
                        'description' => "{$size} ta mijoz yo'qotish xavfida!",
                        'action_items' => [
                            'Personalized win-back emails yuboring',
                            'Special discount taklif qiling (15-20%)',
                            'Feedback so\'rang - nima muammo?',
                        ],
                    ];
                    break;

                case 'hibernating':
                    $recommendations[] = [
                        'segment' => 'Hibernating',
                        'priority' => 'medium',
                        'title' => 'Re-engagement campaign',
                        'description' => "{$size} ta nofaol mijoz",
                        'action_items' => [
                            'Survey yuboring - nega faol emaslar?',
                            'New products/features haqida xabar bering',
                            'Limited-time offer yuborin',
                        ],
                    ];
                    break;

                case 'lost':
                    $recommendations[] = [
                        'segment' => 'Lost Customers',
                        'priority' => 'low',
                        'title' => 'Last attempt win-back',
                        'description' => "{$size} ta yo'qotilgan mijoz",
                        'action_items' => [
                            'Final win-back email seriyasi',
                            'Aggressive discount (20-30%)',
                            'Agar qaytmasalar, list dan olib tashlang',
                        ],
                    ];
                    break;
            }
        }

        return $recommendations;
    }

    /**
     * Get quality level from silhouette score
     *
     * @param float $score Silhouette score (-1 to +1)
     * @return string Quality level
     */
    protected function getQualityLevel(float $score): string
    {
        if ($score >= 0.7) return 'excellent';
        if ($score >= 0.5) return 'good';
        if ($score >= 0.3) return 'fair';
        if ($score >= 0.0) return 'weak';
        return 'poor';
    }

    /**
     * Generate synthetic customers for demo
     *
     * @return array Synthetic customer data
     */
    protected function generateSyntheticCustomers(): array
    {
        $customers = [];

        for ($i = 1; $i <= 100; $i++) {
            // Create varied customer profiles
            $profile = rand(1, 100);

            if ($profile <= 10) {
                // Champions (10%)
                $numPurchases = rand(15, 30);
                $avgAmount = rand(50000, 150000);
                $daysSinceLast = rand(1, 20);
            } elseif ($profile <= 30) {
                // Loyal (20%)
                $numPurchases = rand(6, 14);
                $avgAmount = rand(30000, 80000);
                $daysSinceLast = rand(10, 45);
            } elseif ($profile <= 55) {
                // Potential (25%)
                $numPurchases = rand(1, 5);
                $avgAmount = rand(20000, 50000);
                $daysSinceLast = rand(5, 50);
            } elseif ($profile <= 75) {
                // At Risk (20%)
                $numPurchases = rand(5, 12);
                $avgAmount = rand(25000, 70000);
                $daysSinceLast = rand(70, 110);
            } elseif ($profile <= 90) {
                // Hibernating (15%)
                $numPurchases = rand(2, 6);
                $avgAmount = rand(15000, 40000);
                $daysSinceLast = rand(130, 170);
            } else {
                // Lost (10%)
                $numPurchases = rand(1, 4);
                $avgAmount = rand(10000, 30000);
                $daysSinceLast = rand(190, 365);
            }

            $purchases = [];
            $totalRevenue = 0;

            for ($p = 0; $p < $numPurchases; $p++) {
                $amount = $avgAmount * (rand(70, 130) / 100);
                $daysAgo = $daysSinceLast + ($p * rand(10, 40));

                $purchases[] = [
                    'amount' => $amount,
                    'date' => Carbon::now()->subDays($daysAgo),
                ];

                $totalRevenue += $amount;
            }

            usort($purchases, function($a, $b) {
                return $a['date'] <=> $b['date'];
            });

            $customers[] = [
                'customer_id' => 'CUST_' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'purchases' => $purchases,
                'total_revenue' => $totalRevenue,
                'first_purchase' => $purchases[0]['date'] ?? now(),
                'last_purchase' => end($purchases)['date'] ?? now(),
            ];
        }

        return $customers;
    }

    /**
     * Return empty result structure
     *
     * @return array Empty result
     */
    protected function emptyResult(): array
    {
        return [
            'success' => true,
            'version' => $this->version,
            'segments' => [],
            'statistics' => ['total_customers' => 0, 'total_revenue' => 0],
            'recommendations' => [],
            'metadata' => [
                'calculated_at' => Carbon::now()->toIso8601String(),
                'customers_analyzed' => 0,
            ],
        ];
    }
}
