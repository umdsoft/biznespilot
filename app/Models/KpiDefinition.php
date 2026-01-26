<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * KPI Definition model for the main kpi_templates table.
 * This defines the standard KPI metrics for business tracking.
 */
class KpiDefinition extends Model
{
    use SoftDeletes;

    protected $table = 'kpi_templates';

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
        'default_weight' => 'decimal:1',
        'default_green_threshold' => 'decimal:2',
        'default_yellow_threshold' => 'decimal:2',
        'is_universal' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function dailyActuals()
    {
        return $this->hasMany(KpiDailyActual::class, 'kpi_code', 'kpi_code');
    }

    /**
     * Calculate status based on achievement percentage
     */
    public function calculateStatus(float $achievementPercentage): string
    {
        $greenThreshold = $this->default_green_threshold ?? 90;
        $yellowThreshold = $this->default_yellow_threshold ?? 70;

        // For "lower is better" KPIs, invert the logic
        if ($this->good_direction === 'lower') {
            if ($achievementPercentage <= 100) {
                return 'green';
            } elseif ($achievementPercentage <= 120) {
                return 'yellow';
            } else {
                return 'red';
            }
        }

        // Standard "higher is better" logic
        if ($achievementPercentage >= $greenThreshold) {
            return 'green';
        } elseif ($achievementPercentage >= $yellowThreshold) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeUniversal($query)
    {
        return $query->where('is_universal', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('priority_level', 'critical');
    }
}
