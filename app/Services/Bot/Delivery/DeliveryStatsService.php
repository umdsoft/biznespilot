<?php

namespace App\Services\Bot\Delivery;

use App\Models\Bot\Delivery\DeliveryDailyStat;
use App\Models\Bot\Delivery\DeliveryOrder;
use Illuminate\Support\Facades\DB;

class DeliveryStatsService
{
    public function calculateDailyStats(string $businessId, ?string $date = null): DeliveryDailyStat
    {
        $date = $date ?? now()->toDateString();

        $stats = DeliveryOrder::forBusiness($businessId)
            ->whereDate('created_at', $date)
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'delivered' THEN total ELSE NULL END) as avg_order_value,
                AVG(CASE WHEN status = 'delivered' AND delivered_at IS NOT NULL AND confirmed_at IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, confirmed_at, delivered_at) ELSE NULL END) as avg_delivery_time
            ")
            ->first();

        $topItems = DeliveryOrder::forBusiness($businessId)
            ->whereDate('created_at', $date)
            ->where('status', 'delivered')
            ->join('delivery_order_items', 'delivery_orders.id', '=', 'delivery_order_items.order_id')
            ->select('delivery_order_items.item_name', DB::raw('SUM(delivery_order_items.quantity) as qty'))
            ->groupBy('delivery_order_items.item_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($i) => ['name' => $i->item_name, 'qty' => $i->qty])
            ->toArray();

        return DeliveryDailyStat::updateOrCreate(
            ['business_id' => $businessId, 'date' => $date],
            [
                'total_orders' => $stats->total_orders ?? 0,
                'completed_orders' => $stats->completed_orders ?? 0,
                'cancelled_orders' => $stats->cancelled_orders ?? 0,
                'total_revenue' => $stats->total_revenue ?? 0,
                'avg_order_value' => $stats->avg_order_value ?? 0,
                'avg_delivery_time' => $stats->avg_delivery_time,
                'top_items' => $topItems,
            ]
        );
    }

    public function getDashboardData(string $businessId): array
    {
        $today = now()->toDateString();

        $todayStats = DeliveryOrder::forBusiness($businessId)
            ->whereDate('created_at', $today)
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) as revenue,
                AVG(CASE WHEN status = 'delivered' THEN total ELSE NULL END) as avg_order,
                AVG(CASE WHEN status = 'delivered' AND delivered_at IS NOT NULL AND confirmed_at IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, confirmed_at, delivered_at) ELSE NULL END) as avg_time
            ")
            ->first();

        $recentOrders = DeliveryOrder::forBusiness($businessId)
            ->with('items')
            ->latest()
            ->limit(10)
            ->get();

        return [
            'today' => [
                'total_orders' => $todayStats->total_orders ?? 0,
                'revenue' => (float) ($todayStats->revenue ?? 0),
                'avg_order_value' => round($todayStats->avg_order ?? 0, 0),
                'avg_delivery_time' => $todayStats->avg_time ? round($todayStats->avg_time) : null,
            ],
            'recent_orders' => $recentOrders,
        ];
    }

    public function getChartData(string $businessId, int $days = 7): array
    {
        $stats = DeliveryDailyStat::forBusiness($businessId)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date')
            ->get();

        return [
            'labels' => $stats->pluck('date')->map(fn ($d) => $d->format('d.m'))->toArray(),
            'orders' => $stats->pluck('total_orders')->toArray(),
            'revenue' => $stats->pluck('total_revenue')->toArray(),
        ];
    }

    public function getOrdersByStatus(string $businessId): array
    {
        return DeliveryOrder::forBusiness($businessId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
