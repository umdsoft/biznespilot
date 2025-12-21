<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\InsightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        public ?Business $business = null
    ) {}

    public function handle(InsightService $insightService): void
    {
        if ($this->business) {
            $this->generateForBusiness($insightService, $this->business);
        } else {
            $this->generateForAllBusinesses($insightService);
        }
    }

    protected function generateForBusiness(InsightService $service, Business $business): void
    {
        try {
            $insights = $service->generateInsights($business);

            Log::info('Insights generated', [
                'business_id' => $business->id,
                'count' => $insights->count(),
                'types' => $insights->pluck('type')->countBy()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate insights', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function generateForAllBusinesses(InsightService $service): void
    {
        $businesses = Business::where('is_active', true)->get();
        $totalInsights = 0;

        foreach ($businesses as $business) {
            try {
                $insights = $service->generateInsights($business);
                $totalInsights += $insights->count();
            } catch (\Exception $e) {
                // Log but continue with other businesses
                continue;
            }
        }

        Log::info('Insights generated for all businesses', [
            'businesses_count' => $businesses->count(),
            'total_insights' => $totalInsights,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateInsightsJob failed', [
            'business_id' => $this->business?->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
