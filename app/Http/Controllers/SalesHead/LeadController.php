<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class LeadController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display leads with pipeline view (like business owner)
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login')->with('error', 'Biznes topilmadi');
        }

        // Get lead sources for filters
        $channels = Cache::remember("lead_sources_{$business->id}", 300, function () use ($business) {
            return LeadSource::forBusiness($business->id)
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn($source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'category' => $source->category,
                ]);
        });

        // Get team operators for filters and assignment
        $operators = BusinessUser::where('business_id', $business->id)
            ->whereIn('department', ['sales_head', 'sales_operator'])
            ->with('user:id,name,email')
            ->get()
            ->map(fn($bu) => [
                'id' => $bu->user_id,
                'name' => $bu->user->name,
                'email' => $bu->user->email,
            ]);

        return Inertia::render('SalesHead/Leads/Index', [
            'leads' => null, // Lazy load via API
            'stats' => null, // Lazy load via API
            'channels' => $channels,
            'operators' => $operators,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get leads with pagination for lazy loading
     */
    public function getLeads(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $perPage = $request->input('per_page', 100);
        $status = $request->input('status');
        $source = $request->input('source');
        $search = $request->input('search');
        $operator = $request->input('operator');

        $query = Lead::where('business_id', $business->id)
            ->with(['source', 'assignedTo:id,name']);

        // Apply filters
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($source && $source !== 'all') {
            $query->where('source_id', $source);
        }

        if ($operator === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($operator && $operator !== 'all') {
            $query->where('assigned_to', $operator);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $leads = $query->latest()->paginate($perPage);

        // Transform the data
        $leads->getCollection()->transform(function($lead) {
            // Load source relationship if source_id exists but source is not loaded
            if ($lead->source_id && !$lead->relationLoaded('source')) {
                $lead->load('source');
            }

            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'status' => $lead->status,
                'score' => $lead->score,
                'estimated_value' => $lead->estimated_value,
                'source_id' => $lead->source_id, // Debug: include source_id
                'source' => $lead->source ? [
                    'id' => $lead->source->id,
                    'name' => $lead->source->name,
                ] : null,
                'assigned_to' => $lead->assignedTo ? [
                    'id' => $lead->assignedTo->id,
                    'name' => $lead->assignedTo->name,
                ] : null,
                'created_at' => $lead->created_at,
                'last_contacted_at' => $lead->last_contacted_at,
            ];
        });

        return response()->json($leads);
    }

    /**
     * API: Get stats
     */
    public function getStats()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $stats = [
            'total_leads' => Lead::where('business_id', $business->id)->count(),
            'new_leads' => Lead::where('business_id', $business->id)->where('status', 'new')->count(),
            'qualified_leads' => Lead::where('business_id', $business->id)->where('status', 'qualified')->count(),
            'won_deals' => Lead::where('business_id', $business->id)->where('status', 'won')->count(),
            'pipeline_value' => Lead::where('business_id', $business->id)
                ->whereNotIn('status', ['won', 'lost'])
                ->sum('estimated_value'),
            'total_value' => Lead::where('business_id', $business->id)
                ->where('status', 'won')
                ->sum('estimated_value'),
        ];

        return response()->json($stats);
    }

    /**
     * Show create lead form
     */
    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $channels = LeadSource::forBusiness($business->id)
            ->active()
            ->orderBy('sort_order')
            ->get();

        $operators = BusinessUser::where('business_id', $business->id)
            ->whereIn('department', ['sales_head', 'sales_operator'])
            ->with('user:id,name')
            ->get()
            ->map(fn($bu) => [
                'id' => $bu->user_id,
                'name' => $bu->user->name,
            ]);

        return Inertia::render('SalesHead/Leads/Create', [
            'channels' => $channels,
            'operators' => $operators,
        ]);
    }

    /**
     * Store new lead
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'source_id' => 'nullable|exists:lead_sources,id',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['business_id'] = $business->id;
        $validated['status'] = 'new';
        $validated['score'] = 50; // Default score

        Lead::create($validated);

        return redirect()->route('sales-head.leads.index')
            ->with('success', 'Lead muvaffaqiyatli yaratildi');
    }

    /**
     * Show single lead
     */
    public function show($lead)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $lead = Lead::where('business_id', $business->id)
            ->with(['source', 'assignedTo', 'tasks'])
            ->findOrFail($lead);

        // Format dates for display
        $lead->created_at_formatted = $lead->created_at?->format('d.m.Y H:i');
        $lead->last_contacted_at_formatted = $lead->last_contacted_at?->format('d.m.Y H:i');

        // Get team operators for assignment
        $operators = BusinessUser::where('business_id', $business->id)
            ->whereIn('department', ['sales_head', 'sales_operator'])
            ->with('user:id,name,email')
            ->get()
            ->map(fn($bu) => [
                'id' => $bu->user_id,
                'name' => $bu->user->name,
                'email' => $bu->user->email,
            ]);

        // Get lead sources for source editing
        $sources = LeadSource::forBusiness($business->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($source) => [
                'id' => $source->id,
                'name' => $source->name,
            ]);

        return Inertia::render('SalesHead/Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'phone2' => $lead->phone2,
                'company' => $lead->company,
                'birth_date' => $lead->birth_date?->format('Y-m-d'),
                'region' => $lead->region,
                'district' => $lead->district,
                'address' => $lead->address,
                'gender' => $lead->gender,
                'status' => $lead->status,
                'notes' => $lead->notes,
                'source_id' => $lead->source_id,
                'source' => $lead->source ? [
                    'id' => $lead->source->id,
                    'name' => $lead->source->name,
                ] : null,
                'assigned_to' => $lead->assignedTo ? [
                    'id' => $lead->assignedTo->id,
                    'name' => $lead->assignedTo->name,
                ] : null,
                'created_at' => $lead->created_at_formatted,
                'last_contacted_at' => $lead->last_contacted_at_formatted,
            ],
            'operators' => $operators,
            'sources' => $sources,
            'regions' => Lead::REGIONS,
            'districts' => Lead::DISTRICTS,
        ]);
    }

    /**
     * Update lead
     */
    public function update(Request $request, $leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'phone2' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'region' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'source_id' => 'nullable',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'sometimes|required|string|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'notes' => 'nullable|string',
            'lost_reason' => 'nullable|string',
            'lost_reason_details' => 'nullable|string',
        ]);

        // Handle source_id - can be null or valid source
        if (isset($validated['source_id']) && $validated['source_id'] !== null && $validated['source_id'] !== '') {
            // Validate source exists
            $source = LeadSource::where('id', $validated['source_id'])
                ->where(function($q) use ($business) {
                    $q->where('business_id', $business->id)
                      ->orWhereNull('business_id');
                })
                ->first();
            if (!$source) {
                $validated['source_id'] = null;
            }
        } else {
            $validated['source_id'] = null;
        }

        // Track changes for activity log
        $original = $lead->getOriginal();
        $changes = [];
        $fieldLabels = [
            'name' => 'Ism',
            'email' => 'Email',
            'phone' => 'Telefon',
            'phone2' => 'Qo\'shimcha telefon',
            'company' => 'Kompaniya',
            'birth_date' => 'Tug\'ilgan sana',
            'region' => 'Viloyat',
            'district' => 'Tuman',
            'address' => 'Manzil',
            'gender' => 'Jinsi',
            'source_id' => 'Manba',
            'notes' => 'Izoh',
        ];

        foreach ($validated as $key => $value) {
            $originalValue = $original[$key] ?? null;
            if ($originalValue != $value && isset($fieldLabels[$key])) {
                $changes[$key] = [
                    'label' => $fieldLabels[$key],
                    'old' => $originalValue,
                    'new' => $value,
                ];
            }
        }

        $lead->update($validated);

        // Log activity if there are changes
        if (!empty($changes)) {
            $changedFields = array_map(fn($c) => $c['label'], $changes);
            LeadActivity::log(
                $lead->id,
                LeadActivity::TYPE_UPDATED,
                'Ma\'lumotlar yangilandi',
                implode(', ', $changedFields) . ' o\'zgartirildi',
                $changes
            );
        }

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead yangilandi',
                'lead' => $lead->fresh(['source', 'assignedTo']),
            ]);
        }

        return back()->with('success', 'Lead yangilandi');
    }

    /**
     * Delete lead
     */
    public function destroy($leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);
        $lead->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Lead o\'chirildi']);
        }

        return back()->with('success', 'Lead o\'chirildi');
    }

    /**
     * Assign lead to operator
     */
    public function assign(Request $request, $leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);
        $oldAssignedTo = $lead->assigned_to;

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $lead->update(['assigned_to' => $validated['assigned_to']]);

        // Log activity
        if ($oldAssignedTo != $validated['assigned_to']) {
            $newOperator = $validated['assigned_to'] ? User::find($validated['assigned_to'])?->name : null;
            $oldOperator = $oldAssignedTo ? User::find($oldAssignedTo)?->name : null;

            if ($newOperator) {
                LeadActivity::log(
                    $lead->id,
                    LeadActivity::TYPE_ASSIGNED,
                    'Operator tayinlandi',
                    $oldOperator ? "{$oldOperator} dan {$newOperator} ga o'zgartirildi" : "{$newOperator} ga tayinlandi"
                );
            } else {
                LeadActivity::log(
                    $lead->id,
                    LeadActivity::TYPE_ASSIGNED,
                    'Operator olib tashlandi',
                    "{$oldOperator} dan tayinlov olib tashlandi"
                );
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead tayinlandi',
                'lead' => $lead->fresh(['assignedTo']),
            ]);
        }

        return back()->with('success', 'Lead tayinlandi');
    }

    /**
     * Update lead status (for drag & drop)
     */
    public function updateStatus(Request $request, $leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);
        $oldStatus = $lead->status;

        $validated = $request->validate([
            'status' => 'required|string|in:new,contacted,qualified,proposal,negotiation,won,lost',
            'lost_reason' => 'nullable|string',
            'lost_reason_details' => 'nullable|string',
        ]);

        $lead->update($validated);

        // Log status change activity
        if ($oldStatus != $validated['status']) {
            $statusLabels = [
                'new' => 'Yangi',
                'contacted' => 'Bog\'lanildi',
                'qualified' => 'Qualified',
                'proposal' => 'Taklif',
                'negotiation' => 'Muzokara',
                'won' => 'Yutildi',
                'lost' => 'Yo\'qotildi',
            ];

            LeadActivity::log(
                $lead->id,
                LeadActivity::TYPE_STATUS_CHANGED,
                'Holat o\'zgardi',
                ($statusLabels[$oldStatus] ?? $oldStatus) . ' dan ' . ($statusLabels[$validated['status']] ?? $validated['status']) . ' ga o\'zgartirildi',
                ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Status yangilandi',
            'lead' => $lead->fresh(),
        ]);
    }

    /**
     * Mark lead as lost with reason
     */
    public function markLost(Request $request, $leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);
        $oldStatus = $lead->status;

        $validated = $request->validate([
            'lost_reason' => 'required|string',
            'lost_reason_details' => 'nullable|string',
        ]);

        $lead->update([
            'status' => 'lost',
            'lost_reason' => $validated['lost_reason'],
            'lost_reason_details' => $validated['lost_reason_details'] ?? null,
        ]);

        // Log activity
        LeadActivity::log(
            $lead->id,
            LeadActivity::TYPE_STATUS_CHANGED,
            'Yo\'qotildi deb belgilandi',
            'Sabab: ' . $validated['lost_reason'] . ($validated['lost_reason_details'] ? ' - ' . $validated['lost_reason_details'] : ''),
            ['old_status' => $oldStatus, 'new_status' => 'lost', 'lost_reason' => $validated['lost_reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead yo\'qotilgan deb belgilandi',
        ]);
    }

    /**
     * Get lead activities
     */
    public function getActivities($leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        $activities = $lead->activities()
            ->with('user:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn($activity) => [
                'id' => $activity->id,
                'type' => $activity->type,
                'title' => $activity->title,
                'description' => $activity->description,
                'changes' => $activity->changes,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                ] : null,
                'created_at' => $activity->created_at->format('d.m.Y H:i'),
                'created_at_human' => $activity->created_at->diffForHumans(),
            ]);

        return response()->json(['activities' => $activities]);
    }

    /**
     * Add note to lead
     */
    public function addNote(Request $request, $leadId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        $validated = $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        // Log note activity
        LeadActivity::log(
            $lead->id,
            LeadActivity::TYPE_NOTE_ADDED,
            'Izoh qo\'shildi',
            $validated['note']
        );

        return response()->json([
            'success' => true,
            'message' => 'Izoh qo\'shildi',
        ]);
    }
}
