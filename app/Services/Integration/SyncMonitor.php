<?php

namespace App\Services\Integration;

use App\Models\Business;
use App\Models\KpiDailyActual;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMonitor
{
    /**
     * Health check thresholds
     */
    protected const THRESHOLDS = [
        'success_rate_warning' => 80,      // Warn if success rate < 80%
        'success_rate_critical' => 60,     // Critical if success rate < 60%
        'avg_duration_warning' => 300,     // Warn if avg duration > 5 minutes
        'avg_duration_critical' => 600,    // Critical if avg duration > 10 minutes
        'failed_businesses_warning' => 10, // Warn if 10+ businesses failed
        'failed_businesses_critical' => 30, // Critical if 30+ businesses failed
    ];

    /**
     * Get current sync health status
     *
     * @param string|null $date Date to check (Y-m-d format). Defaults to today
     * @return array Health status
     */
    public function getHealthStatus(?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        // Get overall stats from cache
        $overallStats = cache()->get("kpi_sync_overall_stats:{$date}");

        if (!$overallStats) {
            return [
                'status' => 'unknown',
                'message' => 'No sync data available for this date',
                'date' => $date,
            ];
        }

        // Calculate health metrics
        $totalBusinesses = $overallStats['total_businesses'] ?? 0;
        $totalSuccess = $overallStats['total_success'] ?? 0;
        $totalFailed = $overallStats['total_failed'] ?? 0;
        $duration = $overallStats['duration_seconds'] ?? 0;

        $successRate = $totalBusinesses > 0
            ? round(($totalSuccess / $totalBusinesses) * 100, 2)
            : 0;

        $avgDuration = $totalBusinesses > 0
            ? round($duration / $totalBusinesses, 2)
            : 0;

        // Determine health status
        $status = $this->calculateHealthStatus($successRate, $avgDuration, $totalFailed);

        return [
            'status' => $status,
            'date' => $date,
            'metrics' => [
                'total_businesses' => $totalBusinesses,
                'successful' => $totalSuccess,
                'failed' => $totalFailed,
                'success_rate' => $successRate,
                'total_duration_seconds' => $duration,
                'average_duration_seconds' => $avgDuration,
                'total_batches' => $overallStats['total_batches'] ?? 0,
                'processed_batches' => $overallStats['processed_batches'] ?? 0,
            ],
            'thresholds' => self::THRESHOLDS,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate overall health status based on metrics
     *
     * @param float $successRate Success rate percentage
     * @param float $avgDuration Average duration per business
     * @param int $failedCount Number of failed businesses
     * @return string 'healthy', 'warning', or 'critical'
     */
    protected function calculateHealthStatus(
        float $successRate,
        float $avgDuration,
        int $failedCount
    ): string {
        // Critical conditions
        if (
            $successRate < self::THRESHOLDS['success_rate_critical'] ||
            $avgDuration > self::THRESHOLDS['avg_duration_critical'] ||
            $failedCount > self::THRESHOLDS['failed_businesses_critical']
        ) {
            return 'critical';
        }

        // Warning conditions
        if (
            $successRate < self::THRESHOLDS['success_rate_warning'] ||
            $avgDuration > self::THRESHOLDS['avg_duration_warning'] ||
            $failedCount > self::THRESHOLDS['failed_businesses_warning']
        ) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get detailed batch statistics
     *
     * @param string $date Date to check (Y-m-d format)
     * @return array Batch statistics
     */
    public function getBatchStats(string $date): array
    {
        $overallStats = cache()->get("kpi_sync_overall_stats:{$date}");

        if (!$overallStats) {
            return [];
        }

        $totalBatches = $overallStats['total_batches'] ?? 0;
        $batches = [];

        for ($i = 0; $i < $totalBatches; $i++) {
            $batchStats = cache()->get("kpi_sync_batch_stats:{$date}:batch_{$i}");
            if ($batchStats) {
                $batches[] = $batchStats;
            }
        }

        return $batches;
    }

    /**
     * Get failed businesses for a specific date
     *
     * @param string $date Date to check (Y-m-d format)
     * @return array Failed business details
     */
    public function getFailedBusinesses(string $date): array
    {
        // Query businesses that should have synced but have no data
        $activeBusinesses = Business::whereHas('kpiConfiguration', function ($query) {
            $query->where('status', 'active');
        })->pluck('id');

        $syncedBusinesses = KpiDailyActual::where('date', $date)
            ->where('sync_status', 'synced')
            ->whereIn('business_id', $activeBusinesses)
            ->distinct('business_id')
            ->pluck('business_id');

        $failedBusinessIds = $activeBusinesses->diff($syncedBusinesses);

        // Optimize: Load last sync attempts in single query instead of N+1
        $lastSyncAttempts = KpiDailyActual::whereIn('business_id', $failedBusinessIds)
            ->where('date', $date)
            ->select('business_id', DB::raw('MAX(last_synced_at) as last_sync'))
            ->groupBy('business_id')
            ->pluck('last_sync', 'business_id');

        $failedBusinesses = Business::whereIn('id', $failedBusinessIds)
            ->with('kpiConfiguration')
            ->get()
            ->map(function ($business) use ($lastSyncAttempts) {
                return [
                    'business_id' => $business->id,
                    'business_name' => $business->name,
                    'industry_code' => $business->kpiConfiguration->industry_code ?? null,
                    'last_sync_attempt' => $lastSyncAttempts[$business->id] ?? null,
                ];
            });

        return $failedBusinesses->toArray();
    }

    /**
     * Get last sync attempt for a business (no longer needed - kept for backward compatibility)
     *
     * @param int $businessId Business ID
     * @param string $date Date
     * @return string|null ISO timestamp or null
     * @deprecated Use eager-loaded data instead
     */
    protected function getLastSyncAttempt(int $businessId, string $date): ?string
    {
        $lastActual = KpiDailyActual::where('business_id', $businessId)
            ->where('date', $date)
            ->orderBy('last_synced_at', 'desc')
            ->first();

        return $lastActual?->last_synced_at?->toIso8601String();
    }

    /**
     * Get sync performance trends over time
     *
     * @param int $days Number of days to analyze
     * @return array Trend data
     */
    public function getPerformanceTrends(int $days = 7): array
    {
        $trends = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $overallStats = cache()->get("kpi_sync_overall_stats:{$date}");

            if ($overallStats) {
                $totalBusinesses = $overallStats['total_businesses'] ?? 0;
                $successCount = $overallStats['total_success'] ?? 0;
                $failedCount = $overallStats['total_failed'] ?? 0;

                $trends[] = [
                    'date' => $date,
                    'total_businesses' => $totalBusinesses,
                    'successful' => $successCount,
                    'failed' => $failedCount,
                    'success_rate' => $totalBusinesses > 0
                        ? round(($successCount / $totalBusinesses) * 100, 2)
                        : 0,
                    'duration_seconds' => $overallStats['duration_seconds'] ?? 0,
                ];
            }
        }

        return array_reverse($trends);
    }

    /**
     * Get integration-specific statistics
     *
     * @param string $date Date to check
     * @return array Integration statistics
     */
    public function getIntegrationStats(string $date): array
    {
        $integrations = ['instagram_api', 'facebook_api', 'pos_system'];
        $integrationStats = [];

        foreach ($integrations as $integration) {
            // Optimize: Single query instead of 3 separate queries
            $queryResult = KpiDailyActual::where('date', $date)
                ->where('data_source', $integration)
                ->selectRaw('
                    COUNT(CASE WHEN sync_status = "synced" THEN 1 END) as synced_count,
                    COUNT(CASE WHEN sync_status = "failed" THEN 1 END) as failed_count,
                    AVG(CASE WHEN sync_status = "synced" THEN data_quality_score END) as avg_quality
                ')
                ->first();

            $syncedCount = $queryResult->synced_count ?? 0;
            $failedCount = $queryResult->failed_count ?? 0;
            $avgQualityScore = $queryResult->avg_quality;

            $integrationStats[$integration] = [
                'synced' => $syncedCount,
                'failed' => $failedCount,
                'success_rate' => ($syncedCount + $failedCount) > 0
                    ? round(($syncedCount / ($syncedCount + $failedCount)) * 100, 2)
                    : 0,
                'avg_quality_score' => round($avgQualityScore ?? 0, 2),
            ];
        }

        return $integrationStats;
    }

    /**
     * Check if sync is currently running
     *
     * @return array Running status
     */
    public function isRunning(): array
    {
        $lockKey = 'kpi_sync:running';
        $isRunning = Cache::has($lockKey);

        if (!$isRunning) {
            return [
                'running' => false,
                'message' => 'No sync currently running',
            ];
        }

        $syncInfo = Cache::get($lockKey);

        return [
            'running' => true,
            'started_at' => $syncInfo['started_at'] ?? null,
            'current_batch' => $syncInfo['current_batch'] ?? null,
            'total_batches' => $syncInfo['total_batches'] ?? null,
            'progress_percentage' => $syncInfo['progress_percentage'] ?? null,
        ];
    }

    /**
     * Mark sync as running
     *
     * @param array $info Sync information
     * @return void
     */
    public function markAsRunning(array $info): void
    {
        $lockKey = 'kpi_sync:running';
        Cache::put($lockKey, array_merge($info, [
            'started_at' => now()->toIso8601String(),
        ]), 3600); // 1 hour expiry
    }

    /**
     * Mark sync as completed
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        Cache::forget('kpi_sync:running');
    }

    /**
     * Get comprehensive dashboard data
     *
     * @param string|null $date Date to check
     * @return array Dashboard data
     */
    public function getDashboard(?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        return [
            'health' => $this->getHealthStatus($date),
            'batches' => $this->getBatchStats($date),
            'failed_businesses' => $this->getFailedBusinesses($date),
            'integrations' => $this->getIntegrationStats($date),
            'trends' => $this->getPerformanceTrends(7),
            'running_status' => $this->isRunning(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Alert on critical issues
     *
     * @param string $date Date to check
     * @return void
     */
    public function checkAndAlert(string $date): void
    {
        $health = $this->getHealthStatus($date);

        if ($health['status'] === 'critical') {
            Log::critical('KPI Sync Health Critical', [
                'date' => $date,
                'metrics' => $health['metrics'],
            ]);

            // You can add notification logic here (email, Slack, Telegram, etc.)
            // Example:
            // Notification::send($admins, new SyncHealthCritical($health));
        } elseif ($health['status'] === 'warning') {
            Log::warning('KPI Sync Health Warning', [
                'date' => $date,
                'metrics' => $health['metrics'],
            ]);
        } else {
            Log::info('KPI Sync Health OK', [
                'date' => $date,
                'success_rate' => $health['metrics']['success_rate'],
            ]);
        }
    }
}
