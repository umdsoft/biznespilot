<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WeeklyPlan extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'uuid',
        'business_id',
        'monthly_plan_id',
        'year',
        'week_number',
        'month',
        'week_of_month',
        'start_date',
        'end_date',
        'title',
        'status',
        'weekly_focus',
        'priorities',
        'notes',
        'goals',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'tasks',
        'total_tasks',
        'completed_tasks',
        'content_items',
        'posts_planned',
        'posts_published',
        'revenue_target',
        'spend_budget',
        'lead_target',
        'engagement_target',
        'marketing_activities',
        'sales_activities',
        'meetings',
        'ai_suggestions',
        'ai_content_ideas',
        'actual_results',
        'completion_percent',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'priorities' => 'array',
        'goals' => 'array',
        'monday' => 'array',
        'tuesday' => 'array',
        'wednesday' => 'array',
        'thursday' => 'array',
        'friday' => 'array',
        'saturday' => 'array',
        'sunday' => 'array',
        'tasks' => 'array',
        'content_items' => 'array',
        'marketing_activities' => 'array',
        'sales_activities' => 'array',
        'meetings' => 'array',
        'ai_suggestions' => 'array',
        'ai_content_ideas' => 'array',
        'actual_results' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'revenue_target' => 'decimal:2',
        'spend_budget' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const DAYS = [
        'monday' => 'Dushanba',
        'tuesday' => 'Seshanba',
        'wednesday' => 'Chorshanba',
        'thursday' => 'Payshanba',
        'friday' => 'Juma',
        'saturday' => 'Shanba',
        'sunday' => 'Yakshanba',
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
    public function monthlyPlan(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class);
    }

    public function contentCalendarItems(): HasMany
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

    public function scopeForWeek($query, int $week)
    {
        return $query->where('week_number', $week);
    }

    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year)
            ->where('week_number', now()->weekOfYear);
    }

    public function scopeInDateRange($query, $start, $end)
    {
        return $query->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);
    }

    // Helpers
    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getWeekLabel(): string
    {
        return "Hafta {$this->week_of_month}";
    }

    public function getFullLabel(): string
    {
        return $this->start_date->format('d.m').' - '.$this->end_date->format('d.m.Y');
    }

    public function getDayPlan(string $day): ?array
    {
        $day = strtolower($day);

        return $this->$day ?? null;
    }

    public function getTaskCompletionPercent(): int
    {
        if ($this->total_tasks === 0) {
            return 0;
        }

        return round(($this->completed_tasks / $this->total_tasks) * 100);
    }

    public function getContentCompletionPercent(): int
    {
        if ($this->posts_planned === 0) {
            return 0;
        }

        return round(($this->posts_published / $this->posts_planned) * 100);
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

        $this->monthlyPlan?->updateProgress();
    }

    public function addTask(array $task): void
    {
        $tasks = $this->tasks ?? [];
        $tasks[] = array_merge($task, [
            'id' => Str::uuid()->toString(),
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
        ]);

        $this->update([
            'tasks' => $tasks,
            'total_tasks' => count($tasks),
        ]);
    }

    public function completeTask(string $taskId): void
    {
        $tasks = collect($this->tasks ?? []);
        $tasks = $tasks->map(function ($task) use ($taskId) {
            if (($task['id'] ?? null) === $taskId) {
                $task['status'] = 'completed';
                $task['completed_at'] = now()->toIso8601String();
            }

            return $task;
        })->toArray();

        $completed = collect($tasks)->where('status', 'completed')->count();

        $this->update([
            'tasks' => $tasks,
            'completed_tasks' => $completed,
        ]);
    }

    public function updateProgress(): void
    {
        $taskPercent = $this->getTaskCompletionPercent();
        $contentPercent = $this->getContentCompletionPercent();

        // Weight: 60% tasks, 40% content
        $overall = ($taskPercent * 0.6) + ($contentPercent * 0.4);
        $this->update(['completion_percent' => round($overall)]);
    }
}
