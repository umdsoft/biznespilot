<?php

namespace App\Services\Agent\HealthMonitor\Calculators;

use Illuminate\Support\Facades\DB;

/**
 * Mijoz sog'ligi kalkulyatori (bazadan, bepul).
 * Ball = repeat_rate(30%) + complaint_rate(20%) + review_sentiment(25%) + churn_risk(25%)
 */
class CustomerHealthCalculator
{
    public function calculate(string $businessId): array
    {
        $repeatRate = $this->getRepeatRate($businessId);
        $complaintRate = $this->getComplaintRate($businessId);
        $reviewSentiment = $this->getReviewSentiment($businessId);
        $churnRisk = $this->getChurnRisk($businessId);

        $score = (int) round(
            $repeatRate * 0.30
            + $complaintRate * 0.20
            + $reviewSentiment * 0.25
            + $churnRisk * 0.25
        );

        return [
            'score' => min(100, max(0, $score)),
            'details' => [
                'repeat_rate' => $repeatRate,
                'complaint_rate' => $complaintRate,
                'review_sentiment' => $reviewSentiment,
                'churn_risk' => $churnRisk,
            ],
        ];
    }

    private function getRepeatRate(string $businessId): int
    {
        $totalCustomers = DB::table('sales')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(90))
            ->distinct()->count('customer_id');

        $repeatCustomers = DB::table('sales')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(90))
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()->count();

        if ($totalCustomers == 0) return 50;
        return (int) round(($repeatCustomers / $totalCustomers) * 100);
    }

    private function getComplaintRate(string $businessId): int
    {
        // Kam shikoyat = yuqori ball
        return 80; // Default — shikoyat tizimi keyingi modulda (Reputation)
    }

    private function getReviewSentiment(string $businessId): int
    {
        // Izohlar tizimi keyingi modulda qo'shiladi
        return 70; // Default o'rta
    }

    private function getChurnRisk(string $businessId): int
    {
        $totalCustomers = DB::table('sales')->where('business_id', $businessId)
            ->distinct()->count('customer_id');

        if ($totalCustomers == 0) return 80;

        // Oxirgi 60 kunda xarid qilmagan mijozlar
        $activeCustomers = DB::table('sales')->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(60))
            ->distinct()->count('customer_id');

        $churning = $totalCustomers - $activeCustomers;
        $churnRate = ($churning / $totalCustomers) * 100;
        return max(0, 100 - (int) round($churnRate));
    }
}
