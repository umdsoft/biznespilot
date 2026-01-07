<?php

namespace App\Http\Controllers;

use App\Models\DreamBuyer;
use App\Services\DreamBuyerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DreamBuyerController extends Controller
{
    protected DreamBuyerService $dreamBuyerService;

    public function __construct(DreamBuyerService $dreamBuyerService)
    {
        $this->dreamBuyerService = $dreamBuyerService;
    }

    /**
     * Display a listing of dream buyers
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        // Load dream buyers with their CustDev surveys
        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->with(['survey' => function($query) {
                $query->withCount(['responses', 'completedResponses']);
            }])
            ->latest()
            ->get();

        return Inertia::render('Business/DreamBuyer/Index', [
            'dreamBuyers' => $dreamBuyers,
        ]);
    }

    /**
     * Show the wizard form
     */
    public function create(Request $request)
    {
        return Inertia::render('Business/DreamBuyer/Wizard');
    }

    /**
     * Store a new dream buyer from wizard
     */
    public function store(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'where_spend_time' => 'required|string',
            'info_sources' => 'required|string',
            'frustrations' => 'required|string',
            'dreams' => 'required|string',
            'fears' => 'required|string',
            'communication_preferences' => 'required|string',
            'language_style' => 'required|string',
            'daily_routine' => 'required|string',
            'happiness_triggers' => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
            'is_primary' => 'boolean',
        ]);

        // Generate AI profile if requested
        if ($request->boolean('generate_profile')) {
            $profile = $this->dreamBuyerService->generateProfile($validated);
            $validated['data'] = $profile;

            // Override name with AI-generated avatar name if available
            if (isset($profile['avatar_name'])) {
                $validated['name'] = $profile['avatar_name'];
            }
        }

        $dreamBuyer = DreamBuyer::create(array_merge($validated, [
            'business_id' => $business->id,
        ]));

        return redirect()->route('dream-buyer.show', $dreamBuyer)
            ->with('success', 'Dream Buyer muvaffaqiyatli yaratildi!');
    }

    /**
     * Display the specified dream buyer
     */
    public function show(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('view', $dreamBuyer);

        // Load the survey relationship
        $dreamBuyer->load('survey');

        return Inertia::render('Business/DreamBuyer/Show', [
            'dreamBuyer' => $dreamBuyer,
        ]);
    }

    /**
     * Show the form for editing
     */
    public function edit(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('update', $dreamBuyer);

        return Inertia::render('Business/DreamBuyer/Wizard', [
            'dreamBuyer' => $dreamBuyer,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified dream buyer
     */
    public function update(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('update', $dreamBuyer);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'where_spend_time' => 'required|string',
            'info_sources' => 'required|string',
            'frustrations' => 'required|string',
            'dreams' => 'required|string',
            'fears' => 'required|string',
            'communication_preferences' => 'required|string',
            'language_style' => 'required|string',
            'daily_routine' => 'required|string',
            'happiness_triggers' => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
            'is_primary' => 'boolean',
        ]);

        // Regenerate AI profile if requested
        if ($request->boolean('generate_profile')) {
            $profile = $this->dreamBuyerService->generateProfile($validated);
            $validated['data'] = $profile;

            if (isset($profile['avatar_name'])) {
                $validated['name'] = $profile['avatar_name'];
            }
        }

        $dreamBuyer->update($validated);

        return redirect()->route('dream-buyer.show', $dreamBuyer)
            ->with('success', 'Dream Buyer yangilandi!');
    }

    /**
     * Remove the specified dream buyer
     */
    public function destroy(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('delete', $dreamBuyer);

        $dreamBuyer->delete();

        return redirect()->route('dream-buyer.index')
            ->with('success', 'Dream Buyer o\'chirildi!');
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
    public function generateContentIdeas(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('view', $dreamBuyer);

        $contentIdeas = $this->dreamBuyerService->generateContentIdeas($dreamBuyer);

        return response()->json([
            'success' => true,
            'content_ideas' => $contentIdeas,
        ]);
    }

    /**
     * Generate ad copy for Dream Buyer
     */
    public function generateAdCopy(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('view', $dreamBuyer);

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
    public function setPrimary(Request $request, DreamBuyer $dreamBuyer)
    {
        $this->authorize('update', $dreamBuyer);

        $business = $request->user()->currentBusiness;

        // Unset all other primary
        DreamBuyer::where('business_id', $business->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $dreamBuyer->update(['is_primary' => true]);

        return back()->with('success', 'Primary Dream Buyer belgilandi!');
    }
}
