<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InterviewPipelineController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $query = JobApplication::where('business_id', $business->id)
            ->with(['jobPosting:id,title', 'assignedTo:id,name']);

        if ($request->filled('job_posting_id')) {
            $query->where('job_posting_id', $request->job_posting_id);
        }

        $applications = $query->orderBy('updated_at', 'desc')->get()
            ->map(fn ($app) => [
                'id' => $app->id,
                'candidate_name' => $app->candidate_name,
                'candidate_email' => $app->candidate_email,
                'pipeline_stage' => $app->pipeline_stage ?: $app->status,
                'rating' => $app->rating,
                'job_posting' => $app->jobPosting?->title,
                'assigned_to' => $app->assignedTo?->name,
                'interview_round' => $app->interview_round,
                'updated_at' => $app->updated_at->diffForHumans(),
                'days_in_stage' => $app->updated_at->diffInDays(now()),
            ]);

        $stages = JobApplication::PIPELINE_STAGES;

        $jobPostings = JobPosting::where('business_id', $business->id)
            ->where('status', 'open')
            ->select('id', 'title')
            ->get();

        return Inertia::render('HR/Recruiting/Pipeline', [
            'applications' => $applications,
            'stages' => $stages,
            'jobPostings' => $jobPostings,
            'selectedJobPosting' => $request->job_posting_id,
        ]);
    }

    public function moveStage(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $application = JobApplication::where('business_id', $business->id)->findOrFail($id);

        $request->validate([
            'stage' => 'required|in:' . implode(',', array_keys(JobApplication::PIPELINE_STAGES)),
        ]);

        $newStage = $request->stage;

        // Status ni ham sinxronlashtirish
        $statusMap = [
            'new' => 'new',
            'screening' => 'screening',
            'phone_screen' => 'screening',
            'interview_scheduled' => 'interviewing',
            'interview_done' => 'interviewing',
            'assessment' => 'interviewing',
            'offer' => 'offer',
            'hired' => 'hired',
            'rejected' => 'rejected',
        ];

        $application->update([
            'pipeline_stage' => $newStage,
            'status' => $statusMap[$newStage] ?? $application->status,
        ]);

        return response()->json(['success' => true]);
    }
}
