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
    ];

    protected $casts = [
        'poor_threshold' => 'decimal:4',
        'average_value' => 'decimal:4',
        'good_threshold' => 'decimal:4',
        'excellent_threshold' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
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
            if ($value >= $this->excellent_threshold) return 'excellent';
            if ($value >= $this->good_threshold) return 'good';
            if ($value >= $this->average_value) return 'average';
            return 'poor';
        } else {
            // Lower is better
            if ($value <= $this->excellent_threshold) return 'excellent';
            if ($value <= $this->good_threshold) return 'good';
            if ($value <= $this->average_value) return 'average';
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
            'currency' => number_format($value, 0, '.', ' ') . ' UZS',
            'percent' => number_format($value, 1) . '%',
            'hours' => number_format($value, 1) . ' soat',
            'days' => number_format($value, 0) . ' kun',
            default => number_format($value, 2),
        };

        return $formatted;
    }
}
