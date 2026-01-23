<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorCallStats extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'operator_call_stats';

    protected $fillable = [
        'business_id',
        'user_id',
        'period_type',
        'period_date',
        'total_calls',
        'analyzed_calls',
        'successful_calls',
        'missed_calls',
        'avg_score',
        'min_score',
        'max_score',
        'avg_stage_scores',
        'total_anti_patterns',
        'anti_pattern_counts',
        'total_duration_seconds',
        'avg_duration_seconds',
        'total_analysis_cost',
        'score_change',
        'score_change_percent',
    ];

    protected $casts = [
        'period_date' => 'date',
        'avg_score' => 'decimal:2',
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'avg_stage_scores' => 'array',
        'anti_pattern_counts' => 'array',
        'total_analysis_cost' => 'decimal:6',
        'score_change' => 'decimal:2',
        'score_change_percent' => 'decimal:2',
    ];

    public const PERIOD_DAILY = 'daily';
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';

    /**
     * Get the operator (user)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship
     */
    public function operator(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Scope by period type
     */
    public function scopePeriodType($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_date', [$startDate, $endDate]);
    }

    /**
     * Scope for daily stats
     */
    public function scopeDaily($query)
    {
        return $query->where('period_type', self::PERIOD_DAILY);
    }

    /**
     * Scope for weekly stats
     */
    public function scopeWeekly($query)
    {
        return $query->where('period_type', self::PERIOD_WEEKLY);
    }

    /**
     * Scope for monthly stats
     */
    public function scopeMonthly($query)
    {
        return $query->where('period_type', self::PERIOD_MONTHLY);
    }

    /**
     * Get formatted average score
     */
    public function getFormattedAvgScoreAttribute(): string
    {
        return $this->avg_score !== null
            ? number_format($this->avg_score, 1) . '/100'
            : '-';
    }

    /**
     * Get score trend indicator
     */
    public function getScoreTrendAttribute(): string
    {
        if ($this->score_change === null) {
            return 'neutral';
        }

        return $this->score_change > 0 ? 'up' : ($this->score_change < 0 ? 'down' : 'neutral');
    }

    /**
     * Get score color based on average
     */
    public function getScoreColorAttribute(): string
    {
        if ($this->avg_score === null) {
            return 'gray';
        }

        return match (true) {
            $this->avg_score >= 80 => 'green',
            $this->avg_score >= 60 => 'yellow',
            $this->avg_score >= 40 => 'orange',
            default => 'red',
        };
    }

    /**
     * Get score label
     */
    public function getScoreLabelAttribute(): string
    {
        if ($this->avg_score === null) {
            return 'Baholanmagan';
        }

        return match (true) {
            $this->avg_score >= 80 => 'A\'lo',
            $this->avg_score >= 60 => 'Yaxshi',
            $this->avg_score >= 40 => 'O\'rtacha',
            default => 'Yaxshilash kerak',
        };
    }

    /**
     * Get analysis rate (percentage of calls analyzed)
     */
    public function getAnalysisRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }

        return round(($this->analyzed_calls / $this->total_calls) * 100, 1);
    }

    /**
     * Get success rate (percentage of successful calls)
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }

        return round(($this->successful_calls / $this->total_calls) * 100, 1);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedTotalDurationAttribute(): string
    {
        $hours = floor($this->total_duration_seconds / 3600);
        $minutes = floor(($this->total_duration_seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}s {$minutes}d";
        }

        return "{$minutes} daqiqa";
    }

    /**
     * Get formatted average duration
     */
    public function getFormattedAvgDurationAttribute(): string
    {
        $minutes = floor($this->avg_duration_seconds / 60);
        $seconds = $this->avg_duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted cost in UZS
     */
    public function getFormattedCostAttribute(): string
    {
        $uzsRate = config('call-center.currency.usd_to_uzs', 12800);
        $costUzs = $this->total_analysis_cost * $uzsRate;

        return number_format($costUzs, 0, '.', ' ') . ' so\'m';
    }
}
