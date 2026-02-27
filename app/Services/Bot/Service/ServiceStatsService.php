<?php

namespace App\Services\Bot\Service;

use App\Models\Bot\Service\ServiceDailyStat;
use App\Models\Bot\Service\ServiceRequest;
use Illuminate\Support\Facades\DB;

class ServiceStatsService
{
    public function getDashboardData(string $businessId): array
    {
        $today = now()->toDateString();

        $todayStats = ServiceRequest::forBusiness($businessId)
            ->whereDate('created_at', $today)
            ->selectRaw("
                COUNT(CASE WHEN status NOT IN ('completed','cancelled') THEN 1 END) as active_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as today_completed,
                AVG(CASE WHEN status = 'completed' AND rating IS NOT NULL THEN rating ELSE NULL END) as avg_rating,
                SUM(CASE WHEN status = 'completed' THEN total_cost ELSE 0 END) as today_revenue
            ")
            ->first();

        $recentRequests = ServiceRequest::forBusiness($businessId)
            ->with(['category', 'master'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'kpi' => [
                'active_requests' => $todayStats->active_requests ?? 0,
                'today_completed' => $todayStats->today_completed ?? 0,
                'avg_rating' => $todayStats->avg_rating ? round($todayStats->avg_rating, 1) : null,
                'today_revenue' => (float) ($todayStats->today_revenue ?? 0),
            ],
            'recent_requests' => $recentRequests,
        ];
    }

    public function getChartData(string $businessId, int $days = 7): array
    {
        $stats = ServiceDailyStat::forBusiness($businessId)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date')
            ->get();

        return [
            'labels' => $stats->pluck('date')->map(fn ($d) => $d->format('d.m'))->toArray(),
            'requests' => $stats->pluck('total_requests')->toArray(),
            'revenue' => $stats->pluck('total_revenue')->toArray(),
            'completed' => $stats->pluck('completed')->toArray(),
        ];
    }

    public function calculateDailyStats(string $businessId, ?string $date = null): ServiceDailyStat
    {
        $date = $date ?? now()->toDateString();

        $stats = ServiceRequest::forBusiness($businessId)
            ->whereDate('created_at', $date)
            ->selectRaw("
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'completed' THEN total_cost ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'completed' AND rating IS NOT NULL THEN rating ELSE NULL END) as avg_rating,
                AVG(CASE WHEN status = 'completed' AND completed_at IS NOT NULL AND assigned_at IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, assigned_at, completed_at) ELSE NULL END) as avg_completion_time
            ")
            ->first();

        $topCategories = ServiceRequest::forBusiness($businessId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->join('service_categories', 'service_requests.category_id', '=', 'service_categories.id')
            ->select('service_categories.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('service_categories.name')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'count' => $c->cnt])
            ->toArray();

        $topMasters = ServiceRequest::forBusiness($businessId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->join('service_masters', 'service_requests.master_id', '=', 'service_masters.id')
            ->select('service_masters.name', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(service_requests.total_cost) as revenue'))
            ->groupBy('service_masters.name')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get()
            ->map(fn ($m) => ['name' => $m->name, 'count' => $m->cnt, 'revenue' => (float) $m->revenue])
            ->toArray();

        return ServiceDailyStat::updateOrCreate(
            ['business_id' => $businessId, 'date' => $date],
            [
                'total_requests' => $stats->total_requests ?? 0,
                'completed' => $stats->completed ?? 0,
                'cancelled' => $stats->cancelled ?? 0,
                'total_revenue' => $stats->total_revenue ?? 0,
                'avg_rating' => $stats->avg_rating ?? 0,
                'avg_completion_time' => $stats->avg_completion_time,
                'top_categories' => $topCategories,
                'top_masters' => $topMasters,
            ]
        );
    }

    public function getRequestsByStatus(string $businessId): array
    {
        return ServiceRequest::forBusiness($businessId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
