<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MarketingChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Get current business helper
     */
    protected function getCurrentBusiness()
    {
        return session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();
    }

    /**
     * Display sales dashboard - LAZY LOADING version
     */
    public function index()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes yarating');
        }

        // Only load lightweight data for initial page render
        $channels = Cache::remember("lead_sources_{$currentBusiness->id}", 300, function () use ($currentBusiness) {
            return LeadSource::forBusiness($currentBusiness->id)
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
        });

        return Inertia::render('Business/Sales/Index', [
            'leads' => null, // Lazy load via API
            'stats' => null, // Lazy load via API
            'channels' => $channels,
            'lazyLoad' => true,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    /**
     * API: Get leads with pagination
     */
    public function getLeads(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $perPage = $request->input('per_page', 25);
        $status = $request->input('status');
        $source = $request->input('source');
        $search = $request->input('search');

        $query = Lead::where('business_id', $currentBusiness->id)
            ->with(['source:id,name', 'assignedTo:id,name']);

        // Apply filters
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($source && $source !== 'all') {
            $query->where('source_id', $source);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $leads = $query->latest()
            ->paginate($perPage);

        // Transform the data
        $leads->getCollection()->transform(function ($lead) {
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

        return response()->json($leads);
    }

    /**
     * API: Get lead stats (cached)
     */
    public function getStats()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Cache stats for 5 minutes
        $stats = Cache::remember("lead_stats_{$currentBusiness->id}", 300, function () use ($currentBusiness) {
            // Use efficient aggregate queries instead of loading all leads
            return [
                'total_leads' => Lead::where('business_id', $currentBusiness->id)->count(),
                'new_leads' => Lead::where('business_id', $currentBusiness->id)->where('status', 'new')->count(),
                'qualified_leads' => Lead::where('business_id', $currentBusiness->id)->where('status', 'qualified')->count(),
                'won_deals' => Lead::where('business_id', $currentBusiness->id)->where('status', 'won')->count(),
                'total_value' => Lead::where('business_id', $currentBusiness->id)->where('status', 'won')->sum('estimated_value') ?? 0,
                'pipeline_value' => Lead::where('business_id', $currentBusiness->id)->whereNotIn('status', ['won', 'lost'])->sum('estimated_value') ?? 0,
            ];
        });

        return response()->json($stats);
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create()
    {
        $currentBusiness = $this->getCurrentBusiness();

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
        $currentBusiness = $this->getCurrentBusiness();

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

        // Clear stats cache on lead creation
        Cache::forget("lead_stats_{$currentBusiness->id}");

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

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
        $currentBusiness = $this->getCurrentBusiness();

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
        $currentBusiness = $this->getCurrentBusiness();

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

        // Clear stats cache on lead update
        Cache::forget("lead_stats_{$currentBusiness->id}");

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // Compare as strings to avoid type mismatch
        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            abort(403);
        }

        $lead->delete();

        // Clear stats cache on lead deletion
        Cache::forget("lead_stats_{$currentBusiness->id}");

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli o\'chirildi!');
    }
}
