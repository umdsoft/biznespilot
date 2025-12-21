<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Integration;
use App\Services\MetaSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetaInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 1800; // 30 minutes for full sync

    public function __construct(
        protected int $businessId,
        protected bool $fullSync = true
    ) {}

    public function handle(MetaSyncService $syncService): void
    {
        $business = Business::find($this->businessId);
        if (!$business) {
            Log::warning('SyncMetaInsightsJob: Business not found', ['business_id' => $this->businessId]);
            return;
        }

        $integration = Integration::where('business_id', $this->businessId)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            Log::warning('SyncMetaInsightsJob: No connected Meta integration', ['business_id' => $this->businessId]);
            return;
        }

        try {
            Log::info('SyncMetaInsightsJob: Starting sync', [
                'business_id' => $this->businessId,
                'full_sync' => $this->fullSync,
            ]);

            $syncService->initialize($integration);

            if ($this->fullSync) {
                // Full sync - last 12 months of data
                $results = $syncService->fullSync();
            } else {
                // Quick sync - last 30 days only
                $results = $syncService->quickSync();
            }

            Log::info('SyncMetaInsightsJob: Completed successfully', [
                'business_id' => $this->businessId,
                'results' => $results,
            ]);

            if (!empty($results['errors'])) {
                Log::warning('SyncMetaInsightsJob: Completed with errors', [
                    'business_id' => $this->businessId,
                    'errors' => $results['errors'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SyncMetaInsightsJob: Failed', [
                'business_id' => $this->businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update integration with error
            $integration->update([
                'last_error_at' => now(),
                'last_error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
