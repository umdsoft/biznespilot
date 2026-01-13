<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgPosition extends Model
{
    use HasUuid;

    protected $fillable = [
        'org_department_id',
        'position_template_id',
        'job_description_id',
        'title',
        'level',
        'yqm_primary',
        'yqm_description',
        'yqm_metrics',
        'required_count',
        'current_count',
        'salary_min',
        'salary_max',
        'order',
        'is_active',
    ];

    protected $casts = [
        'yqm_metrics' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function orgDepartment(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class);
    }

    public function positionTemplate(): BelongsTo
    {
        return $this->belongsTo(PositionTemplate::class);
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrgAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(OrgAssignment::class)->where('is_active', true);
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

    public function scopeUnfilled($query)
    {
        return $query->whereColumn('current_count', '<', 'required_count');
    }

    public function scopeFilled($query)
    {
        return $query->whereColumn('current_count', '>=', 'required_count');
    }

    // ==================== Accessors ====================

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

    public function getVacanciesAttribute(): int
    {
        return max(0, $this->required_count - $this->current_count);
    }

    public function getFillRateAttribute(): float
    {
        return $this->required_count > 0 
            ? ($this->current_count / $this->required_count) * 100 
            : 0;
    }

    public function getIsFullyStaffedAttribute(): bool
    {
        return $this->current_count >= $this->required_count;
    }

    public function getYqmPrimaryAttribute($value): ?string
    {
        return $value ?? $this->positionTemplate?->yqm_primary;
    }
}
