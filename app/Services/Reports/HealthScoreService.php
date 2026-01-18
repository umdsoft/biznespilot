<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\IndustryBenchmark;

/**
 * HealthScoreService
 *
 * Calculates business health score using weighted metrics
 * Pure algorithmic approach without AI
 */
class HealthScoreService
{
    // Weight configuration for each metric category
    protected array $weights = [
        'sales_performance' => 0.25,      // 25%
        'marketing_efficiency' => 0.20,   // 20%
        'financial_health' => 0.25,       // 25%
        'customer_metrics' => 0.15,       // 15%
        'kpi_achievement' => 0.15,        // 15%
    ];

    protected array $benchmarks = [];

    /**
     * Calculate health score
     */
    public function calculate(Business $business, array $metrics, array $trends): array
    {
        $this->loadBenchmarks($business);

        $scores = [
            'sales_performance' => $this->calculateSalesScore($metrics),
            'marketing_efficiency' => $this->calculateMarketingScore($metrics),
            'financial_health' => $this->calculateFinancialScore($metrics),
            'customer_metrics' => $this->calculateCustomerScore($metrics),
            'kpi_achievement' => $this->calculateKpiScore($metrics),
        ];

        // Apply trend bonus/penalty
        $trendModifier = $this->calculateTrendModifier($trends);

        // Calculate weighted total
        $totalScore = 0;
        foreach ($scores as $category => $score) {
            $totalScore += $score * $this->weights[$category];
        }

        // Apply trend modifier (-10 to +10 points)
        $totalScore += $trendModifier;

        // Clamp to 0-100
        $totalScore = max(0, min(100, round($totalScore)));

        return [
            'score' => $totalScore,
            'label' => $this->getScoreLabel($totalScore),
            'color' => $this->getScoreColor($totalScore),
            'breakdown' => $this->formatBreakdown($scores),
            'trend_modifier' => $trendModifier,
        ];
    }

    /**
     * Load industry benchmarks
     */
    protected function loadBenchmarks(Business $business): void
    {
        try {
            $benchmark = IndustryBenchmark::where('industry', 'LIKE', "%{$business->industry}%")->first()
                ?? IndustryBenchmark::where('industry', 'default')->first();

            if ($benchmark) {
                $this->benchmarks = $benchmark->toAlgorithmArray();
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to load benchmarks: '.$e->getMessage());
        }

        // Set defaults
        $this->benchmarks = array_merge([
            'conversion_rate' => 20,
            'repeat_purchase_rate' => 25,
            'churn_rate' => 5,
            'cac_ltv_ratio' => 3,
        ], $this->benchmarks);
    }

    /**
     * Calculate sales performance score (0-100)
     */
    protected function calculateSalesScore(array $metrics): float
    {
        $sales = $metrics['sales'] ?? [];
        $scores = [];

        // Daily sales consistency (up to 30 points)
        $dailyAvg = $sales['daily_avg_sales'] ?? 0;
        if ($dailyAvg >= 5) {
            $scores[] = 30;
        } elseif ($dailyAvg >= 3) {
            $scores[] = 20;
        } elseif ($dailyAvg >= 1) {
            $scores[] = 10;
        } else {
            $scores[] = 0;
        }

        // Repeat rate (up to 35 points)
        $repeatRate = $sales['repeat_rate'] ?? 0;
        $repeatBenchmark = $this->benchmarks['repeat_purchase_rate'] ?? 25;
        if ($repeatRate >= $repeatBenchmark * 1.2) {
            $scores[] = 35;
        } elseif ($repeatRate >= $repeatBenchmark) {
            $scores[] = 25;
        } elseif ($repeatRate >= $repeatBenchmark * 0.5) {
            $scores[] = 15;
        } else {
            $scores[] = 5;
        }

        // Revenue generated (up to 35 points)
        $revenue = $sales['total_revenue'] ?? 0;
        if ($revenue >= 50000000) { // 50M+
            $scores[] = 35;
        } elseif ($revenue >= 20000000) { // 20M+
            $scores[] = 25;
        } elseif ($revenue >= 5000000) { // 5M+
            $scores[] = 15;
        } elseif ($revenue > 0) {
            $scores[] = 5;
        } else {
            $scores[] = 0;
        }

        return array_sum($scores);
    }

    /**
     * Calculate marketing efficiency score (0-100)
     */
    protected function calculateMarketingScore(array $metrics): float
    {
        $marketing = $metrics['marketing'] ?? [];
        $scores = [];

        // Conversion rate (up to 40 points)
        $conversion = $marketing['conversion_rate'] ?? 0;
        $conversionBenchmark = $this->benchmarks['conversion_rate'] ?? 20;

        if ($conversion >= $conversionBenchmark * 1.5) {
            $scores[] = 40;
        } elseif ($conversion >= $conversionBenchmark) {
            $scores[] = 30;
        } elseif ($conversion >= $conversionBenchmark * 0.5) {
            $scores[] = 20;
        } elseif ($conversion > 0) {
            $scores[] = 10;
        } else {
            $scores[] = 0;
        }

        // Lead cost efficiency (up to 30 points)
        $leadCost = $marketing['lead_cost'] ?? 0;
        if ($leadCost > 0 && $leadCost < 15000) {
            $scores[] = 30;
        } elseif ($leadCost <= 25000) {
            $scores[] = 20;
        } elseif ($leadCost <= 50000) {
            $scores[] = 10;
        } else {
            $scores[] = 5;
        }

        // Lead volume (up to 30 points)
        $leads = $marketing['total_leads'] ?? 0;
        if ($leads >= 100) {
            $scores[] = 30;
        } elseif ($leads >= 50) {
            $scores[] = 20;
        } elseif ($leads >= 20) {
            $scores[] = 10;
        } elseif ($leads > 0) {
            $scores[] = 5;
        } else {
            $scores[] = 0;
        }

        return array_sum($scores);
    }

    /**
     * Calculate financial health score (0-100)
     */
    protected function calculateFinancialScore(array $metrics): float
    {
        $financial = $metrics['financial'] ?? [];
        $scores = [];

        // ROI (up to 40 points)
        $roi = $financial['roi'] ?? 0;
        if ($roi >= 300) {
            $scores[] = 40;
        } elseif ($roi >= 200) {
            $scores[] = 35;
        } elseif ($roi >= 100) {
            $scores[] = 25;
        } elseif ($roi >= 50) {
            $scores[] = 15;
        } elseif ($roi > 0) {
            $scores[] = 5;
        } else {
            $scores[] = 0;
        }

        // LTV/CAC ratio (up to 35 points)
        $ltvCac = $financial['ltv_cac_ratio'] ?? 0;
        $ltvCacBenchmark = $this->benchmarks['cac_ltv_ratio'] ?? 3;

        if ($ltvCac >= $ltvCacBenchmark * 2) {
            $scores[] = 35;
        } elseif ($ltvCac >= $ltvCacBenchmark) {
            $scores[] = 25;
        } elseif ($ltvCac >= $ltvCacBenchmark * 0.5) {
            $scores[] = 15;
        } elseif ($ltvCac > 0) {
            $scores[] = 5;
        } else {
            $scores[] = 0;
        }

        // ROAS (up to 25 points)
        $roas = $financial['roas'] ?? 0;
        if ($roas >= 5) {
            $scores[] = 25;
        } elseif ($roas >= 3) {
            $scores[] = 20;
        } elseif ($roas >= 2) {
            $scores[] = 10;
        } elseif ($roas > 0) {
            $scores[] = 5;
        } else {
            $scores[] = 0;
        }

        return array_sum($scores);
    }

    /**
     * Calculate customer metrics score (0-100)
     */
    protected function calculateCustomerScore(array $metrics): float
    {
        $customer = $metrics['customer'] ?? [];
        $scores = [];

        // Customer growth (up to 40 points)
        $newCustomers = $customer['new_customers'] ?? 0;
        if ($newCustomers >= 50) {
            $scores[] = 40;
        } elseif ($newCustomers >= 20) {
            $scores[] = 30;
        } elseif ($newCustomers >= 10) {
            $scores[] = 20;
        } elseif ($newCustomers > 0) {
            $scores[] = 10;
        } else {
            $scores[] = 0;
        }

        // Retention rate (up to 35 points)
        $retention = $customer['retention_rate'] ?? 0;
        if ($retention >= 80) {
            $scores[] = 35;
        } elseif ($retention >= 60) {
            $scores[] = 25;
        } elseif ($retention >= 40) {
            $scores[] = 15;
        } elseif ($retention > 0) {
            $scores[] = 5;
        } else {
            $scores[] = 0;
        }

        // Churn rate (up to 25 points - lower is better)
        $churn = $customer['churn_rate'] ?? 0;
        $churnBenchmark = $this->benchmarks['churn_rate'] ?? 5;

        if ($churn <= $churnBenchmark * 0.5) {
            $scores[] = 25;
        } elseif ($churn <= $churnBenchmark) {
            $scores[] = 20;
        } elseif ($churn <= $churnBenchmark * 2) {
            $scores[] = 10;
        } else {
            $scores[] = 0;
        }

        return array_sum($scores);
    }

    /**
     * Calculate KPI achievement score (0-100)
     */
    protected function calculateKpiScore(array $metrics): float
    {
        $kpi = $metrics['kpi_progress'] ?? [];

        if (! ($kpi['has_plan'] ?? false)) {
            return 50; // Neutral score if no plan
        }

        $scores = [];

        // Sales achievement (up to 35 points)
        if (isset($kpi['sales']['progress'])) {
            $progress = $kpi['sales']['progress'];
            $expected = $kpi['expected_progress'] ?? 100;
            $ratio = $expected > 0 ? $progress / $expected : 1;

            if ($ratio >= 1.1) {
                $scores[] = 35;
            } elseif ($ratio >= 0.9) {
                $scores[] = 25;
            } elseif ($ratio >= 0.7) {
                $scores[] = 15;
            } elseif ($ratio >= 0.5) {
                $scores[] = 5;
            } else {
                $scores[] = 0;
            }
        }

        // Revenue achievement (up to 35 points)
        if (isset($kpi['revenue']['progress'])) {
            $progress = $kpi['revenue']['progress'];
            $expected = $kpi['expected_progress'] ?? 100;
            $ratio = $expected > 0 ? $progress / $expected : 1;

            if ($ratio >= 1.1) {
                $scores[] = 35;
            } elseif ($ratio >= 0.9) {
                $scores[] = 25;
            } elseif ($ratio >= 0.7) {
                $scores[] = 15;
            } elseif ($ratio >= 0.5) {
                $scores[] = 5;
            } else {
                $scores[] = 0;
            }
        }

        // Leads achievement (up to 30 points)
        if (isset($kpi['leads']['progress'])) {
            $progress = $kpi['leads']['progress'];
            $expected = $kpi['expected_progress'] ?? 100;
            $ratio = $expected > 0 ? $progress / $expected : 1;

            if ($ratio >= 1.1) {
                $scores[] = 30;
            } elseif ($ratio >= 0.9) {
                $scores[] = 20;
            } elseif ($ratio >= 0.7) {
                $scores[] = 10;
            } else {
                $scores[] = 0;
            }
        }

        return empty($scores) ? 50 : array_sum($scores);
    }

    /**
     * Calculate trend modifier (-10 to +10)
     */
    protected function calculateTrendModifier(array $trends): float
    {
        if (! ($trends['has_data'] ?? false)) {
            return 0;
        }

        $modifier = 0;

        // Sales trend impact
        if (isset($trends['sales_trend']['change_percent'])) {
            $change = $trends['sales_trend']['change_percent'];
            if ($change > 20) {
                $modifier += 3;
            } elseif ($change > 10) {
                $modifier += 2;
            } elseif ($change < -20) {
                $modifier -= 3;
            } elseif ($change < -10) {
                $modifier -= 2;
            }
        }

        // Revenue trend impact
        if (isset($trends['revenue_trend']['change_percent'])) {
            $change = $trends['revenue_trend']['change_percent'];
            if ($change > 20) {
                $modifier += 3;
            } elseif ($change > 10) {
                $modifier += 2;
            } elseif ($change < -20) {
                $modifier -= 3;
            } elseif ($change < -10) {
                $modifier -= 2;
            }
        }

        // Anomalies penalty
        $anomalies = $trends['anomalies'] ?? [];
        $negativeAnomalies = count(array_filter($anomalies, fn ($a) => $a['type'] === 'drop'));
        $modifier -= min(4, $negativeAnomalies);

        return max(-10, min(10, $modifier));
    }

    /**
     * Get score label
     */
    protected function getScoreLabel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Ajoyib',
            $score >= 60 => 'Yaxshi',
            $score >= 40 => 'O\'rtacha',
            $score >= 20 => 'Zaif',
            default => 'Kritik',
        };
    }

    /**
     * Get score color
     */
    protected function getScoreColor(float $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'average',
            default => 'poor',
        };
    }

    /**
     * Format breakdown for display
     */
    protected function formatBreakdown(array $scores): array
    {
        $labels = [
            'sales_performance' => 'Sotuvlar samaradorligi',
            'marketing_efficiency' => 'Marketing samaradorligi',
            'financial_health' => 'Moliyaviy salomatlik',
            'customer_metrics' => 'Mijoz ko\'rsatkichlari',
            'kpi_achievement' => 'KPI bajarilishi',
        ];

        $breakdown = [];
        foreach ($scores as $category => $score) {
            $breakdown[] = [
                'category' => $category,
                'label' => $labels[$category] ?? $category,
                'score' => round($score),
                'weight' => $this->weights[$category] * 100,
                'weighted_score' => round($score * $this->weights[$category]),
            ];
        }

        return $breakdown;
    }
}
