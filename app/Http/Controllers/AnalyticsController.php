<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalyticsService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    protected SalesAnalyticsService $analyticsService;
    protected ExportService $exportService;
    protected int $cacheTTL = 300; // 5 minutes

    public function __construct(
        SalesAnalyticsService $analyticsService,
        ExportService $exportService
    ) {
        $this->analyticsService = $analyticsService;
        $this->exportService = $exportService;
    }

    /**
     * Get current business helper
     */
    protected function getCurrentBusiness()
    {
        return session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();
    }

    /**
     * Main analytics dashboard - LAZY LOADING
     */
    public function index(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'source_id']);

        // Return default values - data will be loaded via API
        return Inertia::render('Business/Analytics/Dashboard', [
            'metrics' => [
                'revenue_growth' => 0,
                'total_revenue' => 0,
                'total_leads' => 0,
                'new_leads' => 0,
                'active_pipeline_deals' => 0,
                'won_deals' => 0,
                'conversion_rate' => 0,
                'avg_deal_size' => 0,
                'pipeline_value' => 0,
            ],
            'revenue_trends' => null,
            'top_performers' => [
                'top_dream_buyer' => null,
                'top_offer' => null,
                'top_source' => null,
            ],
            'funnel_summary' => [
                'total_leads' => 0,
                'won_leads' => 0,
                'active_leads' => 0,
                'overall_conversion_rate' => 0,
            ],
            'filters' => $filters,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get initial analytics data (combined for faster loading)
     */
    public function getInitialData(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'source_id']);
        $filterKey = md5(json_encode($filters));
        $cacheKey = "analytics_initial_{$currentBusiness->id}_{$filterKey}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($currentBusiness, $filters) {
            return [
                'metrics' => $this->analyticsService->getDashboardMetrics($currentBusiness->id, $filters),
                'revenue_trends' => $this->analyticsService->getRevenueTrends($currentBusiness->id, 'daily', 30),
                'top_performers' => $this->analyticsService->getTopPerformers($currentBusiness->id, $filters),
                'funnel_summary' => $this->analyticsService->getFunnelData($currentBusiness->id, $filters)['summary'] ?? null,
            ];
        });

        return response()->json($data);
    }

    /**
     * Conversion funnel page - LAZY LOADING
     */
    public function funnel(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'offer_id', 'source_id']);

        return Inertia::render('Business/Analytics/Funnel', [
            'funnel_data' => [
                'funnel_stages' => [],
                'summary' => [
                    'total_leads' => 0,
                    'won_leads' => 0,
                    'lost_leads' => 0,
                    'active_leads' => 0,
                    'overall_conversion_rate' => 0,
                    'win_rate' => 0,
                ],
            ],
            'conversion_rates' => [],
            'filters' => $filters,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get funnel page data
     */
    public function getFunnelPageData(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'offer_id', 'source_id']);
        $filterKey = md5(json_encode($filters));
        $cacheKey = "analytics_funnel_{$currentBusiness->id}_{$filterKey}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($currentBusiness, $filters) {
            return [
                'funnel_data' => $this->analyticsService->getFunnelData($currentBusiness->id, $filters),
                'conversion_rates' => $this->analyticsService->getConversionRates($currentBusiness->id, $filters),
            ];
        });

        return response()->json($data);
    }

    /**
     * Performance reports page - LAZY LOADING
     */
    public function performance(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $filters = $request->only(['date_from', 'date_to']);

        return Inertia::render('Business/Analytics/Performance', [
            'dream_buyer_performance' => [],
            'offer_performance' => [],
            'source_analysis' => [],
            'filters' => $filters,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get performance page data
     */
    public function getPerformancePageData(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $filters = $request->only(['date_from', 'date_to']);
        $filterKey = md5(json_encode($filters));
        $cacheKey = "analytics_performance_{$currentBusiness->id}_{$filterKey}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($currentBusiness, $filters) {
            return [
                'dream_buyer_performance' => $this->analyticsService->getDreamBuyerPerformance($currentBusiness->id, $filters),
                'offer_performance' => $this->analyticsService->getOfferPerformance($currentBusiness->id, $filters),
                'source_analysis' => $this->analyticsService->getLeadSourceAnalysis($currentBusiness->id, $filters),
            ];
        });

        return response()->json($data);
    }

    /**
     * Revenue trends and forecasting page - LAZY LOADING
     */
    public function revenue(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $period = $request->input('period', 'daily');
        $points = $request->input('points', 30);

        return Inertia::render('Business/Analytics/Revenue', [
            'trends' => [],
            'forecast' => [],
            'period' => $period,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get revenue page data
     */
    public function getRevenuePageData(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $period = $request->input('period', 'daily');
        $points = $request->input('points', 30);
        $cacheKey = "analytics_revenue_{$currentBusiness->id}_{$period}_{$points}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($currentBusiness, $period, $points) {
            return [
                'trends' => $this->analyticsService->getRevenueTrends($currentBusiness->id, $period, $points),
                'forecast' => $this->analyticsService->forecastRevenue($currentBusiness->id, 30),
            ];
        });

        return response()->json($data);
    }

    /**
     * AJAX: Get funnel data
     */
    public function getFunnelData(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'offer_id', 'source_id']);

        $funnelData = $this->analyticsService->getFunnelData(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $funnelData,
        ]);
    }

    /**
     * AJAX: Get Dream Buyer performance
     */
    public function getDreamBuyerPerformance(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to']);

        $performance = $this->analyticsService->getDreamBuyerPerformance(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    /**
     * AJAX: Get Offer performance
     */
    public function getOfferPerformance(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to']);

        $performance = $this->analyticsService->getOfferPerformance(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }

    /**
     * AJAX: Get lead source analysis
     */
    public function getLeadSourceAnalysis(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to']);

        $analysis = $this->analyticsService->getLeadSourceAnalysis(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * AJAX: Get revenue trends
     */
    public function getRevenueTrends(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $period = $request->input('period', 'daily');
        $points = $request->input('points', 30);

        $trends = $this->analyticsService->getRevenueTrends(
            $currentBusiness->id,
            $period,
            $points
        );

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * AJAX: Get revenue forecast
     */
    public function getRevenueForecast(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $forecastDays = $request->input('forecast_days', 30);

        $forecast = $this->analyticsService->forecastRevenue(
            $currentBusiness->id,
            $forecastDays
        );

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }

    /**
     * AJAX: Get conversion rates
     */
    public function getConversionRates(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to']);

        $rates = $this->analyticsService->getConversionRates(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * AJAX: Get dashboard metrics
     */
    public function getDashboardMetrics(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to']);

        $metrics = $this->analyticsService->getDashboardMetrics(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * AJAX: Get top performers
     */
    public function getTopPerformers(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to']);

        $performers = $this->analyticsService->getTopPerformers(
            $currentBusiness->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $performers,
        ]);
    }

    /**
     * Export analytics report to PDF
     */
    public function exportPDF(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to', 'report_type']);
        $reportType = $filters['report_type'] ?? 'full';

        // Get all analytics data
        $data = [
            'business' => $currentBusiness,
            'metrics' => $this->analyticsService->getDashboardMetrics($currentBusiness->id, $filters),
            'funnel' => $this->analyticsService->getFunnelData($currentBusiness->id, $filters),
            'dream_buyer_performance' => $this->analyticsService->getDreamBuyerPerformance($currentBusiness->id, $filters),
            'offer_performance' => $this->analyticsService->getOfferPerformance($currentBusiness->id, $filters),
            'source_analysis' => $this->analyticsService->getLeadSourceAnalysis($currentBusiness->id, $filters),
            'filters' => $filters,
        ];

        // Generate PDF
        try {
            $filename = $this->exportService->generatePDF($currentBusiness, $data, $reportType);

            return response()->download(
                storage_path('app/exports/' . $filename),
                $filename,
                ['Content-Type' => 'application/pdf']
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PDF yaratishda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export analytics report to Excel
     */
    public function exportExcel(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        $filters = $request->only(['date_from', 'date_to', 'report_type']);
        $reportType = $filters['report_type'] ?? 'full';

        // Get all analytics data based on report type
        $data = [
            'business' => $currentBusiness,
            'metrics' => $this->analyticsService->getDashboardMetrics($currentBusiness->id, $filters),
            'funnel' => $this->analyticsService->getFunnelData($currentBusiness->id, $filters),
            'dream_buyer_performance' => $this->analyticsService->getDreamBuyerPerformance($currentBusiness->id, $filters),
            'offer_performance' => $this->analyticsService->getOfferPerformance($currentBusiness->id, $filters),
            'source_analysis' => $this->analyticsService->getLeadSourceAnalysis($currentBusiness->id, $filters),
            'filters' => $filters,
        ];

        // For revenue report, add trends and forecast
        if ($reportType === 'revenue') {
            $data['trends'] = $this->analyticsService->getRevenueTrends($currentBusiness->id, 'daily', 30);
            $data['forecast'] = $this->analyticsService->forecastRevenue($currentBusiness->id, 30);
        }

        // Generate Excel
        try {
            $filename = $this->exportService->generateExcel($currentBusiness, $data, $reportType);

            return response()->download(
                storage_path('app/exports/' . $filename),
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Excel yaratishda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }
}
