<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BudgetAllocation extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'uuid',
        'business_id',
        'period_type',
        'annual_strategy_id',
        'quarterly_plan_id',
        'monthly_plan_id',
        'weekly_plan_id',
        'year',
        'quarter',
        'month',
        'week',
        'category',
        'subcategory',
        'channel',
        'campaign',
        'planned_budget',
        'allocated_budget',
        'spent_amount',
        'remaining_amount',
        'allocation_percent',
        'expected_roi',
        'actual_roi',
        'expected_leads',
        'actual_leads',
        'expected_revenue',
        'actual_revenue',
        'cost_per_lead',
        'cost_per_acquisition',
        'status',
        'overspend_alert',
        'overspend_threshold_percent',
        'description',
        'notes',
        'history',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'history' => 'array',
        'planned_budget' => 'decimal:2',
        'allocated_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'allocation_percent' => 'decimal:2',
        'expected_roi' => 'decimal:2',
        'actual_roi' => 'decimal:2',
        'expected_revenue' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'cost_per_lead' => 'decimal:2',
        'cost_per_acquisition' => 'decimal:2',
        'overspend_alert' => 'boolean',
        'overspend_threshold_percent' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'marketing' => 'Marketing',
        'advertising' => 'Reklama',
        'content' => 'Kontent',
        'tools' => 'Asboblar',
        'team' => 'Jamoa',
        'events' => 'Tadbirlar',
        'pr' => 'PR',
        'other' => 'Boshqa',
    ];

    public const STATUSES = [
        'planned' => 'Rejalashtirilgan',
        'approved' => 'Tasdiqlangan',
        'active' => 'Faol',
        'paused' => 'To\'xtatilgan',
        'completed' => 'Tugallangan',
        'cancelled' => 'Bekor qilingan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::saving(function ($model) {
            // Auto-calculate remaining
            $model->remaining_amount = ($model->allocated_budget ?? $model->planned_budget) - $model->spent_amount;

            // Check overspend
            $budget = $model->allocated_budget ?? $model->planned_budget;
            if ($budget > 0) {
                $spentPercent = ($model->spent_amount / $budget) * 100;
                $model->overspend_alert = $spentPercent >= ($model->overspend_threshold_percent ?? 100);
            }

            // Calculate CPL
            if ($model->actual_leads > 0 && $model->spent_amount > 0) {
                $model->cost_per_lead = $model->spent_amount / $model->actual_leads;
            }

            // Calculate actual ROI
            if ($model->spent_amount > 0 && $model->actual_revenue > 0) {
                $model->actual_roi = (($model->actual_revenue - $model->spent_amount) / $model->spent_amount) * 100;
            }
        });
    }

    // Relationships
    public function annualStrategy(): BelongsTo
    {
        return $this->belongsTo(AnnualStrategy::class);
    }

    public function quarterlyPlan(): BelongsTo
    {
        return $this->belongsTo(QuarterlyPlan::class);
    }

    public function monthlyPlan(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class);
    }

    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForPeriod($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverspent($query)
    {
        return $query->where('overspend_alert', true);
    }

    // Helpers
    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'planned' => 'gray',
            'approved' => 'blue',
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'emerald',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getSpentPercent(): float
    {
        $budget = $this->allocated_budget ?? $this->planned_budget;
        if ($budget == 0) {
            return 0;
        }

        return round(($this->spent_amount / $budget) * 100, 1);
    }

    public function getSpentPercentColor(): string
    {
        $percent = $this->getSpentPercent();
        if ($percent >= 100) {
            return 'red';
        }
        if ($percent >= 80) {
            return 'yellow';
        }
        if ($percent >= 50) {
            return 'blue';
        }

        return 'green';
    }

    public function isOverBudget(): bool
    {
        return $this->spent_amount > ($this->allocated_budget ?? $this->planned_budget);
    }

    public function approve(?int $userId = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'allocated_budget' => $this->allocated_budget ?? $this->planned_budget,
        ]);
    }

    public function addSpending(float $amount, ?string $description = null): void
    {
        $history = $this->history ?? [];
        $history[] = [
            'type' => 'spending',
            'amount' => $amount,
            'description' => $description,
            'date' => now()->toIso8601String(),
            'balance_before' => $this->spent_amount,
            'balance_after' => $this->spent_amount + $amount,
        ];

        $this->update([
            'spent_amount' => $this->spent_amount + $amount,
            'history' => $history,
        ]);
    }

    public function addResult(int $leads = 0, float $revenue = 0): void
    {
        $this->update([
            'actual_leads' => $this->actual_leads + $leads,
            'actual_revenue' => $this->actual_revenue + $revenue,
        ]);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function getFormattedBudget(): string
    {
        return $this->formatMoney($this->planned_budget);
    }

    public function getFormattedSpent(): string
    {
        return $this->formatMoney($this->spent_amount);
    }

    public function getFormattedRemaining(): string
    {
        return $this->formatMoney($this->remaining_amount);
    }

    private function formatMoney(float $amount): string
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 1).'M so\'m';
        }
        if ($amount >= 1000) {
            return number_format($amount / 1000, 1).'K so\'m';
        }

        return number_format($amount, 0).' so\'m';
    }

    public function getROIColor(): string
    {
        $roi = $this->actual_roi ?? 0;
        if ($roi >= 200) {
            return 'emerald';
        }
        if ($roi >= 100) {
            return 'green';
        }
        if ($roi >= 50) {
            return 'blue';
        }
        if ($roi >= 0) {
            return 'yellow';
        }

        return 'red';
    }
}
