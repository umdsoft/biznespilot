<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessMaturityAssessment;

class MaturityCalculatorService
{
    /**
     * Calculate maturity score for a business
     */
    public function calculateScore(Business $business): array
    {
        $assessment = $business->maturityAssessment;

        if (! $assessment) {
            return [
                'score' => 0,
                'level' => 'beginner',
                'level_label' => 'Boshlang\'ich',
                'breakdown' => [],
            ];
        }

        // Calculate component scores
        $scores = [
            'infrastructure' => $this->calculateInfrastructureScore($assessment),
            'process' => $this->calculateProcessScore($assessment),
            'team' => $this->calculateTeamScore($assessment),
            'revenue' => $this->calculateRevenueScore($assessment),
            'marketing' => $this->calculateMarketingScore($assessment),
        ];

        // Weighted average
        $weights = [
            'infrastructure' => 15,
            'process' => 20,
            'team' => 15,
            'revenue' => 25,
            'marketing' => 25,
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($scores as $key => $score) {
            $totalScore += $score * $weights[$key];
            $totalWeight += $weights[$key];
        }

        $finalScore = $totalWeight > 0 ? round($totalScore / $totalWeight) : 0;
        $level = $this->determineLevel($finalScore);

        // Update assessment
        $assessment->update([
            'infrastructure_score' => $scores['infrastructure'],
            'process_score' => $scores['process'],
            'team_score' => $scores['team'],
            'revenue_score' => $scores['revenue'],
            'marketing_score' => $scores['marketing'],
            'overall_score' => $finalScore,
            'maturity_level' => $level['key'],
            'calculated_at' => now(),
        ]);

        // Update business
        $business->update([
            'maturity_score' => $finalScore,
            'maturity_level' => $level['key'],
        ]);

        return [
            'score' => $finalScore,
            'level' => $level['key'],
            'level_label' => $level['label'],
            'breakdown' => [
                'infrastructure' => [
                    'score' => $scores['infrastructure'],
                    'weight' => $weights['infrastructure'],
                    'label' => 'Infrastruktura',
                ],
                'process' => [
                    'score' => $scores['process'],
                    'weight' => $weights['process'],
                    'label' => 'Jarayonlar',
                ],
                'team' => [
                    'score' => $scores['team'],
                    'weight' => $weights['team'],
                    'label' => 'Jamoa',
                ],
                'revenue' => [
                    'score' => $scores['revenue'],
                    'weight' => $weights['revenue'],
                    'label' => 'Daromad',
                ],
                'marketing' => [
                    'score' => $scores['marketing'],
                    'weight' => $weights['marketing'],
                    'label' => 'Marketing',
                ],
            ],
        ];
    }

    /**
     * Calculate infrastructure score
     */
    private function calculateInfrastructureScore(BusinessMaturityAssessment $assessment): int
    {
        $score = 0;

        // Has website (20 points)
        if ($assessment->has_website) {
            $score += 20;
        }

        // Has CRM (25 points)
        if ($assessment->has_crm) {
            $score += 25;
        }

        // Uses analytics (20 points)
        if ($assessment->uses_analytics) {
            $score += 20;
        }

        // Has automation (20 points)
        if ($assessment->has_automation) {
            $score += 20;
        }

        // Current tools (15 points based on count)
        $tools = $assessment->current_tools ?? [];
        $toolScore = min(count($tools) * 3, 15);
        $score += $toolScore;

        return min($score, 100);
    }

    /**
     * Calculate process score
     */
    private function calculateProcessScore(BusinessMaturityAssessment $assessment): int
    {
        $score = 0;

        // Has documented processes (30 points)
        if ($assessment->has_documented_processes) {
            $score += 30;
        }

        // Has sales process (25 points)
        if ($assessment->has_sales_process) {
            $score += 25;
        }

        // Has support process (20 points)
        if ($assessment->has_support_process) {
            $score += 20;
        }

        // Has marketing process (25 points)
        if ($assessment->has_marketing_process) {
            $score += 25;
        }

        return min($score, 100);
    }

    /**
     * Calculate team score
     */
    private function calculateTeamScore(BusinessMaturityAssessment $assessment): int
    {
        $business = $assessment->business;
        $teamSize = $business->team_size ?? '1';

        // Team size scoring
        $teamSizeScores = [
            '1' => 10,
            '2-5' => 30,
            '6-10' => 50,
            '11-25' => 70,
            '26-50' => 85,
            '50+' => 100,
        ];

        $score = $teamSizeScores[$teamSize] ?? 20;

        // Has dedicated marketing person (bonus 20 points if applicable)
        if ($assessment->has_dedicated_marketing) {
            $score = min($score + 20, 100);
        }

        return $score;
    }

    /**
     * Calculate revenue score
     */
    private function calculateRevenueScore(BusinessMaturityAssessment $assessment): int
    {
        $revenueRange = $assessment->monthly_revenue_range ?? 'none';

        $revenueScores = [
            'none' => 0,
            'under_5m' => 15,
            '5m_20m' => 30,
            '20m_50m' => 45,
            '50m_100m' => 60,
            '100m_500m' => 75,
            '500m_1b' => 85,
            'over_1b' => 100,
        ];

        return $revenueScores[$revenueRange] ?? 0;
    }

    /**
     * Calculate marketing score
     */
    private function calculateMarketingScore(BusinessMaturityAssessment $assessment): int
    {
        $score = 0;
        $business = $assessment->business;

        // Active marketing channels (up to 40 points)
        $channels = $assessment->marketing_channels ?? [];
        $channelScore = min(count($channels) * 10, 40);
        $score += $channelScore;

        // Has connected integrations (30 points)
        $connectedIntegrations = $business->integrations()
            ->where('status', 'connected')
            ->count();
        if ($connectedIntegrations > 0) {
            $score += min($connectedIntegrations * 10, 30);
        }

        // Has marketing budget (15 points)
        if ($assessment->has_marketing_budget) {
            $score += 15;
        }

        // Tracks marketing metrics (15 points)
        if ($assessment->tracks_marketing_metrics) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Determine maturity level based on score
     */
    private function determineLevel(int $score): array
    {
        if ($score >= 80) {
            return ['key' => 'advanced', 'label' => 'Rivojlangan'];
        } elseif ($score >= 60) {
            return ['key' => 'growing', 'label' => 'O\'sish bosqichida'];
        } elseif ($score >= 40) {
            return ['key' => 'developing', 'label' => 'Rivojlanayotgan'];
        } elseif ($score >= 20) {
            return ['key' => 'early', 'label' => 'Boshlang\'ich'];
        } else {
            return ['key' => 'beginner', 'label' => 'Yangi'];
        }
    }

    /**
     * Get recommendations based on maturity level
     */
    public function getRecommendations(Business $business): array
    {
        $assessment = $business->maturityAssessment;

        if (! $assessment) {
            return [];
        }

        $recommendations = [];

        // Infrastructure recommendations
        if (! $assessment->has_website) {
            $recommendations[] = [
                'category' => 'infrastructure',
                'priority' => 'high',
                'title' => 'Web-sayt yarating',
                'description' => 'Online mavjudlik uchun web-sayt yaratish muhim.',
            ];
        }

        if (! $assessment->has_crm) {
            $recommendations[] = [
                'category' => 'infrastructure',
                'priority' => 'high',
                'title' => 'CRM tizimini joriy qiling',
                'description' => 'Mijozlar bilan ishlashni tartibga solish uchun CRM kerak.',
            ];
        }

        if (! $assessment->uses_analytics) {
            $recommendations[] = [
                'category' => 'infrastructure',
                'priority' => 'medium',
                'title' => 'Analitika o\'rnating',
                'description' => 'Biznes ko\'rsatkichlarini kuzatish uchun analitika zarur.',
            ];
        }

        // Process recommendations
        if (! $assessment->has_documented_processes) {
            $recommendations[] = [
                'category' => 'process',
                'priority' => 'medium',
                'title' => 'Jarayonlarni hujjatlashtiring',
                'description' => 'Biznes jarayonlarini yozma ravishda belgilang.',
            ];
        }

        if (! $assessment->has_sales_process) {
            $recommendations[] = [
                'category' => 'process',
                'priority' => 'high',
                'title' => 'Sotuv jarayonini yarating',
                'description' => 'Sotuvni standartlashtirish konversiyani oshiradi.',
            ];
        }

        // Marketing recommendations
        if (! $assessment->has_marketing_budget) {
            $recommendations[] = [
                'category' => 'marketing',
                'priority' => 'medium',
                'title' => 'Marketing byudjetini belgilang',
                'description' => 'Aniq byudjet bilan samarali marketing qilish mumkin.',
            ];
        }

        $channels = $assessment->marketing_channels ?? [];
        if (count($channels) < 2) {
            $recommendations[] = [
                'category' => 'marketing',
                'priority' => 'high',
                'title' => 'Ko\'proq marketing kanallaridan foydalaning',
                'description' => 'Kamida 2-3 ta kanal orqali auditoriyaga yeting.',
            ];
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

            return ($priorityOrder[$a['priority']] ?? 2) <=> ($priorityOrder[$b['priority']] ?? 2);
        });

        return $recommendations;
    }

    /**
     * Get maturity comparison with industry average
     */
    public function getIndustryComparison(Business $business): array
    {
        // In a real app, this would fetch industry averages from the database
        // For now, we'll use static benchmarks
        $industryAverages = [
            'e_commerce' => 55,
            'education' => 45,
            'services' => 50,
            'healthcare' => 60,
            'finance' => 65,
            'technology' => 70,
            'manufacturing' => 55,
            'retail' => 50,
            'hospitality' => 45,
            'real_estate' => 55,
        ];

        $industry = $business->industryRelation;
        $industryCode = $industry ? strtolower(str_replace(' ', '_', $industry->code ?? '')) : 'default';
        $industryAverage = $industryAverages[$industryCode] ?? 50;

        $businessScore = $business->maturity_score ?? 0;
        $difference = $businessScore - $industryAverage;

        return [
            'business_score' => $businessScore,
            'industry_average' => $industryAverage,
            'difference' => $difference,
            'percentile' => $this->calculatePercentile($businessScore, $industryAverage),
            'comparison_text' => $this->getComparisonText($difference),
        ];
    }

    /**
     * Calculate approximate percentile
     */
    private function calculatePercentile(int $score, int $average): int
    {
        // Simplified percentile calculation
        // Assumes normal distribution around the average
        $diff = $score - $average;

        if ($diff >= 30) {
            return 95;
        }
        if ($diff >= 20) {
            return 85;
        }
        if ($diff >= 10) {
            return 70;
        }
        if ($diff >= 0) {
            return 55;
        }
        if ($diff >= -10) {
            return 40;
        }
        if ($diff >= -20) {
            return 25;
        }

        return 10;
    }

    /**
     * Get comparison text
     */
    private function getComparisonText(int $difference): string
    {
        if ($difference >= 20) {
            return 'Siz soha o\'rtachasidan ancha yuqorisiz!';
        } elseif ($difference >= 10) {
            return 'Siz soha o\'rtachasidan yuqorisiz.';
        } elseif ($difference >= 0) {
            return 'Siz soha o\'rtachasi darajasida.';
        } elseif ($difference >= -10) {
            return 'Siz soha o\'rtachasiga yaqin.';
        } else {
            return 'Sizda o\'sish uchun katta imkoniyat bor!';
        }
    }
}
