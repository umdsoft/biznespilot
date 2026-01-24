<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiMonthlySummary extends Model
{
    use HasFactory, SoftDeletes, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'kpi_code',
        'month',
        'year',
        'month_number',
        'month_name',
        'month_name_uz',
        'planned_value',
        'actual_value',
        'unit',
        'aggregation_method',
        'achievement_percentage',
        'variance',
        'variance_percentage',
        'status',
        'target_met',
        'weekly_breakdown',
        'weekly_average',
        'weekly_median',
        'weekly_min',
        'weekly_max',
        'weekly_std_deviation',
        'daily_average',
        'daily_min',
        'daily_max',
        'trend',
        'trend_percentage',
        'trend_notes',
        'weekly_trend_pattern',
        'best_week_start',
        'best_week_value',
        'worst_week_start',
        'worst_week_value',
        'best_day_date',
        'best_day_value',
        'worst_day_date',
        'worst_day_value',
        'total_weeks',
        'completed_weeks',
        'green_weeks_count',
        'yellow_weeks_count',
        'red_weeks_count',
        'weeks_on_target_percentage',
        'total_days',
        'completed_days',
        'green_days_count',
        'yellow_days_count',
        'red_days_count',
        'days_on_target_percentage',
        'is_month_complete',
        'completion_percentage',
        'total_financial_impact',
        'average_weekly_impact',
        'average_daily_impact',
        'previous_month_id',
        'vs_previous_month_change',
        'vs_previous_month_status',
        'same_month_last_year_id',
        'vs_last_year_change',
        'vs_last_year_status',
        'quarter',
        'quarter_progress_percentage',
        'seasonality_index',
        'is_peak_season',
        'is_low_season',
        'performance_summary',
        'insights',
        'recommendations',
        'key_learnings',
        'top_achievements',
        'top_failures',
        'next_month_focus_areas',
        'recommended_next_month_target',
        'has_anomalies',
        'anomaly_days_count',
        'is_partial_month',
        'data_quality_score',
        'verified_days_count',
        'verified_data_percentage',
        'pdf_report_generated',
        'pdf_report_path',
        'report_generated_at',
        'calculated_at',
        'calculated_by_job_id',
    ];

    protected $casts = [
        'month' => 'date',
        'planned_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'target_met' => 'boolean',
        'weekly_breakdown' => 'array',
        'weekly_average' => 'decimal:2',
        'weekly_median' => 'decimal:2',
        'weekly_min' => 'decimal:2',
        'weekly_max' => 'decimal:2',
        'weekly_std_deviation' => 'decimal:2',
        'daily_average' => 'decimal:2',
        'daily_min' => 'decimal:2',
        'daily_max' => 'decimal:2',
        'trend_percentage' => 'decimal:2',
        'weekly_trend_pattern' => 'array',
        'best_week_start' => 'date',
        'best_week_value' => 'decimal:2',
        'worst_week_start' => 'date',
        'worst_week_value' => 'decimal:2',
        'best_day_date' => 'date',
        'best_day_value' => 'decimal:2',
        'worst_day_date' => 'date',
        'worst_day_value' => 'decimal:2',
        'weeks_on_target_percentage' => 'decimal:2',
        'days_on_target_percentage' => 'decimal:2',
        'is_month_complete' => 'boolean',
        'completion_percentage' => 'decimal:2',
        'total_financial_impact' => 'decimal:2',
        'average_weekly_impact' => 'decimal:2',
        'average_daily_impact' => 'decimal:2',
        'vs_previous_month_change' => 'decimal:2',
        'vs_last_year_change' => 'decimal:2',
        'quarter_progress_percentage' => 'decimal:2',
        'seasonality_index' => 'decimal:2',
        'is_peak_season' => 'boolean',
        'is_low_season' => 'boolean',
        'insights' => 'array',
        'recommendations' => 'array',
        'key_learnings' => 'array',
        'top_achievements' => 'array',
        'top_failures' => 'array',
        'next_month_focus_areas' => 'array',
        'recommended_next_month_target' => 'decimal:2',
        'has_anomalies' => 'boolean',
        'is_partial_month' => 'boolean',
        'data_quality_score' => 'decimal:2',
        'verified_data_percentage' => 'decimal:2',
        'pdf_report_generated' => 'boolean',
        'report_generated_at' => 'datetime',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function kpiTemplate()
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_code', 'kpi_code');
    }

    public function weeklySummaries()
    {
        return $this->hasMany(KpiWeeklySummary::class, 'monthly_summary_id');
    }

    public function dailyActuals()
    {
        return $this->hasMany(KpiDailyActual::class, 'monthly_summary_id');
    }

    public function previousMonth()
    {
        return $this->belongsTo(KpiMonthlySummary::class, 'previous_month_id');
    }

    public function nextMonth()
    {
        return $this->hasOne(KpiMonthlySummary::class, 'previous_month_id');
    }

    public function sameMonthLastYear()
    {
        return $this->belongsTo(KpiMonthlySummary::class, 'same_month_last_year_id');
    }

    /**
     * Scopes
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForKpi($query, string $kpiCode)
    {
        return $query->where('kpi_code', $kpiCode);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month_number', $month);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForQuarter($query, int $year, int $quarter)
    {
        return $query->where('year', $year)->where('quarter', $quarter);
    }

    public function scopeComplete($query)
    {
        return $query->where('is_month_complete', true);
    }

    public function scopeGreen($query)
    {
        return $query->where('status', 'green');
    }

    public function scopeTargetMet($query)
    {
        return $query->where('target_met', true);
    }

    /**
     * Get weekly summaries for this month
     */
    public function getWeeklySummariesData()
    {
        return KpiWeeklySummary::where('business_id', $this->business_id)
            ->where('kpi_code', $this->kpi_code)
            ->whereYear('week_start_date', $this->year)
            ->whereMonth('week_start_date', $this->month_number)
            ->orderBy('week_start_date')
            ->get();
    }

    /**
     * Aggregate from weekly summaries
     */
    public function aggregateFromWeekly(): void
    {
        $weeklyData = $this->getWeeklySummariesData();

        if ($weeklyData->isEmpty()) {
            $this->actual_value = 0;
            $this->achievement_percentage = 0;
            $this->status = 'grey';

            return;
        }

        // Calculate based on aggregation method
        switch ($this->aggregation_method) {
            case 'sum':
                $this->actual_value = $weeklyData->sum('actual_value');
                break;
            case 'average':
                $this->actual_value = $weeklyData->avg('actual_value');
                break;
            case 'min':
                $this->actual_value = $weeklyData->min('actual_value');
                break;
            case 'max':
                $this->actual_value = $weeklyData->max('actual_value');
                break;
            case 'last':
                $this->actual_value = $weeklyData->last()->actual_value;
                break;
        }

        // Calculate achievement
        if ($this->planned_value > 0) {
            $this->achievement_percentage = ($this->actual_value / $this->planned_value) * 100;
        }

        // Calculate variance
        $this->variance = $this->actual_value - $this->planned_value;
        $this->variance_percentage = $this->planned_value > 0
            ? ($this->variance / $this->planned_value) * 100
            : 0;

        // Calculate status
        $template = $this->kpiTemplate;
        if ($template) {
            $this->status = $template->calculateStatus($this->achievement_percentage);
        }

        $this->target_met = $this->achievement_percentage >= 90;

        // Calculate statistics
        $this->calculateStatistics($weeklyData);

        // Build weekly breakdown
        $this->buildWeeklyBreakdown($weeklyData);

        // Update completion info
        $this->completed_weeks = $weeklyData->count();
        $this->completion_percentage = ($this->completed_weeks / $this->total_weeks) * 100;
        $this->is_month_complete = $this->completion_percentage >= 90;

        // Count statuses
        $this->green_weeks_count = $weeklyData->where('status', 'green')->count();
        $this->yellow_weeks_count = $weeklyData->where('status', 'yellow')->count();
        $this->red_weeks_count = $weeklyData->where('status', 'red')->count();

        $this->weeks_on_target_percentage = $this->completed_weeks > 0
            ? ($this->green_weeks_count / $this->completed_weeks) * 100
            : 0;

        // Aggregate daily stats
        $this->aggregateDailyStats($weeklyData);

        // Check anomalies
        $this->has_anomalies = $weeklyData->where('has_anomalies', true)->count() > 0;
        $this->anomaly_days_count = $weeklyData->sum('anomaly_days_count');

        // Verified days
        $this->verified_days_count = $weeklyData->sum('verified_days_count');
        $this->verified_data_percentage = $this->completed_days > 0
            ? ($this->verified_days_count / $this->completed_days) * 100
            : 0;

        // Data quality score
        $this->data_quality_score = $this->calculateDataQualityScore();

        // Financial impact
        $this->total_financial_impact = $weeklyData->sum('total_financial_impact');
        $this->average_weekly_impact = $this->completed_weeks > 0
            ? $this->total_financial_impact / $this->completed_weeks
            : 0;
        $this->average_daily_impact = $this->completed_days > 0
            ? $this->total_financial_impact / $this->completed_days
            : 0;

        // Calculate trends
        $this->calculateTrends();

        // Calculate quarter progress
        $this->calculateQuarterProgress();

        // Calculate seasonality
        $this->calculateSeasonality();

        $this->calculated_at = now();
    }

    /**
     * Calculate statistics from weekly data
     */
    protected function calculateStatistics($weeklyData): void
    {
        $values = $weeklyData->pluck('actual_value')->toArray();

        if (empty($values)) {
            return;
        }

        $this->weekly_average = array_sum($values) / count($values);
        $this->weekly_min = min($values);
        $this->weekly_max = max($values);

        // Calculate median
        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);
        if ($count % 2) {
            $this->weekly_median = $values[$middle];
        } else {
            $this->weekly_median = ($values[$middle] + $values[$middle + 1]) / 2;
        }

        // Calculate standard deviation
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $this->weekly_average, 2);
        }
        $this->weekly_std_deviation = sqrt($variance / count($values));

        // Find best and worst weeks
        $best = $weeklyData->sortByDesc('actual_value')->first();
        $worst = $weeklyData->sortBy('actual_value')->first();

        if ($best) {
            $this->best_week_start = $best->week_start_date;
            $this->best_week_value = $best->actual_value;
        }

        if ($worst) {
            $this->worst_week_start = $worst->week_start_date;
            $this->worst_week_value = $worst->actual_value;
        }
    }

    /**
     * Build weekly breakdown JSON
     */
    protected function buildWeeklyBreakdown($weeklyData): void
    {
        $breakdown = [];
        $weekNum = 1;

        foreach ($weeklyData as $weekly) {
            $breakdown["week_$weekNum"] = [
                'start' => $weekly->week_start_date->format('Y-m-d'),
                'plan' => (float) $weekly->planned_value,
                'actual' => (float) $weekly->actual_value,
                'achievement' => (float) $weekly->achievement_percentage,
                'status' => $weekly->status,
            ];
            $weekNum++;
        }

        $this->weekly_breakdown = $breakdown;
    }

    /**
     * Aggregate daily stats from weekly summaries
     */
    protected function aggregateDailyStats($weeklyData): void
    {
        $this->completed_days = $weeklyData->sum('completed_days');
        $this->green_days_count = $weeklyData->sum('green_days_count');
        $this->yellow_days_count = $weeklyData->sum('yellow_days_count');
        $this->red_days_count = $weeklyData->sum('red_days_count');

        $this->days_on_target_percentage = $this->completed_days > 0
            ? ($this->green_days_count / $this->completed_days) * 100
            : 0;

        // Daily min/max/average from weekly data
        $this->daily_min = $weeklyData->min('daily_min');
        $this->daily_max = $weeklyData->max('daily_max');
        $this->daily_average = $weeklyData->avg('daily_average');

        // Find best and worst days
        $bestDay = null;
        $worstDay = null;

        foreach ($weeklyData as $weekly) {
            if ($weekly->best_day_value && (! $bestDay || $weekly->best_day_value > $bestDay['value'])) {
                $bestDay = ['date' => $weekly->best_day_date, 'value' => $weekly->best_day_value];
            }

            if ($weekly->worst_day_value && (! $worstDay || $weekly->worst_day_value < $worstDay['value'])) {
                $worstDay = ['date' => $weekly->worst_day_date, 'value' => $weekly->worst_day_value];
            }
        }

        if ($bestDay) {
            $this->best_day_date = $bestDay['date'];
            $this->best_day_value = $bestDay['value'];
        }

        if ($worstDay) {
            $this->worst_day_date = $worstDay['date'];
            $this->worst_day_value = $worstDay['value'];
        }
    }

    /**
     * Calculate trends
     */
    protected function calculateTrends(): void
    {
        // Trend vs previous month
        if ($this->previous_month_id) {
            $previousMonth = $this->previousMonth;

            if ($previousMonth) {
                $change = $this->actual_value - $previousMonth->actual_value;
                $this->vs_previous_month_change = $previousMonth->actual_value > 0
                    ? ($change / $previousMonth->actual_value) * 100
                    : 0;

                $this->vs_previous_month_status = $change > 0 ? 'better' : ($change < 0 ? 'worse' : 'same');
            }
        }

        // Trend vs same month last year
        if ($this->same_month_last_year_id) {
            $lastYear = $this->sameMonthLastYear;

            if ($lastYear) {
                $change = $this->actual_value - $lastYear->actual_value;
                $this->vs_last_year_change = $lastYear->actual_value > 0
                    ? ($change / $lastYear->actual_value) * 100
                    : 0;

                $this->vs_last_year_status = $change > 0 ? 'better' : ($change < 0 ? 'worse' : 'same');
            }
        }

        // Overall trend based on week-over-week pattern
        $weeklyData = $this->getWeeklySummariesData();
        if ($weeklyData->count() >= 2) {
            $improving = 0;
            $declining = 0;

            for ($i = 1; $i < $weeklyData->count(); $i++) {
                $prev = $weeklyData[$i - 1];
                $curr = $weeklyData[$i];

                if ($curr->actual_value > $prev->actual_value) {
                    $improving++;
                } elseif ($curr->actual_value < $prev->actual_value) {
                    $declining++;
                }
            }

            if ($improving > $declining) {
                $this->trend = 'improving';
            } elseif ($declining > $improving) {
                $this->trend = 'declining';
            } else {
                $this->trend = 'stable';
            }

            // Check volatility
            if ($this->weekly_std_deviation && $this->weekly_average > 0) {
                $cv = ($this->weekly_std_deviation / $this->weekly_average) * 100;
                if ($cv > 30) {
                    $this->trend = 'volatile';
                }
            }
        }
    }

    /**
     * Calculate quarter progress
     */
    protected function calculateQuarterProgress(): void
    {
        $this->quarter = ceil($this->month_number / 3);

        // Calculate progress within quarter (1-3 months)
        $monthInQuarter = (($this->month_number - 1) % 3) + 1;
        $this->quarter_progress_percentage = ($monthInQuarter / 3) * 100;
    }

    /**
     * Calculate seasonality index
     */
    protected function calculateSeasonality(): void
    {
        // Compare with same month last year
        if ($this->same_month_last_year_id) {
            $lastYear = $this->sameMonthLastYear;

            if ($lastYear && $lastYear->actual_value > 0) {
                $this->seasonality_index = $this->actual_value / $lastYear->actual_value;

                $this->is_peak_season = $this->seasonality_index >= 1.2;
                $this->is_low_season = $this->seasonality_index <= 0.8;
            }
        } else {
            $this->seasonality_index = 1.0;
        }
    }

    /**
     * Calculate data quality score
     */
    protected function calculateDataQualityScore(): float
    {
        $completeness = $this->completion_percentage ?? 0;
        $verifiedRate = $this->verified_data_percentage ?? 0;

        // Weighted average: 70% completeness, 30% verification
        return ($completeness * 0.7) + ($verifiedRate * 0.3);
    }

    /**
     * Get status emoji
     */
    public function getStatusEmoji(): string
    {
        return match ($this->status) {
            'green' => '🟢',
            'yellow' => '🟡',
            'red' => '🔴',
            default => '⚪',
        };
    }

    /**
     * Get trend emoji
     */
    public function getTrendEmoji(): string
    {
        return match ($this->trend) {
            'improving' => '📈',
            'declining' => '📉',
            'stable' => '➡️',
            'volatile' => '📊',
            default => '❓',
        };
    }

    /**
     * Get summary array
     */
    public function toSummaryArray(): array
    {
        return [
            'month' => $this->month->format('Y-m'),
            'month_name' => $this->month_name_uz,
            'planned' => (float) $this->planned_value,
            'actual' => (float) $this->actual_value,
            'achievement' => (float) $this->achievement_percentage,
            'status' => $this->status,
            'status_emoji' => $this->getStatusEmoji(),
            'trend' => $this->trend,
            'trend_emoji' => $this->getTrendEmoji(),
            'vs_previous_month' => (float) $this->vs_previous_month_change,
            'vs_last_year' => (float) $this->vs_last_year_change,
            'is_complete' => $this->is_month_complete,
            'weeks_on_target' => (float) $this->weeks_on_target_percentage,
            'days_on_target' => (float) $this->days_on_target_percentage,
        ];
    }
}
