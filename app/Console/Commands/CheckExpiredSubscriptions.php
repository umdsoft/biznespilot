<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';

    protected $description = 'Muddati o\'tgan subscriptionlarni expired statusiga o\'tkazish';

    public function handle(): int
    {
        // 1. Active subscriptions where ends_at is past — mass update
        $countActive = Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->update(['status' => 'expired']);

        if ($countActive > 0) {
            Log::info("Expired {$countActive} active subscriptions");
        }

        // 2. Trial subscriptions where trial_ends_at is past — mass update
        $countTrial = Subscription::where('status', 'trialing')
            ->where(function ($q) {
                $q->where('trial_ends_at', '<', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('trial_ends_at')
                            ->where('ends_at', '<', now());
                    });
            })
            ->update(['status' => 'expired']);

        if ($countTrial > 0) {
            Log::info("Expired {$countTrial} trial subscriptions");
        }

        $this->info("Expired: {$countActive} active, {$countTrial} trial subscriptions.");

        return self::SUCCESS;
    }
}
