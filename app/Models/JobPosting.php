<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    use BelongsToBusiness, HasUuid;

    // Status constants
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_FILLED = 'filled';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_OPEN => 'Ochiq',
        self::STATUS_CLOSED => 'Yopilgan',
        self::STATUS_FILLED => 'To\'ldirilgan',
        self::STATUS_CANCELLED => 'Bekor qilingan',
    ];

    // Employment types
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

    protected $fillable = [
        'business_id',
        'job_description_id',
        'title',
        'department',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'location',
        'employment_type',
        'openings',
        'status',
        'posted_date',
        'closing_date',
        'posted_by',
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'posted_date' => 'date',
        'closing_date' => 'date',
    ];

    // ==================== Relationships ====================

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    // ==================== Scopes ====================

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeFilled($query)
    {
        return $query->where('status', self::STATUS_FILLED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where(function ($q) {
                $q->whereNull('closing_date')
                    ->orWhere('closing_date', '>=', now());
            });
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? $this->employment_type;
    }

    public function getDepartmentLabelAttribute(): string
    {
        $departments = \App\Models\BusinessUser::DEPARTMENTS;
        return $departments[$this->department] ?? $this->department;
    }

    public function getSalaryRangeFormattedAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        if ($this->salary_min && $this->salary_max) {
            return number_format($this->salary_min, 0, '.', ' ') . ' - ' . number_format($this->salary_max, 0, '.', ' ') . ' UZS';
        }

        if ($this->salary_min) {
            return 'dan ' . number_format($this->salary_min, 0, '.', ' ') . ' UZS';
        }

        return 'gacha ' . number_format($this->salary_max, 0, '.', ' ') . ' UZS';
    }

    public function getApplicationsCountAttribute(): int
    {
        return $this->applications()->count();
    }

    public function getNewApplicationsCountAttribute(): int
    {
        return $this->applications()->where('status', 'new')->count();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_OPEN &&
               (!$this->closing_date || $this->closing_date->isFuture());
    }

    public function getDaysUntilClosingAttribute(): ?int
    {
        if (!$this->closing_date) {
            return null;
        }

        return now()->diffInDays($this->closing_date, false);
    }
}
