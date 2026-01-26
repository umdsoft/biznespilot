<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\Telegram\SystemBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendPaymentNotification Listener
 *
 * Sends real-time Telegram notifications when payments are received.
 * This is the "Dopamine" trigger - instant gratification for business owners.
 */
class SendPaymentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public function __construct(
        protected SystemBotService $systemBot
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        Log::info('SendPaymentNotification: Processing payment notification', [
            'business_id' => $event->business->id,
            'amount' => $event->amount,
            'provider' => $event->provider,
        ]);

        // Get business owners/managers who have Telegram linked
        $usersToNotify = $event->business->users()
            ->whereNotNull('telegram_chat_id')
            ->where('receive_daily_reports', true)
            ->get();

        if ($usersToNotify->isEmpty()) {
            Log::debug('SendPaymentNotification: No users with Telegram for business', [
                'business_id' => $event->business->id,
            ]);
            return;
        }

        $sentCount = 0;

        foreach ($usersToNotify as $user) {
            $sent = $this->systemBot->sendPaymentAlert(
                $user,
                $event->amount,
                $event->provider,
                $event->clientName
            );

            if ($sent) {
                $sentCount++;
            }
        }

        Log::info('SendPaymentNotification: Notifications sent', [
            'business_id' => $event->business->id,
            'sent_count' => $sentCount,
            'total_users' => $usersToNotify->count(),
        ]);

        // Check for daily record and send viral alert if broken
        $this->checkAndSendRecordAlert($event, $usersToNotify);
    }

    /**
     * Check if daily sales record was broken and send viral alert.
     */
    protected function checkAndSendRecordAlert(PaymentReceived $event, $users): void
    {
        $today = now()->startOfDay();

        // Calculate today's total sales
        $todaySales = $event->business->orders()
            ->whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total');

        // Get previous daily record (excluding today)
        $previousRecord = $event->business->orders()
            ->whereDate('created_at', '<', $today)
            ->where('payment_status', 'paid')
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('total', 'desc')
            ->first();

        $previousRecordAmount = $previousRecord?->total ?? 0;

        // If today's sales exceed the previous record, send viral alert
        if ($todaySales > $previousRecordAmount && $previousRecordAmount > 0) {
            Log::info('SendPaymentNotification: Daily record broken!', [
                'business_id' => $event->business->id,
                'today_sales' => $todaySales,
                'previous_record' => $previousRecordAmount,
            ]);

            foreach ($users as $user) {
                $this->systemBot->sendRecordBrokenAlert(
                    $user,
                    'Kunlik savdo rekordi',
                    $todaySales,
                    $previousRecordAmount
                );
            }
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(PaymentReceived $event): bool
    {
        // Always queue to avoid slowing down payment processing
        return true;
    }
}
