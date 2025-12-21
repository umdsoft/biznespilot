<?php

namespace App\Services;

use App\Models\Business;
use App\Models\IndustryBenchmark;
use Illuminate\Support\Collection;

class BenchmarkService
{
    /**
     * Get benchmarks for a business's industry
     */
    public function getBenchmarksForBusiness(Business $business): Collection
    {
        $industryId = $business->industry_id;

        if (!$industryId) {
            // Return empty collection if no industry set
            return collect();
        }

        return IndustryBenchmark::where('industry_id', $industryId)
            ->active()
            ->valid()
            ->get()
            ->keyBy('metric_code');
    }

    /**
     * Get benchmarks for a specific industry
     */
    public function getBenchmarksForIndustry(int $industryId): Collection
    {
        return IndustryBenchmark::where('industry_id', $industryId)
            ->active()
            ->valid()
            ->get()
            ->keyBy('metric_code');
    }

    /**
     * Compare KPIs with benchmarks
     */
    public function compareWithBenchmarks(array $kpis, Collection $benchmarks): array
    {
        $comparison = [];

        foreach ($kpis as $metricCode => $value) {
            if ($value === null) {
                continue;
            }

            $benchmark = $benchmarks->get($metricCode);

            if (!$benchmark) {
                continue;
            }

            $comparison[$metricCode] = [
                'metric_code' => $metricCode,
                'metric_name' => $benchmark->getMetricName(),
                'value' => $value,
                'formatted_value' => $benchmark->formatValue($value),
                'benchmark_average' => $benchmark->average_value,
                'formatted_benchmark' => $benchmark->formatValue($benchmark->average_value),
                'status' => $benchmark->getStatus($value),
                'status_color' => $benchmark->getStatusColor($value),
                'status_label' => $benchmark->getStatusLabel($value),
                'gap' => $benchmark->calculateGap($value),
                'gap_percent' => $benchmark->calculateGapPercent($value),
                'direction' => $benchmark->direction,
                'unit' => $benchmark->unit,
                'thresholds' => [
                    'poor' => $benchmark->poor_threshold,
                    'average' => $benchmark->average_value,
                    'good' => $benchmark->good_threshold,
                    'excellent' => $benchmark->excellent_threshold,
                ],
            ];
        }

        return $comparison;
    }

    /**
     * Identify strengths from benchmark comparison
     */
    public function identifyStrengths(array $comparison): array
    {
        $strengths = [];

        foreach ($comparison as $metric) {
            if (in_array($metric['status'], ['good', 'excellent'])) {
                $strengths[] = [
                    'metric' => $metric['metric_name'],
                    'value' => $metric['formatted_value'],
                    'status' => $metric['status_label'],
                    'gap_percent' => $metric['gap_percent'],
                    'description' => $this->generateStrengthDescription($metric),
                ];
            }
        }

        // Sort by gap percent (highest first)
        usort($strengths, fn($a, $b) => $b['gap_percent'] <=> $a['gap_percent']);

        return $strengths;
    }

    /**
     * Identify weaknesses from benchmark comparison
     */
    public function identifyWeaknesses(array $comparison): array
    {
        $weaknesses = [];

        foreach ($comparison as $metric) {
            if (in_array($metric['status'], ['poor', 'average'])) {
                $weaknesses[] = [
                    'metric' => $metric['metric_name'],
                    'value' => $metric['formatted_value'],
                    'status' => $metric['status_label'],
                    'gap_percent' => $metric['gap_percent'],
                    'description' => $this->generateWeaknessDescription($metric),
                    'recommendation' => $this->generateRecommendation($metric),
                ];
            }
        }

        // Sort by gap percent (lowest first - worst metrics)
        usort($weaknesses, fn($a, $b) => $a['gap_percent'] <=> $b['gap_percent']);

        return $weaknesses;
    }

    /**
     * Calculate overall score based on benchmark comparison
     */
    public function calculateScoreFromComparison(array $comparison): int
    {
        if (empty($comparison)) {
            return 50; // Default score
        }

        $totalScore = 0;
        $count = 0;

        foreach ($comparison as $metric) {
            $score = match ($metric['status']) {
                'excellent' => 100,
                'good' => 75,
                'average' => 50,
                'poor' => 25,
                default => 50,
            };

            $totalScore += $score;
            $count++;
        }

        return $count > 0 ? (int) round($totalScore / $count) : 50;
    }

    /**
     * Generate strength description
     */
    private function generateStrengthDescription(array $metric): string
    {
        $gapPercent = abs($metric['gap_percent']);

        if ($metric['status'] === 'excellent') {
            return "{$metric['metric_name']} ko'rsatkichi ajoyib - soha o'rtachasidan {$gapPercent}% yuqori.";
        }

        return "{$metric['metric_name']} ko'rsatkichi yaxshi - soha standartlaridan yuqori.";
    }

    /**
     * Generate weakness description
     */
    private function generateWeaknessDescription(array $metric): string
    {
        $gapPercent = abs($metric['gap_percent']);

        if ($metric['status'] === 'poor') {
            return "{$metric['metric_name']} ko'rsatkichi zaif - soha o'rtachasidan {$gapPercent}% past.";
        }

        return "{$metric['metric_name']} ko'rsatkichi o'rtacha - yaxshilash imkoniyati mavjud.";
    }

    /**
     * Generate recommendation based on metric
     */
    private function generateRecommendation(array $metric): string
    {
        $recommendations = [
            'engagement_rate' => 'Kontent strategiyangizni ko\'rib chiqing. Ko\'proq interactive postlar yarating.',
            'follower_growth_rate' => 'Targetlangan reklamalar va kolaboratsiyalar orqali o\'sishni tezlashtiring.',
            'cpl' => 'Lead magnet va landing pagelarni optimizatsiya qiling.',
            'cac' => 'Marketing kanallarini tahlil qilib, eng samarali kanallarga fokuslanish.',
            'cpc' => 'Reklama targetingini yaxshilang va kreativlarni A/B test qiling.',
            'conversion_rate' => 'Sotuv jarayonini optimizatsiya qiling, follow-up tizimini yaxshilang.',
            'ctr' => 'Reklama matnlari va rasmlarini yangilang, CTAlarni kuchayiring.',
            'roas' => 'Eng yuqori konversiya beruvchi mahsulotlarga fokuslanish.',
            'ltv_cac_ratio' => 'Mijoz saqlab qolish va repeat purchase strategiyalarini kuchaytiring.',
            'churn_rate' => 'Mijoz mamnuniyatini oshiring, loyalty dasturi yarating.',
            'repeat_purchase_rate' => 'Email marketing va personalizatsiya orqali qayta sotuvlarni oshiring.',
            'avg_response_time' => 'Chatbot yoki avtomatik javob tizimini joriy qiling.',
            'content_frequency' => 'Kontent kalendar yarating va post chastotasini oshiring.',
            'funnel_conversion' => 'Har bir funnel bosqichini alohida tahlil qilib, bottlenecklarni aniqlang.',
            'sales_cycle_days' => 'Sotuv jarayonini soddalshtiring, avtomatlashtiring.',
        ];

        return $recommendations[$metric['metric_code']]
            ?? "{$metric['metric_name']} ko'rsatkichini yaxshilash uchun strategiya ishlab chiqing.";
    }

    /**
     * Get benchmark summary
     */
    public function getBenchmarkSummary(array $comparison): array
    {
        $excellent = 0;
        $good = 0;
        $average = 0;
        $poor = 0;

        foreach ($comparison as $metric) {
            match ($metric['status']) {
                'excellent' => $excellent++,
                'good' => $good++,
                'average' => $average++,
                'poor' => $poor++,
                default => null,
            };
        }

        $total = count($comparison);

        return [
            'total_metrics' => $total,
            'excellent' => $excellent,
            'good' => $good,
            'average' => $average,
            'poor' => $poor,
            'excellent_percent' => $total > 0 ? round(($excellent / $total) * 100) : 0,
            'good_percent' => $total > 0 ? round(($good / $total) * 100) : 0,
            'average_percent' => $total > 0 ? round(($average / $total) * 100) : 0,
            'poor_percent' => $total > 0 ? round(($poor / $total) * 100) : 0,
        ];
    }
}
