<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Services\Sales\KpiCalculationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateDailyKpiSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 3;

    /**
     * Qayta urinish orasidagi vaqt (soniyalar)
     */
    public int $backoff = 60;

    /**
     * Job timeout (10 daqiqa)
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null,
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::today();
    }

    /**
     * Execute the job.
     */
    public function handle(KpiCalculationService $calculator): void
    {
        Log::info('CalculateDailyKpiSnapshotsJob started', [
            'business_id' => $this->businessId,
            'date' => $this->date->format('Y-m-d'),
        ]);

        if ($this->businessId) {
            $this->processBusinessSnapshots($calculator, $this->businessId);
        } else {
            $this->processAllBusinesses($calculator);
        }

        Log::info('CalculateDailyKpiSnapshotsJob completed', [
            'date' => $this->date->format('Y-m-d'),
        ]);
    }

    /**
     * Bitta biznes uchun snapshotlar yaratish
     */
    protected function processBusinessSnapshots(KpiCalculationService $calculator, string $businessId): void
    {
        try {
            // Sotuv operatorlarini olish
            $operators = BusinessUser::where('business_id', $businessId)
                ->whereIn('department', ['sales_operator', 'sales_head'])
                ->whereNotNull('accepted_at')
                ->pluck('user_id');

            $successCount = 0;
            $errorCount = 0;

            foreach ($operators as $userId) {
                try {
                    $calculator->createDailySnapshot($businessId, $userId, $this->date);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::warning('Failed to create snapshot for user', [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'date' => $this->date->format('Y-m-d'),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Business snapshots completed', [
                'business_id' => $businessId,
                'operators_count' => $operators->count(),
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process business snapshots', [
                'business_id' => $businessId,
                'date' => $this->date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Barcha bizneslar uchun snapshotlar yaratish
     */
    protected function processAllBusinesses(KpiCalculationService $calculator): void
    {
        $businesses = Business::where('status', 'active')
            ->whereHas('kpiSettings', fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        $processedCount = 0;
        $errorCount = 0;

        foreach ($businesses as $businessId) {
            try {
                $this->processBusinessSnapshots($calculator, $businessId);
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to process business', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
                // Davom etish, boshqa bizneslarni ishlab chiqish
            }
        }

        Log::info('All businesses snapshots completed', [
            'date' => $this->date->format('Y-m-d'),
            'businesses_processed' => $processedCount,
            'businesses_failed' => $errorCount,
        ]);
    }

    /**
     * Job muvaffaqiyatsiz tugadi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateDailyKpiSnapshotsJob failed', [
            'business_id' => $this->businessId,
            'date' => $this->date?->format('Y-m-d'),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
