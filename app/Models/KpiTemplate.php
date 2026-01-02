<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category',
        'kpi_code',
        'kpi_name',
        'kpi_name_uz',
        'kpi_name_ru',
        'description',
        'description_uz',
        'measurement_method',
        'formula',
        'good_direction',
        'default_frequency',
        'default_unit',
        'is_universal',
        'applicable_industries',
        'applicable_subcategories',
        'priority_level',
        'default_weight',
        'min_business_age',
        'excluded_for_maturity',
        'icon',
        'color_code',
        'display_order',
        'default_green_threshold',
        'default_yellow_threshold',
        'tips',
        'tips_uz',
        'help_url',
        'is_active',
        'deprecation_note',
    ];

    protected $casts = [
        'applicable_industries' => 'array',
        'applicable_subcategories' => 'array',
        'excluded_for_maturity' => 'array',
        'is_universal' => 'boolean',
        'is_active' => 'boolean',
        'default_weight' => 'decimal:1',
        'default_green_threshold' => 'decimal:2',
        'default_yellow_threshold' => 'decimal:2',
    ];

    /**
     * Get all industry benchmarks for this KPI
     */
    public function industryBenchmarks()
    {
        return $this->hasMany(IndustryBenchmark::class, 'kpi_code', 'kpi_code');
    }

    /**
     * Get all daily actuals using this KPI
     */
    public function dailyActuals()
    {
        return $this->hasMany(KpiDailyActual::class, 'kpi_code', 'kpi_code');
    }

    /**
     * Get all weekly summaries using this KPI
     */
    public function weeklySummaries()
    {
        return $this->hasMany(KpiWeeklySummary::class, 'kpi_code', 'kpi_code');
    }

    /**
     * Get all monthly summaries using this KPI
     */
    public function monthlySummaries()
    {
        return $this->hasMany(KpiMonthlySummary::class, 'kpi_code', 'kpi_code');
    }

    /**
     * Scope: Get only active KPIs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get universal KPIs (applicable to all businesses)
     */
    public function scopeUniversal($query)
    {
        return $query->where('is_universal', true);
    }

    /**
     * Scope: Get KPIs by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get KPIs by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority_level', $priority);
    }

    /**
     * Scope: Get KPIs applicable to specific industry
     */
    public function scopeForIndustry($query, string $industryCode)
    {
        return $query->where(function ($q) use ($industryCode) {
            $q->where('is_universal', true)
                ->orWhereJsonContains('applicable_industries', $industryCode);
        });
    }

    /**
     * Scope: Get KPIs applicable to specific subcategory
     */
    public function scopeForSubcategory($query, string $subcategoryCode)
    {
        return $query->where(function ($q) use ($subcategoryCode) {
            $q->where('is_universal', true)
                ->orWhereJsonContains('applicable_subcategories', $subcategoryCode);
        });
    }

    /**
     * Check if this KPI is applicable to a given industry
     */
    public function isApplicableToIndustry(string $industryCode): bool
    {
        if ($this->is_universal) {
            return true;
        }

        if (is_array($this->applicable_industries)) {
            return in_array($industryCode, $this->applicable_industries) ||
                   in_array('all', $this->applicable_industries);
        }

        return false;
    }

    /**
     * Check if this KPI is applicable to a given subcategory
     */
    public function isApplicableToSubcategory(string $subcategoryCode): bool
    {
        if ($this->is_universal) {
            return true;
        }

        if (is_array($this->applicable_subcategories)) {
            return in_array($subcategoryCode, $this->applicable_subcategories) ||
                   in_array('all', $this->applicable_subcategories);
        }

        return false;
    }

    /**
     * Check if this KPI is suitable for a given business maturity level
     */
    public function isSuitableForMaturity(string $maturityLevel): bool
    {
        // Check if this maturity is explicitly excluded
        if (is_array($this->excluded_for_maturity) &&
            in_array($maturityLevel, $this->excluded_for_maturity)) {
            return false;
        }

        // Check minimum age requirement
        $maturityOrder = ['new' => 1, 'growing' => 2, 'established' => 3, 'any' => 0];
        $minOrder = $maturityOrder[$this->min_business_age] ?? 0;
        $currentOrder = $maturityOrder[$maturityLevel] ?? 0;

        if ($this->min_business_age === 'any') {
            return true;
        }

        return $currentOrder >= $minOrder;
    }

    /**
     * Get display name in specified language
     */
    public function getDisplayName(string $lang = 'uz'): string
    {
        return match($lang) {
            'uz' => $this->kpi_name_uz ?? $this->kpi_name,
            'ru' => $this->kpi_name_ru ?? $this->kpi_name,
            default => $this->kpi_name,
        };
    }

    /**
     * Get description in specified language
     */
    public function getDescription(string $lang = 'uz'): string
    {
        return match($lang) {
            'uz' => $this->description_uz ?? $this->description ?? '',
            default => $this->description ?? '',
        };
    }

    /**
     * Calculate status based on achievement percentage
     */
    public function calculateStatus(float $achievementPercentage): string
    {
        $greenThreshold = $this->default_green_threshold ?? 90.0;
        $yellowThreshold = $this->default_yellow_threshold ?? 70.0;

        if ($this->good_direction === 'lower') {
            // For KPIs where lower is better (e.g., response time, costs)
            // Invert the logic
            if ($achievementPercentage <= 110) {
                return 'green';
            } elseif ($achievementPercentage <= 130) {
                return 'yellow';
            } else {
                return 'red';
            }
        } else {
            // For KPIs where higher is better (default)
            if ($achievementPercentage >= $greenThreshold) {
                return 'green';
            } elseif ($achievementPercentage >= $yellowThreshold) {
                return 'yellow';
            } else {
                return 'red';
            }
        }
    }

    /**
     * Get status emoji
     */
    public function getStatusEmoji(string $status): string
    {
        return match($status) {
            'green' => '🟢',
            'yellow' => '🟡',
            'red' => '🔴',
            default => '⚪',
        };
    }

    /**
     * Get priority emoji
     */
    public function getPriorityEmoji(): string
    {
        return match($this->priority_level) {
            'critical' => '🔴',
            'high' => '🟡',
            'medium' => '🔵',
            'low' => '⚪',
            default => '⚪',
        };
    }
}
