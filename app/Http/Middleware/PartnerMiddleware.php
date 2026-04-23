<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner area access guard.
 *
 * /partner/* route'lari uchun access nazorati. 3 xil foydalanuvchi panelga
 * kiradi:
 *
 *   1. partner       — o'z commission/referrals/payouts dashboard'i
 *   2. super_admin   — barcha partnerlarni ko'rish huquqi (support uchun)
 *   3. admin         — support vazifalari uchun
 *
 * Apply sahifasi (/partner/apply) bu middleware'dan TASHQARIDA qoladi —
 * partner bo'lmagan userlar ham apply qilishi kerak.
 */
class PartnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Iltimos, tizimga kiring');
        }

        $user = auth()->user();

        $allowedRoles = ['partner', 'admin', 'super_admin'];
        $hasAccess = false;
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                $hasAccess = true;
                break;
            }
        }

        if (! $hasAccess) {
            // Agar partner bo'lmagan user /partner'ga kirsa — apply sahifasiga yo'nalt
            return redirect()->route('partner.apply')
                ->with('info', "Partner paneliga kirish uchun avval ariza topshiring.");
        }

        return $next($request);
    }
}
