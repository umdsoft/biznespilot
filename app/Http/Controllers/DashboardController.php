<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\MarketingChannel;
use App\Models\Offer;
use App\Models\Sale;
use App\Models\AiInsight;
use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\DashboardWidget;
use App\Services\KPICalculator;
use App\Services\KPIPlanCalculator;
use App\Services\SalesAnalyticsService;
use App\Services\DashboardService;
use App\Models\KpiPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $kpiCalculator;
    protected $analyticsService;
    protected $dashboardService;

    public function __construct(
        KPICalculator $kpiCalculator,
        SalesAnalyticsService $analyticsService,
        DashboardService $dashboardService
    ) {
        $this->kpiCalculator = $kpiCalculator;
        $this->analyticsService = $analyticsService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dashboard index - OPTIMIZED for fast initial load
     * Heavy data is loaded via API after page mounts
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.business.create');
        }

        // OPTIMIZATION: Only load cached/lightweight data on initial render
        // Heavy calculations moved to API endpoints

        // Quick stats for modules (cached for 5 minutes) - lightweight
        $moduleStats = Cache::remember(
            "dashboard_module_stats_{$currentBusiness->id}",
            300,
            fn() => [
                'dream_buyers' => DreamBuyer::where('business_id', $currentBusiness->id)->count(),
                'marketing_channels' => MarketingChannel::where('business_id', $currentBusiness->id)
                    ->where('is_active', true)
                    ->count(),
                'active_offers' => Offer::where('business_id', $currentBusiness->id)
                    ->where('status', 'active')
                    ->count(),
            ]
        );

        // Active alerts count - lightweight query
        $activeAlertsCount = Cache::remember(
            "dashboard_alerts_count_{$currentBusiness->id}",
            60,
            fn() => Alert::where('business_id', $currentBusiness->id)
                ->active()
                ->unresolved()
                ->notSnoozed()
                ->count()
        );

        return Inertia::render('Business/Dashboard', [
            // LAZY LOAD flags - frontend will fetch via API
            'lazyLoad' => true,
            'stats' => null,
            'kpis' => null,
            'roasBenchmark' => null,
            'ltvCacBenchmark' => null,
            'salesTrend' => null,
            'revenueForecast' => null,
            'aiInsights' => null,
            'recentActivities' => null,
            // Lightweight cached data - loaded immediately
            'moduleStats' => $moduleStats,
            'activeAlertsCount' => $activeAlertsCount,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }

    /**
     * Get initial dashboard data via API (for lazy loading)
     */
    public function getInitialData(Request $request)
    {
        $business = $this->getCurrentBusiness();

        // Date range (default: last 30 days)
        $endDate = now();
        $startDate = now()->subDays(30);

        // Get KPIs with caching
        $cacheKey = "dashboard_kpis_{$business->id}";
        $kpis = Cache::remember($cacheKey, 300, fn() => $this->kpiCalculator->getAllKPIs(
            $business->id,
            $startDate,
            $endDate
        ));

        // Get basic stats with caching
        $statsCacheKey = "dashboard_stats_{$business->id}";
        $stats = Cache::remember($statsCacheKey, 300, fn() => [
            'total_leads' => Sale::where('business_id', $business->id)->count(),
            'total_customers' => $this->kpiCalculator->getTotalCustomers($business->id),
            'total_revenue' => $this->kpiCalculator->getTotalRevenue($business->id, $startDate, $endDate),
            'conversion_rate' => $this->kpiCalculator->getConversionRate($business->id, $startDate, $endDate),
        ]);

        // Get ROAS and LTV/CAC benchmarks
        $roasBenchmark = $this->kpiCalculator->getROASBenchmark($kpis['roas']);
        $ltvCacBenchmark = $this->kpiCalculator->getLTVCACBenchmark($kpis['ltv_cac_ratio']);

        // Sales trend (last 7 days) - cached
        $trendCacheKey = "dashboard_sales_trend_{$business->id}";
        $salesTrend = Cache::remember($trendCacheKey, 300, fn() => Sale::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => $item->count,
                'revenue' => (float) $item->revenue,
            ])
        );

        // Revenue Forecast - cached
        $forecastCacheKey = "dashboard_forecast_{$business->id}";
        $revenueForecast = Cache::remember($forecastCacheKey, 600, fn() =>
            $this->analyticsService->forecastRevenue($business->id, 7)
        );

        // AI Insights - cached
        $insightsCacheKey = "dashboard_insights_{$business->id}";
        $aiInsights = Cache::remember($insightsCacheKey, 300, fn() =>
            AiInsight::where('business_id', $business->id)
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(fn($insight) => [
                    'id' => $insight->id,
                    'type' => $insight->type,
                    'title' => $insight->title,
                    'summary' => $insight->content ?? $insight->description_uz ?? '',
                    'priority' => $insight->priority,
                    'created_at' => $insight->created_at->diffForHumans(),
                ])
        );

        // Recent Activities - cached
        $activitiesCacheKey = "dashboard_activities_{$business->id}";
        $recentActivities = Cache::remember($activitiesCacheKey, 300, fn() =>
            ActivityLog::with('user:id,name')
                ->where('business_id', $business->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($activity) => [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'type' => $activity->type,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'user_name' => $activity->user->name ?? 'System',
                ])
        );

        return response()->json([
            'stats' => $stats,
            'kpis' => $kpis,
            'roasBenchmark' => $roasBenchmark,
            'ltvCacBenchmark' => $ltvCacBenchmark,
            'salesTrend' => $salesTrend,
            'revenueForecast' => $revenueForecast,
            'aiInsights' => $aiInsights,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Get dashboard data via API
     */
    public function getData(Request $request)
    {
        $business = $this->getCurrentBusiness();

        return response()->json([
            'data' => $this->dashboardService->getDashboardData($business),
        ]);
    }

    /**
     * Get KPIs via API
     */
    public function getKPIs(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $todaySnapshot = $this->dashboardService->getTodaySnapshot($business);
        $yesterdaySnapshot = $this->dashboardService->getSnapshot($business, now()->subDay());
        $weekAgoSnapshot = $this->dashboardService->getSnapshot($business, now()->subWeek());

        return response()->json([
            'kpis' => $this->dashboardService->getKPISummary($business, $todaySnapshot, $yesterdaySnapshot, $weekAgoSnapshot),
            'health_score' => $todaySnapshot?->health_score ?? 0,
        ]);
    }

    /**
     * Get trends data via API
     */
    public function getTrends(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $metric = $request->get('metric', 'revenue_total');
        $days = $request->get('days', 30);

        return response()->json([
            'trends' => $this->dashboardService->getTrendData($business, $metric, $days),
        ]);
    }

    /**
     * Get funnel data via API
     */
    public function getFunnel(Request $request)
    {
        $business = $this->getCurrentBusiness();

        return response()->json([
            'funnel' => $this->dashboardService->getFunnelData($business),
        ]);
    }

    /**
     * Get channel comparison via API
     */
    public function getChannelComparison(Request $request)
    {
        $business = $this->getCurrentBusiness();

        return response()->json([
            'channels' => $this->dashboardService->getChannelComparison($business),
        ]);
    }

    /**
     * Update dashboard widgets
     */
    public function updateWidgets(Request $request)
    {
        $request->validate([
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|string',
            'widgets.*.sort_order' => 'required|integer',
            'widgets.*.is_visible' => 'required|boolean',
            'widgets.*.settings' => 'nullable|array',
        ]);

        $business = $this->getCurrentBusiness();
        $user = Auth::user();

        foreach ($request->widgets as $widgetData) {
            DashboardWidget::where('id', $widgetData['id'])
                ->where('business_id', $business->id)
                ->where('user_id', $user->id)
                ->update([
                    'sort_order' => $widgetData['sort_order'],
                    'is_visible' => $widgetData['is_visible'],
                    'settings' => $widgetData['settings'] ?? [],
                ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Refresh dashboard data
     */
    public function refresh(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $this->dashboardService->refreshDashboard($business);

        return response()->json([
            'data' => $this->dashboardService->getDashboardData($business),
        ]);
    }

    /**
     * Display the KPI page
     */
    public function kpi(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.business.create');
        }

        // Date range (default: last 30 days)
        $endDate = now();
        $startDate = now()->subDays(30);

        // Get KPIs
        $kpis = $this->kpiCalculator->getAllKPIs(
            $currentBusiness->id,
            $startDate,
            $endDate
        );

        // Get ROAS and LTV/CAC benchmarks
        $roasBenchmark = $this->kpiCalculator->getROASBenchmark($kpis['roas']);
        $ltvCacBenchmark = $this->kpiCalculator->getLTVCACBenchmark($kpis['ltv_cac_ratio']);

        // Get active KPI plan for current/next month
        $calculator = app(KPIPlanCalculator::class);
        $targetMonth = $calculator->determineTargetMonth();

        $activePlan = KpiPlan::where('business_id', $currentBusiness->id)
            ->where('year', $targetMonth['year'])
            ->where('month', $targetMonth['month'])
            ->where('status', 'active')
            ->first();

        // Get all KPI plans for this business
        $kpiPlans = KpiPlan::where('business_id', $currentBusiness->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Month names in Uzbek
        $monthNames = [
            1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
            5 => 'May', 6 => 'Iyun', 7 => 'Iyul', 8 => 'Avgust',
            9 => 'Sentabr', 10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr',
        ];

        // Get daily entries for current month + last week of previous month (for weekly view edge cases)
        $rangeStart = now()->startOfMonth()->subDays(7);
        $rangeEnd = now()->endOfMonth();

        $dailyEntries = \App\Models\KpiDailyEntry::where('business_id', $currentBusiness->id)
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->orderBy('date')
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        return Inertia::render('Business/KPI/Index', [
            'kpis' => $kpis,
            'roasBenchmark' => $roasBenchmark,
            'ltvCacBenchmark' => $ltvCacBenchmark,
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'businessRegistrationDate' => $currentBusiness->created_at->format('Y-m-d'),
            'activePlan' => $activePlan,
            'kpiPlans' => $kpiPlans,
            'dailyEntries' => $dailyEntries,
            'targetMonth' => [
                'year' => $targetMonth['year'],
                'month' => $targetMonth['month'],
                'month_name' => $monthNames[$targetMonth['month']],
                'start_date' => $targetMonth['start_date'],
                'end_date' => $targetMonth['end_date'],
                'working_days' => $targetMonth['working_days'],
            ],
        ]);
    }

    /**
     * KPI Data Entry page
     */
    public function kpiDataEntry(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.business.create');
        }

        return Inertia::render('Business/KPI/DataEntry', [
            'business' => $currentBusiness,
        ]);
    }

    /**
     * Calculate KPI plan based on new sales and average check
     */
    public function calculateKPIPlan(Request $request)
    {
        try {
            $request->validate([
                'new_sales' => 'required|integer|min:1',
                'avg_check' => 'required|numeric|min:0',
            ]);

            $business = $this->getCurrentBusiness();

            // Use KPIPlanCalculator service
            $calculator = app(\App\Services\KPIPlanCalculator::class);
            $plan = $calculator->calculateNextMonthPlan(
                $business,
                $request->new_sales,
                $request->avg_check
            );

            return response()->json([
                'plan' => $plan,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validatsiya xatoligi',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Hisoblashda xatolik yuz berdi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save KPI plan for target month
     */
    public function saveKPIPlan(Request $request)
    {
        $request->validate([
            'new_sales' => 'required|integer|min:1',
            'avg_check' => 'required|numeric|min:0',
            'leads' => 'nullable|integer|min:1',
            'lead_cost' => 'nullable|numeric|min:0',
        ]);

        $business = $this->getCurrentBusiness();
        $calculator = app(KPIPlanCalculator::class);

        // Create full plan with date calculations and breakdowns
        $fullPlan = $calculator->createFullPlan(
            $business,
            $request->new_sales,
            $request->avg_check,
            $request->leads,
            $request->lead_cost
        );

        // Check if plan already exists for this month
        $existingPlan = KpiPlan::where('business_id', $business->id)
            ->where('year', $fullPlan['year'])
            ->where('month', $fullPlan['month'])
            ->first();

        if ($existingPlan) {
            // Update existing plan
            $existingPlan->update([
                'start_date' => $fullPlan['start_date'],
                'end_date' => $fullPlan['end_date'],
                'working_days' => $fullPlan['working_days'],
                'new_sales' => $fullPlan['metrics']['new_sales'],
                'avg_check' => $fullPlan['metrics']['avg_check'],
                'repeat_sales' => $fullPlan['metrics']['repeat_sales'],
                'total_customers' => $fullPlan['metrics']['total_customers'],
                'total_revenue' => $fullPlan['metrics']['total_revenue'],
                'ad_costs' => $fullPlan['metrics']['ad_costs'],
                'gross_margin' => $fullPlan['metrics']['gross_margin'],
                'gross_margin_percent' => $fullPlan['metrics']['gross_margin_percent'],
                'roi' => $fullPlan['metrics']['roi'],
                'roas' => $fullPlan['metrics']['roas'],
                'cac' => $fullPlan['metrics']['cac'],
                'clv' => $fullPlan['metrics']['clv'],
                'ltv_cac_ratio' => $fullPlan['metrics']['ltv_cac_ratio'],
                'total_leads' => $fullPlan['metrics']['total_leads'],
                'lead_cost' => $fullPlan['metrics']['lead_cost'],
                'conversion_rate' => $fullPlan['metrics']['conversion_rate'],
                'ctr' => $fullPlan['metrics']['ctr'],
                'churn_rate' => $fullPlan['metrics']['churn_rate'],
                'daily_breakdown' => $fullPlan['daily'],
                'weekly_breakdown' => $fullPlan['weekly'],
                'calculation_method' => $fullPlan['metrics']['calculation_method'],
            ]);
            $kpiPlan = $existingPlan;
        } else {
            // Create new plan
            $kpiPlan = KpiPlan::create([
                'business_id' => $business->id,
                'year' => $fullPlan['year'],
                'month' => $fullPlan['month'],
                'start_date' => $fullPlan['start_date'],
                'end_date' => $fullPlan['end_date'],
                'working_days' => $fullPlan['working_days'],
                'new_sales' => $fullPlan['metrics']['new_sales'],
                'avg_check' => $fullPlan['metrics']['avg_check'],
                'repeat_sales' => $fullPlan['metrics']['repeat_sales'],
                'total_customers' => $fullPlan['metrics']['total_customers'],
                'total_revenue' => $fullPlan['metrics']['total_revenue'],
                'ad_costs' => $fullPlan['metrics']['ad_costs'],
                'gross_margin' => $fullPlan['metrics']['gross_margin'],
                'gross_margin_percent' => $fullPlan['metrics']['gross_margin_percent'],
                'roi' => $fullPlan['metrics']['roi'],
                'roas' => $fullPlan['metrics']['roas'],
                'cac' => $fullPlan['metrics']['cac'],
                'clv' => $fullPlan['metrics']['clv'],
                'ltv_cac_ratio' => $fullPlan['metrics']['ltv_cac_ratio'],
                'total_leads' => $fullPlan['metrics']['total_leads'],
                'lead_cost' => $fullPlan['metrics']['lead_cost'],
                'conversion_rate' => $fullPlan['metrics']['conversion_rate'],
                'ctr' => $fullPlan['metrics']['ctr'],
                'churn_rate' => $fullPlan['metrics']['churn_rate'],
                'daily_breakdown' => $fullPlan['daily'],
                'weekly_breakdown' => $fullPlan['weekly'],
                'calculation_method' => $fullPlan['metrics']['calculation_method'],
                'status' => 'active',
            ]);
        }

        return redirect()->route('business.kpi')->with('success', 'KPI rejasi muvaffaqiyatli saqlandi!');
    }

    /**
     * Get current business helper
     */
    protected function getCurrentBusiness(): Business
    {
        $user = Auth::user();
        $business = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$business) {
            abort(400, 'Biznes tanlanmagan. Iltimos, avval biznes yarating yoki tanlang.');
        }

        return $business;
    }
}
