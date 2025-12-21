<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasBusiness
{
    /**
     * Handle an incoming request.
     * Redirect to welcome page if user doesn't have a business.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has at least one business
        if (!$user->businesses()->exists()) {
            // Allow access to welcome routes
            if ($request->is('welcome*') || $request->is('logout')) {
                return $next($request);
            }

            return redirect()->route('welcome.index');
        }

        return $next($request);
    }
}
