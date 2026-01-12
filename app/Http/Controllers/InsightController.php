<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InsightController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display insights index
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        return Inertia::render('Business/Insights/Index', [
            'insights' => [],
        ]);
    }

    /**
     * Get active insights
     */
    public function getActive()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['insights' => []]);
        }

        return response()->json([
            'insights' => [],
        ]);
    }

    /**
     * Regenerate insights
     */
    public function regenerate(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Insights yangilandi',
        ]);
    }

    /**
     * Get insights by category
     */
    public function getByCategory(Request $request)
    {
        return response()->json([
            'insights' => [],
        ]);
    }

    /**
     * Show single insight
     */
    public function show($insight)
    {
        return Inertia::render('Business/Insights/Show', [
            'insight' => null,
        ]);
    }

    /**
     * Mark insight as viewed
     */
    public function markViewed($insight)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Mark insight as acted upon
     */
    public function markActed($insight)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Dismiss insight
     */
    public function dismiss($insight)
    {
        return response()->json(['success' => true]);
    }
}
