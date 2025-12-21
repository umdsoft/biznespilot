<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MonthlyPlan extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'uuid',
        'business_id',
        'quarterly_plan_id',
        'year',
        'month',
        'title',
        'status',
        'theme',
        'executive_summary',
        'monthly_objectives',
        'goals',
        'okrs',
        'revenue_target',
        'budget',
        'lead_target',
        'customer_target',
        'content_pieces_target',
        'posts_target',
        'week_1_plan',
        'week_2_plan',
        'week_3_plan',
        'week_4_plan',
        'week_5_plan',
        'content_themes',
        'content_types',
        'content_calendar_summary',
        'campaigns',
        'promotions',
        'events',
        'channel_focus',
        'channel_budget',
        'channel_targets',
        'sales_activities',
        'offers',
        'pricing_actions',
        'ai_recommendations',
        'ai_content_suggestions',
        'ai_summary',
        'confidence_score',
        'completion_percent',
        'weekly_progress',
        'actual_results',
        'success_rate',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'monthly_objectives' => 'array',
        'goals' => 'array',
        'okrs' => 'array',
        'week_1_plan' => 'array',
        'week_2_plan' => 'array',
        'week_3_plan' => 'array',
        'week_4_plan' => 'array',
        'week_5_plan' => 'array',
        'content_themes' => 'array',
        'content_types' => 'array',
        'content_calendar_summary' => 'array',
        'campaigns' => 'array',
        'promotions' => 'array',
        'events' => 'array',
        'channel_focus' => 'array',
        'channel_budget' => 'array',
        'channel_targets' => 'array',
        'sales_activities' => 'array',
        'offers' => 'array',
        'pricing_actions' => 'array',
        'ai_recommendations' => 'array',
        'ai_content_suggestions' => 'array',
        'weekly_progress' => 'array',
        'actual_results' => 'array',
        'revenue_target' => 'decimal:2',
        'budget' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const MONTHS = [
        1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart',
        4 => 'Aprel', 5 => 'May', 6 => 'Iyun',
        7 => 'Iyul', 8 => 'Avgust', 9 => 'Sentabr',
        10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr',
    ];

    public const STATUSES = [
        'draft' => 'Qoralama',
        'active' => 'Faol',
        'completed' => 'Tugallangan',
        'archived' => 'Arxivlangan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function quarterlyPlan(): BelongsTo
    {
        return $this->belongsTo(QuarterlyPlan::class);
    }

    public function weeklyPlans(): HasMany
    {
        return $this->hasMany(WeeklyPlan::class);
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentCalendar::class);
    }

    public function kpiTargets(): HasMany
    {
        return $this->hasMany(KpiTarget::class);
    }

    public function budgetAllocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year)
            ->where('month', now()->month);
    }

    // Helpers
    public function getMonthLabel(): string
    {
        return self::MONTHS[$this->month] ?? $this->month;
    }

    public function getFullPeriodLabel(): string
    {
        return $this->getMonthLabel() . ' ' . $this->year;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStartDate(): \Carbon\Carbon
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1);
    }

    public function getEndDate(): \Carbon\Carbon
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1)->endOfMonth();
    }

    public function getWeeksInMonth(): int
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();
        return $start->diffInWeeks($end) + 1;
    }

    public function getWeekPlan(int $week): ?array
    {
        $prop = "week_{$week}_plan";
        return $this->$prop;
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    public function complete(array $results = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_results' => $results,
        ]);

        $this->calculateSuccessRate();
        $this->quarterlyPlan?->updateProgress();
    }

    public function calculateSuccessRate(): void
    {
        if (!$this->actual_results || !$this->goals) {
            return;
        }

        $achievements = [];
        foreach ($this->goals as $goal) {
            $key = $goal['metric'] ?? null;
            $target = $goal['target'] ?? null;
            $actual = $this->actual_results[$key] ?? null;

            if ($key && $target && $actual && is_numeric($target) && is_numeric($actual) && $target > 0) {
                $achievements[] = min(($actual / $target) * 100, 150);
            }
        }

        if (!empty($achievements)) {
            $this->update(['success_rate' => round(array_sum($achievements) / count($achievements), 2)]);
        }
    }

    public function updateProgress(): void
    {
        $weeks = $this->weeklyPlans()->count();
        if ($weeks === 0) {
            $this->update(['completion_percent' => 0]);
            return;
        }

        $completed = $this->weeklyPlans()->where('status', 'completed')->count();
        $percent = round(($completed / $weeks) * 100);
        $this->update(['completion_percent' => $percent]);
    }
}
