<?php

namespace App\Services\Integration\Contracts;

use Illuminate\Support\Collection;

interface KpiSyncServiceInterface
{
    /**
     * Sync all available KPIs for a business on a specific date
     *
     * @param int $businessId
     * @param string $date Format: Y-m-d
     * @return array ['success' => bool, 'synced_count' => int, 'failed_count' => int, 'errors' => array]
     */
    public function syncDailyKpis(int $businessId, string $date): array;

    /**
     * Sync a specific KPI for a business on a specific date
     *
     * @param int $businessId
     * @param string $kpiCode
     * @param string $date Format: Y-m-d
     * @return array ['success' => bool, 'kpi_code' => string, 'value' => float|null, 'message' => string]
     */
    public function syncKpi(int $businessId, string $kpiCode, string $date): array;

    /**
     * Get list of KPI codes that this service can sync
     *
     * @return array Array of KPI codes
     */
    public function getSupportedKpis(): array;

    /**
     * Check if the integration is configured and available for a business
     *
     * @param int $businessId
     * @return bool
     */
    public function isAvailable(int $businessId): bool;

    /**
     * Get integration name
     *
     * @return string
     */
    public function getIntegrationName(): string;

    /**
     * Get sync status for a business
     *
     * @param int $businessId
     * @return array ['available' => bool, 'last_sync' => string|null, 'supported_kpis' => array]
     */
    public function getSyncStatus(int $businessId): array;
}
