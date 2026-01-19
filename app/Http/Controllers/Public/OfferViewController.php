<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OfferLeadAssignment;
use App\Services\OfferAutomationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfferViewController extends Controller
{
    protected OfferAutomationService $offerAutomationService;

    public function __construct(OfferAutomationService $offerAutomationService)
    {
        $this->offerAutomationService = $offerAutomationService;
    }

    /**
     * Display public offer page
     */
    public function view(Request $request, string $trackingCode)
    {
        $assignment = OfferLeadAssignment::where('tracking_code', $trackingCode)
            ->with(['offer.components', 'lead', 'business'])
            ->firstOrFail();

        // Check if expired
        if ($assignment->isExpired()) {
            return Inertia::render('Public/OfferExpired', [
                'offer' => $assignment->offer,
                'business' => $assignment->business,
            ]);
        }

        // Record view
        $this->offerAutomationService->recordView($assignment, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ]);

        // Calculate remaining time if expires
        $expiresIn = null;
        if ($assignment->expires_at) {
            $expiresIn = [
                'timestamp' => $assignment->expires_at->timestamp * 1000,
                'formatted' => $assignment->expires_at->diffForHumans(),
            ];
        }

        return Inertia::render('Public/OfferView', [
            'assignment' => [
                'id' => $assignment->id,
                'tracking_code' => $assignment->tracking_code,
                'status' => $assignment->status,
                'offered_price' => $assignment->offered_price,
                'discount_amount' => $assignment->discount_amount,
                'discount_code' => $assignment->discount_code,
                'expires_at' => $expiresIn,
            ],
            'offer' => [
                'id' => $assignment->offer->id,
                'name' => $assignment->offer->name,
                'description' => $assignment->offer->description,
                'value_proposition' => $assignment->offer->value_proposition,
                'core_offer' => $assignment->offer->core_offer,
                'pricing' => $assignment->offer->pricing,
                'total_value' => $assignment->offer->total_value,
                'guarantee_type' => $assignment->offer->guarantee_type,
                'guarantee_terms' => $assignment->offer->guarantee_terms,
                'guarantee_period_days' => $assignment->offer->guarantee_period_days,
                'scarcity' => $assignment->offer->scarcity,
                'urgency' => $assignment->offer->urgency,
                'dream_outcome_score' => $assignment->offer->dream_outcome_score,
                'perceived_likelihood_score' => $assignment->offer->perceived_likelihood_score,
                'time_delay_days' => $assignment->offer->time_delay_days,
                'effort_score' => $assignment->offer->effort_score,
                'value_score' => $assignment->offer->value_score,
                'components' => $assignment->offer->components->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'value' => $c->value,
                    'type' => $c->type,
                ])->toArray(),
            ],
            'lead' => [
                'name' => $assignment->lead->name,
            ],
            'business' => [
                'name' => $assignment->business->name,
                'phone' => $assignment->business->phone ?? null,
                'email' => $assignment->business->email ?? null,
            ],
        ]);
    }

    /**
     * Record CTA click
     */
    public function click(Request $request, string $trackingCode)
    {
        $assignment = OfferLeadAssignment::where('tracking_code', $trackingCode)->firstOrFail();

        $this->offerAutomationService->recordClick($assignment, $request->input('action', 'cta'), [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Show interest in offer
     */
    public function interested(Request $request, string $trackingCode)
    {
        $assignment = OfferLeadAssignment::where('tracking_code', $trackingCode)->firstOrFail();

        $assignment->markAsInterested();

        $this->offerAutomationService->recordClick($assignment, 'interested', [
            'ip' => $request->ip(),
            'message' => $request->input('message'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rahmat! Tez orada siz bilan bog'lanamiz.",
        ]);
    }

    /**
     * Request callback
     */
    public function requestCallback(Request $request, string $trackingCode)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'preferred_time' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
        ]);

        $assignment = OfferLeadAssignment::where('tracking_code', $trackingCode)->firstOrFail();

        $assignment->markAsInterested();

        // Update lead phone if provided
        if ($request->phone && $request->phone !== $assignment->lead->phone) {
            $assignment->lead->update(['phone2' => $request->phone]);
        }

        // Store callback request in metadata
        $callbacks = $assignment->metadata['callback_requests'] ?? [];
        $callbacks[] = [
            'phone' => $request->phone,
            'preferred_time' => $request->preferred_time,
            'message' => $request->message,
            'requested_at' => now()->toISOString(),
        ];

        $assignment->update([
            'metadata' => array_merge($assignment->metadata ?? [], ['callback_requests' => $callbacks]),
        ]);

        // TODO: Create task for operator to call back

        return response()->json([
            'success' => true,
            'message' => "So'rovingiz qabul qilindi! Tez orada qo'ng'iroq qilamiz.",
        ]);
    }

    /**
     * Reject offer
     */
    public function reject(Request $request, string $trackingCode)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $assignment = OfferLeadAssignment::where('tracking_code', $trackingCode)->firstOrFail();

        $this->offerAutomationService->recordRejection($assignment, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Fikringiz uchun rahmat.',
        ]);
    }
}
