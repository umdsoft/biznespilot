<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Competitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * SalesHead CompetitorController - Read-Only
 * Sales team can view competitors but not modify them
 */
class CompetitorController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display a listing of competitors (Read-Only)
     */
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
            'panelType' => 'saleshead',
            'readOnly' => true,
        ]);
    }

    /**
     * Display competitor details (Read-Only)
     */
    public function show(Request $request, Competitor $competitor)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $competitor->business_id !== $business->id) {
            abort(403);
        }

        $competitor->load(['metrics' => fn ($q) => $q->orderBy('recorded_date', 'desc')->limit(90)]);

        // Get latest metric
        $latestMetric = $competitor->metrics->first();

        // Get SWOT analysis
        $swotAnalysis = null;
        if ($competitor->swot_data || $competitor->strengths || $competitor->weaknesses) {
            $swotAnalysis = $competitor->swot_data ?? [
                'strengths' => $competitor->strengths ?? [],
                'weaknesses' => $competitor->weaknesses ?? [],
                'opportunities' => [],
                'threats' => [],
            ];
        }

        return Inertia::render('Shared/Competitors/Show', [
            'competitor' => $competitor,
            'metrics' => $competitor->metrics ?? [],
            'latest_metric' => $latestMetric,
            'swot_analysis' => $swotAnalysis,
            'panelType' => 'saleshead',
        ]);
    }

    /**
     * Display competitor dashboard (Read-Only)
     */
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

        return Inertia::render('Shared/Competitors/Dashboard', [
            'competitors' => $competitors,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
            'panelType' => 'saleshead',
            'readOnly' => true,
        ]);
    }
}
