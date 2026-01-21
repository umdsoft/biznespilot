<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EmployeeEngagement - Hodim engagement (ishtirok) darajasi
 *
 * Gallup Q12 va boshqa metodologiyalar asosida engagement tracking
 */
class EmployeeEngagement extends Model
{
    use HasUuid;

    protected $table = 'employee_engagements';

    protected $fillable = [
        'business_id',
        'user_id',
        'period',
        // Gallup Q12 komponentlari
        'work_satisfaction',
        'team_collaboration',
        'growth_opportunities',
        'recognition_frequency',
        'manager_support',
        'work_life_balance',
        'purpose_clarity',
        'resources_adequacy',
        // Umumiy ko'rsatkichlar
        'overall_score',
        'engagement_level',
        'previous_score',
        'score_change',
        'trend',
        // Tarix
        'score_history',
        'q12_responses',
        'last_survey_at',
        'last_boosted_at',
    ];

    protected $casts = [
        'work_satisfaction' => 'float',
        'team_collaboration' => 'float',
        'growth_opportunities' => 'float',
        'recognition_frequency' => 'float',
        'manager_support' => 'float',
        'work_life_balance' => 'float',
        'purpose_clarity' => 'float',
        'resources_adequacy' => 'float',
        'overall_score' => 'float',
        'previous_score' => 'float',
        'score_change' => 'float',
        'score_history' => 'array',
        'q12_responses' => 'array',
        'last_survey_at' => 'datetime',
        'last_boosted_at' => 'datetime',
    ];

    // Engagement darajalari
    public const LEVEL_CRITICAL = 'critical';     // 0-30
    public const LEVEL_LOW = 'low';               // 31-50
    public const LEVEL_MODERATE = 'moderate';     // 51-70
    public const LEVEL_HIGH = 'high';             // 71-85
    public const LEVEL_EXCELLENT = 'excellent';   // 86-100

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
    public function scopeHighEngagement($query)
    {
        return $query->where('overall_score', '>=', 70);
    }

    public function scopeLowEngagement($query)
    {
        return $query->where('overall_score', '<', 50);
    }

    public function scopeCritical($query)
    {
        return $query->where('overall_score', '<=', 30);
    }

    // Accessors
    public function getLevelAttribute(): string
    {
        return match(true) {
            $this->overall_score <= 30 => self::LEVEL_CRITICAL,
            $this->overall_score <= 50 => self::LEVEL_LOW,
            $this->overall_score <= 70 => self::LEVEL_MODERATE,
            $this->overall_score <= 85 => self::LEVEL_HIGH,
            default => self::LEVEL_EXCELLENT,
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            self::LEVEL_CRITICAL => 'Jiddiy past',
            self::LEVEL_LOW => 'Past',
            self::LEVEL_MODERATE => "O'rtacha",
            self::LEVEL_HIGH => 'Yuqori',
            self::LEVEL_EXCELLENT => "A'lo",
        };
    }

    public function getLevelColorAttribute(): string
    {
        return match($this->level) {
            self::LEVEL_CRITICAL => 'red',
            self::LEVEL_LOW => 'orange',
            self::LEVEL_MODERATE => 'yellow',
            self::LEVEL_HIGH => 'green',
            self::LEVEL_EXCELLENT => 'emerald',
        };
    }

    // Methods
    public function requiresAttention(): bool
    {
        return $this->overall_score < 50;
    }

    public function isAtRisk(): bool
    {
        return $this->overall_score < 40;
    }

    /**
     * Gallup Q12 asosida ball taqsimoti
     */
    public function getScoreBreakdown(): array
    {
        return [
            'work_satisfaction' => [
                'score' => $this->work_satisfaction,
                'weight' => 0.15,
                'label' => 'Ish qoniqishi',
                'description' => 'Kundalik vazifalardan qoniqish',
            ],
            'team_collaboration' => [
                'score' => $this->team_collaboration,
                'weight' => 0.125,
                'label' => 'Jamoa hamkorligi',
                'description' => 'Hamkasblar bilan munosabat',
            ],
            'growth_opportunities' => [
                'score' => $this->growth_opportunities,
                'weight' => 0.15,
                'label' => "O'sish imkoniyatlari",
                'description' => 'Karyera rivojlanishi',
            ],
            'recognition_frequency' => [
                'score' => $this->recognition_frequency,
                'weight' => 0.125,
                'label' => "Tan olish",
                'description' => 'Yutuqlarning e\'tirof etilishi',
            ],
            'manager_support' => [
                'score' => $this->manager_support,
                'weight' => 0.15,
                'label' => "Rahbar qo'llab-quvvatlashi",
                'description' => 'Menejerdan qo\'llab-quvvatlash',
            ],
            'work_life_balance' => [
                'score' => $this->work_life_balance,
                'weight' => 0.10,
                'label' => 'Ish-hayot balansi',
                'description' => 'Shaxsiy hayot va ish muvozanati',
            ],
            'purpose_clarity' => [
                'score' => $this->purpose_clarity,
                'weight' => 0.10,
                'label' => 'Maqsad aniqligi',
                'description' => 'Kompaniya missiyasini tushunish',
            ],
            'resources_adequacy' => [
                'score' => $this->resources_adequacy,
                'weight' => 0.10,
                'label' => 'Resurslar yetarliligi',
                'description' => 'Ishni bajarish uchun kerakli vositalar',
            ],
        ];
    }

    /**
     * Umumiy ballni hisoblash
     */
    public function calculateOverallScore(): float
    {
        $breakdown = $this->getScoreBreakdown();
        $score = 0;

        foreach ($breakdown as $component) {
            $score += $component['score'] * $component['weight'];
        }

        return round($score, 2);
    }

    /**
     * Trend ni aniqlash
     */
    public function getTrendStatus(): string
    {
        if ($this->score_change >= 5) {
            return 'improving';
        } elseif ($this->score_change <= -5) {
            return 'declining';
        }
        return 'stable';
    }

    public function recordScoreChange(float $oldScore, float $newScore, string $reason): void
    {
        $history = $this->score_history ?? [];
        $history[] = [
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'reason' => $reason,
            'recorded_at' => now()->toISOString(),
        ];

        // Oxirgi 50 ta yozuvni saqlash
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        $this->update(['score_history' => $history]);
    }
}
