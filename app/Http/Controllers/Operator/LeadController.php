<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeadController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (!$business) {
            return Inertia::render('Operator/Leads/Index', [
                'leads' => [],
                'stats' => ['total' => 0, 'new' => 0, 'in_progress' => 0],
            ]);
        }

        $query = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate(20)->through(fn($lead) => [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'status' => $lead->status,
            'source' => $lead->source,
            'notes' => $lead->notes,
            'created_at' => $lead->created_at->format('Y-m-d H:i'),
            'last_contact' => $lead->last_contact?->format('Y-m-d H:i'),
        ]);

        $stats = [
            'total' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->count(),
            'new' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'new')->count(),
            'in_progress' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'in_progress')->count(),
            'contacted' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'contacted')->count(),
            'converted' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'converted')->count(),
        ];

        return Inertia::render('Operator/Leads/Index', [
            'leads' => $leads,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $lead = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->findOrFail($id);

        $activities = LeadActivity::where('lead_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'description' => $a->description,
                'created_at' => $a->created_at->format('Y-m-d H:i'),
                'user' => $a->user?->name,
            ]);

        return Inertia::render('Operator/Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'status' => $lead->status,
                'source' => $lead->source,
                'notes' => $lead->notes,
                'created_at' => $lead->created_at->format('Y-m-d H:i'),
                'last_contact' => $lead->last_contact?->format('Y-m-d H:i'),
            ],
            'activities' => $activities,
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
            'status' => 'required|in:new,contacted,in_progress,qualified,converted,lost',
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
            'description' => "Qo'ng'iroq: {$validated['outcome']}" . ($validated['notes'] ? " - {$validated['notes']}" : ''),
            'metadata' => [
                'outcome' => $validated['outcome'],
                'duration' => $validated['duration'] ?? null,
            ],
        ]);

        $lead->update(['last_contact' => now()]);

        return redirect()->back()->with('success', 'Qo\'ng\'iroq qayd etildi');
    }
}
