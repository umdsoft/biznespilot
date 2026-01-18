<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CallController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Calls/Index');
    }

    public function show($call)
    {
        return Inertia::render('SalesHead/Calls/Show');
    }
}
