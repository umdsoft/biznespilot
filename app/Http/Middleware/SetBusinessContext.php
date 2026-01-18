<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetBusinessContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // If no business is selected in session, auto-select the first one
        if (! session()->has('current_business_id')) {
            // Get user's first business (owned or member of)
            $firstBusiness = $user->businesses()->first() ?? $user->teamBusinesses()->first();

            if ($firstBusiness) {
                session(['current_business_id' => $firstBusiness->id]);
            }
        }

        // Verify that the selected business is still accessible
        if (session()->has('current_business_id')) {
            $businessId = session('current_business_id');

            // Check if user has access to this business
            $hasAccess = $user->businesses()->where('id', $businessId)->exists()
                || $user->teamBusinesses()->where('business_id', $businessId)->exists();

            if (! $hasAccess) {
                // User lost access, clear and select a new one
                session()->forget('current_business_id');

                $firstBusiness = $user->businesses()->first() ?? $user->teamBusinesses()->first();

                if ($firstBusiness) {
                    session(['current_business_id' => $firstBusiness->id]);
                }
            }
        }

        return $next($request);
    }
}
