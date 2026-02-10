<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\BusinessUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasBusiness
{
    /**
     * Handle an incoming request.
     * Optimized with session caching to reduce DB queries.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Skip redirects for non-GET requests
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // Skip check for certain routes
        if ($this->shouldSkipCheck($request)) {
            return $next($request);
        }

        // Use session-cached user context to avoid repeated DB queries
        $userContext = $this->getUserContext($user->id);

        // Handle team members (employees)
        if ($userContext['is_team_member']) {
            session(['current_business_id' => $userContext['business_id']]);

            // Allow access to department routes
            if ($this->isAllowedDepartmentRoute($request, $userContext['department'])) {
                return $next($request);
            }

            // Redirect to appropriate department panel
            return $this->redirectToDepartmentPanel($userContext['department']);
        }

        // Handle users with roles but no business (e.g., manually assigned operators/hr without team membership)
        if (! $userContext['has_business']) {
            // Check if user has a specific role that grants access to a panel
            $roleRedirect = $this->getRedirectForUserRole($user);

            if ($roleRedirect) {
                // Redirect to appropriate dashboard
                return $roleRedirect;
            }

            // If user has role and is already on correct route, allow access
            if ($this->userHasRoleAndOnCorrectRoute($user)) {
                return $next($request);
            }

            // Only new users without roles should go to welcome page
            return redirect()->route('welcome.index');
        }

        // Set business context
        $this->setBusinessContext($userContext);

        // Subscription tekshirish — trial tugagan bo'lsa subscription sahifasiga yo'naltirish
        if (! $this->isSubscriptionExemptRoute($request) && $userContext['business_id']) {
            if (! $this->hasActiveSubscription($userContext['business_id'])) {
                return redirect()->route('business.subscription.index')
                    ->with('warning', 'Sinov davri tugadi. Davom etish uchun tarif tanlang.');
            }
        }

        return $next($request);
    }

    /**
     * Get cached user context
     */
    private function getUserContext(string|int $userId): array
    {
        $cacheKey = "user_context_{$userId}";

        // Check session first (fastest)
        if (session()->has($cacheKey)) {
            return session($cacheKey);
        }

        // Build context with single optimized query
        $context = $this->buildUserContext($userId);

        // Cache in session for this request cycle
        session([$cacheKey => $context]);

        return $context;
    }

    /**
     * Build user context with optimized queries
     */
    private function buildUserContext(string|int $userId): array
    {
        // Check team membership first (single query)
        $teamMembership = BusinessUser::where('user_id', $userId)
            ->whereNotNull('department')
            ->select(['business_id', 'department'])
            ->first();

        if ($teamMembership) {
            return [
                'is_team_member' => true,
                'has_business' => true,
                'business_id' => $teamMembership->business_id,
                'department' => $teamMembership->department,
                'needs_onboarding' => false,
            ];
        }

        // Check owned businesses (single query with limit)
        $business = Business::where('user_id', $userId)
            ->select(['id', 'onboarding_status'])
            ->first();

        return [
            'is_team_member' => false,
            'has_business' => (bool) $business,
            'business_id' => $business?->id,
            'department' => null,
            'needs_onboarding' => $business && $business->onboarding_status !== 'completed',
        ];
    }

    /**
     * Check if route should skip business check
     */
    private function shouldSkipCheck(Request $request): bool
    {
        return $request->is('logout') ||
               $request->is('api/*') ||
               $request->is('sanctum/*') ||
               $request->is('_debugbar/*') ||
               $request->is('integrations/*/auth-url') ||
               $request->is('integrations/*/callback') ||
               $request->is('business/notifications*'); // Allow notifications for all departments
    }

    /**
     * Check if route is exempt from subscription check
     */
    private function isSubscriptionExemptRoute(Request $request): bool
    {
        return $request->is('business/subscription*') ||
               $request->is('business/settings*') ||
               $request->is('welcome*') ||
               $request->is('new-business*') ||
               $request->is('switch-business*') ||
               $request->is('logout');
    }

    /**
     * Check if business has active subscription (cached per request)
     */
    private function hasActiveSubscription(string|int $businessId): bool
    {
        $cacheKey = "sub_active_{$businessId}";

        if (session()->has($cacheKey)) {
            return session($cacheKey);
        }

        $business = Business::find($businessId);
        $isActive = $business && $business->hasActiveSubscription();

        session([$cacheKey => $isActive]);

        return $isActive;
    }

    /**
     * Check if route is allowed for department
     */
    private function isAllowedDepartmentRoute(Request $request, ?string $department): bool
    {
        $departmentRoutes = [
            'sales_head' => ['sales-head*'],
            'sales_operator' => ['operator*'],
            'operator' => ['operator*'],
            'marketing' => ['marketing*'],
            'hr' => ['hr*'], // HR has dedicated panel
            'finance' => ['finance*'],
        ];

        $allowedRoutes = $departmentRoutes[$department] ?? [];

        foreach ($allowedRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to department panel
     */
    private function redirectToDepartmentPanel(?string $department): Response
    {
        return match ($department) {
            'sales_head' => redirect()->route('sales-head.dashboard'),
            'sales_operator', 'operator' => redirect()->route('operator.dashboard'),
            'marketing' => redirect()->route('marketing.hub'),
            'finance' => redirect()->route('finance.dashboard'),
            'hr' => redirect()->route('hr.dashboard'), // HR has dedicated panel
            default => redirect()->route('business.dashboard'),
        };
    }

    /**
     * Set business context in session
     */
    private function setBusinessContext(array $context): void
    {
        $currentBusinessId = session('current_business_id');

        if (! $currentBusinessId || $currentBusinessId !== $context['business_id']) {
            session(['current_business_id' => $context['business_id']]);
        }
    }

    /**
     * Check if current route is onboarding related
     */
    private function isOnboardingRoute(Request $request): bool
    {
        return $request->is('onboarding*') ||
               $request->is('business*') ||
               $request->is('switch-business*') ||
               $request->is('new-business*') ||
               $request->is('welcome*');
    }

    /**
     * Get redirect URL for user based on their role (if they have no business/team membership)
     */
    private function getRedirectForUserRole($user): ?Response
    {
        $redirectMap = [
            'operator' => 'operator.dashboard',
            'sales_operator' => 'operator.dashboard',
            'sales_head' => 'sales-head.dashboard',
            'marketing' => 'marketing.hub',
            'finance' => 'finance.dashboard',
            'hr' => 'hr.dashboard',
        ];

        foreach ($redirectMap as $role => $routeName) {
            if ($user->hasRole($role)) {
                // Check if already on the target route to prevent infinite loop
                if (request()->routeIs($routeName)) {
                    return null; // Will be handled by userHasRoleAndOnCorrectRoute
                }

                return redirect()->route($routeName);
            }
        }

        // No matching role
        return null;
    }

    /**
     * Check if user has role and is already on their correct route
     */
    private function userHasRoleAndOnCorrectRoute($user): bool
    {
        $roleRouteMap = [
            'operator' => 'operator*',
            'sales_operator' => 'operator*',
            'sales_head' => 'sales-head*',
            'marketing' => 'marketing*',
            'finance' => 'finance*',
            'hr' => 'hr*',
        ];

        foreach ($roleRouteMap as $role => $routePattern) {
            if ($user->hasRole($role) && request()->is($routePattern)) {
                return true;
            }
        }

        return false;
    }
}
