<?php

namespace App\Services\Reports;

use App\Models\Business;

class HealthScoreService
{
    public function calculate(Business $business, array $metrics, array $trends): array
    {
        $scores = [];
        $totalWeight = 0;
        $weightedSum = 0;

        // Sales health (weight: 30)
        $salesScore = $this->calculateSalesHealth($metrics['sales'] ?? []);
        $scores['sales'] = [
            'score' => $salesScore,
            'weight' => 30,
            'label' => 'Sotuv salomatligi',
        ];
        $weightedSum += $salesScore * 30;
        $totalWeight += 30;

        // Lead health (weight: 25)
        $leadScore = $this->calculateLeadHealth($metrics['leads'] ?? []);
        $scores['leads'] = [
            'score' => $leadScore,
            'weight' => 25,
            'label' => 'Lead salomatligi',
        ];
        $weightedSum += $leadScore * 25;
        $totalWeight += 25;

        // Marketing health (weight: 20)
        $marketingScore = $this->calculateMarketingHealth($metrics['marketing'] ?? []);
        $scores['marketing'] = [
            'score' => $marketingScore,
            'weight' => 20,
            'label' => 'Marketing salomatligi',
        ];
        $weightedSum += $marketingScore * 20;
        $totalWeight += 20;

        // Trend health (weight: 25)
        $trendScore = $this->calculateTrendHealth($trends['period_comparison'] ?? []);
        $scores['trend'] = [
            'score' => $trendScore,
            'weight' => 25,
            'label' => 'O\'sish tendensiyasi',
        ];
        $weightedSum += $trendScore * 25;
        $totalWeight += 25;

        $overallScore = $totalWeight > 0 ? round($weightedSum / $totalWeight) : 0;

        return [
            'overall_score' => $overallScore,
            'label' => $this->getScoreLabel($overallScore),
            'color' => $this->getScoreColor($overallScore),
            'breakdown' => $scores,
        ];
    }

    protected function calculateSalesHealth(array $sales): int
    {
        if (empty($sales) || ($sales['total_count'] ?? 0) === 0) {
            return 30;
        }

        $score = 50; // Base score

        // Bonus for revenue
        if (($sales['total_revenue'] ?? 0) > 0) {
            $score += 25;
        }

        // Bonus for profit
        if (($sales['total_profit'] ?? 0) > 0) {
            $score += 25;
        }

        return min(100, $score);
    }

    protected function calculateLeadHealth(array $leads): int
    {
        if (empty($leads) || ($leads['total_leads'] ?? 0) === 0) {
            return 30;
        }

        $conversionRate = $leads['conversion_rate'] ?? 0;

        if ($conversionRate >= 20) return 100;
        if ($conversionRate >= 10) return 80;
        if ($conversionRate >= 5) return 60;
        if ($conversionRate > 0) return 40;

        return 30;
    }

    protected function calculateMarketingHealth(array $marketing): int
    {
        $channels = $marketing['active_channels'] ?? 0;

        if ($channels >= 5) return 100;
        if ($channels >= 3) return 80;
        if ($channels >= 1) return 60;

        return 30;
    }

    protected function calculateTrendHealth(array $comparison): int
    {
        if (empty($comparison)) {
            return 50;
        }

        $changePercent = $comparison['change_percent'] ?? 0;

        if ($changePercent >= 20) return 100;
        if ($changePercent >= 10) return 85;
        if ($changePercent >= 0) return 70;
        if ($changePercent >= -10) return 50;
        if ($changePercent >= -20) return 35;

        return 20;
    }

    protected function getScoreLabel(int $score): string
    {
        if ($score >= 80) return 'A\'lo';
        if ($score >= 60) return 'Yaxshi';
        if ($score >= 40) return 'O\'rtacha';
        if ($score >= 20) return 'Yomon';

        return 'Kritik';
    }

    protected function getScoreColor(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        if ($score >= 20) return 'poor';

        return 'poor';
    }
}
