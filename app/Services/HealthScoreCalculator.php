<?php

namespace App\Services;

use App\Models\Business;
use App\Models\MaturityAssessment;

class HealthScoreCalculator
{
    // Category weights for health score calculation
    protected array $categoryWeights = [
        'marketing' => 0.25,
        'sales' => 0.25,
        'content' => 0.20,
        'funnel' => 0.20,
        'analytics' => 0.10,
    ];

    protected BenchmarkService $benchmarkService;

    public function __construct(BenchmarkService $benchmarkService)
    {
        $this->benchmarkService = $benchmarkService;
    }

    /**
     * Calculate overall health score for a business
     */
    public function calculateHealthScore(
        Business $business,
        array $kpis,
        array $benchmarkComparison,
        ?MaturityAssessment $maturity = null
    ): array {
        // Calculate category scores
        $categoryScores = $this->calculateCategoryScores($kpis, $benchmarkComparison, $maturity);

        // Calculate weighted overall score
        $overallScore = $this->calculateWeightedScore($categoryScores);

        // Get health status
        $status = $this->getHealthStatus($overallScore);

        return [
            'overall_score' => $overallScore,
            'status' => $status['status'],
            'status_label' => $status['label'],
            'status_color' => $status['color'],
            'category_scores' => $categoryScores,
            'improvement_potential' => $this->calculateImprovementPotential($categoryScores),
            'weakest_category' => $this->findWeakestCategory($categoryScores),
            'strongest_category' => $this->findStrongestCategory($categoryScores),
        ];
    }

    /**
     * Calculate scores for each category
     */
    protected function calculateCategoryScores(
        array $kpis,
        array $benchmarkComparison,
        ?MaturityAssessment $maturity
    ): array {
        $scores = [
            'marketing' => $this->calculateMarketingScore($kpis, $benchmarkComparison, $maturity),
            'sales' => $this->calculateSalesScore($kpis, $benchmarkComparison, $maturity),
            'content' => $this->calculateContentScore($kpis, $benchmarkComparison, $maturity),
            'funnel' => $this->calculateFunnelScore($kpis, $benchmarkComparison, $maturity),
            'analytics' => $this->calculateAnalyticsScore($kpis, $maturity),
        ];

        return array_map(function ($score, $category) {
            return [
                'score' => $score,
                'weight' => $this->categoryWeights[$category],
                'weighted_score' => $score * $this->categoryWeights[$category],
                'status' => $this->getCategoryStatus($score),
            ];
        }, $scores, array_keys($scores));
    }

    /**
     * Calculate marketing category score
     */
    protected function calculateMarketingScore(
        array $kpis,
        array $benchmarkComparison,
        ?MaturityAssessment $maturity
    ): int {
        $scores = [];

        // From benchmark comparison
        $marketingMetrics = ['engagement_rate', 'follower_growth_rate', 'ctr', 'cpc', 'cpl'];
        foreach ($marketingMetrics as $metric) {
            if (isset($benchmarkComparison[$metric])) {
                $scores[] = $this->statusToScore($benchmarkComparison[$metric]['status']);
            }
        }

        // From maturity assessment
        if ($maturity && $maturity->marketing_score) {
            $scores[] = $maturity->marketing_score;
        }

        return $this->calculateAverageScore($scores);
    }

    /**
     * Calculate sales category score
     */
    protected function calculateSalesScore(
        array $kpis,
        array $benchmarkComparison,
        ?MaturityAssessment $maturity
    ): int {
        $scores = [];

        // From benchmark comparison
        $salesMetrics = ['conversion_rate', 'cac', 'ltv_cac_ratio', 'sales_cycle_days', 'repeat_purchase_rate'];
        foreach ($salesMetrics as $metric) {
            if (isset($benchmarkComparison[$metric])) {
                $scores[] = $this->statusToScore($benchmarkComparison[$metric]['status']);
            }
        }

        // From maturity assessment
        if ($maturity && $maturity->sales_score) {
            $scores[] = $maturity->sales_score;
        }

        return $this->calculateAverageScore($scores);
    }

    /**
     * Calculate content category score
     */
    protected function calculateContentScore(
        array $kpis,
        array $benchmarkComparison,
        ?MaturityAssessment $maturity
    ): int {
        $scores = [];

        // From benchmark comparison
        $contentMetrics = ['content_frequency', 'engagement_rate', 'avg_response_time'];
        foreach ($contentMetrics as $metric) {
            if (isset($benchmarkComparison[$metric])) {
                $scores[] = $this->statusToScore($benchmarkComparison[$metric]['status']);
            }
        }

        // From maturity assessment
        if ($maturity && $maturity->content_score) {
            $scores[] = $maturity->content_score;
        }

        return $this->calculateAverageScore($scores);
    }

    /**
     * Calculate funnel category score
     */
    protected function calculateFunnelScore(
        array $kpis,
        array $benchmarkComparison,
        ?MaturityAssessment $maturity
    ): int {
        $scores = [];

        // From benchmark comparison
        $funnelMetrics = ['funnel_conversion', 'conversion_rate', 'roas'];
        foreach ($funnelMetrics as $metric) {
            if (isset($benchmarkComparison[$metric])) {
                $scores[] = $this->statusToScore($benchmarkComparison[$metric]['status']);
            }
        }

        // From maturity assessment
        if ($maturity && $maturity->funnel_score) {
            $scores[] = $maturity->funnel_score;
        }

        return $this->calculateAverageScore($scores);
    }

    /**
     * Calculate analytics category score
     */
    protected function calculateAnalyticsScore(array $kpis, ?MaturityAssessment $maturity): int
    {
        $scores = [];

        // From maturity assessment
        if ($maturity && $maturity->analytics_score) {
            $scores[] = $maturity->analytics_score;
        }

        // Check if business has analytics integration
        // This would come from integration data
        // For now, use maturity score or default

        return $this->calculateAverageScore($scores, 50);
    }

    /**
     * Calculate weighted overall score
     */
    protected function calculateWeightedScore(array $categoryScores): int
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($categoryScores as $category => $data) {
            $totalWeightedScore += $data['weighted_score'];
            $totalWeight += $data['weight'];
        }

        return $totalWeight > 0 ? (int) round($totalWeightedScore / $totalWeight) : 50;
    }

    /**
     * Convert status to numeric score
     */
    protected function statusToScore(string $status): int
    {
        return match ($status) {
            'excellent' => 100,
            'good' => 75,
            'average' => 50,
            'poor' => 25,
            default => 50,
        };
    }

    /**
     * Calculate average score from array
     */
    protected function calculateAverageScore(array $scores, int $default = 50): int
    {
        if (empty($scores)) {
            return $default;
        }

        return (int) round(array_sum($scores) / count($scores));
    }

    /**
     * Get health status based on score
     */
    protected function getHealthStatus(int $score): array
    {
        if ($score >= 80) {
            return [
                'status' => 'excellent',
                'label' => 'Ajoyib',
                'color' => 'blue',
            ];
        }

        if ($score >= 60) {
            return [
                'status' => 'good',
                'label' => 'Yaxshi',
                'color' => 'green',
            ];
        }

        if ($score >= 40) {
            return [
                'status' => 'average',
                'label' => 'O\'rtacha',
                'color' => 'yellow',
            ];
        }

        return [
            'status' => 'poor',
            'label' => 'Zaif',
            'color' => 'red',
        ];
    }

    /**
     * Get category status
     */
    protected function getCategoryStatus(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        return 'poor';
    }

    /**
     * Calculate improvement potential
     */
    protected function calculateImprovementPotential(array $categoryScores): int
    {
        $potentials = [];

        foreach ($categoryScores as $category => $data) {
            $potential = 100 - $data['score'];
            $potentials[] = $potential * $data['weight'];
        }

        return (int) round(array_sum($potentials));
    }

    /**
     * Find weakest category
     */
    protected function findWeakestCategory(array $categoryScores): array
    {
        $weakest = null;
        $lowestScore = 101;

        foreach ($categoryScores as $category => $data) {
            if ($data['score'] < $lowestScore) {
                $lowestScore = $data['score'];
                $weakest = $category;
            }
        }

        return [
            'category' => $weakest,
            'score' => $lowestScore,
            'label' => $this->getCategoryLabel($weakest),
        ];
    }

    /**
     * Find strongest category
     */
    protected function findStrongestCategory(array $categoryScores): array
    {
        $strongest = null;
        $highestScore = -1;

        foreach ($categoryScores as $category => $data) {
            if ($data['score'] > $highestScore) {
                $highestScore = $data['score'];
                $strongest = $category;
            }
        }

        return [
            'category' => $strongest,
            'score' => $highestScore,
            'label' => $this->getCategoryLabel($strongest),
        ];
    }

    /**
     * Get category label in Uzbek
     */
    protected function getCategoryLabel(?string $category): string
    {
        return match ($category) {
            'marketing' => 'Marketing',
            'sales' => 'Sotuvlar',
            'content' => 'Kontent',
            'funnel' => 'Funnel',
            'analytics' => 'Analitika',
            default => 'Noma\'lum',
        };
    }

    /**
     * Get detailed health breakdown
     */
    public function getDetailedBreakdown(array $healthScore): array
    {
        $breakdown = [];

        foreach ($healthScore['category_scores'] as $category => $data) {
            $breakdown[] = [
                'category' => $category,
                'label' => $this->getCategoryLabel($category),
                'score' => $data['score'],
                'weight' => round($data['weight'] * 100) . '%',
                'weighted_score' => round($data['weighted_score'], 1),
                'status' => $data['status'],
                'status_label' => $this->getHealthStatus($data['score'])['label'],
                'color' => $this->getHealthStatus($data['score'])['color'],
                'improvement_needed' => 100 - $data['score'],
            ];
        }

        // Sort by score ascending (worst first)
        usort($breakdown, fn($a, $b) => $a['score'] <=> $b['score']);

        return $breakdown;
    }

    /**
     * Calculate trend compared to previous diagnostic
     */
    public function calculateTrend(int $currentScore, ?int $previousScore): array
    {
        if ($previousScore === null) {
            return [
                'trend' => 'new',
                'change' => 0,
                'change_percent' => 0,
                'label' => 'Birinchi diagnostika',
                'icon' => 'minus',
            ];
        }

        $change = $currentScore - $previousScore;
        $changePercent = $previousScore > 0 ? round(($change / $previousScore) * 100, 1) : 0;

        if ($change > 5) {
            return [
                'trend' => 'up',
                'change' => $change,
                'change_percent' => $changePercent,
                'label' => "+{$change} ball o'sish",
                'icon' => 'arrow-up',
            ];
        }

        if ($change < -5) {
            return [
                'trend' => 'down',
                'change' => $change,
                'change_percent' => $changePercent,
                'label' => abs($change) . " ball pasayish",
                'icon' => 'arrow-down',
            ];
        }

        return [
            'trend' => 'stable',
            'change' => $change,
            'change_percent' => $changePercent,
            'label' => 'Barqaror',
            'icon' => 'minus',
        ];
    }
}
