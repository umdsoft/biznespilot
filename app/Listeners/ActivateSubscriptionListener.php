<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ActivateSubscriptionListener - To'lov muvaffaqiyatli bo'lganda obunani aktivlashtiradi
 *
 * Bu listener PaymentSuccessEvent ni eshitadi va:
 * 1. SubscriptionService orqali obunani aktivlashtiradi
 * 2. Foydalanuvchiga email xabar yuboradi
 * 3. Admin ga xabar yuboradi (agar sozlangan bo'lsa)
 *
 * Queue'da ishlaydi - to'lov jarayonini sekinlashtirmaydi.
 */
class ActivateSubscriptionListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        $transaction = $event->getTransaction();
        $business = $event->getBusiness();
        $plan = $event->getPlan();

        Log::channel('billing')->info('[Listener] ActivateSubscriptionListener started', [
            'transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'business_id' => $business->id,
            'plan_id' => $plan->id,
        ]);

        try {
            // 1. Obunani aktivlashtirish
            $subscription = $this->activateSubscription($transaction, $business, $plan);

            // 2. Tranzaksiyaga subscription_id ni bog'lash
            $transaction->update([
                'subscription_id' => $subscription->id,
            ]);

            // 3. Foydalanuvchiga xabar yuborish
            $this->notifyUser($business, $plan, $transaction);

            // 4. Admin ga xabar yuborish (agar sozlangan bo'lsa)
            $this->notifyAdmin($business, $plan, $transaction);

            Log::channel('billing')->info('[Listener] Subscription activated successfully', [
                'subscription_id' => $subscription->id,
                'business_id' => $business->id,
            ]);

        } catch (\Exception $e) {
            Log::channel('billing')->error('[Listener] Failed to activate subscription', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Obunani aktivlashtirish
     */
    protected function activateSubscription($transaction, $business, $plan): Subscription
    {
        // Mavjud aktiv obunani tekshirish
        $existingSubscription = $business->subscription;

        if ($existingSubscription && $existingSubscription->isActive()) {
            // Mavjud obunani yangilash (upgrade/renew)
            return $this->subscriptionService->renewFromPayment(
                subscription: $existingSubscription,
                plan: $plan,
                paymentProvider: $transaction->provider,
                transactionId: $transaction->id
            );
        }

        // Yangi obuna yaratish
        return $this->subscriptionService->activate(
            business: $business,
            plan: $plan,
            paymentProvider: $transaction->provider,
            transactionId: $transaction->id
        );
    }

    /**
     * Foydalanuvchiga xabar yuborish
     */
    protected function notifyUser($business, $plan, $transaction): void
    {
        $owner = $business->owner;

        if (!$owner || !$owner->email) {
            Log::channel('billing')->warning('[Listener] No owner email for notification', [
                'business_id' => $business->id,
            ]);
            return;
        }

        try {
            // Email yuborish
            Mail::send('emails.billing.payment-success', [
                'business' => $business,
                'plan' => $plan,
                'transaction' => $transaction,
                'user' => $owner,
            ], function ($message) use ($owner, $plan) {
                $message->to($owner->email, $owner->name)
                    ->subject("To'lov qabul qilindi - {$plan->name} tarifi aktivlashtirildi");
            });

            Log::channel('billing')->info('[Listener] User notification sent', [
                'email' => $owner->email,
            ]);

        } catch (\Exception $e) {
            // Email xatosi asosiy jarayonni to'xtatmasin
            Log::channel('billing')->error('[Listener] Failed to send user notification', [
                'email' => $owner->email ?? 'N/A',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Admin ga xabar yuborish
     */
    protected function notifyAdmin($business, $plan, $transaction): void
    {
        $adminEmail = config('billing.notifications.email.admin_email');

        if (!$adminEmail) {
            return;
        }

        try {
            Mail::send('emails.billing.admin-payment-notification', [
                'business' => $business,
                'plan' => $plan,
                'transaction' => $transaction,
            ], function ($message) use ($adminEmail, $business) {
                $message->to($adminEmail)
                    ->subject("Yangi to'lov - {$business->name}");
            });

            Log::channel('billing')->info('[Listener] Admin notification sent', [
                'email' => $adminEmail,
            ]);

        } catch (\Exception $e) {
            Log::channel('billing')->error('[Listener] Failed to send admin notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentSuccessEvent $event, \Throwable $exception): void
    {
        $transaction = $event->getTransaction();

        Log::channel('billing')->critical('[Listener] ActivateSubscriptionListener FAILED permanently', [
            'transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'error' => $exception->getMessage(),
        ]);

        // Admin ga kritik xabar yuborish
        $adminEmail = config('billing.notifications.email.admin_email');
        if ($adminEmail) {
            Mail::raw(
                "KRITIK: Obuna aktivlashtirilmadi!\n\n" .
                "Transaction ID: {$transaction->id}\n" .
                "Order ID: {$transaction->order_id}\n" .
                "Xato: {$exception->getMessage()}",
                function ($message) use ($adminEmail) {
                    $message->to($adminEmail)
                        ->subject('KRITIK: Obuna aktivlashtirilmadi!');
                }
            );
        }
    }
}
