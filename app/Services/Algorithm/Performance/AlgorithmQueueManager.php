<?php

namespace App\Services\Algorithm\Performance;

use App\Models\Business;
use App\Jobs\ProcessDiagnosticJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Algorithm Queue Manager
 *
 * Handles heavy diagnostic calculations via queue system.
 * Enables async processing and prevents request timeouts.
 *
 * Use Cases:
 * - Batch diagnostics for multiple businesses
 * - Heavy calculations during peak loads
 * - Background recalculation after data updates
 *
 * @version 1.0.0
 */
class AlgorithmQueueManager
{
    /**
     * Queue names
     */
    protected const QUEUE_HIGH = 'diagnostic-high';
    protected const QUEUE_DEFAULT = 'diagnostic';
    protected const QUEUE_LOW = 'diagnostic-low';

    /**
     * Status keys
     */
    protected const STATUS_PREFIX = 'diagnostic_status:';
    protected const RESULT_PREFIX = 'diagnostic_result:';

    /**
     * Status values
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Queue a diagnostic calculation
     *
     * @param Business $business
     * @param string $priority high|default|low
     * @return string Job ID for status tracking
     */
    public function queueDiagnostic(Business $business, string $priority = 'default'): string
    {
        $jobId = $this->generateJobId($business);
        $queue = $this->getQueueName($priority);

        // Set initial status
        $this->setStatus($jobId, self::STATUS_PENDING, [
            'business_id' => $business->id,
            'queued_at' => now()->toIso8601String(),
            'queue' => $queue,
        ]);

        // Dispatch job
        ProcessDiagnosticJob::dispatch($business, $jobId)
            ->onQueue($queue);

        Log::info("Diagnostic queued", [
            'job_id' => $jobId,
            'business_id' => $business->id,
            'queue' => $queue,
        ]);

        return $jobId;
    }

    /**
     * Queue diagnostics for multiple businesses
     */
    public function queueBatch(array $businesses, string $priority = 'low'): array
    {
        $jobIds = [];

        foreach ($businesses as $business) {
            $jobIds[$business->id] = $this->queueDiagnostic($business, $priority);
        }

        Log::info("Batch diagnostic queued", [
            'count' => count($businesses),
            'queue' => $this->getQueueName($priority),
        ]);

        return $jobIds;
    }

    /**
     * Check if diagnostic result is ready
     */
    public function isReady(string $jobId): bool
    {
        $status = $this->getStatus($jobId);
        return $status === self::STATUS_COMPLETED;
    }

    /**
     * Get diagnostic status
     */
    public function getStatus(string $jobId): ?string
    {
        $data = Cache::get(self::STATUS_PREFIX . $jobId);
        return $data['status'] ?? null;
    }

    /**
     * Get full status info
     */
    public function getStatusInfo(string $jobId): array
    {
        return Cache::get(self::STATUS_PREFIX . $jobId) ?? [
            'status' => 'not_found',
            'error' => 'Job not found',
        ];
    }

    /**
     * Get diagnostic result (if ready)
     */
    public function getResult(string $jobId): ?array
    {
        if (!$this->isReady($jobId)) {
            return null;
        }

        return Cache::get(self::RESULT_PREFIX . $jobId);
    }

    /**
     * Wait for result with polling
     *
     * @param string $jobId
     * @param int $maxWaitSeconds Maximum time to wait
     * @param int $pollIntervalMs Polling interval in milliseconds
     * @return array|null Result or null if timeout
     */
    public function waitForResult(string $jobId, int $maxWaitSeconds = 30, int $pollIntervalMs = 200): ?array
    {
        $maxWait = $maxWaitSeconds * 1000000; // Convert to microseconds
        $pollInterval = $pollIntervalMs * 1000;
        $waited = 0;

        while ($waited < $maxWait) {
            $status = $this->getStatus($jobId);

            if ($status === self::STATUS_COMPLETED) {
                return $this->getResult($jobId);
            }

            if ($status === self::STATUS_FAILED) {
                return $this->getStatusInfo($jobId);
            }

            usleep($pollInterval);
            $waited += $pollInterval;
        }

        return null; // Timeout
    }

    /**
     * Set job status (called by job)
     */
    public function setStatus(string $jobId, string $status, array $data = []): void
    {
        $statusData = array_merge($data, [
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ]);

        Cache::put(self::STATUS_PREFIX . $jobId, $statusData, 3600); // 1 hour TTL
    }

    /**
     * Store result (called by job)
     */
    public function storeResult(string $jobId, array $result): void
    {
        Cache::put(self::RESULT_PREFIX . $jobId, $result, 3600); // 1 hour TTL

        $this->setStatus($jobId, self::STATUS_COMPLETED, [
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markFailed(string $jobId, string $error): void
    {
        $this->setStatus($jobId, self::STATUS_FAILED, [
            'error' => $error,
            'failed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Generate unique job ID
     */
    protected function generateJobId(Business $business): string
    {
        return sprintf(
            'diag_%d_%s_%s',
            $business->id,
            now()->format('YmdHis'),
            substr(md5(uniqid()), 0, 8)
        );
    }

    /**
     * Get queue name by priority
     */
    protected function getQueueName(string $priority): string
    {
        return match ($priority) {
            'high' => self::QUEUE_HIGH,
            'low' => self::QUEUE_LOW,
            default => self::QUEUE_DEFAULT,
        };
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        return [
            'queues' => [
                'high' => Queue::size(self::QUEUE_HIGH),
                'default' => Queue::size(self::QUEUE_DEFAULT),
                'low' => Queue::size(self::QUEUE_LOW),
            ],
            'connection' => config('queue.default'),
        ];
    }

    /**
     * Clear old status entries
     */
    public function cleanup(int $olderThanHours = 24): int
    {
        // Note: This would require Redis SCAN or database cleanup
        // depending on cache driver
        Log::info("Queue cleanup requested", ['older_than_hours' => $olderThanHours]);
        return 0;
    }
}
