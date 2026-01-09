<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\MarketingChannel;
use App\Models\Task;
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
    protected function getCurrentBusiness(): Business
    {
        $business = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$business) {
            abort(400, 'Biznes tanlanmagan. Iltimos, avval biznes yarating yoki tanlang.');
        }

        return $business;
    }

    /**
     * Check if current user can assign leads (owner or sales head)
     */
    protected function canAssignLeads(?Business $business = null): bool
    {
        $business = $business ?? $this->getCurrentBusiness();

        if (!$business) {
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
            'canAssignLeads' => $this->canAssignLeads($currentBusiness),
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
        $operator = $request->input('operator');

        $query = Lead::where('business_id', $currentBusiness->id)
            ->with(['source:id,name', 'assignedTo:id,name']);

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

        if (!$currentBusiness) {
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
            'force_create' => ['nullable', 'boolean'], // Skip duplicate check
        ]);

        // Check for duplicate by phone number (if phone is provided)
        if (!empty($validated['phone']) && !($validated['force_create'] ?? false)) {
            $normalizedPhone = $this->normalizePhoneNumber($validated['phone']);
            $existingLead = Lead::where('business_id', $currentBusiness->id)
                ->where(function ($query) use ($normalizedPhone, $validated) {
                    // Check phone match
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?", ["%{$normalizedPhone}%"]);
                })
                ->first();

            if ($existingLead) {
                return redirect()->back()
                    ->withInput()
                    ->with('duplicate_warning', [
                        'message' => "Bu telefon raqami bilan lid allaqachon mavjud",
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

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $duplicates = [];

        // Check phone
        if (!empty($validated['phone'])) {
            $normalizedPhone = $this->normalizePhoneNumber($validated['phone']);

            $phoneDuplicates = Lead::where('business_id', $currentBusiness->id)
                ->where(function ($query) use ($normalizedPhone) {
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?", ["%{$normalizedPhone}%"]);
                })
                ->select('id', 'name', 'phone', 'email', 'status', 'created_at')
                ->limit(5)
                ->get();

            if ($phoneDuplicates->isNotEmpty()) {
                $duplicates['phone'] = $phoneDuplicates->map(fn($lead) => [
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
        if (!empty($validated['email'])) {
            $emailDuplicates = Lead::where('business_id', $currentBusiness->id)
                ->where('email', $validated['email'])
                ->select('id', 'name', 'phone', 'email', 'status', 'created_at')
                ->limit(5)
                ->get();

            if ($emailDuplicates->isNotEmpty()) {
                $duplicates['email'] = $emailDuplicates->map(fn($lead) => [
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
            'has_duplicates' => !empty($duplicates),
            'duplicates' => $duplicates,
        ]);
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
            'canAssignLeads' => $this->canAssignLeads($currentBusiness),
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

    /**
     * API: Get sales operators for the current business
     */
    public function getOperators()
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
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

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        // Only owner or sales head can assign leads
        if (!$this->canAssignLeads($currentBusiness)) {
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

        if (!$currentBusiness) {
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
        usort($stats, fn($a, $b) => $b['leads']['won_value'] <=> $a['leads']['won_value']);

        return response()->json(['stats' => $stats]);
    }

    /**
     * API: Bulk assign leads to operator
     */
    public function bulkAssign(Request $request)
    {
        $currentBusiness = $this->getCurrentBusiness();

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Only owner or sales head can assign leads
        if (!$this->canAssignLeads($currentBusiness)) {
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
                ? count($leadIds) . ' ta lead muvaffaqiyatli tayinlandi'
                : count($leadIds) . ' ta lead tayinlovi bekor qilindi',
            'leads' => $leads->map(fn($lead) => [
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

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        // Get counts by status
        $statusCounts = Lead::where('business_id', $currentBusiness->id)
            ->selectRaw("
                status,
                COUNT(*) as count,
                SUM(COALESCE(estimated_value, 0)) as value
            ")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusValues = Lead::where('business_id', $currentBusiness->id)
            ->selectRaw("status, SUM(COALESCE(estimated_value, 0)) as value")
            ->groupBy('status')
            ->pluck('value', 'status')
            ->toArray();

        // Define stages in order
        $stages = [
            ['key' => 'new', 'label' => 'Yangi', 'color' => '#3B82F6'],
            ['key' => 'contacted', 'label' => 'Bog\'lanildi', 'color' => '#6366F1'],
            ['key' => 'qualified', 'label' => 'Qualified', 'color' => '#8B5CF6'],
            ['key' => 'proposal', 'label' => 'Taklif', 'color' => '#F97316'],
            ['key' => 'negotiation', 'label' => 'Muzokara', 'color' => '#EAB308'],
            ['key' => 'won', 'label' => 'Yutildi', 'color' => '#22C55E'],
            ['key' => 'lost', 'label' => 'Yo\'qotildi', 'color' => '#EF4444'],
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

        if (!$currentBusiness) {
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
        usort($result, fn($a, $b) => $b['total'] <=> $a['total']);

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

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $lostStats = Lead::where('business_id', $currentBusiness->id)
            ->where('status', 'lost')
            ->selectRaw("
                lost_reason,
                COUNT(*) as count,
                SUM(COALESCE(estimated_value, 0)) as lost_value
            ")
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
        usort($result, fn($a, $b) => $b['count'] <=> $a['count']);

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

        if (!$currentBusiness) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        if ((string) $lead->business_id !== (string) $currentBusiness->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'lost_reason' => 'required|in:' . implode(',', array_keys(Lead::LOST_REASONS)),
            'lost_reason_details' => 'nullable|string|max:1000',
        ]);

        $lead->update([
            'status' => 'lost',
            'lost_reason' => $validated['lost_reason'],
            'lost_reason_details' => $validated['lost_reason_details'] ?? null,
        ]);

        // Clear cache
        Cache::forget("lead_stats_{$currentBusiness->id}");

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
}
