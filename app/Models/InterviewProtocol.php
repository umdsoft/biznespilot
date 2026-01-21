<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interview Protocol based on book methodology:
 * - "Biladi" (Knows) assessment - theoretical knowledge
 * - "Uddalaydi" (Can do) assessment - practical skills
 * - Employee type assessment (Thinker vs Doer)
 * - Goal alignment check
 */
class InterviewProtocol extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'vacancy_card_id',
        'candidate_id',
        'candidate_name',
        'candidate_phone',
        'candidate_email',
        'interview_date',
        'interviewer_id',
        'interview_type',
        // "Biladi" (Knows) assessment
        'knowledge_score',
        'knowledge_details',
        // "Uddalaydi" (Can do) assessment
        'skills_score',
        'skills_details',
        // Employee type
        'assessed_employee_type',
        'employee_type_notes',
        // Goals alignment
        'candidate_goals',
        'goals_aligned',
        // Overall
        'overall_score',
        'recommendation',
        'strengths',
        'weaknesses',
        'notes',
        // Trial suggestion
        'suggested_trial_days',
        'suggested_trial_kpis',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'knowledge_details' => 'array',
        'skills_details' => 'array',
        'goals_aligned' => 'boolean',
        'suggested_trial_kpis' => 'array',
    ];

    // Relationships
    public function vacancyCard(): BelongsTo
    {
        return $this->belongsTo(VacancyCard::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    // Get overall assessment
    public function getAverageScoreAttribute(): float
    {
        $scores = array_filter([$this->knowledge_score, $this->skills_score]);
        if (empty($scores)) return 0;

        return round(array_sum($scores) / count($scores), 2);
    }

    // Get knowledge score label
    public function getKnowledgeScoreLabelAttribute(): string
    {
        return $this->getScoreLabel($this->knowledge_score);
    }

    // Get skills score label
    public function getSkillsScoreLabelAttribute(): string
    {
        return $this->getScoreLabel($this->skills_score);
    }

    // Get overall score label
    public function getOverallScoreLabelAttribute(): string
    {
        return $this->getScoreLabel($this->overall_score);
    }

    protected function getScoreLabel(?int $score): string
    {
        return match($score) {
            5 => 'A\'lo',
            4 => 'Yaxshi',
            3 => 'Qoniqarli',
            2 => 'Qoniqarsiz',
            1 => 'Yomon',
            default => 'Baholanmagan',
        };
    }

    // Get recommendation color
    public function getRecommendationColorAttribute(): string
    {
        return match($this->recommendation) {
            'strong_hire' => 'green',
            'hire' => 'blue',
            'maybe' => 'yellow',
            'no_hire' => 'red',
            default => 'gray',
        };
    }

    // Get recommendation label
    public function getRecommendationLabelAttribute(): string
    {
        return match($this->recommendation) {
            'strong_hire' => 'Albatta qabul qilish',
            'hire' => 'Qabul qilish tavsiya etiladi',
            'maybe' => 'Qayta ko\'rib chiqish',
            'no_hire' => 'Qabul qilmaslik',
            default => 'Qaror kutilmoqda',
        };
    }

    // Get employee type label
    public function getAssessedEmployeeTypeLabelAttribute(): string
    {
        return match($this->assessed_employee_type) {
            'thinker' => 'Думатель (Mustaqil)',
            'doer' => 'Делатель (Ijrochi)',
            'mixed' => 'Aralash',
            default => 'Aniqlanmagan',
        };
    }

    // Check if candidate fits vacancy type
    public function getTypeMatchesVacancyAttribute(): ?bool
    {
        if (!$this->vacancyCard || !$this->assessed_employee_type) {
            return null;
        }

        $neededType = $this->vacancyCard->employee_type_needed;

        if ($neededType === 'mixed') {
            return true;
        }

        return $this->assessed_employee_type === $neededType;
    }

    // Scopes
    public function scopeForVacancy($query, string $vacancyId)
    {
        return $query->where('vacancy_card_id', $vacancyId);
    }

    public function scopeRecommendedToHire($query)
    {
        return $query->whereIn('recommendation', ['strong_hire', 'hire']);
    }

    public function scopeByInterviewer($query, string $userId)
    {
        return $query->where('interviewer_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('interview_date', '>=', now()->subDays($days));
    }
}
