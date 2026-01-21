<?php

namespace App\Http\Controllers\Api\BusinessSystematization;

use App\Http\Controllers\Controller;
use App\Models\MarketingKpi;
use App\Models\MarketingCampaign;
use App\Models\MarketingChannel;
use App\Models\MarketingBudget;
use App\Models\LeadFlowTracking;
use App\Models\ContentCalendar;
use App\Services\BusinessSystematization\MarketingSalesIntegrationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Marketing Integration API Controller
 * Implements Marketing-Sales linkage from Denis Shenukov's book:
 * - Marketing bonus: 70% from Sales results + 30% from own tasks
 * - Channel ROI tracking
 * - Lead quality metrics
 */
class MarketingIntegrationController extends Controller
{
    protected MarketingSalesIntegrationService $integrationService;

    public function __construct(MarketingSalesIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Get marketing dashboard with sales integration
     */
    public function dashboard(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $data = $this->integrationService->getDashboardData($businessId, $date);

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => [
                'month' => $date->format('Y-m'),
                'month_name' => $date->translatedFormat('F Y'),
            ],
        ]);
    }

    /**
     * Get sales linkage data (70/30 rule)
     */
    public function salesLinkage(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->integrationService->getSalesLinkageData($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get channel performance with ROI
     */
    public function channelPerformance(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->integrationService->getChannelPerformance($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get budget status
     */
    public function budgetStatus(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $data = $this->integrationService->getBudgetStatus($businessId, $year, $month);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get lead quality metrics
     */
    public function leadQuality(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->integrationService->getLeadQualityMetrics($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get campaign ROI analysis
     */
    public function campaignRoi(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->integrationService->getCampaignRoiAnalysis($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Calculate marketing team bonus (70/30 rule)
     */
    public function calculateBonus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'base_bonus_fund' => 'required|numeric|min:0',
        ]);

        $businessId = $request->user()->current_business_id;

        $data = $this->integrationService->calculateMarketingBonus(
            $businessId,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $validated['base_bonus_fund']
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ==================== Marketing KPIs ====================

    /**
     * List marketing KPIs
     */
    public function listKpis(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $kpis = MarketingKpi::where('business_id', $businessId)
            ->with(['user', 'department'])
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->orderByDesc('period_start')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Create marketing KPI record
     */
    public function createKpi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'tasks_planned' => 'nullable|array',
            'tasks_completed' => 'nullable|array',
            'tasks_completion_percent' => 'nullable|numeric|min:0|max:100',
            'leads_target' => 'nullable|integer|min:0',
            'leads_generated' => 'nullable|integer|min:0',
            'budget_allocated' => 'nullable|numeric|min:0',
            'budget_spent' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;

        $kpi = MarketingKpi::create($validated);

        return response()->json([
            'success' => true,
            'data' => $kpi,
            'message' => 'Marketing KPI yaratildi',
        ], 201);
    }

    /**
     * Update marketing KPI
     */
    public function updateKpi(Request $request, MarketingKpi $kpi): JsonResponse
    {
        $this->authorize('update', $kpi);

        $validated = $request->validate([
            'tasks_completed' => 'nullable|array',
            'tasks_completion_percent' => 'nullable|numeric|min:0|max:100',
            'leads_generated' => 'nullable|integer|min:0',
            'budget_spent' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $kpi->update($validated);

        return response()->json([
            'success' => true,
            'data' => $kpi->fresh(),
            'message' => 'Marketing KPI yangilandi',
        ]);
    }

    // ==================== Marketing Campaigns ====================

    /**
     * List marketing campaigns
     */
    public function listCampaigns(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $campaigns = MarketingCampaign::where('business_id', $businessId)
            ->with(['channel', 'segment'])
            ->when($request->channel_id, fn($q) => $q->where('channel_id', $request->channel_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('start_date')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * Create marketing campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'channel_id' => 'required|uuid|exists:marketing_channels,id',
            'segment_id' => 'nullable|uuid|exists:customer_segments,id',
            'campaign_type' => 'required|in:brand,lead_generation,sales,retention,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget_planned' => 'required|numeric|min:0',
            'leads_target' => 'nullable|integer|min:0',
            'deals_target' => 'nullable|integer|min:0',
            'revenue_target' => 'nullable|numeric|min:0',
            'goals' => 'nullable|array',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'planned';

        $campaign = MarketingCampaign::create($validated);

        return response()->json([
            'success' => true,
            'data' => $campaign->load(['channel', 'segment']),
            'message' => 'Kampaniya yaratildi',
        ], 201);
    }

    /**
     * Update campaign metrics
     */
    public function updateCampaignMetrics(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'status' => 'sometimes|in:planned,active,paused,completed,cancelled',
            'budget_spent' => 'nullable|numeric|min:0',
            'impressions' => 'nullable|integer|min:0',
            'clicks' => 'nullable|integer|min:0',
            'leads_generated' => 'nullable|integer|min:0',
            'deals_closed' => 'nullable|integer|min:0',
            'revenue_generated' => 'nullable|numeric|min:0',
        ]);

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'data' => $campaign->fresh(),
            'message' => 'Kampaniya yangilandi',
        ]);
    }

    // ==================== Marketing Budgets ====================

    /**
     * List marketing budgets
     */
    public function listBudgets(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $year = $request->input('year', now()->year);

        $budgets = MarketingBudget::where('business_id', $businessId)
            ->where('year', $year)
            ->with('channel')
            ->orderBy('month')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    /**
     * Create/Update marketing budget
     */
    public function saveBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_id' => 'nullable|uuid|exists:marketing_channels,id',
            'year' => 'required|integer|min:2020|max:2050',
            'month' => 'required|integer|min:1|max:12',
            'budget_limit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;

        $budget = MarketingBudget::updateOrCreate(
            [
                'business_id' => $validated['business_id'],
                'channel_id' => $validated['channel_id'],
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'budget_limit' => $validated['budget_limit'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $budget->load('channel'),
            'message' => 'Byudjet saqlandi',
        ]);
    }

    /**
     * Record budget spending
     */
    public function recordSpending(Request $request, MarketingBudget $budget): JsonResponse
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $budget->increment('spent_amount', $validated['amount']);

        return response()->json([
            'success' => true,
            'data' => $budget->fresh(),
            'message' => 'Xarajat qayd etildi',
        ]);
    }

    // ==================== Lead Flow Tracking ====================

    /**
     * Record lead flow data (Marketing → Sales)
     */
    public function recordLeadFlow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_id' => 'required|uuid|exists:marketing_channels,id',
            'campaign_id' => 'nullable|uuid|exists:marketing_campaigns,id',
            'tracking_date' => 'required|date',
            'leads_generated' => 'required|integer|min:0',
            'leads_accepted' => 'nullable|integer|min:0',
            'leads_rejected' => 'nullable|integer|min:0',
            'leads_converted' => 'nullable|integer|min:0',
            'lead_quality_score' => 'nullable|numeric|min:0|max:5',
            'rejection_reasons_summary' => 'nullable|array',
            'sales_feedback' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;

        $tracking = LeadFlowTracking::updateOrCreate(
            [
                'business_id' => $validated['business_id'],
                'channel_id' => $validated['channel_id'],
                'campaign_id' => $validated['campaign_id'],
                'tracking_date' => $validated['tracking_date'],
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $tracking,
            'message' => 'Lid oqimi qayd etildi',
        ]);
    }

    /**
     * Get lead flow history
     */
    public function getLeadFlowHistory(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $tracking = LeadFlowTracking::where('business_id', $businessId)
            ->with(['channel', 'campaign'])
            ->when($request->channel_id, fn($q) => $q->where('channel_id', $request->channel_id))
            ->when($request->start_date, fn($q) => $q->where('tracking_date', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('tracking_date', '<=', $request->end_date))
            ->orderByDesc('tracking_date')
            ->paginate($request->per_page ?? 30);

        return response()->json([
            'success' => true,
            'data' => $tracking,
        ]);
    }

    // ==================== Marketing Channels ====================

    /**
     * List marketing channels
     */
    public function listChannels(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $channels = MarketingChannel::where('business_id', $businessId)
            ->when($request->active_only, fn($q) => $q->active())
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $channels,
        ]);
    }

    /**
     * Create marketing channel
     */
    public function createChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:social_media,search,email,content,referral,events,other',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;

        $channel = MarketingChannel::create($validated);

        return response()->json([
            'success' => true,
            'data' => $channel,
            'message' => 'Kanal yaratildi',
        ], 201);
    }
}
