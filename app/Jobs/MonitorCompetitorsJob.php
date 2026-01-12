<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\CompetitorMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorCompetitorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 180;
    public int $timeout = 900;

    public function __construct(
        public ?Business $business = null
    ) {}

    public function handle(CompetitorMonitoringService $monitorService): void
    {
        if ($this->business) {
            $this->monitorForBusiness($monitorService, $this->business);
        } else {
            $this->monitorForAllBusinesses($monitorService);
        }
    }

    protected function monitorForBusiness(CompetitorMonitoringService $service, Business $business): void
    {
        try {
            $alerts = $service->checkCompetitors($business);

            Log::info('Competitor monitoring completed', [
                'business_id' => $business->id,
                'alerts_count' => $alerts->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to monitor competitors', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function monitorForAllBusinesses(CompetitorMonitoringService $service): void
    {
        $businesses = Business::where('is_active', true)
            ->whereHas('competitors', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        $totalAlerts = 0;

        foreach ($businesses as $business) {
            try {
                $alerts = $service->checkCompetitors($business);
                $totalAlerts += $alerts->count();
            } catch (\Exception $e) {
                // Log but continue with other businesses
                continue;
            }
        }

        Log::info('Competitor monitoring completed for all businesses', [
            'businesses_count' => $businesses->count(),
            'total_alerts' => $totalAlerts,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('MonitorCompetitorsJob failed', [
            'business_id' => $this->business?->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
