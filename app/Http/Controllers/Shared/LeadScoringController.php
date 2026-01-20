<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SalesLeadScoringRule;
use App\Services\Sales\LeadScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadScoringController extends Controller
{
    public function __construct(
        private LeadScoringService $scoringService
    ) {}

    /**
     * Lead Scoring asosiy sahifa
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness;

        // Agar qoidalar mavjud bo'lmasa, avtomatik default qoidalarni yaratish
        $rulesCount = SalesLeadScoringRule::forBusiness($business->id)->count();
        if ($rulesCount === 0) {
            SalesLeadScoringRule::initializeForBusiness($business->id);
        }

        // Scoring qoidalari
        $rules = SalesLeadScoringRule::forBusiness($business->id)
            ->ordered()
            ->get()
            ->map(fn ($rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'description' => $rule->description,
                'field' => $rule->field,
                'condition' => $rule->condition,
                'condition_label' => SalesLeadScoringRule::CONDITIONS[$rule->condition] ?? $rule->condition,
                'value' => $rule->value,
                'value_type' => $rule->value_type,
                'points' => $rule->points,
                'category' => $rule->category,
                'category_info' => $rule->category_info,
                'is_active' => $rule->is_active,
                'priority' => $rule->priority,
            ]);

        // Score distribution
        $distribution = $this->scoringService->getScoreDistribution($business->id);

        // Top 10 hot leads
        $hotLeads = Lead::forBusiness($business->id)
            ->whereNull('lost_reason')
            ->where('score', '>=', 80)
            ->with(['source:id,name,code', 'assignedTo:id,name'])
            ->orderByDesc('score')
            ->limit(10)
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'phone' => $lead->phone,
                'score' => $lead->score,
                'score_category' => $lead->score_category,
                'score_category_info' => $lead->score_category_info,
                'source' => $lead->source?->name,
                'assigned_to' => $lead->assignedTo?->name,
                'created_at' => $lead->created_at->format('d.m.Y'),
            ]);

        // Kategoriyalar
        $categories = SalesLeadScoringRule::CATEGORIES;
        $conditions = SalesLeadScoringRule::CONDITIONS;

        return Inertia::render('Shared/LeadScoring/Index', [
            'rules' => $rules,
            'distribution' => $distribution,
            'hotLeads' => $hotLeads,
            'categories' => $categories,
            'conditions' => $conditions,
            'scoringCategories' => LeadScoringService::CATEGORIES,
        ]);
    }

    /**
     * Yangi qoida yaratish
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'field' => 'required|string|max:100',
            'condition' => 'required|string|in:'.implode(',', array_keys(SalesLeadScoringRule::CONDITIONS)),
            'value' => 'nullable|string|max:255',
            'value_type' => 'required|string|in:string,number,boolean',
            'points' => 'required|integer|min:-50|max:50',
            'category' => 'required|string|in:'.implode(',', array_keys(SalesLeadScoringRule::CATEGORIES)),
            'priority' => 'integer|min:0|max:100',
        ]);

        $business = $request->user()->currentBusiness;

        SalesLeadScoringRule::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'field' => $validated['field'],
            'condition' => $validated['condition'],
            'value' => $validated['value'],
            'value_type' => $validated['value_type'],
            'points' => $validated['points'],
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Scoring qoidasi qo\'shildi');
    }

    /**
     * Qoidani yangilash
     */
    public function update(Request $request, SalesLeadScoringRule $rule)
    {
        $business = $request->user()->currentBusiness;

        if ($rule->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'field' => 'required|string|max:100',
            'condition' => 'required|string|in:'.implode(',', array_keys(SalesLeadScoringRule::CONDITIONS)),
            'value' => 'nullable|string|max:255',
            'value_type' => 'required|string|in:string,number,boolean',
            'points' => 'required|integer|min:-50|max:50',
            'category' => 'required|string|in:'.implode(',', array_keys(SalesLeadScoringRule::CATEGORIES)),
            'priority' => 'integer|min:0|max:100',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Qoida yangilandi');
    }

    /**
     * Qoidani yoqish/o'chirish
     */
    public function toggle(Request $request, SalesLeadScoringRule $rule)
    {
        $business = $request->user()->currentBusiness;

        if ($rule->business_id !== $business->id) {
            abort(403);
        }

        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', $rule->is_active ? 'Qoida yoqildi' : 'Qoida o\'chirildi');
    }

    /**
     * Qoidani o'chirish
     */
    public function destroy(Request $request, SalesLeadScoringRule $rule)
    {
        $business = $request->user()->currentBusiness;

        if ($rule->business_id !== $business->id) {
            abort(403);
        }

        $rule->delete();

        return back()->with('success', 'Qoida o\'chirildi');
    }

    /**
     * Standart qoidalarni tiklash
     */
    public function resetToDefaults(Request $request)
    {
        $business = $request->user()->currentBusiness;

        // Mavjud rules ni o'chirish
        SalesLeadScoringRule::where('business_id', $business->id)->delete();

        // Default rules yaratish
        SalesLeadScoringRule::initializeForBusiness($business->id);

        return back()->with('success', 'Standart qoidalar tiklandi');
    }

    /**
     * Barcha lead larni qayta baholash
     */
    public function recalculateAll(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $results = $this->scoringService->recalculateAllScores($business->id);

        return back()->with('success', "Barcha leadlar qayta baholandi. Yangilangan: {$results['updated']}/{$results['total']}");
    }

    /**
     * Bitta lead ni qayta baholash
     */
    public function recalculateLead(Request $request, Lead $lead)
    {
        $business = $request->user()->currentBusiness;

        if ($lead->business_id !== $business->id) {
            abort(403);
        }

        $result = $this->scoringService->updateLeadScore($lead);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'score' => $result['score'],
                'category' => $result['category'],
                'changed' => $result['changed'],
            ]);
        }

        return back()->with('success', "Lead balli: {$result['score']} ({$this->scoringService->getCategoryLabel($result['category'])})");
    }

    /**
     * Lead score details
     */
    public function leadScoreDetails(Request $request, Lead $lead)
    {
        $business = $request->user()->currentBusiness;

        if ($lead->business_id !== $business->id) {
            abort(403);
        }

        $details = $this->scoringService->getScoreDetails($lead);
        $history = $this->scoringService->getLeadHistory($lead->id);
        $recommendations = $this->scoringService->getScoringRecommendations($lead);

        return response()->json([
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'score' => $lead->score,
                'score_category' => $lead->score_category,
            ],
            'details' => $details,
            'history' => $history,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Leads by category
     */
    public function leadsByCategory(Request $request, string $category)
    {
        $business = $request->user()->currentBusiness;

        $validCategories = array_keys(LeadScoringService::CATEGORIES);
        if (! in_array($category, $validCategories)) {
            abort(404);
        }

        $leads = Lead::forBusiness($business->id)
            ->whereNull('lost_reason')
            ->where('score_category', $category)
            ->with(['source:id,name,code', 'assignedTo:id,name'])
            ->orderByDesc('score')
            ->paginate(20)
            ->through(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'score' => $lead->score,
                'score_category' => $lead->score_category,
                'score_category_info' => $lead->score_category_info,
                'source' => $lead->source?->name,
                'assigned_to' => $lead->assignedTo?->name,
                'last_contacted_at' => $lead->last_contacted_at?->format('d.m.Y H:i'),
                'created_at' => $lead->created_at->format('d.m.Y'),
            ]);

        $categoryInfo = LeadScoringService::CATEGORIES[$category];

        if ($request->wantsJson()) {
            return response()->json(['leads' => $leads, 'category' => $categoryInfo]);
        }

        return Inertia::render('Shared/LeadScoring/LeadsByCategory', [
            'leads' => $leads,
            'category' => $category,
            'categoryInfo' => $categoryInfo,
        ]);
    }
}
