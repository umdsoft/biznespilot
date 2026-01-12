<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $invoices = [
            [
                'id' => 1,
                'number' => 'INV-2026-001',
                'client' => 'ABC Company',
                'client_email' => 'info@abc.com',
                'amount' => 8500000,
                'paid_amount' => 0,
                'issue_date' => '2026-01-05',
                'due_date' => '2026-01-20',
                'status' => 'pending',
                'items_count' => 3,
            ],
            [
                'id' => 2,
                'number' => 'INV-2026-002',
                'client' => 'XYZ Corp',
                'client_email' => 'finance@xyz.com',
                'amount' => 4200000,
                'paid_amount' => 0,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-10',
                'status' => 'overdue',
                'items_count' => 2,
            ],
            [
                'id' => 3,
                'number' => 'INV-2025-156',
                'client' => 'Tech Solutions',
                'client_email' => 'billing@tech.com',
                'amount' => 12500000,
                'paid_amount' => 12500000,
                'issue_date' => '2025-12-15',
                'due_date' => '2025-12-30',
                'status' => 'paid',
                'items_count' => 5,
            ],
            [
                'id' => 4,
                'number' => 'INV-2026-003',
                'client' => 'Global Trade',
                'client_email' => 'accounts@global.com',
                'amount' => 6800000,
                'paid_amount' => 3400000,
                'issue_date' => '2026-01-08',
                'due_date' => '2026-01-22',
                'status' => 'partial',
                'items_count' => 4,
            ],
        ];

        $stats = [
            'total' => count($invoices),
            'pending' => 1,
            'overdue' => 1,
            'paid' => 1,
            'partial' => 1,
            'total_amount' => 32000000,
            'received_amount' => 15900000,
            'pending_amount' => 16100000,
        ];

        return Inertia::render('Finance/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Finance/Invoices/Create', [
            'clients' => [
                ['id' => 1, 'name' => 'ABC Company', 'email' => 'info@abc.com'],
                ['id' => 2, 'name' => 'XYZ Corp', 'email' => 'finance@xyz.com'],
                ['id' => 3, 'name' => 'Tech Solutions', 'email' => 'billing@tech.com'],
            ],
            'taxRates' => [
                ['value' => 0, 'label' => '0%'],
                ['value' => 12, 'label' => '12% QQS'],
                ['value' => 15, 'label' => '15% Soliq'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        return redirect()->route('finance.invoices.index')
            ->with('success', 'Hisob-faktura yaratildi');
    }

    public function show($id)
    {
        $invoice = [
            'id' => $id,
            'number' => 'INV-2026-001',
            'client' => [
                'name' => 'ABC Company',
                'email' => 'info@abc.com',
                'phone' => '+998 90 123 45 67',
                'address' => 'Toshkent sh., Chilonzor tumani',
            ],
            'amount' => 8500000,
            'paid_amount' => 0,
            'issue_date' => '2026-01-05',
            'due_date' => '2026-01-20',
            'status' => 'pending',
            'items' => [
                ['description' => 'Web sayt ishlab chiqish', 'quantity' => 1, 'unit_price' => 5000000, 'total' => 5000000],
                ['description' => 'Hosting (1 yil)', 'quantity' => 1, 'unit_price' => 1500000, 'total' => 1500000],
                ['description' => 'Domen (1 yil)', 'quantity' => 1, 'unit_price' => 200000, 'total' => 200000],
                ['description' => 'SSL sertifikat', 'quantity' => 1, 'unit_price' => 500000, 'total' => 500000],
                ['description' => 'Texnik qo\'llab-quvvatlash', 'quantity' => 1, 'unit_price' => 1300000, 'total' => 1300000],
            ],
            'subtotal' => 8500000,
            'tax' => 0,
            'total' => 8500000,
            'notes' => 'To\'lov muddati 15 kun',
            'payments' => [],
        ];

        return Inertia::render('Finance/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('finance.invoices.show', $id)
            ->with('success', 'Hisob-faktura yangilandi');
    }

    public function destroy($id)
    {
        return redirect()->route('finance.invoices.index')
            ->with('success', 'Hisob-faktura o\'chirildi');
    }

    public function sendReminder($id)
    {
        return redirect()->route('finance.invoices.show', $id)
            ->with('success', 'Eslatma yuborildi');
    }

    public function recordPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        return redirect()->route('finance.invoices.show', $id)
            ->with('success', 'To\'lov qayd etildi');
    }

    public function downloadPdf($id)
    {
        // PDF generation logic
        return response()->json(['message' => 'PDF yuklab olish']);
    }
}
