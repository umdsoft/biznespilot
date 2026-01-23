<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\WeeklyAnalytics;
use App\Notifications\WeeklyAnalyticsReportNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWeeklyAnalyticsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WeeklyAnalytics $analytics,
        public array $channels = ['mail', 'telegram']
    ) {}

    public function handle(): void
    {
        $business = $this->analytics->business;

        if (! $business) {
            Log::warning('Weekly analytics notification: Business not found', [
                'analytics_id' => $this->analytics->id,
            ]);
            return;
        }

        // Get business owner
        $owner = $business->owner;

        if (! $owner) {
            Log::warning('Weekly analytics notification: Owner not found', [
                'business_id' => $business->id,
            ]);
            return;
        }

        try {
            // Send notification
            $owner->notify(new WeeklyAnalyticsReportNotification(
                $this->analytics,
                $this->channels
            ));

            Log::info('Weekly analytics notification sent', [
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'channels' => $this->channels,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send weekly analytics notification', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
