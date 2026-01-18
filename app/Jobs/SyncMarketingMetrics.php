<?php

namespace App\Jobs;

use App\Models\MarketingChannel;
use App\Services\FacebookService;
use App\Services\GoogleAdsService;
use App\Services\InstagramService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMarketingMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The marketing channel to sync
     */
    public MarketingChannel $channel;

    /**
     * The date to sync metrics for
     */
    public Carbon $date;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(MarketingChannel $channel, ?Carbon $date = null)
    {
        $this->channel = $channel;
        $this->date = $date ?? Carbon::today();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if channel is inactive
        if (! $this->channel->is_active) {
            Log::info('Skipping inactive channel', [
                'channel_id' => $this->channel->id,
                'channel_name' => $this->channel->name,
            ]);

            return;
        }

        // Skip if channel doesn't have access token
        if (! $this->channel->access_token) {
            Log::warning('Channel missing access token', [
                'channel_id' => $this->channel->id,
                'channel_name' => $this->channel->name,
            ]);

            return;
        }

        try {
            Log::info('Starting metrics sync', [
                'channel_id' => $this->channel->id,
                'channel_name' => $this->channel->name,
                'channel_type' => $this->channel->type,
                'date' => $this->date->toDateString(),
            ]);

            $result = match ($this->channel->type) {
                'instagram' => $this->syncInstagram(),
                'telegram' => $this->syncTelegram(),
                'facebook' => $this->syncFacebook(),
                'google_ads' => $this->syncGoogleAds(),
                default => null,
            };

            if ($result) {
                // Update last_synced_at timestamp
                $this->channel->update([
                    'last_synced_at' => now(),
                ]);

                Log::info('Metrics sync completed successfully', [
                    'channel_id' => $this->channel->id,
                    'date' => $this->date->toDateString(),
                ]);
            } else {
                Log::warning('Metrics sync returned null', [
                    'channel_id' => $this->channel->id,
                    'date' => $this->date->toDateString(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Metrics sync failed', [
                'channel_id' => $this->channel->id,
                'channel_name' => $this->channel->name,
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Sync Instagram metrics
     */
    private function syncInstagram(): mixed
    {
        $service = app(InstagramService::class);

        // Refresh access token if needed
        $refreshedToken = $service->refreshAccessToken($this->channel->access_token);
        if ($refreshedToken && $refreshedToken !== $this->channel->access_token) {
            $this->channel->update(['access_token' => $refreshedToken]);
        }

        return $service->syncMetrics($this->channel, $this->date);
    }

    /**
     * Sync Telegram metrics
     */
    private function syncTelegram(): mixed
    {
        $service = app(TelegramService::class);

        return $service->syncMetrics($this->channel, $this->date);
    }

    /**
     * Sync Facebook metrics
     */
    private function syncFacebook(): mixed
    {
        $service = app(FacebookService::class);

        // Refresh access token if needed
        $refreshedToken = $service->refreshAccessToken($this->channel->access_token);
        if ($refreshedToken && $refreshedToken !== $this->channel->access_token) {
            $this->channel->update(['access_token' => $refreshedToken]);
        }

        return $service->syncMetrics($this->channel, $this->date);
    }

    /**
     * Sync Google Ads metrics
     */
    private function syncGoogleAds(): mixed
    {
        $service = app(GoogleAdsService::class);

        return $service->syncMetrics($this->channel, $this->date);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Metrics sync job failed permanently', [
            'channel_id' => $this->channel->id,
            'channel_name' => $this->channel->name,
            'date' => $this->date->toDateString(),
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify administrators
        // Notification::route('mail', config('mail.admin_email'))
        //     ->notify(new MetricsSyncFailed($this->channel, $exception));
    }
}
