<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Settings');
    }

    public function update(Request $request)
    {
        // TODO: Implement
        return back()->with('success', 'Sozlamalar saqlandi');
    }
}
