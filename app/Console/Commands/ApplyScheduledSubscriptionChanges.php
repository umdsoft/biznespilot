<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyScheduledSubscriptionChanges extends Command
{
    protected $signature = 'subscriptions:apply-scheduled';

    protected $description = 'Rejalashtirilgan tarif o\'zgarishlarini va bekor qilishlarni bajarish';

    public function handle(): int
    {
        $applied = 0;
        $cancelled = 0;

        // 1. Apply scheduled plan changes
        $scheduledChanges = Subscription::whereNotNull('scheduled_plan_id')
            ->whereNotNull('scheduled_change_at')
            ->where('scheduled_change_at', '<=', now())
            ->whereIn('status', ['active', 'trialing'])
            ->get();

        foreach ($scheduledChanges as $subscription) {
            $oldPlanId = $subscription->plan_id;

            $subscription->update([
                'plan_id' => $subscription->scheduled_plan_id,
                'scheduled_plan_id' => null,
                'scheduled_change_at' => null,
            ]);

            $applied++;

            Log::info("Scheduled plan change applied", [
                'subscription_id' => $subscription->id,
                'business_id' => $subscription->business_id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $subscription->plan_id,
            ]);
        }

        // 2. Apply scheduled cancellations
        $scheduledCancellations = Subscription::whereNotNull('scheduled_cancellation_at')
            ->where('scheduled_cancellation_at', '<=', now())
            ->whereIn('status', ['active', 'trialing'])
            ->get();

        foreach ($scheduledCancellations as $subscription) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'scheduled_cancellation_at' => null,
            ]);

            $cancelled++;

            Log::info("Scheduled cancellation applied", [
                'subscription_id' => $subscription->id,
                'business_id' => $subscription->business_id,
            ]);
        }

        $this->info("Applied: {$applied} plan changes, {$cancelled} cancellations.");

        return self::SUCCESS;
    }
}
