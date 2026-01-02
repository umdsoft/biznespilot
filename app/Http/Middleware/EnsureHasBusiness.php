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
     * Redirect to onboarding if business exists but onboarding is not completed.
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

        // Get current business from session or first business
        $currentBusinessId = session('current_business_id');
        $currentBusiness = null;

        if ($currentBusinessId) {
            $currentBusiness = $user->businesses()->find($currentBusinessId);
        }

        if (!$currentBusiness) {
            $currentBusiness = $user->businesses()->first();
            session(['current_business_id' => $currentBusiness?->id]);
        }

        // Check if current business needs onboarding
        if ($currentBusiness && $currentBusiness->onboarding_status !== 'completed') {
            // Allow access to onboarding, diagnostic and some other routes
            // IMPORTANT: Allow access to dashboard and other business routes if user chooses to skip onboarding from welcome page
            if ($request->is('onboarding*') ||
                $request->is('business*') ||
                $request->is('logout') ||
                $request->is('switch-business*') ||
                $request->is('new-business*')) {
                return $next($request);
            }

            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
