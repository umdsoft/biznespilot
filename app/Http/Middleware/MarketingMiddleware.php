<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketingMiddleware
{
    protected DepartmentMiddleware $departmentMiddleware;

    public function __construct(DepartmentMiddleware $departmentMiddleware)
    {
        $this->departmentMiddleware = $departmentMiddleware;
    }

    /**
     * Handle an incoming request.
     * Check if user is marketing department member
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->departmentMiddleware->handle($request, $next, 'marketing');
    }
}
