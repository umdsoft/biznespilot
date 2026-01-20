<?php

namespace App\Jobs\Sales;

use App\Services\Sales\KpiCalculationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Real-time foydalanuvchi KPI snapshotini yangilash
 * Observer lardan chaqiriladi
 */
class UpdateUserKpiSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 2;

    /**
     * Job timeout (1 daqiqa)
     */
    public int $timeout = 60;

    /**
     * Unique job - bir vaqtda bir foydalanuvchi uchun bitta job
     */
    public int $uniqueFor = 30; // 30 soniya

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $businessId,
        public string $userId,
        public Carbon $date
    ) {}

    /**
     * Get unique ID for preventing duplicates
     */
    public function uniqueId(): string
    {
        return "kpi_snapshot_{$this->businessId}_{$this->userId}_{$this->date->format('Y-m-d')}";
    }

    /**
     * Execute the job.
     */
    public function handle(KpiCalculationService $calculator): void
    {
        try {
            $calculator->createDailySnapshot(
                $this->businessId,
                $this->userId,
                $this->date
            );

            Log::debug('UpdateUserKpiSnapshotJob: Snapshot updated', [
                'business_id' => $this->businessId,
                'user_id' => $this->userId,
                'date' => $this->date->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            Log::error('UpdateUserKpiSnapshotJob: Failed to update snapshot', [
                'business_id' => $this->businessId,
                'user_id' => $this->userId,
                'date' => $this->date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Job muvaffaqiyatsiz tugadi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateUserKpiSnapshotJob failed', [
            'business_id' => $this->businessId,
            'user_id' => $this->userId,
            'date' => $this->date->format('Y-m-d'),
            'error' => $exception->getMessage(),
        ]);
    }
}
