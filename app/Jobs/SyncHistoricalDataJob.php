<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncHistoricalDataJob - 6 oylik tarixiy ma'lumotlarni orqa fonda sinxronlash
 *
 * Bu Job foydalanuvchi akkaunтni ulangandan so'ng avtomatik ishga tushadi.
 * Pagination yordamida barcha ma'lumotlarni to'liq yuklaydi.
 *
 * Sync qilinadigan ma'lumotlar:
 * - Ad Account Insights (impressions, spend, clicks, conversions)
 * - Instagram Media (posts, reels, stories with engagement)
 * - Instagram Daily Insights (followers growth)
 */
class SyncHistoricalDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param string $integrationId Integration UUID
     * @param int $months Number of months to sync (default 6)
     */
    public function __construct(
        protected string $integrationId,
        protected int $months = 6
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FacebookService $facebookService): void
    {
        Log::info('SyncHistoricalDataJob: Starting', [
            'integration_id' => $this->integrationId,
            'months' => $this->months,
        ]);

        $integration = Integration::find($this->integrationId);

        if (!$integration) {
            Log::error('SyncHistoricalDataJob: Integration not found', [
                'integration_id' => $this->integrationId,
            ]);
            return;
        }

        // Mark as syncing
        $integration->update([
            'status' => 'syncing',
            'last_error_message' => null,
        ]);

        try {
            // Run the sync
            $result = $facebookService->syncHistoricalData($this->integrationId, $this->months);

            if ($result['success']) {
                Log::info('SyncHistoricalDataJob: Completed successfully', [
                    'integration_id' => $this->integrationId,
                    'synced' => $result['synced'],
                ]);

                $integration->update([
                    'status' => 'connected',
                    'last_sync_at' => now(),
                ]);

                // Notify user via Telegram if they have it connected
                $this->notifyUserViaTelegram($integration, $result);

            } else {
                Log::warning('SyncHistoricalDataJob: Completed with errors', [
                    'integration_id' => $this->integrationId,
                    'errors' => $result['errors'],
                ]);

                $integration->update([
                    'status' => 'connected', // Still connected, just had sync errors
                    'last_error_at' => now(),
                    'last_error_message' => implode('; ', $result['errors']),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SyncHistoricalDataJob: Failed', [
                'integration_id' => $this->integrationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $integration->update([
                'status' => 'error',
                'last_error_at' => now(),
                'last_error_message' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncHistoricalDataJob: Job failed permanently', [
            'integration_id' => $this->integrationId,
            'error' => $exception->getMessage(),
        ]);

        $integration = Integration::find($this->integrationId);

        if ($integration) {
            $integration->update([
                'status' => 'error',
                'last_error_at' => now(),
                'last_error_message' => 'Sync failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
            ]);
        }
    }

    /**
     * Notify user via Telegram about sync completion
     */
    protected function notifyUserViaTelegram(Integration $integration, array $result): void
    {
        try {
            $business = $integration->business;

            if (!$business) {
                return;
            }

            // Get business owner
            $owner = $business->users()
                ->wherePivot('role', 'owner')
                ->first();

            if (!$owner || !$owner->telegram_chat_id) {
                return;
            }

            // Build notification message
            $syncedInfo = [];
            if (!empty($result['synced']['ad_insights'])) {
                $syncedInfo[] = "📊 Reklama: {$result['synced']['ad_insights']} kun";
            }
            if (!empty($result['synced']['instagram_media'])) {
                $syncedInfo[] = "📸 Instagram: {$result['synced']['instagram_media']} post";
            }

            $message = "✅ <b>Tarixiy ma'lumotlar yuklandi!</b>\n\n"
                . "🏢 <b>{$business->name}</b>\n\n"
                . implode("\n", $syncedInfo) . "\n\n"
                . "📅 Oxirgi {$this->months} oylik ma'lumotlar sinxronlandi.";

            // Send via SystemBotService
            $botService = app(\App\Services\Telegram\SystemBotService::class);
            $botService->sendMessage($owner->telegram_chat_id, $message);

        } catch (\Exception $e) {
            // Don't fail the job just because notification failed
            Log::warning('SyncHistoricalDataJob: Failed to send Telegram notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
