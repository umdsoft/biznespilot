<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Business;

class EnsureBusinessAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get business ID from header or request parameter
        $businessId = $request->header('X-Business-ID') ?? $request->input('business_id');

        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Business ID is required',
            ], 400);
        }

        $user = $request->user();

        // Check if user is the owner of the business
        $isOwner = Business::where('id', $businessId)
            ->where('user_id', $user->id)
            ->exists();

        // Check if user is a team member of the business
        $isTeamMember = $user->teamBusinesses()
            ->where('business_id', $businessId)
            ->exists();

        if (!$isOwner && !$isTeamMember) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this business',
            ], 403);
        }

        // Set current business ID in session
        session(['current_business_id' => $businessId]);

        return $next($request);
    }
}
