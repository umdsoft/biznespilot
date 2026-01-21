<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Motivation Scheme based on Denis Shenukov's methodology:
 * - Two-parameter: Fix + Bonus
 * - Three-parameter: Fix + Soft Salary + Bonus
 * - Project-based: Team project bonus
 * - Key Tasks: Key tasks map based bonus
 */
class MotivationScheme extends Model
{
    use HasUuids, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'scheme_type', // two_parameter, three_parameter, project_based, key_tasks
        'motivation_type', // individual, team, mixed
        'level', // top_management, middle_management, specialists, workers
        'department_id',
        'position_id',
        'bonus_period', // monthly, quarterly, semi_annual, annual, project
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(OrgPosition::class, 'position_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(MotivationComponent::class)->orderBy('order');
    }

    public function employeeMotivations(): HasMany
    {
        return $this->hasMany(EmployeeMotivation::class);
    }

    public function vacancyCards(): HasMany
    {
        return $this->hasMany(VacancyCard::class);
    }

    // Get scheme type label
    public function getSchemeTypeLabelAttribute(): string
    {
        return match($this->scheme_type) {
            'two_parameter' => 'Ikki parametrli (Oklad + Bonus)',
            'three_parameter' => 'Uch parametrli (Oklad + Yumshoq oklad + Bonus)',
            'project_based' => 'Loyihaviy',
            'key_tasks' => 'Asosiy vazifalar xaritasi',
            default => $this->scheme_type,
        };
    }

    // Get total fixed components
    public function getTotalFixedSalaryAttribute(): float
    {
        return $this->components()
            ->where('component_type', 'fixed_salary')
            ->sum('base_amount');
    }

    // Get total soft salary components
    public function getTotalSoftSalaryAttribute(): float
    {
        return $this->components()
            ->where('component_type', 'soft_salary')
            ->sum('base_amount');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('valid_from', '<=', now())
                     ->where(function($q) {
                         $q->whereNull('valid_to')
                           ->orWhere('valid_to', '>=', now());
                     });
    }

    public function scopeForLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
