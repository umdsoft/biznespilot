<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Services\Sales\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSalesAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout
     */
    public int $timeout = 300;

    /**
     * Max attempts
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AlertService $alertService): void
    {
        $startTime = microtime(true);

        try {
            if ($this->businessId) {
                // Bitta biznes uchun
                $business = Business::find($this->businessId);
                if ($business) {
                    $this->processForBusiness($alertService, $business);
                }
            } else {
                // Barcha faol bizneslsr uchun
                Business::whereHas('subscription', function ($q) {
                    $q->where('status', 'active');
                })->chunk(50, function ($businesses) use ($alertService) {
                    foreach ($businesses as $business) {
                        $this->processForBusiness($alertService, $business);
                    }
                });
            }

            $duration = round(microtime(true) - $startTime, 2);
            Log::info('ProcessSalesAlertsJob: Completed', [
                'duration_seconds' => $duration,
                'business_id' => $this->businessId,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessSalesAlertsJob: Failed', [
                'error' => $e->getMessage(),
                'business_id' => $this->businessId,
            ]);
            throw $e;
        }
    }

    /**
     * Bitta biznes uchun alertlarni qayta ishlash
     */
    protected function processForBusiness(AlertService $alertService, Business $business): void
    {
        try {
            $alertService->processScheduledAlerts($business);
        } catch (\Exception $e) {
            Log::error('ProcessSalesAlertsJob: Business processing failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
