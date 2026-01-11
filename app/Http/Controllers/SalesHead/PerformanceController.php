<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Performance/Index');
    }

    public function team()
    {
        return Inertia::render('SalesHead/Performance/Team');
    }

    public function individual($member)
    {
        return Inertia::render('SalesHead/Performance/Individual');
    }
}
