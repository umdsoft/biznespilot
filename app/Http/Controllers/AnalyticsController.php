<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalyticsService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    protected SalesAnalyticsService $analyticsService;
    protected ExportService $exportService;

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
     * Main analytics dashboard
     */
    public function index(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'source_id']);

        // Get dashboard metrics
        $dashboardMetrics = $this->analyticsService->getDashboardMetrics(
            $currentBusiness->id,
            $filters
        );

        // Get revenue trends
        $revenueTrends = $this->analyticsService->getRevenueTrends(
            $currentBusiness->id,
            'daily',
            30
        );

        // Get top performers
        $topPerformers = $this->analyticsService->getTopPerformers(
            $currentBusiness->id,
            $filters
        );

        // Get funnel overview
        $funnelData = $this->analyticsService->getFunnelData(
            $currentBusiness->id,
            $filters
        );

        return Inertia::render('Business/Analytics/Dashboard', [
            'metrics' => $dashboardMetrics,
            'revenue_trends' => $revenueTrends,
            'top_performers' => $topPerformers,
            'funnel_summary' => $funnelData['summary'],
            'filters' => $filters,
        ]);
    }

    /**
     * Conversion funnel page
     */
    public function funnel(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $filters = $request->only(['date_from', 'date_to', 'dream_buyer_id', 'offer_id', 'source_id']);

        $funnelData = $this->analyticsService->getFunnelData(
            $currentBusiness->id,
            $filters
        );

        $conversionRates = $this->analyticsService->getConversionRates(
            $currentBusiness->id,
            $filters
        );

        return Inertia::render('Business/Analytics/Funnel', [
            'funnel_data' => $funnelData,
            'conversion_rates' => $conversionRates,
            'filters' => $filters,
        ]);
    }

    /**
     * Performance reports page
     */
    public function performance(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $filters = $request->only(['date_from', 'date_to']);

        $dreamBuyerPerformance = $this->analyticsService->getDreamBuyerPerformance(
            $currentBusiness->id,
            $filters
        );

        $offerPerformance = $this->analyticsService->getOfferPerformance(
            $currentBusiness->id,
            $filters
        );

        $sourceAnalysis = $this->analyticsService->getLeadSourceAnalysis(
            $currentBusiness->id,
            $filters
        );

        return Inertia::render('Business/Analytics/Performance', [
            'dream_buyer_performance' => $dreamBuyerPerformance,
            'offer_performance' => $offerPerformance,
            'source_analysis' => $sourceAnalysis,
            'filters' => $filters,
        ]);
    }

    /**
     * Revenue trends and forecasting page
     */
    public function revenue(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $period = $request->input('period', 'daily');
        $points = $request->input('points', 30);

        $revenueTrends = $this->analyticsService->getRevenueTrends(
            $currentBusiness->id,
            $period,
            $points
        );

        $forecast = $this->analyticsService->forecastRevenue(
            $currentBusiness->id,
            30
        );

        return Inertia::render('Business/Analytics/Revenue', [
            'trends' => $revenueTrends,
            'forecast' => $forecast,
            'period' => $period,
        ]);
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
