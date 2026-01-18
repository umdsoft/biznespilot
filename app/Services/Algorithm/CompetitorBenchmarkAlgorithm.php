<?php

namespace App\Services\Algorithm;

use App\Models\Business;

/**
 * Competitor Benchmark Algorithm
 *
 * Raqobatchilar bilan solishtirish va pozitsiyani aniqlash algoritmi.
 *
 * Formulalar:
 * - Position Score = (Our Value - Competitor Avg) / Competitor Avg × 100
 * - Market Share = Our Value / Sum(All Values) × 100
 * - Competitive Gap = Competitor Best - Our Value
 *
 * @version 2.0.0
 */
class CompetitorBenchmarkAlgorithm extends AlgorithmEngine
{
    protected string $cachePrefix = 'competitor_bench_';

    protected int $cacheTTL = 3600; // 1 hour

    /**
     * Metrics to compare
     */
    protected array $metricsToCompare = [
        'followers' => ['label' => 'Followerlar', 'higher_better' => true],
        'engagement_rate' => ['label' => 'Engagement Rate', 'higher_better' => true],
        'post_frequency' => ['label' => 'Post chastotasi', 'higher_better' => true],
        'content_quality' => ['label' => 'Kontent sifati', 'higher_better' => true],
        'response_time' => ['label' => 'Javob tezligi', 'higher_better' => false],
        'price_positioning' => ['label' => 'Narx pozitsiyasi', 'higher_better' => false],
    ];

    /**
     * Calculate competitor benchmark
     */
    public function calculate(Business $business, array $industryBenchmarks = []): array
    {
        // Get competitors data
        $competitors = $this->getCompetitorsData($business);

        if (empty($competitors)) {
            return $this->getNoCompetitorsResult($business, $industryBenchmarks);
        }

        // Get our metrics
        $ourMetrics = $this->getOurMetrics($business);

        // Calculate comparisons
        $comparisons = $this->calculateComparisons($ourMetrics, $competitors);

        // Calculate position score
        $positionScore = $this->calculatePositionScore($comparisons);

        // Get strengths and weaknesses
        $strengths = $this->identifyStrengths($comparisons);
        $weaknesses = $this->identifyWeaknesses($comparisons);

        // Calculate market position
        $marketPosition = $this->calculateMarketPosition($ourMetrics, $competitors);

        // Trend analysis
        $trends = $this->analyzeCompetitorTrends($competitors);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($comparisons, $weaknesses);

        return [
            'score' => $positionScore,
            'status' => $this->getPositionStatus($positionScore),
            'competitors_analyzed' => count($competitors),
            'our_metrics' => $ourMetrics,
            'comparisons' => $comparisons,
            'market_position' => $marketPosition,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'trends' => $trends,
            'opportunities' => $this->identifyOpportunities($comparisons, $trends),
            'threats' => $this->identifyThreats($comparisons, $trends),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Get competitors data
     */
    protected function getCompetitorsData(Business $business): array
    {
        try {
            $competitors = $business->competitors()->get();

            return $competitors->map(function ($competitor) {
                return [
                    'id' => $competitor->id,
                    'name' => $competitor->name,
                    'metrics' => [
                        'followers' => $competitor->instagram_followers ?? 0,
                        'engagement_rate' => $competitor->engagement_rate ?? 0,
                        'post_frequency' => $competitor->post_frequency ?? 0,
                        'content_quality' => $competitor->content_quality_score ?? 50,
                        'response_time' => $competitor->response_time_hours ?? 24,
                        'price_positioning' => $competitor->price_level ?? 5, // 1-10
                    ],
                    'strengths' => $competitor->strengths ?? [],
                    'weaknesses' => $competitor->weaknesses ?? [],
                    'last_updated' => $competitor->updated_at?->diffForHumans(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get our metrics
     */
    protected function getOurMetrics(Business $business): array
    {
        $instagram = $business->instagramAccounts()->first();

        return [
            'followers' => $instagram?->followers_count ?? 0,
            'engagement_rate' => $instagram ? $this->calculateER($instagram) : 0,
            'post_frequency' => $this->calculatePostFrequency($business),
            'content_quality' => $this->estimateContentQuality($business),
            'response_time' => $this->getResponseTime($business),
            'price_positioning' => $this->getPricePositioning($business),
        ];
    }

    /**
     * Calculate engagement rate
     */
    protected function calculateER($instagram): float
    {
        $followers = $instagram->followers_count ?? 0;
        if ($followers === 0) {
            return 0;
        }

        $avgLikes = $instagram->metrics['avg_likes'] ?? 0;
        $avgComments = $instagram->metrics['avg_comments'] ?? 0;

        return round((($avgLikes + $avgComments) / $followers) * 100, 2);
    }

    /**
     * Calculate post frequency
     */
    protected function calculatePostFrequency(Business $business): float
    {
        $instagram = $business->instagramAccounts()->first();
        if (! $instagram) {
            return 0;
        }

        $postsCount = $instagram->media_count ?? 0;

        // Estimate posts per week (assume 3 months of activity)
        return round($postsCount / 12, 1);
    }

    /**
     * Estimate content quality
     */
    protected function estimateContentQuality(Business $business): int
    {
        $instagram = $business->instagramAccounts()->first();
        if (! $instagram) {
            return 50;
        }

        // Base score
        $score = 50;

        // Engagement rate bonus
        $er = $this->calculateER($instagram);
        if ($er >= 3) {
            $score += 20;
        } elseif ($er >= 1.5) {
            $score += 10;
        }

        // Post count bonus
        $posts = $instagram->media_count ?? 0;
        if ($posts >= 100) {
            $score += 15;
        } elseif ($posts >= 50) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Get response time in hours
     */
    protected function getResponseTime(Business $business): float
    {
        $hasChatbot = $business->chatbotConfigs()->where('is_active', true)->exists();

        return $hasChatbot ? 0.1 : 2; // 6 minutes vs 2 hours
    }

    /**
     * Get price positioning (1-10, 1=cheapest)
     */
    protected function getPricePositioning(Business $business): int
    {
        $offer = $business->offers()->first();
        if (! $offer || ! $offer->price) {
            return 5;
        }

        // Estimate based on price
        $price = $offer->price;

        if ($price < 500000) {
            return 2;
        }
        if ($price < 1000000) {
            return 3;
        }
        if ($price < 2000000) {
            return 5;
        }
        if ($price < 5000000) {
            return 7;
        }

        return 9;
    }

    /**
     * Calculate comparisons
     */
    protected function calculateComparisons(array $ourMetrics, array $competitors): array
    {
        $comparisons = [];

        foreach ($this->metricsToCompare as $metric => $config) {
            $ourValue = $ourMetrics[$metric] ?? 0;

            // Calculate competitor statistics
            $competitorValues = array_map(fn ($c) => $c['metrics'][$metric] ?? 0, $competitors);
            $competitorAvg = count($competitorValues) > 0 ? array_sum($competitorValues) / count($competitorValues) : 0;
            $competitorMax = count($competitorValues) > 0 ? max($competitorValues) : 0;
            $competitorMin = count($competitorValues) > 0 ? min($competitorValues) : 0;

            // Calculate gap
            $gap = $ourValue - $competitorAvg;
            $gapPercent = $competitorAvg > 0 ? round(($gap / $competitorAvg) * 100, 1) : 0;

            // Determine if we're ahead or behind
            $higherBetter = $config['higher_better'];
            $isAhead = $higherBetter ? $gap > 0 : $gap < 0;

            // Calculate position score for this metric
            $metricScore = $this->calculateMetricPosition($ourValue, $competitorAvg, $competitorMax, $higherBetter);

            $comparisons[$metric] = [
                'metric' => $metric,
                'label' => $config['label'],
                'our_value' => $ourValue,
                'competitor_avg' => round($competitorAvg, 2),
                'competitor_max' => round($competitorMax, 2),
                'competitor_min' => round($competitorMin, 2),
                'gap' => round($gap, 2),
                'gap_percent' => $gapPercent,
                'is_ahead' => $isAhead,
                'higher_better' => $higherBetter,
                'position_score' => $metricScore,
                'status' => $isAhead ? 'ahead' : ($gap == 0 ? 'equal' : 'behind'),
            ];
        }

        return $comparisons;
    }

    /**
     * Calculate metric position score
     */
    protected function calculateMetricPosition(float $ourValue, float $avg, float $max, bool $higherBetter): int
    {
        if ($max == 0) {
            return 50;
        }

        if ($higherBetter) {
            // Normalize to 0-100 based on max
            $normalized = ($ourValue / $max) * 100;
            // Bonus if above average
            if ($ourValue > $avg) {
                $normalized = min(100, $normalized + 10);
            }
        } else {
            // For metrics where lower is better (inverted)
            if ($ourValue <= 0) {
                return 100;
            }
            $normalized = 100 - (($ourValue / $max) * 100);
            // Bonus if below average
            if ($ourValue < $avg) {
                $normalized = min(100, $normalized + 10);
            }
        }

        return (int) round($normalized);
    }

    /**
     * Calculate overall position score
     */
    protected function calculatePositionScore(array $comparisons): int
    {
        if (empty($comparisons)) {
            return 50;
        }

        $totalScore = 0;
        foreach ($comparisons as $comparison) {
            $totalScore += $comparison['position_score'];
        }

        return (int) round($totalScore / count($comparisons));
    }

    /**
     * Get position status
     */
    protected function getPositionStatus(int $score): array
    {
        if ($score >= 80) {
            return [
                'level' => 'leader',
                'label' => 'Bozor lideri',
                'color' => 'blue',
                'message' => 'Siz raqobatchilardan oldinda. Pozitsiyani mustahkamlang.',
            ];
        }

        if ($score >= 60) {
            return [
                'level' => 'competitive',
                'label' => 'Raqobatbardosh',
                'color' => 'green',
                'message' => 'Yaxshi pozitsiya. Liderlikka intiling.',
            ];
        }

        if ($score >= 40) {
            return [
                'level' => 'average',
                'label' => 'O\'rtacha',
                'color' => 'yellow',
                'message' => 'Raqobatchilar bilan tengdasiz. Farqlanish kerak.',
            ];
        }

        return [
            'level' => 'behind',
            'label' => 'Orqada',
            'color' => 'red',
            'message' => 'Raqobatchilardan orqadasiz. Tez harakat qiling.',
        ];
    }

    /**
     * Identify strengths
     */
    protected function identifyStrengths(array $comparisons): array
    {
        $strengths = [];

        foreach ($comparisons as $metric => $data) {
            if ($data['is_ahead'] && $data['gap_percent'] > 10) {
                $strengths[] = [
                    'metric' => $metric,
                    'label' => $data['label'],
                    'advantage' => '+'.abs($data['gap_percent']).'%',
                    'description' => $this->getStrengthDescription($metric, $data['gap_percent']),
                ];
            }
        }

        // Sort by advantage
        usort($strengths, fn ($a, $b) => $b['advantage'] <=> $a['advantage']);

        return $strengths;
    }

    /**
     * Identify weaknesses
     */
    protected function identifyWeaknesses(array $comparisons): array
    {
        $weaknesses = [];

        foreach ($comparisons as $metric => $data) {
            if (! $data['is_ahead'] && abs($data['gap_percent']) > 10) {
                $weaknesses[] = [
                    'metric' => $metric,
                    'label' => $data['label'],
                    'disadvantage' => '-'.abs($data['gap_percent']).'%',
                    'description' => $this->getWeaknessDescription($metric, $data['gap_percent']),
                    'priority' => abs($data['gap_percent']) > 30 ? 'high' : 'medium',
                ];
            }
        }

        // Sort by priority and disadvantage
        usort($weaknesses, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] === 'high' ? -1 : 1;
            }

            return $b['disadvantage'] <=> $a['disadvantage'];
        });

        return $weaknesses;
    }

    /**
     * Calculate market position
     */
    protected function calculateMarketPosition(array $ourMetrics, array $competitors): array
    {
        $allFollowers = array_merge(
            [$ourMetrics['followers']],
            array_map(fn ($c) => $c['metrics']['followers'] ?? 0, $competitors)
        );

        $totalFollowers = array_sum($allFollowers);
        $marketShare = $totalFollowers > 0
            ? round(($ourMetrics['followers'] / $totalFollowers) * 100, 1)
            : 0;

        // Sort to find rank
        rsort($allFollowers);
        $rank = array_search($ourMetrics['followers'], $allFollowers) + 1;

        return [
            'market_share' => $marketShare,
            'rank' => $rank,
            'total_players' => count($allFollowers),
            'position_label' => $this->getPositionLabel($rank, count($allFollowers)),
        ];
    }

    /**
     * Get position label
     */
    protected function getPositionLabel(int $rank, int $total): string
    {
        if ($rank === 1) {
            return '🥇 Birinchi o\'rin';
        }
        if ($rank === 2) {
            return '🥈 Ikkinchi o\'rin';
        }
        if ($rank === 3) {
            return '🥉 Uchinchi o\'rin';
        }
        if ($rank <= $total / 2) {
            return "📈 Yuqori yarmi ({$rank}/{$total})";
        }

        return "📉 Pastki yarmi ({$rank}/{$total})";
    }

    /**
     * Analyze competitor trends
     */
    protected function analyzeCompetitorTrends(array $competitors): array
    {
        // Would need historical data for real trends
        // For now, estimate based on current data

        $trends = [];
        foreach ($competitors as $competitor) {
            $er = $competitor['metrics']['engagement_rate'] ?? 0;
            $followers = $competitor['metrics']['followers'] ?? 0;

            $trends[] = [
                'competitor' => $competitor['name'],
                'growth_trend' => $followers > 5000 ? 'growing' : 'stable',
                'engagement_trend' => $er > 3 ? 'strong' : ($er > 1.5 ? 'moderate' : 'weak'),
                'threat_level' => $this->assessThreatLevel($competitor['metrics']),
            ];
        }

        return $trends;
    }

    /**
     * Assess threat level
     */
    protected function assessThreatLevel(array $metrics): string
    {
        $score = 0;

        if (($metrics['followers'] ?? 0) > 10000) {
            $score += 2;
        }
        if (($metrics['engagement_rate'] ?? 0) > 3) {
            $score += 2;
        }
        if (($metrics['post_frequency'] ?? 0) > 5) {
            $score += 1;
        }

        if ($score >= 4) {
            return 'high';
        }
        if ($score >= 2) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Identify opportunities
     */
    protected function identifyOpportunities(array $comparisons, array $trends): array
    {
        $opportunities = [];

        // Weak competitors
        $weakCompetitors = array_filter($trends, fn ($t) => $t['engagement_trend'] === 'weak');
        if (count($weakCompetitors) > 0) {
            $opportunities[] = [
                'type' => 'weak_competitor',
                'title' => 'Zaif raqobatchilar',
                'description' => count($weakCompetitors).' ta raqobatchi zaif engagement ko\'rsatmoqda',
                'action' => 'Ularning auditoriyasini jalb qiling',
            ];
        }

        // Gaps in market
        foreach ($comparisons as $metric => $data) {
            if ($data['is_ahead'] && $data['gap_percent'] > 20) {
                $opportunities[] = [
                    'type' => 'market_gap',
                    'title' => $data['label'].' da ustunlik',
                    'description' => 'Siz '.$data['gap_percent'].'% oldinda - bu ustunlikni marketing qiling',
                    'action' => 'Bu farqni reklama qiling',
                ];
            }
        }

        return $opportunities;
    }

    /**
     * Identify threats
     */
    protected function identifyThreats(array $comparisons, array $trends): array
    {
        $threats = [];

        // Fast growing competitors
        $growingCompetitors = array_filter($trends, fn ($t) => $t['threat_level'] === 'high');
        if (count($growingCompetitors) > 0) {
            $threats[] = [
                'type' => 'growing_competitor',
                'title' => 'Tez o\'sayotgan raqobatchi',
                'description' => count($growingCompetitors).' ta raqobatchi tez o\'smoqda',
                'severity' => 'high',
                'action' => 'Ularni kuzatib boring va differensiatsiya qiling',
            ];
        }

        // Areas where we're behind
        foreach ($comparisons as $metric => $data) {
            if (! $data['is_ahead'] && abs($data['gap_percent']) > 30) {
                $threats[] = [
                    'type' => 'competitive_gap',
                    'title' => $data['label'].' bo\'yicha orqada',
                    'description' => 'Raqobatchilardan '.abs($data['gap_percent']).'% orqadasiz',
                    'severity' => 'medium',
                    'action' => $this->getImprovementAction($metric),
                ];
            }
        }

        return $threats;
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(array $comparisons, array $weaknesses): array
    {
        $recommendations = [];

        foreach ($weaknesses as $weakness) {
            $recommendations[] = [
                'priority' => $weakness['priority'],
                'area' => $weakness['label'],
                'current_gap' => $weakness['disadvantage'],
                'recommendation' => $this->getRecommendation($weakness['metric']),
                'expected_impact' => $this->getExpectedImpact($weakness['metric']),
            ];
        }

        // Sort by priority
        usort($recommendations, fn ($a, $b) => ($a['priority'] === 'high' ? 0 : 1) <=> ($b['priority'] === 'high' ? 0 : 1)
        );

        return array_slice($recommendations, 0, 5);
    }

    /**
     * Get strength description
     */
    protected function getStrengthDescription(string $metric, float $gap): string
    {
        $descriptions = [
            'followers' => 'Auditoriya hajmi bo\'yicha ustunlik',
            'engagement_rate' => 'Kontent sifati va engagement bo\'yicha ustunlik',
            'post_frequency' => 'Faollik bo\'yicha ustunlik',
            'content_quality' => 'Kontent sifati bo\'yicha ustunlik',
            'response_time' => 'Tezkor javob bo\'yicha ustunlik',
            'price_positioning' => 'Narx strategiyasi bo\'yicha ustunlik',
        ];

        return $descriptions[$metric] ?? 'Bu metrika bo\'yicha ustunlik';
    }

    /**
     * Get weakness description
     */
    protected function getWeaknessDescription(string $metric, float $gap): string
    {
        $descriptions = [
            'followers' => 'Auditoriya hajmini oshirish kerak',
            'engagement_rate' => 'Engagement ko\'tarishga e\'tibor bering',
            'post_frequency' => 'Post chastotasini oshiring',
            'content_quality' => 'Kontent sifatini yaxshilang',
            'response_time' => 'Javob tezligini oshiring (chatbot)',
            'price_positioning' => 'Narx strategiyasini qayta ko\'ring',
        ];

        return $descriptions[$metric] ?? 'Bu metrikani yaxshilash kerak';
    }

    /**
     * Get improvement action
     */
    protected function getImprovementAction(string $metric): string
    {
        $actions = [
            'followers' => 'Reklama va kolaboratsiyalar orqali followerlarni oshiring',
            'engagement_rate' => 'Interactive kontent yarating (poll, quiz, carousel)',
            'post_frequency' => 'Kontent kalendar tuzing va muntazam post qiling',
            'content_quality' => 'Professional dizayn va videografiya ishlating',
            'response_time' => 'AI chatbot o\'rnating',
            'price_positioning' => 'Narx-qiymat balansini optimallash',
        ];

        return $actions[$metric] ?? 'Bu yo\'nalishda ishlang';
    }

    /**
     * Get recommendation
     */
    protected function getRecommendation(string $metric): string
    {
        $recommendations = [
            'followers' => 'Targetlangan reklamalar va viral kontentlar orqali auditoriyani kengaytiring',
            'engagement_rate' => 'Reels, carousel va interactive stories orqali engagement oshiring',
            'post_frequency' => 'Haftada kamida 5-7 ta sifatli post joylang',
            'content_quality' => 'Professional fotograf/videograf yollang yoki Canva Pro ishlating',
            'response_time' => 'Instagram AI chatbot o\'rnating - 24/7 avtomatik javob',
            'price_positioning' => 'Qiymatni oshiring yoki narxni solishtiring',
        ];

        return $recommendations[$metric] ?? 'Bu metrikani yaxshilash ustida ishlang';
    }

    /**
     * Get expected impact
     */
    protected function getExpectedImpact(string $metric): string
    {
        $impacts = [
            'followers' => '+30-50% reach, +20% leads',
            'engagement_rate' => '+25% reach, +15% konversiya',
            'post_frequency' => '+40% visibility, +20% engagement',
            'content_quality' => '+35% engagement, +25% shares',
            'response_time' => '+50% lead capture, +30% konversiya',
            'price_positioning' => '+10-20% margin yoki +30% volume',
        ];

        return $impacts[$metric] ?? '+10-20% yaxshilanish';
    }

    /**
     * Get no competitors result
     */
    protected function getNoCompetitorsResult(Business $business, array $industryBenchmarks): array
    {
        return [
            'score' => 50,
            'status' => [
                'level' => 'unknown',
                'label' => 'Ma\'lumot yo\'q',
                'color' => 'gray',
                'message' => 'Raqobatchilarni qo\'shing tahlil uchun',
            ],
            'competitors_analyzed' => 0,
            'our_metrics' => $this->getOurMetrics($business),
            'comparisons' => [],
            'market_position' => [
                'market_share' => 0,
                'rank' => 0,
                'total_players' => 1,
                'position_label' => 'Raqobatchilar aniqlanmagan',
            ],
            'industry_benchmarks' => $industryBenchmarks,
            'recommendations' => [
                [
                    'priority' => 'high',
                    'area' => 'Raqobatchilar',
                    'recommendation' => 'Kamida 3-5 ta raqobatchini qo\'shing to\'liq tahlil uchun',
                    'action_route' => '/business/competitors',
                ],
            ],
        ];
    }
}
