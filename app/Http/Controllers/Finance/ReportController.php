<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $reports = [
            [
                'id' => 'profit-loss',
                'name' => 'Foyda va Zarar',
                'description' => 'Daromad va xarajatlar tahlili',
                'icon' => 'chart-bar',
                'period' => 'Oylik/Yillik',
            ],
            [
                'id' => 'balance-sheet',
                'name' => 'Balans',
                'description' => 'Aktivlar va passivlar holati',
                'icon' => 'scale',
                'period' => 'Sana bo\'yicha',
            ],
            [
                'id' => 'cash-flow',
                'name' => 'Pul Oqimi',
                'description' => 'Kiruvchi va chiquvchi pul oqimlari',
                'icon' => 'currency-dollar',
                'period' => 'Oylik',
            ],
            [
                'id' => 'accounts-receivable',
                'name' => 'Debitorlik Qarzi',
                'description' => 'Mijozlardan olinadigan to\'lovlar',
                'icon' => 'users',
                'period' => 'Joriy holat',
            ],
            [
                'id' => 'accounts-payable',
                'name' => 'Kreditorlik Qarzi',
                'description' => 'To\'lanishi kerak bo\'lgan qarzlar',
                'icon' => 'building-office',
                'period' => 'Joriy holat',
            ],
            [
                'id' => 'expense-summary',
                'name' => 'Xarajatlar Hisoboti',
                'description' => 'Kategoriyalar bo\'yicha xarajatlar',
                'icon' => 'receipt-percent',
                'period' => 'Oylik/Yillik',
            ],
        ];

        return Inertia::render('Finance/Reports/Index', [
            'reports' => $reports,
        ]);
    }

    public function profitLoss(Request $request)
    {
        $period = $request->get('period', 'month');

        $data = [
            'revenue' => [
                ['category' => 'Mahsulot sotish', 'amount' => 85000000],
                ['category' => 'Xizmatlar', 'amount' => 32000000],
                ['category' => 'Boshqa daromadlar', 'amount' => 8000000],
            ],
            'expenses' => [
                ['category' => 'Ish haqi', 'amount' => 45000000],
                ['category' => 'Ijara', 'amount' => 12000000],
                ['category' => 'Marketing', 'amount' => 8500000],
                ['category' => 'Kommunal', 'amount' => 3200000],
                ['category' => 'Boshqa xarajatlar', 'amount' => 6300000],
            ],
            'summary' => [
                'total_revenue' => 125000000,
                'total_expenses' => 75000000,
                'gross_profit' => 50000000,
                'net_profit' => 50000000,
                'profit_margin' => 40.0,
            ],
        ];

        return Inertia::render('Finance/Reports/ProfitLoss', [
            'data' => $data,
            'period' => $period,
        ]);
    }

    public function cashFlow(Request $request)
    {
        $data = [
            'operating' => [
                'inflow' => 32000000,
                'outflow' => 21500000,
                'net' => 10500000,
            ],
            'investing' => [
                'inflow' => 0,
                'outflow' => 5000000,
                'net' => -5000000,
            ],
            'financing' => [
                'inflow' => 10000000,
                'outflow' => 2000000,
                'net' => 8000000,
            ],
            'summary' => [
                'opening_balance' => 31500000,
                'net_change' => 13500000,
                'closing_balance' => 45000000,
            ],
            'monthly_trend' => [
                ['month' => 'Sentyabr', 'inflow' => 28000000, 'outflow' => 22000000],
                ['month' => 'Oktyabr', 'inflow' => 30000000, 'outflow' => 24000000],
                ['month' => 'Noyabr', 'inflow' => 35000000, 'outflow' => 26000000],
                ['month' => 'Dekabr', 'inflow' => 42000000, 'outflow' => 28000000],
                ['month' => 'Yanvar', 'inflow' => 32000000, 'outflow' => 21500000],
            ],
        ];

        return Inertia::render('Finance/Reports/CashFlow', [
            'data' => $data,
        ]);
    }

    public function accountsReceivable()
    {
        $data = [
            'summary' => [
                'total' => 32500000,
                'current' => 15000000,
                'overdue_1_30' => 8500000,
                'overdue_31_60' => 5000000,
                'overdue_60_plus' => 4000000,
            ],
            'clients' => [
                ['name' => 'ABC Company', 'amount' => 8500000, 'days_overdue' => 0],
                ['name' => 'XYZ Corp', 'amount' => 4200000, 'days_overdue' => 5],
                ['name' => 'Tech Solutions', 'amount' => 6800000, 'days_overdue' => 0],
                ['name' => 'Global Trade', 'amount' => 5500000, 'days_overdue' => 35],
                ['name' => 'Local Shop', 'amount' => 4000000, 'days_overdue' => 72],
                ['name' => 'City Services', 'amount' => 3500000, 'days_overdue' => 0],
            ],
        ];

        return Inertia::render('Finance/Reports/AccountsReceivable', [
            'data' => $data,
        ]);
    }

    public function expenseSummary(Request $request)
    {
        $data = [
            'by_category' => [
                ['category' => 'Ish haqi', 'amount' => 45000000, 'percentage' => 60.0, 'trend' => 5.2],
                ['category' => 'Ijara', 'amount' => 12000000, 'percentage' => 16.0, 'trend' => 0],
                ['category' => 'Marketing', 'amount' => 8500000, 'percentage' => 11.3, 'trend' => 12.5],
                ['category' => 'Kommunal', 'amount' => 3200000, 'percentage' => 4.3, 'trend' => -3.2],
                ['category' => 'Boshqa', 'amount' => 6300000, 'percentage' => 8.4, 'trend' => 8.1],
            ],
            'monthly_comparison' => [
                ['month' => 'Oktyabr', 'amount' => 68000000],
                ['month' => 'Noyabr', 'amount' => 72000000],
                ['month' => 'Dekabr', 'amount' => 78000000],
                ['month' => 'Yanvar', 'amount' => 75000000],
            ],
            'summary' => [
                'total' => 75000000,
                'average_monthly' => 73250000,
                'highest_category' => 'Ish haqi',
            ],
        ];

        return Inertia::render('Finance/Reports/ExpenseSummary', [
            'data' => $data,
        ]);
    }

    public function export(Request $request)
    {
        $reportType = $request->get('type');
        $format = $request->get('format', 'pdf');

        // Export logic here

        return response()->json(['message' => 'Hisobot yuklab olish tayyor']);
    }
}
