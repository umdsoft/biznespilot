<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marketing KPI based on book methodology:
 * - Marketing bonus is linked to sales results (70/30 rule)
 * - 70% from sales plan completion
 * - 30% from individual marketing tasks
 */
class MarketingKpi extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        // Marketing's own metrics
        'budget_limit',
        'budget_used',
        'budget_within_limit',
        // Tasks
        'tasks_total',
        'tasks_completed',
        'tasks_completion_percent',
        // Linked to Sales
        'linked_sales_target_id',
        'sales_plan_completion',
        // Bonus (70/30 rule)
        'bonus_from_sales',
        'bonus_from_tasks',
        'total_bonus',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'budget_limit' => 'decimal:2',
        'budget_used' => 'decimal:2',
        'budget_within_limit' => 'boolean',
        'tasks_completion_percent' => 'decimal:2',
        'sales_plan_completion' => 'decimal:2',
        'bonus_from_sales' => 'decimal:2',
        'bonus_from_tasks' => 'decimal:2',
        'total_bonus' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linkedSalesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'linked_sales_target_id');
    }

    // Calculate bonus based on 70/30 rule from book
    public function calculateBonus(float $baseBonusFund, float $salesWeight = 70, float $tasksWeight = 30): void
    {
        // 70% from sales plan completion
        $salesBonusPortion = $baseBonusFund * ($salesWeight / 100);
        $this->bonus_from_sales = $salesBonusPortion * ($this->sales_plan_completion / 100);

        // 30% from tasks completion
        $tasksBonusPortion = $baseBonusFund * ($tasksWeight / 100);
        $this->bonus_from_tasks = $tasksBonusPortion * ($this->tasks_completion_percent / 100);

        $this->total_bonus = $this->bonus_from_sales + $this->bonus_from_tasks;

        $this->save();
    }

    // Sync with sales target
    public function syncWithSalesTarget(): void
    {
        if ($this->linkedSalesTarget) {
            $this->sales_plan_completion = $this->linkedSalesTarget->revenue_completion_percent;
            $this->save();
        }
    }

    // Update tasks completion
    public function updateTasksCompletion(): void
    {
        if ($this->tasks_total > 0) {
            $this->tasks_completion_percent = round(($this->tasks_completed / $this->tasks_total) * 100, 2);
        } else {
            $this->tasks_completion_percent = 0;
        }

        $this->save();
    }

    // Check budget status
    public function checkBudgetStatus(): void
    {
        $this->budget_within_limit = $this->budget_used <= $this->budget_limit;
        $this->save();
    }

    // Get budget usage percent
    public function getBudgetUsagePercentAttribute(): float
    {
        if ($this->budget_limit == 0) return 0;
        return round(($this->budget_used / $this->budget_limit) * 100, 2);
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'active' => 'blue',
            'completed' => 'green',
            default => 'gray',
        };
    }

    // Get overall performance label
    public function getPerformanceLabelAttribute(): string
    {
        $avgCompletion = ($this->sales_plan_completion + $this->tasks_completion_percent) / 2;

        if ($avgCompletion >= 100) return 'A\'lo';
        if ($avgCompletion >= 80) return 'Yaxshi';
        if ($avgCompletion >= 60) return 'Qoniqarli';
        return 'Yetarli emas';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                     ->where('period_end', '<=', $endDate);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->where('period_start', '<=', now())
                     ->where('period_end', '>=', now());
    }
}
