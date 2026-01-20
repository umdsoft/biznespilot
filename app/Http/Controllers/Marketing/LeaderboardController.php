<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\MarketingLeaderboard;
use App\Services\MarketingLeaderboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        private MarketingLeaderboardService $leaderboardService
    ) {}

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $periodType = $request->get('period', 'weekly');
        $periodStart = $request->get('period_start')
            ? Carbon::parse($request->get('period_start'))
            : null;

        // Get leaderboard
        $leaderboard = $this->leaderboardService->getLeaderboard(
            $business,
            $periodType,
            $periodStart,
            20
        );

        // Get current user's ranking
        $user = auth()->user();
        $myRanking = $this->leaderboardService->getUserRanking($business, $user, $periodType);

        // Get user's XP and level
        $xpData = $this->leaderboardService->getTotalXpAndCoins($business, $user);

        // Get achievement stats
        $achievements = $this->leaderboardService->getAchievementStats($business, $periodType);

        // Get streak leaders
        $streakLeaders = $this->leaderboardService->getStreakLeaders($business);

        return Inertia::render('Marketing/Leaderboard/Index', [
            'leaderboard' => $leaderboard->map(fn($entry) => [
                'id' => $entry->id,
                'user_id' => $entry->user_id,
                'user_name' => $entry->user->name ?? 'Unknown',
                'overall_rank' => $entry->overall_rank,
                'rank_badge' => $entry->getRankBadge(),
                'rank_color' => $entry->getRankColor(),
                'overall_score' => round($entry->overall_score, 1),
                'leads_score' => $entry->leads_score,
                'conversion_score' => $entry->conversion_score,
                'roi_score' => round($entry->roi_score, 1),
                'achievements' => $entry->achievements ?? [],
                'xp_earned' => $entry->xp_earned,
                'coins_earned' => $entry->coins_earned,
                'current_streak' => $entry->current_streak,
                'is_top_performer' => $entry->isTopPerformer(),
            ]),
            'myRanking' => $myRanking ? [
                'rank' => $myRanking->overall_rank,
                'score' => round($myRanking->overall_score, 1),
                'leads_rank' => $myRanking->leads_rank,
                'conversion_rank' => $myRanking->conversion_rank,
                'roi_rank' => $myRanking->roi_rank,
                'achievements' => $myRanking->achievements ?? [],
                'xp_earned' => $myRanking->xp_earned,
                'coins_earned' => $myRanking->coins_earned,
                'current_streak' => $myRanking->current_streak,
                'best_streak' => $myRanking->best_streak,
            ] : null,
            'xpData' => $xpData,
            'achievements' => $achievements,
            'streakLeaders' => $streakLeaders->map(fn($entry) => [
                'user_name' => $entry->user->name ?? 'Unknown',
                'streak' => $entry->current_streak,
            ]),
            'periodType' => $periodType,
            'availableAchievements' => MarketingLeaderboard::ACHIEVEMENTS,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function myProfile(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Get user history
        $history = $this->leaderboardService->getUserHistory($business, $user, 12);

        // Get XP and level data
        $xpData = $this->leaderboardService->getTotalXpAndCoins($business, $user);

        // Get all achievements earned
        $allAchievements = $history->flatMap(fn($entry) => $entry->achievements ?? [])->unique()->values();

        return Inertia::render('Marketing/Leaderboard/Profile', [
            'history' => $history->map(fn($entry) => [
                'period_start' => $entry->period_start->format('Y-m-d'),
                'period_type' => $entry->period_type,
                'overall_rank' => $entry->overall_rank,
                'overall_score' => round($entry->overall_score, 1),
                'leads_score' => $entry->leads_score,
                'conversion_score' => $entry->conversion_score,
                'roi_score' => round($entry->roi_score, 1),
                'xp_earned' => $entry->xp_earned,
                'coins_earned' => $entry->coins_earned,
                'achievements' => $entry->achievements ?? [],
            ]),
            'xpData' => $xpData,
            'earnedAchievements' => $allAchievements,
            'availableAchievements' => MarketingLeaderboard::ACHIEVEMENTS,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function weekly(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $leaderboard = $this->leaderboardService->getLeaderboard($business, 'weekly', null, 10);

        return response()->json([
            'leaderboard' => $leaderboard->map(fn($entry) => [
                'user_name' => $entry->user->name ?? 'Unknown',
                'rank' => $entry->overall_rank,
                'rank_badge' => $entry->getRankBadge(),
                'score' => round($entry->overall_score, 1),
                'xp_earned' => $entry->xp_earned,
                'is_top_performer' => $entry->isTopPerformer(),
            ]),
        ]);
    }

    public function monthly(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $leaderboard = $this->leaderboardService->getLeaderboard($business, 'monthly', null, 10);

        return response()->json([
            'leaderboard' => $leaderboard->map(fn($entry) => [
                'user_name' => $entry->user->name ?? 'Unknown',
                'rank' => $entry->overall_rank,
                'rank_badge' => $entry->getRankBadge(),
                'score' => round($entry->overall_score, 1),
                'xp_earned' => $entry->xp_earned,
                'is_top_performer' => $entry->isTopPerformer(),
            ]),
        ]);
    }

    public function refresh(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $periodType = $request->get('period', 'weekly');

        $entries = match ($periodType) {
            'weekly' => $this->leaderboardService->updateWeeklyLeaderboard($business),
            'monthly' => $this->leaderboardService->updateMonthlyLeaderboard($business),
            default => collect(),
        };

        return response()->json([
            'success' => true,
            'entries_updated' => $entries->count(),
            'message' => 'Leaderboard yangilandi',
        ]);
    }

    public function achievements(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $stats = $this->leaderboardService->getAchievementStats($business);

        return response()->json([
            'achievements' => MarketingLeaderboard::ACHIEVEMENTS,
            'stats' => $stats,
        ]);
    }
}
