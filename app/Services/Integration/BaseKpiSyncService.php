<?php

namespace App\Services\Integration;

use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Models\KpiTemplate;
use App\Services\Integration\Contracts\KpiSyncServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseKpiSyncService implements KpiSyncServiceInterface
{
    protected CircuitBreaker $circuitBreaker;

    protected RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->circuitBreaker = app(CircuitBreaker::class);
        $this->rateLimiter = app(RateLimiter::class);
    }

    /**
     * Sync all available KPIs for a business with circuit breaker protection
     */
    public function syncDailyKpis(int $businessId, string $date): array
    {
        $business = Business::find($businessId);
        if (! $business) {
            return [
                'success' => false,
                'synced_count' => 0,
                'failed_count' => 0,
                'errors' => ['Business not found'],
            ];
        }

        if (! $this->isAvailable($businessId)) {
            return [
                'success' => false,
                'synced_count' => 0,
                'failed_count' => 0,
                'errors' => ["Integration {$this->getIntegrationName()} not available for business {$businessId}"],
            ];
        }

        // Check circuit breaker state
        try {
            return $this->circuitBreaker->execute(
                $this->getIntegrationName(),
                fn () => $this->performSync($businessId, $date),
                $businessId
            );
        } catch (\Exception $e) {
            Log::error('Circuit breaker prevented sync', [
                'integration' => $this->getIntegrationName(),
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'synced_count' => 0,
                'failed_count' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Perform actual sync operation (called by circuit breaker)
     */
    protected function performSync(int $businessId, string $date): array
    {
        $supportedKpis = $this->getSupportedKpis();
        $syncedCount = 0;
        $failedCount = 0;
        $errors = [];

        Log::info("Starting {$this->getIntegrationName()} KPI sync", [
            'business_id' => $businessId,
            'date' => $date,
            'supported_kpis' => count($supportedKpis),
        ]);

        foreach ($supportedKpis as $kpiCode) {
            try {
                // Apply rate limiting
                $this->rateLimiter->waitIfNeeded($this->getIntegrationName(), $businessId);

                if (! $this->rateLimiter->allowRequest($this->getIntegrationName(), $businessId)) {
                    throw new \Exception("Rate limit exceeded for {$this->getIntegrationName()}");
                }

                $this->rateLimiter->recordRequest($this->getIntegrationName(), $businessId);

                $result = $this->syncKpi($businessId, $kpiCode, $date);

                if ($result['success']) {
                    $syncedCount++;
                    Log::debug("Synced KPI: {$kpiCode}", ['value' => $result['value']]);
                } else {
                    $failedCount++;
                    $errors[] = "KPI {$kpiCode}: {$result['message']}";
                    Log::warning("Failed to sync KPI: {$kpiCode}", ['message' => $result['message']]);
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "KPI {$kpiCode}: {$e->getMessage()}";
                Log::error("Error syncing KPI: {$kpiCode}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("{$this->getIntegrationName()} sync completed", [
            'business_id' => $businessId,
            'date' => $date,
            'synced' => $syncedCount,
            'failed' => $failedCount,
        ]);

        return [
            'success' => $syncedCount > 0,
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Get sync status for a business
     */
    public function getSyncStatus(int $businessId): array
    {
        $available = $this->isAvailable($businessId);
        $lastSync = null;

        if ($available) {
            $lastSyncRecord = KpiDailyActual::where('business_id', $businessId)
                ->where('data_source', $this->getIntegrationName())
                ->where('sync_status', 'synced')
                ->orderBy('last_synced_at', 'desc')
                ->first();

            $lastSync = $lastSyncRecord ? $lastSyncRecord->last_synced_at : null;
        }

        return [
            'integration' => $this->getIntegrationName(),
            'available' => $available,
            'last_sync' => $lastSync,
            'supported_kpis' => $this->getSupportedKpis(),
            'supported_kpis_count' => count($this->getSupportedKpis()),
        ];
    }

    /**
     * Save or update KPI daily actual value
     *
     * @param  array  $metadata  Additional sync metadata
     */
    protected function saveKpiValue(
        int $businessId,
        string $kpiCode,
        string $date,
        float $value,
        array $metadata = []
    ): KpiDailyActual {
        // Get KPI configuration to determine planned value
        $kpiConfig = DB::table('business_kpi_configurations')
            ->where('business_id', $businessId)
            ->first();

        $plannedValue = 0;
        if ($kpiConfig) {
            $kpiWeights = json_decode($kpiConfig->kpi_weights, true) ?? [];
            $plannedValue = $kpiWeights[$kpiCode] ?? 0;
        }

        // Find or create daily actual record
        $dailyActual = KpiDailyActual::updateOrCreate(
            [
                'business_id' => $businessId,
                'kpi_code' => $kpiCode,
                'record_date' => $date,
            ],
            [
                'actual_value' => $value,
                'planned_value' => $plannedValue,
                'data_source' => $this->getIntegrationName(),
                'integration_sync_id' => $metadata['sync_id'] ?? uniqid($this->getIntegrationName().'_'),
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'auto_calculated' => true,
                'can_override' => true,
                'sync_metadata' => array_merge($metadata, [
                    'synced_by' => $this->getIntegrationName(),
                    'synced_at' => now()->toIso8601String(),
                ]),
                'data_quality_score' => 100, // High quality for auto-synced data
                'is_verified' => true,
            ]
        );

        return $dailyActual;
    }

    /**
     * Get KPI template
     */
    protected function getKpiTemplate(string $kpiCode): ?KpiTemplate
    {
        return KpiTemplate::where('kpi_code', $kpiCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Parse date string to Carbon instance
     */
    protected function parseDate(string $date): Carbon
    {
        return Carbon::parse($date);
    }

    /**
     * Check if date is today
     */
    protected function isToday(string $date): bool
    {
        return $this->parseDate($date)->isToday();
    }

    /**
     * Check if date is in the past
     */
    protected function isPast(string $date): bool
    {
        return $this->parseDate($date)->isPast();
    }

    /**
     * Abstract methods that must be implemented by child classes
     */
    abstract public function syncKpi(int $businessId, string $kpiCode, string $date): array;

    abstract public function getSupportedKpis(): array;

    abstract public function isAvailable(int $businessId): bool;

    abstract public function getIntegrationName(): string;
}
