<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\MonthlyStrategyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyStrategy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 180;

    /**
     * Business ID to generate strategy for (null = all businesses)
     */
    public ?int $businessId;

    /**
     * Target year and month
     */
    public ?int $year;
    public ?int $month;

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
    public function handle(MonthlyStrategyService $strategyService): void
    {
        if ($this->businessId) {
            // Generate strategy for specific business
            $business = Business::find($this->businessId);

            if (!$business) {
                Log::warning('Business not found for monthly strategy generation', [
                    'business_id' => $this->businessId,
                ]);
                return;
            }

            $this->generateForBusiness($business, $strategyService);
        } else {
            // Generate strategies for all active businesses with subscriptions
            $businesses = Business::whereHas('subscription', function ($query) {
                $query->where('status', 'active')
                    ->where('ends_at', '>', now());
            })->get();

            Log::info('Starting monthly strategy generation', [
                'businesses_count' => $businesses->count(),
                'target_month' => now()->format('F Y'),
            ]);

            foreach ($businesses as $business) {
                try {
                    $this->generateForBusiness($business, $strategyService);
                } catch (\Exception $e) {
                    Log::error('Failed to generate monthly strategy for business', [
                        'business_id' => $business->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Completed monthly strategy generation');
        }
    }

    /**
     * Generate strategy for a specific business
     */
    private function generateForBusiness(Business $business, MonthlyStrategyService $strategyService): void
    {
        Log::info('Generating monthly strategy for business', [
            'business_id' => $business->id,
            'business_name' => $business->name,
            'target_year' => $this->year ?? now()->year,
            'target_month' => $this->month ?? now()->month,
        ]);

        try {
            $strategy = $strategyService->generateMonthlyStrategy(
                $business,
                $this->year,
                $this->month
            );

            Log::info('Monthly strategy generated successfully', [
                'business_id' => $business->id,
                'strategy_id' => $strategy->id,
                'period' => $strategy->period_label,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating monthly strategy', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Monthly strategy generation job failed', [
            'business_id' => $this->businessId,
            'year' => $this->year,
            'month' => $this->month,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
