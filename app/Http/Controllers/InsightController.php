<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\Business;
use App\Services\InsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class InsightController extends Controller
{
    public function __construct(
        protected InsightService $insightService
    ) {}

    public function index(Request $request): Response
    {
        $business = $this->getCurrentBusiness();

        $query = AiInsight::where('business_id', $business->id)
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter active only
        if ($request->boolean('active_only', true)) {
            $query->active()->notExpired();
        }

        $insights = $query->paginate(20);

        // PERFORMANCE: Get stats with caching (5 minutes)
        $stats = Cache::remember("insights_stats_{$business->id}", 300, function () use ($business) {
            return [
                'total' => AiInsight::where('business_id', $business->id)->count(),
                'active' => AiInsight::where('business_id', $business->id)->active()->notExpired()->count(),
                'by_type' => AiInsight::where('business_id', $business->id)
                    ->active()
                    ->selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ];
        });

        return Inertia::render('Dashboard/Insights/Index', [
            'insights' => $insights,
            'stats' => $stats,
            'filters' => [
                'type' => $request->type ?? 'all',
                'category' => $request->category ?? 'all',
                'active_only' => $request->boolean('active_only', true),
            ],
        ]);
    }

    public function show(string $id): Response
    {
        $business = $this->getCurrentBusiness();
        $insight = AiInsight::where('business_id', $business->id)->findOrFail($id);

        // Mark as viewed
        $insight->markViewed();

        return Inertia::render('Dashboard/Insights/Show', [
            'insight' => $insight,
        ]);
    }

    public function markViewed(string $id)
    {
        $business = $this->getCurrentBusiness();
        $insight = AiInsight::where('business_id', $business->id)->findOrFail($id);

        $insight->markViewed();

        return response()->json(['success' => true]);
    }

    public function markActed(Request $request, string $id)
    {
        $request->validate([
            'action_taken' => 'nullable|string|max:1000',
        ]);

        $business = $this->getCurrentBusiness();
        $insight = AiInsight::where('business_id', $business->id)->findOrFail($id);

        $insight->markActed($request->action_taken);

        return response()->json(['success' => true, 'insight' => $insight->fresh()]);
    }

    public function dismiss(string $id)
    {
        $business = $this->getCurrentBusiness();
        $insight = AiInsight::where('business_id', $business->id)->findOrFail($id);

        $insight->update(['is_active' => false]);

        // Clear stats cache when insight is dismissed
        Cache::forget("insights_stats_{$business->id}");

        return response()->json(['success' => true]);
    }

    public function getActive()
    {
        $business = $this->getCurrentBusiness();
        $insights = $this->insightService->getActiveInsights($business);

        return response()->json(['insights' => $insights]);
    }

    public function regenerate()
    {
        $business = $this->getCurrentBusiness();
        $insights = $this->insightService->generateInsights($business);

        return response()->json([
            'success' => true,
            'count' => $insights->count(),
            'insights' => $insights,
        ]);
    }

    public function getByCategory(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $category = $request->get('category');

        $insights = AiInsight::where('business_id', $business->id)
            ->where('category', $category)
            ->active()
            ->notExpired()
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->limit(10)
            ->get();

        return response()->json(['insights' => $insights]);
    }

    protected function getCurrentBusiness(): Business
    {
        return Auth::user()->currentBusiness ?? Auth::user()->businesses()->firstOrFail();
    }
}
