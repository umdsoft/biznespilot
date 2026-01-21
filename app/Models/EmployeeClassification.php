<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Classification based on book methodology:
 * - Thinker (Думатель): Independent, sees goal and finds way
 * - Doer (Делатель): Needs clear instructions, executes well
 * - Star employee tracking (dangerous dependencies)
 */
class EmployeeClassification extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'employee_type', // thinker, doer, mixed
        'position_fit',
        'position_fit_notes',
        // Star employee flags
        'is_star_employee',
        'has_unique_knowledge',
        'has_client_dependencies',
        'blocks_new_employees',
        // Risk
        'departure_risk',
        'replacement_difficulty',
        // Competencies
        'competency_scores',
        // Development
        'development_plan',
        'career_path_notes',
        'mentor_user_id',
        // Assessment
        'assessed_by',
        'assessed_at',
    ];

    protected $casts = [
        'position_fit' => 'boolean',
        'is_star_employee' => 'boolean',
        'has_unique_knowledge' => 'boolean',
        'has_client_dependencies' => 'boolean',
        'blocks_new_employees' => 'boolean',
        'replacement_difficulty' => 'decimal:2',
        'competency_scores' => 'array',
        'development_plan' => 'array',
        'assessed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_user_id');
    }

    public function assessedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    // Get employee type label
    public function getEmployeeTypeLabelAttribute(): string
    {
        return match($this->employee_type) {
            'thinker' => 'Думатель (Mustaqil qaror qiluvchi)',
            'doer' => 'Делатель (Ijrochi)',
            'mixed' => 'Aralash tip',
            default => 'Noma\'lum',
        };
    }

    // Get employee type description
    public function getEmployeeTypeDescriptionAttribute(): string
    {
        return match($this->employee_type) {
            'thinker' => 'Maqsadni ko\'rib, yo\'lni o\'zi topadi. Mustaqil qaror qabul qiladi. TOP lavozimlar uchun mos.',
            'doer' => 'Aniq ko\'rsatmalar bo\'yicha yaxshi ishlaydi. Liniya xodimlari uchun mos.',
            'mixed' => 'Vaziyatga qarab har ikki rolda ham ishlashi mumkin.',
            default => '',
        };
    }

    // Get star employee risk level
    public function getStarRiskLevelAttribute(): string
    {
        $riskFactors = 0;

        if ($this->has_unique_knowledge) $riskFactors++;
        if ($this->has_client_dependencies) $riskFactors++;
        if ($this->blocks_new_employees) $riskFactors++;

        return match($riskFactors) {
            0 => 'none',
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            default => 'none',
        };
    }

    // Get star employee warnings
    public function getStarWarningsAttribute(): array
    {
        $warnings = [];

        if ($this->has_unique_knowledge) {
            $warnings[] = [
                'type' => 'unique_knowledge',
                'message' => 'Bu xodim kompaniyada yagona bilimga ega',
                'severity' => 'high',
            ];
        }

        if ($this->has_client_dependencies) {
            $warnings[] = [
                'type' => 'client_dependency',
                'message' => 'Mijozlar bu xodimga bog\'liq',
                'severity' => 'high',
            ];
        }

        if ($this->blocks_new_employees) {
            $warnings[] = [
                'type' => 'blocks_newcomers',
                'message' => 'Yangi xodimlarning rivojlanishiga to\'sqinlik qiladi',
                'severity' => 'medium',
            ];
        }

        if (!$this->position_fit) {
            $warnings[] = [
                'type' => 'position_mismatch',
                'message' => 'Xodim tipi lavozimga mos emas',
                'severity' => 'medium',
            ];
        }

        return $warnings;
    }

    // Get departure risk color
    public function getDepartureRiskColorAttribute(): string
    {
        return match($this->departure_risk) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    // Get replacement difficulty label
    public function getReplacementDifficultyLabelAttribute(): string
    {
        $diff = $this->replacement_difficulty;

        if ($diff <= 1.5) return 'Oson';
        if ($diff <= 2.5) return 'O\'rtacha';
        if ($diff <= 3.5) return 'Qiyin';
        if ($diff <= 4.5) return 'Juda qiyin';
        return 'Deyarli imkonsiz';
    }

    // Get competency average
    public function getCompetencyAverageAttribute(): float
    {
        if (!$this->competency_scores) {
            return 0;
        }

        $scores = array_values($this->competency_scores);
        if (empty($scores)) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    // Determine if needs attention
    public function getNeedsAttentionAttribute(): bool
    {
        return $this->is_star_employee
            || $this->departure_risk === 'high'
            || $this->departure_risk === 'critical'
            || !$this->position_fit;
    }

    // Scopes
    public function scopeThinkers($query)
    {
        return $query->where('employee_type', 'thinker');
    }

    public function scopeDoers($query)
    {
        return $query->where('employee_type', 'doer');
    }

    public function scopeStarEmployees($query)
    {
        return $query->where('is_star_employee', true);
    }

    public function scopeAtRisk($query)
    {
        return $query->whereIn('departure_risk', ['high', 'critical']);
    }

    public function scopePositionMismatch($query)
    {
        return $query->where('position_fit', false);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function($q) {
            $q->where('is_star_employee', true)
              ->orWhereIn('departure_risk', ['high', 'critical'])
              ->orWhere('position_fit', false);
        });
    }
}
