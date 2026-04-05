<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEvaluation extends Model
{
    use BelongsToBusiness, HasUuids;

    const REC_HIRE = 'hire';
    const REC_MAYBE = 'maybe';
    const REC_REJECT = 'reject';

    protected $fillable = [
        'business_id', 'job_application_id', 'evaluator_id', 'evaluation_type',
        'technical_skills', 'communication_skills', 'problem_solving', 'cultural_fit',
        'overall_rating', 'strengths', 'weaknesses', 'comments', 'recommendation',
    ];

    protected $casts = [
        'technical_skills' => 'integer',
        'communication_skills' => 'integer',
        'problem_solving' => 'integer',
        'cultural_fit' => 'integer',
        'overall_rating' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function getAverageScoreAttribute(): float
    {
        $scores = array_filter([
            $this->technical_skills,
            $this->communication_skills,
            $this->problem_solving,
            $this->cultural_fit,
        ]);

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    }

    public function getRecommendationLabelAttribute(): string
    {
        return match ($this->recommendation) {
            self::REC_HIRE => 'Ishga olish',
            self::REC_MAYBE => 'Qayta ko\'rib chiqish',
            self::REC_REJECT => 'Rad etish',
            default => $this->recommendation ?? '',
        };
    }
}
