<?php

namespace App\Http\Middleware;

use App\Exceptions\NoActiveSubscriptionException;
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
        $business = $this->resolveBusiness($request);

        if (! $business) {
            throw new NoActiveSubscriptionException;
        }

        // Get the active subscription (scope: active/trialing, latest first)
        $subscription = $business->subscriptions()->active()->first();

        if (! $subscription) {
            throw new NoActiveSubscriptionException;
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

    /**
     * Resolve the current business from request or session.
     */
    protected function resolveBusiness(Request $request): ?Business
    {
        // Try from authenticated user's current business (accessor)
        if ($request->user()) {
            try {
                $business = $request->user()->currentBusiness;
                if ($business) {
                    return $business;
                }
            } catch (\Exception $e) {
                // Accessor mavjud emas yoki xato — fallback ga o'tish
            }
        }

        // Fallback to session
        $businessId = session('current_business_id');
        if ($businessId) {
            return Business::find($businessId);
        }

        return null;
    }
}
