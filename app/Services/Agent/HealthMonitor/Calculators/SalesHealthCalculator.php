<?php

namespace App\Services\Agent\HealthMonitor\Calculators;

use Illuminate\Support\Facades\DB;

/**
 * Sotuv sog'ligi kalkulyatori (bazadan, bepul).
 * Ball = lead_response(35%) + funnel_conversion(25%) + hot_leads(25%) + pipeline(15%)
 */
class SalesHealthCalculator
{
    public function calculate(string $businessId): array
    {
        $leadResponse = $this->getLeadResponseTime($businessId);
        $funnelConversion = $this->getFunnelConversion($businessId);
        $hotLeadsHandled = $this->getHotLeadsHandled($businessId);
        $pipelineHealth = $this->getPipelineHealth($businessId);

        $score = (int) round(
            $leadResponse * 0.35
            + $funnelConversion * 0.25
            + $hotLeadsHandled * 0.25
            + $pipelineHealth * 0.15
        );

        return [
            'score' => min(100, max(0, $score)),
            'details' => [
                'lead_response_time' => $leadResponse,
                'funnel_conversion' => $funnelConversion,
                'hot_leads_handled' => $hotLeadsHandled,
                'pipeline_health' => $pipelineHealth,
            ],
        ];
    }

    private function getLeadResponseTime(string $businessId): int
    {
        // O'rtacha javob vaqtini hisoblash — updated_at dan (contacted_at ustuni yo'q)
        // Yangi lead yaratilgandan status o'zgargunga qadar vaqt
        $avgHours = DB::table('leads')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', '!=', 'new')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->value('avg_hours');

        if ($avgHours === null) return 50;
        if ($avgHours < 2) return 100;
        if ($avgHours < 24) return 60;
        return 20;
    }

    private function getFunnelConversion(string $businessId): int
    {
        $total = DB::table('leads')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))->count();
        $won = DB::table('leads')->where('business_id', $businessId)
            ->where('status', 'won')->where('created_at', '>=', now()->subDays(30))->count();

        if ($total == 0) return 50;
        $rate = ($won / $total) * 100;
        return min(100, (int) round($rate * 5)); // 20% konversiya = 100 ball
    }

    private function getHotLeadsHandled(string $businessId): int
    {
        // Baholangan issiq leadlar orasida javob berilganlari (status != 'new')
        $hotTotal = DB::table('leads')->where('business_id', $businessId)
            ->where('score', '>=', 76)->where('created_at', '>=', now()->subDays(7))->count();
        $hotHandled = DB::table('leads')->where('business_id', $businessId)
            ->where('score', '>=', 76)->where('status', '!=', 'new')
            ->where('created_at', '>=', now()->subDays(7))->count();

        if ($hotTotal == 0) return 80;
        return (int) round(($hotHandled / $hotTotal) * 100);
    }

    private function getPipelineHealth(string $businessId): int
    {
        $stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation'];
        $filled = 0;
        foreach ($stages as $stage) {
            $count = DB::table('leads')->where('business_id', $businessId)
                ->where('status', $stage)->count();
            if ($count > 0) $filled++;
        }
        return (int) round(($filled / count($stages)) * 100);
    }
}
