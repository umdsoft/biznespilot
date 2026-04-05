<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicJobApplicationController extends Controller
{
    /**
     * Public vakansiya sahifasi — nomzod ariza to'ldiradi.
     */
    public function show(string $slug)
    {
        $posting = JobPosting::where('slug', $slug)
            ->where('status', 'open')
            ->where('is_public', true)
            ->with('business:id,name,logo')
            ->firstOrFail();

        return Inertia::render('Public/JobApplication', [
            'posting' => [
                'id' => $posting->id,
                'title' => $posting->title,
                'department' => $posting->department,
                'description' => $posting->description,
                'requirements' => $posting->requirements,
                'location' => $posting->location,
                'employment_type' => $posting->employment_type,
                'salary_min' => $posting->salary_min,
                'salary_max' => $posting->salary_max,
                'form_fields' => $posting->form_fields ?? [],
                'success_message' => $posting->success_message,
                'business_name' => $posting->business?->name,
                'business_logo' => $posting->business?->logo,
            ],
        ]);
    }

    /**
     * Nomzod ariza yuboradi — JobApplication yaratiladi va pipeline'ga tushadi.
     */
    public function submit(Request $request, string $slug)
    {
        $posting = JobPosting::where('slug', $slug)
            ->where('status', 'open')
            ->where('is_public', true)
            ->firstOrFail();

        $validated = $request->validate([
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'nullable|email|max:255',
            'candidate_phone' => 'nullable|string|max:50',
            'cover_letter' => 'nullable|string|max:3000',
            'linkedin_url' => 'nullable|url|max:500',
            'portfolio_url' => 'nullable|url|max:500',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
            'current_company' => 'nullable|string|max:255',
            'expected_salary' => 'nullable|numeric|min:0',
            'custom_answers' => 'nullable|array',
        ]);

        // Dublikat tekshiruv (telefon yoki email bo'yicha)
        if ($validated['candidate_email'] || $validated['candidate_phone']) {
            $existing = JobApplication::where('business_id', $posting->business_id)
                ->where('job_posting_id', $posting->id)
                ->where(function ($q) use ($validated) {
                    if ($validated['candidate_email']) {
                        $q->where('candidate_email', $validated['candidate_email']);
                    }
                    if ($validated['candidate_phone']) {
                        $q->orWhere('candidate_phone', $validated['candidate_phone']);
                    }
                })
                ->exists();

            if ($existing) {
                return back()->with('error', 'Siz allaqachon bu vakansiyaga ariza topshirgansiz.');
            }
        }

        JobApplication::create([
            'business_id' => $posting->business_id,
            'job_posting_id' => $posting->id,
            'candidate_name' => $validated['candidate_name'],
            'candidate_email' => $validated['candidate_email'] ?? null,
            'candidate_phone' => $validated['candidate_phone'] ?? null,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'years_of_experience' => $validated['years_of_experience'] ?? null,
            'current_company' => $validated['current_company'] ?? null,
            'expected_salary' => $validated['expected_salary'] ?? null,
            'status' => 'new',
            'pipeline_stage' => 'new',
            'applied_at' => now(),
            'notes' => ! empty($validated['custom_answers']) ? json_encode($validated['custom_answers']) : null,
        ]);

        return back()->with('success', $posting->success_message ?? 'Arizangiz qabul qilindi! Tez orada siz bilan bog\'lanamiz.');
    }
}
