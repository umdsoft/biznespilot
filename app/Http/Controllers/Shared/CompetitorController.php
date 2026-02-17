<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Jobs\ScrapeCompetitorData;
use App\Models\Competitor;
use App\Models\CompetitorAlert;
use App\Models\GlobalCompetitor;
use App\Services\CompetitorAnalysisService;
use App\Services\CompetitorMonitoringService;
use App\Services\ContentAnalysisService;
use App\Services\MetaAdLibraryService;
use App\Services\PriceMonitoringService;
use App\Services\ReviewsMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CompetitorController extends Controller
{
    use HasCurrentBusiness;

    protected CompetitorMonitoringService $monitoringService;

    protected CompetitorAnalysisService $analysisService;

    protected ContentAnalysisService $contentService;

    protected MetaAdLibraryService $adService;

    protected PriceMonitoringService $priceService;

    protected ReviewsMonitoringService $reviewsService;

    protected int $cacheTTL = 600;

    public function __construct(
        CompetitorMonitoringService $monitoringService,
        CompetitorAnalysisService $analysisService,
        ContentAnalysisService $contentService,
        MetaAdLibraryService $adService,
        PriceMonitoringService $priceService,
        ReviewsMonitoringService $reviewsService
    ) {
        $this->monitoringService = $monitoringService;
        $this->analysisService = $analysisService;
        $this->contentService = $contentService;
        $this->adService = $adService;
        $this->priceService = $priceService;
        $this->reviewsService = $reviewsService;
    }

    // ==========================================
    // PANEL DETECTION
    // ==========================================

    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) {
            return 'marketing';
        }

        return 'business';
    }

    protected function getRoutePrefix(Request $request): string
    {
        $panel = $this->getPanelType($request);

        return match ($panel) {
            'marketing' => 'marketing.competitors',
            default => 'business.competitors',
        };
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    protected function authorizeCompetitor(Competitor $competitor): void
    {
        $business = $this->getCurrentBusiness();
        if (! $business || $competitor->business_id !== $business->id) {
            abort(403, 'Bu raqobatchiga kirish huquqi yo\'q');
        }
    }

    protected function authorizeAlert(CompetitorAlert $alert): void
    {
        $business = $this->getCurrentBusiness();
        if (! $business || $alert->business_id !== $business->id) {
            abort(403, 'Bu alertga kirish huquqi yo\'q');
        }
    }

    // ==========================================
    // INDEX & DASHBOARD
    // ==========================================

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $panelType = $this->getPanelType($request);
        $business->load('industryRelation');

        $query = Competitor::where('business_id', $business->id)
            ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(1)])
            ->withCount('metrics');

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

    public function dashboard(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $panelType = $this->getPanelType($request);

        $stats = Cache::remember("competitor_stats_{$business->id}", 300, function () use ($business) {
            return [
                'total_competitors' => Competitor::where('business_id', $business->id)->where('status', 'active')->count(),
                'active_monitoring' => Competitor::where('business_id', $business->id)->where('status', 'active')->where('auto_monitor', true)->count(),
                'high_threats' => Competitor::where('business_id', $business->id)->whereIn('threat_level', ['high', 'critical'])->count(),
                'unread_alerts' => CompetitorAlert::where('business_id', $business->id)->where('is_read', false)->count(),
            ];
        });

        $competitors = Competitor::where('business_id', $business->id)
            ->where('status', 'active')
            ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(1)])
            ->orderBy('threat_level', 'desc')
            ->limit(10)
            ->get();

        $unreadAlerts = CompetitorAlert::where('business_id', $business->id)
            ->where('is_read', false)
            ->with('competitor')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

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
     * API: Get competitive insights (cached)
     */
    public function getInsights(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $cacheKey = "competitor_insights_{$business->id}";

        $insights = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business) {
            return $this->analysisService->getCompetitiveInsights($business->id);
        });

        return response()->json(['insights' => $insights]);
    }

    /**
     * API: Get dashboard data (cached)
     */
    public function getDashboardData(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $cacheKey = "competitor_dashboard_{$business->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($business) {
            $competitors = Competitor::where('business_id', $business->id)
                ->where('status', 'active')
                ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(30)])
                ->get();

            $unreadAlerts = CompetitorAlert::where('business_id', $business->id)
                ->where('is_read', false)
                ->with('competitor')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

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

    // ==========================================
    // CRUD
    // ==========================================

    public function show(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);
        $panelType = $this->getPanelType($request);

        $competitor->load([
            'metrics' => fn ($q) => $q->latest('recorded_date')->limit(90),
            'activities' => fn ($q) => $q->latest('activity_date')->limit(20),
        ]);

        $latestMetric = $competitor->metrics->first();

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

    public function create(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $panelType = $this->getPanelType($request);
        $business->load('industryRelation');

        return Inertia::render('Shared/Competitors/Create', [
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry_name' => $business->industryRelation?->name_uz ?? $business->industry ?? '',
                'region' => $business->region ?? '',
            ],
            'panelType' => $panelType,
        ]);
    }

    public function edit(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);
        $business = $this->getCurrentBusiness();
        $panelType = $this->getPanelType($request);

        $business->load('industryRelation');

        return Inertia::render('Shared/Competitors/Edit', [
            'competitor' => $competitor,
            'currentBusiness' => [
                ...$business->toArray(),
                'industry_name' => $business->industryRelation?->name_uz ?? $business->industry ?? null,
            ],
            'panelType' => $panelType,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

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
            'status' => 'nullable|in:active,inactive,archived',
            'auto_monitor' => 'boolean',
            'check_frequency_hours' => 'nullable|integer|min:1|max:168',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'global_competitor_id' => 'nullable|integer|exists:global_competitors,id',
        ]);

        $competitor = Competitor::create(array_merge($validated, [
            'business_id' => $business->id,
            'status' => $validated['status'] ?? 'active',
        ]));

        if (empty($validated['global_competitor_id'])) {
            $competitor->syncWithGlobalCompetitor();
        }

        $this->analysisService->updateBusinessSwotFromCompetitor($business);

        return redirect()->route($routePrefix.'.show', $competitor)
            ->with('success', 'Raqib qo\'shildi va SWOT tahlil yangilandi');
    }

    public function update(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);
        $business = $this->getCurrentBusiness();

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
            'status' => 'required|in:active,inactive,archived',
            'auto_monitor' => 'boolean',
            'check_frequency_hours' => 'nullable|integer|min:1|max:168',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $competitor->update($validated);
        $competitor->syncWithGlobalCompetitor();

        $this->analysisService->updateBusinessSwotFromCompetitor($business);

        return back()->with('success', 'Yangilandi');
    }

    public function destroy(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);
        $business = $this->getCurrentBusiness();
        $routePrefix = $this->getRoutePrefix($request);

        $competitor->delete();

        $this->analysisService->updateBusinessSwotFromCompetitor($business);

        return redirect()->route($routePrefix.'.index')
            ->with('success', 'Raqib o\'chirildi va SWOT tahlil yangilandi');
    }

    // ==========================================
    // METRICS & MONITORING
    // ==========================================

    public function recordMetrics(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

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

        $date = isset($validated['date']) && $validated['date'] ? \Carbon\Carbon::parse($validated['date']) : null;
        unset($validated['date']);

        $this->monitoringService->recordManualMetrics($competitor, $validated, $date);

        return back()->with('success', 'Metrika saqlandi');
    }

    public function monitor(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);
        ScrapeCompetitorData::dispatch($competitor->id);

        return back()->with('success', 'Monitoring boshlandi');
    }

    // ==========================================
    // SWOT ANALYSIS
    // ==========================================

    public function generateSwot(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $swot = $this->analysisService->generateSWOTAnalysis($competitor);

        return response()->json([
            'success' => true,
            'swot' => $swot,
        ]);
    }

    public function swotIndex(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $panelType = $this->getPanelType($request);

        $swot = $business->swot_data ?? [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];

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

    public function generateBusinessSwot(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        try {
            $swot = $this->analysisService->generateBusinessSWOT($business);

            $settings = $business->settings ?? [];
            $settings['swot'] = $swot;
            $business->settings = $settings;
            $business->save();

            return response()->json([
                'success' => true,
                'swot' => $swot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveBusinessSwot(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $business->swot_data = $request->input('swot_data', []);
        $business->swot_updated_at = now();
        $business->save();

        return response()->json([
            'success' => true,
            'message' => 'SWOT saqlandi',
        ]);
    }

    public function generateCompetitorSwot(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $swot = [
            'strengths' => [
                $competitor->name.' kuchli brend nomiga ega',
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

    public function saveCompetitorSwot(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $competitor->swot_data = $request->input('swot_data');
        $competitor->swot_analyzed_at = now();
        $competitor->save();

        $competitor->syncWithGlobalCompetitor();

        return response()->json(['success' => true]);
    }

    // ==========================================
    // ALERTS
    // ==========================================

    public function alerts(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $query = CompetitorAlert::where('business_id', $business->id)
            ->with(['competitor', 'activity']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($alerts);
    }

    public function markAlertRead(Request $request, CompetitorAlert $alert)
    {
        $this->authorizeAlert($alert);
        $alert->markAsRead();

        return back()->with('success', 'Alert o\'qilgan deb belgilandi');
    }

    public function archiveAlert(Request $request, CompetitorAlert $alert)
    {
        $this->authorizeAlert($alert);
        $alert->archive();

        return back()->with('success', 'Alert arxivlandi');
    }

    // ==========================================
    // PRODUCTS (Price Tracking)
    // ==========================================

    public function addProduct(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'current_price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
        ]);

        $this->priceService->addProduct($competitor, $validated);
        Cache::forget("competitor_price_insights_{$competitor->id}");

        return back()->with('success', 'Mahsulot qo\'shildi');
    }

    public function deleteProduct(Request $request, Competitor $competitor, $productId)
    {
        $this->authorizeCompetitor($competitor);

        $product = $competitor->products()->findOrFail($productId);
        $product->delete();

        Cache::forget("competitor_price_insights_{$competitor->id}");

        return back()->with('success', 'Mahsulot o\'chirildi');
    }

    // ==========================================
    // ADS
    // ==========================================

    public function addAd(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,google,tiktok,youtube',
            'headline' => 'nullable|string|max:255',
            'body_text' => 'nullable|string',
            'call_to_action' => 'nullable|string|max:100',
            'destination_url' => 'nullable|url',
            'media_type' => 'nullable|in:image,video,carousel',
            'started_at' => 'nullable|date',
        ]);

        $this->adService->addManualAd($competitor, $validated);
        Cache::forget("competitor_ad_insights_{$competitor->id}");

        return back()->with('success', 'Reklama qo\'shildi');
    }

    public function deleteAd(Request $request, Competitor $competitor, $adId)
    {
        $this->authorizeCompetitor($competitor);

        $ad = $competitor->ads()->findOrFail($adId);
        $ad->delete();

        Cache::forget("competitor_ad_insights_{$competitor->id}");

        return back()->with('success', 'Reklama o\'chirildi');
    }

    // ==========================================
    // REVIEWS
    // ==========================================

    public function addReviewSource(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $validated = $request->validate([
            'platform' => 'required|in:google,2gis,yandex,facebook,instagram,custom',
            'profile_url' => 'required|url',
            'profile_name' => 'nullable|string|max:255',
        ]);

        $this->reviewsService->addReviewSource($competitor, $validated);
        Cache::forget("competitor_review_insights_{$competitor->id}");

        return back()->with('success', 'Sharh manbasi qo\'shildi');
    }

    public function deleteReviewSource(Request $request, Competitor $competitor, $sourceId)
    {
        $this->authorizeCompetitor($competitor);

        $source = $competitor->reviewSources()->findOrFail($sourceId);
        $source->delete();

        Cache::forget("competitor_review_insights_{$competitor->id}");

        return back()->with('success', 'Sharh manbasi o\'chirildi');
    }

    // ==========================================
    // CONTENT & SCANNING
    // ==========================================

    public function analyzeContent(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $results = $this->contentService->analyzeCompetitor($competitor);
        Cache::forget("competitor_content_insights_{$competitor->id}");

        $message = $results['success']
            ? "Kontent tahlil qilindi: {$results['new_content_count']} ta yangi post topildi"
            : 'Tahlil qilishda xatolik yuz berdi';

        return back()->with($results['success'] ? 'success' : 'error', $message);
    }

    public function scanAds(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $results = $this->adService->searchCompetitorAds($competitor);
        Cache::forget("competitor_ad_insights_{$competitor->id}");

        $message = $results['success']
            ? "Reklamalar skanerlandi: {$results['new_ads']} ta yangi reklama topildi"
            : 'Reklamalarni skanerlashda xatolik yuz berdi';

        return back()->with($results['success'] ? 'success' : 'error', $message);
    }

    public function scanReviews(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $results = $this->reviewsService->monitorReviews($competitor);
        Cache::forget("competitor_review_insights_{$competitor->id}");

        $message = $results['success']
            ? "Sharhlar skanerlandi: {$results['new_reviews']} ta yangi sharh topildi"
            : 'Sharhlarni skanerlashda xatolik yuz berdi';

        return back()->with($results['success'] ? 'success' : 'error', $message);
    }

    // ==========================================
    // GLOBAL COMPETITORS
    // ==========================================

    public function searchGlobal(Request $request)
    {
        try {
            $search = trim($request->input('q', ''));

            if (strlen($search) < 3) {
                return response()->json([]);
            }

            $business = $this->getCurrentBusiness();
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
            \Log::error('searchGlobal error: '.$e->getMessage());

            return response()->json([]);
        }
    }

    public function getGlobalCompetitor(Request $request, $id)
    {
        $globalCompetitor = GlobalCompetitor::find($id);

        if (! $globalCompetitor) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $globalCompetitor->id,
            'name' => $globalCompetitor->name,
            'industry' => $globalCompetitor->industry,
            'region' => $globalCompetitor->region,
            'district' => $globalCompetitor->district,
            'description' => $globalCompetitor->description,
            'instagram_handle' => $globalCompetitor->instagram_handle,
            'telegram_handle' => $globalCompetitor->telegram_handle,
            'facebook_page' => $globalCompetitor->facebook_page,
            'tiktok_handle' => $globalCompetitor->tiktok_handle,
            'youtube_channel' => $globalCompetitor->youtube_channel,
            'website' => $globalCompetitor->website,
            'swot_data' => $globalCompetitor->swot_data,
            'swot_contributors_count' => $globalCompetitor->swot_contributors_count ?? 0,
        ]);
    }
}
