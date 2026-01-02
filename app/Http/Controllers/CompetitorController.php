<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Models\CompetitorAlert;
use App\Models\CompetitorMetric;
use App\Services\CompetitorAnalysisService;
use App\Services\CompetitorMonitoringService;
use App\Jobs\ScrapeCompetitorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CompetitorController extends Controller
{
    protected CompetitorMonitoringService $monitoringService;
    protected CompetitorAnalysisService $analysisService;
    protected int $cacheTTL = 600; // 10 minutes

    public function __construct(
        CompetitorMonitoringService $monitoringService,
        CompetitorAnalysisService $analysisService
    ) {
        $this->monitoringService = $monitoringService;
        $this->analysisService = $analysisService;
    }

    /**
     * Display competitors list - LAZY LOADING
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        if (!$business) {
            return redirect()->route('business.index');
        }

        $query = Competitor::where('business_id', $business->id)
            ->with(['metrics' => fn($q) => $q->latest('date')->limit(1)])
            ->withCount('metrics');

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('threat_level') && $request->threat_level !== 'all') {
            $query->where('threat_level', $request->threat_level);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhere('industry', 'like', "%{$request->search}%");
            });
        }

        $competitors = $query->orderBy('threat_level', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Insights will be loaded via API (lazy loading)
        return Inertia::render('Business/Competitors/Index', [
            'competitors' => $competitors,
            'insights' => null,
            'filters' => $request->only(['status', 'threat_level', 'search']),
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get competitive insights (cached)
     */
    public function getInsights(Request $request)
    {
        $business = $request->user()->currentBusiness;

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $cacheKey = "competitor_insights_{$business->id}";

        $insights = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business) {
            return $this->analysisService->getCompetitiveInsights($business->id);
        });

        return response()->json(['insights' => $insights]);
    }

    /**
     * Show competitor dashboard - LAZY LOADING
     */
    public function dashboard(Request $request)
    {
        $business = $request->user()->currentBusiness;

        if (!$business) {
            return redirect()->route('business.index');
        }

        // Load only lightweight stats initially
        $stats = Cache::remember("competitor_stats_{$business->id}", 300, function () use ($business) {
            return [
                'total_competitors' => Competitor::where('business_id', $business->id)->where('status', 'active')->count(),
                'active_monitoring' => Competitor::where('business_id', $business->id)->where('status', 'active')->where('auto_monitor', true)->count(),
                'high_threats' => Competitor::where('business_id', $business->id)->whereIn('threat_level', ['high', 'critical'])->count(),
                'unread_alerts' => CompetitorAlert::where('business_id', $business->id)->where('status', 'unread')->count(),
            ];
        });

        return Inertia::render('Business/Competitors/Dashboard', [
            'competitors' => null,
            'stats' => $stats,
            'alerts' => null,
            'insights' => null,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get dashboard data
     */
    public function getDashboardData(Request $request)
    {
        $business = $request->user()->currentBusiness;

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $cacheKey = "competitor_dashboard_{$business->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($business) {
            // Get all active competitors
            $competitors = Competitor::where('business_id', $business->id)
                ->where('status', 'active')
                ->with(['metrics' => fn($q) => $q->latest('date')->limit(30)])
                ->get();

            // Get unread alerts
            $unreadAlerts = CompetitorAlert::where('business_id', $business->id)
                ->where('status', 'unread')
                ->with('competitor')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get insights (use separate cache)
            $insights = Cache::remember("competitor_insights_{$business->id}", 600, function () use ($business) {
                return $this->analysisService->getCompetitiveInsights($business->id);
            });

            return [
                'competitors' => $competitors,
                'alerts' => $unreadAlerts,
                'insights' => $insights,
            ];
        });

        return response()->json($data);
    }

    /**
     * Show single competitor
     */
    public function show(Request $request, Competitor $competitor)
    {
        $this->authorize('view', $competitor);

        $competitor->load([
            'metrics' => fn($q) => $q->latest('date')->limit(90),
            'activities' => fn($q) => $q->latest('activity_date')->limit(20),
        ]);

        // Get latest metric
        $latestMetric = $competitor->metrics->first();

        return Inertia::render('Business/Competitors/Detail', [
            'competitor' => $competitor,
            'latestMetric' => $latestMetric,
        ]);
    }

    /**
     * Store new competitor
     */
    public function store(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'instagram_handle' => 'nullable|string|max:255',
            'telegram_handle' => 'nullable|string|max:255',
            'facebook_page' => 'nullable|string|max:255',
            'tiktok_handle' => 'nullable|string|max:255',
            'youtube_channel' => 'nullable|string|max:255',
            'threat_level' => 'required|in:low,medium,high,critical',
            'auto_monitor' => 'boolean',
            'check_frequency_hours' => 'nullable|integer|min:1|max:168',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $competitor = Competitor::create(array_merge($validated, [
            'business_id' => $business->id,
            'status' => 'active',
        ]));

        return redirect()->route('competitors.show', $competitor)
            ->with('success', 'Raqib qo\'shildi');
    }

    /**
     * Update competitor
     */
    public function update(Request $request, Competitor $competitor)
    {
        $this->authorize('update', $competitor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'instagram_handle' => 'nullable|string|max:255',
            'telegram_handle' => 'nullable|string|max:255',
            'facebook_page' => 'nullable|string|max:255',
            'tiktok_handle' => 'nullable|string|max:255',
            'youtube_channel' => 'nullable|string|max:255',
            'threat_level' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:active,inactive,archived',
            'auto_monitor' => 'boolean',
            'check_frequency_hours' => 'nullable|integer|min:1|max:168',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $competitor->update($validated);

        return back()->with('success', 'Yangilandi');
    }

    /**
     * Delete competitor
     */
    public function destroy(Request $request, Competitor $competitor)
    {
        $this->authorize('delete', $competitor);

        $competitor->delete();

        return redirect()->route('competitors.index')
            ->with('success', 'Raqib o\'chirildi');
    }

    /**
     * Record manual metrics
     */
    public function recordMetrics(Request $request, Competitor $competitor)
    {
        $this->authorize('update', $competitor);

        $validated = $request->validate([
            'date' => 'nullable|date',
            'instagram_followers' => 'nullable|integer|min:0',
            'instagram_following' => 'nullable|integer|min:0',
            'instagram_posts' => 'nullable|integer|min:0',
            'instagram_engagement_rate' => 'nullable|numeric|min:0|max:100',
            'telegram_members' => 'nullable|integer|min:0',
            'facebook_followers' => 'nullable|integer|min:0',
            'tiktok_followers' => 'nullable|integer|min:0',
            'youtube_subscribers' => 'nullable|integer|min:0',
        ]);

        $date = $validated['date'] ? \Carbon\Carbon::parse($validated['date']) : null;
        unset($validated['date']);

        $metric = $this->monitoringService->recordManualMetrics($competitor, $validated, $date);

        return back()->with('success', 'Metrika saqlandi');
    }

    /**
     * Trigger monitoring for competitor
     */
    public function monitor(Request $request, Competitor $competitor)
    {
        $this->authorize('update', $competitor);

        // Dispatch job
        ScrapeCompetitorData::dispatch($competitor->id);

        return back()->with('success', 'Monitoring boshlandi');
    }

    /**
     * Generate SWOT analysis
     */
    public function generateSwot(Request $request, Competitor $competitor)
    {
        $this->authorize('view', $competitor);

        $swot = $this->analysisService->generateSWOTAnalysis($competitor);

        return response()->json([
            'success' => true,
            'swot' => $swot,
        ]);
    }

    /**
     * Get alerts
     */
    public function alerts(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $query = CompetitorAlert::where('business_id', $business->id)
            ->with(['competitor', 'activity']);

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($alerts);
    }

    /**
     * Mark alert as read
     */
    public function markAlertRead(Request $request, CompetitorAlert $alert)
    {
        $this->authorize('update', $alert);

        $alert->markAsRead();

        return back()->with('success', 'Alert o\'qilgan deb belgilandi');
    }

    /**
     * Archive alert
     */
    public function archiveAlert(Request $request, CompetitorAlert $alert)
    {
        $this->authorize('update', $alert);

        $alert->archive();

        return back()->with('success', 'Alert arxivlandi');
    }
}
