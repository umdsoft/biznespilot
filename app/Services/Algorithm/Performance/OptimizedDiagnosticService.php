<?php

namespace App\Services\Algorithm\Performance;

use App\Models\Business;
use App\Services\Algorithm\ChurnRiskAlgorithm;
use App\Services\Algorithm\CompetitorBenchmarkAlgorithm;
use App\Services\Algorithm\ContentOptimizationAlgorithm;
use App\Services\Algorithm\DiagnosticAlgorithmService;
use App\Services\Algorithm\DreamBuyerScoringAlgorithm;
use App\Services\Algorithm\EngagementAlgorithm;
use App\Services\Algorithm\FunnelAnalysisAlgorithm;
use App\Services\Algorithm\HealthScoreAlgorithm;
use App\Services\Algorithm\MoneyLossAlgorithm;
use App\Services\Algorithm\RevenueForecaster;
use App\Services\Algorithm\ValueEquationAlgorithm;
use Illuminate\Support\Facades\Log;

/**
 * Optimized Diagnostic Service
 *
 * High-performance diagnostic service that handles 100+ concurrent requests.
 * Uses intelligent caching, parallel execution, and rate limiting.
 *
 * Performance Features:
 * - Multi-layer caching (request, application, computed)
 * - Parallel algorithm execution where possible
 * - Rate limiting to prevent overload
 * - Lazy loading of expensive calculations
 * - Memory-efficient processing
 *
 * @version 1.0.0
 */
class OptimizedDiagnosticService
{
    protected DiagnosticAlgorithmService $baseService;

    protected AlgorithmCacheManager $cache;

    protected AsyncAlgorithmRunner $runner;

    protected RateLimiter $rateLimiter;

    protected AlgorithmQueueManager $queue;

    /**
     * Sub-calculators (lazy loaded)
     */
    protected ?HealthScoreAlgorithm $healthScoreAlgorithm = null;

    protected ?MoneyLossAlgorithm $moneyLossAlgorithm = null;

    protected ?FunnelAnalysisAlgorithm $funnelAnalysisAlgorithm = null;

    protected ?EngagementAlgorithm $engagementAlgorithm = null;

    protected ?ValueEquationAlgorithm $valueEquationAlgorithm = null;

    protected ?ChurnRiskAlgorithm $churnRiskAlgorithm = null;

    protected ?RevenueForecaster $revenueForecaster = null;

    protected ?ContentOptimizationAlgorithm $contentOptimizationAlgorithm = null;

    protected ?DreamBuyerScoringAlgorithm $dreamBuyerScoringAlgorithm = null;

    protected ?CompetitorBenchmarkAlgorithm $competitorBenchmarkAlgorithm = null;

    public function __construct(
        DiagnosticAlgorithmService $baseService,
        AlgorithmCacheManager $cache,
        AsyncAlgorithmRunner $runner,
        RateLimiter $rateLimiter,
        AlgorithmQueueManager $queue
    ) {
        $this->baseService = $baseService;
        $this->cache = $cache;
        $this->runner = $runner;
        $this->rateLimiter = $rateLimiter;
        $this->queue = $queue;
    }

    /**
     * Run full diagnostic with caching and optimization
     *
     * @param  array  $options  [use_cache, async, force_refresh]
     */
    public function runDiagnostic(Business $business, array $benchmarks = [], array $options = []): array
    {
        $startTime = microtime(true);
        $useCache = $options['use_cache'] ?? true;
        $forceRefresh = $options['force_refresh'] ?? false;

        // Rate limit check
        if (! $this->rateLimiter->attempt("business:{$business->id}", 'diagnostic')) {
            return $this->getRateLimitedResponse($business);
        }

        try {
            // Try cache first (unless force refresh)
            if ($useCache && ! $forceRefresh) {
                $cached = $this->cache->diagnostic($business, function () use ($business, $benchmarks) {
                    return $this->computeDiagnostic($business, $benchmarks);
                });

                if ($cached) {
                    $cached['_meta']['from_cache'] = true;
                    $cached['_meta']['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

                    return $cached;
                }
            }

            // Compute fresh
            $result = $this->computeDiagnostic($business, $benchmarks);
            $result['_meta']['from_cache'] = false;
            $result['_meta']['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

            return $result;

        } catch (\Exception $e) {
            Log::error('Diagnostic error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getErrorResponse($business, $e);
        }
    }

    /**
     * Compute diagnostic using parallel execution
     */
    protected function computeDiagnostic(Business $business, array $benchmarks): array
    {
        $startTime = microtime(true);

        // Collect data first (single DB query batch)
        $businessData = $this->collectBusinessDataOptimized($business);
        $metrics = $this->collectMetricsOptimized($business);

        // Run algorithms using parallel runner
        $algorithms = $this->prepareAlgorithms($business, $metrics, $benchmarks);
        $algorithmResults = $this->runner->runParallel($algorithms);

        // Extract results
        $results = $algorithmResults['results'];

        // Calculate aggregates
        $results['overall_score'] = $this->calculateOverallScore($results);
        $results['status'] = $this->getStatusFromScore($results['overall_score']);
        $results['roi_calculations'] = $this->calculateROI($results);
        $results['action_priorities'] = $this->prioritizeActions($results);
        $results['expected_results'] = $this->calculateExpectedResults($results);

        // Meta info
        $results['_meta'] = [
            'calculated_at' => now()->toIso8601String(),
            'calculation_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'algorithm_version' => '2.0.0-optimized',
            'algorithm_times' => $algorithmResults['execution_times'] ?? [],
            'data_completeness' => $this->calculateDataCompleteness($businessData),
        ];

        Log::info('Optimized diagnostic completed', [
            'business_id' => $business->id,
            'overall_score' => $results['overall_score'],
            'calculation_time_ms' => $results['_meta']['calculation_time_ms'],
        ]);

        return $results;
    }

    /**
     * Prepare algorithms for parallel execution
     */
    protected function prepareAlgorithms(Business $business, array $metrics, array $benchmarks): array
    {
        return [
            'health_score' => fn () => $this->getHealthScoreAlgorithm()->calculate($business, $metrics, $benchmarks),
            'dream_buyer_analysis' => fn () => $this->getDreamBuyerScoringAlgorithm()->calculate($business),
            'offer_strength' => fn () => $this->getValueEquationAlgorithm()->calculate($business),
            'money_loss' => fn () => $this->getMoneyLossAlgorithm()->calculate($business, $metrics, $benchmarks),
            'funnel_analysis' => fn () => $this->getFunnelAnalysisAlgorithm()->calculate($business, $metrics),
            'engagement_metrics' => fn () => $this->getEngagementAlgorithm()->calculate($business),
            'content_optimization' => fn () => $this->getContentOptimizationAlgorithm()->calculate($business),
            'churn_risk' => fn () => $this->getChurnRiskAlgorithm()->calculate($business),
            'revenue_forecast' => fn () => $this->getRevenueForecaster()->calculate($business),
            'competitor_benchmark' => fn () => $this->getCompetitorBenchmarkAlgorithm()->calculate($business, $benchmarks),
        ];
    }

    /**
     * Run diagnostic asynchronously via queue
     */
    public function runAsync(Business $business, string $priority = 'default'): string
    {
        return $this->queue->queueDiagnostic($business, $priority);
    }

    /**
     * Check async diagnostic status
     */
    public function checkStatus(string $jobId): array
    {
        return $this->queue->getStatusInfo($jobId);
    }

    /**
     * Get async diagnostic result
     */
    public function getAsyncResult(string $jobId): ?array
    {
        return $this->queue->getResult($jobId);
    }

    /**
     * Wait for async result
     */
    public function waitForResult(string $jobId, int $maxWaitSeconds = 30): ?array
    {
        return $this->queue->waitForResult($jobId, $maxWaitSeconds);
    }

    /**
     * Run single algorithm with caching
     */
    public function runSingleAlgorithm(string $name, Business $business, array $options = []): array
    {
        return $this->cache->algorithm($name, $business, function () use ($name, $business, $options) {
            return match ($name) {
                'health_score' => $this->getHealthScoreAlgorithm()->calculate($business, $options['metrics'] ?? [], $options['benchmarks'] ?? []),
                'dream_buyer' => $this->getDreamBuyerScoringAlgorithm()->calculate($business),
                'offer_strength' => $this->getValueEquationAlgorithm()->calculate($business),
                'money_loss' => $this->getMoneyLossAlgorithm()->calculate($business, $options['metrics'] ?? [], $options['benchmarks'] ?? []),
                'funnel' => $this->getFunnelAnalysisAlgorithm()->calculate($business, $options['metrics'] ?? []),
                'engagement' => $this->getEngagementAlgorithm()->calculate($business),
                'content' => $this->getContentOptimizationAlgorithm()->calculate($business),
                'churn_risk' => $this->getChurnRiskAlgorithm()->calculate($business),
                'revenue' => $this->getRevenueForecaster()->calculate($business),
                'competitor' => $this->getCompetitorBenchmarkAlgorithm()->calculate($business, $options['benchmarks'] ?? []),
                default => throw new \InvalidArgumentException("Unknown algorithm: {$name}"),
            };
        });
    }

    /**
     * Batch process multiple businesses
     */
    public function batchDiagnostic(array $businesses, array $benchmarks = []): array
    {
        return $this->runner->batchProcess(
            $businesses,
            fn ($business) => $this->runDiagnostic($business, $benchmarks, ['use_cache' => true]),
            10 // Batch size
        );
    }

    /**
     * Invalidate cache for business
     */
    public function invalidateCache(Business $business): void
    {
        $this->cache->invalidateBusiness($business);
    }

    /**
     * Pre-warm cache
     */
    public function warmCache(Business $business, array $benchmarks = []): void
    {
        $this->cache->warmCache($business, fn () => $this->computeDiagnostic($business, $benchmarks));
    }

    /**
     * Collect business data with optimized queries
     */
    protected function collectBusinessDataOptimized(Business $business): array
    {
        // Use eager loading to minimize queries
        $business->loadMissing([
            'dreamBuyers',
            'offers',
            'integrations',
            'maturityAssessment',
        ]);

        return [
            'id' => $business->id,
            'name' => $business->name,
            'industry' => $business->category ?? $business->industry ?? 'default',
            'team_size' => $business->team_size ?? 1,
            'has_dream_buyer' => $business->dreamBuyers->isNotEmpty(),
            'has_offers' => $business->offers->isNotEmpty(),
            'connected_channels' => $this->getConnectedChannels($business),
            'maturity_assessment' => $business->maturityAssessment?->toArray(),
        ];
    }

    /**
     * Collect metrics with optimized queries
     */
    protected function collectMetricsOptimized(Business $business): array
    {
        // Load relationships in single query
        $business->loadMissing([
            'salesMetrics',
            'marketingMetrics',
            'instagramAccounts',
            'leads',
        ]);

        return [
            'sales' => $this->collectSalesMetrics($business),
            'marketing' => $this->collectMarketingMetrics($business),
            'social' => $this->collectSocialMetrics($business),
            'funnel' => $this->collectFunnelMetrics($business),
        ];
    }

    /**
     * Get connected channels
     */
    protected function getConnectedChannels(Business $business): array
    {
        $channels = [];

        if ($business->instagramAccounts->isNotEmpty()) {
            $channels[] = 'instagram';
        }

        $integrationTypes = $business->integrations
            ->where('status', 'connected')
            ->pluck('type')
            ->toArray();

        return array_unique(array_merge($channels, $integrationTypes));
    }

    /**
     * Collect sales metrics
     */
    protected function collectSalesMetrics(Business $business): array
    {
        $metrics = $business->salesMetrics;

        return [
            'monthly_revenue' => $metrics?->monthly_revenue ?? 0,
            'monthly_leads' => $metrics?->monthly_leads ?? 0,
            'conversion_rate' => $metrics?->conversion_rate ?? 0,
            'average_deal_size' => $metrics?->average_deal_value ?? 0,
            'sales_cycle_days' => $metrics?->sales_cycle_days ?? 30,
            'cac' => $metrics?->customer_acquisition_cost ?? 0,
            'ltv' => $metrics?->customer_lifetime_value ?? 0,
            'repeat_purchase_rate' => $metrics?->repeat_purchase_rate ?? 0,
        ];
    }

    /**
     * Collect marketing metrics
     */
    protected function collectMarketingMetrics(Business $business): array
    {
        $metrics = $business->marketingMetrics;

        return [
            'monthly_budget' => $metrics?->monthly_budget ?? 0,
            'ad_spend' => $metrics?->ad_spend ?? 0,
            'impressions' => $metrics?->impressions ?? 0,
            'clicks' => $metrics?->clicks ?? 0,
            'ctr' => $metrics?->ctr ?? 0,
            'cpc' => $metrics?->cpc ?? 0,
            'cpl' => $metrics?->cost_per_lead ?? 0,
            'roas' => $metrics?->roas ?? 0,
        ];
    }

    /**
     * Collect social metrics
     */
    protected function collectSocialMetrics(Business $business): array
    {
        $metrics = [];

        $instagram = $business->instagramAccounts->first();
        if ($instagram) {
            $metrics['instagram'] = [
                'connected' => true,
                'followers' => $instagram->followers_count ?? 0,
                'posts_count' => $instagram->media_count ?? 0,
            ];
        } else {
            $metrics['instagram'] = ['connected' => false];
        }

        $telegram = $business->integrations->where('type', 'telegram')->first();
        $metrics['telegram'] = $telegram
            ? ['connected' => true, 'subscribers' => $telegram->metadata['subscribers'] ?? 0]
            : ['connected' => false];

        $facebook = $business->integrations->where('type', 'facebook')->first();
        $metrics['facebook'] = $facebook
            ? ['connected' => true, 'followers' => $facebook->metadata['followers'] ?? 0]
            : ['connected' => false];

        return $metrics;
    }

    /**
     * Collect funnel metrics
     */
    protected function collectFunnelMetrics(Business $business): array
    {
        $stages = ['awareness', 'interest', 'consideration', 'intent', 'purchase'];
        $thirtyDaysAgo = now()->subDays(30);

        $leadCounts = [];
        foreach ($stages as $stage) {
            $leadCounts[$stage] = $business->leads
                ->where('stage', $stage)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count();
        }

        return [
            'stages' => $leadCounts,
            'total_leads' => array_sum($leadCounts),
            'converted_leads' => $leadCounts['purchase'] ?? 0,
        ];
    }

    /**
     * Calculate overall score
     */
    protected function calculateOverallScore(array $results): int
    {
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
            return ['level' => 'excellent', 'label' => 'Ajoyib', 'color' => 'blue', 'emoji' => '🚀'];
        }
        if ($score >= 60) {
            return ['level' => 'good', 'label' => 'Yaxshi', 'color' => 'green', 'emoji' => '✅'];
        }
        if ($score >= 40) {
            return ['level' => 'average', 'label' => 'O\'rtacha', 'color' => 'yellow', 'emoji' => '⚠️'];
        }

        return ['level' => 'weak', 'label' => 'Zaif', 'color' => 'red', 'emoji' => '🔴'];
    }

    /**
     * Calculate ROI (simplified)
     */
    protected function calculateROI(array $results): array
    {
        return $this->baseService->calculateROI($results);
    }

    /**
     * Prioritize actions
     */
    protected function prioritizeActions(array $results): array
    {
        return $this->baseService->prioritizeActions($results);
    }

    /**
     * Calculate expected results
     */
    protected function calculateExpectedResults(array $results): array
    {
        return $this->baseService->calculateExpectedResults($results);
    }

    /**
     * Calculate data completeness
     */
    protected function calculateDataCompleteness(array $businessData): array
    {
        $checks = [
            'basic' => ! empty($businessData['name']) && ! empty($businessData['industry']),
            'dream_buyer' => $businessData['has_dream_buyer'],
            'offers' => $businessData['has_offers'],
            'channels' => ! empty($businessData['connected_channels']),
            'maturity' => ! empty($businessData['maturity_assessment']),
        ];

        $score = array_sum(array_map(fn ($v) => $v ? 100 : 0, $checks)) / count($checks);

        return [
            'checks' => $checks,
            'overall' => round($score),
        ];
    }

    /**
     * Get rate limited response
     */
    protected function getRateLimitedResponse(Business $business): array
    {
        $stats = $this->rateLimiter->getStats("business:{$business->id}", 'diagnostic');

        return [
            'error' => true,
            'code' => 'RATE_LIMITED',
            'message' => "So'rovlar limiti oshdi. {$stats['available_in_seconds']} sekunddan so'ng qayta urinib ko'ring.",
            'retry_after' => $stats['available_in_seconds'],
            'overall_score' => 0,
            'status' => ['level' => 'error', 'label' => 'Xatolik'],
        ];
    }

    /**
     * Get error response
     */
    protected function getErrorResponse(Business $business, \Exception $e): array
    {
        return [
            'error' => true,
            'code' => 'CALCULATION_ERROR',
            'message' => 'Hisoblashda xatolik yuz berdi',
            'overall_score' => 0,
            'status' => ['level' => 'error', 'label' => 'Xatolik'],
            '_meta' => [
                'error' => $e->getMessage(),
                'calculated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Get performance stats
     */
    public function getPerformanceStats(): array
    {
        return [
            'runner' => $this->runner->getStats(),
            'cache' => $this->cache->getStats(),
            'queue' => $this->queue->getQueueStats(),
        ];
    }

    // Lazy-loaded algorithm getters
    protected function getHealthScoreAlgorithm(): HealthScoreAlgorithm
    {
        return $this->healthScoreAlgorithm ??= new HealthScoreAlgorithm;
    }

    protected function getMoneyLossAlgorithm(): MoneyLossAlgorithm
    {
        return $this->moneyLossAlgorithm ??= new MoneyLossAlgorithm;
    }

    protected function getFunnelAnalysisAlgorithm(): FunnelAnalysisAlgorithm
    {
        return $this->funnelAnalysisAlgorithm ??= new FunnelAnalysisAlgorithm;
    }

    protected function getEngagementAlgorithm(): EngagementAlgorithm
    {
        return $this->engagementAlgorithm ??= new EngagementAlgorithm;
    }

    protected function getValueEquationAlgorithm(): ValueEquationAlgorithm
    {
        return $this->valueEquationAlgorithm ??= new ValueEquationAlgorithm;
    }

    protected function getChurnRiskAlgorithm(): ChurnRiskAlgorithm
    {
        return $this->churnRiskAlgorithm ??= new ChurnRiskAlgorithm;
    }

    protected function getRevenueForecaster(): RevenueForecaster
    {
        return $this->revenueForecaster ??= new RevenueForecaster;
    }

    protected function getContentOptimizationAlgorithm(): ContentOptimizationAlgorithm
    {
        return $this->contentOptimizationAlgorithm ??= new ContentOptimizationAlgorithm;
    }

    protected function getDreamBuyerScoringAlgorithm(): DreamBuyerScoringAlgorithm
    {
        return $this->dreamBuyerScoringAlgorithm ??= new DreamBuyerScoringAlgorithm;
    }

    protected function getCompetitorBenchmarkAlgorithm(): CompetitorBenchmarkAlgorithm
    {
        return $this->competitorBenchmarkAlgorithm ??= new CompetitorBenchmarkAlgorithm;
    }
}
