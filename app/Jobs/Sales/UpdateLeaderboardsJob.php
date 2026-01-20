<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Services\Sales\LeaderboardService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Leaderboardni yangilash
 * Har 15 daqiqada ishga tushadi
 */
class UpdateLeaderboardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 2;

    /**
     * Job timeout (5 daqiqa)
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null,
        public string $periodType = 'daily'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LeaderboardService $leaderboardService): void
    {
        Log::info('UpdateLeaderboardsJob started', [
            'business_id' => $this->businessId,
            'period_type' => $this->periodType,
        ]);

        if ($this->businessId) {
            $this->updateBusinessLeaderboard($leaderboardService, $this->businessId);
        } else {
            $this->updateAllLeaderboards($leaderboardService);
        }

        Log::info('UpdateLeaderboardsJob completed');
    }

    /**
     * Bitta biznes uchun leaderboardni yangilash
     */
    protected function updateBusinessLeaderboard(LeaderboardService $service, string $businessId): void
    {
        try {
            $service->recalculateLeaderboard($businessId, $this->periodType);

            Log::debug('Business leaderboard updated', [
                'business_id' => $businessId,
                'period_type' => $this->periodType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update business leaderboard', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Barcha bizneslar uchun leaderboardni yangilash
     */
    protected function updateAllLeaderboards(LeaderboardService $service): void
    {
        $businesses = Business::where('status', 'active')
            ->whereHas('kpiSettings', fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        $successCount = 0;
        $errorCount = 0;

        foreach ($businesses as $businessId) {
            try {
                $this->updateBusinessLeaderboard($service, $businessId);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to update leaderboard', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('All leaderboards update completed', [
            'period_type' => $this->periodType,
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ]);
    }

    /**
     * Job muvaffaqiyatsiz tugadi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateLeaderboardsJob failed', [
            'business_id' => $this->businessId,
            'period_type' => $this->periodType,
            'error' => $exception->getMessage(),
        ]);
    }
}
