<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Services\AIInsightsService;
use App\Jobs\GenerateDailyInsights;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AIInsightsController extends Controller
{
    protected AIInsightsService $insightsService;

    public function __construct(AIInsightsService $insightsService)
    {
        $this->insightsService = $insightsService;
    }

    /**
     * Display insights dashboard
     */
    public function index(Request $request)
    {
        $businessId = session('current_business_id');

        $query = AiInsight::where('business_id', $businessId);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'actionable') {
                $query->actionable();
            }
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority !== 'all') {
            $query->byPriority($request->priority);
        }

        $insights = $query->orderBy('generated_at', 'desc')
            ->paginate(20);

        // Get summary statistics
        $summary = $this->insightsService->generateMonthlyInsightsSummary(
            \App\Models\Business::find($businessId)
        );

        // Get top priority insights
        $topInsights = $this->insightsService->getTopPriorityInsights(
            \App\Models\Business::find($businessId),
            5
        );

        return Inertia::render('Business/AI/Insights', [
            'insights' => $insights,
            'summary' => $summary,
            'topInsights' => $topInsights,
            'filters' => [
                'status' => $request->status ?? 'all',
                'type' => $request->type ?? 'all',
                'priority' => $request->priority ?? 'all',
            ],
        ]);
    }

    /**
     * Show single insight
     */
    public function show(AiInsight $insight)
    {
        // Mark as read
        if (!$insight->is_read) {
            $insight->markAsRead();
        }

        return Inertia::render('Business/AI/InsightDetail', [
            'insight' => $insight,
        ]);
    }

    /**
     * Mark insight as read
     */
    public function markAsRead(AiInsight $insight)
    {
        $insight->markAsRead();

        return back()->with('success', 'Insight marked as read');
    }

    /**
     * Mark multiple insights as read
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'insight_ids' => 'required|array',
            'insight_ids.*' => 'exists:ai_insights,id',
        ]);

        AiInsight::whereIn('id', $request->insight_ids)
            ->where('business_id', session('current_business_id'))
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', count($request->insight_ids) . ' insights marked as read');
    }

    /**
     * Record action taken on insight
     */
    public function recordAction(Request $request, AiInsight $insight)
    {
        $request->validate([
            'action' => 'required|string|max:1000',
        ]);

        $insight->recordAction($request->action);

        return back()->with('success', 'Action recorded successfully');
    }

    /**
     * Delete insight
     */
    public function destroy(AiInsight $insight)
    {
        $insight->delete();

        return back()->with('success', 'Insight deleted successfully');
    }

    /**
     * Generate insights manually
     */
    public function generate(Request $request)
    {
        $businessId = session('current_business_id');

        $types = $request->input('types', ['marketing', 'sales', 'customer']);

        try {
            $business = \App\Models\Business::findOrFail($businessId);
            $insights = $this->insightsService->generateInsightsForBusiness($business, $types);

            return back()->with('success', count($insights) . ' new insights generated');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate insights: ' . $e->getMessage());
        }
    }

    /**
     * Queue insight generation job
     */
    public function queueGeneration()
    {
        $businessId = session('current_business_id');

        GenerateDailyInsights::dispatch($businessId);

        return back()->with('success', 'Insight generation queued. You will receive insights shortly.');
    }

    /**
     * Get insights statistics (API endpoint)
     */
    public function statistics()
    {
        $businessId = session('current_business_id');
        $business = \App\Models\Business::findOrFail($businessId);

        return response()->json([
            'summary' => $this->insightsService->generateMonthlyInsightsSummary($business),
            'top_insights' => $this->insightsService->getTopPriorityInsights($business, 5),
        ]);
    }
}
