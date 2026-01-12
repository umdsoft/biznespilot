<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $expenses = [
            [
                'id' => 1,
                'description' => 'Ofis ijarasi - Yanvar',
                'category' => 'rent',
                'amount' => 3500000,
                'date' => '2026-01-05',
                'vendor' => 'ABC Realty',
                'status' => 'paid',
                'receipt' => true,
            ],
            [
                'id' => 2,
                'description' => 'Google Ads - Yanvar',
                'category' => 'marketing',
                'amount' => 1500000,
                'date' => '2026-01-10',
                'vendor' => 'Google LLC',
                'status' => 'paid',
                'receipt' => true,
            ],
            [
                'id' => 3,
                'description' => 'Elektr energiya',
                'category' => 'utilities',
                'amount' => 450000,
                'date' => '2026-01-08',
                'vendor' => 'Toshkent Elektr',
                'status' => 'pending',
                'receipt' => false,
            ],
            [
                'id' => 4,
                'description' => 'Server hosting',
                'category' => 'technology',
                'amount' => 800000,
                'date' => '2026-01-01',
                'vendor' => 'DigitalOcean',
                'status' => 'paid',
                'receipt' => true,
            ],
            [
                'id' => 5,
                'description' => 'Xodim oylik maoshi',
                'category' => 'salary',
                'amount' => 8500000,
                'date' => '2026-01-05',
                'vendor' => 'Payroll',
                'status' => 'paid',
                'receipt' => true,
            ],
        ];

        $stats = [
            'total' => 14750000,
            'this_month' => 14750000,
            'by_category' => [
                ['category' => 'salary', 'amount' => 8500000, 'percentage' => 57.6],
                ['category' => 'rent', 'amount' => 3500000, 'percentage' => 23.7],
                ['category' => 'marketing', 'amount' => 1500000, 'percentage' => 10.2],
                ['category' => 'technology', 'amount' => 800000, 'percentage' => 5.4],
                ['category' => 'utilities', 'amount' => 450000, 'percentage' => 3.1],
            ],
        ];

        $categories = [
            ['value' => 'salary', 'label' => 'Ish haqi'],
            ['value' => 'rent', 'label' => 'Ijara'],
            ['value' => 'utilities', 'label' => 'Kommunal'],
            ['value' => 'marketing', 'label' => 'Marketing'],
            ['value' => 'technology', 'label' => 'Texnologiya'],
            ['value' => 'office', 'label' => 'Ofis xarajatlari'],
            ['value' => 'travel', 'label' => 'Safar xarajatlari'],
            ['value' => 'other', 'label' => 'Boshqa'],
        ];

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $request->only(['category', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $categories = [
            ['value' => 'salary', 'label' => 'Ish haqi'],
            ['value' => 'rent', 'label' => 'Ijara'],
            ['value' => 'utilities', 'label' => 'Kommunal'],
            ['value' => 'marketing', 'label' => 'Marketing'],
            ['value' => 'technology', 'label' => 'Texnologiya'],
            ['value' => 'office', 'label' => 'Ofis xarajatlari'],
            ['value' => 'travel', 'label' => 'Safar xarajatlari'],
            ['value' => 'other', 'label' => 'Boshqa'],
        ];

        return Inertia::render('Finance/Expenses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        return redirect()->route('finance.expenses.index')
            ->with('success', 'Xarajat qo\'shildi');
    }

    public function show($id)
    {
        $expense = [
            'id' => $id,
            'description' => 'Ofis ijarasi - Yanvar',
            'category' => 'rent',
            'amount' => 3500000,
            'date' => '2026-01-05',
            'vendor' => 'ABC Realty',
            'status' => 'paid',
            'notes' => 'Oylik ijara to\'lovi',
            'receipt_url' => null,
            'created_by' => 'Admin User',
            'created_at' => '2026-01-05 10:30:00',
        ];

        return Inertia::render('Finance/Expenses/Show', [
            'expense' => $expense,
        ]);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('finance.expenses.index')
            ->with('success', 'Xarajat yangilandi');
    }

    public function destroy($id)
    {
        return redirect()->route('finance.expenses.index')
            ->with('success', 'Xarajat o\'chirildi');
    }

    public function approve($id)
    {
        return redirect()->route('finance.expenses.index')
            ->with('success', 'Xarajat tasdiqlandi');
    }
}
