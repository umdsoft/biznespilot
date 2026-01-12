<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Services\OfferBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OffersController extends Controller
{
    use HasCurrentBusiness;

    protected OfferBuilderService $offerBuilderService;

    public function __construct(OfferBuilderService $offerBuilderService)
    {
        $this->offerBuilderService = $offerBuilderService;
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
            'marketing' => 'marketing.offers',
            'finance' => 'finance.offers',
            'operator' => 'operator.offers',
            'saleshead' => 'saleshead.offers',
            default => 'business.offers',
        };
    }

    /**
     * Display listing of offers
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);

        $offers = Offer::where('business_id', $business->id)
            ->withCount('components')
            ->latest()
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'name' => $offer->name,
                    'description' => $offer->description,
                    'core_offer' => $offer->core_offer,
                    'pricing' => $offer->pricing,
                    'pricing_model' => $offer->pricing_model,
                    'status' => $offer->status,
                    'conversion_rate' => $offer->conversion_rate,
                    'components_count' => $offer->components_count,
                    'value_score' => $offer->value_score,
                    'total_value' => $offer->total_value,
                    'guarantee_type' => $offer->guarantee_type,
                    'created_at' => $offer->created_at->format('d.m.Y'),
                ];
            });

        $stats = [
            'total_offers' => $offers->count(),
            'active_offers' => $offers->where('status', 'active')->count(),
            'draft_offers' => $offers->where('status', 'draft')->count(),
            'avg_conversion' => round($offers->avg('conversion_rate'), 2),
            'total_value' => $offers->sum('total_value'),
        ];

        return Inertia::render('Shared/Offers/Index', [
            'offers' => $offers,
            'stats' => $stats,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Show offer builder wizard
     */
    public function create(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->get(['id', 'name', 'description']);

        return Inertia::render('Shared/Offers/Builder', [
            'dreamBuyers' => $dreamBuyers,
            'isEdit' => false,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Store new offer
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index');
        }

        $routePrefix = $this->getRoutePrefix($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_name' => 'nullable|string',
            'product_description' => 'nullable|string',
            'main_benefit' => 'nullable|string',
            'value_proposition' => 'required|string',
            'target_audience' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'pricing_model' => 'nullable|string',
            'core_offer' => 'nullable|string',
            'guarantees' => 'nullable|string',
            'guarantee_type' => 'nullable|string',
            'guarantee_terms' => 'nullable|string',
            'guarantee_period_days' => 'nullable|integer',
            'bonuses' => 'nullable|string',
            'scarcity' => 'nullable|string',
            'urgency' => 'nullable|string',
            'dream_outcome_score' => 'nullable|integer|min:1|max:10',
            'perceived_likelihood_score' => 'nullable|integer|min:1|max:10',
            'time_delay_days' => 'nullable|integer|min:1',
            'effort_score' => 'nullable|integer|min:1|max:10',
            'total_value' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,paused,archived',
            'metadata' => 'nullable|array',
            'dream_buyer_id' => 'nullable|exists:dream_buyers,id',
            'offer_components' => 'nullable|array',
            'generate_ai' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // Generate AI offer if requested
            if ($request->boolean('generate_ai')) {
                $dreamBuyer = $request->dream_buyer_id
                    ? DreamBuyer::find($request->dream_buyer_id)
                    : null;

                $aiOffer = $this->offerBuilderService->generateOffer([
                    'product_name' => $validated['product_name'] ?? $validated['name'],
                    'product_description' => $validated['product_description'] ?? $validated['description'],
                    'main_benefit' => $validated['main_benefit'] ?? '',
                    'price' => $validated['pricing'] ?? 0,
                    'target_audience' => $validated['target_audience'] ?? '',
                ], $dreamBuyer);

                $validated = array_merge($validated, [
                    'name' => $aiOffer['name'] ?? $validated['name'],
                    'core_offer' => $aiOffer['core_offer'] ?? '',
                    'value_proposition' => $aiOffer['value_proposition'] ?? $validated['value_proposition'],
                    'guarantee_type' => $aiOffer['guarantee_type'] ?? null,
                    'guarantee_terms' => $aiOffer['guarantee_terms'] ?? null,
                    'guarantee_period_days' => $aiOffer['guarantee_period_days'] ?? null,
                    'scarcity' => $aiOffer['scarcity'] ?? null,
                    'urgency' => $aiOffer['urgency'] ?? null,
                    'dream_outcome_score' => $aiOffer['dream_outcome_score'] ?? 5,
                    'perceived_likelihood_score' => $aiOffer['perceived_likelihood_score'] ?? 5,
                    'time_delay_days' => $aiOffer['time_delay_days'] ?? 30,
                    'effort_score' => $aiOffer['effort_score'] ?? 5,
                    'total_value' => $aiOffer['total_value'] ?? 0,
                    'pricing_model' => $aiOffer['pricing_model'] ?? $validated['pricing_model'],
                    'metadata' => [
                        'headline' => $aiOffer['headline'] ?? '',
                        'subheadline' => $aiOffer['subheadline'] ?? '',
                        'main_cta' => $aiOffer['main_cta'] ?? '',
                        'ai_generated' => true,
                    ],
                ]);

                $validated['offer_components'] = $aiOffer['offer_components'] ?? [];
            }

            $validated['business_id'] = $business->id;
            $offer = Offer::create($validated);

            if (!empty($validated['offer_components'])) {
                foreach ($validated['offer_components'] as $component) {
                    $offer->components()->create($component);
                }
            }

            DB::commit();

            return redirect()->route($routePrefix . '.show', $offer)
                ->with('success', 'Offer muvaffaqiyatli yaratildi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Offer yaratishda xatolik: ' . $e->getMessage()]);
        }
    }

    /**
     * Display offer details
     */
    public function show(Request $request, Offer $offer)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $offer->business_id !== $business->id) {
            abort(403);
        }

        $panelType = $this->getPanelType($request);
        $offer->load('components');

        return Inertia::render('Shared/Offers/Show', [
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'description' => $offer->description,
                'core_offer' => $offer->core_offer,
                'value_proposition' => $offer->value_proposition,
                'target_audience' => $offer->target_audience,
                'pricing' => $offer->pricing,
                'pricing_model' => $offer->pricing_model,
                'guarantees' => $offer->guarantees,
                'guarantee_type' => $offer->guarantee_type,
                'guarantee_terms' => $offer->guarantee_terms,
                'guarantee_period_days' => $offer->guarantee_period_days,
                'bonuses' => $offer->bonuses,
                'scarcity' => $offer->scarcity,
                'urgency' => $offer->urgency,
                'status' => $offer->status,
                'conversion_rate' => $offer->conversion_rate,
                'dream_outcome_score' => $offer->dream_outcome_score,
                'perceived_likelihood_score' => $offer->perceived_likelihood_score,
                'time_delay_days' => $offer->time_delay_days,
                'effort_score' => $offer->effort_score,
                'value_score' => $offer->value_score,
                'total_value' => $offer->total_value,
                'metadata' => $offer->metadata,
                'created_at' => $offer->created_at->format('d.m.Y H:i'),
            ],
            'components' => $offer->components->map(fn($c) => [
                'id' => $c->id,
                'type' => $c->type,
                'name' => $c->name,
                'description' => $c->description,
                'value' => $c->value,
                'order' => $c->order,
                'is_highlighted' => $c->is_highlighted,
            ])->sortBy('order')->values(),
            'panelType' => $panelType,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Offer $offer)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $offer->business_id !== $business->id) {
            abort(403);
        }

        $panelType = $this->getPanelType($request);

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->get(['id', 'name', 'description']);

        $offer->load('components');

        return Inertia::render('Shared/Offers/Builder', [
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'description' => $offer->description,
                'core_offer' => $offer->core_offer,
                'value_proposition' => $offer->value_proposition,
                'target_audience' => $offer->target_audience,
                'pricing' => $offer->pricing,
                'pricing_model' => $offer->pricing_model,
                'guarantee_type' => $offer->guarantee_type,
                'guarantee_terms' => $offer->guarantee_terms,
                'guarantee_period_days' => $offer->guarantee_period_days,
                'scarcity' => $offer->scarcity,
                'urgency' => $offer->urgency,
                'dream_outcome_score' => $offer->dream_outcome_score,
                'perceived_likelihood_score' => $offer->perceived_likelihood_score,
                'time_delay_days' => $offer->time_delay_days,
                'effort_score' => $offer->effort_score,
                'total_value' => $offer->total_value,
                'status' => $offer->status,
                'metadata' => $offer->metadata,
                'components' => $offer->components->sortBy('order')->values(),
            ],
            'dreamBuyers' => $dreamBuyers,
            'isEdit' => true,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Update offer
     */
    public function update(Request $request, Offer $offer)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $offer->business_id !== $business->id) {
            abort(403);
        }

        $routePrefix = $this->getRoutePrefix($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value_proposition' => 'required|string',
            'core_offer' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'pricing_model' => 'nullable|string',
            'guarantees' => 'nullable|string',
            'guarantee_type' => 'nullable|string',
            'guarantee_terms' => 'nullable|string',
            'guarantee_period_days' => 'nullable|integer',
            'bonuses' => 'nullable|string',
            'scarcity' => 'nullable|string',
            'urgency' => 'nullable|string',
            'dream_outcome_score' => 'nullable|integer|min:1|max:10',
            'perceived_likelihood_score' => 'nullable|integer|min:1|max:10',
            'time_delay_days' => 'nullable|integer|min:1',
            'effort_score' => 'nullable|integer|min:1|max:10',
            'total_value' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,paused,archived',
            'metadata' => 'nullable|array',
        ]);

        $offer->update($validated);

        return redirect()->route($routePrefix . '.show', $offer)
            ->with('success', 'Offer yangilandi!');
    }

    /**
     * Delete offer
     */
    public function destroy(Request $request, Offer $offer)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $offer->business_id !== $business->id) {
            abort(403);
        }

        $routePrefix = $this->getRoutePrefix($request);

        $offer->delete();

        return redirect()->route($routePrefix . '.index')
            ->with('success', 'Offer o\'chirildi!');
    }

    /**
     * Generate AI offer (AJAX endpoint)
     */
    public function generateAI(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string',
            'product_description' => 'required|string',
            'main_benefit' => 'required|string',
            'price' => 'required|numeric',
            'target_audience' => 'required|string',
            'dream_buyer_id' => 'nullable|exists:dream_buyers,id',
        ]);

        $dreamBuyer = $validated['dream_buyer_id']
            ? DreamBuyer::find($validated['dream_buyer_id'])
            : null;

        $offer = $this->offerBuilderService->generateOffer($validated, $dreamBuyer);

        return response()->json([
            'success' => true,
            'offer' => $offer,
        ]);
    }

    /**
     * Generate A/B test variations
     */
    public function generateVariations(Request $request, Offer $offer)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $offer->business_id !== $business->id) {
            abort(403);
        }

        $variations = $this->offerBuilderService->generateVariations($offer, 3);

        return response()->json([
            'success' => true,
            'variations' => $variations,
        ]);
    }

    /**
     * Duplicate offer for A/B testing
     */
    public function duplicate(Request $request, Offer $offer)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $offer->business_id !== $business->id) {
            abort(403);
        }

        $routePrefix = $this->getRoutePrefix($request);

        DB::beginTransaction();

        try {
            $newOffer = $offer->replicate();
            $newOffer->name = $offer->name . ' (Copy)';
            $newOffer->status = 'draft';
            $newOffer->save();

            foreach ($offer->components as $component) {
                $newComponent = $component->replicate();
                $newComponent->offer_id = $newOffer->id;
                $newComponent->save();
            }

            DB::commit();

            return redirect()->route($routePrefix . '.edit', $newOffer)
                ->with('success', 'Offer nusxalandi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Nusxalashda xatolik']);
        }
    }

    /**
     * Generate guarantee suggestions
     */
    public function generateGuarantee(Request $request)
    {
        $validated = $request->validate([
            'product_type' => 'required|string',
            'target_audience' => 'required|string',
        ]);

        $guarantees = $this->offerBuilderService->generateGuarantee(
            $validated['product_type'],
            $validated['target_audience']
        );

        return response()->json([
            'success' => true,
            'guarantees' => $guarantees,
        ]);
    }

    /**
     * Calculate value score
     */
    public function calculateValueScore(Request $request)
    {
        $validated = $request->validate([
            'dream_outcome_score' => 'required|integer|min:1|max:10',
            'perceived_likelihood_score' => 'required|integer|min:1|max:10',
            'time_delay_days' => 'required|integer|min:1',
            'effort_score' => 'required|integer|min:1|max:10',
        ]);

        $valueScore = $this->offerBuilderService->calculateValueScore($validated);

        return response()->json([
            'success' => true,
            'value_score' => $valueScore,
        ]);
    }
}
