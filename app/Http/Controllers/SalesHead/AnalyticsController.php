<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Analytics/Index');
    }

    public function conversion()
    {
        return Inertia::render('SalesHead/Analytics/Conversion');
    }

    public function revenue()
    {
        return Inertia::render('SalesHead/Analytics/Revenue');
    }
}
