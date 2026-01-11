<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BusinessUser;

class SalesHeadMiddleware
{
    /**
     * Handle an incoming request.
     * Check if user is sales_head department member
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $businessId = session('current_business_id');

        if (!$businessId) {
            return redirect('/business')->with('error', 'Biznes tanlanmagan');
        }

        // Check if user is sales_head in current business
        $membership = BusinessUser::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('department', 'sales_head')
            ->first();

        if (!$membership) {
            // Check if user is business owner
            $business = \App\Models\Business::find($businessId);
            if ($business && $business->user_id === $user->id) {
                // Owner can access sales head panel too
                return $next($request);
            }

            return redirect('/business')->with('error', 'Sizda sotuv bo\'limi rahbari huquqlari yo\'q');
        }

        return $next($request);
    }
}
