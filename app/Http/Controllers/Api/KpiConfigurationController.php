<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessKpiConfiguration;
use App\Models\KpiTemplate;
use App\Services\KpiMatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KpiConfigurationController extends Controller
{
    protected $kpiMatcher;

    public function __construct(KpiMatcherService $kpiMatcher)
    {
        $this->kpiMatcher = $kpiMatcher;
    }

    /**
     * Get KPI configuration for a business
     */
    public function show(int $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $configuration = $business->kpiConfiguration;
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $kpiTemplates = $configuration->getKpiTemplates();
        $kpisByPriority = $configuration->getKpisByPriority();

        return response()->json([
            'success' => true,
            'data' => [
                'configuration' => $configuration,
                'kpi_templates' => $kpiTemplates,
                'kpis_by_priority' => $kpisByPriority,
                'summary' => $configuration->getSummary(),
            ],
        ]);
    }

    /**
     * Generate recommended KPIs for a business
     */
    public function generateRecommendations(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'primary_goal' => 'required|string|in:revenue_growth,customer_acquisition,customer_retention,profitability,brand_awareness,operational_efficiency,market_expansion,customer_satisfaction',
            'secondary_goals' => 'nullable|array',
            'secondary_goals.*' => 'string|in:revenue_growth,customer_acquisition,customer_retention,profitability,brand_awareness,operational_efficiency,market_expansion,customer_satisfaction',
            'preferences' => 'nullable|array',
            'preferences.max_kpis' => 'nullable|integer|min:5|max:30',
            'preferences.min_kpis' => 'nullable|integer|min:3|max:15',
            'preferences.preferred_categories' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $recommendations = $this->kpiMatcher->generateRecommendedKpis(
            $business,
            $request->input('primary_goal'),
            $request->input('secondary_goals', []),
            $request->input('preferences', [])
        );

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * Create or update KPI configuration
     */
    public function store(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'primary_goal' => 'required|string',
            'secondary_goals' => 'nullable|array',
            'selected_kpis' => 'nullable|array',
            'selected_kpis.*' => 'string|exists:kpi_templates,kpi_code',
            'kpi_priorities' => 'nullable|array',
            'kpi_weights' => 'nullable|array',
            'preferences' => 'nullable|array',
            'auto_generate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        // Auto-generate if requested or no KPIs selected
        if ($request->input('auto_generate', false) || ! $request->has('selected_kpis')) {
            $configuration = $this->kpiMatcher->createConfiguration(
                $business,
                $request->input('primary_goal'),
                $request->input('secondary_goals', []),
                $request->input('preferences', [])
            );
        } else {
            // Manual configuration
            $configuration = BusinessKpiConfiguration::updateOrCreate(
                ['business_id' => $businessId],
                [
                    'industry_code' => $business->industry_code ?? 'all',
                    'sub_category' => $business->sub_category,
                    'business_size' => $business->business_size ?? 'micro',
                    'business_maturity' => $business->business_maturity ?? 'startup',
                    'primary_goal' => $request->input('primary_goal'),
                    'secondary_goals' => $request->input('secondary_goals', []),
                    'selected_kpis' => $request->input('selected_kpis'),
                    'kpi_priorities' => $request->input('kpi_priorities', []),
                    'kpi_weights' => $request->input('kpi_weights', []),
                    'is_auto_generated' => false,
                    'customized_by_user' => true,
                    'status' => 'draft',
                ]
            );

            $configuration->updateKpiCounts();
            $configuration->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'KPI configuration created successfully',
            'data' => [
                'configuration' => $configuration,
                'summary' => $configuration->getSummary(),
            ],
        ], 201);
    }

    /**
     * Update KPI configuration
     */
    public function update(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'primary_goal' => 'nullable|string',
            'secondary_goals' => 'nullable|array',
            'selected_kpis' => 'nullable|array',
            'kpi_priorities' => 'nullable|array',
            'kpi_weights' => 'nullable|array',
            'notification_settings' => 'nullable|array',
            'user_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->update($request->only([
            'primary_goal',
            'secondary_goals',
            'selected_kpis',
            'kpi_priorities',
            'kpi_weights',
            'notification_settings',
            'user_notes',
        ]));

        $configuration->customized_by_user = true;
        $configuration->updateKpiCounts();
        $configuration->save();

        return response()->json([
            'success' => true,
            'message' => 'KPI configuration updated successfully',
            'data' => [
                'configuration' => $configuration,
                'summary' => $configuration->getSummary(),
            ],
        ]);
    }

    /**
     * Add KPI to configuration
     */
    public function addKpi(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kpi_code' => 'required|string|exists:kpi_templates,kpi_code',
            'priority' => 'nullable|string|in:critical,high,medium,low',
            'weight' => 'nullable|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->addKpi(
            $request->input('kpi_code'),
            $request->input('priority'),
            $request->input('weight')
        );
        $configuration->save();

        return response()->json([
            'success' => true,
            'message' => 'KPI added successfully',
            'data' => [
                'configuration' => $configuration,
            ],
        ]);
    }

    /**
     * Remove KPI from configuration
     */
    public function removeKpi(Request $request, int $businessId, string $kpiCode): JsonResponse
    {
        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->removeKpi($kpiCode);
        $configuration->save();

        return response()->json([
            'success' => true,
            'message' => 'KPI removed successfully',
            'data' => [
                'configuration' => $configuration,
            ],
        ]);
    }

    /**
     * Update KPI priority
     */
    public function updateKpiPriority(Request $request, int $businessId, string $kpiCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'required|string|in:critical,high,medium,low',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->updateKpiPriority($kpiCode, $request->input('priority'));
        $configuration->save();

        return response()->json([
            'success' => true,
            'message' => 'KPI priority updated successfully',
            'data' => [
                'configuration' => $configuration,
            ],
        ]);
    }

    /**
     * Update KPI weight
     */
    public function updateKpiWeight(Request $request, int $businessId, string $kpiCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'weight' => 'required|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->updateKpiWeight($kpiCode, $request->input('weight'));
        $configuration->save();

        return response()->json([
            'success' => true,
            'message' => 'KPI weight updated successfully',
            'data' => [
                'configuration' => $configuration,
            ],
        ]);
    }

    /**
     * Activate configuration
     */
    public function activate(int $businessId): JsonResponse
    {
        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->activate();

        return response()->json([
            'success' => true,
            'message' => 'KPI configuration activated successfully',
            'data' => [
                'configuration' => $configuration,
            ],
        ]);
    }

    /**
     * Pause configuration
     */
    public function pause(int $businessId): JsonResponse
    {
        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $configuration->pause();

        return response()->json([
            'success' => true,
            'message' => 'KPI configuration paused successfully',
            'data' => [
                'configuration' => $configuration,
            ],
        ]);
    }

    /**
     * Get all available KPI templates
     */
    public function getAvailableKpis(int $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $industryCode = $business->industry_code ?? 'all';
        $subCategory = $business->sub_category ?? null;

        $kpis = KpiTemplate::where('is_active', true)
            ->where(function ($query) use ($industryCode, $subCategory) {
                $query->where('is_universal', true)
                    ->orWhere(function ($q) use ($industryCode, $subCategory) {
                        // SECURITY FIX: Use whereJsonContains to prevent SQL injection
                        $q->where(function ($jsonQuery) use ($industryCode) {
                            $jsonQuery->whereJsonContains('applicable_industries', $industryCode)
                                ->orWhereJsonContains('applicable_industries', 'all');
                        });

                        if ($subCategory) {
                            $q->where(function ($sq) use ($subCategory) {
                                $sq->whereNull('applicable_subcategories')
                                    // SECURITY FIX: Use whereJsonContains to prevent SQL injection
                                    ->orWhereJsonContains('applicable_subcategories', $subCategory);
                            });
                        }
                    });
            })
            ->orderBy('priority_level')
            ->orderBy('category')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'data' => [
                'kpis_by_category' => $kpis,
                'total_count' => $kpis->flatten(1)->count(),
            ],
        ]);
    }

    /**
     * Suggest additional KPIs
     */
    public function suggestAdditionalKpis(int $businessId): JsonResponse
    {
        $configuration = BusinessKpiConfiguration::where('business_id', $businessId)->first();
        if (! $configuration) {
            return response()->json([
                'success' => false,
                'message' => 'KPI configuration not found',
            ], 404);
        }

        $suggestions = $this->kpiMatcher->suggestAdditionalKpis($configuration);

        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $suggestions,
            ],
        ]);
    }

    /**
     * Get benchmark targets
     */
    public function getBenchmarkTargets(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kpi_codes' => 'required|array',
            'kpi_codes.*' => 'string|exists:kpi_templates,kpi_code',
            'scenario' => 'nullable|string|in:conservative,realistic,optimistic,aggressive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (! $business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $targets = $this->kpiMatcher->getBenchmarkTargets(
            $business,
            $request->input('kpi_codes'),
            $request->input('scenario', 'realistic')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'targets' => $targets,
            ],
        ]);
    }
}
