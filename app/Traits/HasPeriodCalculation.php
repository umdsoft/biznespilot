<?php

namespace App\Traits;

use Carbon\Carbon;

/**
 * HasPeriodCalculation - Davr sanalarini hisoblash uchun umumiy trait
 *
 * Bu trait Sales va Marketing modullarida qayta-qayta ishlatiladigan
 * davr sanalarini hisoblash funksiyalarini birlashtiradi.
 *
 * Ishlatilishi:
 * - Sales: KpiCalculationService, LeaderboardService
 * - Marketing: MarketingKpiCalculatorService, MarketingLeaderboardService
 * - Jobs: CalculateDailyKpiSnapshotsJob, UpdateMarketingUserKpisJob
 */
trait HasPeriodCalculation
{
    /**
     * Davr sanalarini hisoblash (start, end)
     *
     * Ishlatilishi:
     * - [$start, $end] = $this->getPeriodDates('monthly', $date);
     * - list($start, $end) = $this->getPeriodDates('weekly');
     *
     * @param string $periodType - daily, weekly, monthly, quarterly, yearly
     * @param Carbon|null $date - Hisoblash sanasi (default: now())
     * @return array{0: Carbon, 1: Carbon} - [startDate, endDate]
     */
    protected function getPeriodDates(string $periodType, ?Carbon $date = null): array
    {
        $date = $date ?? now();

        return match ($periodType) {
            'daily' => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
            'weekly' => [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
            'quarterly' => [
                $date->copy()->startOfQuarter(),
                $date->copy()->endOfQuarter(),
            ],
            'yearly' => [
                $date->copy()->startOfYear(),
                $date->copy()->endOfYear(),
            ],
            default => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Oldingi davr sanalarini olish
     *
     * @param string $periodType
     * @param Carbon $currentPeriodStart
     * @return array{0: Carbon, 1: Carbon} - [startDate, endDate]
     */
    protected function getPreviousPeriodDates(string $periodType, Carbon $currentPeriodStart): array
    {
        $previousStart = match ($periodType) {
            'daily' => $currentPeriodStart->copy()->subDay(),
            'weekly' => $currentPeriodStart->copy()->subWeek(),
            'monthly' => $currentPeriodStart->copy()->subMonth(),
            'quarterly' => $currentPeriodStart->copy()->subQuarter(),
            'yearly' => $currentPeriodStart->copy()->subYear(),
            default => $currentPeriodStart->copy()->subDay(),
        };

        return $this->getPeriodDates($periodType, $previousStart);
    }

    /**
     * Keyingi davr sanalarini olish
     *
     * @param string $periodType
     * @param Carbon $currentPeriodStart
     * @return array{0: Carbon, 1: Carbon} - [startDate, endDate]
     */
    protected function getNextPeriodDates(string $periodType, Carbon $currentPeriodStart): array
    {
        $nextStart = match ($periodType) {
            'daily' => $currentPeriodStart->copy()->addDay(),
            'weekly' => $currentPeriodStart->copy()->addWeek(),
            'monthly' => $currentPeriodStart->copy()->addMonth(),
            'quarterly' => $currentPeriodStart->copy()->addQuarter(),
            'yearly' => $currentPeriodStart->copy()->addYear(),
            default => $currentPeriodStart->copy()->addDay(),
        };

        return $this->getPeriodDates($periodType, $nextStart);
    }

    /**
     * Davr uchun ish kunlari sonini hisoblash
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    protected function getWorkingDaysInPeriod(Carbon $start, Carbon $end): int
    {
        return $start->diffInWeekdays($end);
    }

    /**
     * Joriy davr progress foizini hisoblash
     * Masalan: oyning 15-kuni = 50% progress
     *
     * @param string $periodType
     * @param Carbon|null $date
     * @return float
     */
    protected function getPeriodProgress(string $periodType, ?Carbon $date = null): float
    {
        $date = $date ?? now();
        [$periodStart, $periodEnd] = $this->getPeriodDates($periodType, $date);

        $totalDays = $periodStart->diffInDays($periodEnd) + 1;
        $passedDays = $periodStart->diffInDays($date) + 1;

        return min(100, round(($passedDays / $totalDays) * 100, 2));
    }

    /**
     * Davr labelini olish (O'zbekcha)
     *
     * @param string $periodType
     * @param Carbon $periodStart
     * @return string
     */
    protected function getPeriodLabel(string $periodType, Carbon $periodStart): string
    {
        return match ($periodType) {
            'daily' => $periodStart->format('d.m.Y'),
            'weekly' => $periodStart->format('d.m') . ' - ' . $periodStart->copy()->endOfWeek()->format('d.m.Y'),
            'monthly' => $periodStart->translatedFormat('F Y'),
            'quarterly' => 'Q' . $periodStart->quarter . ' ' . $periodStart->year,
            'yearly' => (string) $periodStart->year,
            default => $periodStart->format('d.m.Y'),
        };
    }

    /**
     * Davr turlari ro'yxati
     *
     * @return array<string, string>
     */
    protected function getPeriodTypes(): array
    {
        return [
            'daily' => 'Kunlik',
            'weekly' => 'Haftalik',
            'monthly' => 'Oylik',
            'quarterly' => 'Choraklik',
            'yearly' => 'Yillik',
        ];
    }
}
