<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $budgets = [
            [
                'id' => 1,
                'category' => 'Marketing',
                'allocated' => 10000000,
                'spent' => 6500000,
                'remaining' => 3500000,
                'percentage' => 65,
                'status' => 'on_track',
            ],
            [
                'id' => 2,
                'category' => 'Ish haqi',
                'allocated' => 50000000,
                'spent' => 45000000,
                'remaining' => 5000000,
                'percentage' => 90,
                'status' => 'warning',
            ],
            [
                'id' => 3,
                'category' => 'Ofis xarajatlari',
                'allocated' => 5000000,
                'spent' => 3200000,
                'remaining' => 1800000,
                'percentage' => 64,
                'status' => 'on_track',
            ],
            [
                'id' => 4,
                'category' => 'Texnologiya',
                'allocated' => 8000000,
                'spent' => 8500000,
                'remaining' => -500000,
                'percentage' => 106,
                'status' => 'over_budget',
            ],
            [
                'id' => 5,
                'category' => 'Safar xarajatlari',
                'allocated' => 3000000,
                'spent' => 1200000,
                'remaining' => 1800000,
                'percentage' => 40,
                'status' => 'on_track',
            ],
        ];

        $summary = [
            'total_allocated' => 76000000,
            'total_spent' => 64400000,
            'total_remaining' => 11600000,
            'overall_percentage' => 84.7,
            'on_track_count' => 3,
            'warning_count' => 1,
            'over_budget_count' => 1,
        ];

        return Inertia::render('Finance/Budget/Index', [
            'budgets' => $budgets,
            'summary' => $summary,
        ]);
    }

    public function create()
    {
        $categories = [
            ['value' => 'marketing', 'label' => 'Marketing'],
            ['value' => 'salary', 'label' => 'Ish haqi'],
            ['value' => 'office', 'label' => 'Ofis xarajatlari'],
            ['value' => 'technology', 'label' => 'Texnologiya'],
            ['value' => 'travel', 'label' => 'Safar xarajatlari'],
            ['value' => 'utilities', 'label' => 'Kommunal'],
            ['value' => 'other', 'label' => 'Boshqa'],
        ];

        return Inertia::render('Finance/Budget/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'allocated' => 'required|numeric|min:0',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return redirect()->route('finance.budget.index')
            ->with('success', 'Byudjet yaratildi');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'allocated' => 'required|numeric|min:0',
        ]);

        return redirect()->route('finance.budget.index')
            ->with('success', 'Byudjet yangilandi');
    }

    public function destroy($id)
    {
        return redirect()->route('finance.budget.index')
            ->with('success', 'Byudjet o\'chirildi');
    }
}
