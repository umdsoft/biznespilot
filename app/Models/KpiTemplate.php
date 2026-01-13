<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplate extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'hr_kpi_templates';

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'category',
        'measurement_unit',
        'target_value',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function employeeGoals(): HasMany
    {
        return $this->hasMany(EmployeeGoal::class);
    }

    // ==================== Accessors ====================

    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'sales' => 'Sotuv',
            'productivity' => 'Samaradorlik',
            'quality' => 'Sifat',
            'customer_satisfaction' => 'Mijozlar Mamnuniyati',
        ];

        return $labels[$this->category] ?? $this->category;
    }

    public function getFrequencyLabelAttribute(): string
    {
        $labels = [
            'daily' => 'Kunlik',
            'weekly' => 'Haftalik',
            'monthly' => 'Oylik',
            'quarterly' => 'Choraklik',
            'annually' => 'Yillik',
        ];

        return $labels[$this->frequency] ?? $this->frequency;
    }

    public function getMeasurementUnitLabelAttribute(): string
    {
        $labels = [
            'percentage' => '%',
            'number' => 'Raqam',
            'currency' => 'UZS',
        ];

        return $labels[$this->measurement_unit] ?? $this->measurement_unit;
    }
}
