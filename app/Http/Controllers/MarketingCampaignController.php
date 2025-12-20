<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Services\MarketingAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MarketingCampaignController extends Controller
{
    protected MarketingAutomationService $automationService;

    public function __construct(MarketingAutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    public function index()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        $campaigns = Campaign::where('business_id', $currentBusiness->id)
            ->with('messages')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type,
                'channel' => $campaign->channel,
                'status' => $campaign->status,
                'sent_count' => $campaign->sent_count ?? 0,
                'failed_count' => $campaign->failed_count ?? 0,
                'scheduled_at' => $campaign->scheduled_at?->format('Y-m-d H:i'),
                'created_at' => $campaign->created_at->diffForHumans(),
            ]);

        return Inertia::render('Business/Marketing/Campaigns/Index', [
            'campaigns' => $campaigns,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        return Inertia::render('Business/Marketing/Campaigns/Create', [
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:broadcast,drip,trigger',
            'channel' => 'required|in:whatsapp,instagram,all',
            'message_template' => 'required|string',
            'target_audience' => 'nullable',
            'schedule_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'nullable|date',
            'settings' => 'nullable|array',
        ]);

        $campaign = $this->automationService->createCampaign($currentBusiness, $validated);

        if ($validated['schedule_type'] === 'immediate') {
            $this->automationService->sendBroadcast($campaign);
        } else {
            $campaign->update(['status' => 'scheduled']);
        }

        return redirect()->route('marketing.campaigns.index')
            ->with('success', 'Campaign yaratildi!');
    }

    public function show(Campaign $campaign)
    {
        $analytics = $this->automationService->getCampaignAnalytics($campaign);

        return response()->json([
            'campaign' => $campaign,
            'analytics' => $analytics,
        ]);
    }

    public function generateAI(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        $validated = $request->validate([
            'campaign_goal' => 'required|string',
        ]);

        $message = $this->automationService->generateAICampaignMessage(
            $currentBusiness,
            $validated['campaign_goal']
        );

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function launch(Campaign $campaign)
    {
        $result = $this->automationService->sendBroadcast($campaign);

        return response()->json($result);
    }
}
