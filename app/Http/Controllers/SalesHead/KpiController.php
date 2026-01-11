<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KpiController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/KPI/Index');
    }

    public function setTargets(Request $request)
    {
        // TODO: Implement
        return back()->with('success', 'KPI maqsadlari saqlandi');
    }
}
