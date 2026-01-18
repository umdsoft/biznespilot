<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\KpiAggregationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AggregateWeeklyKpisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $businessId;

    public $weekStartDate;

    public $tries = 3;

    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(?int $businessId = null, ?string $weekStartDate = null)
    {
        $this->businessId = $businessId;
        $this->weekStartDate = $weekStartDate;
    }

    /**
     * Execute the job.
     */
    public function handle(KpiAggregationService $aggregationService): void
    {
        Log::info('AggregateWeeklyKpisJob started', [
            'business_id' => $this->businessId,
            'week_start_date' => $this->weekStartDate,
        ]);

        // If specific business ID provided, aggregate only that business
        if ($this->businessId) {
            $this->aggregateBusiness($this->businessId, $aggregationService);

            return;
        }

        // Otherwise, aggregate all businesses with active KPI configurations
        $businesses = Business::whereHas('kpiConfiguration', function ($query) {
            $query->where('status', 'active');
        })->get();

        $successCount = 0;
        $errorCount = 0;

        foreach ($businesses as $business) {
            try {
                $this->aggregateBusiness($business->id, $aggregationService);
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to aggregate weekly KPIs for business {$business->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }
        }

        Log::info('AggregateWeeklyKpisJob completed', [
            'total_businesses' => $businesses->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ]);
    }

    /**
     * Aggregate KPIs for a specific business
     */
    protected function aggregateBusiness(int $businessId, KpiAggregationService $aggregationService): void
    {
        // Determine week start date
        $weekStart = $this->weekStartDate
            ? Carbon::parse($this->weekStartDate)
            : Carbon::now()->subWeek()->startOfWeek();

        Log::info("Aggregating weekly KPIs for business $businessId", [
            'week_start' => $weekStart->format('Y-m-d'),
        ]);

        $result = $aggregationService->aggregateAllKpisWeekly(
            $businessId,
            $weekStart->format('Y-m-d')
        );

        if ($result['success']) {
            Log::info("Weekly aggregation successful for business $businessId", [
                'total_kpis' => $result['total'],
                'success_count' => $result['success_count'],
                'error_count' => $result['error_count'],
            ]);
        } else {
            Log::error("Weekly aggregation failed for business $businessId", [
                'message' => $result['message'] ?? 'Unknown error',
            ]);
            throw new \Exception($result['message'] ?? 'Weekly aggregation failed');
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AggregateWeeklyKpisJob failed', [
            'business_id' => $this->businessId,
            'week_start_date' => $this->weekStartDate,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
