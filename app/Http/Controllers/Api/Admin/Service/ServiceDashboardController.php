<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Service\ServiceRequestResource;
use App\Services\Bot\Service\ServiceStatsService;
use Illuminate\Http\JsonResponse;

class ServiceDashboardController extends Controller
{
    public function __construct(
        private ServiceStatsService $statsService,
    ) {}

    public function index(): JsonResponse
    {
        $businessId = session('current_business_id');

        $dashboard = $this->statsService->getDashboardData($businessId);
        $charts = $this->statsService->getChartData($businessId);
        $statusCounts = $this->statsService->getRequestsByStatus($businessId);

        return response()->json([
            'kpi' => $dashboard['kpi'],
            'recent_requests' => ServiceRequestResource::collection($dashboard['recent_requests']),
            'charts' => $charts,
            'status_counts' => $statusCounts,
        ]);
    }
}
