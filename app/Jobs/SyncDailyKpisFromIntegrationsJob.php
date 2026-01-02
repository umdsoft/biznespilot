<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Integration\FacebookKpiSyncService;
use App\Services\Integration\InstagramKpiSyncService;
use App\Services\Integration\PosKpiSyncService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDailyKpisFromIntegrationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $businessId;
    public $date;
    public $batchSize;
    public $batchNumber;
    public $tries = 3;
    public $timeout = 900; // 15 minutes
    public $maxExceptions = 3; // Maximum exceptions before failing

    /**
     * Create a new job instance.
     *
     * @param int|null $businessId If null, syncs all businesses
     * @param string|null $date Date to sync (Y-m-d format). If null, syncs yesterday
     * @param int $batchSize Number of businesses to process per batch
     * @param int|null $batchNumber Specific batch number to process (for manual triggers)
     */
    public function __construct(
        ?int $businessId = null,
        ?string $date = null,
        int $batchSize = 20,
        ?int $batchNumber = null
    ) {
        $this->businessId = $businessId;
        $this->date = $date ?? Carbon::yesterday()->format('Y-m-d');
        $this->batchSize = $batchSize;
        $this->batchNumber = $batchNumber;
    }

    /**
     * Get the unique ID for this job (prevents duplicate jobs in queue).
     * Jobs are unique per business_id + date combination.
     */
    public function uniqueId(): string
    {
        $businessKey = $this->businessId ?? 'all';
        return "sync_kpis_{$businessKey}_{$this->date}";
    }

    /**
     * How long the job's unique lock should be maintained (in seconds).
     * Set to 15 minutes to match job timeout.
     */
    public function uniqueFor(): int
    {
        return 900; // 15 minutes
    }

    /**
     * Execute the job.
     */
    public function handle(
        InstagramKpiSyncService $instagramSync,
        FacebookKpiSyncService $facebookSync,
        PosKpiSyncService $posSync
    ): void {
        Log::info('SyncDailyKpisFromIntegrationsJob started', [
            'business_id' => $this->businessId,
            'date' => $this->date,
        ]);

        // If specific business ID provided, sync only that business
        if ($this->businessId) {
            $this->syncBusiness($this->businessId, $instagramSync, $facebookSync, $posSync);
            return;
        }

        // Use batched processing for scalability
        $this->processBatchedSync($instagramSync, $facebookSync, $posSync);
    }

    /**
     * Process businesses in batches using distributed queue jobs (for 1000+ businesses)
     */
    protected function processBatchedSync(
        InstagramKpiSyncService $instagramSync,
        FacebookKpiSyncService $facebookSync,
        PosKpiSyncService $posSync
    ): void {
        $query = Business::whereHas('kpiConfiguration', function ($query) {
            $query->where('status', 'active');
        });

        // Get total count for logging
        $totalBusinesses = $query->count();

        Log::info("Starting distributed batched KPI sync", [
            'total_businesses' => $totalBusinesses,
            'batch_size' => $this->batchSize,
            'date' => $this->date,
        ]);

        // If specific batch number provided, process only that batch
        if ($this->batchNumber !== null) {
            $this->processSingleBatch(
                $this->batchNumber,
                $instagramSync,
                $facebookSync,
                $posSync
            );
            return;
        }

        // Calculate total batches needed
        $totalBatches = (int) ceil($totalBusinesses / $this->batchSize);

        // Initialize progress tracking
        $progressKey = "kpi_sync_progress:{$this->date}";
        cache()->put($progressKey . ':completed_batches', 0, now()->addHours(24));
        cache()->put($progressKey . ':total_success', 0, now()->addHours(24));
        cache()->put($progressKey . ':total_failed', 0, now()->addHours(24));

        // Mark sync as running
        $monitor = app(\App\Services\Integration\SyncMonitor::class);
        $monitor->markAsRunning([
            'total_batches' => $totalBatches,
            'total_businesses' => $totalBusinesses,
        ]);

        Log::info("Dispatching {$totalBatches} batch jobs to queue", [
            'date' => $this->date,
            'batch_size' => $this->batchSize,
        ]);

        // Dispatch all batch jobs to queue for parallel processing
        for ($batchNum = 0; $batchNum < $totalBatches; $batchNum++) {
            ProcessSingleBatchJob::dispatch(
                $batchNum,
                $this->date,
                $this->batchSize,
                $totalBusinesses
            );
        }

        Log::info("All {$totalBatches} batch jobs dispatched successfully");
    }

    /**
     * Process a single batch of businesses
     */
    protected function processSingleBatch(
        int $batchNumber,
        InstagramKpiSyncService $instagramSync,
        FacebookKpiSyncService $facebookSync,
        PosKpiSyncService $posSync
    ): array {
        $offset = $batchNumber * $this->batchSize;

        $businesses = Business::whereHas('kpiConfiguration', function ($query) {
            $query->where('status', 'active');
        })
            ->skip($offset)
            ->take($this->batchSize)
            ->get();

        $batchStats = [
            'batch_number' => $batchNumber,
            'business_count' => $businesses->count(),
            'success_count' => 0,
            'error_count' => 0,
            'started_at' => now(),
        ];

        Log::info("Processing batch {$batchNumber}", [
            'offset' => $offset,
            'limit' => $this->batchSize,
            'businesses_in_batch' => $businesses->count(),
        ]);

        foreach ($businesses as $business) {
            try {
                $this->syncBusiness($business->id, $instagramSync, $facebookSync, $posSync);
                $batchStats['success_count']++;

                // PERFORMANCE FIX: Removed sleep() - RateLimiter handles throttling automatically
                // Old code wasted 8+ minutes on 1000 businesses (500ms × 1000 = 500s)
                // RateLimiter in BaseKpiSyncService ensures API limits are respected
            } catch (\Exception $e) {
                Log::error("Failed to sync business {$business->id} in batch {$batchNumber}", [
                    'business_name' => $business->name,
                    'error' => $e->getMessage(),
                ]);
                $batchStats['error_count']++;
            }
        }

        $batchStats['completed_at'] = now();
        $batchStats['duration_seconds'] = $batchStats['started_at']->diffInSeconds($batchStats['completed_at']);

        Log::info("Batch {$batchNumber} completed", $batchStats);

        // Cache batch statistics
        cache()->put(
            "kpi_sync_batch_stats:{$this->date}:batch_{$batchNumber}",
            $batchStats,
            now()->addDays(7)
        );

        return $batchStats;
    }

    /**
     * Sync KPIs for a specific business from all available integrations
     */
    protected function syncBusiness(
        int $businessId,
        InstagramKpiSyncService $instagramSync,
        FacebookKpiSyncService $facebookSync,
        PosKpiSyncService $posSync
    ): void {
        $business = Business::find($businessId);
        if (!$business) {
            Log::warning("Business not found: {$businessId}");
            return;
        }

        Log::info("Starting KPI sync for business: {$businessId}", [
            'business_name' => $business->name,
            'date' => $this->date,
        ]);

        $syncResults = [
            'business_id' => $businessId,
            'business_name' => $business->name,
            'date' => $this->date,
            'integrations' => [],
        ];

        // Sync from Instagram
        if ($instagramSync->isAvailable($businessId)) {
            Log::info("Syncing Instagram KPIs for business {$businessId}");
            $result = $instagramSync->syncDailyKpis($businessId, $this->date);
            $syncResults['integrations']['instagram'] = $result;

            Log::info("Instagram sync completed", [
                'business_id' => $businessId,
                'synced' => $result['synced_count'],
                'failed' => $result['failed_count'],
            ]);
        } else {
            Log::debug("Instagram integration not available for business {$businessId}");
            $syncResults['integrations']['instagram'] = [
                'success' => false,
                'message' => 'Integration not available',
            ];
        }

        // Sync from Facebook
        if ($facebookSync->isAvailable($businessId)) {
            Log::info("Syncing Facebook KPIs for business {$businessId}");
            $result = $facebookSync->syncDailyKpis($businessId, $this->date);
            $syncResults['integrations']['facebook'] = $result;

            Log::info("Facebook sync completed", [
                'business_id' => $businessId,
                'synced' => $result['synced_count'],
                'failed' => $result['failed_count'],
            ]);
        } else {
            Log::debug("Facebook integration not available for business {$businessId}");
            $syncResults['integrations']['facebook'] = [
                'success' => false,
                'message' => 'Integration not available',
            ];
        }

        // Sync from POS system
        if ($posSync->isAvailable($businessId)) {
            Log::info("Syncing POS KPIs for business {$businessId}");
            $result = $posSync->syncDailyKpis($businessId, $this->date);
            $syncResults['integrations']['pos'] = $result;

            Log::info("POS sync completed", [
                'business_id' => $businessId,
                'synced' => $result['synced_count'],
                'failed' => $result['failed_count'],
            ]);
        } else {
            Log::debug("POS integration not available for business {$businessId}");
            $syncResults['integrations']['pos'] = [
                'success' => false,
                'message' => 'Integration not available',
            ];
        }

        // Calculate total sync statistics
        $totalSynced = 0;
        $totalFailed = 0;
        $availableIntegrations = 0;

        foreach ($syncResults['integrations'] as $integration => $result) {
            if (isset($result['synced_count'])) {
                $totalSynced += $result['synced_count'];
                $totalFailed += $result['failed_count'];
                $availableIntegrations++;
            }
        }

        Log::info("KPI sync summary for business {$businessId}", [
            'available_integrations' => $availableIntegrations,
            'total_synced' => $totalSynced,
            'total_failed' => $totalFailed,
            'date' => $this->date,
        ]);

        // Store sync results in cache for dashboard
        cache()->put(
            "kpi_sync_results:business_{$businessId}:{$this->date}",
            $syncResults,
            now()->addDays(7)
        );
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncDailyKpisFromIntegrationsJob failed', [
            'business_id' => $this->businessId,
            'date' => $this->date,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
