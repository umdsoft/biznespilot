<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\AIInsightsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDailyInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Business ID to generate insights for (null = all businesses)
     */
    public ?int $businessId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $businessId = null)
    {
        $this->businessId = $businessId;
    }

    /**
     * Execute the job.
     */
    public function handle(AIInsightsService $insightsService): void
    {
        if ($this->businessId) {
            // Generate insights for specific business
            $business = Business::find($this->businessId);

            if (!$business) {
                Log::warning('Business not found for insight generation', [
                    'business_id' => $this->businessId,
                ]);
                return;
            }

            $this->generateForBusiness($business, $insightsService);
        } else {
            // Generate insights for all active businesses
            $businesses = Business::whereHas('subscription', function ($query) {
                $query->where('status', 'active')
                    ->where('ends_at', '>', now());
            })->get();

            Log::info('Starting daily insights generation', [
                'businesses_count' => $businesses->count(),
            ]);

            foreach ($businesses as $business) {
                try {
                    $this->generateForBusiness($business, $insightsService);
                } catch (\Exception $e) {
                    Log::error('Failed to generate insights for business', [
                        'business_id' => $business->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Completed daily insights generation');
        }
    }

    /**
     * Generate insights for a specific business
     */
    private function generateForBusiness(Business $business, AIInsightsService $insightsService): void
    {
        Log::info('Generating insights for business', [
            'business_id' => $business->id,
            'business_name' => $business->name,
        ]);

        // Generate different types of insights
        $types = ['marketing', 'sales', 'customer'];

        $insights = $insightsService->generateInsightsForBusiness($business, $types);

        Log::info('Insights generated successfully', [
            'business_id' => $business->id,
            'insights_count' => count($insights),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Daily insights generation job failed', [
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
