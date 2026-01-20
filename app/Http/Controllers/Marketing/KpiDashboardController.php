<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Services\MarketingDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KpiDashboardController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        private MarketingDashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        $dashboardData = $this->dashboardService->getDashboardData($business, $user);

        return Inertia::render('Marketing/KpiDashboard/Index', [
            'overview' => $dashboardData['overview'],
            'kpiSummary' => $dashboardData['kpi_summary'],
            'targets' => $dashboardData['targets'],
            'channels' => $dashboardData['channels'],
            'alerts' => $dashboardData['alerts'],
            'leaderboard' => $dashboardData['leaderboard'],
            'bonusPreview' => $dashboardData['bonus_preview'],
            'trends' => $dashboardData['trends'],
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function realtime(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        return response()->json(
            $this->dashboardService->getRealTimeMetrics($business)
        );
    }

    public function comparison(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $compareWith = $request->get('compare_with', 'previous_month');

        return response()->json(
            $this->dashboardService->getComparisonData($business, $compareWith)
        );
    }

    public function teamPerformance(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        return response()->json([
            'team' => $this->dashboardService->getTeamPerformance($business),
        ]);
    }

    public function refresh(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $this->dashboardService->clearDashboardCache($business);

        return response()->json(['success' => true, 'message' => 'Dashboard yangilandi']);
    }
}
