<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\Partner\PartnerReferralTracker;
use Illuminate\Http\Request;

/**
 * Public /refer/{code} — cookie o'rnatib landing'ga redirect qiladi.
 */
class PartnerReferralController extends Controller
{
    public function __construct(
        protected PartnerReferralTracker $tracker
    ) {}

    public function track(Request $request, string $code)
    {
        $partner = $this->tracker->trackClick($request, $code);

        // Invalid code — landing'ga to'g'ridan-to'g'ri yubor
        if (! $partner) {
            return redirect('/');
        }

        // Landing to`param yoki default /
        $to = $request->query('to', '/');
        if (! str_starts_with($to, '/')) {
            $to = '/';
        }

        return redirect($to)
            ->withCookie($this->tracker->makeCookie($code));
    }
}
