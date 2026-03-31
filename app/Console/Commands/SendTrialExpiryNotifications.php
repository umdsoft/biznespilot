<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTrialExpiryNotifications extends Command
{
    protected $signature = 'subscriptions:trial-expiry-notify';

    protected $description = 'Trial muddati tugashiga 3 va 1 kun qolganida ogohlantirish yuborish';

    public function handle(NotificationService $notificationService): int
    {
        $notified = 0;

        // Trial subscriptions expiring in exactly 3 days or 1 day
        foreach ([3, 1] as $daysRemaining) {
            $targetDate = now()->addDays($daysRemaining)->startOfDay();

            $subscriptions = Subscription::where('status', 'trialing')
                ->whereNotNull('trial_ends_at')
                ->whereDate('trial_ends_at', $targetDate)
                ->with(['business.owner'])
                ->get();

            foreach ($subscriptions as $subscription) {
                $business = $subscription->business;
                $owner = $business?->owner;

                if (! $owner) {
                    continue;
                }

                $title = $daysRemaining === 1
                    ? 'Trial ertaga tugaydi!'
                    : "Trial {$daysRemaining} kunda tugaydi";

                $message = $daysRemaining === 1
                    ? "Sizning bepul sinov muddatingiz ertaga tugaydi. Tarifni tanlang va xizmatlardan foydalanishni davom ettiring."
                    : "Sizning bepul sinov muddatingiz {$daysRemaining} kunda tugaydi. Hoziroq tarifni tanlang — barcha ma'lumotlaringiz saqlanib qoladi.";

                try {
                    $notificationService->sendToUser(
                        $owner,
                        'subscription',
                        $title,
                        $message,
                        [
                            'business_id' => $business->id,
                            'action_url' => '/business/subscription',
                            'action_label' => 'Tarifni tanlash',
                            'priority' => $daysRemaining === 1 ? 'high' : 'normal',
                        ]
                    );

                    $notified++;

                    Log::info("Trial expiry notification sent", [
                        'business_id' => $business->id,
                        'user_id' => $owner->id,
                        'days_remaining' => $daysRemaining,
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Failed to send trial expiry notification", [
                        'business_id' => $business->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Sent {$notified} trial expiry notifications.");

        return self::SUCCESS;
    }
}
