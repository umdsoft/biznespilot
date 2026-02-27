<?php

namespace App\Http\Controllers\Api\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Delivery\DeliveryOrderResource;
use App\Services\Bot\Delivery\DeliveryStatsService;
use Illuminate\Http\JsonResponse;

class DeliveryDashboardController extends Controller
{
    public function __construct(
        private DeliveryStatsService $statsService,
    ) {}

    public function index(): JsonResponse
    {
        $businessId = session('current_business_id');

        $dashboard = $this->statsService->getDashboardData($businessId);
        $charts = $this->statsService->getChartData($businessId);
        $statusCounts = $this->statsService->getOrdersByStatus($businessId);

        return response()->json([
            'kpi' => $dashboard['today'],
            'recent_orders' => DeliveryOrderResource::collection($dashboard['recent_orders']),
            'charts' => $charts,
            'status_counts' => $statusCounts,
        ]);
    }
}
