<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Integration;
use App\Services\InstagramSyncService;
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
        protected string $businessId,
        protected bool $fullSync = true
    ) {}

    public function handle(MetaSyncService $syncService, InstagramSyncService $instagramSyncService): void
    {
        $business = Business::find($this->businessId);
        if (! $business) {
            Log::warning('SyncMetaInsightsJob: Business not found', ['business_id' => $this->businessId]);

            return;
        }

        $integration = Integration::where('business_id', $this->businessId)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->first();

        if (! $integration) {
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

            Log::info('SyncMetaInsightsJob: Meta sync completed', [
                'business_id' => $this->businessId,
                'results' => $results,
            ]);

            // Sync Instagram accounts
            try {
                $instagramSyncService->initialize($integration);
                $igAccounts = $instagramSyncService->syncInstagramAccounts();

                if (! empty($igAccounts)) {
                    // Get access token from integration
                    $credentials = json_decode($integration->credentials, true);

                    // Create/update Instagram integration
                    Integration::updateOrCreate(
                        [
                            'business_id' => $this->businessId,
                            'type' => 'instagram',
                        ],
                        [
                            'name' => 'Instagram',
                            'is_active' => true,
                            'status' => 'connected',
                            'credentials' => json_encode([
                                'access_token' => $credentials['access_token'] ?? '',
                                'token_type' => $credentials['token_type'] ?? 'bearer',
                                'linked_to' => 'meta_ads',
                            ]),
                            'connected_at' => now(),
                            'expires_at' => $integration->expires_at,
                            'metadata' => json_encode([
                                'accounts_count' => count($igAccounts),
                                'meta_integration_id' => $integration->id,
                            ]),
                        ]
                    );

                    Log::info('SyncMetaInsightsJob: Instagram accounts synced', [
                        'business_id' => $this->businessId,
                        'accounts_count' => count($igAccounts),
                    ]);
                }
            } catch (\Exception $igError) {
                Log::warning('SyncMetaInsightsJob: Instagram sync failed', [
                    'business_id' => $this->businessId,
                    'error' => $igError->getMessage(),
                ]);
            }

            if (! empty($results['errors'])) {
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
