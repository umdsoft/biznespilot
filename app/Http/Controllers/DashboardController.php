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
use App\Services\SalesAnalyticsService;
use App\Services\DashboardService;
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

    public function index(Request $request)
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

        // Get basic stats
        $stats = [
            'total_leads' => Sale::where('business_id', $currentBusiness->id)->count(),
            'total_customers' => $this->kpiCalculator->getTotalCustomers($currentBusiness->id),
            'total_revenue' => $this->kpiCalculator->getTotalRevenue(
                $currentBusiness->id,
                $startDate,
                $endDate
            ),
            'conversion_rate' => $this->kpiCalculator->getConversionRate(
                $currentBusiness->id,
                $startDate,
                $endDate
            ),
        ];

        // Get ROAS and LTV/CAC benchmarks
        $roasBenchmark = $this->kpiCalculator->getROASBenchmark($kpis['roas']);
        $ltvCacBenchmark = $this->kpiCalculator->getLTVCACBenchmark($kpis['ltv_cac_ratio']);

        // Sales trend (last 7 days)
        $salesTrend = Sale::where('business_id', $currentBusiness->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Quick stats for modules (cached for 5 minutes)
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

        // Revenue Forecast (7 kunlik)
        $revenueForecast = $this->analyticsService->forecastRevenue($currentBusiness->id, 7);

        // AI Insights (so'nggi 3 ta)
        $aiInsights = AiInsight::where('business_id', $currentBusiness->id)
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
            ]);

        // Recent Activities (so'nggi 5 ta) - eager load user to prevent N+1
        $recentActivities = ActivityLog::with('user:id,name')
            ->where('business_id', $currentBusiness->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'type' => $activity->type,
                'created_at' => $activity->created_at->diffForHumans(),
                'user_name' => $activity->user->name ?? 'System',
            ]);

        // Get active alerts count
        $activeAlertsCount = Alert::where('business_id', $currentBusiness->id)
            ->active()
            ->unresolved()
            ->notSnoozed()
            ->count();

        return Inertia::render('Business/Dashboard', [
            'stats' => $stats,
            'kpis' => $kpis,
            'roasBenchmark' => $roasBenchmark,
            'ltvCacBenchmark' => $ltvCacBenchmark,
            'salesTrend' => $salesTrend,
            'moduleStats' => $moduleStats,
            'revenueForecast' => $revenueForecast,
            'aiInsights' => $aiInsights,
            'recentActivities' => $recentActivities,
            'activeAlertsCount' => $activeAlertsCount,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
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
     * Get current business helper
     */
    protected function getCurrentBusiness(): Business
    {
        $user = Auth::user();
        return session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();
    }
}
