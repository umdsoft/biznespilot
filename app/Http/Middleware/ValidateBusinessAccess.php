<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SECURITY: Validate Business Ownership/Access
 *
 * Prevents horizontal privilege escalation by ensuring users can only
 * access businesses they own or have been granted access to.
 *
 * CRITICAL: This middleware MUST be applied to ALL business-scoped routes
 */
class ValidateBusinessAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->route('businessId');

        // Skip validation if no businessId in route
        if (! $businessId) {
            return $next($request);
        }

        $user = $request->user();

        // Ensure user is authenticated
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // Check if user has access to this business
        if (! $this->userHasAccessToBusiness($user, $businessId)) {
            abort(403, 'You do not have permission to access this business');
        }

        return $next($request);
    }

    /**
     * Check if user has access to business
     *
     * @param  \App\Models\User  $user
     */
    protected function userHasAccessToBusiness($user, int $businessId): bool
    {
        // Option 1: User owns the business
        if ($user->business_id == $businessId) {
            return true;
        }

        // Option 2: User has explicit access (e.g., team member, admin)
        // Check if business exists in user's accessible businesses
        $hasAccess = $user->businesses()
            ->where('id', $businessId)
            ->exists();

        if ($hasAccess) {
            return true;
        }

        // Option 3: Super admin bypass (use with caution!)
        if ($user->hasRole('super_admin')) {
            \Log::warning('Super admin accessing business', [
                'user_id' => $user->id,
                'business_id' => $businessId,
                'ip' => request()->ip(),
            ]);

            return true;
        }

        return false;
    }
}
