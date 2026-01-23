<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorWeeklyKpi extends Model
{
    use HasFactory;

    protected $table = 'operator_weekly_kpis';

    protected $fillable = [
        'business_id',
        'user_id',
        'weekly_goal_id',
        'week_start',
        'week_end',
        'target_leads',
        'target_won',
        'target_revenue',
        'target_calls',
        'actual_leads',
        'actual_won',
        'actual_revenue',
        'actual_calls',
        'overall_score',
        'rank',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'target_revenue' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weeklyGoal(): BelongsTo
    {
        return $this->belongsTo(WeeklyGoal::class);
    }

    /**
     * Calculate overall score
     */
    public function calculateScore(): float
    {
        $weights = [
            'revenue' => 40,
            'won' => 30,
            'leads' => 20,
            'calls' => 10,
        ];

        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($weights as $metric => $weight) {
            $target = $this->{"target_{$metric}"};
            $actual = $this->{"actual_{$metric}"};

            if ($target > 0) {
                $achievement = min(($actual / $target) * 100, 150); // Cap at 150%
                $weightedScore += $achievement * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($weightedScore / $totalWeight, 2) : 0;
    }

    /**
     * Update score and save
     */
    public function updateScore(): self
    {
        $this->overall_score = $this->calculateScore();

        return $this;
    }

    /**
     * Update ranks for all operators in a business for a week
     */
    public static function updateRanks(string $businessId, $weekStart): void
    {
        $kpis = static::where('business_id', $businessId)
            ->where('week_start', $weekStart)
            ->orderByDesc('overall_score')
            ->get();

        $rank = 1;
        foreach ($kpis as $kpi) {
            $kpi->rank = $rank++;
            $kpi->save();
        }
    }

    /**
     * Get or create KPI for a user and week
     */
    public static function getOrCreateForWeek($businessId, $userId, $weekStart = null): self
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        return static::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'week_start' => $weekStart->format('Y-m-d'),
            ],
            [
                'week_end' => $weekEnd->format('Y-m-d'),
            ]
        );
    }
}
