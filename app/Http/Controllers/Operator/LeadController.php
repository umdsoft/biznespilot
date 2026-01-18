<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeadController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login')->with('error', 'Biznes topilmadi');
        }

        return Inertia::render('Operator/Leads/Index', [
            'leads' => null, // Lazy load via API
            'stats' => null, // Lazy load via API
            'channels' => [],
            'operators' => [],
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get my assigned leads with pagination for lazy loading
     */
    public function getLeads(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $perPage = $request->input('per_page', 100);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->with(['source']);

        // Apply filters
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        $leads = $query->paginate($perPage)->through(fn ($lead) => [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'status' => $lead->status,
            'source' => $lead->source?->name ?? 'Unknown',
            'source_id' => $lead->source_id,
            'notes' => $lead->notes,
            'priority' => $lead->priority ?? null,
            'value' => $lead->value ?? 0,
            'created_at' => $lead->created_at->format('Y-m-d H:i'),
            'last_contact' => $lead->last_contact?->format('Y-m-d H:i'),
        ]);

        return response()->json($leads);
    }

    /**
     * API: Get stats for my assigned leads
     */
    public function getStats()
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $stats = [
            'total_leads' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->count(),
            'new_leads' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'new')->count(),
            'qualified_leads' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'qualified')->count(),
            'pipeline_value' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->whereNotIn('status', ['converted', 'lost'])->sum('value'),
            'won_deals' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'converted')->count(),
            'total_value' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'converted')->sum('value'),
        ];

        return response()->json($stats);
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (! $business) {
            return redirect()->route('login');
        }

        $lead = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->with(['source', 'assignedTo', 'tasks'])
            ->findOrFail($id);

        // Format dates for display
        $lead->created_at_formatted = $lead->created_at?->format('d.m.Y H:i');
        $lead->last_contacted_at_formatted = $lead->last_contacted_at?->format('d.m.Y H:i');

        return Inertia::render('Operator/Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'position' => $lead->position,
                'source_id' => $lead->source_id,
                'source_name' => $lead->source?->name,
                'assigned_to' => $lead->assigned_to,
                'assigned_to_name' => $lead->assignedTo?->name,
                'status' => $lead->status,
                'priority' => $lead->priority,
                'score' => $lead->score,
                'tags' => $lead->tags ?? [],
                'notes' => $lead->notes,
                'address' => $lead->address,
                'city' => $lead->city,
                'region' => $lead->region,
                'district' => $lead->district,
                'estimated_value' => $lead->estimated_value,
                'actual_value' => $lead->actual_value,
                'created_at' => $lead->created_at_formatted,
                'last_contacted_at' => $lead->last_contacted_at_formatted,
                'converted_at' => $lead->converted_at?->format('d.m.Y H:i'),
                'lost_at' => $lead->lost_at?->format('d.m.Y H:i'),
                'lost_reason' => $lead->lost_reason,
                'tasks_count' => $lead->tasks->count(),
                'activities_count' => $lead->activities_count ?? 0,
            ],
            'operators' => [],
            'sources' => [],
            'regions' => [],
            'districts' => [],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $lead = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,callback,considering,meeting_scheduled,meeting_attended,won,lost',
        ]);

        $oldStatus = $lead->status;
        $lead->update([
            'status' => $validated['status'],
            'last_contact' => now(),
        ]);

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'type' => 'status_change',
            'description' => "Status o'zgartirildi: {$oldStatus} -> {$validated['status']}",
        ]);

        return redirect()->back()->with('success', 'Lead statusi yangilandi');
    }

    public function addNote(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $lead = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->findOrFail($id);

        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        // Log activity
        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'type' => 'note',
            'description' => $validated['note'],
        ]);

        $lead->update(['last_contact' => now()]);

        return redirect()->back()->with('success', 'Izoh qo\'shildi');
    }

    public function logCall(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $lead = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->findOrFail($id);

        $validated = $request->validate([
            'outcome' => 'required|in:answered,no_answer,busy,callback',
            'notes' => 'nullable|string|max:1000',
            'duration' => 'nullable|integer|min:0',
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'type' => 'call',
            'description' => "Qo'ng'iroq: {$validated['outcome']}".($validated['notes'] ? " - {$validated['notes']}" : ''),
            'metadata' => [
                'outcome' => $validated['outcome'],
                'duration' => $validated['duration'] ?? null,
            ],
        ]);

        $lead->update(['last_contact' => now()]);

        return redirect()->back()->with('success', 'Qo\'ng\'iroq qayd etildi');
    }
}
