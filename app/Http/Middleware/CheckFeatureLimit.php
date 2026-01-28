<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureLimit
{
    protected PlanLimitService $planLimitService;

    public function __construct(PlanLimitService $planLimitService)
    {
        $this->planLimitService = $planLimitService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $limitOrFeature  The limit key (e.g., 'users', 'monthly_leads') or feature key (e.g., 'hr_tasks')
     * @param  string|null  $type  Type of check: 'limit' or 'feature' (default: 'limit')
     */
    public function handle(Request $request, Closure $next, ?string $limitOrFeature = null, ?string $type = 'limit'): Response
    {
        // Get current business ID from session
        $businessId = session('current_business_id');

        if (!$businessId) {
            return $this->errorResponse('Business context is required', 400);
        }

        // Get the business
        $business = Business::find($businessId);

        if (!$business) {
            return $this->errorResponse('Business not found', 404);
        }

        // Check if business has an active subscription
        $subscription = $this->planLimitService->getActiveSubscription($business);

        if (!$subscription || !$subscription->plan) {
            return $this->errorResponse(
                'Aktiv obuna topilmadi. Iltimos, tarifni tanlang.',
                402,
                'NO_ACTIVE_SUBSCRIPTION'
            );
        }

        // If no specific limit/feature to check, just verify subscription exists
        if (!$limitOrFeature) {
            return $next($request);
        }

        // Check based on type
        if ($type === 'feature') {
            return $this->checkFeature($request, $next, $business, $limitOrFeature);
        }

        return $this->checkLimit($request, $next, $business, $limitOrFeature);
    }

    /**
     * Check if limit has been reached.
     */
    protected function checkLimit(Request $request, Closure $next, Business $business, string $limitKey): Response
    {
        if ($this->planLimitService->hasReachedLimit($business, $limitKey)) {
            $plan = $this->planLimitService->getCurrentPlan($business);
            $limit = $this->planLimitService->getPlanLimit($plan, $limitKey);
            $config = $this->planLimitService->getLimitConfig()[$limitKey] ?? null;
            $label = $config['label'] ?? $limitKey;

            return $this->errorResponse(
                "{$label} limiti tugadi ({$limit}). Tarifingizni yangilang.",
                403,
                'FEATURE_LIMIT_EXCEEDED',
                [
                    'limit_key' => $limitKey,
                    'limit_value' => $limit,
                    'upgrade_required' => true,
                ]
            );
        }

        return $next($request);
    }

    /**
     * Check if feature is enabled.
     */
    protected function checkFeature(Request $request, Closure $next, Business $business, string $featureKey): Response
    {
        if (!$this->planLimitService->hasFeature($business, $featureKey)) {
            $config = $this->planLimitService->getFeatureConfig()[$featureKey] ?? null;
            $label = $config['label'] ?? $featureKey;

            return $this->errorResponse(
                "{$label} xususiyati sizning tarifingizda mavjud emas. Tarifingizni yangilang.",
                403,
                'FEATURE_NOT_AVAILABLE',
                [
                    'feature_key' => $featureKey,
                    'upgrade_required' => true,
                ]
            );
        }

        return $next($request);
    }

    /**
     * Generate error response.
     */
    protected function errorResponse(
        string $message,
        int $status,
        ?string $errorCode = null,
        array $extra = []
    ): Response {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return response()->json(array_merge($response, $extra), $status);
    }
}
