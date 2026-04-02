<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InterviewScheduleController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $interviews = Interview::where('business_id', $business->id)
            ->with(['application:id,candidate_name,candidate_email,job_posting_id', 'application.jobPosting:id,title', 'interviewer:id,name'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        $applications = JobApplication::where('business_id', $business->id)
            ->whereNotIn('status', ['hired', 'rejected'])
            ->select('id', 'candidate_name', 'job_posting_id')
            ->with('jobPosting:id,title')
            ->get();

        $interviewers = User::whereHas('businesses', fn ($q) => $q->where('businesses.id', $business->id))
            ->select('id', 'name')
            ->get();

        return Inertia::render('HR/Recruiting/Interviews', [
            'interviews' => $interviews,
            'applications' => $applications,
            'interviewers' => $interviewers,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $validated = $request->validate([
            'job_application_id' => 'required|exists:job_applications,id',
            'interview_type' => 'required|in:phone,video,in_person,technical',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'interviewer_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['business_id'] = $business->id;
        $validated['status'] = 'scheduled';

        Interview::create($validated);

        // Nomzodni pipeline_stage ga o'tkazish
        $application = JobApplication::find($validated['job_application_id']);
        if ($application && in_array($application->pipeline_stage, ['new', 'screening', 'phone_screen'])) {
            $application->update([
                'pipeline_stage' => 'interview_scheduled',
                'interview_scheduled_at' => $validated['scheduled_at'],
                'current_interviewer_id' => $validated['interviewer_id'],
                'interview_round' => $application->interview_round + 1,
            ]);
        }

        return redirect()->back()->with('success', 'Intervyu rejalashtirildi');
    }

    public function complete(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $interview = Interview::where('business_id', $business->id)->findOrFail($id);

        $validated = $request->validate([
            'feedback' => 'nullable|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string|max:1000',
        ]);

        $interview->update(array_merge($validated, ['status' => 'completed']));

        // Pipeline stage yangilash
        $application = $interview->application;
        if ($application && $application->pipeline_stage === 'interview_scheduled') {
            $application->update(['pipeline_stage' => 'interview_done']);
        }

        return redirect()->back()->with('success', 'Intervyu yakunlandi');
    }

    public function cancel($id)
    {
        $business = $this->getCurrentBusiness();
        $interview = Interview::where('business_id', $business->id)->findOrFail($id);
        $interview->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Intervyu bekor qilindi');
    }
}
