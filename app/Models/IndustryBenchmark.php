<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryBenchmark extends Model
{
    protected $fillable = [
        'industry_id',
        'metric_code',
        'metric_name_uz',
        'metric_name_en',
        'description',
        'poor_threshold',
        'average_value',
        'good_threshold',
        'excellent_threshold',
        'unit',
        'direction',
        'source',
        'valid_from',
        'valid_until',
        'is_active',
        // New fields for AI Diagnostics
        'industry',
        'sub_industry',
        'avg_health_score',
        'avg_conversion_rate',
        'avg_engagement_rate',
        'avg_response_time_minutes',
        'avg_repeat_purchase_rate',
        'top_health_score',
        'top_conversion_rate',
        'top_engagement_rate',
        'top_response_time_minutes',
        'top_repeat_purchase_rate',
        'optimal_post_frequency_weekly',
        'optimal_stories_daily',
        'optimal_caption_length',
        'optimal_hashtag_count',
        'optimal_posting_times',
        'proven_tactics',
        'businesses_count',
        'last_calculated_at',
    ];

    protected $casts = [
        'poor_threshold' => 'decimal:4',
        'average_value' => 'decimal:4',
        'good_threshold' => 'decimal:4',
        'excellent_threshold' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        // New casts
        'avg_health_score' => 'decimal:2',
        'avg_conversion_rate' => 'decimal:2',
        'avg_engagement_rate' => 'decimal:2',
        'avg_repeat_purchase_rate' => 'decimal:2',
        'top_health_score' => 'decimal:2',
        'top_conversion_rate' => 'decimal:2',
        'top_engagement_rate' => 'decimal:2',
        'top_repeat_purchase_rate' => 'decimal:2',
        'optimal_posting_times' => 'array',
        'proven_tactics' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    // Constants
    public const UNITS = [
        'percent' => '%',
        'currency' => 'UZS',
        'number' => '',
        'hours' => 'soat',
        'days' => 'kun',
    ];

    public const DIRECTIONS = [
        'higher_better' => 'Yuqori yaxshi',
        'lower_better' => 'Past yaxshi',
    ];

    // Relationships
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMetric($query, string $metricCode)
    {
        return $query->where('metric_code', $metricCode);
    }

    public function scopeForIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now());
        });
    }

    // Helpers
    public function getMetricName(string $locale = 'uz'): string
    {
        return $locale === 'en' && $this->metric_name_en
            ? $this->metric_name_en
            : $this->metric_name_uz;
    }

    public function getUnitSymbol(): string
    {
        return self::UNITS[$this->unit] ?? '';
    }

    public function isHigherBetter(): bool
    {
        return $this->direction === 'higher_better';
    }

    /**
     * Get status based on value
     * Returns: poor, average, good, excellent
     */
    public function getStatus(float $value): string
    {
        if ($this->isHigherBetter()) {
            if ($value >= $this->excellent_threshold) {
                return 'excellent';
            }
            if ($value >= $this->good_threshold) {
                return 'good';
            }
            if ($value >= $this->average_value) {
                return 'average';
            }

            return 'poor';
        } else {
            // Lower is better
            if ($value <= $this->excellent_threshold) {
                return 'excellent';
            }
            if ($value <= $this->good_threshold) {
                return 'good';
            }
            if ($value <= $this->average_value) {
                return 'average';
            }

            return 'poor';
        }
    }

    /**
     * Get status color
     */
    public function getStatusColor(float $value): string
    {
        $status = $this->getStatus($value);

        return match ($status) {
            'excellent' => 'blue',
            'good' => 'green',
            'average' => 'yellow',
            'poor' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(float $value): string
    {
        $status = $this->getStatus($value);

        return match ($status) {
            'excellent' => 'Ajoyib',
            'good' => 'Yaxshi',
            'average' => 'O\'rtacha',
            'poor' => 'Zaif',
            default => 'Noma\'lum',
        };
    }

    /**
     * Calculate gap from average
     */
    public function calculateGap(float $value): float
    {
        if ($this->isHigherBetter()) {
            return $value - $this->average_value;
        } else {
            return $this->average_value - $value;
        }
    }

    /**
     * Calculate percentage difference from average
     */
    public function calculateGapPercent(float $value): float
    {
        if ($this->average_value == 0) {
            return 0;
        }

        $gap = $this->calculateGap($value);

        return round(($gap / $this->average_value) * 100, 1);
    }

    /**
     * Format value with unit
     */
    public function formatValue(float $value): string
    {
        $formatted = match ($this->unit) {
            'currency' => number_format($value, 0, '.', ' ').' UZS',
            'percent' => number_format($value, 1).'%',
            'hours' => number_format($value, 1).' soat',
            'days' => number_format($value, 0).' kun',
            default => number_format($value, 2),
        };

        return $formatted;
    }

    /**
     * Get benchmark data for AI Diagnostics
     */
    public function toAIBenchmarkArray(): array
    {
        return [
            'industry' => $this->industry,
            'sub_industry' => $this->sub_industry,
            'avg_health_score' => $this->avg_health_score ?? 50,
            'avg_conversion_rate' => $this->avg_conversion_rate ?? 2.5,
            'avg_engagement_rate' => $this->avg_engagement_rate ?? 3.5,
            'avg_response_time_minutes' => $this->avg_response_time_minutes ?? 60,
            'avg_repeat_purchase_rate' => $this->avg_repeat_purchase_rate ?? 20,
            'top_health_score' => $this->top_health_score ?? 85,
            'top_conversion_rate' => $this->top_conversion_rate ?? 8,
            'top_engagement_rate' => $this->top_engagement_rate ?? 8,
            'top_response_time_minutes' => $this->top_response_time_minutes ?? 15,
            'top_repeat_purchase_rate' => $this->top_repeat_purchase_rate ?? 40,
            'optimal_post_frequency_weekly' => $this->optimal_post_frequency_weekly ?? 5,
            'optimal_stories_daily' => $this->optimal_stories_daily ?? 5,
            'proven_tactics' => $this->proven_tactics ?? [],
            'businesses_count' => $this->businesses_count ?? 0,
        ];
    }

    /**
     * Get default benchmarks for an industry
     */
    public static function getDefaultBenchmarks(string $industry): array
    {
        $benchmark = self::where('industry', $industry)->first();

        if ($benchmark) {
            return $benchmark->toAIBenchmarkArray();
        }

        // Default values
        return [
            'industry' => $industry,
            'avg_health_score' => 50,
            'avg_conversion_rate' => 2.5,
            'avg_engagement_rate' => 3.5,
            'avg_response_time_minutes' => 60,
            'avg_repeat_purchase_rate' => 20,
            'top_health_score' => 85,
            'top_conversion_rate' => 8,
            'top_engagement_rate' => 8,
            'top_response_time_minutes' => 15,
            'top_repeat_purchase_rate' => 40,
            'optimal_post_frequency_weekly' => 5,
            'optimal_stories_daily' => 5,
            'proven_tactics' => [],
            'businesses_count' => 0,
        ];
    }

    /**
     * Get benchmark for algorithm usage with caching
     */
    public static function getForAlgorithm(string $industry): array
    {
        $cacheKey = "algo_benchmark:{$industry}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($industry) {
            $benchmark = self::where('industry', $industry)->active()->first();

            if ($benchmark) {
                return $benchmark->toAlgorithmArray();
            }

            return self::getAlgorithmDefaults();
        });
    }

    /**
     * Convert to algorithm-friendly array
     */
    public function toAlgorithmArray(): array
    {
        return [
            'industry' => $this->industry,
            'conversion_rate' => $this->avg_conversion_rate ?? 2.5,
            'engagement_rate' => $this->avg_engagement_rate ?? 3.0,
            'response_time_hours' => ($this->avg_response_time_minutes ?? 120) / 60,
            'customer_retention' => 100 - ($this->churn_rate ?? 5),
            'cac_ltv_ratio' => 3.0,
            'repeat_purchase_rate' => $this->avg_repeat_purchase_rate ?? 25,
            'churn_rate' => $this->churn_rate ?? 5,
            'funnel_conversion' => $this->funnel_conversion ?? [
                'awareness_to_interest' => 30,
                'interest_to_consideration' => 50,
                'consideration_to_intent' => 40,
                'intent_to_purchase' => 25,
            ],
            'social_benchmarks' => $this->social_benchmarks ?? [
                'instagram_er' => 3.0,
                'telegram_growth' => 5,
                'post_frequency' => $this->optimal_post_frequency_weekly ?? 5,
            ],
            'content_benchmarks' => $this->content_benchmarks ?? [
                'caption_length' => $this->optimal_caption_length ?? 150,
                'hashtag_count' => $this->optimal_hashtag_count ?? 15,
                'stories_daily' => $this->optimal_stories_daily ?? 5,
            ],
        ];
    }

    /**
     * Get algorithm default benchmarks
     */
    public static function getAlgorithmDefaults(): array
    {
        return [
            'industry' => 'default',
            'conversion_rate' => 2.5,
            'engagement_rate' => 3.0,
            'response_time_hours' => 2,
            'customer_retention' => 70,
            'cac_ltv_ratio' => 3.0,
            'repeat_purchase_rate' => 25,
            'churn_rate' => 5,
            'funnel_conversion' => [
                'awareness_to_interest' => 30,
                'interest_to_consideration' => 50,
                'consideration_to_intent' => 40,
                'intent_to_purchase' => 25,
            ],
            'social_benchmarks' => [
                'instagram_er' => 3.0,
                'telegram_growth' => 5,
                'post_frequency' => 5,
            ],
            'content_benchmarks' => [
                'caption_length' => 150,
                'hashtag_count' => 15,
                'stories_daily' => 5,
            ],
        ];
    }
}
