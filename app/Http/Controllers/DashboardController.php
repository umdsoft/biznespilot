<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\Business;
use App\Models\DashboardWidget;
use App\Models\DreamBuyer;
use App\Models\KpiDailySnapshot;
use App\Models\KpiPlan;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\Offer;
use App\Models\SalesKpiUserTarget;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use App\Models\Task;
use App\Services\DashboardRecommendationService;
use App\Services\DashboardService;
use App\Services\KPICalculator;
use App\Services\KPIPlanCalculator;
use App\Services\SalesAnalyticsService;
use App\Services\SubscriptionGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HasCurrentBusiness;

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

        if (! $currentBusiness) {
            return redirect()->route('business.business.create');
        }

        // Lightweight cached data only — heavy stats loaded via API (lazy)

        // Active alerts count
        $activeAlertsCount = Cache::remember(
            "dashboard_alerts_count_{$currentBusiness->id}",
            60,
            fn () => Alert::where('business_id', $currentBusiness->id)
                ->active()
                ->unresolved()
                ->notSnoozed()
                ->count()
        );

        // Subscription status (cached 5 min)
        $subscriptionStatus = Cache::remember(
            "dashboard_subscription_status_{$currentBusiness->id}",
            300,
            fn () => $this->getSubscriptionStatus($currentBusiness)
        );

        // Recent activities (CRM + Store, cached 5 min)
        $recentActivities = Cache::remember(
            "dashboard_activities_{$currentBusiness->id}",
            300,
            fn () => $this->buildRecentActivities($currentBusiness)
        );

        return Inertia::render('Business/Dashboard', [
            'lazyLoad' => true,
            'dashboardData' => null,
            'recentActivities' => $recentActivities,
            'activeAlertsCount' => $activeAlertsCount,
            'subscriptionStatus' => $subscriptionStatus,
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

        $cacheKey = "dashboard_initial_v2_{$business->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($business) {
            $storeIds = TelegramStore::where('business_id', $business->id)->pluck('id');
            $today = today();
            $yesterday = today()->subDay();
            $monthStart = now()->subDays(30);
            $prevMonthStart = now()->subDays(60);
            $prevMonthEnd = now()->subDays(30);

            // === STATS ===
            // Bekor qilingan/qaytarilgan buyurtmalar CHIQARILADI
            $excludedStatuses = [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED];

            // Bugungi daromad (barcha yaroqli buyurtmalar — naqd to'lov ham hisobga olinadi)
            $todayRevenue = $storeIds->isNotEmpty()
                ? (float) StoreOrder::whereIn('store_id', $storeIds)
                    ->whereNotIn('status', $excludedStatuses)
                    ->whereDate('created_at', $today)
                    ->sum('total')
                : 0;

            // Kechagi daromad
            $yesterdayRevenue = $storeIds->isNotEmpty()
                ? (float) StoreOrder::whereIn('store_id', $storeIds)
                    ->whereNotIn('status', $excludedStatuses)
                    ->whereDate('created_at', $yesterday)
                    ->sum('total')
                : 0;

            // Oylik daromad (30 kun — Store + CRM)
            $monthlyStoreRevenue = $storeIds->isNotEmpty()
                ? (float) StoreOrder::whereIn('store_id', $storeIds)
                    ->whereNotIn('status', $excludedStatuses)
                    ->where('created_at', '>=', $monthStart)
                    ->sum('total')
                : 0;

            // CRM daromad — converted_at NULL bo'lsa updated_at ham tekshiriladi (fallback)
            $monthlyCrmRevenue = (float) Lead::where('business_id', $business->id)
                ->where('status', 'won')
                ->where(function ($q) use ($monthStart) {
                    $q->where('converted_at', '>=', $monthStart)
                      ->orWhere(function ($sq) use ($monthStart) {
                          $sq->whereNull('converted_at')
                             ->where('updated_at', '>=', $monthStart);
                      });
                })
                ->sum('estimated_value');

            $monthlyRevenue = $monthlyStoreRevenue + $monthlyCrmRevenue;

            // Oldingi 30 kun daromad (taqqoslash uchun)
            $prevMonthStoreRevenue = $storeIds->isNotEmpty()
                ? (float) StoreOrder::whereIn('store_id', $storeIds)
                    ->whereNotIn('status', $excludedStatuses)
                    ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                    ->sum('total')
                : 0;

            $prevMonthCrmRevenue = (float) Lead::where('business_id', $business->id)
                ->where('status', 'won')
                ->where(function ($q) use ($prevMonthStart, $prevMonthEnd) {
                    $q->whereBetween('converted_at', [$prevMonthStart, $prevMonthEnd])
                      ->orWhere(function ($sq) use ($prevMonthStart, $prevMonthEnd) {
                          $sq->whereNull('converted_at')
                             ->whereBetween('updated_at', [$prevMonthStart, $prevMonthEnd]);
                      });
                })
                ->sum('estimated_value');

            $prevMonthRevenue = $prevMonthStoreRevenue + $prevMonthCrmRevenue;

            // Buyurtmalar (30 kun)
            $monthlyOrders = $storeIds->isNotEmpty()
                ? StoreOrder::whereIn('store_id', $storeIds)
                    ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
                    ->where('created_at', '>=', $monthStart)
                    ->count()
                : 0;

            $prevMonthOrders = $storeIds->isNotEmpty()
                ? StoreOrder::whereIn('store_id', $storeIds)
                    ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
                    ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                    ->count()
                : 0;

            // Bugungi buyurtmalar
            $todayOrders = $storeIds->isNotEmpty()
                ? StoreOrder::whereIn('store_id', $storeIds)
                    ->whereDate('created_at', $today)
                    ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
                    ->count()
                : 0;

            $yesterdayOrders = $storeIds->isNotEmpty()
                ? StoreOrder::whereIn('store_id', $storeIds)
                    ->whereDate('created_at', $yesterday)
                    ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
                    ->count()
                : 0;

            // Mijozlar
            $storeCustomers = $storeIds->isNotEmpty()
                ? StoreCustomer::whereIn('store_id', $storeIds)->count()
                : 0;
            $crmCustomers = Lead::where('business_id', $business->id)->where('status', 'won')->count();
            $totalCustomers = $storeCustomers + $crmCustomers;

            // Bugungi yangi mijozlar
            $newCustomersToday = $storeIds->isNotEmpty()
                ? StoreCustomer::whereIn('store_id', $storeIds)
                    ->whereDate('created_at', $today)
                    ->count()
                : 0;

            // O'rtacha chek
            $avgOrderValue = $monthlyOrders > 0
                ? round($monthlyStoreRevenue / $monthlyOrders, 0)
                : 0;

            $stats = [
                'today_revenue' => $todayRevenue,
                'yesterday_revenue' => $yesterdayRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'prev_month_revenue' => $prevMonthRevenue,
                'monthly_orders' => $monthlyOrders,
                'prev_month_orders' => $prevMonthOrders,
                'today_orders' => $todayOrders,
                'yesterday_orders' => $yesterdayOrders,
                'total_customers' => $totalCustomers,
                'new_customers_today' => $newCustomersToday,
                'avg_order_value' => $avgOrderValue,
            ];

            // === HEALTH SCORE ===
            $healthScore = KpiDailySnapshot::where('business_id', $business->id)
                ->where('snapshot_date', $today)
                ->value('health_score') ?? 0;

            // === REVENUE CHART (30 kun — "Oylik daromad" kardi bilan mos) ===
            $revenueChart = $this->buildRevenueChart($business, $storeIds, 30);

            // === PENDING ACTIONS ===
            $pendingOrders = $storeIds->isNotEmpty()
                ? StoreOrder::whereIn('store_id', $storeIds)
                    ->whereIn('status', [StoreOrder::STATUS_PENDING, StoreOrder::STATUS_CONFIRMED])
                    ->count()
                : 0;

            $unansweredLeads = Lead::where('business_id', $business->id)
                ->where('status', 'new')
                ->count();

            $todayTasks = Task::where('business_id', $business->id)
                ->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereDate('due_date', today())
                        ->orWhere('due_date', '<', today());
                })
                ->count();

            $pendingActions = [
                'pending_orders' => $pendingOrders,
                'unanswered_leads' => $unansweredLeads,
                'today_tasks' => $todayTasks,
            ];

            // === QUICK-LINK COUNTS (sidebar card'lari uchun) ===
            // "Do'kon buyurtmalari" — jami aktiv buyurtmalar (30 kun)
            // "Lidlar" — jami ochiq lidlar (new/qualified/proposal/negotiation)
            // "Sotuvlar" — 30 kun ichida yopilgan ('won')
            $openLeadStatuses = ['new', 'qualified', 'proposal', 'negotiation', 'contacted'];
            $openLeads = Lead::where('business_id', $business->id)
                ->whereIn('status', $openLeadStatuses)
                ->count();

            $wonSales30d = Lead::where('business_id', $business->id)
                ->where('status', 'won')
                ->where(function ($q) use ($monthStart) {
                    $q->where('converted_at', '>=', $monthStart)
                      ->orWhere(function ($sq) use ($monthStart) {
                          $sq->whereNull('converted_at')
                             ->where('updated_at', '>=', $monthStart);
                      });
                })
                ->count();

            $quickCounts = [
                'store_orders' => $monthlyOrders,
                'open_leads' => $openLeads,
                'won_sales_30d' => $wonSales30d,
            ];

            // === TEAM KPI (joriy oy aktiv maqsadlar bo'yicha) ===
            $teamKpi = $this->buildTeamKpi($business);

            // === RECOMMENDATIONS ===
            $recommendationService = app(DashboardRecommendationService::class);
            $recommendations = $recommendationService->getRecommendations($business, array_merge($stats, [
                'store_ids' => $storeIds,
                'pending_orders' => $pendingOrders,
                'unanswered_leads' => $unansweredLeads,
                'overdue_tasks' => $todayTasks,
            ]));

            // === RECENT ORDERS (5 ta) ===
            $recentOrders = [];
            if ($storeIds->isNotEmpty()) {
                $recentOrders = StoreOrder::with('customer:id,name')
                    ->whereIn('store_id', $storeIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn ($order) => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer->name ?? 'Telegram',
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'status_label' => $order->getStatusLabel(),
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at->diffForHumans(),
                    ])
                    ->values()
                    ->toArray();
            }

            return [
                'stats' => $stats,
                'health_score' => $healthScore,
                'revenue_chart' => $revenueChart,
                'pending_actions' => $pendingActions,
                'quick_counts' => $quickCounts,
                'team_kpi' => $teamKpi,
                'recommendations' => $recommendations,
                'recent_orders' => $recentOrders,
            ];
        });

        return response()->json($data);
    }

    /**
     * Kunlik daromad grafigi uchun ma'lumot (Store + CRM)
     */
    private function buildRevenueChart(Business $business, $storeIds, int $days): array
    {
        // Store orders — kunlik (bekor/qaytarilgan CHIQARILADI)
        $storeTrend = collect();
        if ($storeIds->isNotEmpty()) {
            $storeTrend = StoreOrder::whereIn('store_id', $storeIds)
                ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
                ->groupBy('date')
                ->get()
                ->keyBy('date');
        }

        // CRM won leads — kunlik.
        // `converted_at` bo'sh bo'lsa `updated_at` fallback (monthly_revenue cardi bilan mos).
        $crmTrend = Lead::where('business_id', $business->id)
            ->where('status', 'won')
            ->where(function ($q) use ($days) {
                $from = now()->subDays($days);
                $q->where('converted_at', '>=', $from)
                  ->orWhere(function ($sq) use ($from) {
                      $sq->whereNull('converted_at')
                         ->where('updated_at', '>=', $from);
                  });
            })
            ->selectRaw('DATE(COALESCE(converted_at, updated_at)) as date, COUNT(*) as orders, SUM(estimated_value) as revenue')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $storeData = $storeTrend->get($date);
            $crmData = $crmTrend->get($date);

            $chart[] = [
                'date' => $date,
                'revenue' => (float) ($storeData->revenue ?? 0) + (float) ($crmData->revenue ?? 0),
                'orders' => ($storeData->orders ?? 0) + ($crmData->orders ?? 0),
            ];
        }

        return $chart;
    }

    /**
     * Jamoa KPI bajarilishi — joriy oy uchun aktiv SalesKpiUserTarget
     * yozuvlari foydalanuvchi bo'yicha guruhlangan.
     *
     * Har bir user uchun barcha faol KPI'lar bo'yicha o'rtacha
     * achievement_percent hisoblanadi va progress bar uchun qaytariladi.
     *
     * Natija — 3 ta kategoriya:
     *   - behind      : < 70% (qizil)
     *   - on_track    : 70-99% (sariq/ko'k)
     *   - ahead       : >= 100% (yashil)
     */
    private function buildTeamKpi(Business $business): array
    {
        $targets = SalesKpiUserTarget::query()
            ->withoutGlobalScope('business')
            ->with(['user:id,name', 'kpiSetting:id,name'])
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->currentMonth()
            ->get();

        if ($targets->isEmpty()) {
            return [
                'has_data' => false,
                'summary' => null,
                'behind' => [],
                'on_track' => [],
                'ahead' => [],
                'total_users' => 0,
            ];
        }

        // User bo'yicha guruhlash — bir user bir nechta KPI'ga ega bo'lishi mumkin
        $byUser = $targets->groupBy('user_id')->map(function ($userTargets, $userId) {
            $user = $userTargets->first()->user;
            $count = $userTargets->count();
            $avgPercent = (float) $userTargets->avg('achievement_percent') ?? 0;
            $totalTarget = (float) $userTargets->sum(fn ($t) => $t->adjusted_target ?? $t->target_value);
            $totalAchieved = (float) $userTargets->sum('achieved_value');

            $kpiNames = $userTargets
                ->map(fn ($t) => $t->kpiSetting?->name)
                ->filter()
                ->take(3)
                ->values()
                ->toArray();

            return [
                'user_id' => $userId,
                'user_name' => $user?->name ?? 'Noma\'lum',
                'achievement_percent' => round($avgPercent, 1),
                'target_value' => $totalTarget,
                'achieved_value' => $totalAchieved,
                'kpi_count' => $count,
                'kpi_names' => $kpiNames,
            ];
        })->values();

        $behind = $byUser->filter(fn ($u) => $u['achievement_percent'] < 70)
            ->sortBy('achievement_percent')
            ->take(5)
            ->values()
            ->toArray();

        $onTrack = $byUser->filter(fn ($u) => $u['achievement_percent'] >= 70 && $u['achievement_percent'] < 100)
            ->sortByDesc('achievement_percent')
            ->take(5)
            ->values()
            ->toArray();

        $ahead = $byUser->filter(fn ($u) => $u['achievement_percent'] >= 100)
            ->sortByDesc('achievement_percent')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'has_data' => true,
            'summary' => [
                'total_users' => $byUser->count(),
                'avg_achievement' => round((float) $byUser->avg('achievement_percent'), 1),
                'behind_count' => count($behind),
                'ahead_count' => count($ahead),
            ],
            'behind' => $behind,
            'on_track' => $onTrack,
            'ahead' => $ahead,
            'total_users' => $byUser->count(),
        ];
    }

    /**
     * So'nggi faoliyatlar (CRM + Store buyurtmalar birlashtirilgan)
     */
    private function buildRecentActivities(Business $business): array
    {
        // CRM faoliyatlar
        $activities = ActivityLog::with('user:id,name')
            ->where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'type' => $activity->type,
                'created_at' => $activity->created_at,
                'created_at_human' => $activity->created_at->diffForHumans(),
                'user_name' => $activity->user->name ?? 'System',
            ])
            ->values();

        // Store buyurtmalar
        $storeIds = TelegramStore::where('business_id', $business->id)->pluck('id');
        if ($storeIds->isNotEmpty()) {
            $storeActivities = StoreOrder::with('customer:id,name')
                ->whereIn('store_id', $storeIds)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($order) => [
                    'id' => 'order_'.$order->id,
                    'description' => "Yangi buyurtma #{$order->order_number} — ".number_format($order->total, 0, '.', ' ')." so'm",
                    'type' => 'store_order',
                    'created_at' => $order->created_at,
                    'created_at_human' => $order->created_at->diffForHumans(),
                    'user_name' => $order->customer->name ?? 'Telegram',
                ])
                ->values();

            $activities = collect(array_merge($activities->all(), $storeActivities->all()));
        }

        return $activities
            ->sortByDesc('created_at')
            ->take(5)
            ->map(fn ($item) => [
                'id' => $item['id'],
                'description' => $item['description'],
                'type' => $item['type'],
                'created_at' => $item['created_at_human'],
                'user_name' => $item['user_name'],
            ])
            ->values()
            ->toArray();
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

        $widgetIds = collect($request->widgets)->pluck('id')->toArray();
        $widgets = DashboardWidget::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->whereIn('id', $widgetIds)
            ->get()
            ->keyBy('id');

        foreach ($request->widgets as $widgetData) {
            if ($widget = $widgets->get($widgetData['id'])) {
                $widget->update([
                    'sort_order' => $widgetData['sort_order'],
                    'is_visible' => $widgetData['is_visible'],
                    'settings' => $widgetData['settings'] ?? [],
                ]);
            }
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

        if (! $currentBusiness) {
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
            'businessCategory' => $currentBusiness->category,
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

        if (! $currentBusiness) {
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
                'error' => 'Hisoblashda xatolik yuz berdi: '.$e->getMessage(),
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
     * Get subscription status for dashboard widget
     */
    private function getSubscriptionStatus(Business $business): ?array
    {
        $gate = app(SubscriptionGate::class);

        try {
            $subscription = $gate->getActiveSubscription($business);
            $plan = $subscription->plan;

            // Calculate days remaining (trial uchun trial_ends_at, pullik uchun ends_at)
            $effectiveEndDate = ($subscription->status === 'trialing' && $subscription->trial_ends_at)
                ? $subscription->trial_ends_at
                : $subscription->ends_at;
            $daysRemaining = (int) max(0, now()->diffInDays($effectiveEndDate, false));

            // Format renewal date
            $renewsAt = $effectiveEndDate?->translatedFormat('d-F, Y');

            // Get usage stats
            $usageStats = $gate->getUsageStats($business);

            // Build usage array with percentages for main limits
            $usage = [];
            $mainLimits = ['users', 'monthly_leads', 'ai_call_minutes', 'instagram_accounts', 'telegram_bots', 'storage_mb'];

            foreach ($mainLimits as $limitKey) {
                if (isset($usageStats[$limitKey])) {
                    $stat = $usageStats[$limitKey];
                    $usage[$limitKey] = [
                        'label' => $stat['label'],
                        'used' => $stat['current'],
                        'limit' => $stat['is_unlimited'] ? null : $stat['limit'],
                        'percent' => $stat['percentage'],
                        'is_unlimited' => $stat['is_unlimited'],
                        'is_exceeded' => $stat['is_exceeded'],
                        'is_warning' => $stat['is_warning'],
                    ];
                }
            }

            // Determine status label and color
            $statusLabel = match ($subscription->status) {
                'active' => 'Faol',
                'trialing' => 'Sinov davri',
                'past_due' => "To'lov kutilmoqda",
                'canceled' => 'Bekor qilingan',
                default => 'Faol',
            };

            $statusColor = match ($subscription->status) {
                'active' => 'green',
                'trialing' => 'blue',
                'past_due' => 'red',
                'canceled' => 'gray',
                default => 'green',
            };

            return [
                'has_subscription' => true,
                'plan_name' => $plan->name,
                'plan_slug' => $plan->slug,
                'price' => $plan->monthly_price,
                'status' => $subscription->status,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'is_trial' => $subscription->status === 'trialing',
                'days_remaining' => $daysRemaining,
                'renews_at' => $renewsAt,
                'usage' => $usage,
            ];

        } catch (\App\Exceptions\NoActiveSubscriptionException $e) {
            return [
                'has_subscription' => false,
                'plan_name' => null,
                'plan_slug' => null,
                'price' => null,
                'status' => 'no_subscription',
                'status_label' => 'Obuna yo\'q',
                'status_color' => 'gray',
                'is_trial' => false,
                'days_remaining' => 0,
                'renews_at' => null,
                'usage' => [],
            ];
        }
    }
}
