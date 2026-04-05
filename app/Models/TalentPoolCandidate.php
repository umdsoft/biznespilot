<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TalentPoolCandidate extends Model
{
    use BelongsToBusiness, HasUuids;

    const STATUS_AVAILABLE = 'available';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_NOT_INTERESTED = 'not_interested';
    const STATUS_HIRED = 'hired';
    const STATUS_ARCHIVED = 'archived';

    const STATUSES = [
        self::STATUS_AVAILABLE => 'Mavjud',
        self::STATUS_CONTACTED => "Bog'lanildi",
        self::STATUS_NOT_INTERESTED => 'Qiziqmaydi',
        self::STATUS_HIRED => 'Ishga olindi',
        self::STATUS_ARCHIVED => 'Arxivlangan',
    ];

    protected $fillable = [
        'business_id', 'job_application_id', 'candidate_name', 'candidate_email',
        'candidate_phone', 'resume_path', 'linkedin_url', 'portfolio_url',
        'years_of_experience', 'current_company', 'skills', 'tags', 'employee_type',
        'rating', 'status', 'source', 'source_vacancy_id', 'notes',
        'assessment_summary', 'expected_salary', 'preferred_position',
        'preferred_department', 'added_by', 'last_contacted_at',
    ];

    protected $casts = [
        'skills' => 'array',
        'tags' => 'array',
        'assessment_summary' => 'array',
        'expected_salary' => 'decimal:2',
        'rating' => 'integer',
        'last_contacted_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TalentPoolNote::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeBySkill($query, string $skill)
    {
        return $query->whereJsonContains('skills', $skill);
    }

    public function scopeByMinRating($query, int $min)
    {
        return $query->where('rating', '>=', $min);
    }

    public function scopeByEmployeeType($query, string $type)
    {
        return $query->where('employee_type', $type);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'emerald',
            self::STATUS_CONTACTED => 'blue',
            self::STATUS_NOT_INTERESTED => 'gray',
            self::STATUS_HIRED => 'purple',
            self::STATUS_ARCHIVED => 'gray',
            default => 'gray',
        };
    }
}
