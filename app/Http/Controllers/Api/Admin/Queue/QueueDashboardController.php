<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Queue\QueueBookingResource;
use App\Models\Bot\Queue\QueueBooking;
use App\Services\Bot\Queue\QueueStatsService;
use Illuminate\Http\JsonResponse;

class QueueDashboardController extends Controller
{
    public function __construct(
        private QueueStatsService $statsService,
    ) {}

    public function index(): JsonResponse
    {
        $businessId = session('current_business_id');

        $dashboard = $this->statsService->getDashboardData($businessId);
        $charts = $this->statsService->getChartData($businessId);

        $todayBookings = QueueBooking::forBusiness($businessId)
            ->where('date', now()->toDateString())
            ->whereIn('status', [
                QueueBooking::STATUS_PENDING,
                QueueBooking::STATUS_CONFIRMED,
                QueueBooking::STATUS_IN_PROGRESS,
            ])
            ->with(['service', 'branch', 'specialist'])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'kpi' => $dashboard['kpi'],
            'today_queue' => QueueBookingResource::collection($todayBookings),
            'recent_bookings' => QueueBookingResource::collection($dashboard['recent_bookings']),
            'charts' => $charts,
        ]);
    }
}
