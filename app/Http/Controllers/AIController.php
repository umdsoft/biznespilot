<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AIController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        // Get counts for stats
        $stats = [
            'dream_buyers' => DreamBuyer::where('business_id', $currentBusiness->id)->count(),
            'competitors' => Competitor::where('business_id', $currentBusiness->id)->count(),
            'offers' => Offer::where('business_id', $currentBusiness->id)->count(),
        ];

        return Inertia::render('Business/AI/Index', [
            'stats' => $stats,
            'hasApiKey' => $user->settings &&
                         ($user->settings->openai_api_key || $user->settings->claude_api_key),
        ]);
    }

    /**
     * Analyze Dream Buyer
     */
    public function analyzeDreamBuyer(Request $request)
    {
        $validated = $request->validate([
            'dream_buyer_id' => ['required', 'exists:dream_buyers,id'],
        ]);

        $dreamBuyer = DreamBuyer::findOrFail($validated['dream_buyer_id']);

        // Check business ownership
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($dreamBuyer->business_id !== $currentBusiness->id) {
            abort(403);
        }

        try {
            $analysis = $this->aiService->analyzeDreamBuyer([
                'name' => $dreamBuyer->name,
                'demographics' => $dreamBuyer->demographics,
                'psychographics' => $dreamBuyer->psychographics,
                'goals' => $dreamBuyer->goals,
                'challenges' => $dreamBuyer->challenges,
                'values' => $dreamBuyer->values,
            ]);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate marketing content
     */
    public function generateContent(Request $request)
    {
        $validated = $request->validate([
            'content_type' => ['required', 'string', 'max:100'],
            'topic' => ['required', 'string', 'max:500'],
            'target_audience' => ['nullable', 'string', 'max:500'],
            'tone' => ['nullable', 'string', 'max:100'],
            'keywords' => ['nullable', 'array'],
        ]);

        try {
            $content = $this->aiService->generateMarketingContent(
                $validated['content_type'],
                $validated
            );

            return response()->json([
                'success' => true,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze competitor
     */
    public function analyzeCompetitor(Request $request)
    {
        $validated = $request->validate([
            'competitor_id' => ['required', 'exists:competitors,id'],
        ]);

        $competitor = Competitor::findOrFail($validated['competitor_id']);

        // Check business ownership
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($competitor->business_id !== $currentBusiness->id) {
            abort(403);
        }

        try {
            $analysis = $this->aiService->analyzeCompetitor([
                'name' => $competitor->name,
                'website' => $competitor->website,
                'products' => $competitor->products ?? [],
                'pricing' => $competitor->pricing ?? [],
                'marketing_strategies' => $competitor->marketing_strategies ?? [],
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
            ]);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize offer
     */
    public function optimizeOffer(Request $request)
    {
        $validated = $request->validate([
            'offer_id' => ['required', 'exists:offers,id'],
        ]);

        $offer = Offer::findOrFail($validated['offer_id']);

        // Check business ownership
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($offer->business_id !== $currentBusiness->id) {
            abort(403);
        }

        try {
            $optimization = $this->aiService->optimizeOffer([
                'name' => $offer->name,
                'value_proposition' => $offer->value_proposition,
                'pricing' => $offer->pricing,
                'target_audience' => $offer->target_audience,
                'guarantees' => $offer->guarantees,
                'bonuses' => $offer->bonuses,
            ]);

            return response()->json([
                'success' => true,
                'optimization' => $optimization,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get business advice (general AI assistant)
     */
    public function getAdvice(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'context' => ['nullable', 'array'],
        ]);

        try {
            $advice = $this->aiService->getBusinessAdvice(
                $validated['question'],
                $validated['context'] ?? []
            );

            return response()->json([
                'success' => true,
                'advice' => $advice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
