<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PositionTemplate extends Model
{
    protected $fillable = [
        'department_template_id',
        'code',
        'title_uz',
        'title_ru',
        'title_en',
        'level',
        'reports_to',
        'yqm_primary',
        'yqm_description',
        'yqm_metrics',
        'responsibilities',
        'success_criteria',
        'requirements',
        'default_count',
        'salary_min',
        'salary_max',
        'order',
        'is_active',
    ];

    protected $casts = [
        'yqm_metrics' => 'array',
        'responsibilities' => 'array',
        'success_criteria' => 'array',
        'requirements' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function departmentTemplate(): BelongsTo
    {
        return $this->belongsTo(DepartmentTemplate::class);
    }

    public function orgPositions(): HasMany
    {
        return $this->hasMany(OrgPosition::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // ==================== Accessors ====================

    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"title_{$locale}"} ?? $this->title_uz;
    }

    public function getLevelLabelAttribute(): string
    {
        $labels = [
            0 => 'Direktor',
            1 => 'Bo\'lim boshlig\'i',
            2 => 'Menejer',
            3 => 'Mutaxassis',
            4 => 'Junior',
        ];

        return $labels[$this->level] ?? 'N/A';
    }
}
