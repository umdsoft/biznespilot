<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\TalentPoolCandidate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HRHubController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.dashboard');
        }

        $teamCount = $business->users()->count() + 1;
        $pendingInvitations = BusinessUser::where('business_id', $business->id)
            ->whereNull('accepted_at')
            ->count();

        $openVacancies = 0;
        $talentPoolSize = 0;
        $interviewsThisWeek = 0;

        try {
            $openVacancies = JobPosting::where('business_id', $business->id)->where('status', 'open')->count();
        } catch (\Exception $e) {}

        try {
            $talentPoolSize = TalentPoolCandidate::where('business_id', $business->id)->where('status', 'available')->count();
        } catch (\Exception $e) {}

        try {
            $interviewsThisWeek = Interview::where('business_id', $business->id)
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();
        } catch (\Exception $e) {}

        $stats = [
            'total_employees' => $teamCount,
            'pending_invitations' => $pendingInvitations,
            'open_vacancies' => $openVacancies,
            'talent_pool_size' => $talentPoolSize,
            'interviews_this_week' => $interviewsThisWeek,
        ];

        return Inertia::render('Business/HR/Index', [
            'stats' => $stats,
        ]);
    }
}
