<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

class PlanDataService
{
    /**
     * Get active plans for public pages (cached).
     * Trial plan is excluded.
     */
    public function getPublicPlans(): array
    {
        return Cache::remember('public_plans', 3600, function () {
            return Plan::where('is_active', true)
                ->where('slug', '!=', 'trial-pack')
                ->orderBy('sort_order')
                ->orderBy('price_monthly')
                ->get()
                ->map(fn ($plan) => [
                    'id' => $plan->id,
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price_monthly' => (int) $plan->price_monthly,
                    'price_yearly' => (int) $plan->price_yearly,
                    'currency' => $plan->currency ?? 'UZS',
                    'limits' => $plan->limits ?? [],
                    'features' => $plan->features ?? [],
                    'sort_order' => $plan->sort_order,
                ])
                ->values()
                ->toArray();
        });
    }
}
