<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyAnalytics extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $table = 'weekly_analytics';

    protected $fillable = [
        'business_id',
        'week_start',
        'week_end',
        'summary_stats',
        'channel_stats',
        'operator_stats',
        'time_stats',
        'lost_reason_stats',
        'trend_stats',
        // Extended stats
        'regional_stats',
        'qualification_stats',
        'call_stats',
        'task_stats',
        'pipeline_stats',
        // AI analysis
        'ai_good_results',
        'ai_problems',
        'ai_recommendations',
        'ai_next_week_goal',
        'ai_raw_response',
        'tokens_used',
        'generated_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'summary_stats' => 'array',
        'channel_stats' => 'array',
        'operator_stats' => 'array',
        'time_stats' => 'array',
        'lost_reason_stats' => 'array',
        'trend_stats' => 'array',
        // Extended stats
        'regional_stats' => 'array',
        'qualification_stats' => 'array',
        'call_stats' => 'array',
        'task_stats' => 'array',
        'pipeline_stats' => 'array',
        // AI analysis
        'ai_good_results' => 'array',
        'ai_problems' => 'array',
        'ai_recommendations' => 'array',
        'generated_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the current week's analytics for a business
     */
    public static function getCurrentWeek(string $businessId): ?self
    {
        $weekStart = now()->startOfWeek();

        return self::where('business_id', $businessId)
            ->where('week_start', $weekStart->format('Y-m-d'))
            ->first();
    }

    /**
     * Get the last N weeks of analytics
     */
    public static function getLastWeeks(string $businessId, int $count = 4): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('business_id', $businessId)
            ->orderBy('week_start', 'desc')
            ->limit($count)
            ->get();
    }

    /**
     * Check if AI analysis has been generated
     */
    public function hasAiAnalysis(): bool
    {
        return ! empty($this->ai_good_results) || ! empty($this->ai_problems);
    }

    /**
     * Get week label (e.g., "13-19 Yanvar")
     */
    public function getWeekLabelAttribute(): string
    {
        $months = [
            1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
            5 => 'May', 6 => 'Iyun', 7 => 'Iyul', 8 => 'Avgust',
            9 => 'Sentabr', 10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr',
        ];

        $startDay = $this->week_start->day;
        $endDay = $this->week_end->day;
        $month = $months[$this->week_start->month];

        if ($this->week_start->month !== $this->week_end->month) {
            $endMonth = $months[$this->week_end->month];

            return "{$startDay} {$month} - {$endDay} {$endMonth}";
        }

        return "{$startDay}-{$endDay} {$month}";
    }
}
