<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreReview;
use App\Models\Store\TelegramStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StoreAnalyticsController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    /**
     * Main analytics dashboard
     */
    public function dashboard()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup('Avval do\'kon yarating.');
        }

        // Today's stats
        $todayStats = $this->getPeriodStats($store, 'today');

        // This week's stats
        $weekStats = $this->getPeriodStats($store, 'week');

        // This month's stats
        $monthStats = $this->getPeriodStats($store, 'month');

        // Daily analytics for chart (last 30 days) — from actual orders
        $dailyOrders = StoreOrder::where('store_id', $store->id)
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill all 30 days (including days with no orders)
        $dailyAnalytics = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayData = $dailyOrders->get($date);
            $dailyAnalytics->push([
                'date' => now()->subDays($i)->format('d.m'),
                'orders_count' => $dayData->orders_count ?? 0,
                'revenue' => (float) ($dayData->revenue ?? 0),
            ]);
        }

        // Recent orders (last 10)
        $recentOrders = StoreOrder::where('store_id', $store->id)
            ->with(['customer', 'items'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'total' => $order->total,
                'items_count' => $order->items->count(),
                'customer_name' => $order->customer?->getDisplayName(),
                'customer_phone' => $order->customer?->phone,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at?->toISOString(),
            ]);

        // Pending orders (need attention)
        $pendingOrders = StoreOrder::where('store_id', $store->id)
            ->whereIn('status', [StoreOrder::STATUS_PENDING, StoreOrder::STATUS_CONFIRMED])
            ->with(['customer', 'items'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'total' => $order->total,
                'items_count' => $order->items->count(),
                'items_preview' => $order->items->take(3)->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                ])->toArray(),
                'customer_name' => $order->customer?->getDisplayName(),
                'customer_phone' => $order->customer?->phone,
                'created_at' => $order->created_at?->toISOString(),
                'minutes_ago' => $order->created_at?->diffInMinutes(now()),
            ]);

        // Top products (by order count this month)
        $topProducts = DB::table('store_order_items')
            ->join('store_orders', 'store_order_items.order_id', '=', 'store_orders.id')
            ->join('store_products', 'store_order_items.product_id', '=', 'store_products.id')
            ->where('store_orders.store_id', $store->id)
            ->where('store_orders.created_at', '>=', now()->startOfMonth())
            ->whereNotIn('store_orders.status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])
            ->select(
                'store_products.id',
                'store_products.name',
                'store_products.price',
                DB::raw('SUM(store_order_items.quantity) as total_quantity'),
                DB::raw('SUM(store_order_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT store_orders.id) as orders_count')
            )
            ->groupBy('store_products.id', 'store_products.name', 'store_products.price')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Order status distribution
        $statusDistribution = StoreOrder::where('store_id', $store->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Overall store health
        $totalProducts = StoreProduct::where('store_id', $store->id)->count();
        $activeProducts = StoreProduct::where('store_id', $store->id)->where('is_active', true)->count();
        $totalCustomers = StoreCustomer::where('store_id', $store->id)->count();
        $pendingOrdersCount = StoreOrder::where('store_id', $store->id)
            ->where('status', StoreOrder::STATUS_PENDING)
            ->count();

        // Bot turiga mos qo'shimcha KPIlar
        $botTypeKpis = $this->getBotTypeKpis($store);

        // Store type ma'lumotlari (dashboard sarlavha, rang, ikonlar uchun)
        $botTypeEnum = $store->getBotTypeEnum();

        return Inertia::render('Business/Store/Dashboard', [
            'store' => [
                'name' => $store->name,
                'is_active' => $store->is_active,
                'mini_app_url' => $store->getMiniAppUrl(),
                'store_type' => $store->store_type,
            ],
            'storeTypeConfig' => $botTypeEnum ? [
                'type' => $botTypeEnum->value,
                'label' => $botTypeEnum->label(),
                'icon' => $botTypeEnum->icon(),
                'color' => $botTypeEnum->color(),
                'bgColor' => $botTypeEnum->bgColor(),
                'actionLabel' => $botTypeEnum->primaryActionLabel(),
            ] : null,
            'stats' => $todayStats,
            'weekStats' => $weekStats,
            'monthStats' => $monthStats,
            'chartData' => $dailyAnalytics,
            'recentOrders' => $recentOrders,
            'pendingOrders' => $pendingOrders,
            'topProducts' => $topProducts,
            'statusDistribution' => $statusDistribution,
            'storeHealth' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'total_customers' => $totalCustomers,
                'pending_orders' => $pendingOrdersCount,
            ],
            'botTypeKpis' => $botTypeKpis,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Sales report with date range and breakdown
     */
    public function salesReport(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup();
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'day'); // day, week, month

        // Sales data grouped by period
        $salesQuery = StoreOrder::where('store_id', $store->id)
            ->where('payment_status', StoreOrder::PAYMENT_PAID)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        $dateFormat = match ($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $salesData = (clone $salesQuery)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('AVG(total) as avg_order_value'),
                DB::raw('SUM(discount_amount) as total_discounts'),
                DB::raw('SUM(delivery_fee) as total_delivery_fees')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Summary totals
        $summary = [
            'total_orders' => (clone $salesQuery)->count(),
            'total_revenue' => (clone $salesQuery)->sum('total'),
            'avg_order_value' => (clone $salesQuery)->avg('total'),
            'total_discounts' => (clone $salesQuery)->sum('discount_amount'),
            'total_delivery_fees' => (clone $salesQuery)->sum('delivery_fee'),
            'unique_customers' => (clone $salesQuery)->distinct('customer_id')->count('customer_id'),
        ];

        $summary['avg_order_value'] = $summary['avg_order_value']
            ? round($summary['avg_order_value'], 2)
            : 0;

        // Payment method breakdown
        $paymentMethods = StoreOrder::where('store_id', $store->id)
            ->where('payment_status', StoreOrder::PAYMENT_PAID)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get();

        return Inertia::render('Business/Store/Dashboard', [
            'reportType' => 'sales',
            'salesData' => $salesData,
            'summary' => $summary,
            'paymentMethods' => $paymentMethods,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'group_by' => $groupBy,
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Product performance report
     */
    public function productReport(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup();
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $sortBy = $request->input('sort_by', 'revenue'); // revenue, quantity, orders

        // Product performance data
        $productQuery = DB::table('store_order_items')
            ->join('store_orders', 'store_order_items.order_id', '=', 'store_orders.id')
            ->join('store_products', 'store_order_items.product_id', '=', 'store_products.id')
            ->leftJoin('store_categories', 'store_products.category_id', '=', 'store_categories.id')
            ->where('store_orders.store_id', $store->id)
            ->whereDate('store_orders.created_at', '>=', $dateFrom)
            ->whereDate('store_orders.created_at', '<=', $dateTo)
            ->whereNotIn('store_orders.status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED]);

        $products = (clone $productQuery)
            ->select(
                'store_products.id',
                'store_products.name',
                'store_products.price as current_price',
                'store_products.stock_quantity',
                'store_products.is_active',
                'store_categories.name as category_name',
                DB::raw('SUM(store_order_items.quantity) as total_quantity'),
                DB::raw('SUM(store_order_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT store_orders.id) as orders_count'),
                DB::raw('AVG(store_order_items.price) as avg_selling_price')
            )
            ->groupBy(
                'store_products.id',
                'store_products.name',
                'store_products.price',
                'store_products.stock_quantity',
                'store_products.is_active',
                'store_categories.name'
            );

        // Sorting
        match ($sortBy) {
            'quantity' => $products->orderByDesc('total_quantity'),
            'orders' => $products->orderByDesc('orders_count'),
            default => $products->orderByDesc('total_revenue'),
        };

        $productData = $products->get();

        // Category breakdown
        $categoryBreakdown = (clone $productQuery)
            ->select(
                'store_categories.name as category_name',
                DB::raw('SUM(store_order_items.quantity) as total_quantity'),
                DB::raw('SUM(store_order_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT store_orders.id) as orders_count')
            )
            ->groupBy('store_categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        // Products with no sales in period
        $soldProductIds = (clone $productQuery)
            ->select('store_products.id')
            ->distinct()
            ->pluck('id');

        $noSalesProducts = StoreProduct::where('store_id', $store->id)
            ->where('is_active', true)
            ->whereNotIn('id', $soldProductIds)
            ->select('id', 'name', 'price', 'stock_quantity')
            ->get();

        // Summary
        $totalRevenue = $productData->sum('total_revenue');
        $totalQuantity = $productData->sum('total_quantity');

        return Inertia::render('Business/Store/Dashboard', [
            'reportType' => 'products',
            'productData' => $productData,
            'categoryBreakdown' => $categoryBreakdown,
            'noSalesProducts' => $noSalesProducts,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_quantity' => $totalQuantity,
                'unique_products_sold' => $productData->count(),
                'no_sales_products' => $noSalesProducts->count(),
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort_by' => $sortBy,
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Bot turiga mos qo'shimcha KPIlar hisoblash
     */
    protected function getBotTypeKpis(TelegramStore $store): array
    {
        $storeType = $store->store_type ?? 'ecommerce';

        return match ($storeType) {
            'delivery' => $this->getDeliveryKpis($store),
            'queue' => $this->getQueueKpis($store),
            'service' => $this->getServiceKpis($store),
            default => $this->getEcommerceKpis($store),
        };
    }

    /**
     * E-commerce: mahsulotlar, mijozlar statistikasi
     */
    protected function getEcommerceKpis(TelegramStore $store): array
    {
        $activeProducts = StoreProduct::where('store_id', $store->id)->where('is_active', true)->count();
        $totalCustomers = StoreCustomer::where('store_id', $store->id)->count();

        return [
            'type' => 'ecommerce',
            'extra_kpi_1' => [
                'label' => 'Mahsulotlar',
                'value' => $activeProducts,
                'format' => 'number',
            ],
            'extra_kpi_2' => [
                'label' => 'Mijozlar',
                'value' => $totalCustomers,
                'format' => 'number',
            ],
        ];
    }

    /**
     * Delivery: o'rtacha yetkazish vaqti, bekor qilish foizi
     */
    protected function getDeliveryKpis(TelegramStore $store): array
    {
        $now = now();

        // O'rtacha yetkazish vaqti (shipped_at → delivered_at, daqiqalarda)
        $avgDeliveryMinutes = StoreOrder::where('store_id', $store->id)
            ->where('status', StoreOrder::STATUS_DELIVERED)
            ->whereNotNull('shipped_at')
            ->whereNotNull('delivered_at')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, shipped_at, delivered_at)) as avg_minutes')
            ->value('avg_minutes');

        // Bekor qilish foizi (oxirgi 30 kun)
        $totalOrders = StoreOrder::where('store_id', $store->id)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $cancelledOrders = StoreOrder::where('store_id', $store->id)
            ->where('status', StoreOrder::STATUS_CANCELLED)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $cancelRate = $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100, 1) : 0;

        return [
            'type' => 'delivery',
            'extra_kpi_1' => [
                'label' => "O'rt. yetkazish",
                'value' => $avgDeliveryMinutes ? round($avgDeliveryMinutes) : 0,
                'suffix' => 'daq',
                'format' => 'duration',
            ],
            'extra_kpi_2' => [
                'label' => 'Bekor qilingan',
                'value' => $cancelRate,
                'suffix' => '%',
                'format' => 'percent',
            ],
        ];
    }

    /**
     * Queue: navbatdagilar, o'rtacha kutish, bajarilganlik foizi
     */
    protected function getQueueKpis(TelegramStore $store): array
    {
        $now = now();

        // Hozir navbatda (pending + confirmed buyurtmalar)
        $inQueue = StoreOrder::where('store_id', $store->id)
            ->whereIn('status', [StoreOrder::STATUS_PENDING, StoreOrder::STATUS_CONFIRMED])
            ->count();

        // O'rtacha kutish vaqti (created_at → confirmed_at, daqiqalarda)
        $avgWaitMinutes = StoreOrder::where('store_id', $store->id)
            ->whereNotNull('confirmed_at')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, confirmed_at)) as avg_minutes')
            ->value('avg_minutes');

        // Bajarilganlik foizi (delivered / (delivered + cancelled))
        $completed = StoreOrder::where('store_id', $store->id)
            ->where('status', StoreOrder::STATUS_DELIVERED)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $totalFinished = StoreOrder::where('store_id', $store->id)
            ->whereIn('status', [StoreOrder::STATUS_DELIVERED, StoreOrder::STATUS_CANCELLED])
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $completionRate = $totalFinished > 0 ? round(($completed / $totalFinished) * 100, 1) : 0;

        return [
            'type' => 'queue',
            'extra_kpi_1' => [
                'label' => 'Navbatdagilar',
                'value' => $inQueue,
                'format' => 'number',
            ],
            'extra_kpi_2' => [
                'label' => 'Bajarilganlik',
                'value' => $completionRate,
                'suffix' => '%',
                'format' => 'percent',
            ],
            'avg_wait' => $avgWaitMinutes ? round($avgWaitMinutes) : 0,
        ];
    }

    /**
     * Service: faol arizalar, o'rtacha baho
     */
    protected function getServiceKpis(TelegramStore $store): array
    {
        // Faol arizalar (active status dagi buyurtmalar)
        $activeRequests = StoreOrder::where('store_id', $store->id)
            ->whereIn('status', StoreOrder::ACTIVE_STATUSES)
            ->count();

        // O'rtacha baho (reviews jadvalidan)
        $avgRating = StoreReview::where('store_id', $store->id)
            ->where('is_approved', true)
            ->avg('rating');

        return [
            'type' => 'service',
            'extra_kpi_1' => [
                'label' => 'Faol arizalar',
                'value' => $activeRequests,
                'format' => 'number',
            ],
            'extra_kpi_2' => [
                'label' => "O'rt. baho",
                'value' => $avgRating ? round($avgRating, 1) : 0,
                'suffix' => '',
                'format' => 'rating',
            ],
        ];
    }

    /**
     * Get stats for a specific period
     */
    protected function getPeriodStats(TelegramStore $store, string $period): array
    {
        $query = StoreOrder::where('store_id', $store->id);

        $now = now();
        match ($period) {
            'today' => $query->whereDate('created_at', $now),
            'week' => $query->where('created_at', '>=', $now->copy()->startOfWeek()),
            'month' => $query->where('created_at', '>=', $now->copy()->startOfMonth()),
            'year' => $query->where('created_at', '>=', $now->copy()->startOfYear()),
            default => null,
        };

        $orders = $query->get();
        // Bekor qilingan va qaytarilgan buyurtmalarni chiqarib tashlash
        $activeOrders = $orders->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED]);

        // Get previous period for comparison
        $prevQuery = StoreOrder::where('store_id', $store->id);

        match ($period) {
            'today' => $prevQuery->whereDate('created_at', $now->copy()->subDay()),
            'week' => $prevQuery->whereBetween('created_at', [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ]),
            'month' => $prevQuery->whereBetween('created_at', [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ]),
            default => null,
        };

        $prevOrders = $prevQuery->get();
        $prevActiveOrders = $prevOrders->whereNotIn('status', [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED]);

        $currentRevenue = $activeOrders->sum('total');
        $prevRevenue = $prevActiveOrders->sum('total');

        $revenueChange = $prevRevenue > 0
            ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($currentRevenue > 0 ? 100 : 0);

        $currentOrdersCount = $orders->count();
        $prevOrdersCount = $prevOrders->count();

        $ordersChange = $prevOrdersCount > 0
            ? round((($currentOrdersCount - $prevOrdersCount) / $prevOrdersCount) * 100, 1)
            : ($currentOrdersCount > 0 ? 100 : 0);

        return [
            'orders_count' => $currentOrdersCount,
            'orders_change' => $ordersChange,
            'revenue' => $currentRevenue,
            'revenue_change' => $revenueChange,
            'avg_order_value' => $activeOrders->count() > 0 ? round($activeOrders->avg('total'), 2) : 0,
            'pending_orders' => $orders->where('status', StoreOrder::STATUS_PENDING)->count(),
            'completed_orders' => $orders->where('status', StoreOrder::STATUS_DELIVERED)->count(),
            'cancelled_orders' => $orders->where('status', StoreOrder::STATUS_CANCELLED)->count(),
            'new_customers' => StoreCustomer::where('store_id', $store->id)
                ->where('created_at', '>=', match ($period) {
                    'today' => $now->copy()->startOfDay(),
                    'week' => $now->copy()->startOfWeek(),
                    'month' => $now->copy()->startOfMonth(),
                    default => $now->copy()->startOfYear(),
                })
                ->count(),
        ];
    }
}
