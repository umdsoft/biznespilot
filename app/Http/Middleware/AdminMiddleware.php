<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Iltimos, tizimga kiring');
        }

        // Check if user has admin role
        $user = auth()->user();

        // Assuming we have a 'role' field or relationship
        // Check if user is admin (you can adjust this based on your role system)
        if (! $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
            abort(403, 'Sizda admin paneliga kirish huquqi yo\'q');
        }

        return $next($request);
    }
}
