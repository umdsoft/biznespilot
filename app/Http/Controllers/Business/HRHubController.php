<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\TalentPoolCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $stats = Cache::remember(
            "hr_hub_stats_{$business->id}",
            120, // 2 daqiqa — badge raqamlari real-time bo'lishi shart emas
            fn () => $this->buildStats($business->id),
        );

        return Inertia::render('Business/HR/Index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Barcha HR stats'ni tejamkor tarzda yig'adi.
     * Har count alohida try/catch — jadval yo'q bo'lsa nol qaytariladi.
     */
    private function buildStats(string $businessId): array
    {
        $teamCount = 1; // Owner doimo hisoblanadi
        $pendingInvitations = 0;
        $openVacancies = 0;
        $talentPoolSize = 0;
        $interviewsThisWeek = 0;

        try {
            $teamCount += BusinessUser::where('business_id', $businessId)
                ->whereNotNull('accepted_at')
                ->count();
            $pendingInvitations = BusinessUser::where('business_id', $businessId)
                ->whereNull('accepted_at')
                ->count();
        } catch (\Throwable $e) {}

        try {
            $openVacancies = JobPosting::where('business_id', $businessId)
                ->where('status', 'open')
                ->count();
        } catch (\Throwable $e) {}

        try {
            $talentPoolSize = TalentPoolCandidate::where('business_id', $businessId)
                ->where('status', 'available')
                ->count();
        } catch (\Throwable $e) {}

        try {
            $interviewsThisWeek = Interview::where('business_id', $businessId)
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();
        } catch (\Throwable $e) {}

        return [
            'total_employees' => $teamCount,
            'pending_invitations' => $pendingInvitations,
            'open_vacancies' => $openVacancies,
            'talent_pool_size' => $talentPoolSize,
            'interviews_this_week' => $interviewsThisWeek,
        ];
    }
}
