<?php

namespace App\Traits;

use Illuminate\Support\Collection;

/**
 * HasLeaderboardRanking - Reyting hisoblash uchun umumiy trait
 *
 * Bu trait Sales va Marketing Leaderboard servicelarida
 * qayta-qayta ishlatiladigan ranking funksiyalarini birlashtiradi.
 *
 * Ishlatilishi:
 * - Sales: LeaderboardService
 * - Marketing: MarketingLeaderboardService
 */
trait HasLeaderboardRanking
{
    /**
     * Collection'ni score bo'yicha tartiblash va rank berish
     * Bir xil scoreli userlar bir xil rank oladi (tie handling)
     *
     * @param Collection $items - ['user_id' => ..., 'score' => ...]
     * @param string $scoreField - Score maydoni nomi
     * @return Collection
     */
    protected function calculateRankings(Collection $items, string $scoreField = 'score'): Collection
    {
        // Score bo'yicha tartiblash
        $sorted = $items->sortByDesc($scoreField)->values();

        $rank = 0;
        $previousScore = null;
        $sameRankCount = 0;

        return $sorted->map(function ($item, $index) use ($scoreField, &$rank, &$previousScore, &$sameRankCount) {
            $currentScore = $item[$scoreField] ?? $item->$scoreField ?? 0;

            if ($currentScore !== $previousScore) {
                $rank += $sameRankCount + 1;
                $sameRankCount = 0;
            } else {
                $sameRankCount++;
            }

            $previousScore = $currentScore;

            // Array yoki object ekanligini tekshirish
            if (is_array($item)) {
                $item['rank'] = $rank;
            } else {
                $item->rank = $rank;
            }

            return $item;
        });
    }

    /**
     * Rank o'zgarishini hisoblash
     *
     * @param int|null $previousRank
     * @param int $currentRank
     * @return int - Ijobiy = ko'tarilgan, Salbiy = tushgan
     */
    protected function calculateRankChange(?int $previousRank, int $currentRank): int
    {
        if ($previousRank === null) {
            return 0;
        }

        return $previousRank - $currentRank;
    }

    /**
     * Medal aniqlash (Top 3)
     *
     * @param int $rank
     * @return string|null - gold, silver, bronze, null
     */
    protected function determineMedal(int $rank): ?string
    {
        return match ($rank) {
            1 => 'gold',
            2 => 'silver',
            3 => 'bronze',
            default => null,
        };
    }

    /**
     * Top performer ekanligini aniqlash
     *
     * @param int $rank
     * @param int $topThreshold - Default: 3
     * @return bool
     */
    protected function isTopPerformer(int $rank, int $topThreshold = 3): bool
    {
        return $rank <= $topThreshold;
    }

    /**
     * Streak (ketma-ket davr) hisoblash
     *
     * @param int|null $previousStreak - Oldingi davrdagi streak
     * @param int $currentRank - Joriy reyting
     * @param int $previousRank - Oldingi davrdagi reyting
     * @param int $streakThreshold - Streak uchun kerakli minimum rank (default: 3)
     * @return array{current: int, best: int}
     */
    protected function calculateStreak(
        ?int $previousStreak,
        int $currentRank,
        ?int $previousRank,
        int $streakThreshold = 3
    ): array {
        $previousStreak = $previousStreak ?? 0;
        $bestStreak = $previousStreak;

        // Joriy davr top threshold ichida bo'lsa
        if ($currentRank <= $streakThreshold) {
            // Oldingi davr ham top threshold ichida bo'lsa - streak davom etadi
            if ($previousRank !== null && $previousRank <= $streakThreshold) {
                $currentStreak = $previousStreak + 1;
            } else {
                // Yangi streak boshlanadi
                $currentStreak = 1;
            }
        } else {
            // Top threshold dan tashqarida - streak tugaydi
            $currentStreak = 0;
        }

        $bestStreak = max($bestStreak, $currentStreak);

        return [
            'current' => $currentStreak,
            'best' => $bestStreak,
        ];
    }

    /**
     * Rank uchun ball hisoblash (gamification)
     *
     * @param int $rank
     * @param array $pointsByRank - [1 => 100, 2 => 50, 3 => 25]
     * @param int $defaultPoints
     * @return int
     */
    protected function calculateRankPoints(
        int $rank,
        array $pointsByRank = [1 => 100, 2 => 75, 3 => 50, 4 => 25, 5 => 10],
        int $defaultPoints = 5
    ): int {
        return $pointsByRank[$rank] ?? $defaultPoints;
    }

    /**
     * Percentile hisoblash
     *
     * @param int $rank
     * @param int $totalParticipants
     * @return float - 0-100 orasida
     */
    protected function calculatePercentile(int $rank, int $totalParticipants): float
    {
        if ($totalParticipants <= 1) {
            return 100;
        }

        return round(((($totalParticipants - $rank) / ($totalParticipants - 1)) * 100), 1);
    }

    /**
     * User atrofidagi reytingni olish (neighborhood)
     *
     * @param Collection $rankedItems
     * @param string $userId
     * @param int $neighbors - Har tomondan nechta
     * @param string $userIdField
     * @return array{user: mixed, above: Collection, below: Collection}
     */
    protected function getUserNeighborhood(
        Collection $rankedItems,
        string $userId,
        int $neighbors = 2,
        string $userIdField = 'user_id'
    ): array {
        $userIndex = $rankedItems->search(function ($item) use ($userId, $userIdField) {
            $id = is_array($item) ? ($item[$userIdField] ?? null) : ($item->$userIdField ?? null);
            return $id === $userId;
        });

        if ($userIndex === false) {
            return [
                'user' => null,
                'above' => collect(),
                'below' => collect(),
            ];
        }

        $user = $rankedItems->get($userIndex);

        $above = $rankedItems->slice(
            max(0, $userIndex - $neighbors),
            min($neighbors, $userIndex)
        )->values();

        $below = $rankedItems->slice(
            $userIndex + 1,
            $neighbors
        )->values();

        return [
            'user' => $user,
            'above' => $above,
            'below' => $below,
        ];
    }
}
