<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\ContentIdea;
use App\Models\ContentIdeaCollection;
use App\Models\ContentIdeaUsage;
use App\Services\ContentAI\ContentGeneratorService;
use App\Services\ContentAI\ContentIdeaRecommendationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentIdeaController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected ContentIdeaRecommendationService $recommendationService,
        protected ContentGeneratorService $generatorService
    ) {}

    /**
     * Content Ideas dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return redirect()->route('login');
        }

        $businessId = $business->id;

        // Smart recommendations
        $smartData = $this->recommendationService->getSmartRecommendations($businessId);

        // Collections
        $collections = $this->recommendationService->getCollections($businessId);

        // Categories
        $categories = ContentIdea::CATEGORIES;

        // Recent usage
        $recentUsages = ContentIdeaUsage::where('business_id', $businessId)
            ->with(['idea:id,title,content_type,category', 'generation:id,status,generated_content'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Marketing/ContentAI/Ideas', [
            'recommendations' => $smartData['recommendations'],
            'stats' => $smartData['stats'],
            'currentSeason' => $smartData['current_season'],
            'collections' => $collections,
            'categories' => $categories,
            'recentUsages' => $recentUsages,
            'contentTypes' => ContentIdea::CONTENT_TYPES,
            'purposes' => ContentIdea::PURPOSES,
            'seasons' => ContentIdea::SEASONS,
            'panelType' => 'business',
        ]);
    }

    /**
     * Get ideas by category
     */
    public function byCategory(Request $request, string $category)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $ideas = $this->recommendationService->getByCategory($business->id, $category, 20);

        return response()->json([
            'ideas' => $ideas,
            'category' => $category,
            'label' => ContentIdea::CATEGORIES[$category] ?? $category,
        ]);
    }

    /**
     * Get collection ideas
     */
    public function collection(string $collectionId)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $collection = ContentIdeaCollection::with(['ideas' => function ($q) {
            $q->where('is_active', true)->orderByDesc('quality_score');
        }])->findOrFail($collectionId);

        return response()->json([
            'collection' => $collection,
            'ideas' => $collection->ideas,
        ]);
    }

    /**
     * Use an idea - generate content from it
     */
    public function useIdea(Request $request, string $ideaId)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $idea = ContentIdea::findOrFail($ideaId);

        $validated = $request->validate([
            'generate_now' => 'boolean',
            'additional_context' => 'nullable|string|max:500',
        ]);

        // Record usage
        $usage = $idea->recordUsage(
            $business->id,
            auth()->id()
        );

        $result = [
            'usage' => $usage,
            'idea' => $idea,
            'generation' => null,
        ];

        // Generate content if requested
        if ($validated['generate_now'] ?? false) {
            $generation = $this->generatorService->generate(
                $business->id,
                auth()->id(),
                $idea->title,
                $idea->content_type,
                $idea->purpose,
                null,
                $idea->buildGenerationContext() . "\n" . ($validated['additional_context'] ?? '')
            );

            $usage->update(['content_generation_id' => $generation->id]);
            $result['generation'] = $generation;
        }

        return response()->json($result);
    }

    /**
     * Rate an idea usage
     */
    public function rateUsage(Request $request, string $usageId)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $usage = ContentIdeaUsage::where('business_id', $business->id)
            ->findOrFail($usageId);

        $validated = $request->validate([
            'rating' => 'required|in:helpful,neutral,not_helpful',
            'notes' => 'nullable|string|max:500',
        ]);

        $usage->rate($validated['rating'], $validated['notes']);

        return response()->json(['success' => true]);
    }

    /**
     * Create custom idea
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'required|string|max:1000',
            'example_content' => 'nullable|string|max:2000',
            'key_points' => 'nullable|array',
            'content_type' => 'required|in:post,story,reel,ad,carousel,article',
            'purpose' => 'required|in:educate,inspire,sell,engage,announce,entertain',
            'category' => 'nullable|string',
            'suggested_hashtags' => 'nullable|array',
        ]);

        $idea = ContentIdea::create([
            ...$validated,
            'business_id' => $business->id,
            'created_by_user_id' => auth()->id(),
            'industry_id' => $business->industry_id,
            'is_global' => false,
            'quality_score' => 50, // Starting score
        ]);

        return response()->json([
            'success' => true,
            'idea' => $idea,
        ]);
    }

    /**
     * Search ideas
     */
    public function search(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'content_type' => 'nullable|in:post,story,reel,ad,carousel,article',
            'purpose' => 'nullable|in:educate,inspire,sell,engage,announce,entertain',
            'category' => 'nullable|string',
        ]);

        $query = ContentIdea::query()
            ->where('is_active', true)
            ->where(function ($q) use ($business) {
                $q->where('is_global', true)
                    ->orWhere('business_id', $business->id)
                    ->orWhere('industry_id', $business->industry_id)
                    ->orWhereJsonContains('suitable_industries', $business->industry_id);
            })
            ->where(function ($q) use ($validated) {
                $q->where('title', 'like', '%' . $validated['query'] . '%')
                    ->orWhere('description', 'like', '%' . $validated['query'] . '%')
                    ->orWhereJsonContains('tags', $validated['query']);
            });

        if (!empty($validated['content_type'])) {
            $query->where('content_type', $validated['content_type']);
        }

        if (!empty($validated['purpose'])) {
            $query->where('purpose', $validated['purpose']);
        }

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $ideas = $query->orderByDesc('quality_score')->limit(20)->get();

        return response()->json(['ideas' => $ideas]);
    }

    /**
     * Mark usage as published with metrics
     */
    public function markPublished(Request $request, string $usageId)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $usage = ContentIdeaUsage::where('business_id', $business->id)
            ->findOrFail($usageId);

        $validated = $request->validate([
            'engagement_rate' => 'nullable|numeric|min:0|max:100',
            'likes_count' => 'nullable|integer|min:0',
            'comments_count' => 'nullable|integer|min:0',
            'shares_count' => 'nullable|integer|min:0',
        ]);

        $usage->markPublished($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Get trending ideas
     */
    public function trending()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $trending = $this->recommendationService->getTrending($business->id, 15);

        return response()->json(['ideas' => $trending]);
    }

    /**
     * Get seasonal ideas
     */
    public function seasonal()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $seasonal = $this->recommendationService->getSeasonalIdeas($business->id, 15);

        return response()->json(['ideas' => $seasonal]);
    }
}
