<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Integration;
use App\Services\InstagramSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInstagramDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 1800; // 30 minutes for full sync

    public function __construct(
        protected int $businessId,
        protected bool $fullSync = false
    ) {}

    public function handle(InstagramSyncService $syncService): void
    {
        $business = Business::find($this->businessId);
        if (! $business) {
            Log::warning('SyncInstagramDataJob: Business not found', ['business_id' => $this->businessId]);

            return;
        }

        $integration = Integration::where('business_id', $this->businessId)
            ->where('type', 'meta_ads') // Instagram uses same Meta integration
            ->where('status', 'connected')
            ->first();

        if (! $integration) {
            Log::warning('SyncInstagramDataJob: No connected Meta integration', ['business_id' => $this->businessId]);

            return;
        }

        try {
            Log::info('SyncInstagramDataJob: Starting sync', [
                'business_id' => $this->businessId,
                'full_sync' => $this->fullSync,
            ]);

            $syncService->initialize($integration);

            if ($this->fullSync) {
                $results = $syncService->fullSync();
            } else {
                // Incremental sync for 2-hour updates
                $results = $syncService->incrementalSync();
            }

            Log::info('SyncInstagramDataJob: Completed successfully', [
                'business_id' => $this->businessId,
                'results' => $results,
            ]);

            if (! empty($results['errors'])) {
                Log::warning('SyncInstagramDataJob: Completed with errors', [
                    'business_id' => $this->businessId,
                    'errors' => $results['errors'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SyncInstagramDataJob: Failed', [
                'business_id' => $this->businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $integration->update([
                'last_error_at' => now(),
                'last_error_message' => 'Instagram sync: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }
}
