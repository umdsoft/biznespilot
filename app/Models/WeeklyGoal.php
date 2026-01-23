<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'weekly_analytics_id',
        'week_start',
        'week_end',
        'target_leads',
        'target_won',
        'target_conversion',
        'target_revenue',
        'target_calls',
        'target_meetings',
        'actual_leads',
        'actual_won',
        'actual_conversion',
        'actual_revenue',
        'actual_calls',
        'actual_meetings',
        'leads_achievement',
        'won_achievement',
        'conversion_achievement',
        'revenue_achievement',
        'calls_achievement',
        'meetings_achievement',
        'overall_score',
        'status',
        'ai_suggested_goal',
        'ai_focus_areas',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'target_conversion' => 'decimal:2',
        'target_revenue' => 'decimal:2',
        'actual_conversion' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'leads_achievement' => 'decimal:2',
        'won_achievement' => 'decimal:2',
        'conversion_achievement' => 'decimal:2',
        'revenue_achievement' => 'decimal:2',
        'calls_achievement' => 'decimal:2',
        'meetings_achievement' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'ai_focus_areas' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function weeklyAnalytics(): BelongsTo
    {
        return $this->belongsTo(WeeklyAnalytics::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function operatorKpis(): HasMany
    {
        return $this->hasMany(OperatorWeeklyKpi::class);
    }

    /**
     * Get week label
     */
    public function getWeekLabelAttribute(): string
    {
        $start = $this->week_start;
        $end = $this->week_end;

        if ($start->month === $end->month) {
            return $start->day . '-' . $end->day . ' ' . $start->translatedFormat('F');
        }

        return $start->day . ' ' . $start->translatedFormat('F') . ' - ' . $end->day . ' ' . $end->translatedFormat('F');
    }

    /**
     * Calculate achievement percentage for a metric
     */
    public function calculateAchievement(string $metric): float
    {
        $target = $this->{"target_{$metric}"};
        $actual = $this->{"actual_{$metric}"};

        if ($target <= 0) {
            return 0;
        }

        return round(($actual / $target) * 100, 2);
    }

    /**
     * Update all achievement metrics
     */
    public function updateAchievements(): self
    {
        $this->leads_achievement = $this->calculateAchievement('leads');
        $this->won_achievement = $this->calculateAchievement('won');
        $this->conversion_achievement = $this->target_conversion > 0
            ? round(($this->actual_conversion / $this->target_conversion) * 100, 2)
            : 0;
        $this->revenue_achievement = $this->calculateAchievement('revenue');
        $this->calls_achievement = $this->calculateAchievement('calls');
        $this->meetings_achievement = $this->calculateAchievement('meetings');

        // Calculate overall score (weighted average)
        $weights = [
            'revenue' => 30,
            'won' => 25,
            'conversion' => 20,
            'leads' => 15,
            'calls' => 5,
            'meetings' => 5,
        ];

        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($weights as $metric => $weight) {
            $achievement = $this->{"{$metric}_achievement"};
            if ($achievement > 0) {
                $weightedScore += min($achievement, 150) * $weight; // Cap at 150%
                $totalWeight += $weight;
            }
        }

        $this->overall_score = $totalWeight > 0
            ? round($weightedScore / $totalWeight, 2)
            : 0;

        return $this;
    }

    /**
     * Determine status based on date and achievements
     */
    public function updateStatus(): self
    {
        $today = now()->startOfDay();

        if ($today < $this->week_start) {
            $this->status = 'pending';
        } elseif ($today >= $this->week_start && $today <= $this->week_end) {
            $this->status = 'in_progress';
        } else {
            // Week is over
            $this->status = $this->overall_score >= 80 ? 'completed' : 'missed';
        }

        return $this;
    }

    /**
     * Scope for current week
     */
    public function scopeCurrentWeek($query)
    {
        $weekStart = now()->startOfWeek();

        return $query->where('week_start', $weekStart->format('Y-m-d'));
    }

    /**
     * Scope for specific business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Get or create goal for a specific week
     */
    public static function getOrCreateForWeek($businessId, $weekStart = null): self
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        return static::firstOrCreate(
            [
                'business_id' => $businessId,
                'week_start' => $weekStart->format('Y-m-d'),
            ],
            [
                'week_end' => $weekEnd->format('Y-m-d'),
            ]
        );
    }
}
