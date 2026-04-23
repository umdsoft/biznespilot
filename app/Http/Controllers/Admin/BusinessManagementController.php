<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessManagementController extends Controller
{
    /**
     * Display a listing of businesses
     */
    public function index()
    {
        $businesses = Business::with(['owner', 'subscriptions' => function ($q) {
                $q->whereIn('status', ['active', 'trialing'])
                    ->where('ends_at', '>', now())
                    ->with('plan')
                    ->latest();
            }])
            ->withCount(['customers', 'campaigns'])
            ->get()
            ->map(function ($business) {
                $activeSub = $business->subscriptions->first();

                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'industry' => $business->industry,
                    'status' => $business->status,
                    'owner_name' => $business->owner->name ?? 'N/A',
                    'owner_email' => $business->owner->email ?? 'N/A',
                    'customers_count' => $business->customers_count,
                    'campaigns_count' => $business->campaigns_count,
                    'created_at' => $business->created_at->diffForHumans(),
                    'created_at_raw' => $business->created_at->toDateTimeString(),
                    'subscription' => $activeSub ? [
                        'id' => $activeSub->id,
                        'plan_name' => $activeSub->plan->name ?? '—',
                        'plan_id' => $activeSub->plan_id,
                        'status' => $activeSub->status,
                        'ends_at' => $activeSub->ends_at?->format('d.m.Y'),
                        'days_remaining' => (int) max(0, ceil(now()->floatDiffInDays($activeSub->ends_at, false))),
                    ] : null,
                ];
            });

        $stats = [
            'total' => Business::count(),
            'active' => Business::where('status', 'active')->count(),
            'inactive' => Business::where('status', 'inactive')->count(),
            'this_month' => Business::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
            ]);

        return Inertia::render('Admin/Businesses/Index', [
            'businesses' => $businesses,
            'stats' => $stats,
            'plans' => $plans,
        ]);
    }

    /**
     * Display a specific business
     */
    public function show(Business $business)
    {
        $business->load(['owner', 'customers', 'campaigns', 'chatbot_conversations']);

        $businessData = [
            'id' => $business->id,
            'name' => $business->name,
            'industry' => $business->industry,
            'description' => $business->description,
            'status' => $business->status,
            'website' => $business->website,
            'phone' => $business->phone,
            'email' => $business->email,
            'address' => $business->address,
            'created_at' => $business->created_at->format('d M Y'),
            'owner' => [
                'id' => $business->owner->id,
                'name' => $business->owner->name,
                'email' => $business->owner->email,
                'phone' => $business->owner->phone,
            ],
        ];

        $stats = [
            'total_customers' => $business->customers()->count(),
            'total_campaigns' => $business->campaigns()->count(),
            'active_campaigns' => $business->campaigns()->whereIn('status', ['running', 'scheduled'])->count(),
            'total_conversations' => $business->chatbot_conversations()->count(),
            'pending_conversations' => $business->chatbot_conversations()->where('status', 'pending')->count(),
        ];

        // Recent activity
        $recentCustomers = $business->customers()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'created_at' => $customer->created_at->diffForHumans(),
            ]);

        $recentCampaigns = $business->campaigns()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at->diffForHumans(),
            ]);

        return Inertia::render('Admin/Businesses/Show', [
            'business' => $businessData,
            'stats' => $stats,
            'recentCustomers' => $recentCustomers,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }

    /**
     * Update business status
     */
    public function updateStatus(Request $request, Business $business)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $business->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Business status yangilandi');
    }

    /**
     * Admin tomonidan biznesga obuna berish
     */
    public function assignSubscription(Request $request, Business $business)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'duration_months' => 'required|integer|min:1|max:36',
            'force' => 'sometimes|boolean', // Admin over-limit downgrade uchun majburiy bayroq
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        // Downgrade himoyasi — agar biznes hozirgi resurslari yangi tarif limitidan ortiq
        // bo'lsa, admin 'force' bayrog'ini tasdiqlaganda yana o'tkazamiz, aks holda
        // konkret issues ro'yxati qaytariladi.
        $downgrade = app(\App\Services\PlanLimitService::class)->canDowngradeToPlan($business, $plan);
        if (!$downgrade['can_downgrade'] && !($validated['force'] ?? false)) {
            $issues = collect($downgrade['issues'] ?? [])
                ->map(fn ($i) => "{$i['label']}: {$i['current']} → yangi limit {$i['new_limit']}")
                ->values()
                ->all();

            return response()->json([
                'success' => false,
                'message' => "«{$plan->name}» tarifi hozirgi holatga mos kelmaydi. Ortiqcha resurslar mavjud.",
                'issues' => $issues,
                'requires_force' => true,
            ], 422);
        }

        // Avvalgi faol obunalarni bekor qilish
        Subscription::where('business_id', $business->id)
            ->whereIn('status', ['active', 'trialing'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Admin tomonidan yangi tarif tayinlandi',
            ]);

        // Yangi obuna yaratish
        $startsAt = now();
        $endsAt = now()->addMonths($validated['duration_months']);

        $amount = $validated['billing_cycle'] === 'yearly'
            ? $plan->price_yearly
            : $plan->price_monthly;

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $validated['billing_cycle'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'amount' => $amount,
            'currency' => 'UZS',
            'auto_renew' => false,
            'payment_provider' => 'admin',
            'metadata' => [
                'assigned_by' => auth()->user()->name,
                'assigned_at' => now()->toDateTimeString(),
            ],
        ]);

        // Keshni tozalash
        HandleInertiaRequests::clearSubscriptionCache($business->id);

        return redirect()->back()->with('success', "{$business->name} ga \"{$plan->name}\" tarifi {$validated['duration_months']} oyga muvaffaqiyatli tayinlandi");
    }

    /**
     * Delete a business
     */
    public function destroy(Business $business)
    {
        $businessName = $business->name;

        // Delete business (cascade will handle related records)
        $business->delete();

        return redirect()->route('admin.businesses.index')
            ->with('success', "{$businessName} biznesi muvaffaqiyatli o'chirildi");
    }
}
