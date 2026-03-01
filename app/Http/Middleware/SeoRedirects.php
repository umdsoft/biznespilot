<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        // Remove trailing slash (except for root /)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $query = $request->getQueryString();
            $url = rtrim($path, '/') . ($query ? '?' . $query : '');

            return redirect($url, 301);
        }

        $response = $next($request);

        // Add security + SEO headers for HTML responses
        if ($response instanceof \Illuminate\Http\Response
            || $response instanceof \Inertia\Response) {
            // X-Content-Type-Options prevents MIME sniffing
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            // Referrer policy for SEO link attribution
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        return $response;
    }
}
