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

        // Get the active subscription
        $subscription = $business->subscriptions()
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now())
            ->first();

        // Check if trial is active
        $hasActiveTrial = $business->subscriptions()
            ->where('status', 'trial')
            ->whereDate('trial_ends_at', '>=', now())
            ->exists();

        if (! $subscription && ! $hasActiveTrial) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found. Please upgrade your plan.',
                'error_code' => 'NO_ACTIVE_SUBSCRIPTION',
            ], 402);
        }

        return $next($request);
    }
}
