<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get current business ID from session
        $businessId = session('current_business_id');

        if (! $businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Business context is required',
            ], 400);
        }

        // Get the business
        $business = Business::find($businessId);

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        // Get the active subscription (including trialing status)
        // SubscriptionService 'trialing' status yaratadi
        $subscription = $business->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereDate('ends_at', '>=', now())
                    ->orWhere(function ($q) {
                        $q->where('status', 'trialing')
                            ->whereDate('trial_ends_at', '>=', now());
                    });
            })
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aktiv obuna topilmadi. Iltimos, tarifni tanlang.',
                'error_code' => 'NO_ACTIVE_SUBSCRIPTION',
            ], 402);
        }

        // Trial tugash ogohlantirishi (3 kun qolganida)
        if ($subscription->status === 'trialing' && $subscription->trial_ends_at) {
            $daysRemaining = (int) ceil(now()->floatDiffInDays($subscription->trial_ends_at, false));
            if ($daysRemaining <= 3 && $daysRemaining >= 0) {
                $request->headers->set('X-Trial-Warning', "Trial {$daysRemaining} kunda tugaydi");
            }
        }

        // Subscription ma'lumotlarini request ga qo'shish
        $request->merge(['current_subscription' => $subscription]);

        return $next($request);
    }
}
