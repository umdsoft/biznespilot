<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CampaignController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $query = Campaign::where('business_id', $business->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($campaign) {
                $settings = $campaign->settings ?? [];

                return [
                    'id' => $campaign->id,
                    'uuid' => $campaign->uuid,
                    'name' => $campaign->name,
                    'type' => $campaign->type,
                    'channel' => $campaign->channel,
                    'status' => $campaign->status,
                    'start_date' => $campaign->scheduled_at?->format('Y-m-d'),
                    'end_date' => $settings['end_date'] ?? null,
                    'budget' => $settings['budget'] ?? 0,
                    'spent' => $settings['spent'] ?? 0,
                    'leads' => $campaign->sent_count ?? 0,
                    'conversions' => $settings['conversions'] ?? 0,
                    'channels' => $settings['channels'] ?? [$campaign->channel],
                    'created_at' => $campaign->created_at->format('Y-m-d'),
                ];
            });

        // Calculate stats
        $allCampaigns = Campaign::where('business_id', $business->id);

        $totalBudget = 0;
        $totalSpent = 0;
        $totalLeads = 0;

        Campaign::where('business_id', $business->id)
            ->get()
            ->each(function ($c) use (&$totalBudget, &$totalSpent, &$totalLeads) {
                $settings = $c->settings ?? [];
                $totalBudget += $settings['budget'] ?? 0;
                $totalSpent += $settings['spent'] ?? 0;
                $totalLeads += $c->sent_count ?? 0;
            });

        $stats = [
            'total' => Campaign::where('business_id', $business->id)->count(),
            'active' => Campaign::where('business_id', $business->id)->where('status', 'active')->count(),
            'completed' => Campaign::where('business_id', $business->id)->where('status', 'completed')->count(),
            'paused' => Campaign::where('business_id', $business->id)->where('status', 'paused')->count(),
            'draft' => Campaign::where('business_id', $business->id)->where('status', 'draft')->count(),
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_leads' => $totalLeads,
        ];

        return Inertia::render('Marketing/Campaigns/Index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Marketing/Campaigns/Create', [
            'campaignTypes' => [
                ['value' => 'broadcast', 'label' => 'Ommaviy xabar'],
                ['value' => 'drip', 'label' => 'Drip kampaniya'],
                ['value' => 'trigger', 'label' => 'Trigger kampaniya'],
                ['value' => 'promotion', 'label' => 'Aksiya'],
                ['value' => 'branding', 'label' => 'Brending'],
            ],
            'channels' => [
                ['value' => 'instagram', 'label' => 'Instagram'],
                ['value' => 'facebook', 'label' => 'Facebook'],
                ['value' => 'telegram', 'label' => 'Telegram'],
                ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'sms', 'label' => 'SMS'],
                ['value' => 'all', 'label' => 'Barcha kanallar'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'channel' => 'required|string',
            'message_template' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'channels' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $campaign = Campaign::create([
            'business_id' => $business->id,
            'uuid' => Str::uuid(),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'message_template' => $validated['message_template'] ?? null,
            'status' => 'draft',
            'scheduled_at' => $validated['start_date'] ?? null,
            'settings' => [
                'budget' => $validated['budget'] ?? 0,
                'spent' => 0,
                'end_date' => $validated['end_date'] ?? null,
                'channels' => $validated['channels'] ?? [$validated['channel']],
                'description' => $validated['description'] ?? null,
            ],
        ]);

        return redirect()->route('marketing.campaigns.show', $campaign->id)
            ->with('success', 'Kampaniya muvaffaqiyatli yaratildi');
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $campaign = Campaign::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $settings = $campaign->settings ?? [];

        $campaignData = [
            'id' => $campaign->id,
            'uuid' => $campaign->uuid,
            'name' => $campaign->name,
            'type' => $campaign->type,
            'channel' => $campaign->channel,
            'status' => $campaign->status,
            'message_template' => $campaign->message_template,
            'start_date' => $campaign->scheduled_at?->format('Y-m-d'),
            'end_date' => $settings['end_date'] ?? null,
            'budget' => $settings['budget'] ?? 0,
            'spent' => $settings['spent'] ?? 0,
            'leads' => $campaign->sent_count ?? 0,
            'failed' => $campaign->failed_count ?? 0,
            'conversions' => $settings['conversions'] ?? 0,
            'channels' => $settings['channels'] ?? [$campaign->channel],
            'description' => $settings['description'] ?? null,
            'created_at' => $campaign->created_at->format('Y-m-d H:i'),
            'completed_at' => $campaign->completed_at?->format('Y-m-d H:i'),
        ];

        // Get campaign messages for stats
        $messages = $campaign->messages()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as sent, SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($m) => [
                'date' => $m->date,
                'sent' => $m->sent,
                'delivered' => $m->delivered,
            ]);

        return Inertia::render('Marketing/Campaigns/Show', [
            'campaign' => $campaignData,
            'daily_stats' => $messages,
        ]);
    }

    public function edit($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $campaign = Campaign::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $settings = $campaign->settings ?? [];

        return Inertia::render('Marketing/Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type,
                'channel' => $campaign->channel,
                'status' => $campaign->status,
                'message_template' => $campaign->message_template,
                'start_date' => $campaign->scheduled_at?->format('Y-m-d'),
                'end_date' => $settings['end_date'] ?? null,
                'budget' => $settings['budget'] ?? 0,
                'channels' => $settings['channels'] ?? [$campaign->channel],
                'description' => $settings['description'] ?? null,
            ],
            'campaignTypes' => [
                ['value' => 'broadcast', 'label' => 'Ommaviy xabar'],
                ['value' => 'drip', 'label' => 'Drip kampaniya'],
                ['value' => 'trigger', 'label' => 'Trigger kampaniya'],
                ['value' => 'promotion', 'label' => 'Aksiya'],
                ['value' => 'branding', 'label' => 'Brending'],
            ],
            'channels' => [
                ['value' => 'instagram', 'label' => 'Instagram'],
                ['value' => 'facebook', 'label' => 'Facebook'],
                ['value' => 'telegram', 'label' => 'Telegram'],
                ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'sms', 'label' => 'SMS'],
                ['value' => 'all', 'label' => 'Barcha kanallar'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $campaign = Campaign::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'channel' => 'nullable|string',
            'status' => 'nullable|in:draft,active,paused,completed',
            'message_template' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'channels' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $settings = $campaign->settings ?? [];
        $settings['budget'] = $validated['budget'] ?? $settings['budget'] ?? 0;
        $settings['end_date'] = $validated['end_date'] ?? $settings['end_date'] ?? null;
        $settings['channels'] = $validated['channels'] ?? $settings['channels'] ?? [];
        $settings['description'] = $validated['description'] ?? $settings['description'] ?? null;

        $campaign->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? $campaign->type,
            'channel' => $validated['channel'] ?? $campaign->channel,
            'status' => $validated['status'] ?? $campaign->status,
            'message_template' => $validated['message_template'] ?? $campaign->message_template,
            'scheduled_at' => $validated['start_date'] ?? $campaign->scheduled_at,
            'settings' => $settings,
        ]);

        return redirect()->route('marketing.campaigns.show', $id)
            ->with('success', 'Kampaniya yangilandi');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $campaign = Campaign::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $campaign->delete();

        return redirect()->route('marketing.campaigns.index')
            ->with('success', 'Kampaniya o\'chirildi');
    }

    /**
     * Start/activate a campaign
     */
    public function start($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $campaign = Campaign::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $campaign->update(['status' => 'active']);

        return response()->json(['success' => true, 'message' => 'Kampaniya faollashtirildi']);
    }

    /**
     * Pause a campaign
     */
    public function pause($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $campaign = Campaign::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $campaign->update(['status' => 'paused']);

        return response()->json(['success' => true, 'message' => 'Kampaniya to\'xtatildi']);
    }
}
