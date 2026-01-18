<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SalesController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Check if current user can assign leads (owner or sales head)
     */
    protected function canAssignLeads(?Business $business = null): bool
    {
        $business = $business ?? $this->getCurrentBusiness();

        if (! $business) {
            return false;
        }

        // Business owner can always assign
        if ((string) $business->user_id === (string) Auth::id()) {
            return true;
        }

        // Check if user is sales head in this business
        $membership = BusinessUser::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->where('department', 'sales_head')
            ->first();

        return $membership !== null;
    }

    /**
     * Display sales dashboard - LAZY LOADING version
     */
    public function index()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
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
            'canAssignLeads' => $this->canAssignLeads($currentBusiness),
        ]);
    }

    /**
     * API: Get leads with pagination
     */
    public function getLeads(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $perPage = $request->input('per_page', 25);
        $status = $request->input('status');
        $source = $request->input('source');
        $search = $request->input('search');
        $operator = $request->input('operator');

        $query = Lead::where('business_id', $currentBusiness->id)
            ->with(['source', 'assignedTo:id,name']);

        // Apply filters
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($source && $source !== 'all') {
            $query->where('source_id', $source);
        }

        // Operator filter
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

        $leads = $query->latest()
            ->paginate($perPage);

        // Transform the data
        $leads->getCollection()->transform(function ($lead) {
            // Load source relationship if source_id exists but source is not loaded
            if ($lead->source_id && ! $lead->relationLoaded('source')) {
                $lead->load('source');
            }

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
                'source_id' => $lead->source_id,
                'source' => $lead->source ? [
                    'id' => $lead->source->id,
                    'name' => $lead->source->name,
                    'code' => $lead->source->code,
                    'icon' => $lead->source->icon,
                    'color' => $lead->source->color,
                ] : null,
                'assigned_to' => $lead->assignedTo ? [
                    'id' => $lead->assignedTo->id,
                    'name' => $lead->assignedTo->name,
                ] : null,
                'data' => $lead->data,
                'last_contacted_at' => $lead->last_contacted_at?->format('d.m.Y H:i'),
                'created_at' => $lead->created_at->toISOString(),
            ];
        });

        return response()->json($leads);
    }

    /**
     * API: Get lead stats (cached) - Optimized single query
     */
    public function getStats()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Cache stats for 5 minutes - using single optimized query
        $stats = Cache::remember("lead_stats_{$currentBusiness->id}", 300, function () use ($currentBusiness) {
            // Single query with conditional aggregates for better performance
            $result = Lead::where('business_id', $currentBusiness->id)
                ->selectRaw("
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
                    SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified_leads,
                    SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as won_deals,
                    SUM(CASE WHEN status = 'won' THEN COALESCE(estimated_value, 0) ELSE 0 END) as total_value,
                    SUM(CASE WHEN status NOT IN ('won', 'lost') THEN COALESCE(estimated_value, 0) ELSE 0 END) as pipeline_value
                ")
                ->first();

            return [
                'total_leads' => (int) ($result->total_leads ?? 0),
                'new_leads' => (int) ($result->new_leads ?? 0),
                'qualified_leads' => (int) ($result->qualified_leads ?? 0),
                'won_deals' => (int) ($result->won_deals ?? 0),
                'total_value' => (float) ($result->total_value ?? 0),
                'pipeline_value' => (float) ($result->pipeline_value ?? 0),
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

        if (! $currentBusiness) {
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
            'status' => ['required', 'in:new,contacted,callback,considering,meeting_scheduled,meeting_attended,won,lost'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'force_create' => ['nullable', 'boolean'], // Skip duplicate check
        ]);

        // Check for duplicate by phone number (if phone is provided)
        if (! empty($validated['phone']) && ! ($validated['force_create'] ?? false)) {
            $normalizedPhone = $this->normalizePhoneNumber($validated['phone']);
            $existingLead = Lead::where('business_id', $currentBusiness->id)
                ->where(function ($query) use ($normalizedPhone) {
                    // Check phone match
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?", ["%{$normalizedPhone}%"]);
                })
                ->first();

            if ($existingLead) {
                return redirect()->back()
                    ->withInput()
                    ->with('duplicate_warning', [
                        'message' => 'Bu telefon raqami bilan lid allaqachon mavjud',
                        'existing_lead' => [
                            'id' => $existingLead->id,
                            'name' => $existingLead->name,
                            'phone' => $existingLead->phone,
                            'status' => $existingLead->status,
                            'created_at' => $existingLead->created_at->format('d.m.Y H:i'),
                        ],
                    ]);
            }
        }

        unset($validated['force_create']);
        $validated['business_id'] = $currentBusiness->id;
        $validated['uuid'] = Str::uuid();

        Lead::create($validated);

        // Clear stats cache on lead creation
        Cache::forget("lead_stats_{$currentBusiness->id}");

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Normalize phone number for comparison (remove spaces, dashes, etc.)
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 998, keep last 9 digits
        if (strlen($normalized) >= 9) {
            return substr($normalized, -9);
        }

        return $normalized;
    }

    /**
     * API: Check for duplicate leads
     */
    public function checkDuplicate(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $duplicates = [];

        // Check phone
        if (! empty($validated['phone'])) {
            $normalizedPhone = $this->normalizePhoneNumber($validated['phone']);

            $phoneDuplicates = Lead::where('business_id', $currentBusiness->id)
                ->where(function ($query) use ($normalizedPhone) {
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?", ["%{$normalizedPhone}%"]);
                })
                ->select('id', 'name', 'phone', 'email', 'status', 'created_at')
                ->limit(5)
                ->get();

            if ($phoneDuplicates->isNotEmpty()) {
                $duplicates['phone'] = $phoneDuplicates->map(fn ($lead) => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                    'status' => $lead->status,
                    'created_at' => $lead->created_at->format('d.m.Y H:i'),
                ]);
            }
        }

        // Check email
        if (! empty($validated['email'])) {
            $emailDuplicates = Lead::where('business_id', $currentBusiness->id)
                ->where('email', $validated['email'])
                ->select('id', 'name', 'phone', 'email', 'status', 'created_at')
                ->limit(5)
                ->get();

            if ($emailDuplicates->isNotEmpty()) {
                $duplicates['email'] = $emailDuplicates->map(fn ($lead) => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                    'status' => $lead->status,
                    'created_at' => $lead->created_at->format('d.m.Y H:i'),
                ]);
            }
        }

        return response()->json([
            'has_duplicates' => ! empty($duplicates),
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        // Compare as strings to avoid type mismatch
        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            abort(403);
        }

        $lead->load(['source', 'assignedTo']);

        // Get operators for this business
        $operators = $currentBusiness->users()->select('users.id', 'users.name', 'users.email')->get();

        // Get sources for this business
        $sources = LeadSource::forBusiness($currentBusiness->id)
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'category', 'icon', 'color']);

        return Inertia::render('Business/Sales/Show', [
            'lead' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'phone2' => $lead->phone2,
                'company' => $lead->company,
                'birth_date' => $lead->birth_date,
                'gender' => $lead->gender,
                'region' => $lead->region,
                'district' => $lead->district,
                'address' => $lead->address,
                'status' => $lead->status,
                'score' => $lead->score,
                'estimated_value' => $lead->estimated_value,
                'notes' => $lead->notes,
                'data' => $lead->data,
                'source_id' => $lead->source_id,
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
            'operators' => $operators,
            'sources' => $sources,
            'regions' => Lead::REGIONS,
            'districts' => Lead::DISTRICTS,
            'canAssignLeads' => $this->canAssignLeads($currentBusiness),
        ]);
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
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

        if (! $currentBusiness) {
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
            'phone2' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'source_id' => ['nullable', 'exists:lead_sources,id'],
            'status' => ['nullable', 'in:new,contacted,callback,considering,meeting_scheduled,meeting_attended,won,lost'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

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

        // Update last_contacted_at if status changed to contacted or beyond
        if ($request->status !== 'new' && $lead->status === 'new') {
            $validated['last_contacted_at'] = now();
        }

        // Update converted_at if status changed to won
        if ($request->status === 'won' && $lead->status !== 'won') {
            $validated['converted_at'] = now();
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

        // Clear stats cache on lead update
        Cache::forget("lead_stats_{$currentBusiness->id}");

        // Return JSON for AJAX requests (axios), redirect for form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead muvaffaqiyatli yangilandi!',
                'lead' => $lead->fresh(['source', 'assignedTo']),
            ]);
        }

        return redirect()->route('business.sales.index')
            ->with('success', 'Lead muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified lead.
     */

    /**
     * API: Get sales operators for the current business
     */
    public function getOperators()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Get users with department = 'sales_operator' in this business
        $operators = BusinessUser::where('business_id', $currentBusiness->id)
            ->where('department', 'sales_operator')
            ->with(['user:id,name,phone'])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->user_id,
                    'name' => $member->user->name ?? 'Noma\'lum',
                    'phone' => $member->user->phone ?? null,
                ];
            });

        return response()->json([
            'operators' => $operators,
            'has_operators' => $operators->isNotEmpty(),
            'can_assign' => $this->canAssignLeads($currentBusiness),
        ]);
    }

    /**
     * API: Assign lead to operator
     */
    public function assign(Request $request, Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        // Only owner or sales head can assign leads
        if (! $this->canAssignLeads($currentBusiness)) {
            return response()->json(['error' => 'Faqat biznes egasi yoki sotuv bo\'limi rahbari tayinlay oladi'], 403);
        }

        $validated = $request->validate([
            'operator_id' => 'nullable|exists:users,id',
            'reassign_tasks' => 'boolean',
        ]);

        $newOperatorId = $validated['operator_id'] ?? null;
        $reassignTasks = $validated['reassign_tasks'] ?? true;

        // Update lead assignment
        $lead->update(['assigned_to' => $newOperatorId]);

        // Auto-assign existing pending tasks to new operator if requested
        if ($reassignTasks) {
            Task::where('lead_id', $lead->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['assigned_to' => $newOperatorId]);
        }

        // Clear cache
        Cache::forget("lead_stats_{$currentBusiness->id}");

        $lead->load('assignedTo:id,name');

        return response()->json([
            'success' => true,
            'message' => $newOperatorId ? 'Lead muvaffaqiyatli tayinlandi' : 'Tayinlov bekor qilindi',
            'lead' => [
                'id' => $lead->id,
                'assigned_to' => $lead->assignedTo ? [
                    'id' => $lead->assignedTo->id,
                    'name' => $lead->assignedTo->name,
                ] : null,
            ],
        ]);
    }

    /**
     * API: Get operator performance stats
     */
    public function getOperatorStats()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $operators = BusinessUser::where('business_id', $currentBusiness->id)
            ->where('department', 'sales_operator')
            ->with(['user:id,name'])
            ->get();

        $stats = [];
        foreach ($operators as $op) {
            $userId = $op->user_id;

            // Lead stats using single query with conditional aggregates
            $leadStats = Lead::where('business_id', $currentBusiness->id)
                ->where('assigned_to', $userId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as won,
                    SUM(CASE WHEN status = 'won' THEN COALESCE(estimated_value, 0) ELSE 0 END) as won_value
                ")
                ->first();

            // Task stats using single query
            $taskStats = Task::where('business_id', $currentBusiness->id)
                ->where('assigned_to', $userId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                ")
                ->first();

            $totalLeads = (int) ($leadStats->total ?? 0);
            $wonLeads = (int) ($leadStats->won ?? 0);
            $totalTasks = (int) ($taskStats->total ?? 0);
            $completedTasks = (int) ($taskStats->completed ?? 0);

            $stats[] = [
                'operator' => [
                    'id' => $userId,
                    'name' => $op->user->name ?? 'Noma\'lum',
                ],
                'leads' => [
                    'total' => $totalLeads,
                    'won' => $wonLeads,
                    'won_value' => (float) ($leadStats->won_value ?? 0),
                    'conversion_rate' => $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0,
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
                ],
            ];
        }

        // Sort by won value descending
        usort($stats, fn ($a, $b) => $b['leads']['won_value'] <=> $a['leads']['won_value']);

        return response()->json(['stats' => $stats]);
    }

    /**
     * API: Bulk assign leads to operator
     */
    public function bulkAssign(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Only owner or sales head can assign leads
        if (! $this->canAssignLeads($currentBusiness)) {
            return response()->json(['error' => 'Faqat biznes egasi yoki sotuv bo\'limi rahbari tayinlay oladi'], 403);
        }

        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'operator_id' => 'nullable|exists:users,id',
            'reassign_tasks' => 'boolean',
        ]);

        $leadIds = $validated['lead_ids'];
        $newOperatorId = $validated['operator_id'] ?? null;
        $reassignTasks = $validated['reassign_tasks'] ?? true;

        // Verify all leads belong to this business
        $leads = Lead::whereIn('id', $leadIds)
            ->where('business_id', $currentBusiness->id)
            ->get();

        if ($leads->count() !== count($leadIds)) {
            return response()->json(['error' => 'Ba\'zi leadlar topilmadi yoki ruxsat yo\'q'], 403);
        }

        // Update all leads
        Lead::whereIn('id', $leadIds)
            ->where('business_id', $currentBusiness->id)
            ->update(['assigned_to' => $newOperatorId]);

        // Auto-assign existing pending tasks to new operator if requested
        if ($reassignTasks) {
            Task::whereIn('lead_id', $leadIds)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['assigned_to' => $newOperatorId]);
        }

        // Clear cache
        Cache::forget("lead_stats_{$currentBusiness->id}");

        return response()->json([
            'success' => true,
            'message' => $newOperatorId
                ? count($leadIds).' ta lead muvaffaqiyatli tayinlandi'
                : count($leadIds).' ta lead tayinlovi bekor qilindi',
            'leads' => $leads->map(fn ($lead) => [
                'id' => $lead->id,
                'assigned_to' => $newOperatorId,
            ]),
        ]);
    }

    /**
     * API: Get funnel statistics for pipeline visualization
     */
    public function getFunnelStats()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Get counts by status
        $statusCounts = Lead::where('business_id', $currentBusiness->id)
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(COALESCE(estimated_value, 0)) as value
            ')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusValues = Lead::where('business_id', $currentBusiness->id)
            ->selectRaw('status, SUM(COALESCE(estimated_value, 0)) as value')
            ->groupBy('status')
            ->pluck('value', 'status')
            ->toArray();

        // Define stages in order
        $stages = [
            ['key' => 'new', 'label' => 'Yangi', 'color' => '#3B82F6'],
            ['key' => 'contacted', 'label' => 'Bog\'lanildi', 'color' => '#6366F1'],
            ['key' => 'callback', 'label' => 'Keyinroq bog\'lanish qilamiz', 'color' => '#8B5CF6'],
            ['key' => 'considering', 'label' => 'O\'ylab ko\'radi', 'color' => '#F97316'],
            ['key' => 'meeting_scheduled', 'label' => 'Uchrashuv belgilandi', 'color' => '#EAB308'],
            ['key' => 'meeting_attended', 'label' => 'Uchrashuvga keldi', 'color' => '#14B8A6'],
            ['key' => 'won', 'label' => 'Sotuv', 'color' => '#22C55E'],
            ['key' => 'lost', 'label' => 'Sifatsiz lid', 'color' => '#EF4444'],
        ];

        $total = array_sum($statusCounts);
        $funnel = [];
        $runningTotal = $total;

        foreach ($stages as $stage) {
            $count = $statusCounts[$stage['key']] ?? 0;
            $value = (float) ($statusValues[$stage['key']] ?? 0);

            // Skip lost for conversion calculation
            if ($stage['key'] !== 'lost') {
                $conversionRate = $runningTotal > 0 ? round(($count / $runningTotal) * 100, 1) : 0;
            } else {
                $conversionRate = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            }

            $funnel[] = [
                'key' => $stage['key'],
                'label' => $stage['label'],
                'color' => $stage['color'],
                'count' => $count,
                'value' => $value,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                'conversion_rate' => $conversionRate,
            ];
        }

        // Calculate overall conversion rate (won / total excl. lost)
        $wonCount = $statusCounts['won'] ?? 0;
        $totalExclLost = $total - ($statusCounts['lost'] ?? 0);
        $overallConversion = $totalExclLost > 0 ? round(($wonCount / $totalExclLost) * 100, 1) : 0;

        return response()->json([
            'funnel' => $funnel,
            'total' => $total,
            'overall_conversion' => $overallConversion,
            'total_value' => array_sum($statusValues),
        ]);
    }

    /**
     * API: Get source/channel statistics
     */
    public function getSourceStats()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Get leads by source with status breakdown
        $sourceStats = Lead::where('business_id', $currentBusiness->id)
            ->selectRaw("
                source_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN status NOT IN ('won', 'lost') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'won' THEN COALESCE(estimated_value, 0) ELSE 0 END) as won_value,
                SUM(COALESCE(estimated_value, 0)) as total_value
            ")
            ->groupBy('source_id')
            ->get();

        // Get source names
        $sourceIds = $sourceStats->pluck('source_id')->filter()->toArray();
        $sources = LeadSource::whereIn('id', $sourceIds)->pluck('name', 'id')->toArray();

        $result = [];
        foreach ($sourceStats as $stat) {
            $sourceName = $stat->source_id ? ($sources[$stat->source_id] ?? 'Noma\'lum') : 'Noma\'lum';
            $total = (int) $stat->total;
            $won = (int) $stat->won;

            $result[] = [
                'source_id' => $stat->source_id,
                'name' => $sourceName,
                'total' => $total,
                'won' => $won,
                'lost' => (int) $stat->lost,
                'active' => (int) $stat->active,
                'won_value' => (float) $stat->won_value,
                'total_value' => (float) $stat->total_value,
                'conversion_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
            ];
        }

        // Sort by total descending
        usort($result, fn ($a, $b) => $b['total'] <=> $a['total']);

        return response()->json([
            'sources' => $result,
            'total_sources' => count($result),
        ]);
    }

    /**
     * API: Get lost reasons statistics
     */
    public function getLostReasonsStats()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lostStats = Lead::where('business_id', $currentBusiness->id)
            ->where('status', 'lost')
            ->selectRaw('
                lost_reason,
                COUNT(*) as count,
                SUM(COALESCE(estimated_value, 0)) as lost_value
            ')
            ->groupBy('lost_reason')
            ->get();

        $reasons = Lead::LOST_REASONS;
        $result = [];
        $total = 0;

        foreach ($lostStats as $stat) {
            $reasonKey = $stat->lost_reason ?? 'unknown';
            $reasonLabel = $reasons[$reasonKey] ?? 'Belgilanmagan';
            $count = (int) $stat->count;
            $total += $count;

            $result[] = [
                'reason' => $reasonKey,
                'label' => $reasonLabel,
                'count' => $count,
                'lost_value' => (float) $stat->lost_value,
            ];
        }

        // Add percentage
        foreach ($result as &$item) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
        }

        // Sort by count descending
        usort($result, fn ($a, $b) => $b['count'] <=> $a['count']);

        return response()->json([
            'reasons' => $result,
            'total_lost' => $total,
            'available_reasons' => $reasons,
        ]);
    }

    /**
     * API: Mark lead as lost with reason
     */
    public function markAsLost(Request $request, Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'lost_reason' => 'required|in:'.implode(',', array_keys(Lead::LOST_REASONS)),
            'lost_reason_details' => 'nullable|string|max:1000',
        ]);

        $lead->update([
            'status' => 'lost',
            'lost_reason' => $validated['lost_reason'],
            'lost_reason_details' => $validated['lost_reason_details'] ?? null,
        ]);

        // Clear cache
        Cache::forget("lead_stats_{$currentBusiness->id}");

        // Log activity
        LeadActivity::log(
            $lead->id,
            LeadActivity::TYPE_STATUS_CHANGED,
            'Yo\'qotildi deb belgilandi',
            'Sabab: '.(Lead::LOST_REASONS[$validated['lost_reason']] ?? $validated['lost_reason']).
                ($validated['lost_reason_details'] ? ' - '.$validated['lost_reason_details'] : ''),
            ['old_status' => 'active', 'new_status' => 'lost', 'lost_reason' => $validated['lost_reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead yo\'qotildi deb belgilandi',
            'lead' => [
                'id' => $lead->id,
                'status' => $lead->status,
                'lost_reason' => $lead->lost_reason,
                'lost_reason_label' => Lead::LOST_REASONS[$lead->lost_reason] ?? null,
            ],
        ]);
    }

    /**
     * Get lead activities
     */
    public function getActivities(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

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
    public function addNote(Request $request, Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

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
     * Get lead's call history
     */
    public function getCalls(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        try {
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
                    'full_label' => $call->full_label,
                    'duration' => $call->duration,
                    'duration_formatted' => $this->formatCallDuration($call->duration),
                    'wait_time' => $call->wait_time,
                    'recording_url' => $call->recording_url,
                    'notes' => $call->notes,
                    'operator_name' => $call->user?->name,
                    'started_at' => $call->started_at?->format('d.m.Y H:i:s'),
                    'created_at' => $call->created_at->format('d.m.Y H:i'),
                ]);

            // Calculate stats
            $stats = $lead->getCallStats();

            return response()->json([
                'success' => true,
                'calls' => $calls,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get calls error', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'calls' => [],
                'stats' => [
                    'total_calls' => 0,
                    'outbound_calls' => 0,
                    'inbound_calls' => 0,
                    'answered_calls' => 0,
                    'missed_calls' => 0,
                    'total_duration' => 0,
                    'total_duration_formatted' => '0:00',
                    'avg_duration' => 0,
                    'answer_rate' => 0,
                ],
                'error' => 'Ma\'lumotlarni yuklashda xatolik',
            ]);
        }
    }

    /**
     * Sync calls for a specific lead from PBX
     */
    public function syncLeadCalls(Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        try {
            // Get PBX account for business
            $pbxAccount = \App\Models\PbxAccount::where('business_id', $currentBusiness->id)
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
            $linkedCount = $this->linkCallsToLead($lead, $currentBusiness->id);

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
                    'full_label' => $call->full_label,
                    'duration' => $call->duration,
                    'duration_formatted' => $this->formatCallDuration($call->duration),
                    'wait_time' => $call->wait_time,
                    'recording_url' => $call->recording_url,
                    'notes' => $call->notes,
                    'operator_name' => $call->user?->name,
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

        // Get last 9 digits for matching
        $last9 = substr(preg_replace('/[^0-9]/', '', $phone), -9);

        // Find orphan calls that match this lead's phone number
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

        // Also check calls that might have different lead_id but same phone
        $mismatchedCalls = \App\Models\CallLog::where('business_id', $businessId)
            ->where('lead_id', '!=', $lead->id)
            ->where(function ($query) use ($last9) {
                $query->where('from_number', 'like', '%'.$last9)
                    ->orWhere('to_number', 'like', '%'.$last9);
            })
            ->get();

        foreach ($mismatchedCalls as $call) {
            // Check if the phone number matches exactly
            $callPhone = $call->direction === 'inbound' ? $call->from_number : $call->to_number;
            $callLast9 = substr(preg_replace('/[^0-9]/', '', $callPhone), -9);

            if ($callLast9 === $last9) {
                $call->update(['lead_id' => $lead->id]);
                $linked++;
            }
        }

        return $linked;
    }

    /**
     * Get readable call status label
     */
    protected function getCallStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Tugallangan',
            'missed' => 'O\'tkazildi',
            'no_answer' => 'Javob yo\'q',
            'busy' => 'Band',
            'failed' => 'Xato',
            'initiated' => 'Boshlangan',
            'ringing' => 'Jiringlayapti',
            default => ucfirst($status),
        };
    }

    /**
     * Format call duration
     */
    protected function formatCallDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '-';
        }
        if ($seconds < 60) {
            return $seconds.' sek';
        }
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return $minutes.':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Update lead status
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $oldStatus = $lead->status;

        $validated = $request->validate([
            'status' => 'required|string|in:new,contacted,qualified,proposal,negotiation,won,lost',
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

        // Clear cache
        Cache::forget("lead_stats_{$currentBusiness->id}");

        return response()->json([
            'success' => true,
            'message' => 'Status yangilandi',
            'lead' => $lead->fresh(),
        ]);
    }

    /**
     * Update call status manually
     */
    public function updateCallStatus(Request $request, string $callId)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $call = \App\Models\CallLog::where('id', $callId)
            ->where('business_id', $currentBusiness->id)
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

        // If marking as completed/answered and no ended_at, set it
        if (in_array($validated['status'], ['completed', 'answered']) && ! $call->ended_at) {
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
                'duration_formatted' => $this->formatCallDuration($call->duration),
            ],
        ]);
    }

    /**
     * Get recording URL for a call
     */
    public function getCallRecording(string $callId)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (! $currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $call = \App\Models\CallLog::where('business_id', $currentBusiness->id)
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
        $pbxAccount = \App\Models\PbxAccount::where('business_id', $currentBusiness->id)
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
