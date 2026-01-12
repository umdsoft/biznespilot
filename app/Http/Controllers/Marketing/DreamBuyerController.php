<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\DreamBuyer;
use App\Services\DreamBuyerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DreamBuyerController extends Controller
{
    use HasCurrentBusiness;

    protected DreamBuyerService $dreamBuyerService;

    public function __construct(DreamBuyerService $dreamBuyerService)
    {
        $this->dreamBuyerService = $dreamBuyerService;
    }

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->with(['survey' => function($query) {
                $query->withCount(['responses', 'completedResponses']);
            }])
            ->latest()
            ->get();

        return Inertia::render('Marketing/DreamBuyer/Index', [
            'dreamBuyers' => $dreamBuyers,
        ]);
    }

    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        return Inertia::render('Marketing/DreamBuyer/Wizard');
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
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
            'language_style' => 'required|string',
            'daily_routine' => 'required|string',
            'happiness_triggers' => 'required|string',
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

        return redirect()->route('marketing.dream-buyer.show', $dreamBuyer)
            ->with('success', 'Ideal Mijoz muvaffaqiyatli yaratildi!');
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->with('survey')
            ->firstOrFail();

        return Inertia::render('Marketing/DreamBuyer/Show', [
            'dreamBuyer' => $dreamBuyer,
        ]);
    }

    public function edit($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        return Inertia::render('Marketing/DreamBuyer/Wizard', [
            'dreamBuyer' => $dreamBuyer,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
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
            'language_style' => 'required|string',
            'daily_routine' => 'required|string',
            'happiness_triggers' => 'required|string',
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

        return redirect()->route('marketing.dream-buyer.show', $dreamBuyer)
            ->with('success', 'Ideal Mijoz yangilandi!');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $dreamBuyer->delete();

        return redirect()->route('marketing.dream-buyer.index')
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

        if (!$business) {
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

        if (!$business) {
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

        if (!$business) {
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
