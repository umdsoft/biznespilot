<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Permission to check (e.g., 'manage:leads')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // Get current business from session
        $businessId = session('current_business_id');

        if (! $businessId) {
            abort(403, 'No business selected');
        }

        // Check if user has permission
        if (! $user->hasPermission($businessId, $permission)) {
            abort(403, 'You do not have permission to perform this action');
        }

        return $next($request);
    }
}
