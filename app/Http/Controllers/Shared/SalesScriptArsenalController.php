<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesScriptArsenalController extends Controller
{
    /**
     * Get the current business from session
     */
    protected function getCurrentBusiness()
    {
        $businessId = session('current_business_id');

        if (! $businessId) {
            return null;
        }

        return Business::find($businessId);
    }

    /**
     * Display the sales script arsenal for operators
     */
    public function operatorIndex(Request $request)
    {
        $business = $this->getCurrentBusiness();

        return Inertia::render('Operator/SalesScript/Index', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }

    /**
     * Display the sales script arsenal for sales head
     */
    public function salesHeadIndex(Request $request)
    {
        $business = $this->getCurrentBusiness();

        return Inertia::render('SalesHead/SalesScript/Index', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }

    /**
     * Display the sales script arsenal for business panel
     */
    public function businessIndex(Request $request)
    {
        $business = $this->getCurrentBusiness();

        return Inertia::render('Business/SalesScript/Index', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }
}
