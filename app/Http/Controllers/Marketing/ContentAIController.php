<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\ContentGeneration;
use App\Models\ContentStyleGuide;
use App\Models\ContentTemplate;
use App\Models\Offer;
use App\Services\ContentAI\ContentAnalyzerService;
use App\Services\ContentAI\ContentGeneratorService;
use App\Services\ContentAI\ContentStyleGuideService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContentAIController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected ContentGeneratorService $generator,
        protected ContentStyleGuideService $styleGuideService,
        protected ContentAnalyzerService $analyzer,
        protected PlanLimitService $planLimitService
    ) {}

    /**
     * Content AI Dashboard
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;

        // Style guide
        $styleGuide = ContentStyleGuide::getOrCreate($businessId);

        // Statistics
        $stats = [
            'templates_count' => ContentTemplate::where('business_id', $businessId)->count(),
            'top_performers' => ContentTemplate::where('business_id', $businessId)->where('is_top_performer', true)->count(),
            'generations_count' => ContentGeneration::where('business_id', $businessId)->count(),
            'generations_this_month' => ContentGeneration::where('business_id', $businessId)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'total_tokens' => ContentGeneration::where('business_id', $businessId)->sum('total_tokens'),
            'total_cost' => ContentGeneration::where('business_id', $businessId)->sum('cost_usd'),
        ];

        // Recent generations
        $recentGenerations = ContentGeneration::where('business_id', $businessId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Top performing templates
        $topTemplates = ContentTemplate::where('business_id', $businessId)
            ->where('is_top_performer', true)
            ->orderByDesc('performance_score')
            ->limit(5)
            ->get();

        // Active offers for dropdown
        $activeOffers = Offer::where('business_id', $businessId)
            ->active()
            ->select('id', 'name', 'core_offer', 'value_proposition', 'pricing', 'pricing_model')
            ->orderBy('name')
            ->get();

        return Inertia::render('Marketing/ContentAI/Index', [
            'styleGuide' => $styleGuide,
            'stats' => $stats,
            'recentGenerations' => $recentGenerations,
            'topTemplates' => $topTemplates,
            'activeOffers' => $activeOffers,
            'tones' => ContentStyleGuide::TONES,
            'languageStyles' => ContentStyleGuide::LANGUAGE_STYLES,
            'ctaStyles' => ContentStyleGuide::CTA_STYLES,
            'panelType' => 'business',
        ]);
    }

    /**
     * Generate new content
     */
    public function generate(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // AI so'rovlar limitini tekshirish
        if ($this->planLimitService->hasReachedLimit($business, 'ai_requests')) {
            $message = 'Oylik AI so\'rovlar limiti tugadi. Tarifni yangilang.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return back()->with('error', $message);
        }

        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'content_type' => 'required|in:post,story,reel,ad,carousel,article',
            'purpose' => 'required|in:educate,inspire,sell,engage,announce,entertain',
            'target_channel' => 'nullable|in:instagram,telegram,facebook,tiktok',
            'additional_prompt' => 'nullable|string|max:500',
            'offer_id' => 'nullable|uuid|exists:offers,id',
        ]);

        $generation = $this->generator->generate(
            $business->id,
            auth()->id(),
            $validated['topic'],
            $validated['content_type'],
            $validated['purpose'],
            $validated['target_channel'],
            $validated['additional_prompt'] ?? null,
            $validated['offer_id'] ?? null
        );

        // Muvaffaqiyatli bo'lsa cache tozalash
        if ($generation->status === 'completed') {
            \Illuminate\Support\Facades\Cache::forget("business_{$business->id}_ai_requests_" . now()->format('Y_m'));
        }

        if ($generation->status === 'failed') {
            $errorMsg = $generation->error_message ?: 'Kontent yaratishda xatolik yuz berdi.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        if ($request->wantsJson()) {
            return response()->json(['generation' => $generation]);
        }

        return back()->with([
            'success' => 'Kontent muvaffaqiyatli yaratildi!',
            'generation' => $generation,
        ]);
    }

    /**
     * Generate 10 content ideas (DB cache + AI fallback)
     *
     * force_new=true bo'lsa har doim AI chaqiriladi
     * force_new=false bo'lsa avval DB dan tekshiriladi (token tejash)
     */
    public function generateIdeas(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'content_type' => 'required|in:post,story,reel,ad,carousel,article',
            'purpose' => 'required|in:educate,inspire,sell,engage,announce,entertain',
            'target_channel' => 'nullable|in:instagram,telegram,facebook,tiktok',
            'offer_id' => 'nullable|uuid|exists:offers,id',
            'force_new' => 'nullable|boolean',
        ]);

        $forceNew = $validated['force_new'] ?? false;

        // AI limit faqat yangi generatsiya uchun tekshiriladi (cache dan olsa limit sarflanmaydi)
        if ($forceNew && $this->planLimitService->hasReachedLimit($business, 'ai_requests')) {
            return response()->json(['error' => 'Oylik AI so\'rovlar limiti tugadi. Tarifni yangilang.'], 403);
        }

        try {
            $result = $this->generator->generateIdeas(
                $business->id,
                auth()->id(),
                $validated['content_type'],
                $validated['purpose'],
                $validated['target_channel'] ?? null,
                $validated['offer_id'] ?? null,
                $forceNew
            );

            // Cache dan olgan bo'lsa limit sarflanmaydi
            if (!($result['from_cache'] ?? false)) {
                \Illuminate\Support\Facades\Cache::forget("business_{$business->id}_ai_requests_" . now()->format('Y_m'));
            }

            return response()->json([
                'ideas' => $result['ideas'],
                'from_cache' => $result['from_cache'] ?? false,
                'cached_total' => $result['cached_total'] ?? 0,
                'tokens' => $result['tokens'],
            ]);

        } catch (\RuntimeException $e) {
            // User-friendly xabar (service dan)
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('ContentAIController: Ideas failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'G\'oyalar yaratishda xatolik. Qayta urinib ko\'ring.'], 500);
        }
    }

    /**
     * Generate variations for A/B testing
     */
    public function generateVariations(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'variations_count' => 'nullable|integer|min:2|max:5',
            'target_channel' => 'nullable|in:instagram,telegram,facebook,tiktok',
        ]);

        $generation = $this->generator->generateVariations(
            $business->id,
            auth()->id(),
            $validated['topic'],
            $validated['variations_count'] ?? 3,
            $validated['target_channel'] ?? null
        );

        if ($generation->status === 'failed') {
            return back()->with('error', 'Variantlar generatsiya qilinmadi');
        }

        return back()->with([
            'success' => 'Variantlar yaratildi!',
            'generation' => $generation,
        ]);
    }

    /**
     * Rewrite existing content
     */
    public function rewrite(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'style' => 'required|in:improve,shorter,longer,engaging,formal,casual',
            'target_channel' => 'nullable|in:instagram,telegram,facebook,tiktok',
        ]);

        $generation = $this->generator->rewrite(
            $business->id,
            auth()->id(),
            $validated['content'],
            $validated['style'],
            $validated['target_channel'] ?? null
        );

        if ($generation->status === 'failed') {
            return back()->with('error', 'Kontent qayta yozilmadi');
        }

        return back()->with([
            'success' => 'Kontent qayta yozildi!',
            'generation' => $generation,
        ]);
    }

    /**
     * Generate hashtags
     */
    public function generateHashtags(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'count' => 'nullable|integer|min:5|max:30',
        ]);

        $hashtags = $this->generator->generateHashtags(
            $business->id,
            $validated['content'],
            $validated['count'] ?? 10
        );

        return response()->json(['hashtags' => $hashtags]);
    }

    /**
     * Style Guide settings page
     */
    public function styleGuide()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $styleGuide = ContentStyleGuide::getOrCreate($business->id);

        return Inertia::render('Marketing/ContentAI/StyleGuide', [
            'styleGuide' => $styleGuide,
            'tones' => ContentStyleGuide::TONES,
            'languageStyles' => ContentStyleGuide::LANGUAGE_STYLES,
            'ctaStyles' => ContentStyleGuide::CTA_STYLES,
            'panelType' => 'business',
        ]);
    }

    /**
     * Update style guide
     */
    public function updateStyleGuide(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'tone' => 'required|in:formal,casual,professional,friendly,playful',
            'language_style' => 'required|in:simple,technical,creative,persuasive',
            'emoji_frequency' => 'required|in:none,low,medium,high',
            'min_post_length' => 'required|integer|min:50|max:500',
            'max_post_length' => 'required|integer|min:100|max:2000',
            'avg_hashtag_count' => 'required|integer|min:0|max:30',
            'cta_style' => 'required|in:soft,direct,urgent,none',
            'creativity_level' => 'required|numeric|min:0|max:1',
            'content_pillars' => 'nullable|array',
            'common_hashtags' => 'nullable|array',
        ]);

        $this->styleGuideService->updateStyleGuide($business->id, $validated);

        return back()->with('success', 'Style guide yangilandi');
    }

    /**
     * Analyze existing posts and build style guide
     */
    public function analyzeAndBuild()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        // Postlarni tahlil qilish
        $this->analyzer->analyzeMultiplePosts($business->id, 50);

        // Style guide yaratish
        $styleGuide = $this->styleGuideService->buildStyleGuide($business->id);

        return back()->with('success', "Style guide yangilandi. {$styleGuide->analyzed_posts_count} ta post tahlil qilindi.");
    }

    /**
     * Templates library
     */
    public function templates(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $query = ContentTemplate::where('business_id', $business->id)
            ->orderByDesc('performance_score');

        if ($request->has('type')) {
            $query->where('content_type', $request->type);
        }

        if ($request->has('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        if ($request->has('top_only') && $request->top_only) {
            $query->where('is_top_performer', true);
        }

        $templates = $query->paginate(20);

        return Inertia::render('Marketing/ContentAI/Templates', [
            'templates' => $templates,
            'contentTypes' => ContentTemplate::CONTENT_TYPES,
            'purposes' => ContentTemplate::PURPOSES,
            'filters' => [
                'type' => $request->type,
                'purpose' => $request->purpose,
                'top_only' => $request->top_only,
            ],
            'panelType' => 'business',
        ]);
    }

    /**
     * Add new template
     */
    public function storeTemplate(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'content_type' => 'required|in:post,story,reel,ad,carousel,article',
            'purpose' => 'required|in:educate,inspire,sell,engage,announce,entertain',
            'target_channel' => 'nullable|string',
            'likes_count' => 'nullable|integer|min:0',
            'comments_count' => 'nullable|integer|min:0',
            'shares_count' => 'nullable|integer|min:0',
            'posted_at' => 'nullable|date',
        ]);

        $template = ContentTemplate::create([
            'business_id' => $business->id,
            'source_type' => 'manual',
            'content' => $validated['content'],
            'content_cleaned' => ContentTemplate::extractHashtags($validated['content']) ?
                preg_replace('/#\w+/u', '', $validated['content']) : $validated['content'],
            'hashtags' => ContentTemplate::extractHashtags($validated['content']),
            'content_type' => $validated['content_type'],
            'purpose' => $validated['purpose'],
            'target_channel' => $validated['target_channel'],
            'likes_count' => $validated['likes_count'] ?? 0,
            'comments_count' => $validated['comments_count'] ?? 0,
            'shares_count' => $validated['shares_count'] ?? 0,
            'posted_at' => $validated['posted_at'],
        ]);

        // Performance score hisoblash
        $template->updatePerformanceScore();

        // AI tahlil (async bo'lishi mumkin)
        $this->analyzer->analyzePost($template);

        return back()->with('success', 'Template qo\'shildi');
    }

    /**
     * Delete a template
     */
    public function destroyTemplate(string $templateId)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $template = ContentTemplate::where('business_id', $business->id)
            ->findOrFail($templateId);

        $template->delete();

        return back()->with('success', 'Template o\'chirildi');
    }

    /**
     * Generation history
     */
    public function history(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $generations = ContentGeneration::where('business_id', $business->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Monthly stats
        $monthlyStats = ContentGeneration::where('business_id', $business->id)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_tokens) as tokens')
            ->selectRaw('SUM(cost_usd) as cost')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(6)
            ->get();

        return Inertia::render('Marketing/ContentAI/History', [
            'generations' => $generations,
            'monthlyStats' => $monthlyStats,
            'statuses' => ContentGeneration::STATUSES,
            'panelType' => 'business',
        ]);
    }

    /**
     * Rate a generation
     */
    public function rateGeneration(Request $request, string $generationId)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $generation = ContentGeneration::where('business_id', $business->id)
            ->findOrFail($generationId);

        $validated = $request->validate([
            'rating' => 'required|in:good,neutral,bad',
            'feedback' => 'nullable|string|max:500',
        ]);

        $generation->rate($validated['rating'], $validated['feedback']);

        return back()->with('success', 'Baholandi');
    }

    /**
     * API: Get AI suggestions
     */
    public function getSuggestions(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $styleGuide = ContentStyleGuide::getOrCreate($business->id);

        $suggestions = [
            'topics' => $styleGuide->content_pillars ?? [],
            'best_times' => $styleGuide->best_posting_times ?? [],
            'hashtags' => array_slice($styleGuide->common_hashtags ?? [], 0, 10),
            'cta_examples' => array_slice($styleGuide->cta_patterns ?? [], 0, 5),
        ];

        return response()->json($suggestions);
    }
}
