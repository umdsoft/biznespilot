<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobDescription extends Model
{
    use BelongsToBusiness, HasUuid;

    // Employment Types
    public const EMPLOYMENT_TYPE_FULL_TIME = 'full_time';
    public const EMPLOYMENT_TYPE_PART_TIME = 'part_time';
    public const EMPLOYMENT_TYPE_CONTRACT = 'contract';
    public const EMPLOYMENT_TYPE_TEMPORARY = 'temporary';
    public const EMPLOYMENT_TYPE_INTERNSHIP = 'internship';

    public const EMPLOYMENT_TYPES = [
        self::EMPLOYMENT_TYPE_FULL_TIME => 'To\'liq stavka',
        self::EMPLOYMENT_TYPE_PART_TIME => 'Qisman stavka',
        self::EMPLOYMENT_TYPE_CONTRACT => 'Shartnoma',
        self::EMPLOYMENT_TYPE_TEMPORARY => 'Vaqtinchalik',
        self::EMPLOYMENT_TYPE_INTERNSHIP => 'Amaliyot',
    ];

    // Position Levels
    public const LEVEL_JUNIOR = 'junior';
    public const LEVEL_MID = 'mid';
    public const LEVEL_SENIOR = 'senior';
    public const LEVEL_LEAD = 'lead';
    public const LEVEL_MANAGER = 'manager';
    public const LEVEL_DIRECTOR = 'director';

    public const POSITION_LEVELS = [
        self::LEVEL_JUNIOR => 'Junior',
        self::LEVEL_MID => 'Middle',
        self::LEVEL_SENIOR => 'Senior',
        self::LEVEL_LEAD => 'Lead',
        self::LEVEL_MANAGER => 'Manager',
        self::LEVEL_DIRECTOR => 'Direktor',
    ];

    protected $fillable = [
        'business_id',
        'title',
        'department',
        'position_level',
        'reports_to',
        'job_summary',
        'responsibilities',
        'requirements',
        'qualifications',
        'skills',
        'salary_range_min',
        'salary_range_max',
        'employment_type',
        'location',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'salary_range_min' => 'decimal:2',
        'salary_range_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('position_level', $level);
    }

    // ==================== Accessors ====================

    public function getEmploymentTypeLabelAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? $this->employment_type;
    }

    public function getPositionLevelLabelAttribute(): ?string
    {
        return $this->position_level ? (self::POSITION_LEVELS[$this->position_level] ?? $this->position_level) : null;
    }

    public function getDepartmentLabelAttribute(): string
    {
        $departments = \App\Models\BusinessUser::DEPARTMENTS;
        return $departments[$this->department] ?? $this->department;
    }

    public function getSalaryRangeFormattedAttribute(): ?string
    {
        if (!$this->salary_range_min && !$this->salary_range_max) {
            return null;
        }

        if ($this->salary_range_min && $this->salary_range_max) {
            return number_format($this->salary_range_min, 0, '.', ' ') . ' - ' . number_format($this->salary_range_max, 0, '.', ' ') . ' UZS';
        }

        if ($this->salary_range_min) {
            return 'dan ' . number_format($this->salary_range_min, 0, '.', ' ') . ' UZS';
        }

        return 'gacha ' . number_format($this->salary_range_max, 0, '.', ' ') . ' UZS';
    }
}
