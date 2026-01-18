<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreCompetitorRequest;
use App\Http\Requests\Onboarding\StoreHypothesisRequest;
use App\Http\Requests\Onboarding\StoreProblemRequest;
use App\Http\Requests\Onboarding\UpdateBusinessBasicRequest;
use App\Http\Requests\Onboarding\UpdateBusinessDetailsRequest;
use App\Http\Requests\Onboarding\UpdateDreamBuyerRequest;
use App\Http\Requests\Onboarding\UpdateMaturityAssessmentRequest;
use App\Http\Resources\IndustryResource;
use App\Http\Resources\StepResource;
use App\Models\Business;
use App\Models\BusinessMaturityAssessment;
use App\Models\BusinessProblem;
use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\Industry;
use App\Models\MarketingHypothesis;
use App\Models\MarketingMetrics;
use App\Models\SalesMetrics;
use App\Models\StepDefinition;
use App\Services\MaturityCalculatorService;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService,
        private MaturityCalculatorService $maturityCalculator
    ) {}

    /**
     * Get onboarding progress
     */
    public function progress(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $progress = $this->onboardingService->calculateProgress($business);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Initialize onboarding
     */
    public function initialize(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $progress = $this->onboardingService->initializeOnboarding($business);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding boshlandi',
            'data' => $this->onboardingService->calculateProgress($business),
        ]);
    }

    /**
     * Get all step definitions
     */
    public function steps(): JsonResponse
    {
        $steps = StepDefinition::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => StepResource::collection($steps),
        ]);
    }

    /**
     * Get step detail with validation
     */
    public function stepDetail(Request $request, string $stepCode): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $stepDef = StepDefinition::where('code', $stepCode)->first();

        if (! $stepDef) {
            return response()->json([
                'success' => false,
                'message' => 'Step topilmadi',
            ], 404);
        }

        $validation = $this->onboardingService->validateStep($business, $stepCode);
        $isLocked = $this->onboardingService->isStepLocked($business, $stepDef);

        // Get related data based on step
        $data = $this->getStepData($business, $stepCode);

        return response()->json([
            'success' => true,
            'data' => [
                'step' => new StepResource($stepDef),
                'validation' => $validation,
                'is_locked' => $isLocked,
                'data' => $data,
            ],
        ]);
    }

    /**
     * Get industries list
     */
    public function industries(): JsonResponse
    {
        $industries = Industry::parents()->active()->ordered()->with('children')->get();

        return response()->json([
            'success' => true,
            'data' => IndustryResource::collection($industries),
        ]);
    }

    /**
     * Update business basic info (Step 1)
     */
    public function updateBusinessBasic(UpdateBusinessBasicRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $business->update($request->validated());

        $this->onboardingService->updateStepProgress($business, 'business_basic');

        return response()->json([
            'success' => true,
            'message' => 'Asosiy ma\'lumotlar saqlandi',
            'data' => [
                'business' => $business->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Update business details (Step 2)
     */
    public function updateBusinessDetails(UpdateBusinessDetailsRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $business->update($request->validated());

        $this->onboardingService->updateStepProgress($business, 'business_details');

        return response()->json([
            'success' => true,
            'message' => 'Qo\'shimcha ma\'lumotlar saqlandi',
            'data' => [
                'business' => $business->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Update maturity assessment (Step 3)
     */
    public function updateMaturityAssessment(UpdateMaturityAssessmentRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $assessment = $business->maturityAssessment;

        if (! $assessment) {
            $assessment = BusinessMaturityAssessment::create([
                'business_id' => $business->id,
                ...$request->validated(),
            ]);
        } else {
            $assessment->update($request->validated());
        }

        // Calculate maturity score
        $maturityResult = $this->maturityCalculator->calculateScore($business);

        $this->onboardingService->updateStepProgress($business, 'business_maturity');

        return response()->json([
            'success' => true,
            'message' => 'Biznes baholash saqlandi',
            'data' => [
                'assessment' => $assessment->fresh(),
                'maturity' => $maturityResult,
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get maturity score
     */
    public function maturityScore(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $score = $this->maturityCalculator->calculateScore($business);
        $recommendations = $this->maturityCalculator->getRecommendations($business);
        $comparison = $this->maturityCalculator->getIndustryComparison($business);

        return response()->json([
            'success' => true,
            'data' => [
                'score' => $score,
                'recommendations' => $recommendations,
                'industry_comparison' => $comparison,
            ],
        ]);
    }

    /**
     * Store problem (Step 6)
     */
    public function storeProblem(StoreProblemRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $problem = BusinessProblem::create([
            'business_id' => $business->id,
            ...$request->validated(),
        ]);

        $this->onboardingService->updateStepProgress($business, 'framework_problem');

        return response()->json([
            'success' => true,
            'message' => 'Muammo qo\'shildi',
            'data' => [
                'problem' => $problem,
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ], 201);
    }

    /**
     * Update problem
     */
    public function updateProblem(StoreProblemRequest $request, BusinessProblem $problem): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if ($problem->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $problem->update($request->validated());

        $this->onboardingService->updateStepProgress($business, 'framework_problem');

        return response()->json([
            'success' => true,
            'message' => 'Muammo yangilandi',
            'data' => [
                'problem' => $problem->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Delete problem
     */
    public function deleteProblem(Request $request, BusinessProblem $problem): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if ($problem->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $problem->delete();

        $this->onboardingService->updateStepProgress($business, 'framework_problem');

        return response()->json([
            'success' => true,
            'message' => 'Muammo o\'chirildi',
            'data' => [
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get problems list
     */
    public function problems(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $problems = $business->problems()->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $problems,
        ]);
    }

    /**
     * Update dream buyer (Step 7)
     */
    public function updateDreamBuyer(UpdateDreamBuyerRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $dreamBuyer = DreamBuyer::firstOrCreate(
            ['business_id' => $business->id, 'is_primary' => true],
            ['name' => 'Asosiy Dream Buyer']
        );

        $dreamBuyer->update($request->validated());

        $this->onboardingService->updateStepProgress($business, 'framework_dream_buyer');

        return response()->json([
            'success' => true,
            'message' => 'Dream Buyer saqlandi',
            'data' => [
                'dream_buyer' => $dreamBuyer->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get dream buyer
     */
    public function dreamBuyer(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('is_primary', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $dreamBuyer,
        ]);
    }

    /**
     * Store competitor (Step 8)
     */
    public function storeCompetitor(StoreCompetitorRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $competitor = Competitor::create([
            'business_id' => $business->id,
            ...$request->validated(),
        ]);

        $this->onboardingService->updateStepProgress($business, 'framework_competitors');

        return response()->json([
            'success' => true,
            'message' => 'Raqobatchi qo\'shildi',
            'data' => [
                'competitor' => $competitor,
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ], 201);
    }

    /**
     * Update competitor
     */
    public function updateCompetitor(StoreCompetitorRequest $request, Competitor $competitor): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if ($competitor->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $competitor->update($request->validated());

        $this->onboardingService->updateStepProgress($business, 'framework_competitors');

        return response()->json([
            'success' => true,
            'message' => 'Raqobatchi yangilandi',
            'data' => [
                'competitor' => $competitor->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Delete competitor
     */
    public function deleteCompetitor(Request $request, Competitor $competitor): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if ($competitor->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $competitor->delete();

        $this->onboardingService->updateStepProgress($business, 'framework_competitors');

        return response()->json([
            'success' => true,
            'message' => 'Raqobatchi o\'chirildi',
            'data' => [
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get competitors list
     */
    public function competitors(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $competitors = $business->competitors()->where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $competitors,
        ]);
    }

    /**
     * Store hypothesis (Step 9)
     */
    public function storeHypothesis(StoreHypothesisRequest $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $hypothesis = MarketingHypothesis::create([
            'business_id' => $business->id,
            ...$request->validated(),
        ]);

        $this->onboardingService->updateStepProgress($business, 'framework_hypotheses');

        return response()->json([
            'success' => true,
            'message' => 'Gipoteza qo\'shildi',
            'data' => [
                'hypothesis' => $hypothesis,
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ], 201);
    }

    /**
     * Update hypothesis
     */
    public function updateHypothesis(StoreHypothesisRequest $request, MarketingHypothesis $hypothesis): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if ($hypothesis->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $hypothesis->update($request->validated());

        $this->onboardingService->updateStepProgress($business, 'framework_hypotheses');

        return response()->json([
            'success' => true,
            'message' => 'Gipoteza yangilandi',
            'data' => [
                'hypothesis' => $hypothesis->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Delete hypothesis
     */
    public function deleteHypothesis(Request $request, MarketingHypothesis $hypothesis): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if ($hypothesis->business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ruxsat yo\'q',
            ], 403);
        }

        $hypothesis->delete();

        $this->onboardingService->updateStepProgress($business, 'framework_hypotheses');

        return response()->json([
            'success' => true,
            'message' => 'Gipoteza o\'chirildi',
            'data' => [
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get hypotheses list
     */
    public function hypotheses(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $hypotheses = $business->hypotheses()->get();

        return response()->json([
            'success' => true,
            'data' => $hypotheses,
        ]);
    }

    /**
     * Start Phase 2
     */
    public function startPhase2(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $this->onboardingService->canStartPhase2($business)) {
            return response()->json([
                'success' => false,
                'message' => 'Faza 1 to\'liq yakunlanmagan',
            ], 422);
        }

        $this->onboardingService->startPhase2($business);

        return response()->json([
            'success' => true,
            'message' => 'Faza 2 boshlandi',
            'data' => $this->onboardingService->calculateProgress($business),
        ]);
    }

    // ==================== SALES METRICS ====================

    /**
     * Get sales metrics
     */
    public function salesMetrics(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $metrics = $business->salesMetrics;

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Update sales metrics
     */
    public function updateSalesMetrics(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $validated = $request->validate([
            'monthly_lead_volume' => 'nullable|string|max:50',
            'lead_sources' => 'nullable|array',
            'lead_quality' => 'nullable|string|max:20',
            'monthly_sales_volume' => 'nullable|string|max:50',
            'avg_deal_size' => 'nullable|string|max:255',
            'sales_cycle' => 'nullable|string|max:50',
            'sales_team_type' => 'nullable|string|max:50',
            'sales_tools' => 'nullable|array',
            'sales_challenges' => 'nullable|string',
        ]);

        $metrics = $business->salesMetrics;
        $isNew = ! $metrics;

        if (! $metrics) {
            $metrics = SalesMetrics::create([
                'business_id' => $business->id,
                ...$validated,
            ]);
            // Tarixga dastlabki yozuv
            $metrics->saveToHistory('initial', 'Dastlabki ma\'lumotlar kiritildi');
        } else {
            // Tarixga oldingi holatni saqlash
            $metrics->saveToHistory('update');

            // Yangilash
            $metrics->update($validated);
        }

        // Update step progress
        $this->onboardingService->updateStepProgress($business, 'kpi_sales');

        return response()->json([
            'success' => true,
            'message' => 'Sotuv ko\'rsatkichlari saqlandi',
            'data' => [
                'metrics' => $metrics->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get sales metrics history
     */
    public function salesMetricsHistory(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $history = $business->salesMetrics?->history()
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get() ?? collect();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    // ==================== MARKETING METRICS ====================

    /**
     * Get marketing metrics
     */
    public function marketingMetrics(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $metrics = $business->marketingMetrics;

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Update marketing metrics
     */
    public function updateMarketingMetrics(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $validated = $request->validate([
            'monthly_budget' => 'nullable|string|max:255',
            'ad_spend' => 'nullable|string|max:255',
            'website_purpose' => 'nullable|string|max:50',
            'monthly_visits' => 'nullable|integer|min:0',
            'website_conversion' => 'nullable|numeric|min:0|max:100',
            'active_channels' => 'nullable|array',
            'best_channel' => 'nullable|string|max:50',
            'top_lead_channel' => 'nullable|string|max:50',
            'instagram_followers' => 'nullable|integer|min:0',
            'telegram_subscribers' => 'nullable|integer|min:0',
            'facebook_followers' => 'nullable|integer|min:0',
            'roi_tracking_level' => 'nullable|string|max:20',
            'marketing_roi' => 'nullable|numeric',
            'content_activities' => 'nullable|array',
            'marketing_challenges' => 'nullable|string',
        ]);

        $metrics = $business->marketingMetrics;
        $isNew = ! $metrics;

        if (! $metrics) {
            $metrics = MarketingMetrics::create([
                'business_id' => $business->id,
                ...$validated,
            ]);
            // Tarixga dastlabki yozuv
            $metrics->saveToHistory('initial', 'Dastlabki ma\'lumotlar kiritildi');
        } else {
            // Tarixga oldingi holatni saqlash
            $metrics->saveToHistory('update');

            // Yangilash
            $metrics->update($validated);
        }

        // Update step progress
        $this->onboardingService->updateStepProgress($business, 'kpi_marketing');

        return response()->json([
            'success' => true,
            'message' => 'Marketing ko\'rsatkichlari saqlandi',
            'data' => [
                'metrics' => $metrics->fresh(),
                'progress' => $this->onboardingService->calculateProgress($business),
            ],
        ]);
    }

    /**
     * Get marketing metrics history
     */
    public function marketingMetricsHistory(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes tanlanmagan',
            ], 404);
        }

        $history = $business->marketingMetrics?->history()
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get() ?? collect();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get step data based on step code
     */
    private function getStepData(Business $business, string $stepCode): array
    {
        return match ($stepCode) {
            'business_basic', 'business_details' => [
                'business' => $business,
                'industries' => Industry::parents()->active()->ordered()->with('children')->get(),
            ],
            'business_maturity' => [
                'maturity' => $business->maturityAssessment,
            ],
            'framework_problem' => [
                'problems' => $business->problems()->active()->ordered()->get(),
            ],
            'framework_dream_buyer' => [
                'dream_buyer' => DreamBuyer::where('business_id', $business->id)
                    ->where('is_primary', true)
                    ->first(),
            ],
            'framework_competitors' => [
                'competitors' => $business->competitors()->where('is_active', true)->get(),
            ],
            'framework_hypotheses' => [
                'hypotheses' => $business->hypotheses()->get(),
            ],
            'integration_instagram', 'integration_telegram', 'integration_amocrm', 'integration_google_ads' => [
                'integrations' => $business->integrations()->get(),
            ],
            default => [],
        };
    }
}
