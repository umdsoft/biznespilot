<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FlightRisk - Hodim ketish xavfi
 *
 * Retention strategiyasi uchun flight risk tracking
 */
class FlightRisk extends Model
{
    use HasUuid;

    protected $table = 'flight_risks';

    protected $fillable = [
        'business_id',
        'user_id',
        'risk_score',
        'risk_level',
        'risk_factors',
        // Risk faktorlari
        'engagement_factor',
        'tenure_factor',
        'compensation_factor',
        'growth_factor',
        'workload_factor',
        'recognition_factor',
        // Tavsiyalar va harakatlar
        'recommended_actions',
        'actions_taken',
        // Stay interview
        'stay_interview_scheduled',
        'stay_interview_date',
        'stay_interview_notes',
        // Tarix
        'previous_level',
        'level_changed_at',
        'level_history',
    ];

    protected $casts = [
        'risk_score' => 'float',
        'risk_factors' => 'array',
        'engagement_factor' => 'float',
        'tenure_factor' => 'float',
        'compensation_factor' => 'float',
        'growth_factor' => 'float',
        'workload_factor' => 'float',
        'recognition_factor' => 'float',
        'recommended_actions' => 'array',
        'actions_taken' => 'array',
        'stay_interview_scheduled' => 'boolean',
        'stay_interview_date' => 'date',
        'level_changed_at' => 'datetime',
        'level_history' => 'array',
    ];

    // Risk darajalari
    public const LEVEL_LOW = 'low';           // 0-25
    public const LEVEL_MODERATE = 'moderate'; // 26-50
    public const LEVEL_HIGH = 'high';         // 51-75
    public const LEVEL_CRITICAL = 'critical'; // 76-100

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    public function scopeCritical($query)
    {
        return $query->where('risk_level', self::LEVEL_CRITICAL);
    }

    public function scopeLowRisk($query)
    {
        return $query->where('risk_level', self::LEVEL_LOW);
    }

    public function scopeNeedsStayInterview($query)
    {
        return $query->highRisk()
            ->where(function ($q) {
                $q->where('stay_interview_scheduled', false)
                  ->orWhereNull('stay_interview_scheduled');
            });
    }

    // Accessors
    public function getLevelLabelAttribute(): string
    {
        return match($this->risk_level) {
            self::LEVEL_LOW => 'Past xavf',
            self::LEVEL_MODERATE => "O'rtacha xavf",
            self::LEVEL_HIGH => 'Yuqori xavf',
            self::LEVEL_CRITICAL => 'Jiddiy xavf',
            default => "Noma'lum",
        };
    }

    public function getLevelColorAttribute(): string
    {
        return match($this->risk_level) {
            self::LEVEL_LOW => 'green',
            self::LEVEL_MODERATE => 'yellow',
            self::LEVEL_HIGH => 'orange',
            self::LEVEL_CRITICAL => 'red',
            default => 'gray',
        };
    }

    public function getTopRiskFactorsAttribute(): array
    {
        if (empty($this->risk_factors)) {
            return [];
        }

        return collect($this->risk_factors)
            ->sortByDesc('score')
            ->take(3)
            ->values()
            ->toArray();
    }

    // Methods
    public function requiresImmediateAction(): bool
    {
        return in_array($this->risk_level, [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    public function getRecommendedActions(): array
    {
        return match($this->risk_level) {
            self::LEVEL_CRITICAL => [
                ['action' => 'stay_interview', 'label' => "Zudlik bilan suhbat o'tkazish", 'priority' => 'urgent'],
                ['action' => 'salary_review', 'label' => "Maoshni ko'rib chiqish", 'priority' => 'high'],
                ['action' => 'manager_meeting', 'label' => 'Rahbar bilan uchrashuv', 'priority' => 'high'],
            ],
            self::LEVEL_HIGH => [
                ['action' => 'one_on_one', 'label' => '1-on-1 suhbat tayinlash', 'priority' => 'high'],
                ['action' => 'career_discussion', 'label' => 'Karyera rivojlanishini muhokama', 'priority' => 'medium'],
                ['action' => 'engagement_check', 'label' => 'Engagement holatini tekshirish', 'priority' => 'medium'],
            ],
            self::LEVEL_MODERATE => [
                ['action' => 'pulse_check', 'label' => "Qisqa so'rovnoma yuborish", 'priority' => 'medium'],
                ['action' => 'recognition', 'label' => 'Minnatdorchilik bildirish', 'priority' => 'low'],
            ],
            default => [],
        };
    }

    public function scheduleStayInterview(\DateTime $date): bool
    {
        return $this->update([
            'stay_interview_scheduled' => true,
            'stay_interview_date' => $date,
        ]);
    }

    public function recordStayInterview(string $notes): bool
    {
        return $this->update([
            'stay_interview_notes' => $notes,
            'stay_interview_scheduled' => false,
        ]);
    }
}
