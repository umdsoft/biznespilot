<?php

namespace App\Http\Controllers;

use App\Models\DreamBuyer;
use App\Models\MarketingChannel;
use App\Models\Offer;
use App\Models\Sale;
use App\Models\AIInsight;
use App\Models\ActivityLog;
use App\Services\KPICalculator;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $kpiCalculator;
    protected $analyticsService;

    public function __construct(
        KPICalculator $kpiCalculator,
        SalesAnalyticsService $analyticsService
    ) {
        $this->kpiCalculator = $kpiCalculator;
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
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

        // Quick stats for modules
        $moduleStats = [
            'dream_buyers' => DreamBuyer::where('business_id', $currentBusiness->id)->count(),
            'marketing_channels' => MarketingChannel::where('business_id', $currentBusiness->id)
                ->where('is_active', true)
                ->count(),
            'active_offers' => Offer::where('business_id', $currentBusiness->id)
                ->where('status', 'active')
                ->count(),
        ];

        // Revenue Forecast (7 kunlik)
        $revenueForecast = $this->analyticsService->forecastRevenue($currentBusiness->id, 7);

        // AI Insights (so'nggi 3 ta)
        $aiInsights = AIInsight::where('business_id', $currentBusiness->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'title' => $insight->title,
                'summary' => $insight->summary,
                'priority' => $insight->priority,
                'created_at' => $insight->created_at->diffForHumans(),
            ]);

        // Recent Activities (so'nggi 5 ta)
        $recentActivities = ActivityLog::where('business_id', $currentBusiness->id)
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
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'type' => $currentBusiness->type,
            ],
        ]);
    }
}
