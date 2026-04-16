<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Marketing\Orchestrator\CampaignCreator;
use App\Services\Marketing\Orchestrator\CompetitorRadar;
use App\Services\Marketing\Orchestrator\ContentFeedbackLoop;
use App\Services\Marketing\Orchestrator\MarketingOrchestrator;
use App\Services\Marketing\Orchestrator\SmartContentCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MarketerDashboardController extends Controller
{
    public function __construct(
        private MarketingOrchestrator $orchestrator,
        private ContentFeedbackLoop $feedbackLoop,
        private CompetitorRadar $radar,
        private CampaignCreator $campaignCreator,
        private SmartContentCalendar $calendar,
    ) {}

    public function index()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        return Inertia::render('Business/MarketerDashboard/Index');
    }

    /**
     * Dashboard ma'lumotlari — yagona endpoint
     */
    public function data(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $snapshot = $this->orchestrator->getSnapshot($business->id);
        $briefing = $this->orchestrator->dailyBriefing($business->id);
        $competitorDigest = $this->radar->weeklyDigest($business->id);
        $competitorCompare = $this->radar->competitorVsYou($business->id);

        return response()->json([
            'success' => true,
            'snapshot' => $snapshot,
            'briefing' => $briefing,
            'competitor_digest' => $competitorDigest,
            'competitor_compare' => $competitorCompare,
        ]);
    }

    /**
     * Haftalik rejani generatsiya qilish
     */
    public function generateWeekPlan(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $plan = $this->calendar->generateWeek($business->id, $request->input('start_date'));
        return response()->json($plan);
    }

    /**
     * Haftalik rejani saqlash (calendar'ga yozish)
     */
    public function saveWeekPlan(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $validated = $request->validate([
            'plan' => 'required|array',
        ]);

        $result = $this->calendar->saveToCalendar($business->id, $validated['plan']);
        return response()->json($result);
    }

    /**
     * Taklif uchun kampaniya tavsiya
     */
    public function proposeCampaign(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $validated = $request->validate([
            'offer_id' => 'required|string',
            'budget' => 'nullable|numeric',
        ]);

        $result = $this->campaignCreator->proposeCampaign($business->id, $validated['offer_id'], [
            'budget' => $validated['budget'] ?? null,
        ]);

        return response()->json($result);
    }

    /**
     * Kampaniyani yaratish
     */
    public function createCampaign(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $validated = $request->validate([
            'proposal' => 'required|array',
        ]);

        $result = $this->campaignCreator->createFromProposal($business->id, $validated['proposal']);
        return response()->json($result);
    }

    /**
     * Content feedback — top performers va failures
     */
    public function contentFeedback(): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $result = $this->feedbackLoop->runWeeklyAnalysis($business->id);
        return response()->json($result);
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
