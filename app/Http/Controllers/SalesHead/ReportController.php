<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Reports/Index');
    }

    public function daily()
    {
        return Inertia::render('SalesHead/Reports/Daily');
    }

    public function weekly()
    {
        return Inertia::render('SalesHead/Reports/Weekly');
    }

    public function monthly()
    {
        return Inertia::render('SalesHead/Reports/Monthly');
    }

    public function export(Request $request)
    {
        // TODO: Implement export
        return back()->with('success', 'Hisobot eksport qilindi');
    }
}
