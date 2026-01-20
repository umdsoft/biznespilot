<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Services\MarketingAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CheckMarketingAlertsJob - Marketing alertlarini tekshirish
 * Har soatda ishga tushiriladi
 */
class CheckMarketingAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(MarketingAlertService $alertService): void
    {
        Log::info('CheckMarketingAlertsJob: Starting', [
            'business_id' => $this->businessId,
        ]);

        $businesses = $this->businessId
            ? Business::where('id', $this->businessId)->get()
            : Business::where('status', 'active')->get();

        $totalAlerts = 0;
        $criticalAlerts = 0;

        foreach ($businesses as $business) {
            try {
                $alerts = $alertService->checkAndCreateAlerts($business);
                $totalAlerts += $alerts->count();
                $criticalAlerts += $alerts->where('severity', 'critical')->count();

                if ($alerts->isNotEmpty()) {
                    Log::info('CheckMarketingAlertsJob: Alerts created', [
                        'business_id' => $business->id,
                        'alerts_count' => $alerts->count(),
                        'critical_count' => $alerts->where('severity', 'critical')->count(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('CheckMarketingAlertsJob: Failed for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('CheckMarketingAlertsJob: Completed', [
            'businesses_count' => $businesses->count(),
            'total_alerts' => $totalAlerts,
            'critical_alerts' => $criticalAlerts,
        ]);
    }

    public function tags(): array
    {
        return [
            'marketing-alerts',
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
