<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\OfferLeadAssignment;
use App\Services\OfferAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OfferAutomationController extends Controller
{
    protected OfferAutomationService $offerAutomationService;

    public function __construct(OfferAutomationService $offerAutomationService)
    {
        $this->offerAutomationService = $offerAutomationService;
    }

    /**
     * Get panel type from request
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'business')) {
            return 'business';
        }
        if (str_contains($prefix, 'sales-head') || str_contains($prefix, 'saleshead')) {
            return 'saleshead';
        }
        if (str_contains($prefix, 'operator')) {
            return 'operator';
        }

        return 'business';
    }

    /**
     * Get route prefix based on panel
     */
    protected function getRoutePrefix(Request $request): string
    {
        $panel = $this->getPanelType($request);

        return match ($panel) {
            'saleshead' => 'sales-head.offer-automation',
            'operator' => 'operator.offer-automation',
            default => 'business.offer-automation',
        };
    }

    /**
     * Show offer automation dashboard
     */
    public function index(Request $request)
    {
        $business = Auth::user()->currentBusiness;
        $panelType = $this->getPanelType($request);

        // Get active offers
        $offers = Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->withCount([
                'leadAssignments as total_sent' => function ($q) {
                    $q->whereNotIn('status', [OfferLeadAssignment::STATUS_PENDING]);
                },
                'leadAssignments as conversions' => function ($q) {
                    $q->where('status', OfferLeadAssignment::STATUS_CONVERTED);
                },
            ])
            ->orderByDesc('conversion_rate')
            ->get();

        // Get recent assignments
        $recentAssignments = OfferLeadAssignment::where('business_id', $business->id)
            ->with(['offer', 'lead', 'assignedBy'])
            ->latest()
            ->limit(20)
            ->get();

        // Get stats
        $stats = [
            'total_offers' => Offer::where('business_id', $business->id)->where('status', 'active')->count(),
            'total_sent' => OfferLeadAssignment::where('business_id', $business->id)
                ->whereNotIn('status', [OfferLeadAssignment::STATUS_PENDING])->count(),
            'total_conversions' => OfferLeadAssignment::where('business_id', $business->id)
                ->where('status', OfferLeadAssignment::STATUS_CONVERTED)->count(),
            'total_revenue' => OfferLeadAssignment::where('business_id', $business->id)
                ->where('status', OfferLeadAssignment::STATUS_CONVERTED)
                ->sum('final_price'),
            'avg_conversion_rate' => $offers->avg('conversion_rate') ?? 0,
        ];

        // Channel stats
        $channelStats = OfferLeadAssignment::where('business_id', $business->id)
            ->whereNotIn('status', [OfferLeadAssignment::STATUS_PENDING])
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as conversions
            ', [OfferLeadAssignment::STATUS_CONVERTED])
            ->groupBy('channel')
            ->get()
            ->map(fn($item) => [
                'channel' => $item->channel,
                'label' => OfferLeadAssignment::getChannelLabels()[$item->channel] ?? $item->channel,
                'total' => $item->total,
                'conversions' => $item->conversions,
                'conversion_rate' => $item->total > 0 ? round(($item->conversions / $item->total) * 100, 2) : 0,
            ]);

        return Inertia::render('Shared/OfferAutomation/Index', [
            'panelType' => $panelType,
            'offers' => $offers,
            'recentAssignments' => $recentAssignments,
            'stats' => $stats,
            'channelStats' => $channelStats,
            'statusLabels' => OfferLeadAssignment::getStatusLabels(),
            'channelLabels' => OfferLeadAssignment::getChannelLabels(),
        ]);
    }

    /**
     * Show form to send offer to leads
     */
    public function create(Request $request)
    {
        $business = Auth::user()->currentBusiness;
        $panelType = $this->getPanelType($request);

        // Get active offers
        $offers = Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->get(['id', 'name', 'pricing', 'conversion_rate', 'value_score']);

        // Get leads that can receive offers
        $leads = Lead::where('business_id', $business->id)
            ->whereIn('status', ['new', 'contacted', 'qualified', 'proposal', 'negotiation'])
            ->with('telegramUser')
            ->orderBy('name')
            ->get()
            ->map(fn($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'status' => $lead->status,
                'has_telegram' => $lead->hasTelegram(),
            ]);

        return Inertia::render('Shared/OfferAutomation/Create', [
            'panelType' => $panelType,
            'offers' => $offers,
            'leads' => $leads,
            'channels' => OfferLeadAssignment::getChannelLabels(),
        ]);
    }

    /**
     * Send offer to leads
     */
    public function store(Request $request)
    {
        $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'channel' => 'required|in:telegram,sms,email,whatsapp,manual',
            'custom_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string|max:50',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
            'send_immediately' => 'boolean',
        ]);

        $business = Auth::user()->currentBusiness;
        $offer = Offer::where('business_id', $business->id)->findOrFail($request->offer_id);
        $leads = Lead::where('business_id', $business->id)->whereIn('id', $request->lead_ids)->get();

        $options = [
            'custom_price' => $request->custom_price,
            'discount' => $request->discount,
            'discount_code' => $request->discount_code,
            'scheduled_at' => $request->scheduled_at,
            'expires_at' => $request->expires_at,
            'notes' => $request->notes,
        ];

        $assignments = $this->offerAutomationService->assignOfferToLeads(
            $offer,
            $leads,
            $request->channel,
            Auth::user(),
            $options
        );

        // Send immediately if requested and not scheduled
        if ($request->send_immediately && !$request->scheduled_at) {
            foreach ($assignments as $assignment) {
                $this->offerAutomationService->sendOffer($assignment);
            }
        }

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route($routePrefix . '.index')
            ->with('success', "{$assignments->count()} ta lidga taklif yuborildi!");
    }

    /**
     * Show assignment details
     */
    public function show(Request $request, string $id)
    {
        $business = Auth::user()->currentBusiness;
        $panelType = $this->getPanelType($request);

        $assignment = OfferLeadAssignment::where('business_id', $business->id)
            ->with(['offer.components', 'lead', 'assignedBy'])
            ->findOrFail($id);

        return Inertia::render('Shared/OfferAutomation/Show', [
            'panelType' => $panelType,
            'assignment' => $assignment,
            'statusLabels' => OfferLeadAssignment::getStatusLabels(),
            'channelLabels' => OfferLeadAssignment::getChannelLabels(),
        ]);
    }

    /**
     * Get offer analytics
     */
    public function analytics(Request $request, string $offerId)
    {
        $business = Auth::user()->currentBusiness;
        $offer = Offer::where('business_id', $business->id)->findOrFail($offerId);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $analytics = $this->offerAutomationService->getOfferAnalytics($offer, $startDate, $endDate);

        // Get daily metrics for chart
        $dailyMetrics = $offer->metrics()
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('date', [$startDate, $endDate]))
            ->orderBy('date')
            ->limit(30)
            ->get();

        return response()->json([
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'conversion_rate' => $offer->conversion_rate,
            ],
            'analytics' => $analytics,
            'dailyMetrics' => $dailyMetrics,
        ]);
    }

    /**
     * Resend offer
     */
    public function resend(Request $request, string $id)
    {
        $business = Auth::user()->currentBusiness;
        $assignment = OfferLeadAssignment::where('business_id', $business->id)->findOrFail($id);

        // Create new assignment
        $newAssignment = $this->offerAutomationService->assignOfferToLead(
            $assignment->offer,
            $assignment->lead,
            $assignment->channel,
            Auth::user(),
            [
                'custom_price' => $assignment->offered_price,
                'notes' => "Qayta yuborildi (oldingi: {$assignment->tracking_code})",
            ]
        );

        $this->offerAutomationService->sendOffer($newAssignment);

        return back()->with('success', 'Taklif qayta yuborildi!');
    }

    /**
     * Mark as converted manually
     */
    public function markConverted(Request $request, string $id)
    {
        $request->validate([
            'final_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $business = Auth::user()->currentBusiness;
        $assignment = OfferLeadAssignment::where('business_id', $business->id)->findOrFail($id);

        $this->offerAutomationService->recordConversion(
            $assignment,
            $request->final_price,
            ['manual' => true, 'notes' => $request->notes]
        );

        return back()->with('success', 'Konversiya qayd etildi!');
    }

    /**
     * Cancel assignment
     */
    public function cancel(Request $request, string $id)
    {
        $business = Auth::user()->currentBusiness;
        $assignment = OfferLeadAssignment::where('business_id', $business->id)->findOrFail($id);

        $assignment->markAsExpired();

        return back()->with('success', 'Taklif bekor qilindi.');
    }

    /**
     * Quick send offer to lead from lead view
     */
    public function quickSend(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'offer_id' => 'required|exists:offers,id',
            'channel' => 'required|in:telegram,sms,email,whatsapp,manual',
        ]);

        $business = Auth::user()->currentBusiness;
        $lead = Lead::where('business_id', $business->id)->findOrFail($request->lead_id);
        $offer = Offer::where('business_id', $business->id)->findOrFail($request->offer_id);

        $assignment = $this->offerAutomationService->assignOfferToLead(
            $offer,
            $lead,
            $request->channel,
            Auth::user()
        );

        $this->offerAutomationService->sendOffer($assignment);

        return response()->json([
            'success' => true,
            'message' => 'Taklif yuborildi!',
            'assignment' => $assignment,
        ]);
    }

    /**
     * Get suggested offer for lead
     */
    public function suggestOffer(Request $request, string $leadId)
    {
        $business = Auth::user()->currentBusiness;
        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        $offer = $this->offerAutomationService->findBestOfferForLead($lead);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Mos taklif topilmadi',
            ]);
        }

        return response()->json([
            'success' => true,
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'pricing' => $offer->pricing,
                'conversion_rate' => $offer->conversion_rate,
                'value_score' => $offer->value_score,
            ],
        ]);
    }
}
