<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use Inertia\Inertia;

class FlightRiskWebController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        return Inertia::render('HR/FlightRisk/Index', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }
}
