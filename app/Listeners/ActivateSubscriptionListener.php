<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
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

        if (! $business || ! $plan) {
            Log::channel('billing')->error('[Listener] Missing business or plan from transaction', [
                'transaction_id' => $transaction->id,
                'business_exists' => (bool) $business,
                'plan_exists' => (bool) $plan,
            ]);
            return;
        }

        Log::channel('billing')->info('[Listener] ActivateSubscriptionListener started', [
            'transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'business_id' => $business->id,
            'plan_id' => $plan->id,
        ]);

        try {
            // Critical DB operations inside transaction
            // If any of these fail, all changes are rolled back
            $subscription = DB::transaction(function () use ($transaction, $business, $plan) {
                // 1. Obunani aktivlashtirish
                $subscription = $this->activateSubscription($transaction, $business, $plan);

                // 2. Tranzaksiyaga subscription_id ni bog'lash
                $transaction->update([
                    'subscription_id' => $subscription->id,
                ]);

                return $subscription;
            });

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

        // Non-critical operations outside transaction (can fail safely)
        // These run after the subscription is committed to the database

        // 3. Redis subscription cache ni tozalash
        try {
            HandleInertiaRequests::clearSubscriptionCache($business->id);
        } catch (\Exception $e) {
            Log::channel('billing')->warning('[Listener] Failed to clear subscription cache', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 4. Foydalanuvchiga xabar yuborish
        $this->notifyUser($business, $plan, $transaction);

        // 5. Admin ga xabar yuborish (agar sozlangan bo'lsa)
        $this->notifyAdmin($business, $plan, $transaction);
    }

    /**
     * Obunani aktivlashtirish
     *
     * Routing logic:
     * - status=trialing → activate() (trial bekor qilinadi, yangi paid subscription yaratiladi
     *   starts_at=now, ends_at=now+period, trial_ends_at=NULL bilan)
     * - status=active   → renewFromPayment() (mavjud paid muddatga davr qo'shiladi)
     * - obuna yo'q      → activate() (birinchi paid subscription)
     *
     * MUHIM: Trial → Paid o'tishida renewFromPayment ISHLATILMASIN, chunki u
     * trial_ends_at ni saqlab qoladi (banner yo'qolmaydi) va ends_at ni mavjud
     * trial_ends_at dan boshlab uzaytiradi (30 kun emas, 14+30=44 kun chiqadi).
     */
    protected function activateSubscription($transaction, $business, $plan): Subscription
    {
        // Mavjud obuna (active YOKI trialing — Subscription::scopeActive ikkalasini ham qaytaradi)
        $existingSubscription = $business->activeSubscription();

        // Trial → Paid: yangi paid subscription, trialni cancel qilamiz
        if ($existingSubscription && $existingSubscription->status === 'trialing') {
            Log::channel('billing')->info('[Listener] Converting trial → paid subscription', [
                'business_id' => $business->id,
                'old_subscription_id' => $existingSubscription->id,
                'old_trial_ends_at' => $existingSubscription->trial_ends_at?->toIso8601String(),
            ]);

            return $this->subscriptionService->activate(
                business: $business,
                plan: $plan,
                paymentProvider: $transaction->provider,
                transactionId: $transaction->id
            );
        }

        // Active → Active: paid muddatga davr qo'shish (renewal/upgrade)
        if ($existingSubscription && $existingSubscription->isActive()) {
            return $this->subscriptionService->renewFromPayment(
                subscription: $existingSubscription,
                plan: $plan,
                paymentProvider: $transaction->provider,
                transactionId: $transaction->id
            );
        }

        // Obuna yo'q yoki muddati o'tgan: birinchi paid subscription
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
