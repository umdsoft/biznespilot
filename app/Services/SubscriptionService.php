<?php

namespace App\Services;

use App\Models\Billing\BillingTransaction;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PlanLimitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // =========================================================================
    // BILLING INTEGRATION METHODS (Payme/Click to'lovdan keyin chaqiriladi)
    // =========================================================================

    /**
     * Activate subscription after successful payment.
     *
     * Bu metod Payme/Click orqali to'lov muvaffaqiyatli bo'lganda
     * ActivateSubscriptionListener tomonidan chaqiriladi.
     *
     * @param Business $business - To'lov qilgan biznes
     * @param Plan $plan - Sotib olingan tarif
     * @param string $paymentProvider - 'payme' yoki 'click'
     * @param int $transactionId - BillingTransaction ID
     */
    public function activate(
        Business $business,
        Plan $plan,
        string $paymentProvider,
        int $transactionId
    ): Subscription {
        // Mavjud aktiv obunani bekor qilish
        $this->cancelActive($business);

        // Transaction metadata dan billing_cycle o'qish
        $billingCycle = 'monthly';
        $transaction = BillingTransaction::find($transactionId);
        if ($transaction) {
            $billingCycle = $transaction->getMetadata('billing_cycle', 'monthly');
        }

        $startsAt = now();
        $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

        $amount = $billingCycle === 'yearly'
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $billingCycle,
            'trial_ends_at' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'amount' => $amount,
            'currency' => 'UZS',
            'auto_renew' => true,
            'payment_provider' => $paymentProvider,
            'last_payment_at' => now(),
            'metadata' => [
                'activated_via' => $paymentProvider,
                'billing_transaction_id' => $transactionId,
                'billing_cycle' => $billingCycle,
            ],
        ]);

        Log::channel('billing')->info('[SubscriptionService] Subscription activated', [
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
            'provider' => $paymentProvider,
            'transaction_id' => $transactionId,
            'billing_cycle' => $billingCycle,
        ]);

        // Notification yuborish
        $this->notificationService->sendSystemNotification(
            $business,
            'Obuna aktivlashtirildi!',
            "{$plan->name} tarifi muvaffaqiyatli aktivlashtirildi. Keyingi to'lov: {$endsAt->format('d.m.Y')}"
        );

        return $subscription;
    }

    /**
     * Renew existing subscription after successful payment.
     *
     * Bu metod mavjud obunani to'lovdan keyin yangilaydi.
     *
     * @param Subscription $subscription - Mavjud obuna
     * @param Plan $plan - Tarif (yangi yoki mavjud)
     * @param string $paymentProvider - 'payme' yoki 'click'
     * @param int $transactionId - BillingTransaction ID
     */
    public function renewFromPayment(
        Subscription $subscription,
        Plan $plan,
        string $paymentProvider,
        int $transactionId
    ): Subscription {
        // Transaction metadata dan billing_cycle o'qish
        $billingCycle = $subscription->billing_cycle ?? 'monthly';
        $transaction = BillingTransaction::find($transactionId);
        if ($transaction) {
            $billingCycle = $transaction->getMetadata('billing_cycle', $billingCycle);
        }

        $addPeriod = $billingCycle === 'yearly' ? 'addYear' : 'addMonth';

        $newEndsAt = $subscription->ends_at > now()
            ? $subscription->ends_at->$addPeriod() // Muddati tugamagan - qo'shish
            : now()->$addPeriod(); // Muddati tugagan - yangi boshlanish

        $amount = $billingCycle === 'yearly'
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        $subscription->update([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $billingCycle,
            'ends_at' => $newEndsAt,
            'amount' => $amount,
            'payment_provider' => $paymentProvider,
            'last_payment_at' => now(),
            'metadata' => array_merge($subscription->metadata ?? [], [
                'last_renewed_via' => $paymentProvider,
                'last_billing_transaction_id' => $transactionId,
                'renewed_at' => now()->toISOString(),
                'billing_cycle' => $billingCycle,
            ]),
        ]);

        Log::channel('billing')->info('[SubscriptionService] Subscription renewed', [
            'subscription_id' => $subscription->id,
            'business_id' => $subscription->business_id,
            'plan_id' => $plan->id,
            'new_ends_at' => $newEndsAt,
            'provider' => $paymentProvider,
            'transaction_id' => $transactionId,
        ]);

        // Notification yuborish
        $this->notificationService->sendSystemNotification(
            $subscription->business,
            'Obuna yangilandi!',
            "Obunangiz {$newEndsAt->format('d.m.Y')} gacha uzaytirildi."
        );

        return $subscription->fresh();
    }

    // =========================================================================
    // STANDARD SUBSCRIPTION METHODS
    // =========================================================================

    /**
     * Create a new subscription for a business.
     */
    public function create(
        Business $business,
        Plan $plan,
        string $billingCycle = 'monthly',
        ?int $trialDays = null
    ): Subscription {
        // Cancel any existing active subscription
        $this->cancelActive($business);

        $amount = $billingCycle === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        $startsAt = now();

        $trialEndsAt = $trialDays
            ? now()->addDays($trialDays)
            : null;

        // Trial uchun ends_at = trial_ends_at (14 kun), pullik uchun billing cycle bo'yicha
        $endsAt = $trialEndsAt
            ?? ($billingCycle === 'yearly' ? now()->addYear() : now()->addMonth());

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => $trialEndsAt ? 'trialing' : 'active',
            'billing_cycle' => $billingCycle,
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'amount' => $amount,
            'currency' => 'UZS',
            'auto_renew' => true,
        ]);

        Log::info('Subscription created', [
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    /**
     * Upgrade subscription to a higher plan.
     */
    public function upgrade(
        Business $business,
        Plan $newPlan,
        bool $prorateCredit = true
    ): array {
        $currentSubscription = $this->getActiveSubscription($business);

        if (!$currentSubscription) {
            return $this->create($business, $newPlan, 'monthly')
                ? ['success' => true, 'subscription' => $this->getActiveSubscription($business)]
                : ['success' => false, 'error' => 'Could not create subscription'];
        }

        $currentPlan = $currentSubscription->plan;

        // Validate upgrade
        if ($newPlan->id === $currentPlan->id) {
            return ['success' => false, 'error' => 'Siz allaqachon bu rejada turibsiz'];
        }

        $currentPrice = $currentSubscription->billing_cycle === 'yearly'
            ? $currentPlan->price_yearly
            : $currentPlan->price_monthly;

        $newPrice = $currentSubscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly;

        if ($newPrice <= $currentPrice) {
            return ['success' => false, 'error' => 'Upgrade uchun yuqoriroq reja tanlang'];
        }

        DB::beginTransaction();
        try {
            $proratedCredit = 0;

            if ($prorateCredit) {
                $proratedCredit = $this->calculateProratedCredit($currentSubscription);
            }

            $amountDue = $newPrice - $proratedCredit;

            // Update subscription
            $currentSubscription->update([
                'plan_id' => $newPlan->id,
                'amount' => $newPrice,
                'status' => 'active',
            ]);

            // Create invoice if there's an amount due
            $invoice = null;
            if ($amountDue > 0) {
                $invoice = $this->createInvoice($business, $currentSubscription, $amountDue, 'upgrade');
            }

            DB::commit();

            // Send notification
            $this->notificationService->sendSystemNotification(
                $business,
                'Reja yangilandi',
                "Sizning rejangiz {$newPlan->name} ga o'zgartirildi."
            );

            Log::info('Subscription upgraded', [
                'business_id' => $business->id,
                'old_plan_id' => $currentPlan->id,
                'new_plan_id' => $newPlan->id,
                'prorated_credit' => $proratedCredit,
                'amount_due' => $amountDue,
            ]);

            return [
                'success' => true,
                'subscription' => $currentSubscription->fresh(),
                'prorated_credit' => $proratedCredit,
                'amount_due' => $amountDue,
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription upgrade failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Upgrade bajarilmadi: ' . $e->getMessage()];
        }
    }

    /**
     * Downgrade subscription to a lower plan.
     */
    public function downgrade(
        Business $business,
        Plan $newPlan,
        bool $effectiveAtPeriodEnd = true
    ): array {
        $currentSubscription = $this->getActiveSubscription($business);

        if (!$currentSubscription) {
            return ['success' => false, 'error' => 'Aktiv obuna topilmadi'];
        }

        $currentPlan = $currentSubscription->plan;

        // Validate downgrade
        if ($newPlan->id === $currentPlan->id) {
            return ['success' => false, 'error' => 'Siz allaqachon bu rejada turibsiz'];
        }

        $currentPrice = $currentSubscription->billing_cycle === 'yearly'
            ? $currentPlan->price_yearly
            : $currentPlan->price_monthly;

        $newPrice = $currentSubscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly;

        if ($newPrice >= $currentPrice) {
            return ['success' => false, 'error' => 'Downgrade uchun pastroq reja tanlang'];
        }

        // Check limits
        $limitCheck = $this->checkPlanLimits($business, $newPlan);
        if (!$limitCheck['can_downgrade']) {
            return ['success' => false, 'error' => $limitCheck['message']];
        }

        DB::beginTransaction();
        try {
            if ($effectiveAtPeriodEnd) {
                // Schedule downgrade for end of current period
                $currentSubscription->update([
                    'scheduled_plan_id' => $newPlan->id,
                    'scheduled_change_at' => $currentSubscription->ends_at,
                ]);

                $message = "Rejangiz {$currentSubscription->ends_at->format('d.m.Y')} da {$newPlan->name} ga o'zgartiriladi.";
            } else {
                // Immediate downgrade with credit
                $unusedCredit = $this->calculateProratedCredit($currentSubscription);

                $currentSubscription->update([
                    'plan_id' => $newPlan->id,
                    'amount' => $newPrice,
                ]);

                // Create credit note if applicable
                if ($unusedCredit > 0) {
                    $this->createCreditNote($business, $unusedCredit);
                }

                $message = "Sizning rejangiz {$newPlan->name} ga o'zgartirildi.";
            }

            DB::commit();

            $this->notificationService->sendSystemNotification(
                $business,
                'Reja o\'zgartirildi',
                $message
            );

            Log::info('Subscription downgraded', [
                'business_id' => $business->id,
                'old_plan_id' => $currentPlan->id,
                'new_plan_id' => $newPlan->id,
                'effective_at_period_end' => $effectiveAtPeriodEnd,
            ]);

            return [
                'success' => true,
                'subscription' => $currentSubscription->fresh(),
                'effective_at' => $effectiveAtPeriodEnd ? $currentSubscription->ends_at : now(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription downgrade failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Downgrade bajarilmadi: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(
        Business $business,
        bool $immediately = false,
        ?string $reason = null
    ): array {
        $subscription = $this->getActiveSubscription($business);

        if (!$subscription) {
            return ['success' => false, 'error' => 'Aktiv obuna topilmadi'];
        }

        DB::beginTransaction();
        try {
            if ($immediately) {
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ]);
            } else {
                $subscription->update([
                    'auto_renew' => false,
                    'cancellation_reason' => $reason,
                    'scheduled_cancellation_at' => $subscription->ends_at,
                ]);
            }

            DB::commit();

            $this->notificationService->sendSystemNotification(
                $business,
                'Obuna bekor qilindi',
                $immediately
                    ? 'Obunangiz bekor qilindi.'
                    : "Obunangiz {$subscription->ends_at->format('d.m.Y')} da tugaydi."
            );

            Log::info('Subscription cancelled', [
                'business_id' => $business->id,
                'subscription_id' => $subscription->id,
                'immediately' => $immediately,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'subscription' => $subscription->fresh(),
                'ends_at' => $immediately ? now() : $subscription->ends_at,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription cancellation failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Bekor qilish bajarilmadi'];
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Business $business): array
    {
        $subscription = Subscription::where('business_id', $business->id)
            ->where('auto_renew', false)
            ->whereNull('cancelled_at')
            ->where('ends_at', '>', now())
            ->first();

        if (!$subscription) {
            return ['success' => false, 'error' => 'Davom ettirish uchun obuna topilmadi'];
        }

        $subscription->update([
            'auto_renew' => true,
            'scheduled_cancellation_at' => null,
            'cancellation_reason' => null,
        ]);

        $this->notificationService->sendSystemNotification(
            $business,
            'Obuna davom ettirildi',
            'Obunangiz avtomatik uzaytiriladi.'
        );

        return [
            'success' => true,
            'subscription' => $subscription->fresh(),
        ];
    }

    /**
     * Renew subscription.
     */
    public function renew(Business $business): array
    {
        $subscription = $this->getActiveSubscription($business);

        if (!$subscription) {
            return ['success' => false, 'error' => 'Aktiv obuna topilmadi'];
        }

        if (!$subscription->auto_renew) {
            return ['success' => false, 'error' => 'Avtomatik uzaytirish o\'chirilgan'];
        }

        DB::beginTransaction();
        try {
            $newEndsAt = $subscription->billing_cycle === 'yearly'
                ? $subscription->ends_at->addYear()
                : $subscription->ends_at->addMonth();

            // Check for scheduled plan change
            if ($subscription->scheduled_plan_id) {
                $newPlan = Plan::find($subscription->scheduled_plan_id);
                if ($newPlan) {
                    $subscription->update([
                        'plan_id' => $newPlan->id,
                        'amount' => $subscription->billing_cycle === 'yearly'
                            ? $newPlan->price_yearly
                            : $newPlan->price_monthly,
                        'scheduled_plan_id' => null,
                        'scheduled_change_at' => null,
                    ]);
                }
            }

            $subscription->update([
                'starts_at' => $subscription->ends_at,
                'ends_at' => $newEndsAt,
                'status' => 'active',
            ]);

            // Create invoice
            $invoice = $this->createInvoice($business, $subscription, $subscription->amount, 'renewal');

            DB::commit();

            Log::info('Subscription renewed', [
                'business_id' => $business->id,
                'subscription_id' => $subscription->id,
                'new_ends_at' => $newEndsAt,
            ]);

            return [
                'success' => true,
                'subscription' => $subscription->fresh(),
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription renewal failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Uzaytirish bajarilmadi'];
        }
    }

    /**
     * Change billing cycle.
     */
    public function changeBillingCycle(Business $business, string $newCycle): array
    {
        if (!in_array($newCycle, ['monthly', 'yearly'])) {
            return ['success' => false, 'error' => 'Noto\'g\'ri to\'lov davri'];
        }

        $subscription = $this->getActiveSubscription($business);

        if (!$subscription) {
            return ['success' => false, 'error' => 'Aktiv obuna topilmadi'];
        }

        if ($subscription->billing_cycle === $newCycle) {
            return ['success' => false, 'error' => 'Siz allaqachon bu to\'lov davrida turibsiz'];
        }

        $plan = $subscription->plan;
        $newAmount = $newCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        // Calculate prorated adjustment
        $proratedCredit = $this->calculateProratedCredit($subscription);
        $amountDue = $newAmount - $proratedCredit;

        DB::beginTransaction();
        try {
            $newEndsAt = $newCycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $subscription->update([
                'billing_cycle' => $newCycle,
                'amount' => $newAmount,
                'starts_at' => now(),
                'ends_at' => $newEndsAt,
            ]);

            $invoice = null;
            if ($amountDue > 0) {
                $invoice = $this->createInvoice($business, $subscription, $amountDue, 'cycle_change');
            }

            DB::commit();

            $this->notificationService->sendSystemNotification(
                $business,
                'To\'lov davri o\'zgartirildi',
                $newCycle === 'yearly'
                    ? 'Siz yillik to\'lovga o\'tdingiz. 2 oy tekin!'
                    : 'Siz oylik to\'lovga o\'tdingiz.'
            );

            return [
                'success' => true,
                'subscription' => $subscription->fresh(),
                'prorated_credit' => $proratedCredit,
                'amount_due' => $amountDue,
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => 'O\'zgartirish bajarilmadi'];
        }
    }

    /**
     * Get active subscription for a business.
     */
    public function getActiveSubscription(Business $business): ?Subscription
    {
        return Subscription::where('business_id', $business->id)
            ->whereIn('status', ['active', 'trialing'])
            ->where('ends_at', '>', now())
            ->first();
    }

    /**
     * Cancel any active subscription.
     */
    protected function cancelActive(Business $business): void
    {
        Subscription::where('business_id', $business->id)
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /**
     * Calculate prorated credit for unused time.
     */
    protected function calculateProratedCredit(Subscription $subscription): float
    {
        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        $remainingDays = now()->diffInDays($subscription->ends_at, false);

        if ($totalDays <= 0 || $remainingDays <= 0) {
            return 0;
        }

        $dailyRate = $subscription->amount / $totalDays;

        return round($dailyRate * $remainingDays, 2);
    }

    /**
     * Check if business can downgrade to a plan.
     * Uses PlanLimitService for centralized limit checking.
     */
    protected function checkPlanLimits(Business $business, Plan $plan): array
    {
        $planLimitService = app(PlanLimitService::class);
        return $planLimitService->canDowngradeToPlan($business, $plan);
    }

    /**
     * Create invoice for subscription action.
     */
    protected function createInvoice(
        Business $business,
        Subscription $subscription,
        float $amount,
        string $type
    ): Invoice {
        $invoiceNumber = 'INV-' . strtoupper(uniqid());

        return Invoice::create([
            'business_id' => $business->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'UZS',
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'items' => [
                [
                    'description' => $this->getInvoiceDescription($subscription, $type),
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total' => $amount,
                ],
            ],
        ]);
    }

    /**
     * Get invoice item description.
     */
    protected function getInvoiceDescription(Subscription $subscription, string $type): string
    {
        $planName = $subscription->plan->name;
        $cycle = $subscription->billing_cycle === 'yearly' ? 'yillik' : 'oylik';

        return match ($type) {
            'upgrade' => "{$planName} rejasiga upgrade ({$cycle})",
            'renewal' => "{$planName} rejasi uzaytirish ({$cycle})",
            'cycle_change' => "{$planName} rejasi - to'lov davri o'zgarishi",
            default => "{$planName} rejasi ({$cycle})",
        };
    }

    /**
     * Create credit note for unused subscription time.
     */
    protected function createCreditNote(Business $business, float $amount): void
    {
        // Store credit for future use
        $business->update([
            'subscription_credit' => ($business->subscription_credit ?? 0) + $amount,
        ]);

        Log::info('Credit note created', [
            'business_id' => $business->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Get subscription status for a business.
     */
    public function getStatus(Business $business): array
    {
        $subscription = $this->getActiveSubscription($business);

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'inactive',
                'message' => 'Aktiv obuna yo\'q',
            ];
        }

        $plan = $subscription->plan;

        // Trial uchun trial_ends_at, pullik uchun ends_at
        $effectiveEndDate = ($subscription->status === 'trialing' && $subscription->trial_ends_at)
            ? $subscription->trial_ends_at
            : $subscription->ends_at;
        $daysRemaining = (int) ceil(now()->floatDiffInDays($effectiveEndDate, false));

        return [
            'has_subscription' => true,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],
            'billing_cycle' => $subscription->billing_cycle,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'days_remaining' => max(0, $daysRemaining),
            'auto_renew' => $subscription->auto_renew,
            'is_trial' => $subscription->status === 'trialing',
            'trial_ends_at' => $subscription->trial_ends_at,
            'scheduled_plan_change' => $subscription->scheduled_plan_id
                ? Plan::find($subscription->scheduled_plan_id)?->name
                : null,
        ];
    }

    /**
     * Get available plans for upgrade/downgrade.
     * Uses new JSON-based limits and features structure.
     */
    public function getAvailablePlans(Business $business): array
    {
        $currentSubscription = $this->getActiveSubscription($business);
        $currentPlanId = $currentSubscription?->plan_id;

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();

        return $plans->map(function ($plan) use ($currentPlanId, $currentSubscription) {
            $isCurrent = $plan->id === $currentPlanId;
            $isUpgrade = !$isCurrent && $currentSubscription &&
                $plan->price_monthly > $currentSubscription->plan->price_monthly;
            $isDowngrade = !$isCurrent && $currentSubscription &&
                $plan->price_monthly < $currentSubscription->plan->price_monthly;

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'features' => $plan->features ?? [],
                'limits' => $plan->limits ?? [],
                'is_current' => $isCurrent,
                'is_upgrade' => $isUpgrade,
                'is_downgrade' => $isDowngrade,
            ];
        })->toArray();
    }
}
