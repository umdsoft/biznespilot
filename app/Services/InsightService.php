<?php

namespace App\Services;

use App\Models\Business;
use App\Models\KpiDailySnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Algorithmic Insight Service
 *
 * Generates insights based on pattern detection and statistical analysis
 * without using AI or storing in database.
 */
class InsightService
{
    protected array $insightTypes = [
        'trend' => 'Trend tahlili',
        'anomaly' => 'Anomaliya',
        'recommendation' => 'Tavsiya',
        'opportunity' => 'Imkoniyat',
        'warning' => 'Ogohlantirish',
        'celebration' => 'Muvaffaqiyat',
    ];

    public function generateInsights(Business $business): Collection
    {
        $insights = collect();

        $snapshots = $this->getRecentSnapshots($business, 30);

        if ($snapshots->isEmpty()) {
            return $insights;
        }

        $patternInsights = $this->detectPatterns($business, $snapshots);
        $insights = $insights->merge($patternInsights);

        $anomalyInsights = $this->detectAnomalies($business, $snapshots);
        $insights = $insights->merge($anomalyInsights);

        $recommendations = $this->generateRecommendations($business, $snapshots);
        $insights = $insights->merge($recommendations);

        $celebrations = $this->detectCelebrations($business, $snapshots);
        $insights = $insights->merge($celebrations);

        return $insights;
    }

    public function detectPatterns(Business $business, Collection $snapshots): Collection
    {
        $insights = collect();
        $today = $snapshots->last();
        $weekAgo = $snapshots->firstWhere('snapshot_date', Carbon::today()->subWeek()->format('Y-m-d'));

        if (! $today || ! $weekAgo) {
            return $insights;
        }

        $revenueTrend = $this->calculateTrend($snapshots, 'revenue_total', 7);
        if ($revenueTrend !== null && abs($revenueTrend) > 10) {
            $insights->push($this->createInsightArray($business, [
                'type' => 'trend',
                'category' => 'revenue',
                'priority' => abs($revenueTrend) > 20 ? 'high' : 'medium',
                'title' => $revenueTrend > 0
                    ? 'Daromad o\'sish trendida'
                    : 'Daromad pasayish trendida',
                'summary' => sprintf(
                    'Oxirgi 7 kunda daromad %s%.1f%% %s.',
                    $revenueTrend > 0 ? '+' : '',
                    $revenueTrend,
                    $revenueTrend > 0 ? 'o\'sdi' : 'kamaydi'
                ),
                'confidence_score' => 0.85,
            ]));
        }

        $leadTrend = $this->calculateTrend($snapshots, 'leads_total', 7);
        if ($leadTrend !== null && abs($leadTrend) > 15) {
            $insights->push($this->createInsightArray($business, [
                'type' => 'trend',
                'category' => 'leads',
                'priority' => abs($leadTrend) > 25 ? 'high' : 'medium',
                'title' => $leadTrend > 0
                    ? 'Lid generatsiyasi yaxshilandi'
                    : 'Lid generatsiyasi pasaydi',
                'summary' => sprintf(
                    'Oxirgi haftada lidlar soni %s%.1f%% %s.',
                    $leadTrend > 0 ? '+' : '',
                    $leadTrend,
                    $leadTrend > 0 ? 'ko\'paydi' : 'kamaydi'
                ),
                'confidence_score' => 0.82,
            ]));
        }

        return $insights;
    }

    public function detectAnomalies(Business $business, Collection $snapshots): Collection
    {
        $insights = collect();

        if ($snapshots->count() < 7) {
            return $insights;
        }

        $metrics = ['revenue_total', 'leads_total', 'cac', 'ad_roas', 'conversion_rate'];
        $today = $snapshots->last();

        foreach ($metrics as $metric) {
            $values = $snapshots->pluck($metric)->filter()->values();

            if ($values->count() < 5) {
                continue;
            }

            $mean = $values->avg();
            $stdDev = $this->calculateStdDev($values);
            $currentValue = $today->{$metric};

            if ($stdDev > 0 && $currentValue !== null) {
                $zScore = abs(($currentValue - $mean) / $stdDev);

                if ($zScore > 2) {
                    $isPositive = $this->isPositiveAnomaly($metric, $currentValue, $mean);

                    $insights->push($this->createInsightArray($business, [
                        'type' => 'anomaly',
                        'category' => $this->getMetricCategory($metric),
                        'priority' => $zScore > 3 ? 'critical' : 'high',
                        'title' => sprintf('%s uchun g\'ayrioddiy qiymat', $this->getMetricLabel($metric)),
                        'summary' => sprintf(
                            'Bugungi %s qiymati (%.2f) o\'rtacha qiymatdan (%.2f) sezilarli %s.',
                            $this->getMetricLabel($metric),
                            $currentValue,
                            $mean,
                            $isPositive ? 'yuqori' : 'past'
                        ),
                        'confidence_score' => min(0.95, 0.7 + ($zScore * 0.1)),
                    ]));
                }
            }
        }

        return $insights;
    }

    public function generateRecommendations(Business $business, Collection $snapshots): Collection
    {
        $insights = collect();
        $today = $snapshots->last();

        if (! $today) {
            return $insights;
        }

        if ($today->cac > 0 && $today->revenue_total > 0) {
            $cacToRevenue = ($today->cac * $today->leads_total) / $today->revenue_total;
            if ($cacToRevenue > 0.3) {
                $insights->push($this->createInsightArray($business, [
                    'type' => 'recommendation',
                    'category' => 'marketing',
                    'priority' => 'high',
                    'title' => 'CAC optimizatsiyasi tavsiya etiladi',
                    'summary' => 'Mijoz jalb qilish xarajati daromadga nisbatan yuqori.',
                    'confidence_score' => 0.78,
                ]));
            }
        }

        if ($today->conversion_rate < 2 && $today->leads_total > 10) {
            $insights->push($this->createInsightArray($business, [
                'type' => 'recommendation',
                'category' => 'sales',
                'priority' => 'high',
                'title' => 'Konversiya darajasini oshirish kerak',
                'summary' => sprintf('Joriy konversiya: %.1f%%. Sohadagi o\'rtacha 3-5%%.', $today->conversion_rate),
                'confidence_score' => 0.75,
            ]));
        }

        if ($today->ad_roas > 0 && $today->ad_roas < 2) {
            $insights->push($this->createInsightArray($business, [
                'type' => 'recommendation',
                'category' => 'advertising',
                'priority' => 'medium',
                'title' => 'Reklama samaradorligini oshiring',
                'summary' => sprintf('ROAS: %.1fx. Maqsad: kamida 3x.', $today->ad_roas),
                'confidence_score' => 0.72,
            ]));
        }

        return $insights;
    }

    public function detectCelebrations(Business $business, Collection $snapshots): Collection
    {
        $insights = collect();
        $today = $snapshots->last();
        $yesterday = $snapshots->reverse()->skip(1)->first();

        if (! $today || ! $yesterday) {
            return $insights;
        }

        if ($today->revenue_total >= 100000000 && $yesterday->revenue_total < 100000000) {
            $insights->push($this->createInsightArray($business, [
                'type' => 'celebration',
                'category' => 'revenue',
                'priority' => 'high',
                'title' => '100M so\'m belgilanish bosqichi!',
                'summary' => 'Tabriklaymiz! Kunlik daromad 100 million so\'mdan oshdi.',
                'confidence_score' => 1.0,
            ]));
        }

        $maxRevenue = $snapshots->max('revenue_total');
        if ($today->revenue_total >= $maxRevenue && $today->revenue_total > 0) {
            $insights->push($this->createInsightArray($business, [
                'type' => 'celebration',
                'category' => 'revenue',
                'priority' => 'high',
                'title' => 'Eng yaxshi kun!',
                'summary' => sprintf('Bugun rekord daromad: %s so\'m.', number_format($today->revenue_total)),
                'confidence_score' => 1.0,
            ]));
        }

        return $insights;
    }

    protected function createInsightArray(Business $business, array $data): array
    {
        return [
            'id' => uniqid('insight_'),
            'business_id' => $business->id,
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'title' => $data['title'],
            'summary' => $data['summary'],
            'confidence_score' => $data['confidence_score'] ?? 0.7,
            'created_at' => now()->toISOString(),
        ];
    }

    protected function getRecentSnapshots(Business $business, int $days): Collection
    {
        return KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date')
            ->get();
    }

    protected function calculateTrend(Collection $snapshots, string $metric, int $days): ?float
    {
        $recent = $snapshots->take(-$days);

        if ($recent->count() < 2) {
            return null;
        }

        $first = $recent->first()->{$metric};
        $last = $recent->last()->{$metric};

        if (! $first || $first == 0) {
            return null;
        }

        return (($last - $first) / $first) * 100;
    }

    protected function calculateStdDev(Collection $values): float
    {
        $mean = $values->avg();
        $squaredDiffs = $values->map(fn ($v) => pow($v - $mean, 2));

        return sqrt($squaredDiffs->avg());
    }

    protected function isPositiveAnomaly(string $metric, float $current, float $mean): bool
    {
        $negativeMetrics = ['cac', 'churn_rate'];
        $isHigher = $current > $mean;

        return in_array($metric, $negativeMetrics) ? ! $isHigher : $isHigher;
    }

    protected function getMetricCategory(string $metric): string
    {
        return match (true) {
            str_contains($metric, 'revenue') => 'revenue',
            str_contains($metric, 'lead') => 'leads',
            str_contains($metric, 'ad_') || str_contains($metric, 'roas') => 'advertising',
            str_contains($metric, 'cac') => 'marketing',
            str_contains($metric, 'conversion') => 'sales',
            default => 'general',
        };
    }

    protected function getMetricLabel(string $metric): string
    {
        return match ($metric) {
            'revenue_total' => 'Daromad',
            'leads_total' => 'Lidlar',
            'cac' => 'CAC',
            'ad_roas' => 'ROAS',
            'conversion_rate' => 'Konversiya',
            'engagement_rate' => 'Engagement',
            default => $metric,
        };
    }

    public function getActiveInsights(Business $business): Collection
    {
        return $this->generateInsights($business);
    }
}
