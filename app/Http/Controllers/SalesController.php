<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MarketingChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Display sales dashboard.
     */
    public function index()
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes yarating');
        }

        // Get leads with relationships
        $leads = Lead::where('business_id', $currentBusiness->id)
            ->with(['source', 'assignedTo'])
            ->latest()
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'company' => $lead->company,
                    'status' => $lead->status,
                    'score' => $lead->score,
                    'estimated_value' => $lead->estimated_value,
                    'source' => $lead->source ? [
                        'id' => $lead->source->id,
                        'name' => $lead->source->name,
                    ] : null,
                    'assigned_to' => $lead->assignedTo ? [
                        'id' => $lead->assignedTo->id,
                        'name' => $lead->assignedTo->name,
                    ] : null,
                    'last_contacted_at' => $lead->last_contacted_at?->format('d.m.Y H:i'),
                    'created_at' => $lead->created_at->format('d.m.Y'),
                ];
            });

        // Calculate stats
        $stats = [
            'total_leads' => $leads->count(),
            'new_leads' => $leads->where('status', 'new')->count(),
            'qualified_leads' => $leads->where('status', 'qualified')->count(),
            'won_deals' => $leads->where('status', 'won')->count(),
            'total_value' => $leads->where('status', 'won')->sum('estimated_value'),
            'pipeline_value' => $leads->whereNotIn('status', ['won', 'lost'])->sum('estimated_value'),
        ];

        // Get lead sources for source filter
        $channels = LeadSource::forBusiness($currentBusiness->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'category' => $source->category,
                ];
            });

        return Inertia::render('Business/Sales/Index', [
            'leads' => $leads,
            'stats' => $stats,
            'channels' => $channels,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create()
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        // Get lead sources (global + business-specific)
        $channels = LeadSource::forBusiness($currentBusiness->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'category' => $source->category,
                    'icon' => $source->icon,
                    'color' => $source->color,
                ];
            });

        return Inertia::render('Business/Sales/Create', [
            'channels' => $channels,
        ]);
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'exists:lead_sources,id'],
            'status' => ['required', 'in:new,contacted,qualified,proposal,negotiation,won,lost'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['business_id'] = $currentBusiness->id;
        $validated['uuid'] = Str::uuid();

        Lead::create($validated);

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // Compare as strings to avoid type mismatch
        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            abort(403);
        }

        $lead->load(['source', 'assignedTo']);

        return Inertia::render('Business/Sales/Show', [
            'lead' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'status' => $lead->status,
                'score' => $lead->score,
                'estimated_value' => $lead->estimated_value,
                'notes' => $lead->notes,
                'data' => $lead->data,
                'source' => $lead->source ? [
                    'id' => $lead->source->id,
                    'name' => $lead->source->name,
                    'category' => $lead->source->category,
                ] : null,
                'assigned_to' => $lead->assignedTo ? [
                    'id' => $lead->assignedTo->id,
                    'name' => $lead->assignedTo->name,
                    'email' => $lead->assignedTo->email,
                ] : null,
                'last_contacted_at' => $lead->last_contacted_at?->format('d.m.Y H:i'),
                'converted_at' => $lead->converted_at?->format('d.m.Y H:i'),
                'created_at' => $lead->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // Compare as strings to avoid type mismatch
        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            abort(403);
        }

        // Get lead sources (global + business-specific)
        $channels = LeadSource::forBusiness($currentBusiness->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'category' => $source->category,
                    'icon' => $source->icon,
                    'color' => $source->color,
                ];
            });

        $lead->load(['source', 'assignedTo']);

        return Inertia::render('Business/Sales/Edit', [
            'lead' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'status' => $lead->status,
                'score' => $lead->score,
                'estimated_value' => $lead->estimated_value,
                'notes' => $lead->notes,
                'source_id' => $lead->source_id,
            ],
            'channels' => $channels,
        ]);
    }

    /**
     * Update the specified lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // Compare as strings to avoid type mismatch
        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'exists:lead_sources,id'],
            'status' => ['required', 'in:new,contacted,qualified,proposal,negotiation,won,lost'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        // Update last_contacted_at if status changed to contacted or beyond
        if ($request->status !== 'new' && $lead->status === 'new') {
            $validated['last_contacted_at'] = now();
        }

        // Update converted_at if status changed to won
        if ($request->status === 'won' && $lead->status !== 'won') {
            $validated['converted_at'] = now();
        }

        $lead->update($validated);

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // Compare as strings to avoid type mismatch
        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            abort(403);
        }

        $lead->delete();

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli o\'chirildi!');
    }
}
