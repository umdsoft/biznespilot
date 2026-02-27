<?php

namespace App\Services\Bot\Queue;

use App\Models\Bot\Queue\QueueBooking;
use App\Models\Bot\Queue\QueueDailyStat;
use Illuminate\Support\Facades\DB;

class QueueStatsService
{
    /**
     * Get dashboard KPIs and recent bookings.
     */
    public function getDashboardData(string $businessId): array
    {
        $today = now()->toDateString();

        $todayStats = QueueBooking::forBusiness($businessId)
            ->whereDate('date', $today)
            ->selectRaw("
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status IN ('confirmed', 'in_progress') THEN 1 ELSE 0 END) as in_queue_now,
                AVG(CASE WHEN estimated_wait IS NOT NULL THEN estimated_wait ELSE NULL END) as avg_wait,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                CASE WHEN COUNT(*) > 0
                    THEN ROUND(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1)
                    ELSE 0
                END as completion_pct
            ")
            ->first();

        $recentBookings = QueueBooking::forBusiness($businessId)
            ->with(['service', 'branch', 'specialist'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'today' => [
                'total_bookings' => $todayStats->total_bookings ?? 0,
                'in_queue_now' => (int) ($todayStats->in_queue_now ?? 0),
                'avg_wait' => $todayStats->avg_wait ? round($todayStats->avg_wait) : null,
                'completion_pct' => (float) ($todayStats->completion_pct ?? 0),
            ],
            'recent_bookings' => $recentBookings,
        ];
    }

    /**
     * Get chart data for the last N days.
     */
    public function getChartData(string $businessId, int $days = 7): array
    {
        $stats = QueueDailyStat::forBusiness($businessId)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date')
            ->get();

        return [
            'labels' => $stats->pluck('date')->map(fn ($d) => $d->format('d.m'))->toArray(),
            'bookings' => $stats->pluck('total_bookings')->toArray(),
            'completions' => $stats->pluck('completed')->toArray(),
        ];
    }

    /**
     * Calculate and persist daily statistics.
     */
    public function calculateDailyStats(string $businessId, ?string $date = null): QueueDailyStat
    {
        $date = $date ?? now()->toDateString();

        $stats = QueueBooking::forBusiness($businessId)
            ->whereDate('date', $date)
            ->selectRaw("
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_shows,
                AVG(CASE WHEN status = 'completed' AND started_at IS NOT NULL AND confirmed_at IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, confirmed_at, started_at) ELSE NULL END) as avg_wait_time,
                AVG(CASE WHEN status = 'completed' AND completed_at IS NOT NULL AND started_at IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, started_at, completed_at) ELSE NULL END) as avg_service_time
            ")
            ->first();

        // Determine peak hour
        $peakHour = QueueBooking::forBusiness($businessId)
            ->whereDate('date', $date)
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as cnt')
            ->groupBy(DB::raw('HOUR(start_time)'))
            ->orderByDesc('cnt')
            ->value('hour');

        // Determine busiest service
        $busiestServiceId = QueueBooking::forBusiness($businessId)
            ->whereDate('date', $date)
            ->select('service_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('service_id')
            ->orderByDesc('cnt')
            ->value('service_id');

        return QueueDailyStat::updateOrCreate(
            ['business_id' => $businessId, 'date' => $date],
            [
                'total_bookings' => $stats->total_bookings ?? 0,
                'completed' => $stats->completed ?? 0,
                'cancelled' => $stats->cancelled ?? 0,
                'no_shows' => $stats->no_shows ?? 0,
                'avg_wait_time' => $stats->avg_wait_time ?? 0,
                'avg_service_time' => $stats->avg_service_time,
                'peak_hour' => $peakHour,
                'busiest_service_id' => $busiestServiceId,
            ]
        );
    }
}
