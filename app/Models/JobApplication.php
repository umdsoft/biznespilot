<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    use BelongsToBusiness, HasUuid;

    // Status constants
    public const STATUS_NEW = 'new';
    public const STATUS_SCREENING = 'screening';
    public const STATUS_INTERVIEWING = 'interviewing';
    public const STATUS_OFFER = 'offer';
    public const STATUS_HIRED = 'hired';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_NEW => 'Yangi',
        self::STATUS_SCREENING => 'Ko\'rib chiqilmoqda',
        self::STATUS_INTERVIEWING => 'Intervyu',
        self::STATUS_OFFER => 'Taklif',
        self::STATUS_HIRED => 'Ishga qabul qilindi',
        self::STATUS_REJECTED => 'Rad etildi',
    ];

    protected $fillable = [
        'business_id',
        'job_posting_id',
        'candidate_name',
        'candidate_email',
        'candidate_phone',
        'resume_path',
        'cover_letter',
        'linkedin_url',
        'portfolio_url',
        'years_of_experience',
        'current_company',
        'expected_salary',
        'status',
        'notes',
        'rating',
        'assigned_to',
        'applied_at',
    ];

    protected $casts = [
        'expected_salary' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ==================== Scopes ====================

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeScreening($query)
    {
        return $query->where('status', self::STATUS_SCREENING);
    }

    public function scopeInterviewing($query)
    {
        return $query->where('status', self::STATUS_INTERVIEWING);
    }

    public function scopeHired($query)
    {
        return $query->where('status', self::STATUS_HIRED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_NEW => 'blue',
            self::STATUS_SCREENING => 'yellow',
            self::STATUS_INTERVIEWING => 'purple',
            self::STATUS_OFFER => 'green',
            self::STATUS_HIRED => 'emerald',
            self::STATUS_REJECTED => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
