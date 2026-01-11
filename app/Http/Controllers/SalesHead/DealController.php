<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DealController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Deals/Index');
    }

    public function show($deal)
    {
        return Inertia::render('SalesHead/Deals/Show');
    }
}
