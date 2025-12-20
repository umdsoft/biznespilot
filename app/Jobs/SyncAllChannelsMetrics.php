<?php

namespace App\Jobs;

use App\Models\MarketingChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllChannelsMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The date to sync metrics for
     */
    public Carbon $date;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(?Carbon $date = null)
    {
        $this->date = $date ?? Carbon::yesterday(); // Default to yesterday to ensure complete data
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting sync for all marketing channels', [
            'date' => $this->date->toDateString(),
        ]);

        $channels = MarketingChannel::where('is_active', true)
            ->whereNotNull('access_token')
            ->get();

        if ($channels->isEmpty()) {
            Log::info('No active channels to sync');
            return;
        }

        $dispatched = 0;

        foreach ($channels as $channel) {
            try {
                // Dispatch individual sync job for each channel
                SyncMarketingMetrics::dispatch($channel, $this->date)
                    ->onQueue('marketing-sync');

                $dispatched++;

                Log::info('Dispatched sync job for channel', [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'channel_type' => $channel->type,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to dispatch sync job for channel', [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Finished dispatching sync jobs for all channels', [
            'total_channels' => $channels->count(),
            'dispatched' => $dispatched,
            'date' => $this->date->toDateString(),
        ]);
    }
}
