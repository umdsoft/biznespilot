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
                // Tracking script'lar uchun ruxsat:
                //  - Meta Pixel:    connect.facebook.net + facebook.com (img tr-pixel)
                //  - Google Analytics 4 + Tag Manager: googletagmanager.com + google-analytics.com
                //  - Yandex Metrika: mc.yandex.ru + mc.yandex.com (CDN)
                $response->headers->set('Content-Security-Policy', implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://connect.facebook.net https://www.googletagmanager.com https://www.google-analytics.com https://mc.yandex.ru https://mc.yandex.com",
                    "script-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://connect.facebook.net https://www.googletagmanager.com https://www.google-analytics.com https://mc.yandex.ru https://mc.yandex.com",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.tailwindcss.com",
                    "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net",
                    "img-src 'self' data: https: blob:",
                    "connect-src 'self' https://api.anthropic.com https://graph.facebook.com https://www.facebook.com https://www.google-analytics.com https://mc.yandex.ru https://mc.yandex.com https://api.telegram.org wss:",
                    "frame-src 'self' https://www.facebook.com https://www.googletagmanager.com",
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
