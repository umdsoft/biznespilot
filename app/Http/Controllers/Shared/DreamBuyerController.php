<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\DreamBuyer;
use App\Services\DreamBuyerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Shared DreamBuyer Controller
 * Works for all panels: business, marketing, finance, operator, saleshead
 */
class DreamBuyerController extends Controller
{
    use HasCurrentBusiness;

    protected DreamBuyerService $dreamBuyerService;

    public function __construct(DreamBuyerService $dreamBuyerService)
    {
        $this->dreamBuyerService = $dreamBuyerService;
    }

    /**
     * Get panel type from request
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) {
            return 'marketing';
        }
        if (str_contains($prefix, 'finance')) {
            return 'finance';
        }
        if (str_contains($prefix, 'operator')) {
            return 'operator';
        }
        if (str_contains($prefix, 'sales-head') || str_contains($prefix, 'saleshead')) {
            return 'saleshead';
        }

        return 'business';
    }

    /**
     * Get route prefix for current panel
     */
    protected function getRoutePrefix(Request $request): string
    {
        $panel = $this->getPanelType($request);

        return match ($panel) {
            'marketing' => 'marketing.dream-buyer',
            'finance' => 'finance.dream-buyer',
            'operator' => 'operator.dream-buyer',
            'saleshead' => 'sales-head.dream-buyer',
            default => 'business.dream-buyer',
        };
    }

    /**
     * Display a listing of dream buyers
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->with(['survey' => function ($query) {
                $query->withCount(['responses', 'completedResponses']);
            }])
            ->latest()
            ->get();

        $panelType = $this->getPanelType($request);

        return Inertia::render('Shared/DreamBuyer/Index', [
            'dreamBuyers' => $dreamBuyers,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Show the wizard form for creating
     */
    public function create(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $panelType = $this->getPanelType($request);

        return Inertia::render('Shared/DreamBuyer/Create', [
            'panelType' => $panelType,
        ]);
    }

    /**
     * Store a new dream buyer
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'where_spend_time' => 'required|string',
            'info_sources' => 'required|string',
            'frustrations' => 'required|string',
            'dreams' => 'required|string',
            'fears' => 'required|string',
            'communication_preferences' => 'required|string',
            'language_style' => 'nullable|string',
            'daily_routine' => 'nullable|string',
            'happiness_triggers' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'is_primary' => 'boolean',
        ]);

        if ($request->boolean('generate_profile')) {
            $profile = $this->dreamBuyerService->generateProfile($validated);
            $validated['data'] = $profile;

            if (isset($profile['avatar_name'])) {
                $validated['name'] = $profile['avatar_name'];
            }
        }

        $dreamBuyer = DreamBuyer::create(array_merge($validated, [
            'business_id' => $business->id,
        ]));

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route($routePrefix.'.show', $dreamBuyer)
            ->with('success', 'Ideal Mijoz muvaffaqiyatli yaratildi!');
    }

    /**
     * Display the specified dream buyer
     */
    public function show(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->with('survey')
            ->firstOrFail();

        $panelType = $this->getPanelType($request);

        return Inertia::render('Shared/DreamBuyer/Show', [
            'dreamBuyer' => $dreamBuyer,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Show the form for editing
     */
    public function edit(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $panelType = $this->getPanelType($request);

        return Inertia::render('Shared/DreamBuyer/Wizard', [
            'dreamBuyer' => $dreamBuyer,
            'isEdit' => true,
            'panelType' => $panelType,
        ]);
    }

    /**
     * Update the specified dream buyer
     */
    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'where_spend_time' => 'required|string',
            'info_sources' => 'required|string',
            'frustrations' => 'required|string',
            'dreams' => 'required|string',
            'fears' => 'required|string',
            'communication_preferences' => 'required|string',
            'language_style' => 'nullable|string',
            'daily_routine' => 'nullable|string',
            'happiness_triggers' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'is_primary' => 'boolean',
        ]);

        if ($request->boolean('generate_profile')) {
            $profile = $this->dreamBuyerService->generateProfile($validated);
            $validated['data'] = $profile;

            if (isset($profile['avatar_name'])) {
                $validated['name'] = $profile['avatar_name'];
            }
        }

        $dreamBuyer->update($validated);

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route($routePrefix.'.show', $dreamBuyer)
            ->with('success', 'Ideal Mijoz yangilandi!');
    }

    /**
     * Remove the specified dream buyer
     */
    public function destroy(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $dreamBuyer->delete();

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route($routePrefix.'.index')
            ->with('success', 'Ideal Mijoz o\'chirildi!');
    }

    /**
     * Generate AI profile (AJAX endpoint)
     */
    public function generateProfile(Request $request)
    {
        $validated = $request->validate([
            'where_spend_time' => 'required|string',
            'info_sources' => 'required|string',
            'frustrations' => 'required|string',
            'dreams' => 'required|string',
            'fears' => 'required|string',
            'communication_preferences' => 'required|string',
            'language_style' => 'required|string',
            'daily_routine' => 'required|string',
            'happiness_triggers' => 'required|string',
        ]);

        $profile = $this->dreamBuyerService->generateProfile($validated);

        return response()->json([
            'success' => true,
            'profile' => $profile,
        ]);
    }

    /**
     * Generate content ideas for Dream Buyer
     */
    public function generateContentIdeas(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $contentIdeas = $this->dreamBuyerService->generateContentIdeas($dreamBuyer);

        return response()->json([
            'success' => true,
            'content_ideas' => $contentIdeas,
        ]);
    }

    /**
     * Generate ad copy for Dream Buyer
     */
    public function generateAdCopy(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'product' => 'required|string',
        ]);

        $adCopy = $this->dreamBuyerService->generateAdCopy($dreamBuyer, $validated['product']);

        return response()->json([
            'success' => true,
            'ad_copy' => $adCopy,
        ]);
    }

    /**
     * Set as primary Dream Buyer
     */
    public function setPrimary(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Unset all other primary
        DreamBuyer::where('business_id', $business->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $dreamBuyer->update(['is_primary' => true]);

        return back()->with('success', 'Primary Ideal Mijoz belgilandi!');
    }
}
