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

class AggregateMonthlyKpisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $businessId;
    public $year;
    public $month;
    public $tries = 3;
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(?int $businessId = null, ?int $year = null, ?int $month = null)
    {
        $this->businessId = $businessId;
        $this->year = $year;
        $this->month = $month;
    }

    /**
     * Execute the job.
     */
    public function handle(KpiAggregationService $aggregationService): void
    {
        Log::info('AggregateMonthlyKpisJob started', [
            'business_id' => $this->businessId,
            'year' => $this->year,
            'month' => $this->month,
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
                Log::error("Failed to aggregate monthly KPIs for business {$business->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }
        }

        Log::info('AggregateMonthlyKpisJob completed', [
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
        // Determine year and month
        $date = Carbon::now()->subMonth();
        $year = $this->year ?? $date->year;
        $month = $this->month ?? $date->month;

        Log::info("Aggregating monthly KPIs for business $businessId", [
            'year' => $year,
            'month' => $month,
        ]);

        $result = $aggregationService->aggregateAllKpisMonthly(
            $businessId,
            $year,
            $month
        );

        if ($result['success']) {
            Log::info("Monthly aggregation successful for business $businessId", [
                'total_kpis' => $result['total'],
                'success_count' => $result['success_count'],
                'error_count' => $result['error_count'],
            ]);
        } else {
            Log::error("Monthly aggregation failed for business $businessId", [
                'message' => $result['message'] ?? 'Unknown error',
            ]);
            throw new \Exception($result['message'] ?? 'Monthly aggregation failed');
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AggregateMonthlyKpisJob failed', [
            'business_id' => $this->businessId,
            'year' => $this->year,
            'month' => $this->month,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
