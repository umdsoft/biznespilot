<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\JobDescription;
use App\Models\BusinessUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JobDescriptionsController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $jobDescriptions = JobDescription::where('business_id', $business->id)
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'department' => $job->department,
                    'department_label' => $job->department_label,
                    'position_level' => $job->position_level,
                    'position_level_label' => $job->position_level_label,
                    'employment_type' => $job->employment_type,
                    'employment_type_label' => $job->employment_type_label,
                    'location' => $job->location,
                    'salary_range_formatted' => $job->salary_range_formatted,
                    'is_active' => $job->is_active,
                    'created_by_name' => $job->creator?->name,
                    'created_at' => $job->created_at->format('d.m.Y'),
                ];
            });

        return Inertia::render('HR/JobDescriptions/Index', [
            'jobDescriptions' => $jobDescriptions,
            'departments' => BusinessUser::DEPARTMENTS,
            'positionLevels' => JobDescription::POSITION_LEVELS,
            'employmentTypes' => JobDescription::EMPLOYMENT_TYPES,
        ]);
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $jobDescription = JobDescription::where('business_id', $business->id)
            ->where('id', $id)
            ->with('creator:id,name')
            ->firstOrFail();

        return Inertia::render('HR/JobDescriptions/Show', [
            'jobDescription' => [
                'id' => $jobDescription->id,
                'title' => $jobDescription->title,
                'department' => $jobDescription->department,
                'department_label' => $jobDescription->department_label,
                'position_level' => $jobDescription->position_level,
                'position_level_label' => $jobDescription->position_level_label,
                'reports_to' => $jobDescription->reports_to,
                'job_summary' => $jobDescription->job_summary,
                'responsibilities' => $jobDescription->responsibilities,
                'requirements' => $jobDescription->requirements,
                'qualifications' => $jobDescription->qualifications,
                'skills' => $jobDescription->skills,
                'salary_range_min' => $jobDescription->salary_range_min,
                'salary_range_max' => $jobDescription->salary_range_max,
                'salary_range_formatted' => $jobDescription->salary_range_formatted,
                'employment_type' => $jobDescription->employment_type,
                'employment_type_label' => $jobDescription->employment_type_label,
                'location' => $jobDescription->location,
                'is_active' => $jobDescription->is_active,
                'created_by_name' => $jobDescription->creator?->name,
                'created_at' => $jobDescription->created_at->format('d.m.Y H:i'),
            ],
            'departments' => BusinessUser::DEPARTMENTS,
            'positionLevels' => JobDescription::POSITION_LEVELS,
            'employmentTypes' => JobDescription::EMPLOYMENT_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|in:' . implode(',', array_keys(BusinessUser::DEPARTMENTS)),
            'position_level' => 'nullable|in:' . implode(',', array_keys(JobDescription::POSITION_LEVELS)),
            'reports_to' => 'nullable|string|max:255',
            'job_summary' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'requirements' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'skills' => 'nullable|string',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'employment_type' => 'required|in:' . implode(',', array_keys(JobDescription::EMPLOYMENT_TYPES)),
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ], [
            'title.required' => 'Lavozim nomi kiritilishi shart',
            'department.required' => 'Bo\'lim tanlanishi shart',
            'employment_type.required' => 'Ish turi tanlanishi shart',
        ]);

        $jobDescription = JobDescription::create([
            'business_id' => $business->id,
            'created_by' => Auth::id(),
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lavozim majburiyati muvaffaqiyatli yaratildi',
            'jobDescription' => [
                'id' => $jobDescription->id,
                'title' => $jobDescription->title,
                'department_label' => $jobDescription->department_label,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $jobDescription = JobDescription::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|in:' . implode(',', array_keys(BusinessUser::DEPARTMENTS)),
            'position_level' => 'nullable|in:' . implode(',', array_keys(JobDescription::POSITION_LEVELS)),
            'reports_to' => 'nullable|string|max:255',
            'job_summary' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'requirements' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'skills' => 'nullable|string',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'employment_type' => 'required|in:' . implode(',', array_keys(JobDescription::EMPLOYMENT_TYPES)),
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ], [
            'title.required' => 'Lavozim nomi kiritilishi shart',
            'department.required' => 'Bo\'lim tanlanishi shart',
            'employment_type.required' => 'Ish turi tanlanishi shart',
        ]);

        $jobDescription->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lavozim majburiyati yangilandi',
        ]);
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $jobDescription = JobDescription::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $jobDescription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lavozim majburiyati o\'chirildi',
        ]);
    }

    public function toggleStatus($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $jobDescription = JobDescription::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $jobDescription->update([
            'is_active' => !$jobDescription->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => $jobDescription->is_active ? 'Lavozim faollashtirildi' : 'Lavozim faolsizlantirildi',
            'is_active' => $jobDescription->is_active,
        ]);
    }
}
