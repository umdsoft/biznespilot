<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Diagnostic Algorithm Service
 *
 * Barcha diagnostika hisob-kitoblarini AI o'rniga algoritm bilan bajaradi.
 * AI faqat tavsiya va matn generatsiya uchun ishlatiladi.
 *
 * @version 2.0.0
 * @author BiznesPilot Team
 */
class DiagnosticAlgorithmService extends AlgorithmEngine
{
    protected string $cachePrefix = 'diagnostic_algo_';
    protected int $cacheTTL = 1800; // 30 minutes

    // Sub-calculators
    protected HealthScoreAlgorithm $healthScoreAlgorithm;
    protected MoneyLossAlgorithm $moneyLossAlgorithm;
    protected FunnelAnalysisAlgorithm $funnelAnalysisAlgorithm;
    protected EngagementAlgorithm $engagementAlgorithm;
    protected ValueEquationAlgorithm $valueEquationAlgorithm;
    protected ChurnRiskAlgorithm $churnRiskAlgorithm;
    protected RevenueForecaster $revenueForecaster;
    protected ContentOptimizationAlgorithm $contentOptimizationAlgorithm;
    protected DreamBuyerScoringAlgorithm $dreamBuyerScoringAlgorithm;
    protected CompetitorBenchmarkAlgorithm $competitorBenchmarkAlgorithm;

    public function __construct()
    {
        $this->healthScoreAlgorithm = new HealthScoreAlgorithm();
        $this->moneyLossAlgorithm = new MoneyLossAlgorithm();
        $this->funnelAnalysisAlgorithm = new FunnelAnalysisAlgorithm();
        $this->engagementAlgorithm = new EngagementAlgorithm();
        $this->valueEquationAlgorithm = new ValueEquationAlgorithm();
        $this->churnRiskAlgorithm = new ChurnRiskAlgorithm();
        $this->revenueForecaster = new RevenueForecaster();
        $this->contentOptimizationAlgorithm = new ContentOptimizationAlgorithm();
        $this->dreamBuyerScoringAlgorithm = new DreamBuyerScoringAlgorithm();
        $this->competitorBenchmarkAlgorithm = new CompetitorBenchmarkAlgorithm();
    }

    /**
     * Run full diagnostic calculation
     *
     * Bu metod barcha algoritmlarni ishga tushiradi va natijalarni qaytaradi.
     * AI ga faqat tayyor hisob-kitoblar yuboriladi.
     */
    public function runFullDiagnostic(Business $business, array $benchmarks = []): array
    {
        $startTime = microtime(true);

        // CRITICAL FIX: Wrap in database transaction to prevent partial data saves
        return DB::transaction(function() use ($business, $benchmarks, $startTime) {
            try {
                // 1. Ma'lumotlarni yig'ish
                $businessData = $this->collectBusinessData($business);
                $metrics = $this->collectMetrics($business);

                // 2. Barcha algoritmlarni ishga tushirish
                $results = [
                // Asosiy ballar
                'health_score' => $this->healthScoreAlgorithm->calculate($business, $metrics, $benchmarks),

                // Dream Buyer tahlili
                'dream_buyer_analysis' => $this->dreamBuyerScoringAlgorithm->calculate($business),

                // Taklif kuchi (Value Equation)
                'offer_strength' => $this->valueEquationAlgorithm->calculate($business),

                // Pul yo'qotish tahlili
                'money_loss' => $this->moneyLossAlgorithm->calculate($business, $metrics, $benchmarks),

                // Funnel tahlili
                'funnel_analysis' => $this->funnelAnalysisAlgorithm->calculate($business, $metrics),

                // Ijtimoiy tarmoq metrikalari
                'engagement_metrics' => $this->engagementAlgorithm->calculate($business),

                // Kontent optimallashtirish
                'content_optimization' => $this->contentOptimizationAlgorithm->calculate($business),

                // Churn risk
                'churn_risk' => $this->churnRiskAlgorithm->calculate($business),

                // Daromad bashorati
                'revenue_forecast' => $this->revenueForecaster->calculate($business),

                // Raqobatchi benchmarking
                'competitor_benchmark' => $this->competitorBenchmarkAlgorithm->calculate($business, $benchmarks),
            ];

            // 3. Umumiy ball hisoblash
            $results['overall_score'] = $this->calculateOverallScore($results);
            $results['status'] = $this->getStatusFromScore($results['overall_score']);

            // 4. ROI hisoblash
            $results['roi_calculations'] = $this->calculateROI($results);

            // 5. Action plan prioritetlash
            $results['action_priorities'] = $this->prioritizeActions($results);

            // 6. Expected results
            $results['expected_results'] = $this->calculateExpectedResults($results);

            // Meta ma'lumotlar
            $results['_meta'] = [
                'calculated_at' => now()->toIso8601String(),
                'calculation_time_ms' => round((microtime(true) - $startTime) * 1000),
                'algorithm_version' => '2.0.0',
                'data_completeness' => $this->calculateDataCompleteness($businessData),
            ];

                Log::info('Diagnostic algorithm completed', [
                    'business_id' => $business->id,
                    'overall_score' => $results['overall_score'],
                    'calculation_time_ms' => $results['_meta']['calculation_time_ms'],
                ]);

                return $results;

            } catch (\Exception $e) {
                Log::error('Diagnostic algorithm error', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Collect business data
     */
    protected function collectBusinessData(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'industry' => $business->category ?? $business->industry ?? 'default',
            'team_size' => $business->team_size ?? 1,
            'has_dream_buyer' => $business->dreamBuyers()->exists(),
            'has_offers' => $business->offers()->exists(),
            'connected_channels' => $this->getConnectedChannels($business),
            'maturity_assessment' => $business->maturityAssessment?->toArray(),
        ];
    }

    /**
     * Collect all metrics
     */
    protected function collectMetrics(Business $business): array
    {
        return [
            'sales' => $this->collectSalesMetrics($business),
            'marketing' => $this->collectMarketingMetrics($business),
            'social' => $this->collectSocialMetrics($business),
            'funnel' => $this->collectFunnelMetrics($business),
        ];
    }

    /**
     * Collect sales metrics
     */
    protected function collectSalesMetrics(Business $business): array
    {
        $salesMetrics = $business->salesMetrics;

        return [
            'monthly_revenue' => $salesMetrics?->monthly_revenue ?? 0,
            'monthly_leads' => $salesMetrics?->monthly_leads ?? 0,
            'conversion_rate' => $salesMetrics?->conversion_rate ?? 0,
            'average_deal_size' => $salesMetrics?->average_deal_value ?? 0,
            'sales_cycle_days' => $salesMetrics?->sales_cycle_days ?? 30,
            'cac' => $salesMetrics?->customer_acquisition_cost ?? 0,
            'ltv' => $salesMetrics?->customer_lifetime_value ?? 0,
            'repeat_purchase_rate' => $salesMetrics?->repeat_purchase_rate ?? 0,
        ];
    }

    /**
     * Collect marketing metrics
     */
    protected function collectMarketingMetrics(Business $business): array
    {
        $marketingMetrics = $business->marketingMetrics;

        return [
            'monthly_budget' => $marketingMetrics?->monthly_budget ?? 0,
            'ad_spend' => $marketingMetrics?->ad_spend ?? 0,
            'impressions' => $marketingMetrics?->impressions ?? 0,
            'clicks' => $marketingMetrics?->clicks ?? 0,
            'ctr' => $marketingMetrics?->ctr ?? 0,
            'cpc' => $marketingMetrics?->cpc ?? 0,
            'cpl' => $marketingMetrics?->cost_per_lead ?? 0,
            'roas' => $marketingMetrics?->roas ?? 0,
        ];
    }

    /**
     * Collect social media metrics
     */
    protected function collectSocialMetrics(Business $business): array
    {
        $metrics = [];

        // Instagram
        $instagram = $business->instagramAccounts()->first();
        if ($instagram) {
            $metrics['instagram'] = [
                'connected' => true,
                'followers' => $instagram->followers_count ?? 0,
                'following' => $instagram->follows_count ?? 0,
                'posts_count' => $instagram->media_count ?? 0,
                'engagement_rate' => $this->engagementAlgorithm->calculateInstagramER($instagram),
                'avg_likes' => $instagram->metrics['avg_likes'] ?? 0,
                'avg_comments' => $instagram->metrics['avg_comments'] ?? 0,
            ];
        } else {
            $metrics['instagram'] = ['connected' => false];
        }

        // Telegram
        $telegram = $business->integrations()->where('type', 'telegram')->first();
        if ($telegram) {
            $metrics['telegram'] = [
                'connected' => true,
                'subscribers' => $telegram->metadata['subscribers'] ?? 0,
                'type' => $telegram->metadata['type'] ?? 'bot',
            ];
        } else {
            $metrics['telegram'] = ['connected' => false];
        }

        // Facebook
        $facebook = $business->integrations()->where('type', 'facebook')->first();
        if ($facebook) {
            $metrics['facebook'] = [
                'connected' => true,
                'followers' => $facebook->metadata['followers'] ?? 0,
            ];
        } else {
            $metrics['facebook'] = ['connected' => false];
        }

        return $metrics;
    }

    /**
     * Collect funnel metrics
     */
    protected function collectFunnelMetrics(Business $business): array
    {
        // PERFORMANCE FIX: Single query instead of 5 separate queries (N+1 fix)
        $leadCounts = $business->leads()
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        // Ensure all stages exist with 0 default
        $stages = ['awareness', 'interest', 'consideration', 'intent', 'purchase'];
        foreach ($stages as $stage) {
            if (!isset($leadCounts[$stage])) {
                $leadCounts[$stage] = 0;
            }
        }

        return [
            'stages' => $leadCounts,
            'total_leads' => array_sum($leadCounts),
            'converted_leads' => $leadCounts['purchase'] ?? 0,
        ];
    }

    /**
     * Get connected channels
     */
    protected function getConnectedChannels(Business $business): array
    {
        $channels = [];

        if ($business->instagramAccounts()->exists()) {
            $channels[] = 'instagram';
        }

        $integrationTypes = $business->integrations()
            ->where('status', 'connected')
            ->pluck('type')
            ->toArray();

        return array_unique(array_merge($channels, $integrationTypes));
    }

    /**
     * Calculate overall score from all results
     */
    protected function calculateOverallScore(array $results): int
    {
        // Weighted average of all scores
        $weights = [
            'health_score' => 0.25,
            'dream_buyer_analysis' => 0.20,
            'offer_strength' => 0.15,
            'funnel_analysis' => 0.20,
            'engagement_metrics' => 0.10,
            'content_optimization' => 0.10,
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($weights as $key => $weight) {
            if (isset($results[$key]['score'])) {
                $totalScore += $results[$key]['score'] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? (int) round($totalScore / $totalWeight) : 50;
    }

    /**
     * Get status from score
     */
    protected function getStatusFromScore(int $score): array
    {
        if ($score >= 80) {
            return [
                'level' => 'excellent',
                'label' => 'Ajoyib',
                'color' => 'blue',
                'emoji' => '🚀',
                'message' => 'Biznesingiz ajoyib holatda! Davom eting va yanada rivojlaning.',
            ];
        }

        if ($score >= 60) {
            return [
                'level' => 'good',
                'label' => 'Yaxshi',
                'color' => 'green',
                'emoji' => '✅',
                'message' => 'Biznesingiz yaxshi yo\'nalishda. Bir nechta yaxshilashlar bilan ajoyib natijaga erishish mumkin.',
            ];
        }

        if ($score >= 40) {
            return [
                'level' => 'average',
                'label' => 'O\'rtacha',
                'color' => 'yellow',
                'emoji' => '⚠️',
                'message' => 'Biznesingiz o\'rtacha holatda. Quyidagi tavsiyalarga amal qilsangiz, sezilarli yaxshilanish bo\'ladi.',
            ];
        }

        return [
            'level' => 'weak',
            'label' => 'Zaif',
            'color' => 'red',
            'emoji' => '🔴',
            'message' => 'Biznesingiz diqqatga muhtoj. Zudlik bilan quyidagi qadamlarni bajaring.',
        ];
    }

    /**
     * Calculate ROI for each recommended action
     */
    protected function calculateROI(array $results): array
    {
        $roiCalculations = [
            'summary' => [
                'total_investment' => [
                    'time_hours' => 0,
                    'time_value_uzs' => 0,
                    'money_uzs' => 0,
                    'total_uzs' => 0,
                ],
                'total_monthly_return' => 0,
                'overall_roi_percent' => 0,
                'payback_days' => 0,
            ],
            'per_action' => [],
        ];

        // Hourly rate assumption (50,000 UZS/hour)
        $hourlyRate = 50000;

        // Actions with ROI calculations
        $actions = $this->getActionsWithROI($results, $hourlyRate);

        $totalTimeHours = 0;
        $totalMoneyInvestment = 0;
        $totalMonthlyReturn = 0;

        foreach ($actions as $action) {
            $timeValue = $action['time_minutes'] / 60 * $hourlyRate;
            $totalInvestment = $timeValue + $action['money_investment'];

            $roiPercent = $totalInvestment > 0
                ? round(($action['monthly_return'] / $totalInvestment) * 100)
                : 0;

            $paybackDays = $action['monthly_return'] > 0
                ? round($totalInvestment / ($action['monthly_return'] / 30))
                : 999;

            $roiCalculations['per_action'][] = [
                'id' => $action['id'],
                'action' => $action['title'],
                'priority' => $action['priority'],
                'investment' => [
                    'time' => $this->formatTime($action['time_minutes']),
                    'time_value' => $timeValue,
                    'money' => $action['money_investment'],
                    'total' => $totalInvestment,
                ],
                'expected_return' => [
                    'metric' => $action['metric'],
                    'improvement' => $action['improvement'],
                    'monthly_gain' => $action['monthly_return'],
                    'description' => $action['description'],
                ],
                'roi_percent' => $roiPercent,
                'payback_days' => $paybackDays,
                'module_route' => $action['module_route'],
                'difficulty' => $action['difficulty'],
                'verdict' => $this->getROIVerdict($roiPercent),
            ];

            $totalTimeHours += $action['time_minutes'] / 60;
            $totalMoneyInvestment += $action['money_investment'];
            $totalMonthlyReturn += $action['monthly_return'];
        }

        $totalTimeValue = $totalTimeHours * $hourlyRate;
        $totalInvestment = $totalTimeValue + $totalMoneyInvestment;

        $roiCalculations['summary'] = [
            'total_investment' => [
                'time_hours' => round($totalTimeHours, 1),
                'time_value_uzs' => $totalTimeValue,
                'money_uzs' => $totalMoneyInvestment,
                'total_uzs' => $totalInvestment,
            ],
            'total_monthly_return' => $totalMonthlyReturn,
            'overall_roi_percent' => $totalInvestment > 0
                ? round(($totalMonthlyReturn / $totalInvestment) * 100)
                : 0,
            'payback_days' => $totalMonthlyReturn > 0
                ? round($totalInvestment / ($totalMonthlyReturn / 30))
                : 999,
        ];

        return $roiCalculations;
    }

    /**
     * Get actions with ROI data
     */
    protected function getActionsWithROI(array $results, int $hourlyRate): array
    {
        $actions = [];
        $priority = 1;

        // Dream Buyer action
        $dreamBuyerScore = $results['dream_buyer_analysis']['score'] ?? 50;
        if ($dreamBuyerScore < 70) {
            $monthlyLoss = $results['money_loss']['breakdown']['dream_buyer'] ?? 5000000;
            $actions[] = [
                'id' => $priority,
                'title' => 'Ideal mijozni aniqlang',
                'priority' => $priority++,
                'time_minutes' => 30,
                'money_investment' => 0,
                'monthly_return' => $monthlyLoss * 0.7, // 70% recovery
                'metric' => 'Konversiya',
                'improvement' => '+' . round((70 - $dreamBuyerScore) * 0.5) . '%',
                'description' => 'Aniq auditoriyaga moslashtirilgan marketing',
                'module_route' => '/onboarding/dream-buyer',
                'difficulty' => 'oson',
            ];
        }

        // Offer action
        $offerScore = $results['offer_strength']['score'] ?? 50;
        if ($offerScore < 70) {
            $monthlyLoss = $results['money_loss']['breakdown']['offer'] ?? 7000000;
            $actions[] = [
                'id' => $priority,
                'title' => 'Rad qilib bo\'lmas taklif yarating',
                'priority' => $priority++,
                'time_minutes' => 45,
                'money_investment' => 0,
                'monthly_return' => $monthlyLoss * 0.6,
                'metric' => 'Sotuvlar',
                'improvement' => '+' . round((70 - $offerScore) * 0.8) . '%',
                'description' => 'Raqobatchilardan farqlanadigan kuchli taklif',
                'module_route' => '/onboarding/offer',
                'difficulty' => 'o\'rta',
            ];
        }

        // Channel actions
        $channelScores = $results['engagement_metrics']['channel_scores'] ?? [];
        foreach ($channelScores as $channel => $data) {
            if (($data['score'] ?? 0) < 50) {
                $actions[] = [
                    'id' => $priority,
                    'title' => ucfirst($channel) . ' ni yaxshilang',
                    'priority' => $priority++,
                    'time_minutes' => 20,
                    'money_investment' => 0,
                    'monthly_return' => 3000000,
                    'metric' => 'Engagement',
                    'improvement' => '+50%',
                    'description' => $channel . ' orqali ko\'proq mijozlarga yetish',
                    'module_route' => '/business/' . $channel,
                    'difficulty' => 'oson',
                ];
            }
        }

        // Funnel action
        $funnelScore = $results['funnel_analysis']['score'] ?? 50;
        if ($funnelScore < 60) {
            $biggestLeak = $results['funnel_analysis']['biggest_leak'] ?? [];
            $actions[] = [
                'id' => $priority,
                'title' => 'Sotuv voronkasini yaxshilang',
                'priority' => $priority++,
                'time_minutes' => 60,
                'money_investment' => 0,
                'monthly_return' => $biggestLeak['estimated_loss'] ?? 4000000,
                'metric' => 'Konversiya',
                'improvement' => '+' . round((60 - $funnelScore) * 0.5) . '%',
                'description' => 'Eng katta yo\'qotish joyini tuzatish',
                'module_route' => '/business/funnel',
                'difficulty' => 'o\'rta',
            ];
        }

        return $actions;
    }

    /**
     * Get ROI verdict
     */
    protected function getROIVerdict(int $roiPercent): string
    {
        if ($roiPercent >= 1000) return 'JUDA SAMARALI ✅';
        if ($roiPercent >= 500) return 'SAMARALI ✅';
        if ($roiPercent >= 200) return 'YAXSHI 👍';
        if ($roiPercent >= 100) return 'QABUL QILINADIGAN ⚖️';
        return 'TAHLIL QILISH KERAK ⚠️';
    }

    /**
     * Prioritize actions based on ROI and difficulty
     */
    protected function prioritizeActions(array $results): array
    {
        $actions = [];

        // Collect all potential actions with scores
        $potentialActions = [
            [
                'key' => 'dream_buyer',
                'score' => $results['dream_buyer_analysis']['score'] ?? 50,
                'threshold' => 70,
                'title' => 'Ideal mijozni aniqlang',
                'module' => 'Dream Buyer',
                'route' => '/onboarding/dream-buyer',
                'time_minutes' => 30,
                'impact' => 5,
                'difficulty' => 'oson',
            ],
            [
                'key' => 'offer',
                'score' => $results['offer_strength']['score'] ?? 50,
                'threshold' => 70,
                'title' => 'Rad qilib bo\'lmas taklif yarating',
                'module' => 'Taklif',
                'route' => '/onboarding/offer',
                'time_minutes' => 45,
                'impact' => 5,
                'difficulty' => 'o\'rta',
            ],
            [
                'key' => 'funnel',
                'score' => $results['funnel_analysis']['score'] ?? 50,
                'threshold' => 60,
                'title' => 'Sotuv voronkasini optimallashtiring',
                'module' => 'Funnel',
                'route' => '/business/funnel',
                'time_minutes' => 60,
                'impact' => 4,
                'difficulty' => 'o\'rta',
            ],
            [
                'key' => 'content',
                'score' => $results['content_optimization']['score'] ?? 50,
                'threshold' => 60,
                'title' => 'Kontent strategiyasini yaxshilang',
                'module' => 'Kontent',
                'route' => '/business/content',
                'time_minutes' => 90,
                'impact' => 3,
                'difficulty' => 'o\'rta',
            ],
        ];

        $order = 1;
        foreach ($potentialActions as $action) {
            if ($action['score'] < $action['threshold']) {
                $gap = $action['threshold'] - $action['score'];
                $actions[] = [
                    'order' => $order++,
                    'title' => $action['title'],
                    'module_route' => $action['route'],
                    'module_name' => $action['module'],
                    'time_minutes' => $action['time_minutes'],
                    'impact_stars' => $action['impact'],
                    'difficulty' => $action['difficulty'],
                    'current_score' => $action['score'],
                    'target_score' => $action['threshold'],
                    'gap' => $gap,
                    'why' => $this->getActionReason($action['key'], $gap),
                    'similar_business_result' => $this->getSimilarBusinessResult($action['key']),
                    'timeline' => $order <= 2 ? 'today' : ($order <= 4 ? 'this_week' : 'this_month'),
                ];
            }
        }

        // Sort by impact and gap
        usort($actions, function($a, $b) {
            $scoreA = $a['impact_stars'] * 10 + $a['gap'];
            $scoreB = $b['impact_stars'] * 10 + $b['gap'];
            return $scoreB <=> $scoreA;
        });

        // Re-number
        foreach ($actions as $i => &$action) {
            $action['order'] = $i + 1;
        }

        return $actions;
    }

    /**
     * Get action reason
     */
    protected function getActionReason(string $key, int $gap): string
    {
        $reasons = [
            'dream_buyer' => 'Ideal mijozni bilmasangiz, marketing xarajatlari {gap}% samarasiz bo\'ladi',
            'offer' => 'Zaif taklif bilan mijozlar qaror qila olmaydi, sotuvlar {gap}% past',
            'funnel' => 'Sotuv voronkasidagi muammolar {gap}% leadni yo\'qotmoqda',
            'content' => 'Sifatsiz kontent {gap}% kamroq engagement beradi',
        ];

        return str_replace('{gap}', $gap, $reasons[$key] ?? 'Yaxshilash kerak');
    }

    /**
     * Get similar business result
     */
    protected function getSimilarBusinessResult(string $key): string
    {
        $results = [
            'dream_buyer' => '+45% konversiya',
            'offer' => '+60% sotuvlar',
            'funnel' => '+35% konversiya',
            'content' => '+50% engagement',
        ];

        return $results[$key] ?? '+30% yaxshilanish';
    }

    /**
     * Calculate expected results over time
     */
    protected function calculateExpectedResults(array $results): array
    {
        $currentScore = $results['overall_score'];
        $currentRevenue = $results['health_score']['metrics']['monthly_revenue'] ?? 10000000;
        $currentLeads = $results['health_score']['metrics']['monthly_leads'] ?? 50;

        // Calculate potential improvement based on gaps
        $totalGap = 0;
        $improvements = [
            'dream_buyer' => max(0, 70 - ($results['dream_buyer_analysis']['score'] ?? 50)),
            'offer' => max(0, 70 - ($results['offer_strength']['score'] ?? 50)),
            'funnel' => max(0, 60 - ($results['funnel_analysis']['score'] ?? 50)),
        ];
        $totalGap = array_sum($improvements);

        // Monthly improvement rate (more aggressive at start)
        $monthlyImprovement = min(20, $totalGap / 4);

        return [
            'current' => [
                'score' => $currentScore,
                'leads_weekly' => round($currentLeads / 4),
                'conversion' => $results['funnel_analysis']['overall_conversion'] ?? 2,
                'monthly_revenue' => $currentRevenue,
            ],
            '30_days' => [
                'score' => min(100, $currentScore + $monthlyImprovement),
                'score_improvement' => $monthlyImprovement,
                'leads_weekly' => round($currentLeads / 4 * 1.3),
                'leads_improvement' => '+30%',
                'conversion' => round(($results['funnel_analysis']['overall_conversion'] ?? 2) * 1.5, 1),
                'conversion_improvement' => '+50%',
                'description' => 'Ideal mijoz aniqlanadi, taklif optimallashtiriladi',
            ],
            '60_days' => [
                'score' => min(100, $currentScore + $monthlyImprovement * 1.8),
                'score_improvement' => round($monthlyImprovement * 1.8),
                'leads_weekly' => round($currentLeads / 4 * 1.8),
                'leads_improvement' => '+80%',
                'revenue_improvement' => '+40%',
                'monthly_revenue' => round($currentRevenue * 1.4),
                'description' => 'Marketing kanallari yaxshilanadi, sotuvlar barqarorlashadi',
            ],
            '90_days' => [
                'score' => min(100, $currentScore + $monthlyImprovement * 2.5),
                'score_improvement' => round($monthlyImprovement * 2.5),
                'leads_weekly' => round($currentLeads / 4 * 2.5),
                'leads_improvement' => '+150%',
                'revenue_improvement' => '+80%',
                'monthly_revenue' => round($currentRevenue * 1.8),
                'description' => 'To\'liq tizim ishlaydi, avtomatlashtirish tugallangan',
            ],
        ];
    }

    /**
     * Calculate data completeness
     */
    protected function calculateDataCompleteness(array $businessData): array
    {
        $requiredFields = [
            'basic' => ['name', 'industry'],
            'dream_buyer' => ['has_dream_buyer'],
            'offers' => ['has_offers'],
            'channels' => ['connected_channels'],
            'maturity' => ['maturity_assessment'],
        ];

        $completeness = [];
        foreach ($requiredFields as $category => $fields) {
            $filled = 0;
            foreach ($fields as $field) {
                if (!empty($businessData[$field])) {
                    $filled++;
                }
            }
            $completeness[$category] = round(($filled / count($fields)) * 100);
        }

        $completeness['overall'] = round(array_sum($completeness) / count($completeness));

        return $completeness;
    }

    /**
     * Format time for display
     */
    protected function formatTime(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' daqiqa';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours . ' soat';
        }

        return $hours . ' soat ' . $remainingMinutes . ' daqiqa';
    }
}
