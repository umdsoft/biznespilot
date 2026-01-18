<?php

namespace App\Http\Controllers;

use App\Services\Algorithm\DataAccuracyEngine;
use App\Services\Algorithm\ModuleAnalyzer;
use App\Services\Algorithm\NextStepPredictor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlgorithmController extends Controller
{
    protected ModuleAnalyzer $moduleAnalyzer;

    protected NextStepPredictor $nextStepPredictor;

    protected DataAccuracyEngine $dataAccuracyEngine;

    public function __construct(
        ModuleAnalyzer $moduleAnalyzer,
        NextStepPredictor $nextStepPredictor,
        DataAccuracyEngine $dataAccuracyEngine
    ) {
        $this->moduleAnalyzer = $moduleAnalyzer;
        $this->nextStepPredictor = $nextStepPredictor;
        $this->dataAccuracyEngine = $dataAccuracyEngine;
    }

    /**
     * Get full module analysis
     */
    public function analyzeModules(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $analysis = $this->moduleAnalyzer->analyzeAllModules($business);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Get single module analysis
     */
    public function analyzeModule(string $module): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $methodMap = [
            'sales' => 'analyzeSalesModule',
            'marketing' => 'analyzeMarketingModule',
            'customers' => 'analyzeCustomerModule',
            'content' => 'analyzeContentModule',
            'funnel' => 'analyzeFunnelModule',
        ];

        if (! isset($methodMap[$module])) {
            return response()->json(['error' => 'Noma\'lum modul'], 400);
        }

        $method = $methodMap[$module];
        $analysis = $this->moduleAnalyzer->$method($business);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Get next step predictions
     */
    public function predictNextSteps(Request $request): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $limit = $request->input('limit', 5);
        $predictions = $this->nextStepPredictor->predictNextSteps($business, $limit);

        return response()->json([
            'success' => true,
            'data' => $predictions,
        ]);
    }

    /**
     * Get quick wins
     */
    public function getQuickWins(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $quickWins = $this->nextStepPredictor->getQuickWins($business);

        return response()->json([
            'success' => true,
            'data' => $quickWins,
        ]);
    }

    /**
     * Get critical actions
     */
    public function getCriticalActions(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $criticalActions = $this->nextStepPredictor->getCriticalActions($business);

        return response()->json([
            'success' => true,
            'data' => $criticalActions,
        ]);
    }

    /**
     * Get data accuracy audit
     */
    public function auditDataAccuracy(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $audit = $this->dataAccuracyEngine->auditDataAccuracy($business);

        return response()->json([
            'success' => true,
            'data' => $audit,
        ]);
    }

    /**
     * Get complete algorithm dashboard data
     * Combines all algorithm services for dashboard view
     */
    public function dashboard(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        // Get all data in parallel-like manner (cached)
        $moduleAnalysis = $this->moduleAnalyzer->analyzeAllModules($business);
        $predictions = $this->nextStepPredictor->predictNextSteps($business, 5);
        $quickWins = $this->nextStepPredictor->getQuickWins($business, 3);
        $dataAudit = $this->dataAccuracyEngine->auditDataAccuracy($business);

        return response()->json([
            'success' => true,
            'data' => [
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                ],
                'overview' => [
                    'health_score' => $moduleAnalysis['overall_score'],
                    'data_accuracy' => $dataAudit['overall_accuracy'],
                    'data_quality' => $dataAudit['quality_score'],
                    'prediction_confidence' => $predictions['confidence'],
                ],
                'modules' => $moduleAnalysis['modules'],
                'predictions' => $predictions['predictions'],
                'quick_wins' => $quickWins,
                'cross_module_insights' => $moduleAnalysis['cross_module_insights'],
                'data_recommendations' => $dataAudit['recommendations'],
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Inertia view for algorithm dashboard
     */
    public function showDashboard()
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return redirect()->route('welcome.index');
        }

        // Get all algorithm data
        $moduleAnalysis = $this->moduleAnalyzer->analyzeAllModules($business);
        $predictions = $this->nextStepPredictor->predictNextSteps($business, 5);
        $quickWins = $this->nextStepPredictor->getQuickWins($business, 3);
        $dataAudit = $this->dataAccuracyEngine->auditDataAccuracy($business);

        return Inertia::render('Algorithm/Dashboard', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'overview' => [
                'health_score' => $moduleAnalysis['overall_score'],
                'data_accuracy' => $dataAudit['overall_accuracy'],
                'data_quality' => $dataAudit['quality_score'],
                'prediction_confidence' => $predictions['confidence'],
            ],
            'modules' => $moduleAnalysis['modules'],
            'predictions' => $predictions['predictions'],
            'quick_wins' => $quickWins,
            'crossModuleInsights' => $moduleAnalysis['cross_module_insights'],
            'dataRecommendations' => $dataAudit['recommendations'],
        ]);
    }

    /**
     * Refresh algorithm cache
     */
    public function refreshCache(): JsonResponse
    {
        $business = auth()->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        // Invalidate caches
        $this->moduleAnalyzer->invalidateAllCaches();

        // Regenerate data
        $moduleAnalysis = $this->moduleAnalyzer->analyzeAllModules($business);
        $predictions = $this->nextStepPredictor->predictNextSteps($business, 5);

        return response()->json([
            'success' => true,
            'message' => 'Kesh yangilandi',
            'data' => [
                'health_score' => $moduleAnalysis['overall_score'],
                'predictions_count' => count($predictions['predictions']),
            ],
        ]);
    }
}
