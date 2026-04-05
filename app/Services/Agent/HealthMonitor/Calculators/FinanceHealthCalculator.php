<?php

namespace App\Services\Agent\HealthMonitor\Calculators;

use Illuminate\Support\Facades\DB;

/**
 * Moliyaviy sog'lik kalkulyatori (bazadan, bepul).
 * Ball = revenue_trend(30%) + cac_efficiency(25%) + roas_health(25%) + spend_balance(20%)
 */
class FinanceHealthCalculator
{
    public function calculate(string $businessId): array
    {
        $revenueTrend = $this->getRevenueTrend($businessId);
        $cacEfficiency = $this->getCacEfficiency($businessId);
        $roasHealth = $this->getRoasHealth($businessId);
        $spendBalance = $this->getSpendBalance($businessId);

        $score = (int) round(
            $revenueTrend * 0.30
            + $cacEfficiency * 0.25
            + $roasHealth * 0.25
            + $spendBalance * 0.20
        );

        return [
            'score' => min(100, max(0, $score)),
            'details' => [
                'revenue_trend' => $revenueTrend,
                'cac_efficiency' => $cacEfficiency,
                'roas_health' => $roasHealth,
                'spend_balance' => $spendBalance,
            ],
        ];
    }

    private function getRevenueTrend(string $businessId): int
    {
        $thisMonth = (float) DB::table('sales')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $lastMonth = (float) DB::table('sales')->where('business_id', $businessId)
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');

        if ($lastMonth == 0) return $thisMonth > 0 ? 100 : 50;
        return min(100, max(0, (int) round(($thisMonth / $lastMonth) * 100)));
    }

    private function getCacEfficiency(string $businessId): int
    {
        try {
            // CAC = marketing xarajat / yangi mijozlar soni (bazadan, to'g'ridan-to'g'ri)
            $spend = (float) DB::table('marketing_spends')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount');

            $newCustomers = DB::table('sales')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct('customer_id')
                ->count('customer_id');

            if ($newCustomers == 0) return 50;
            $cac = $spend / $newCustomers;

            // CAC past bo'lsa yaxshi
            if ($cac == 0) return 80;
            if ($cac < 50000) return 100;
            if ($cac < 100000) return 75;
            if ($cac < 200000) return 50;
            return 25;
        } catch (\Exception $e) {
            return 50;
        }
    }

    private function getRoasHealth(string $businessId): int
    {
        try {
            $revenue = (float) DB::table('sales')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount');

            $adSpend = (float) DB::table('marketing_spends')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount');

            if ($adSpend == 0) return $revenue > 0 ? 80 : 50;
            $roas = $revenue / $adSpend;

            if ($roas >= 5) return 100;
            if ($roas >= 3) return 80;
            if ($roas >= 2) return 60;
            if ($roas >= 1) return 40;
            return 20;
        } catch (\Exception $e) {
            return 50;
        }
    }

    private function getSpendBalance(string $businessId): int
    {
        $revenue = (float) DB::table('sales')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))->sum('amount');
        $expense = (float) DB::table('marketing_spends')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))->sum('amount');

        if ($expense == 0) return $revenue > 0 ? 100 : 50;
        $ratio = $revenue / $expense;
        return min(100, max(0, (int) round($ratio * 50))); // 2x ratio = 100
    }
}
