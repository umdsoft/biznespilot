<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public ?Business $business = null
    ) {}

    public function handle(AlertService $alertService): void
    {
        if ($this->business) {
            $this->checkForBusiness($alertService, $this->business);
        } else {
            $this->checkForAllBusinesses($alertService);
        }
    }

    protected function checkForBusiness(AlertService $service, Business $business): void
    {
        try {
            $triggeredAlerts = $service->checkAlerts($business);

            if ($triggeredAlerts->isNotEmpty()) {
                Log::info('Alerts triggered', [
                    'business_id' => $business->id,
                    'count' => $triggeredAlerts->count(),
                    'severities' => $triggeredAlerts->pluck('severity')->countBy()->toArray(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to check alerts', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function checkForAllBusinesses(AlertService $service): void
    {
        $businesses = Business::where('is_active', true)->get();
        $totalAlerts = 0;

        foreach ($businesses as $business) {
            try {
                $alerts = $service->checkAlerts($business);
                $totalAlerts += $alerts->count();
            } catch (\Exception $e) {
                // Log but continue with other businesses
                continue;
            }
        }

        Log::info('Alert check completed for all businesses', [
            'businesses_count' => $businesses->count(),
            'total_alerts_triggered' => $totalAlerts,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAlertsJob failed', [
            'business_id' => $this->business?->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
