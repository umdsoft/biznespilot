<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetBusinessContext
{
    /**
     * Handle an incoming request.
     *
     * Performance: Har request'da 2-3 DB query o'rniga cached access check.
     * 5 minutlik cache — business access o'zgarsa invalidation kerak bo'lmaydi
     * (acceptable: yangi ta'minot 5 daq kechikadi).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // If no business is selected in session, auto-select the first one (cached)
        if (! session()->has('current_business_id')) {
            $firstBusinessId = $this->firstAccessibleBusinessId($user->id);
            if ($firstBusinessId) {
                session(['current_business_id' => $firstBusinessId]);
            }
        }

        // Verify that the selected business is still accessible (cached)
        if (session()->has('current_business_id')) {
            $businessId = session('current_business_id');

            $hasAccess = Cache::remember(
                "biz_access:{$user->id}:{$businessId}",
                300, // 5 min
                fn () => $user->businesses()->where('id', $businessId)->exists()
                    || $user->teamBusinesses()->where('business_id', $businessId)->exists()
            );

            if (! $hasAccess) {
                session()->forget('current_business_id');
                Cache::forget("biz_first:{$user->id}");

                $firstBusinessId = $this->firstAccessibleBusinessId($user->id);
                if ($firstBusinessId) {
                    session(['current_business_id' => $firstBusinessId]);
                }
            }
        }

        return $next($request);
    }

    /**
     * User uchun birinchi kirish mumkin bo'lgan biznes ID ni cache'dan oladi.
     */
    protected function firstAccessibleBusinessId(string $userId): ?string
    {
        return Cache::remember("biz_first:{$userId}", 300, function () use ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return null;
            }
            $first = $user->businesses()->first() ?? $user->teamBusinesses()->first();
            return $first?->id;
        });
    }
}
