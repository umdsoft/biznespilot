<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers for production environment.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers in production
        if (app()->environment('production')) {
            // Prevent clickjacking attacks
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

            // Prevent MIME type sniffing
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // Enable XSS filter
            $response->headers->set('X-XSS-Protection', '1; mode=block');

            // Referrer policy
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Permissions policy (disable unnecessary features)
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

            // Content Security Policy (adjust as needed for your app)
            // Note: This is a basic CSP, you may need to adjust based on your frontend needs
            if (! $request->is('api/*')) {
                $response->headers->set('Content-Security-Policy', implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.tailwindcss.com",
                    "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net",
                    "img-src 'self' data: https: blob:",
                    "connect-src 'self' https://api.anthropic.com https://graph.facebook.com https://api.telegram.org wss:",
                    "frame-ancestors 'self'",
                    "form-action 'self'",
                    "base-uri 'self'",
                ]));
            }

            // Strict Transport Security (HSTS) - always set, preload included
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
