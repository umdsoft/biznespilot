<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\KpiDailyEntry;
use App\Models\KpiPlan;
use App\Models\Lead;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KpiController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display sales KPI dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;
        $period = $request->get('period', 'daily');
        $selectedDay = $request->get('day', now()->day);

        // Get current month's plan
        $activePlan = KpiPlan::where('business_id', $businessId)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->where('status', 'active')
            ->first();

        // Get all plans for dropdown
        $kpiPlans = KpiPlan::where('business_id', $businessId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Get daily entries for current month
        $dailyEntries = KpiDailyEntry::where('business_id', $businessId)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($entry) => $entry->date->format('Y-m-d'));

        // Build KPI metrics based on period
        $kpiMetrics = $this->buildKpiMetrics($businessId, $activePlan, $dailyEntries, $period, $selectedDay);

        // Get team performance
        $teamKpi = $this->getTeamKpi($businessId, $period, $selectedDay);

        // Date info
        $dateRange = [
            'current_month' => now()->translatedFormat('F Y'),
            'current_day' => now()->day,
            'days_in_month' => now()->daysInMonth,
            'current_week' => now()->weekOfMonth,
            'selected_day' => (int) $selectedDay,
        ];

        return Inertia::render('SalesHead/KPI/Index', [
            'activePlan' => $activePlan,
            'kpiPlans' => $kpiPlans,
            'dailyEntries' => $dailyEntries,
            'kpiMetrics' => $kpiMetrics,
            'teamKpi' => $teamKpi,
            'period' => $period,
            'dateRange' => $dateRange,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Build KPI metrics based on period
     */
    private function buildKpiMetrics($businessId, $activePlan, $dailyEntries, $period, $selectedDay): array
    {
        // Calculate working days in month
        $workingDays = $this->getWorkingDaysInMonth();

        // Monthly targets from plan
        $monthlyTargets = [
            'sales' => $activePlan->new_sales ?? 50,
            'revenue' => $activePlan->total_revenue ?? 50000000,
            'conversion' => $activePlan->conversion_rate ?? 20,
            'leads' => $activePlan->total_leads ?? 250,
            'avg_check' => $activePlan->avg_check ?? 1000000,
        ];

        // Calculate daily targets (monthly / working days)
        $dailyTargets = [
            'sales' => round($monthlyTargets['sales'] / $workingDays, 1),
            'revenue' => round($monthlyTargets['revenue'] / $workingDays),
            'conversion' => $monthlyTargets['conversion'], // Same for daily
            'leads' => round($monthlyTargets['leads'] / $workingDays, 1),
            'avg_check' => $monthlyTargets['avg_check'], // Same for daily
        ];

        // Weekly targets (monthly / 4)
        $weeklyTargets = [
            'sales' => round($monthlyTargets['sales'] / 4, 1),
            'revenue' => round($monthlyTargets['revenue'] / 4),
            'conversion' => $monthlyTargets['conversion'],
            'leads' => round($monthlyTargets['leads'] / 4, 1),
            'avg_check' => $monthlyTargets['avg_check'],
        ];

        // Get actual values based on period
        if ($period === 'daily') {
            $actuals = $this->getDailyActuals($businessId, $dailyEntries, $selectedDay);
            $targets = $dailyTargets;
            $targetLabel = 'Kunlik';
        } elseif ($period === 'weekly') {
            $actuals = $this->getWeeklyActuals($businessId, $dailyEntries);
            $targets = $weeklyTargets;
            $targetLabel = 'Haftalik';
        } else {
            $actuals = $this->getMonthlyActuals($businessId, $dailyEntries);
            $targets = $monthlyTargets;
            $targetLabel = 'Oylik';
        }

        // Calculate achievements
        $salesAchievement = $targets['sales'] > 0 ? round(($actuals['sales'] / $targets['sales']) * 100) : 0;
        $revenueAchievement = $targets['revenue'] > 0 ? round(($actuals['revenue'] / $targets['revenue']) * 100) : 0;
        $conversionAchievement = $targets['conversion'] > 0 ? round(($actuals['conversion'] / $targets['conversion']) * 100) : 0;
        $leadsAchievement = $targets['leads'] > 0 ? round(($actuals['leads'] / $targets['leads']) * 100) : 0;
        $avgCheckAchievement = $targets['avg_check'] > 0 ? round(($actuals['avg_check'] / $targets['avg_check']) * 100) : 0;

        return [
            [
                'name' => 'Yangi Sotuvlar',
                'description' => $period === 'daily' ? 'Shu kungi yopilgan bitimlar' : ($period === 'weekly' ? 'Hafta davomida yopilgan bitimlar' : 'Oy davomida yopilgan bitimlar'),
                'category' => 'sotuv',
                'current' => $actuals['sales'],
                'plan' => $targets['sales'],
                'achievement' => min($salesAchievement, 150),
                'unit' => 'dona',
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />',
                'iconBg' => 'bg-teal-100 dark:bg-teal-900',
                'iconColor' => 'text-teal-600 dark:text-teal-400',
            ],
            [
                'name' => 'Daromad',
                'description' => $period === 'daily' ? 'Shu kungi sotuv daromadi' : ($period === 'weekly' ? 'Haftalik sotuv daromadi' : 'Oylik sotuv daromadi'),
                'category' => 'moliyaviy',
                'current' => $actuals['revenue'],
                'plan' => $targets['revenue'],
                'achievement' => min($revenueAchievement, 150),
                'unit' => 'som',
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'iconBg' => 'bg-green-100 dark:bg-green-900',
                'iconColor' => 'text-green-600 dark:text-green-400',
            ],
            [
                'name' => 'Konversiya',
                'description' => 'Liddan sotuvga o\'tish foizi',
                'category' => 'sotuv',
                'current' => round($actuals['conversion'], 1),
                'plan' => $targets['conversion'],
                'achievement' => min($conversionAchievement, 150),
                'unit' => 'foiz',
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />',
                'iconBg' => 'bg-blue-100 dark:bg-blue-900',
                'iconColor' => 'text-blue-600 dark:text-blue-400',
            ],
            [
                'name' => $period === 'daily' ? 'Bugungi Lidlar' : ($period === 'weekly' ? 'Haftalik Lidlar' : 'Jami Lidlar'),
                'description' => $period === 'daily' ? 'Shu kunda kelgan lidlar' : ($period === 'weekly' ? 'Hafta davomida kelgan lidlar' : 'Oy davomida kelgan lidlar'),
                'category' => 'sotuv',
                'current' => $actuals['leads'],
                'plan' => $targets['leads'],
                'achievement' => min($leadsAchievement, 150),
                'unit' => 'dona',
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                'iconBg' => 'bg-purple-100 dark:bg-purple-900',
                'iconColor' => 'text-purple-600 dark:text-purple-400',
            ],
            [
                'name' => 'O\'rtacha Chek',
                'description' => $period === 'daily' ? 'Shu kungi o\'rtacha chek' : ($period === 'weekly' ? 'Haftalik o\'rtacha chek' : 'Oylik o\'rtacha chek'),
                'category' => 'moliyaviy',
                'current' => $actuals['avg_check'],
                'plan' => $targets['avg_check'],
                'achievement' => min($avgCheckAchievement, 150),
                'unit' => 'som',
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />',
                'iconBg' => 'bg-emerald-100 dark:bg-emerald-900',
                'iconColor' => 'text-emerald-600 dark:text-emerald-400',
            ],
            [
                'name' => 'Bajarilgan Vazifalar',
                'description' => $period === 'daily' ? 'Shu kunda tugatilgan vazifalar' : ($period === 'weekly' ? 'Hafta davomida tugatilgan' : 'Oy davomida tugatilgan'),
                'category' => 'samaradorlik',
                'current' => $actuals['completed_tasks'],
                'plan' => $period === 'daily' ? 5 : ($period === 'weekly' ? 20 : 50),
                'achievement' => $actuals['completed_tasks'] >= ($period === 'daily' ? 5 : ($period === 'weekly' ? 20 : 50)) ? 100 : round(($actuals['completed_tasks'] / max(($period === 'daily' ? 5 : ($period === 'weekly' ? 20 : 50)), 1)) * 100),
                'unit' => 'dona',
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'iconBg' => 'bg-green-100 dark:bg-green-900',
                'iconColor' => 'text-green-600 dark:text-green-400',
            ],
            [
                'name' => 'Kechikkan Vazifalar',
                'description' => 'Muddati o\'tgan vazifalar',
                'category' => 'samaradorlik',
                'current' => $actuals['overdue_tasks'],
                'plan' => 0,
                'achievement' => $actuals['overdue_tasks'] == 0 ? 100 : max(0, 100 - ($actuals['overdue_tasks'] * 10)),
                'unit' => 'dona',
                'isNegative' => true,
                'targetLabel' => $targetLabel,
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'iconBg' => 'bg-red-100 dark:bg-red-900',
                'iconColor' => 'text-red-600 dark:text-red-400',
            ],
        ];
    }

    /**
     * Get daily actual values
     */
    private function getDailyActuals($businessId, $dailyEntries, $selectedDay): array
    {
        $selectedDate = Carbon::create(now()->year, now()->month, $selectedDay);
        $dateKey = $selectedDate->format('Y-m-d');

        // Check daily entries first
        $entry = $dailyEntries->get($dateKey);

        if ($entry) {
            $totalSales = $entry->sales_total ?? ($entry->sales_new + $entry->sales_repeat);
            $totalRevenue = $entry->revenue_total ?? ($entry->revenue_new + $entry->revenue_repeat);
            $totalLeads = $entry->leads_total ?? 0;
            $avgCheck = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
            $conversion = $totalLeads > 0 ? ($totalSales / $totalLeads) * 100 : 0;
        } else {
            // Fallback to leads table
            $totalLeads = Lead::where('business_id', $businessId)
                ->whereDate('created_at', $selectedDate)
                ->count();

            $totalSales = Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereDate('updated_at', $selectedDate)
                ->count();

            $totalRevenue = Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereDate('updated_at', $selectedDate)
                ->sum('estimated_value') ?? 0;

            $avgCheck = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
            $conversion = $totalLeads > 0 ? ($totalSales / $totalLeads) * 100 : 0;
        }

        // Tasks for selected day
        $completedTasks = Task::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('updated_at', $selectedDate)
            ->count();

        $overdueTasks = Task::where('business_id', $businessId)
            ->whereDate('due_date', $selectedDate)
            ->where('status', '!=', 'completed')
            ->count();

        return [
            'sales' => $totalSales,
            'revenue' => $totalRevenue,
            'leads' => $totalLeads,
            'avg_check' => $avgCheck,
            'conversion' => $conversion,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
        ];
    }

    /**
     * Get weekly actual values
     */
    private function getWeeklyActuals($businessId, $dailyEntries): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Sum from daily entries
        $weeklyEntries = $dailyEntries->filter(function ($entry) use ($startOfWeek, $endOfWeek) {
            return $entry->date->between($startOfWeek, $endOfWeek);
        });

        $totalSales = $weeklyEntries->sum('sales_total') ?: $weeklyEntries->sum('sales_new') + $weeklyEntries->sum('sales_repeat');
        $totalRevenue = $weeklyEntries->sum('revenue_total') ?: $weeklyEntries->sum('revenue_new') + $weeklyEntries->sum('revenue_repeat');
        $totalLeads = $weeklyEntries->sum('leads_total');

        // Fallback to leads table
        if ($totalLeads == 0 && $totalSales == 0) {
            $totalLeads = Lead::where('business_id', $businessId)
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->count();

            $totalSales = Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
                ->count();

            $totalRevenue = Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
                ->sum('estimated_value') ?? 0;
        }

        $avgCheck = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        $conversion = $totalLeads > 0 ? ($totalSales / $totalLeads) * 100 : 0;

        // Tasks
        $completedTasks = Task::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
            ->count();

        $overdueTasks = Task::where('business_id', $businessId)
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        return [
            'sales' => $totalSales,
            'revenue' => $totalRevenue,
            'leads' => $totalLeads,
            'avg_check' => $avgCheck,
            'conversion' => $conversion,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
        ];
    }

    /**
     * Get monthly actual values
     */
    private function getMonthlyActuals($businessId, $dailyEntries): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Sum from daily entries
        $totalSales = $dailyEntries->sum('sales_total') ?: $dailyEntries->sum('sales_new') + $dailyEntries->sum('sales_repeat');
        $totalRevenue = $dailyEntries->sum('revenue_total') ?: $dailyEntries->sum('revenue_new') + $dailyEntries->sum('revenue_repeat');
        $totalLeads = $dailyEntries->sum('leads_total');

        // Fallback to leads table
        if ($totalLeads == 0 && $totalSales == 0) {
            $totalLeads = Lead::where('business_id', $businessId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $totalSales = Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->count();

            $totalRevenue = Lead::where('business_id', $businessId)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->sum('estimated_value') ?? 0;
        }

        $avgCheck = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        $conversion = $totalLeads > 0 ? ($totalSales / $totalLeads) * 100 : 0;

        // Tasks
        $completedTasks = Task::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $overdueTasks = Task::where('business_id', $businessId)
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        return [
            'sales' => $totalSales,
            'revenue' => $totalRevenue,
            'leads' => $totalLeads,
            'avg_check' => $avgCheck,
            'conversion' => $conversion,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
        ];
    }

    /**
     * Get working days in current month
     */
    private function getWorkingDaysInMonth(): int
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $workingDays = 0;

        while ($start <= $end) {
            if ($start->isWeekday()) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays ?: 22; // Default 22 working days
    }

    /**
     * Get team KPI performance
     */
    private function getTeamKpi($businessId, $period, $selectedDay): array
    {
        if ($period === 'daily') {
            $selectedDate = Carbon::create(now()->year, now()->month, $selectedDay);
            $startDate = $selectedDate->copy()->startOfDay();
            $endDate = $selectedDate->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } else {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        }

        return DB::table('business_user')
            ->join('users', 'business_user.user_id', '=', 'users.id')
            ->leftJoin('leads', function ($join) use ($businessId, $startDate, $endDate) {
                $join->on('business_user.user_id', '=', 'leads.assigned_to')
                    ->where('leads.business_id', '=', $businessId)
                    ->whereBetween('leads.created_at', [$startDate, $endDate]);
            })
            ->where('business_user.business_id', $businessId)
            ->where('business_user.department', 'sales_operator')
            ->select(
                'business_user.user_id',
                'users.name',
                DB::raw('COUNT(DISTINCT leads.id) as leads_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN leads.status = "won" THEN leads.id END) as won_count'),
                DB::raw('COALESCE(SUM(CASE WHEN leads.status = "won" THEN leads.estimated_value ELSE 0 END), 0) as revenue')
            )
            ->groupBy('business_user.user_id', 'users.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $leadsCount = (int) $row->leads_count;
                $wonCount = (int) $row->won_count;

                return [
                    'name' => $row->name ?? 'Noma\'lum',
                    'avatar' => strtoupper(substr($row->name ?? 'N', 0, 1)),
                    'leads' => $leadsCount,
                    'won' => $wonCount,
                    'conversion' => $leadsCount > 0 ? round(($wonCount / $leadsCount) * 100, 0) : 0,
                    'revenue' => (float) $row->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Store new KPI plan
     */
    public function storePlan(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2024',
            'month' => 'required|integer|min:1|max:12',
            'new_sales' => 'required|integer|min:0',
            'avg_check' => 'required|numeric|min:0',
            'total_leads' => 'nullable|integer|min:0',
            'conversion_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Calculate derived values
        $newSales = $validated['new_sales'];
        $avgCheck = $validated['avg_check'];
        $totalRevenue = $newSales * $avgCheck;

        // Use provided or calculate defaults
        $totalLeads = $validated['total_leads'] ?? ceil($newSales / 0.2); // 20% conversion default
        $conversionRate = $validated['conversion_rate'] ?? ($totalLeads > 0 ? ($newSales / $totalLeads) * 100 : 20);

        // Create or update plan
        KpiPlan::updateOrCreate(
            [
                'business_id' => $business->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'new_sales' => $newSales,
                'avg_check' => $avgCheck,
                'total_revenue' => $totalRevenue,
                'total_leads' => $totalLeads,
                'conversion_rate' => $conversionRate,
                'repeat_sales' => 0,
                'total_customers' => $newSales,
                'status' => 'active',
                'calculation_method' => 'manual',
            ]
        );

        return back()->with('success', 'KPI rejasi saqlandi!');
    }

    /**
     * Store daily entry
     */
    public function storeDailyEntry(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'leads_total' => 'nullable|integer|min:0',
            'sales_new' => 'nullable|integer|min:0',
            'sales_repeat' => 'nullable|integer|min:0',
            'revenue_new' => 'nullable|numeric|min:0',
            'revenue_repeat' => 'nullable|numeric|min:0',
        ]);

        KpiDailyEntry::updateOrCreate(
            [
                'business_id' => $business->id,
                'date' => $validated['date'],
            ],
            [
                'leads_total' => $validated['leads_total'] ?? 0,
                'sales_new' => $validated['sales_new'] ?? 0,
                'sales_repeat' => $validated['sales_repeat'] ?? 0,
                'revenue_new' => $validated['revenue_new'] ?? 0,
                'revenue_repeat' => $validated['revenue_repeat'] ?? 0,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Kunlik ma\'lumotlar saqlandi!');
    }

    /**
     * Data entry page
     */
    public function dataEntry(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $date = $request->get('date', now()->format('Y-m-d'));

        $entry = KpiDailyEntry::where('business_id', $business->id)
            ->where('date', $date)
            ->first();

        return Inertia::render('SalesHead/KPI/DataEntry', [
            'date' => $date,
            'entry' => $entry,
            'panelType' => 'saleshead',
        ]);
    }
}
