<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\JobPosting;
use App\Models\JobApplication;
use App\Models\JobDescription;
use App\Models\BusinessUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RecruitingController extends Controller
{
    use HasCurrentBusiness;

    // Job Postings List
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $jobPostings = JobPosting::where('business_id', $business->id)
            ->withCount('applications')
            ->with('postedBy:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($posting) {
                return [
                    'id' => $posting->id,
                    'title' => $posting->title,
                    'department' => $posting->department,
                    'department_label' => $posting->department_label,
                    'location' => $posting->location,
                    'employment_type' => $posting->employment_type,
                    'employment_type_label' => $posting->employment_type_label,
                    'openings' => $posting->openings,
                    'status' => $posting->status,
                    'status_label' => $posting->status_label,
                    'applications_count' => $posting->applications_count,
                    'posted_date' => $posting->posted_date?->format('d.m.Y'),
                    'closing_date' => $posting->closing_date?->format('d.m.Y'),
                    'posted_by_name' => $posting->postedBy?->name,
                    'created_at' => $posting->created_at->format('d.m.Y'),
                ];
            });

        $jobDescriptions = JobDescription::where('business_id', $business->id)
            ->where('is_active', true)
            ->get(['id', 'title', 'department']);

        return Inertia::render('HR/Recruiting/Index', [
            'jobPostings' => $jobPostings,
            'jobDescriptions' => $jobDescriptions,
            'departments' => BusinessUser::DEPARTMENTS,
            'employmentTypes' => JobPosting::EMPLOYMENT_TYPES,
            'statuses' => JobPosting::STATUSES,
        ]);
    }

    // Applications List
    public function applications(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $query = JobApplication::where('business_id', $business->id)
            ->with(['jobPosting:id,title', 'assignedTo:id,name']);

        // Filter by job posting
        if ($request->has('job_posting_id') && $request->job_posting_id) {
            $query->where('job_posting_id', $request->job_posting_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('applied_at', 'desc')
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'candidate_name' => $app->candidate_name,
                    'candidate_email' => $app->candidate_email,
                    'candidate_phone' => $app->candidate_phone,
                    'job_posting_title' => $app->jobPosting->title,
                    'years_of_experience' => $app->years_of_experience,
                    'current_company' => $app->current_company,
                    'expected_salary' => $app->expected_salary,
                    'status' => $app->status,
                    'status_label' => $app->status_label,
                    'status_color' => $app->status_color,
                    'rating' => $app->rating,
                    'assigned_to_name' => $app->assignedTo?->name,
                    'applied_at' => $app->applied_at?->format('d.m.Y H:i'),
                    'resume_path' => $app->resume_path,
                    'linkedin_url' => $app->linkedin_url,
                ];
            });

        $jobPostings = JobPosting::where('business_id', $business->id)
            ->get(['id', 'title']);

        return Inertia::render('HR/Recruiting/Applications', [
            'applications' => $applications,
            'jobPostings' => $jobPostings,
            'statuses' => JobApplication::STATUSES,
        ]);
    }

    // Create Job Posting
    public function storeJobPosting(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'job_description_id' => 'nullable|exists:job_descriptions,id',
            'title' => 'required|string|max:255',
            'department' => 'required|in:' . implode(',', array_keys(BusinessUser::DEPARTMENTS)),
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'required|in:' . implode(',', array_keys(JobPosting::EMPLOYMENT_TYPES)),
            'openings' => 'required|integer|min:1',
            'posted_date' => 'nullable|date',
            'closing_date' => 'nullable|date|after_or_equal:posted_date',
        ], [
            'title.required' => 'Lavozim nomi kiritilishi shart',
            'department.required' => 'Bo\'lim tanlanishi shart',
            'employment_type.required' => 'Ish turi tanlanishi shart',
            'openings.required' => 'O\'rinlar soni kiritilishi shart',
        ]);

        $jobPosting = JobPosting::create([
            'business_id' => $business->id,
            'posted_by' => Auth::id(),
            'status' => JobPosting::STATUS_OPEN,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vakansiya muvaffaqiyatli yaratildi',
            'jobPosting' => [
                'id' => $jobPosting->id,
                'title' => $jobPosting->title,
            ],
        ]);
    }

    // Update Job Posting Status
    public function updateJobPostingStatus(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $jobPosting = JobPosting::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(JobPosting::STATUSES)),
        ]);

        $jobPosting->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status yangilandi',
        ]);
    }

    // Update Application Status
    public function updateApplicationStatus(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $application = JobApplication::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(JobApplication::STATUSES)),
            'notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $application->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status yangilandi',
        ]);
    }

    // Delete Job Posting
    public function destroyJobPosting($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $jobPosting = JobPosting::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Check if there are applications
        if ($jobPosting->applications()->count() > 0) {
            return response()->json([
                'error' => 'Bu vakansiyaga arizalar mavjud. Avval statusni "Yopilgan" ga o\'zgartiring'
            ], 422);
        }

        $jobPosting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vakansiya o\'chirildi',
        ]);
    }
}
