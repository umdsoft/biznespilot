<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\CandidateAssessmentLink;
use App\Models\HRSurveyResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidateAssessmentPublicController extends Controller
{
    public function show($token)
    {
        $link = CandidateAssessmentLink::where('token', $token)
            ->with('survey')
            ->firstOrFail();

        if ($link->isExpired()) {
            return Inertia::render('Public/CandidateAssessment', [
                'expired' => true,
                'link' => null,
                'survey' => null,
            ]);
        }

        if ($link->status === 'completed') {
            return Inertia::render('Public/CandidateAssessment', [
                'completed' => true,
                'link' => $link,
                'survey' => null,
            ]);
        }

        if ($link->status === 'pending') {
            $link->update(['status' => 'started']);
        }

        return Inertia::render('Public/CandidateAssessment', [
            'link' => $link,
            'survey' => $link->survey,
        ]);
    }

    public function submit(Request $request, $token)
    {
        $link = CandidateAssessmentLink::where('token', $token)->firstOrFail();

        if ($link->isExpired() || $link->status === 'completed') {
            return redirect()->back()->with('error', 'Bu baholash muddati tugagan yoki allaqachon to\'ldirilgan.');
        }

        $request->validate(['answers' => 'required|array']);

        $response = HRSurveyResponse::create([
            'hr_survey_id' => $link->hr_survey_id,
            'business_id' => $link->business_id,
            'user_id' => null, // tashqi nomzod
            'answers' => $request->answers,
            'is_complete' => true,
            'completed_at' => now(),
            'metadata' => [
                'candidate_name' => $link->candidate_name,
                'assessment_link_id' => $link->id,
                'submitted_from_ip' => $request->ip(),
            ],
        ]);

        $link->update([
            'status' => 'completed',
            'response_id' => $response->id,
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Javoblaringiz uchun rahmat!');
    }
}
