<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BusinessUser;
use App\Models\Business;

class EnsureHasBusiness
{
    /**
     * Handle an incoming request.
     * Optimized with session caching to reduce DB queries.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Skip redirects for non-GET requests
        if (!$request->isMethod('GET')) {
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

        // Handle business owners
        if (!$userContext['has_business']) {
            return redirect()->route('welcome.index');
        }

        // Set business context
        $this->setBusinessContext($userContext);

        // Check onboarding status
        if ($userContext['needs_onboarding'] && !$this->isOnboardingRoute($request)) {
            return redirect()->route('onboarding.index');
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
               $request->is('_debugbar/*');
    }

    /**
     * Check if route is allowed for department
     */
    private function isAllowedDepartmentRoute(Request $request, ?string $department): bool
    {
        $departmentRoutes = [
            'sales_head' => ['sales-head*'],
            'sales_operator' => ['sales-head*', 'operator*'],
            'marketing' => ['marketing*'],
            'hr' => ['hr*'],
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
            'sales_head', 'sales_operator' => redirect()->route('sales-head.dashboard'),
            'marketing' => redirect()->route('business.dashboard'), // TODO: marketing panel
            'hr' => redirect()->route('business.dashboard'), // TODO: hr panel
            'finance' => redirect()->route('business.dashboard'), // TODO: finance panel
            default => redirect()->route('business.dashboard'),
        };
    }

    /**
     * Set business context in session
     */
    private function setBusinessContext(array $context): void
    {
        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId || $currentBusinessId !== $context['business_id']) {
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

}
