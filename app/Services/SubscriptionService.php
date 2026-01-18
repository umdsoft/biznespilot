<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionService
{
    /**
     * Create a new subscription for a business
     */
    public function create(Business $business, Plan $plan, string $billingCycle = 'monthly', bool $isTrial = false): Subscription
    {
        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $subscription = new Subscription;
        $subscription->business_id = $business->id;
        $subscription->plan_id = $plan->id;
        $subscription->status = $isTrial ? 'trial' : 'active';
        $subscription->billing_cycle = $billingCycle;
        $subscription->amount = $amount;
        $subscription->currency = 'UZS';
        $subscription->auto_renew = true;

        if ($isTrial) {
            $subscription->trial_ends_at = now()->addDays(14); // 14 day trial
            $subscription->starts_at = now();
        } else {
            $subscription->starts_at = now();
            $subscription->ends_at = $billingCycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();
        }

        $subscription->save();

        return $subscription;
    }

    /**
     * Upgrade/downgrade a subscription to a new plan
     */
    public function changePlan(Subscription $subscription, Plan $newPlan, ?string $billingCycle = null): Subscription
    {
        // Cancel current subscription
        $this->cancel($subscription, false);

        // Create new subscription
        $newBillingCycle = $billingCycle ?? $subscription->billing_cycle;

        return $this->create(
            $subscription->business,
            $newPlan,
            $newBillingCycle,
            false
        );
    }

    /**
     * Renew a subscription
     */
    public function renew(Subscription $subscription): Subscription
    {
        $subscription->starts_at = now();
        $subscription->ends_at = $subscription->billing_cycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();
        $subscription->status = 'active';
        $subscription->save();

        return $subscription;
    }

    /**
     * Cancel a subscription
     */
    public function cancel(Subscription $subscription, bool $immediate = false): Subscription
    {
        if ($immediate) {
            $subscription->status = 'cancelled';
            $subscription->ends_at = now();
        } else {
            $subscription->status = 'cancelled';
            // Keep ends_at as is for grace period
        }

        $subscription->auto_renew = false;
        $subscription->save();

        return $subscription;
    }

    /**
     * Convert trial to paid subscription
     */
    public function convertTrialToPaid(Subscription $subscription, string $billingCycle = 'monthly'): Subscription
    {
        if ($subscription->status !== 'trial') {
            throw new \Exception('Subscription is not a trial');
        }

        $plan = $subscription->plan;
        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $subscription->status = 'active';
        $subscription->billing_cycle = $billingCycle;
        $subscription->amount = $amount;
        $subscription->trial_ends_at = null;
        $subscription->starts_at = now();
        $subscription->ends_at = $billingCycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();
        $subscription->save();

        return $subscription;
    }

    /**
     * Suspend a subscription (for non-payment, etc.)
     */
    public function suspend(Subscription $subscription): Subscription
    {
        $subscription->status = 'suspended';
        $subscription->save();

        return $subscription;
    }

    /**
     * Reactivate a suspended subscription
     */
    public function reactivate(Subscription $subscription): Subscription
    {
        $subscription->status = 'active';
        $subscription->save();

        return $subscription;
    }

    /**
     * Check if subscription needs renewal
     */
    public function needsRenewal(Subscription $subscription): bool
    {
        if ($subscription->status !== 'active' || ! $subscription->auto_renew) {
            return false;
        }

        // Check if within 7 days of expiration
        return $subscription->ends_at && $subscription->ends_at->diffInDays(now()) <= 7;
    }

    /**
     * Get all expired subscriptions
     */
    public function getExpiredSubscriptions()
    {
        return Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->with(['business', 'plan'])
            ->get();
    }

    /**
     * Get all expiring trials
     */
    public function getExpiringTrials(int $daysThreshold = 3)
    {
        return Subscription::where('status', 'trial')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($daysThreshold)])
            ->with(['business', 'plan'])
            ->get();
    }

    /**
     * Calculate prorated amount for plan change
     */
    public function calculateProratedAmount(Subscription $currentSubscription, Plan $newPlan): float
    {
        // Get days remaining in current subscription
        $daysRemaining = max(0, now()->diffInDays($currentSubscription->ends_at, false));

        // Get total days in billing cycle
        $totalDays = $currentSubscription->billing_cycle === 'yearly' ? 365 : 30;

        // Calculate unused amount
        $unusedAmount = ($currentSubscription->amount / $totalDays) * $daysRemaining;

        // Calculate new plan amount
        $newAmount = $currentSubscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly;

        // Return prorated difference
        return max(0, $newAmount - $unusedAmount);
    }

    /**
     * Get subscription summary for a business
     */
    public function getSubscriptionSummary(Business $business): array
    {
        $subscription = $business->activeSubscription();

        if (! $subscription) {
            return [
                'has_subscription' => false,
                'plan' => null,
                'status' => 'no_subscription',
                'days_remaining' => null,
                'usage' => [],
            ];
        }

        return [
            'has_subscription' => true,
            'plan' => $subscription->plan,
            'status' => $subscription->status,
            'is_trial' => $subscription->status === 'trial',
            'billing_cycle' => $subscription->billing_cycle,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at ?? $subscription->trial_ends_at,
            'days_remaining' => $business->subscriptionDaysRemaining(),
            'auto_renew' => $subscription->auto_renew,
            'usage' => $business->getUsageStats(),
        ];
    }
}
