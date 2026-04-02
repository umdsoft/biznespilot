<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\JobApplication;
use App\Models\TalentPoolCandidate;
use App\Models\TalentPoolNote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TalentPoolController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $query = TalentPoolCandidate::where('business_id', $business->id)
            ->with('addedBy:id,name');

        // Filters
        if ($request->filled('search')) {
            $query->where('candidate_name', 'like', "%{$request->search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }
        if ($request->filled('employee_type')) {
            $query->where('employee_type', $request->employee_type);
        }
        if ($request->filled('skill')) {
            $query->whereJsonContains('skills', $request->skill);
        }

        $candidates = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => TalentPoolCandidate::where('business_id', $business->id)->count(),
            'available' => TalentPoolCandidate::where('business_id', $business->id)->where('status', 'available')->count(),
            'contacted' => TalentPoolCandidate::where('business_id', $business->id)->where('status', 'contacted')->count(),
            'hired' => TalentPoolCandidate::where('business_id', $business->id)->where('status', 'hired')->count(),
        ];

        return Inertia::render('HR/TalentPool/Index', [
            'candidates' => $candidates,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'rating', 'employee_type', 'skill']),
        ]);
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        $candidate = TalentPoolCandidate::where('business_id', $business->id)
            ->with(['application.jobPosting', 'addedBy:id,name'])
            ->findOrFail($id);

        $notesList = TalentPoolNote::where('talent_pool_candidate_id', $candidate->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        $candidate->notes_list = $notesList;

        return Inertia::render('HR/TalentPool/Show', [
            'candidate' => $candidate,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $validated = $request->validate([
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'nullable|email|max:255',
            'candidate_phone' => 'nullable|string|max:50',
            'skills' => 'nullable|array',
            'tags' => 'nullable|array',
            'employee_type' => 'nullable|in:thinker,doer,mixed',
            'rating' => 'nullable|integer|min:1|max:5',
            'expected_salary' => 'nullable|numeric|min:0',
            'preferred_position' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'linkedin_url' => 'nullable|url|max:500',
            'years_of_experience' => 'nullable|integer|min:0',
            'current_company' => 'nullable|string|max:255',
        ]);

        $validated['business_id'] = $business->id;
        $validated['added_by'] = auth()->id();
        $validated['source'] = 'manual';

        TalentPoolCandidate::create($validated);

        return redirect()->back()->with('success', 'Nomzod kadrlar zaxirasiga qo\'shildi');
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $candidate = TalentPoolCandidate::where('business_id', $business->id)->findOrFail($id);

        $validated = $request->validate([
            'candidate_name' => 'sometimes|string|max:255',
            'candidate_email' => 'nullable|email|max:255',
            'candidate_phone' => 'nullable|string|max:50',
            'skills' => 'nullable|array',
            'tags' => 'nullable|array',
            'employee_type' => 'nullable|in:thinker,doer,mixed',
            'rating' => 'nullable|integer|min:1|max:5',
            'expected_salary' => 'nullable|numeric|min:0',
            'preferred_position' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $candidate->update($validated);

        return redirect()->back()->with('success', 'Nomzod ma\'lumotlari yangilandi');
    }

    public function updateStatus(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $candidate = TalentPoolCandidate::where('business_id', $business->id)->findOrFail($id);

        $request->validate(['status' => 'required|in:available,contacted,not_interested,hired,archived']);

        $oldStatus = $candidate->status;
        $candidate->update([
            'status' => $request->status,
            'last_contacted_at' => $request->status === 'contacted' ? now() : $candidate->last_contacted_at,
        ]);

        TalentPoolNote::create([
            'talent_pool_candidate_id' => $candidate->id,
            'business_id' => $business->id,
            'user_id' => auth()->id(),
            'content' => "Status o'zgartirildi: {$oldStatus} → {$request->status}",
            'type' => 'status_change',
        ]);

        return redirect()->back()->with('success', 'Status yangilandi');
    }

    public function addNote(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $candidate = TalentPoolCandidate::where('business_id', $business->id)->findOrFail($id);

        $request->validate(['content' => 'required|string|max:2000']);

        TalentPoolNote::create([
            'talent_pool_candidate_id' => $candidate->id,
            'business_id' => $business->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'type' => 'note',
        ]);

        return redirect()->back()->with('success', 'Yozuv qo\'shildi');
    }

    public function addFromApplication($applicationId)
    {
        $business = $this->getCurrentBusiness();
        $application = JobApplication::where('business_id', $business->id)->findOrFail($applicationId);

        if ($application->added_to_talent_pool) {
            return redirect()->back()->with('error', 'Bu nomzod allaqachon kadrlar zaxirasida');
        }

        $candidate = TalentPoolCandidate::create([
            'business_id' => $business->id,
            'job_application_id' => $application->id,
            'candidate_name' => $application->candidate_name,
            'candidate_email' => $application->candidate_email,
            'candidate_phone' => $application->candidate_phone,
            'resume_path' => $application->resume_path,
            'linkedin_url' => $application->linkedin_url,
            'portfolio_url' => $application->portfolio_url,
            'years_of_experience' => $application->years_of_experience,
            'current_company' => $application->current_company,
            'expected_salary' => $application->expected_salary,
            'rating' => $application->rating,
            'source' => 'application',
            'source_vacancy_id' => $application->job_posting_id,
            'added_by' => auth()->id(),
        ]);

        $application->update(['added_to_talent_pool' => true]);

        return redirect()->back()->with('success', 'Nomzod kadrlar zaxirasiga qo\'shildi');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();
        $candidate = TalentPoolCandidate::where('business_id', $business->id)->findOrFail($id);
        $candidate->delete();

        return redirect()->back()->with('success', 'Nomzod o\'chirildi');
    }
}
