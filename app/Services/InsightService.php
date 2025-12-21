<?php

namespace App\Services;

use App\Models\Business;
use App\Models\AiInsight;
use App\Models\KpiDailySnapshot;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

        // Get historical data
        $snapshots = $this->getRecentSnapshots($business, 30);

        if ($snapshots->isEmpty()) {
            return $insights;
        }

        // Detect patterns
        $patternInsights = $this->detectPatterns($business, $snapshots);
        $insights = $insights->merge($patternInsights);

        // Detect anomalies
        $anomalyInsights = $this->detectAnomalies($business, $snapshots);
        $insights = $insights->merge($anomalyInsights);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($business, $snapshots);
        $insights = $insights->merge($recommendations);

        // Detect celebrations (positive achievements)
        $celebrations = $this->detectCelebrations($business, $snapshots);
        $insights = $insights->merge($celebrations);

        return $insights;
    }

    public function detectPatterns(Business $business, Collection $snapshots): Collection
    {
        $insights = collect();
        $today = $snapshots->last();
        $weekAgo = $snapshots->firstWhere('snapshot_date', Carbon::today()->subWeek()->format('Y-m-d'));

        if (!$today || !$weekAgo) {
            return $insights;
        }

        // Revenue trend pattern
        $revenueTrend = $this->calculateTrend($snapshots, 'revenue_total', 7);
        if ($revenueTrend !== null && abs($revenueTrend) > 10) {
            $insights->push($this->createInsight($business, [
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
                'details' => [
                    'trend_percent' => $revenueTrend,
                    'period_days' => 7,
                    'metric' => 'revenue_total',
                ],
                'confidence_score' => 0.85,
            ]));
        }

        // Lead generation trend
        $leadTrend = $this->calculateTrend($snapshots, 'leads_total', 7);
        if ($leadTrend !== null && abs($leadTrend) > 15) {
            $insights->push($this->createInsight($business, [
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
                'details' => [
                    'trend_percent' => $leadTrend,
                    'period_days' => 7,
                ],
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

                    $insights->push($this->createInsight($business, [
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
                        'details' => [
                            'metric' => $metric,
                            'current_value' => $currentValue,
                            'mean' => $mean,
                            'std_dev' => $stdDev,
                            'z_score' => $zScore,
                            'is_positive' => $isPositive,
                        ],
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

        if (!$today) {
            return $insights;
        }

        // CAC is too high
        if ($today->cac > 0 && $today->revenue_total > 0) {
            $cacToRevenue = ($today->cac * $today->leads_total) / $today->revenue_total;
            if ($cacToRevenue > 0.3) {
                $insights->push($this->createInsight($business, [
                    'type' => 'recommendation',
                    'category' => 'marketing',
                    'priority' => 'high',
                    'title' => 'CAC optimizatsiyasi tavsiya etiladi',
                    'summary' => 'Mijoz jalb qilish xarajati daromadga nisbatan yuqori.',
                    'details' => [
                        'cac' => $today->cac,
                        'cac_revenue_ratio' => $cacToRevenue,
                    ],
                    'recommendations' => [
                        'Organik kanallarni kuchaytiring',
                        'Referral dasturini yo\'lga qo\'ying',
                        'Kam samarali reklama kanallarini to\'xtating',
                    ],
                    'confidence_score' => 0.78,
                ]));
            }
        }

        // Low conversion rate
        if ($today->conversion_rate < 2 && $today->leads_total > 10) {
            $insights->push($this->createInsight($business, [
                'type' => 'recommendation',
                'category' => 'sales',
                'priority' => 'high',
                'title' => 'Konversiya darajasini oshirish kerak',
                'summary' => sprintf('Joriy konversiya: %.1f%%. Sohadagi o\'rtacha 3-5%%.', $today->conversion_rate),
                'recommendations' => [
                    'Savdo skriptlarini qayta ko\'rib chiqing',
                    'Lead kvalifikatsiyasini yaxshilang',
                    'Follow-up jarayonini avtomatlashtiring',
                ],
                'confidence_score' => 0.75,
            ]));
        }

        // ROAS is low
        if ($today->ad_roas > 0 && $today->ad_roas < 2) {
            $insights->push($this->createInsight($business, [
                'type' => 'recommendation',
                'category' => 'advertising',
                'priority' => 'medium',
                'title' => 'Reklama samaradorligini oshiring',
                'summary' => sprintf('ROAS: %.1fx. Maqsad: kamida 3x.', $today->ad_roas),
                'recommendations' => [
                    'Target auditoriyani aniqroq belgilang',
                    'Kreativlarni yangilang',
                    'A/B testlarni o\'tkazing',
                ],
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

        if (!$today || !$yesterday) {
            return $insights;
        }

        // Revenue milestone
        if ($today->revenue_total >= 100000000 && $yesterday->revenue_total < 100000000) {
            $insights->push($this->createInsight($business, [
                'type' => 'celebration',
                'category' => 'revenue',
                'priority' => 'high',
                'title' => '🎉 100M so\'m belgilanish bosqichi!',
                'summary' => 'Tabriklaymiz! Kunlik daromad 100 million so\'mdan oshdi.',
                'confidence_score' => 1.0,
            ]));
        }

        // Lead milestone
        if ($today->leads_total >= 100 && $yesterday->leads_total < 100) {
            $insights->push($this->createInsight($business, [
                'type' => 'celebration',
                'category' => 'leads',
                'priority' => 'medium',
                'title' => '🎯 100 ta lid belgilandi!',
                'summary' => 'Kunlik lid soni 100 tadan oshdi.',
                'confidence_score' => 1.0,
            ]));
        }

        // Best day ever for revenue
        $maxRevenue = $snapshots->max('revenue_total');
        if ($today->revenue_total >= $maxRevenue && $today->revenue_total > 0) {
            $insights->push($this->createInsight($business, [
                'type' => 'celebration',
                'category' => 'revenue',
                'priority' => 'high',
                'title' => '🏆 Eng yaxshi kun!',
                'summary' => sprintf('Bugun rekord daromad: %s so\'m.', number_format($today->revenue_total)),
                'confidence_score' => 1.0,
            ]));
        }

        return $insights;
    }

    protected function createInsight(Business $business, array $data): AiInsight
    {
        // Check for duplicate
        $existing = AiInsight::where('business_id', $business->id)
            ->where('type', $data['type'])
            ->where('category', $data['category'] ?? null)
            ->where('title', $data['title'])
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        if ($existing) {
            return $existing;
        }

        $insight = AiInsight::create([
            'business_id' => $business->id,
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'title' => $data['title'],
            'summary' => $data['summary'],
            'details' => $data['details'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'data_points' => $data['data_points'] ?? null,
            'confidence_score' => $data['confidence_score'] ?? 0.7,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        // Send notification for high priority insights
        if (in_array($data['priority'], ['critical', 'high'])) {
            $this->sendInsightNotification($insight);
        }

        return $insight;
    }

    protected function sendInsightNotification(AiInsight $insight): void
    {
        Notification::create([
            'business_id' => $insight->business_id,
            'type' => 'insight',
            'channel' => 'in_app',
            'title' => $insight->title,
            'message' => $insight->summary,
            'action_url' => "/insights/{$insight->id}",
            'action_text' => 'Batafsil',
            'related_type' => AiInsight::class,
            'related_id' => $insight->id,
            'priority' => $insight->priority,
        ]);
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

        if (!$first || $first == 0) {
            return null;
        }

        return (($last - $first) / $first) * 100;
    }

    protected function calculateStdDev(Collection $values): float
    {
        $mean = $values->avg();
        $squaredDiffs = $values->map(fn($v) => pow($v - $mean, 2));
        return sqrt($squaredDiffs->avg());
    }

    protected function isPositiveAnomaly(string $metric, float $current, float $mean): bool
    {
        $negativeMetrics = ['cac', 'churn_rate'];
        $isHigher = $current > $mean;

        return in_array($metric, $negativeMetrics) ? !$isHigher : $isHigher;
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
        return AiInsight::where('business_id', $business->id)
            ->active()
            ->notExpired()
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
