<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiIndustryBenchmark extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpi_industry_benchmarks';

    protected $fillable = [
        'industry_code',
        'industry_name',
        'sub_category',
        'sub_category_name',
        'business_size',
        'business_maturity',
        'kpi_code',
        'benchmark_value',
        'benchmark_unit',
        'percentile_25',
        'percentile_50',
        'percentile_75',
        'percentile_90',
        'min_acceptable',
        'max_realistic',
        'market_region',
        'data_source',
        'sample_size',
        'last_updated',
        'data_year',
        'notes',
        'notes_uz',
        'seasonality_factors',
        'confidence_level',
        'is_verified',
        'verified_by',
        'is_active',
    ];

    protected $casts = [
        'benchmark_value' => 'decimal:2',
        'percentile_25' => 'decimal:2',
        'percentile_50' => 'decimal:2',
        'percentile_75' => 'decimal:2',
        'percentile_90' => 'decimal:2',
        'min_acceptable' => 'decimal:2',
        'max_realistic' => 'decimal:2',
        'seasonality_factors' => 'array',
        'last_updated' => 'date',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'sample_size' => 'integer',
        'data_year' => 'integer',
    ];

    /**
     * Get the KPI template this benchmark belongs to
     */
    public function kpiTemplate()
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_code', 'kpi_code');
    }

    /**
     * Scope: Get active benchmarks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get verified benchmarks
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Get benchmarks for specific industry
     */
    public function scopeForIndustry($query, string $industryCode)
    {
        return $query->where('industry_code', $industryCode);
    }

    /**
     * Scope: Get benchmarks for specific subcategory
     */
    public function scopeForSubcategory($query, string $subcategory)
    {
        return $query->where('sub_category', $subcategory);
    }

    /**
     * Scope: Get benchmarks for specific business size
     */
    public function scopeForSize($query, string $size)
    {
        return $query->where('business_size', $size);
    }

    /**
     * Scope: Get benchmarks for specific maturity level
     */
    public function scopeForMaturity($query, string $maturity)
    {
        return $query->where('business_maturity', $maturity);
    }

    /**
     * Scope: Get benchmarks for specific KPI
     */
    public function scopeForKpi($query, string $kpiCode)
    {
        return $query->where('kpi_code', $kpiCode);
    }

    /**
     * Get benchmark value adjusted for seasonality
     */
    public function getSeasonalizedValue(int $month): float
    {
        if (! is_array($this->seasonality_factors) || empty($this->seasonality_factors)) {
            return (float) $this->benchmark_value;
        }

        $monthKey = (string) $month;
        $seasonalityFactor = $this->seasonality_factors[$monthKey] ?? 1.0;

        return (float) $this->benchmark_value * $seasonalityFactor;
    }

    /**
     * Get performance level based on actual value
     */
    public function getPerformanceLevel(float $actualValue): string
    {
        if ($this->percentile_90 && $actualValue >= $this->percentile_90) {
            return 'excellent';
        }

        if ($this->percentile_75 && $actualValue >= $this->percentile_75) {
            return 'good';
        }

        if ($this->percentile_50 && $actualValue >= $this->percentile_50) {
            return 'average';
        }

        if ($this->percentile_25 && $actualValue >= $this->percentile_25) {
            return 'below_average';
        }

        return 'poor';
    }

    /**
     * Get recommended target based on current performance
     */
    public function getRecommendedTarget(
        float $currentValue,
        string $growthScenario = 'moderate'
    ): float {
        // Growth multipliers
        $multipliers = [
            'conservative' => 1.05,  // 5% growth
            'moderate' => 1.15,      // 15% growth
            'aggressive' => 1.30,    // 30% growth
        ];

        $multiplier = $multipliers[$growthScenario] ?? 1.15;

        // If current value is 0 or very low, use benchmark as base
        if ($currentValue < ($this->benchmark_value * 0.3)) {
            return (float) $this->benchmark_value * 0.7; // Start with 70% of benchmark
        }

        // Calculate target based on current performance
        $target = $currentValue * $multiplier;

        // Cap at realistic maximum
        if ($this->max_realistic && $target > $this->max_realistic) {
            $target = $this->max_realistic;
        }

        // Ensure minimum acceptable
        if ($this->min_acceptable && $target < $this->min_acceptable) {
            $target = $this->min_acceptable;
        }

        return round($target, 2);
    }

    /**
     * Check if benchmark data is recent (within last year)
     */
    public function isRecent(): bool
    {
        if (! $this->last_updated) {
            return false;
        }

        return $this->last_updated->diffInMonths(now()) <= 12;
    }

    /**
     * Get confidence description
     */
    public function getConfidenceDescription(): string
    {
        return match ($this->confidence_level) {
            'high' => 'Yuqori ishonch - katta sample size, tasdiqlangan',
            'medium' => 'O\'rtacha ishonch - yetarli sample size',
            'low' => 'Past ishonch - kichik sample size yoki eski ma\'lumot',
            default => 'Noma\'lum',
        };
    }

    /**
     * Get all percentile values as array
     */
    public function getPercentiles(): array
    {
        return [
            25 => $this->percentile_25,
            50 => $this->percentile_50,
            75 => $this->percentile_75,
            90 => $this->percentile_90,
        ];
    }
}
