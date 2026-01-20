<?php

namespace App\Traits;

/**
 * HasKpiCalculation - KPI metrikalar hisoblash uchun umumiy trait
 *
 * Bu trait Sales va Marketing modullarida ishlatiladigan
 * derived metrikalar hisoblash formulalarini birlashtiradi.
 *
 * ESLATMA: Metod nomlari "compute" prefiksi bilan boshlanadi -
 * mavjud service metodlari bilan conflict bo'lmasligi uchun.
 *
 * Ishlatilishi:
 * - Sales: KpiCalculationService
 * - Marketing: MarketingKpiCalculatorService, MarketingIntegrationListener
 */
trait HasKpiCalculation
{
    /**
     * CPL - Cost Per Lead hisoblash
     *
     * @param float $totalSpend
     * @param int $leadsCount
     * @param int $precision
     * @return float
     */
    protected function computeCpl(float $totalSpend, int $leadsCount, int $precision = 2): float
    {
        if ($leadsCount <= 0 || $totalSpend <= 0) {
            return 0;
        }

        return round($totalSpend / $leadsCount, $precision);
    }

    /**
     * CAC - Customer Acquisition Cost hisoblash
     *
     * @param float $totalSpend
     * @param int $customersCount
     * @param int $precision
     * @return float
     */
    protected function computeCac(float $totalSpend, int $customersCount, int $precision = 2): float
    {
        if ($customersCount <= 0 || $totalSpend <= 0) {
            return 0;
        }

        return round($totalSpend / $customersCount, $precision);
    }

    /**
     * ROAS - Return On Ad Spend hisoblash
     *
     * @param float $totalRevenue
     * @param float $totalSpend
     * @param int $precision
     * @return float
     */
    protected function computeRoas(float $totalRevenue, float $totalSpend, int $precision = 4): float
    {
        if ($totalSpend <= 0) {
            return 0;
        }

        return round($totalRevenue / $totalSpend, $precision);
    }

    /**
     * ROI - Return On Investment (%) hisoblash
     *
     * @param float $totalRevenue
     * @param float $totalSpend
     * @param int $precision
     * @return float
     */
    protected function computeRoi(float $totalRevenue, float $totalSpend, int $precision = 2): float
    {
        if ($totalSpend <= 0) {
            return 0;
        }

        return round((($totalRevenue - $totalSpend) / $totalSpend) * 100, $precision);
    }

    /**
     * Conversion Rate (%) hisoblash
     *
     * @param int $converted
     * @param int $total
     * @param int $precision
     * @return float
     */
    protected function computeConversionRate(int $converted, int $total, int $precision = 2): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($converted / $total) * 100, $precision);
    }

    /**
     * Stage Conversion Rate (A -> B) hisoblash
     *
     * @param int $stageACount
     * @param int $stageBCount
     * @param int $precision
     * @return float
     */
    protected function computeStageConversion(int $stageACount, int $stageBCount, int $precision = 2): float
    {
        if ($stageACount <= 0) {
            return 0;
        }

        return round(($stageBCount / $stageACount) * 100, $precision);
    }

    /**
     * Average Deal Size hisoblash
     *
     * @param float $totalRevenue
     * @param int $dealsCount
     * @param int $precision
     * @return float
     */
    protected function computeAvgDealSize(float $totalRevenue, int $dealsCount, int $precision = 2): float
    {
        if ($dealsCount <= 0) {
            return 0;
        }

        return round($totalRevenue / $dealsCount, $precision);
    }

    /**
     * Growth Rate (%) - Davr bo'yicha o'sish
     *
     * @param float $currentValue
     * @param float $previousValue
     * @param int $precision
     * @return float|null - null agar oldingi qiymat 0 bo'lsa
     */
    protected function computeGrowthRate(float $currentValue, float $previousValue, int $precision = 2): ?float
    {
        if ($previousValue == 0) {
            return $currentValue > 0 ? null : 0; // Infinity holati
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, $precision);
    }

    /**
     * Achievement Percentage - Maqsadga erishish foizi
     *
     * @param float $actual
     * @param float $target
     * @param int $precision
     * @return float
     */
    protected function computeAchievement(float $actual, float $target, int $precision = 1): float
    {
        if ($target <= 0) {
            return 0;
        }

        return round(($actual / $target) * 100, $precision);
    }

    /**
     * Weighted Score hisoblash
     *
     * @param array $scores - ['kpi_name' => ['score' => 80, 'weight' => 30], ...]
     * @return float
     */
    protected function computeWeightedScore(array $scores): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($scores as $data) {
            $score = $data['score'] ?? 0;
            $weight = $data['weight'] ?? 0;

            $weightedSum += $score * ($weight / 100);
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0) {
            return 0;
        }

        return round($weightedSum / ($totalWeight / 100), 1);
    }

    /**
     * Performance Tier aniqlash
     *
     * @param float $score
     * @param array $tiers - ['excellent' => 120, 'good' => 100, 'average' => 80, 'poor' => 60]
     * @return string
     */
    protected function determinePerformanceTier(
        float $score,
        array $tiers = [
            'excellent' => 120,
            'good' => 100,
            'average' => 80,
            'poor' => 60,
            'critical' => 0,
        ]
    ): string {
        foreach ($tiers as $tier => $threshold) {
            if ($score >= $threshold) {
                return $tier;
            }
        }

        return 'critical';
    }

    /**
     * Bonus Multiplier olish (tiered system)
     *
     * @param float $kpiScore
     * @param array $multipliers - [threshold => multiplier] kamayish tartibida
     * @return float
     */
    protected function getBonusMultiplier(
        float $kpiScore,
        array $multipliers = [
            150 => 2.0,   // Super Performer
            120 => 1.5,   // High Performer
            100 => 1.2,   // Target Achieved
            80 => 1.0,    // Standard
            60 => 0.75,   // Below Target
            0 => 0.5,     // Minimum
        ]
    ): float {
        // Katta thresholddan kichikka qarab tekshirish
        foreach ($multipliers as $threshold => $multiplier) {
            if ($kpiScore >= $threshold) {
                return $multiplier;
            }
        }

        return 0.5; // Default minimum
    }

    /**
     * Deviation (og'ish) hisoblash
     *
     * @param float $actual
     * @param float $target
     * @param int $precision
     * @return float - Ijobiy = target dan yuqori, Salbiy = past
     */
    protected function computeDeviation(float $actual, float $target, int $precision = 2): float
    {
        if ($target == 0) {
            return 0;
        }

        return round((($actual - $target) / $target) * 100, $precision);
    }

    /**
     * Trend aniqlash (oxirgi n ta qiymat asosida)
     *
     * @param array $values - Vaqt bo'yicha tartiblangan qiymatlar
     * @return string - 'up', 'down', 'stable'
     */
    protected function determineTrend(array $values): string
    {
        if (count($values) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($values, 0, (int) ceil(count($values) / 2));
        $secondHalf = array_slice($values, (int) ceil(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $change = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;

        if ($change > 5) {
            return 'up';
        } elseif ($change < -5) {
            return 'down';
        }

        return 'stable';
    }
}
