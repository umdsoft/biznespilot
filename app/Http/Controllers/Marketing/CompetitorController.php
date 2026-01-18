<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Jobs\ScrapeCompetitorData;
use App\Models\Business;
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

    /**
     * Authorize competitor belongs to current business
     */
    protected function authorizeCompetitor(Competitor $competitor): void
    {
        $business = $this->getCurrentBusiness();
        if (! $business || $competitor->business_id !== $business->id) {
            abort(403, 'Bu raqobatchiga kirish huquqi yo\'q');
        }
    }

    /**
     * Authorize alert belongs to current business
     */
    protected function authorizeAlert(CompetitorAlert $alert): void
    {
        $business = $this->getCurrentBusiness();
        if (! $business || $alert->business_id !== $business->id) {
            abort(403, 'Bu alertga kirish huquqi yo\'q');
        }
    }

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $business->load('industryRelation');

        $competitors = Competitor::where('business_id', $business->id)
            ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(1)])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Shared/Competitors/Index', [
            'competitors' => $competitors,
            'stats' => [
                'total' => $competitors->count(),
                'active' => $competitors->where('status', 'active')->count(),
                'critical' => $competitors->where('threat_level', 'critical')->count(),
                'high' => $competitors->where('threat_level', 'high')->count(),
            ],
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry_name' => $business->industryRelation?->name ?? $business->industry ?? '',
                'region' => $business->region ?? '',
            ],
            'panelType' => 'marketing',
        ]);
    }

    public function dashboard(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $competitors = Competitor::where('business_id', $business->id)
            ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(30)])
            ->orderBy('threat_level', 'desc')
            ->get();

        return Inertia::render('Marketing/Competitors/Dashboard', [
            'competitors' => $competitors,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function show(Request $request, Competitor $competitor)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $competitor->business_id !== $business->id) {
            abort(403);
        }

        $competitor->load(['metrics' => fn ($q) => $q->orderBy('recorded_date', 'desc')->limit(90)]);

        return Inertia::render('Marketing/Competitors/Show', [
            'competitor' => $competitor,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        return Inertia::render('Marketing/Competitors/Create', [
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry_name' => $business->industryRelation?->name ?? $business->industry ?? '',
                'region' => $business->region ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'threat_level' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:active,inactive,archived',
            'instagram_handle' => 'nullable|string|max:255',
            'telegram_handle' => 'nullable|string|max:255',
            'facebook_page' => 'nullable|string|max:255',
            'tiktok_handle' => 'nullable|string|max:255',
            'auto_monitor' => 'boolean',
            'check_frequency_hours' => 'nullable|integer|min:1|max:168',
            'global_competitor_id' => 'nullable|integer|exists:global_competitors,id',
        ]);

        $validated['business_id'] = $business->id;

        $competitor = Competitor::create($validated);

        // Sync with global competitor database if not already linked
        if (empty($validated['global_competitor_id'])) {
            $competitor->syncWithGlobalCompetitor();
        }

        return redirect()->route('marketing.competitors.index')
            ->with('success', 'Raqobatchi muvaffaqiyatli qo\'shildi');
    }

    public function update(Request $request, Competitor $competitor)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $competitor->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'threat_level' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:active,inactive,archived',
            'instagram_handle' => 'nullable|string|max:255',
            'telegram_handle' => 'nullable|string|max:255',
            'facebook_page' => 'nullable|string|max:255',
            'tiktok_handle' => 'nullable|string|max:255',
            'auto_monitor' => 'boolean',
            'check_frequency_hours' => 'nullable|integer|min:1|max:168',
        ]);

        $competitor->update($validated);

        // Sync with global competitor database
        $competitor->syncWithGlobalCompetitor();

        return redirect()->route('marketing.competitors.index')
            ->with('success', 'Raqobatchi muvaffaqiyatli yangilandi');
    }

    public function destroy(Competitor $competitor)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $competitor->business_id !== $business->id) {
            abort(403);
        }

        $competitor->delete();

        return redirect()->route('marketing.competitors.index')
            ->with('success', 'Raqobatchi o\'chirildi');
    }

    public function swotIndex()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get competitors with full data including swot_data and global competitor
        $competitors = Competitor::where('business_id', $business->id)
            ->with('globalCompetitor')
            ->select('id', 'name', 'threat_level', 'instagram_handle', 'telegram_handle', 'swot_data', 'swot_analyzed_at', 'global_competitor_id', 'region', 'district')
            ->orderBy('threat_level', 'desc')
            ->get()
            ->map(function ($competitor) {
                // Use effective SWOT data (local or global)
                $competitor->effective_swot_data = $competitor->effective_swot_data;
                $competitor->global_swot_count = $competitor->globalCompetitor?->swot_count ?? 0;
                $competitor->global_contributors = $competitor->globalCompetitor?->swot_contributors_count ?? 0;

                return $competitor;
            });

        $swotData = $business->swot_data ?? [];

        return Inertia::render('Shared/Swot/Index', [
            'competitors' => $competitors,
            'swot' => $swotData,
            'competitorCount' => $competitors->count(),
            'lastUpdated' => $business->swot_updated_at ?? null,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'panelType' => 'marketing',
        ]);
    }

    public function generateBusinessSwot(Request $request)
    {
        // SWOT generation logic - reuse from main CompetitorController
        return response()->json(['message' => 'SWOT yaratish funksiyasi']);
    }

    public function saveBusinessSwot(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $business->swot_data = $request->input('swot_data');
        $business->swot_updated_at = now();
        $business->save();

        return response()->json(['success' => true]);
    }

    /**
     * Generate SWOT analysis for a specific competitor
     */
    public function generateCompetitorSwot(Request $request, Competitor $competitor)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $competitor->business_id !== $business->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Generate mock SWOT for now - in production this would use AI
        $swot = [
            'strengths' => [
                $competitor->name.' kuchli brend nomiga ega',
                'Keng mijozlar bazasi mavjud',
                'Doimiy marketing faoliyati olib borilmoqda',
            ],
            'weaknesses' => [
                'Narxlari yuqori bo\'lishi mumkin',
                'Xizmat ko\'rsatish tezligi past',
                'Onlayn mavjudligi cheklangan',
            ],
            'opportunities' => [
                'Ular yo\'q qilgan bozor bo\'shliqlaridan foydalaning',
                'Ularning narx strategiyasidan farqlanish',
                'Mijozlarga yaxshiroq xizmat ko\'rsatish',
            ],
            'threats' => [
                'Kuchli reklama byudjeti',
                'Tajribali jamoa',
                'Ko\'p yillik bozor tajribasi',
            ],
        ];

        // Save to competitor
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
        $business = $this->getCurrentBusiness();

        if (! $business || $competitor->business_id !== $business->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $swotData = $request->input('swot_data');

        // Save to local competitor
        $competitor->swot_data = $swotData;
        $competitor->swot_analyzed_at = now();
        $competitor->save();

        // Sync with global competitor
        $competitor->syncWithGlobalCompetitor();

        return response()->json(['success' => true]);
    }

    /**
     * Search global competitors for autocomplete
     * Returns JSON array of matching competitors
     */
    public function searchGlobal(Request $request)
    {
        try {
            $search = trim($request->input('q', ''));

            // Minimum 3 characters
            if (strlen($search) < 3) {
                return response()->json([]);
            }

            $business = $this->getCurrentBusiness();
            $industry = $business?->industryRelation?->name ?? $business?->industry ?? null;
            $region = $business?->region ?? null;

            // Query global_competitors table only
            $results = GlobalCompetitor::query()
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
                ->select(['id', 'name', 'industry', 'region', 'district', 'instagram_handle', 'telegram_handle', 'swot_contributors_count'])
                ->limit(8)
                ->get();

            // Transform to plain array
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

    /**
     * Get global competitor details by ID
     */
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

    /**
     * Get alerts list
     */
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

    /**
     * Mark alert as read
     */
    public function markAlertRead(Request $request, CompetitorAlert $alert)
    {
        $this->authorizeAlert($alert);
        $alert->markAsRead();

        return back()->with('success', 'Alert o\'qilgan deb belgilandi');
    }

    /**
     * Archive alert
     */
    public function archiveAlert(Request $request, CompetitorAlert $alert)
    {
        $this->authorizeAlert($alert);
        $alert->archive();

        return back()->with('success', 'Alert arxivlandi');
    }

    /**
     * Record manual metrics
     */
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

    /**
     * Trigger monitoring for competitor
     */
    public function monitor(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);
        ScrapeCompetitorData::dispatch($competitor->id);

        return back()->with('success', 'Monitoring boshlandi');
    }

    /**
     * Generate SWOT analysis for competitor
     */
    public function generateSwot(Request $request, Competitor $competitor)
    {
        $this->authorizeCompetitor($competitor);

        $swot = $this->analysisService->generateSWOTAnalysis($competitor);

        return response()->json([
            'success' => true,
            'swot' => $swot,
        ]);
    }

    /**
     * Add product for price tracking
     */
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

    /**
     * Delete tracked product
     */
    public function deleteProduct(Request $request, Competitor $competitor, $productId)
    {
        $this->authorizeCompetitor($competitor);

        $product = $competitor->products()->findOrFail($productId);
        $product->delete();

        Cache::forget("competitor_price_insights_{$competitor->id}");

        return back()->with('success', 'Mahsulot o\'chirildi');
    }

    /**
     * Add manual ad entry
     */
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

    /**
     * Delete ad entry
     */
    public function deleteAd(Request $request, Competitor $competitor, $adId)
    {
        $this->authorizeCompetitor($competitor);

        $ad = $competitor->ads()->findOrFail($adId);
        $ad->delete();

        Cache::forget("competitor_ad_insights_{$competitor->id}");

        return back()->with('success', 'Reklama o\'chirildi');
    }

    /**
     * Add review source
     */
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

    /**
     * Delete review source
     */
    public function deleteReviewSource(Request $request, Competitor $competitor, $sourceId)
    {
        $this->authorizeCompetitor($competitor);

        $source = $competitor->reviewSources()->findOrFail($sourceId);
        $source->delete();

        Cache::forget("competitor_review_insights_{$competitor->id}");

        return back()->with('success', 'Sharh manbasi o\'chirildi');
    }

    /**
     * Analyze competitor content
     */
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

    /**
     * Scan for competitor ads
     */
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

    /**
     * Scan for reviews
     */
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
}
