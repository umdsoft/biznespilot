<?php

namespace App\Http\Controllers\Api\BusinessSystematization;

use App\Http\Controllers\Controller;
use App\Models\SalesTarget;
use App\Models\SalesActivity;
use App\Models\Receivable;
use App\Models\SalesFunnelStage;
use App\Models\LostDeal;
use App\Models\RejectionReason;
use App\Services\BusinessSystematization\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Sales Analytics API Controller
 * Provides data for ROP (Sales Manager) Dashboard
 */
class SalesAnalyticsController extends Controller
{
    protected SalesAnalyticsService $analyticsService;

    public function __construct(SalesAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get complete sales dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $data = $this->analyticsService->getDashboardData($businessId, $date);

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
     * Get current period statistics
     */
    public function currentPeriod(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->analyticsService->getCurrentPeriodStats($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get manager rankings
     */
    public function managerRankings(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->analyticsService->getManagerRankings($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get receivables (debitorka) statistics
     */
    public function receivables(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $data = $this->analyticsService->getReceivablesStats($businessId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get sales funnel statistics
     */
    public function funnel(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->analyticsService->getFunnelStats($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get rejection/lost deal analysis
     */
    public function rejectionAnalysis(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->analyticsService->getRejectionAnalysis($businessId, $monthStart, $monthEnd);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get trend data for the last N months
     */
    public function trend(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $months = $request->input('months', 6);

        $data = $this->analyticsService->getTrendData($businessId, $months);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get manager activity summary
     */
    public function managerActivity(Request $request, string $userId): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $date = $request->date ? Carbon::parse($request->date) : now();

        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $data = $this->analyticsService->getManagerActivitySummary(
            $businessId,
            $userId,
            $monthStart,
            $monthEnd
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ==================== CRUD Operations ====================

    /**
     * List sales targets
     */
    public function listTargets(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $targets = SalesTarget::where('business_id', $businessId)
            ->with(['user', 'department'])
            ->when($request->target_type, fn($q) => $q->where('target_type', $request->target_type))
            ->when($request->period, function($q) use ($request) {
                $date = Carbon::parse($request->period);
                $q->where('period_start', '<=', $date->startOfMonth())
                  ->where('period_end', '>=', $date->endOfMonth());
            })
            ->orderByDesc('period_start')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $targets,
        ]);
    }

    /**
     * Create sales target
     */
    public function createTarget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => 'required|in:individual,department,company',
            'user_id' => 'nullable|uuid|exists:users,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'plan_revenue' => 'required|numeric|min:0',
            'base_revenue' => 'required|numeric|min:0',
            'plan_deals' => 'nullable|integer|min:0',
            'plan_new_clients' => 'nullable|integer|min:0',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['created_by'] = $request->user()->id;

        $target = SalesTarget::create($validated);

        return response()->json([
            'success' => true,
            'data' => $target,
            'message' => 'Sotuv rejasi muvaffaqiyatli yaratildi',
        ], 201);
    }

    /**
     * Update sales target
     */
    public function updateTarget(Request $request, SalesTarget $target): JsonResponse
    {
        $this->authorize('update', $target);

        $validated = $request->validate([
            'plan_revenue' => 'sometimes|numeric|min:0',
            'base_revenue' => 'sometimes|numeric|min:0',
            'fact_revenue' => 'sometimes|numeric|min:0',
            'fact_deals' => 'sometimes|integer|min:0',
            'fact_new_clients' => 'sometimes|integer|min:0',
            'plan_deals' => 'sometimes|integer|min:0',
            'plan_new_clients' => 'sometimes|integer|min:0',
        ]);

        $target->update($validated);

        return response()->json([
            'success' => true,
            'data' => $target->fresh(),
            'message' => 'Sotuv rejasi yangilandi',
        ]);
    }

    /**
     * Record daily sales activity
     */
    public function recordActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'activity_date' => 'required|date',
            'calls_made' => 'nullable|integer|min:0',
            'calls_answered' => 'nullable|integer|min:0',
            'meetings_held' => 'nullable|integer|min:0',
            'proposals_sent' => 'nullable|integer|min:0',
            'deals_closed' => 'nullable|integer|min:0',
            'revenue_generated' => 'nullable|numeric|min:0',
            'talk_time_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;

        $activity = SalesActivity::updateOrCreate(
            [
                'business_id' => $validated['business_id'],
                'user_id' => $validated['user_id'],
                'activity_date' => $validated['activity_date'],
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $activity,
            'message' => 'Faoliyat qayd etildi',
        ]);
    }

    /**
     * List receivables
     */
    public function listReceivables(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $receivables = Receivable::where('business_id', $businessId)
            ->with(['customer', 'responsibleUser', 'deal'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn($q) => $q->where('responsible_user_id', $request->user_id))
            ->when($request->overdue_only, fn($q) => $q->overdue())
            ->orderByDesc('due_date')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $receivables,
        ]);
    }

    /**
     * Create receivable record
     */
    public function createReceivable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid',
            'deal_id' => 'nullable|uuid',
            'invoice_number' => 'nullable|string|max:100',
            'original_amount' => 'required|numeric|min:0',
            'remaining_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'due_date' => 'required|date',
            'responsible_user_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['status'] = 'pending';

        $receivable = Receivable::create($validated);

        return response()->json([
            'success' => true,
            'data' => $receivable,
            'message' => 'Debitorka qayd etildi',
        ], 201);
    }

    /**
     * Record payment for receivable
     */
    public function recordPayment(Request $request, Receivable $receivable): JsonResponse
    {
        $this->authorize('update', $receivable);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $receivable->remaining_amount,
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $receivable->recordPayment(
            $validated['amount'],
            Carbon::parse($validated['payment_date']),
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $receivable->fresh(),
            'message' => "To'lov qayd etildi",
        ]);
    }

    /**
     * List funnel stages
     */
    public function listFunnelStages(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $stages = SalesFunnelStage::where('business_id', $businessId)
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stages,
        ]);
    }

    /**
     * List rejection reasons
     */
    public function listRejectionReasons(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $reasons = RejectionReason::where('business_id', $businessId)
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }

    /**
     * Record lost deal
     */
    public function recordLostDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deal_id' => 'nullable|uuid',
            'lead_id' => 'nullable|uuid',
            'customer_id' => 'nullable|uuid',
            'rejection_reason_id' => 'required|uuid|exists:rejection_reasons,id',
            'lost_to_competitor_id' => 'nullable|uuid|exists:competitors,id',
            'lost_date' => 'required|date',
            'potential_value' => 'nullable|numeric|min:0',
            'funnel_stage_id' => 'nullable|uuid|exists:sales_funnel_stages,id',
            'notes' => 'nullable|string',
            'lessons_learned' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['recorded_by'] = $request->user()->id;

        $lostDeal = LostDeal::create($validated);

        return response()->json([
            'success' => true,
            'data' => $lostDeal->load(['rejectionReason', 'lostToCompetitor']),
            'message' => "Yo'qotilgan bitim qayd etildi",
        ], 201);
    }
}
