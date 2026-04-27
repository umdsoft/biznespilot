<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Sales Script Arsenal — barcha 3 panel uchun bitta endpoint.
 *
 * Avval 3 ta alohida metod (operatorIndex/salesHeadIndex/businessIndex) va
 * 3 ta nusxa Vue sahifa bor edi. Endi:
 *   - Bitta `index()` metodi panelType bilan
 *   - Bitta Vue sahifa (Business/SalesScript/Index.vue) dynamic layoutComponent bilan
 *   - Eski 3 metod backwards-compat uchun saqlanadi (route yo'qotilmasin)
 *
 * Xavfsizlik: avvalgi mahalliy `getCurrentBusiness()` faqat session orqali o'qirdi
 * (validatsiyasiz — session-spoofing xavfi). HasCurrentBusiness traiti
 * accessibleBusinessIds tekshiruvi orqali xavfsiz business qaytaradi.
 */
class SalesScriptArsenalController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Universal index — barcha rollar uchun.
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);
        if (! $business) {
            return redirect()->route('login');
        }

        return Inertia::render('Business/SalesScript/Index', [
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'panelType' => $this->detectPanelType($business),
        ]);
    }

    /**
     * Backwards-compat: eski route'lar uchun forward qiladi.
     */
    public function operatorIndex(Request $request)
    {
        return $this->index($request);
    }

    public function salesHeadIndex(Request $request)
    {
        return $this->index($request);
    }

    public function businessIndex(Request $request)
    {
        return $this->index($request);
    }
}
