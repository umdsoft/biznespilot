<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Competitor;
use App\Models\CompetitorAlert;
use App\Models\GlobalCompetitor;
use App\Services\CompetitorAnalysisService;
use App\Services\CompetitorMonitoringService;
use App\Jobs\ScrapeCompetitorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CompetitorsController extends Controller
{
    use HasCurrentBusiness;

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
     * Get panel type from route prefix
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) return 'marketing';
        if (str_contains($prefix, 'finance')) return 'finance';
        if (str_contains($prefix, 'operator')) return 'operator';
        if (str_contains($prefix, 'saleshead')) return 'saleshead';

        return 'business';
    }

    /**
     * Get route name prefix based on panel
     */
    protected function getRoutePrefix(Request $request): string
    {
        $panel = $this->getPanelType($request);

        return match($panel) {
            'marketing' => 'marketing.competitors',
            'finance' => 'finance.competitors',
            'operator' => 'operator.competitors',
            'saleshead' => 'saleshead.competitors',
            default => 'business.competitors',
        };
    }

    /**
     * Check if competitor belongs to current business
     */
    protected function authorizeCompetitor(Request $request, Competitor $competitor): void
    {
        $business = $request->user()->currentBusiness;
        if (!$business || $competitor->business_id !== $business->id) {
            abort(403, 'Bu raqobatchiga kirish huquqi yo\'q');
        }
    }

    /**
     * Display competitors list
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);
        $business->load('industryRelation');

        $query = Competitor::where('business_id', $business->id)
            ->with(['metrics' => fn($q) => $q->latest('recorded_date')->limit(1)])
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

        $businessData = $business->toArray();
        $businessData['industry_name'] = $business->industryRelation?->name_uz ?? $business->industry ?? $business->category ?? null;

        return Inertia::render('Shared/Competitors/Index', [
            'competitors' => $competitors,
            'currentBusiness' => $businessData,
            'insights' => null,
            'filters' => $request->only(['status', 'threat_level', 'search']),
            'lazyLoad' => true,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Show competitor dashboard
     */
    public function dashboard(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);

        // Load stats
        $stats = Cache::remember("competitor_stats_{$business->id}", 300, function () use ($business) {
            return [
                'total_competitors' => Competitor::where('business_id', $business->id)->where('status', 'active')->count(),
                'active_monitoring' => Competitor::where('business_id', $business->id)->where('status', 'active')->where('auto_monitor', true)->count(),
                'high_threats' => Competitor::where('business_id', $business->id)->whereIn('threat_level', ['high', 'critical'])->count(),
                'unread_alerts' => CompetitorAlert::where('business_id', $business->id)->where('is_read', false)->count(),
            ];
        });

        // Load competitors
        $competitors = Competitor::where('business_id', $business->id)
            ->where('status', 'active')
            ->with(['metrics' => fn($q) => $q->latest('recorded_date')->limit(1)])
            ->orderBy('threat_level', 'desc')
            ->limit(10)
            ->get();

        // Load unread alerts
        $unreadAlerts = CompetitorAlert::where('business_id', $business->id)
            ->where('is_read', false)
            ->with('competitor')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Load insights
        $insights = Cache::remember("competitor_insights_{$business->id}", 600, function () use ($business) {
            return $this->analysisService->getCompetitiveInsights($business->id);
        });

        return Inertia::render('Shared/Competitors/Dashboard', [
            'competitors' => $competitors,
            'stats' => $stats,
            'unread_alerts' => $unreadAlerts,
            'insights' => $insights,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Show single competitor
     */
    public function show(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($request, $competitor);
        $panelType = $this->getPanelType($request);

        $competitor->load([
            'metrics' => fn($q) => $q->latest('recorded_date')->limit(90),
            'activities' => fn($q) => $q->latest('activity_date')->limit(20),
        ]);

        // Get latest metric
        $latestMetric = $competitor->metrics->first();

        // Get SWOT analysis
        $swotAnalysis = null;
        if ($competitor->strengths || $competitor->weaknesses) {
            $swotAnalysis = [
                'strengths' => $competitor->strengths ?? [],
                'weaknesses' => $competitor->weaknesses ?? [],
                'opportunities' => [],
                'threats' => [],
                'overall_assessment' => null,
                'recommendations' => [],
                'generated_at' => $competitor->updated_at,
            ];
        }

        return Inertia::render('Shared/Competitors/Show', [
            'competitor' => $competitor,
            'metrics' => $competitor->metrics ?? [],
            'latest_metric' => $latestMetric,
            'swot_analysis' => $swotAnalysis,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Store new competitor
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $routePrefix = $this->getRoutePrefix($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
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
            'global_competitor_id' => 'nullable|integer|exists:global_competitors,id',
        ]);

        $competitor = Competitor::create(array_merge($validated, [
            'business_id' => $business->id,
            'status' => 'active',
        ]));

        // Sync with global competitor if not already linked
        if (empty($validated['global_competitor_id'])) {
            $competitor->syncWithGlobalCompetitor();
        }

        // Auto-update SWOT analysis
        $this->analysisService->updateBusinessSwotFromCompetitor($business);

        return redirect()->route($routePrefix . '.show', $competitor)
            ->with('success', 'Raqib qo\'shildi va SWOT tahlil yangilandi');
    }

    /**
     * Update competitor
     */
    public function update(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($request, $competitor);
        $business = $this->getCurrentBusiness();

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

        // Auto-update SWOT analysis
        $this->analysisService->updateBusinessSwotFromCompetitor($business);

        return back()->with('success', 'Yangilandi');
    }

    /**
     * Delete competitor
     */
    public function destroy(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($request, $competitor);
        $business = $this->getCurrentBusiness();
        $routePrefix = $this->getRoutePrefix($request);

        $competitor->delete();

        // Auto-update SWOT analysis
        $this->analysisService->updateBusinessSwotFromCompetitor($business);

        return redirect()->route($routePrefix . '.index')
            ->with('success', 'Raqib o\'chirildi va SWOT tahlil yangilandi');
    }

    /**
     * SWOT Analysis Index Page
     */
    public function swotIndex(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);

        // Get existing SWOT data
        $swot = $business->swot_data ?? [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

        // Get competitors
        $competitors = Competitor::where('business_id', $business->id)
            ->where('status', 'active')
            ->with('globalCompetitor')
            ->select('id', 'name', 'threat_level', 'instagram_handle', 'telegram_handle', 'swot_data', 'swot_analyzed_at', 'global_competitor_id', 'region', 'district')
            ->orderBy('threat_level', 'desc')
            ->get()
            ->map(function ($competitor) {
                $competitor->effective_swot_data = $competitor->effective_swot_data;
                $competitor->global_swot_count = $competitor->globalCompetitor?->swot_count ?? 0;
                $competitor->global_contributors = $competitor->globalCompetitor?->swot_contributors_count ?? 0;
                return $competitor;
            });

        return Inertia::render('Shared/Swot/Index', [
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'swot' => $swot,
            'competitorCount' => $competitors->count(),
            'lastUpdated' => $business->swot_updated_at,
            'competitors' => $competitors,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Generate SWOT for a specific competitor
     */
    public function generateCompetitorSwot(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($request, $competitor);

        $swot = [
            'strengths' => [
                $competitor->name . ' kuchli brend nomiga ega',
                'Keng mijozlar bazasi mavjud',
                'Doimiy marketing faoliyati olib borilmoqda',
            ],
            'weaknesses' => [
                'Narxlari bozor o\'rtachasidan yuqori',
                'Onlayn mavjudligi cheklangan',
                'Mijozlar xizmati sifati past',
            ],
            'opportunities' => [
                'Ularning marketing strategiyalarini o\'rganish',
                'Zaif tomonlaridan foydalanish',
                'Yangi bozor segmentlariga kirish',
            ],
            'threats' => [
                'Kuchli raqobatchi sifatida ta\'sir qilishi',
                'Narx urushi boshlashi mumkin',
                'Yangi mahsulotlar chiqarishi mumkin',
            ],
        ];

        $competitor->swot_data = $swot;
        $competitor->swot_analyzed_at = now();
        $competitor->save();

        return response()->json([
            'success' => true,
            'swot' => $swot,
        ]);
    }

    /**
     * Save competitor SWOT data manually
     */
    public function saveCompetitorSwot(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($request, $competitor);

        $swotData = $request->input('swot_data');

        $competitor->swot_data = $swotData;
        $competitor->swot_analyzed_at = now();
        $competitor->save();

        // Sync with global competitor
        $competitor->syncWithGlobalCompetitor();

        return response()->json(['success' => true]);
    }

    /**
     * Search global competitors for autocomplete
     */
    public function searchGlobal(Request $request)
    {
        try {
            $search = trim($request->input('q', ''));

            if (strlen($search) < 3) {
                return response()->json([]);
            }

            $business = $request->user()->currentBusiness;
            $industry = $business?->industryRelation?->name ?? $business?->industry ?? null;
            $region = $business?->region ?? null;

            $results = GlobalCompetitor::query()
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                })
                ->select(['id', 'name', 'industry', 'region', 'district', 'instagram_handle', 'telegram_handle', 'swot_contributors_count'])
                ->limit(8)
                ->get();

            $data = [];
            foreach ($results as $item) {
                $data[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'industry' => $item->industry,
                    'region' => $item->region,
                    'district' => $item->district,
                    'instagram_handle' => $item->instagram_handle,
                    'telegram_handle' => $item->telegram_handle,
                    'swot_contributors_count' => $item->swot_contributors_count ?? 0,
                    'has_swot' => ($item->swot_contributors_count ?? 0) > 0,
                    'same_industry' => $item->industry === $industry,
                    'same_region' => $item->region === $region,
                ];
            }

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('searchGlobal error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}
