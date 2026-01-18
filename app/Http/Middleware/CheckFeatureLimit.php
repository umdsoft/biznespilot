<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature to check (e.g., 'leads', 'team_members', 'chatbot_channels')
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        // Get current business ID from session
        $businessId = session('current_business_id');

        if (! $businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Business context is required',
            ], 400);
        }

        // Get the business with subscription and plan
        $business = Business::with(['subscriptions.plan'])->find($businessId);

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        // Get the active subscription
        $subscription = $business->subscriptions()
            ->with('plan')
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->whereDate('ends_at', '>=', now())
                    ->orWhere(function ($q) {
                        $q->where('status', 'trial')
                            ->whereDate('trial_ends_at', '>=', now());
                    });
            })
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription plan found',
            ], 402);
        }

        $plan = $subscription->plan;

        // Check feature limits based on the feature parameter
        if ($feature) {
            $limitExceeded = false;
            $limitMessage = '';

            switch ($feature) {
                case 'leads':
                    $currentCount = $business->leads()->count();
                    if ($plan->lead_limit && $currentCount >= $plan->lead_limit) {
                        $limitExceeded = true;
                        $limitMessage = "Lead limit reached ({$plan->lead_limit}). Please upgrade your plan.";
                    }
                    break;

                case 'team_members':
                    $currentCount = $business->teamMembers()->count();
                    if ($plan->team_member_limit && $currentCount >= $plan->team_member_limit) {
                        $limitExceeded = true;
                        $limitMessage = "Team member limit reached ({$plan->team_member_limit}). Please upgrade your plan.";
                    }
                    break;

                case 'chatbot_channels':
                    $currentCount = $business->chatbotConfigs()->count();
                    if ($plan->chatbot_channel_limit && $currentCount >= $plan->chatbot_channel_limit) {
                        $limitExceeded = true;
                        $limitMessage = "Chatbot channel limit reached ({$plan->chatbot_channel_limit}). Please upgrade your plan.";
                    }
                    break;

                default:
                    // Unknown feature, allow the request to proceed
                    break;
            }

            if ($limitExceeded) {
                return response()->json([
                    'success' => false,
                    'message' => $limitMessage,
                    'error_code' => 'FEATURE_LIMIT_EXCEEDED',
                    'upgrade_required' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
