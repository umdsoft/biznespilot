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

/**
 * Haftalik va Oylik KPI yig'indilarini hisoblash
 * Yakshanba va har oyning 1-kuni ishga tushadi
 */
class AggregatePeriodKpisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 3;

    /**
     * Job timeout (15 daqiqa)
     */
    public int $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $periodType = 'weekly',
        public ?string $businessId = null,
        public ?Carbon $periodStart = null
    ) {
        $this->periodStart = $periodStart ?? $this->getDefaultPeriodStart();
    }

    /**
     * Execute the job.
     */
    public function handle(KpiCalculationService $calculator): void
    {
        Log::info('AggregatePeriodKpisJob started', [
            'period_type' => $this->periodType,
            'business_id' => $this->businessId,
            'period_start' => $this->periodStart->format('Y-m-d'),
        ]);

        if ($this->businessId) {
            $this->processBusinessAggregation($calculator, $this->businessId);
        } else {
            $this->processAllBusinesses($calculator);
        }

        Log::info('AggregatePeriodKpisJob completed', [
            'period_type' => $this->periodType,
        ]);
    }

    /**
     * Bitta biznes uchun yig'indini hisoblash
     */
    protected function processBusinessAggregation(KpiCalculationService $calculator, string $businessId): void
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
                    $calculator->calculatePeriodSummary(
                        $businessId,
                        $userId,
                        $this->periodType,
                        $this->periodStart
                    );
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::warning('Failed to aggregate KPIs for user', [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'period_type' => $this->periodType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Jamoa reytingini yangilash
            $calculator->updateTeamRankings($businessId, $this->periodType, $this->periodStart);

            Log::info('Business aggregation completed', [
                'business_id' => $businessId,
                'operators_count' => $operators->count(),
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process business aggregation', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Barcha bizneslar uchun yig'indini hisoblash
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
                $this->processBusinessAggregation($calculator, $businessId);
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to aggregate business', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('All businesses aggregation completed', [
            'period_type' => $this->periodType,
            'businesses_processed' => $processedCount,
            'businesses_failed' => $errorCount,
        ]);
    }

    /**
     * Standart davr boshlanish sanasini olish
     */
    protected function getDefaultPeriodStart(): Carbon
    {
        return match ($this->periodType) {
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfWeek(),
        };
    }

    /**
     * Job muvaffaqiyatsiz tugadi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AggregatePeriodKpisJob failed', [
            'period_type' => $this->periodType,
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
