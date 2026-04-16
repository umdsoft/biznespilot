<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Agent\CallCenter\LostAnalysisService;
use App\Services\Agent\CallCenter\OperatorScorecardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OperatorScorecardController extends Controller
{
    public function __construct(
        private OperatorScorecardService $scorecard,
    ) {}

    /**
     * Scorecard ro'yxat sahifasi
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        return Inertia::render('Business/OperatorScorecards/Index');
    }

    /**
     * Leaderboard API
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $days = (int) $request->input('days', 30);
        $data = $this->scorecard->leaderboard($business->id, $days);

        return response()->json([
            'success' => true,
            'period_days' => $days,
            'operators' => $data,
        ]);
    }

    /**
     * Bitta operator detallari
     */
    public function detailed(Request $request, string $operatorId): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $days = (int) $request->input('days', 30);
        $data = $this->scorecard->detailed($business->id, $operatorId, $days);

        return response()->json($data);
    }

    /**
     * Yaqin kunlardagi alertlar
     */
    public function alerts(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $since = now()->subDays((int) $request->input('days', 7));

        // Kritik past ball tahlillar
        $criticalScores = \App\Models\CallAnalysis::where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->where('overall_score', '<', 30)
            ->with('operator:id,name')
            ->latest()
            ->limit(10)
            ->get(['id', 'operator_id', 'overall_score', 'created_at']);

        // Skript e'tiborsiz qolganlar
        $scriptIgnored = \App\Models\CallAnalysis::where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->where('script_compliance_score', '<', 30)
            ->with('operator:id,name')
            ->latest()
            ->limit(10)
            ->get(['id', 'operator_id', 'script_compliance_score', 'created_at']);

        $svc = app(\App\Services\Agent\CallCenter\CallAlertService::class);
        $spike = $svc->checkNegativeSentimentSpike($business->id);

        return response()->json([
            'success' => true,
            'critical_scores' => $criticalScores,
            'script_ignored' => $scriptIgnored,
            'negative_spike' => $spike,
        ]);
    }

    /**
     * Lost × Operator matrix
     */
    public function lostMatrix(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $days = (int) $request->input('days', 30);
        $svc = app(LostAnalysisService::class);

        return response()->json([
            'success' => true,
            'matrix' => $svc->operatorLostMatrix($business->id, $days),
            'anti_patterns' => $svc->operatorAntiPatterns($business->id, $days),
        ]);
    }

    private function getCurrentBusiness()
    {
        $user = Auth::user();
        if (!$user) return null;
        $businessId = session('current_business_id');
        if ($businessId) return \App\Models\Business::find($businessId);
        return $user->business ?? $user->businesses()->first();
    }
}
