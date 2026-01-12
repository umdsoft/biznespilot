<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        $stats = [
            'revenue' => [
                'total' => 125000000,
                'this_month' => 28500000,
                'growth' => 12.5,
            ],
            'expenses' => [
                'total' => 85000000,
                'this_month' => 18200000,
                'growth' => 8.3,
            ],
            'profit' => [
                'total' => 40000000,
                'this_month' => 10300000,
                'margin' => 32.0,
            ],
            'invoices' => [
                'total' => 156,
                'pending' => 23,
                'overdue' => 5,
                'amount_pending' => 15800000,
            ],
            'cashflow' => [
                'balance' => 45000000,
                'inflow' => 32000000,
                'outflow' => 21500000,
            ],
        ];

        $recentTransactions = [
            ['id' => 1, 'type' => 'income', 'description' => 'Mijoz to\'lovi #1234', 'amount' => 5500000, 'date' => now()->format('Y-m-d')],
            ['id' => 2, 'type' => 'expense', 'description' => 'Ofis ijarasi', 'amount' => 3500000, 'date' => now()->subDays(1)->format('Y-m-d')],
            ['id' => 3, 'type' => 'income', 'description' => 'Mijoz to\'lovi #1235', 'amount' => 8200000, 'date' => now()->subDays(2)->format('Y-m-d')],
            ['id' => 4, 'type' => 'expense', 'description' => 'Reklama xarajati', 'amount' => 1500000, 'date' => now()->subDays(2)->format('Y-m-d')],
        ];

        $pendingInvoices = [
            ['id' => 1, 'number' => 'INV-2026-001', 'client' => 'ABC Company', 'amount' => 8500000, 'due_date' => now()->addDays(5)->format('Y-m-d'), 'status' => 'pending'],
            ['id' => 2, 'number' => 'INV-2026-002', 'client' => 'XYZ Corp', 'amount' => 4200000, 'due_date' => now()->subDays(2)->format('Y-m-d'), 'status' => 'overdue'],
            ['id' => 3, 'number' => 'INV-2026-003', 'client' => 'Tech Solutions', 'amount' => 3100000, 'due_date' => now()->addDays(10)->format('Y-m-d'), 'status' => 'pending'],
        ];

        return Inertia::render('Finance/Dashboard', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'pendingInvoices' => $pendingInvoices,
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }

    public function apiStats()
    {
        return response()->json([
            'tasks_count' => 5,
            'unread_count' => 2,
        ]);
    }
}
