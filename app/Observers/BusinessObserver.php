<?php

namespace App\Observers;

use App\Models\Business;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

/**
 * BusinessObserver - Business yaratilganda avtomatik subscription yaratish
 *
 * Bu observer yangi business yaratilganda:
 * - "Business" tarifida 14 kunlik bepul sinov beradi (ENG FOYDALI)
 * - Trial tugagandan keyin foydalanuvchi Free ga tushadi yoki upgrade qiladi
 */
class BusinessObserver
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle the Business "created" event.
     */
    public function created(Business $business): void
    {
        // Agar allaqachon subscription mavjud bo'lsa - o'tkazib yuborish
        if ($business->subscriptions()->exists()) {
            Log::info('BusinessObserver: Business already has subscription, skipping', [
                'business_id' => $business->id,
            ]);
            return;
        }

        try {
            // "Business" tarifida 14 kunlik trial berish (ENG FOYDALI)
            // Bu foydalanuvchilarga to'liq funksionallikni sinash imkonini beradi
            $plan = Plan::where('slug', 'business')
                ->where('is_active', true)
                ->first();

            if (!$plan) {
                // Fallback - eng yuqori pullik tarifni olish
                $plan = Plan::where('is_active', true)
                    ->where('price_monthly', '>', 0)
                    ->orderBy('price_monthly', 'desc')
                    ->first();
            }

            if (!$plan) {
                // Oxirgi fallback - Free tarif
                $plan = Plan::where('slug', 'free')
                    ->where('is_active', true)
                    ->first();
            }

            if (!$plan) {
                Log::error('BusinessObserver: No active plan found', [
                    'business_id' => $business->id,
                ]);
                return;
            }

            // 14 kunlik trial subscription yaratish
            $subscription = $this->subscriptionService->create(
                business: $business,
                plan: $plan,
                billingCycle: 'monthly',
                trialDays: 14
            );

            Log::info('BusinessObserver: Trial subscription created successfully', [
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'subscription_id' => $subscription->id,
                'trial_ends_at' => $subscription->trial_ends_at,
            ]);

        } catch (\Exception $e) {
            Log::error('BusinessObserver: Failed to create subscription', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
