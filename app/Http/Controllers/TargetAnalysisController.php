<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\TargetAnalysisService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TargetAnalysisController extends Controller
{
    protected TargetAnalysisService $analysisService;

    public function __construct(TargetAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Display target analysis dashboard
     */
    public function index(Request $request): Response
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (!$businessId) {
            return Inertia::render('Business/TargetAnalysis/Index', [
                'error' => 'Biznes tanlanmagan',
                'analysis' => null,
            ]);
        }

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Sizda bu biznesga kirish huquqi yo\'q');
        }

        $analysis = $this->analysisService->getTargetAnalysis($business);

        return Inertia::render('Business/TargetAnalysis/Index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
            ],
            'analysis' => $analysis,
            'lastUpdated' => now()->format('d.m.Y H:i'),
        ]);
    }

    /**
     * Get analysis data as JSON (for API calls)
     */
    public function getAnalysisData(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');

        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 400);
        }

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $analysis = $this->analysisService->getTargetAnalysis($business);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get Dream Buyer match analysis
     */
    public function getDreamBuyerMatch(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $matchAnalysis = $this->analysisService->analyzeDreamBuyerMatch($business);

        return response()->json([
            'success' => true,
            'data' => $matchAnalysis,
        ]);
    }

    /**
     * Get customer segmentation data
     */
    public function getSegments(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $segments = $this->analysisService->getCustomerSegments($business);

        return response()->json([
            'success' => true,
            'data' => $segments,
        ]);
    }

    /**
     * Get growth trends
     */
    public function getGrowthTrends(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $months = $request->input('months', 6);

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $trends = $this->analysisService->getGrowthTrends($business, $months);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Regenerate AI insights
     */
    public function regenerateInsights(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $overview = $this->analysisService->getOverview($business);
        $segments = $this->analysisService->getCustomerSegments($business);
        $dreamBuyerMatch = $this->analysisService->analyzeDreamBuyerMatch($business);
        $funnel = $this->analysisService->getConversionFunnel($business);

        $insights = $this->analysisService->generateAIInsights($business);

        return response()->json([
            'success' => true,
            'insights' => $insights,
        ]);
    }

    /**
     * Get churn risk customers
     */
    public function getChurnRisk(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $churnRisk = $this->analysisService->getChurnRiskAnalysis($business);

        return response()->json([
            'success' => true,
            'data' => $churnRisk,
        ]);
    }

    /**
     * Export analysis data
     */
    public function export(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $analysis = $this->analysisService->exportAnalysis($business);

        $filename = "target-analysis-{$business->slug}-" . now()->format('Y-m-d') . ".json";

        return response()->json($analysis)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get top performing customers
     */
    public function getTopPerformers(Request $request)
    {
        $businessId = $request->input('business_id') ?? session('current_business_id');
        $limit = $request->input('limit', 10);

        $business = Business::findOrFail($businessId);

        // Check access
        if ($business->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $topPerformers = $this->analysisService->getTopPerformingCustomers($business, $limit);

        return response()->json([
            'success' => true,
            'data' => $topPerformers,
        ]);
    }
}
