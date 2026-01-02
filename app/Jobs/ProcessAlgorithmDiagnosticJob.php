<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\AIDiagnostic;
use App\Services\Algorithm\DiagnosticAlgorithmService;
use App\Services\Algorithm\Performance\AlgorithmQueueManager;
use App\Services\Algorithm\Performance\AlgorithmCacheManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process Algorithm Diagnostic Job
 *
 * Handles diagnostic calculations using internal algorithms (no AI).
 * Optimized for high-load scenarios with 100+ concurrent requests.
 *
 * Features:
 * - Rate limiting
 * - Duplicate prevention
 * - Smart retries
 * - Cache warming
 *
 * @version 1.0.0
 */
class ProcessAlgorithmDiagnosticJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retries
     */
    public int $tries = 3;

    /**
     * Timeout in seconds
     */
    public int $timeout = 120;

    /**
     * Delete job if model is missing
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Job ID for tracking
     */
    public string $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Business $business,
        public ?AIDiagnostic $diagnostic = null,
        ?string $jobId = null
    ) {
        $this->jobId = $jobId ?? $this->generateJobId();
    }

    /**
     * Get unique ID for job
     */
    public function uniqueId(): string
    {
        return 'algo_diag_' . $this->business->id;
    }

    /**
     * How long to maintain uniqueness lock
     */
    public function uniqueFor(): int
    {
        return 60; // 1 minute
    }

    /**
     * Get middleware for this job
     */
    public function middleware(): array
    {
        return [
            // Prevent concurrent diagnostics for same business
            (new WithoutOverlapping($this->business->id))
                ->releaseAfter(60)
                ->expireAfter(180),

            // Rate limit: max 100 diagnostics per minute
            new RateLimited('algorithm-diagnostics'),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        DiagnosticAlgorithmService $algorithmService,
        AlgorithmCacheManager $cacheManager
    ): void {
        $startTime = microtime(true);

        Log::info("Starting algorithm diagnostic job", [
            'job_id' => $this->jobId,
            'business_id' => $this->business->id,
            'has_existing_diagnostic' => $this->diagnostic !== null,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Get benchmarks
            $benchmarks = $this->getBenchmarks();

            // Run algorithm-based diagnostic
            $result = $algorithmService->runFullDiagnostic($this->business, $benchmarks);

            // Processing time
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            // Update or create diagnostic record
            $this->saveDiagnosticResult($result, $processingTime);

            // Warm cache for future requests
            $cacheManager->warmCache($this->business, fn() => $result);

            Log::info("Algorithm diagnostic completed", [
                'job_id' => $this->jobId,
                'business_id' => $this->business->id,
                'overall_score' => $result['overall_score'],
                'processing_time_ms' => $processingTime,
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
            throw $e; // Re-throw for retry
        }
    }

    /**
     * Save diagnostic result to database
     */
    protected function saveDiagnosticResult(array $result, float $processingTime): void
    {
        $diagnosticData = [
            'business_id' => $this->business->id,
            'overall_score' => $result['overall_score'],
            'status' => $result['status']['level'],
            'health_score' => $result['health_score']['score'] ?? null,
            'dream_buyer_score' => $result['dream_buyer_analysis']['score'] ?? null,
            'offer_score' => $result['offer_strength']['score'] ?? null,
            'funnel_score' => $result['funnel_analysis']['score'] ?? null,
            'engagement_score' => $result['engagement_metrics']['score'] ?? null,
            'content_score' => $result['content_optimization']['score'] ?? null,

            // Money calculations
            'money_loss_monthly' => $result['money_loss']['total_monthly_loss'] ?? 0,
            'money_loss_breakdown' => $result['money_loss']['breakdown'] ?? [],

            // Forecasts
            'revenue_forecast' => $result['revenue_forecast'] ?? [],
            'churn_risk' => $result['churn_risk'] ?? [],

            // Action items
            'action_priorities' => $result['action_priorities'] ?? [],
            'roi_calculations' => $result['roi_calculations'] ?? [],
            'expected_results' => $result['expected_results'] ?? [],

            // Full result for detailed view
            'full_analysis' => $result,

            // Metadata
            'algorithm_version' => $result['_meta']['algorithm_version'] ?? '2.0.0',
            'processing_time_ms' => $processingTime,
            'processed_at' => now(),
            'error_message' => null,
        ];

        if ($this->diagnostic) {
            // Update existing
            $this->diagnostic->update($diagnosticData);
        } else {
            // Create new
            AIDiagnostic::create($diagnosticData);
        }
    }

    /**
     * Handle job error
     */
    protected function handleError(Throwable $e): void
    {
        Log::error("Algorithm diagnostic error", [
            'job_id' => $this->jobId,
            'business_id' => $this->business->id,
            'error' => $e->getMessage(),
            'attempt' => $this->attempts(),
        ]);

        if ($this->diagnostic) {
            $this->diagnostic->update([
                'status' => $this->attempts() >= $this->tries ? 'failed' : 'retrying',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure (all retries exhausted)
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Algorithm diagnostic permanently failed", [
            'job_id' => $this->jobId,
            'business_id' => $this->business->id,
            'error' => $exception->getMessage(),
        ]);

        if ($this->diagnostic) {
            $this->diagnostic->update([
                'status' => 'failed',
                'error_message' => 'Algoritmik diagnostika muvaffaqiyatsiz: ' . $exception->getMessage(),
            ]);
        }
    }

    /**
     * Get industry benchmarks
     */
    protected function getBenchmarks(): array
    {
        $industry = $this->business->category ?? $this->business->industry ?? 'default';

        // Try to load from database
        $benchmark = \App\Models\IndustryBenchmark::where('industry', $industry)->first();

        if ($benchmark) {
            return $benchmark->benchmarks ?? $benchmark->toArray();
        }

        return $this->getDefaultBenchmarks();
    }

    /**
     * Default industry benchmarks
     */
    protected function getDefaultBenchmarks(): array
    {
        return [
            'conversion_rate' => 2.5,
            'engagement_rate' => 3.0,
            'response_time_hours' => 2,
            'customer_retention' => 70,
            'cac_ltv_ratio' => 3.0,
            'repeat_purchase_rate' => 25,
            'funnel_conversion' => [
                'awareness_to_interest' => 30,
                'interest_to_consideration' => 50,
                'consideration_to_intent' => 40,
                'intent_to_purchase' => 25,
            ],
        ];
    }

    /**
     * Generate unique job ID
     */
    protected function generateJobId(): string
    {
        return sprintf(
            'algo_%d_%s_%s',
            $this->business->id,
            now()->format('YmdHis'),
            substr(md5(uniqid(mt_rand(), true)), 0, 8)
        );
    }

    /**
     * Retry delay
     */
    public function backoff(): array
    {
        return [5, 15, 30]; // 5s, 15s, 30s
    }
}
