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

        if (! $business) {
            return redirect()->route('login')->with('error', 'Biznes topilmadi');
        }

        // Get lead sources for filters
        $channels = Cache::remember("lead_sources_{$business->id}", 300, function () use ($business) {
            return LeadSource::forBusiness($business->id)
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($source) => [
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
            ->map(fn ($bu) => [
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
     * Optimized for fast kanban board loading
     */
    public function getLeads(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $perPage = min($request->input('per_page', 50), 100); // Max 100 for performance
        $status = $request->input('status');
        $source = $request->input('source');
        $search = $request->input('search');
        $operator = $request->input('operator');

        // Optimized query - only select needed columns
        $query = Lead::where('business_id', $business->id)
            ->select(['id', 'name', 'email', 'phone', 'company', 'status', 'score', 'estimated_value', 'source_id', 'assigned_to', 'created_at', 'last_contacted_at'])
            ->with(['source:id,name', 'assignedTo:id,name']);

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
        $leads->getCollection()->transform(function ($lead) {
            // Load source relationship if source_id exists but source is not loaded
            if ($lead->source_id && ! $lead->relationLoaded('source')) {
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
     * API: Get stats - optimized with single query
     */
    public function getStats()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Cache stats for 30 seconds to reduce DB load
        $cacheKey = "lead_stats_{$business->id}";
        $stats = Cache::remember($cacheKey, 30, function () use ($business) {
            // Single optimized query with aggregations
            $result = Lead::where('business_id', $business->id)
                ->selectRaw('
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_leads,
                    SUM(CASE WHEN status = "qualified" THEN 1 ELSE 0 END) as qualified_leads,
                    SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won_deals,
                    SUM(CASE WHEN status NOT IN ("won", "lost") THEN COALESCE(estimated_value, 0) ELSE 0 END) as pipeline_value,
                    SUM(CASE WHEN status = "won" THEN COALESCE(estimated_value, 0) ELSE 0 END) as total_value
                ')
                ->first();

            return [
                'total_leads' => (int) ($result->total_leads ?? 0),
                'new_leads' => (int) ($result->new_leads ?? 0),
                'qualified_leads' => (int) ($result->qualified_leads ?? 0),
                'won_deals' => (int) ($result->won_deals ?? 0),
                'pipeline_value' => (float) ($result->pipeline_value ?? 0),
                'total_value' => (float) ($result->total_value ?? 0),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Show create lead form
     */
    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
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
            ->map(fn ($bu) => [
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

        if (! $business) {
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

        if (! $business) {
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
            ->map(fn ($bu) => [
                'id' => $bu->user_id,
                'name' => $bu->user->name,
                'email' => $bu->user->email,
            ]);

        // Get lead sources for source editing
        $sources = LeadSource::forBusiness($business->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($source) => [
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

        if (! $business) {
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
            'status' => 'sometimes|required|string|in:new,contacted,callback,considering,meeting_scheduled,meeting_attended,won,lost',
            'notes' => 'nullable|string',
            'lost_reason' => 'nullable|string',
            'lost_reason_details' => 'nullable|string',
        ]);

        // Handle source_id - can be null or valid source
        if (isset($validated['source_id']) && $validated['source_id'] !== null && $validated['source_id'] !== '') {
            // Validate source exists
            $source = LeadSource::where('id', $validated['source_id'])
                ->where(function ($q) use ($business) {
                    $q->where('business_id', $business->id)
                        ->orWhereNull('business_id');
                })
                ->first();
            if (! $source) {
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
        if (! empty($changes)) {
            $changedFields = array_map(fn ($c) => $c['label'], $changes);
            LeadActivity::log(
                $lead->id,
                LeadActivity::TYPE_UPDATED,
                'Ma\'lumotlar yangilandi',
                implode(', ', $changedFields).' o\'zgartirildi',
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
     * Assign lead to operator
     */
    public function assign(Request $request, $leadId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
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

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);
        $oldStatus = $lead->status;

        $validated = $request->validate([
            'status' => 'required|string|in:new,contacted,callback,considering,meeting_scheduled,meeting_attended,won,lost',
            'lost_reason' => 'nullable|string',
            'lost_reason_details' => 'nullable|string',
        ]);

        $lead->update($validated);

        // Log status change activity
        if ($oldStatus != $validated['status']) {
            $statusLabels = [
                'new' => 'Yangi',
                'contacted' => 'Bog\'lanildi',
                'callback' => 'Keyinroq bog\'lanish qilamiz',
                'considering' => 'O\'ylab ko\'radi',
                'meeting_scheduled' => 'Uchrashuv belgilandi',
                'meeting_attended' => 'Uchrashuvga keldi',
                'won' => 'Sotuv',
                'lost' => 'Sifatsiz lid',
            ];

            LeadActivity::log(
                $lead->id,
                LeadActivity::TYPE_STATUS_CHANGED,
                'Holat o\'zgardi',
                ($statusLabels[$oldStatus] ?? $oldStatus).' dan '.($statusLabels[$validated['status']] ?? $validated['status']).' ga o\'zgartirildi',
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

        if (! $business) {
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
            'Sabab: '.$validated['lost_reason'].($validated['lost_reason_details'] ? ' - '.$validated['lost_reason_details'] : ''),
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

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        $activities = $lead->activities()
            ->with('user:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($activity) => [
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

        if (! $business) {
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

    /**
     * Get lead phone calls history
     */
    public function getCalls($leadId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        $calls = $lead->calls()
            ->with('user:id,name')
            ->get()
            ->map(fn ($call) => [
                'id' => $call->id,
                'direction' => $call->direction,
                'direction_label' => $call->direction === 'inbound' ? 'Kiruvchi' : 'Chiquvchi',
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'status' => $call->status,
                'status_label' => $this->getCallStatusLabel($call->status),
                'duration' => $call->duration,
                'duration_formatted' => $this->formatDuration($call->duration),
                'wait_time' => $call->wait_time,
                'recording_url' => $call->recording_url,
                'notes' => $call->notes,
                'user' => $call->user ? [
                    'id' => $call->user->id,
                    'name' => $call->user->name,
                ] : null,
                'started_at' => $call->started_at?->format('d.m.Y H:i:s'),
                'answered_at' => $call->answered_at?->format('d.m.Y H:i:s'),
                'ended_at' => $call->ended_at?->format('d.m.Y H:i:s'),
                'created_at' => $call->created_at->format('d.m.Y H:i'),
                'created_at_human' => $call->created_at->diffForHumans(),
            ]);

        // Get call statistics
        $stats = $lead->getCallStats();

        return response()->json([
            'calls' => $calls,
            'stats' => $stats,
            'total_duration_formatted' => $lead->getFormattedCallDuration(),
        ]);
    }

    /**
     * Sync calls for a specific lead from PBX
     */
    public function syncLeadCalls($leadId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lead = Lead::where('business_id', $business->id)->findOrFail($leadId);

        try {
            // Get PBX account for business
            $pbxAccount = \App\Models\PbxAccount::where('business_id', $business->id)
                ->where('is_active', true)
                ->first();

            $syncedFromApi = 0;
            $apiSyncMessage = null;

            // Try to sync from PBX API if account exists
            if ($pbxAccount) {
                $pbxService = app(\App\Services\OnlinePbxService::class);
                $pbxService->setAccount($pbxAccount);

                // Try to sync call history from PBX API (last 7 days)
                $syncResult = $pbxService->syncCallHistory(\Carbon\Carbon::now()->subDays(7));

                if ($syncResult['success'] ?? false) {
                    $syncedFromApi = $syncResult['synced'] ?? 0;
                } else {
                    // API sync failed - log but continue to link existing calls
                    $apiSyncMessage = 'API sinxronlash ishlamadi. Mavjud qo\'ng\'iroqlar ko\'rsatildi.';
                    \Log::warning('PBX API sync failed, continuing with local calls', [
                        'lead_id' => $lead->id,
                        'error' => $syncResult['error'] ?? 'Unknown',
                    ]);
                }
            }

            // Link any orphan calls to this lead by phone number (always do this)
            $linkedCount = $this->linkCallsToLead($lead, $business->id);

            // Get updated calls
            $calls = $lead->fresh()->calls()
                ->with('user:id,name')
                ->get()
                ->map(fn ($call) => [
                    'id' => $call->id,
                    'direction' => $call->direction,
                    'direction_label' => $call->direction === 'inbound' ? 'Kiruvchi' : 'Chiquvchi',
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'status' => $call->status,
                    'status_label' => $this->getCallStatusLabel($call->status),
                    'duration' => $call->duration,
                    'duration_formatted' => $this->formatDuration($call->duration),
                    'wait_time' => $call->wait_time,
                    'recording_url' => $call->recording_url,
                    'notes' => $call->notes,
                    'user' => $call->user ? ['id' => $call->user->id, 'name' => $call->user->name] : null,
                    'started_at' => $call->started_at?->format('d.m.Y H:i:s'),
                    'created_at' => $call->created_at->format('d.m.Y H:i'),
                ]);

            $stats = $lead->fresh()->getCallStats();

            // Build response message
            $message = $apiSyncMessage ?? "Qo'ng'iroqlar sinxronlandi";
            if ($linkedCount > 0) {
                $message .= " ($linkedCount ta ulandi)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'synced' => $syncedFromApi,
                'linked' => $linkedCount,
                'calls' => $calls,
                'stats' => $stats,
                'warning' => $apiSyncMessage,
            ]);

        } catch (\Exception $e) {
            \Log::error('Lead calls sync error', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Sinxronlash xatosi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Link orphan call logs to a specific lead by phone number
     */
    protected function linkCallsToLead(Lead $lead, string $businessId): int
    {
        $linked = 0;
        $phone = $lead->phone;

        if (empty($phone)) {
            return 0;
        }

        $last9 = substr(preg_replace('/[^0-9]/', '', $phone), -9);

        $orphanCalls = \App\Models\CallLog::where('business_id', $businessId)
            ->whereNull('lead_id')
            ->where(function ($query) use ($phone, $last9) {
                $query->where('from_number', 'like', '%'.$last9)
                    ->orWhere('to_number', 'like', '%'.$last9)
                    ->orWhere('from_number', $phone)
                    ->orWhere('to_number', $phone);
            })
            ->get();

        foreach ($orphanCalls as $call) {
            $call->update(['lead_id' => $lead->id]);
            $linked++;
        }

        return $linked;
    }

    /**
     * Get call status label in Uzbek
     */
    protected function getCallStatusLabel(string $status): string
    {
        return match ($status) {
            'initiated' => 'Boshlandi',
            'ringing' => 'Jiringlayapti',
            'answered' => 'Javob berildi',
            'completed' => 'Yakunlandi',
            'failed' => 'Muvaffaqiyatsiz',
            'missed' => 'O\'tkazib yuborildi',
            'busy' => 'Band',
            'no_answer' => 'Javob yo\'q',
            'cancelled' => 'Bekor qilindi',
            default => $status,
        };
    }

    /**
     * Format duration in seconds to human readable
     */
    protected function formatDuration(?int $seconds): string
    {
        if (! $seconds || $seconds <= 0) {
            return '0 sek';
        }

        if ($seconds < 60) {
            return $seconds.' sek';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes < 60) {
            return $minutes.':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $hours.':'.str_pad($mins, 2, '0', STR_PAD_LEFT).':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Update call status manually
     */
    public function updateCallStatus(Request $request, string $callId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $call = \App\Models\CallLog::where('business_id', $business->id)
            ->where('id', $callId)
            ->first();

        if (! $call) {
            return response()->json(['error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:completed,answered,missed,no_answer,busy,failed',
            'duration' => 'nullable|integer|min:0',
        ]);

        $updates = ['status' => $validated['status']];

        if (isset($validated['duration'])) {
            $updates['duration'] = $validated['duration'];
        }

        if (in_array($validated['status'], ['completed', 'answered']) && ! $call->answered_at) {
            $updates['answered_at'] = $call->started_at ?? now();
        }

        if (in_array($validated['status'], ['completed', 'answered', 'missed', 'no_answer', 'busy', 'failed']) && ! $call->ended_at) {
            $updates['ended_at'] = now();
        }

        $call->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Qo\'ng\'iroq statusi yangilandi',
            'call' => [
                'id' => $call->id,
                'status' => $call->status,
                'full_label' => $call->full_label,
                'duration' => $call->duration,
                'duration_formatted' => $this->formatDuration($call->duration),
            ],
        ]);
    }

    /**
     * Get recording URL for a call
     */
    public function getCallRecording(string $callId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $call = \App\Models\CallLog::where('business_id', $business->id)
            ->where('id', $callId)
            ->first();

        if (! $call) {
            return response()->json(['error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        // If we already have recording URL, return it
        if ($call->recording_url) {
            return response()->json([
                'success' => true,
                'recording_url' => $call->recording_url,
            ]);
        }

        // Try to fetch from PBX API
        $pbxAccount = \App\Models\PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if (! $pbxAccount) {
            return response()->json([
                'success' => false,
                'error' => 'PBX hisobi topilmadi',
            ], 404);
        }

        try {
            $pbxService = app(\App\Services\OnlinePbxService::class);
            $pbxService->setAccount($pbxAccount);

            // Try to get recording URL using provider_call_id
            $providerCallId = $call->provider_call_id;
            if (! $providerCallId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Yozuv topilmadi',
                ]);
            }

            $recordingUrl = $pbxService->getRecordingUrl($providerCallId);

            if ($recordingUrl) {
                // Save for future use
                $call->update(['recording_url' => $recordingUrl]);

                return response()->json([
                    'success' => true,
                    'recording_url' => $recordingUrl,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Yozuv topilmadi',
            ]);

        } catch (\Exception $e) {
            \Log::error('Get recording error', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Yozuvni olishda xatolik',
            ], 500);
        }
    }
}
