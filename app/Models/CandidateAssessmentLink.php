<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CandidateAssessmentLink extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id', 'hr_survey_id', 'job_application_id', 'talent_pool_candidate_id',
        'candidate_email', 'candidate_name', 'token', 'status', 'response_id',
        'sent_at', 'completed_at', 'expires_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->token) {
                $model->token = Str::random(48);
            }
            if (! $model->expires_at) {
                $model->expires_at = now()->addDays(7);
            }
        });
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(HRSurvey::class, 'hr_survey_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function talentPoolCandidate(): BelongsTo
    {
        return $this->belongsTo(TalentPoolCandidate::class);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(HRSurveyResponse::class, 'response_id');
    }

    public function getPublicUrl(): string
    {
        return url("/assessment/{$this->token}");
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
