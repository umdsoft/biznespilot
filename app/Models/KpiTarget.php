<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KpiTarget extends Model
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
        'kpi_name',
        'kpi_key',
        'category',
        'target_value',
        'minimum_value',
        'stretch_value',
        'unit',
        'current_value',
        'previous_value',
        'last_updated_at',
        'progress_percent',
        'status',
        'trend',
        'change_percent',
        'enable_alerts',
        'alert_threshold_percent',
        'alert_triggered',
        'last_alert_at',
        'data_source',
        'calculation_method',
        'calculation_formula',
        'description',
        'notes',
        'priority',
    ];

    protected $casts = [
        'calculation_formula' => 'array',
        'target_value' => 'decimal:4',
        'minimum_value' => 'decimal:4',
        'stretch_value' => 'decimal:4',
        'current_value' => 'decimal:4',
        'previous_value' => 'decimal:4',
        'progress_percent' => 'decimal:2',
        'change_percent' => 'decimal:2',
        'alert_threshold_percent' => 'decimal:2',
        'enable_alerts' => 'boolean',
        'alert_triggered' => 'boolean',
        'last_updated_at' => 'datetime',
        'last_alert_at' => 'datetime',
    ];

    public const PERIOD_TYPES = [
        'annual' => 'Yillik',
        'quarterly' => 'Choraklik',
        'monthly' => 'Oylik',
        'weekly' => 'Haftalik',
    ];

    public const CATEGORIES = [
        'revenue' => 'Daromad',
        'marketing' => 'Marketing',
        'sales' => 'Savdo',
        'content' => 'Kontent',
        'customer' => 'Mijozlar',
        'operational' => 'Operatsion',
    ];

    public const STATUSES = [
        'not_started' => 'Boshlanmagan',
        'on_track' => 'Rejada',
        'at_risk' => 'Xavf ostida',
        'behind' => 'Orqada',
        'achieved' => 'Erishildi',
        'exceeded' => 'Oshib ketdi',
    ];

    public const TRENDS = [
        'up' => 'O\'sish',
        'down' => 'Tushish',
        'stable' => 'Barqaror',
        'unknown' => 'Noma\'lum',
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

    // Scopes
    public function scopeForPeriod($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAtRisk($query)
    {
        return $query->whereIn('status', ['at_risk', 'behind']);
    }

    public function scopeAchieved($query)
    {
        return $query->whereIn('status', ['achieved', 'exceeded']);
    }

    public function scopeWithAlerts($query)
    {
        return $query->where('enable_alerts', true)
            ->where('alert_triggered', true);
    }

    // Helpers
    public function getPeriodTypeLabel(): string
    {
        return self::PERIOD_TYPES[$this->period_type] ?? $this->period_type;
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTrendLabel(): string
    {
        return self::TRENDS[$this->trend] ?? $this->trend;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'not_started' => 'gray',
            'on_track' => 'blue',
            'at_risk' => 'yellow',
            'behind' => 'red',
            'achieved' => 'green',
            'exceeded' => 'emerald',
            default => 'gray',
        };
    }

    public function getTrendIcon(): string
    {
        return match ($this->trend) {
            'up' => 'arrow-up',
            'down' => 'arrow-down',
            'stable' => 'minus',
            default => 'question-mark',
        };
    }

    public function getTrendColor(): string
    {
        // For most KPIs, up is good
        return match ($this->trend) {
            'up' => 'green',
            'down' => 'red',
            'stable' => 'gray',
            default => 'gray',
        };
    }

    public function updateValue(float $value): void
    {
        $this->previous_value = $this->current_value;
        $this->current_value = $value;
        $this->last_updated_at = now();

        $this->calculateProgress();
        $this->calculateTrend();
        $this->updateStatus();
        $this->checkAlerts();

        $this->save();
    }

    public function calculateProgress(): void
    {
        if ($this->target_value == 0) {
            $this->progress_percent = 0;

            return;
        }

        $this->progress_percent = min(
            round(($this->current_value / $this->target_value) * 100, 2),
            150
        );
    }

    public function calculateTrend(): void
    {
        if ($this->previous_value === null) {
            $this->trend = 'unknown';
            $this->change_percent = null;

            return;
        }

        if ($this->previous_value == 0) {
            $this->trend = $this->current_value > 0 ? 'up' : 'stable';
            $this->change_percent = $this->current_value > 0 ? 100 : 0;

            return;
        }

        $change = (($this->current_value - $this->previous_value) / $this->previous_value) * 100;
        $this->change_percent = round($change, 2);

        if ($change > 1) {
            $this->trend = 'up';
        } elseif ($change < -1) {
            $this->trend = 'down';
        } else {
            $this->trend = 'stable';
        }
    }

    public function updateStatus(): void
    {
        $progress = $this->progress_percent ?? 0;

        if ($progress >= 110) {
            $this->status = 'exceeded';
        } elseif ($progress >= 100) {
            $this->status = 'achieved';
        } elseif ($progress >= $this->alert_threshold_percent) {
            $this->status = 'on_track';
        } elseif ($progress >= 50) {
            $this->status = 'at_risk';
        } elseif ($progress > 0) {
            $this->status = 'behind';
        } else {
            $this->status = 'not_started';
        }
    }

    public function checkAlerts(): void
    {
        if (! $this->enable_alerts) {
            return;
        }

        $shouldAlert = $this->progress_percent < $this->alert_threshold_percent
            && $this->current_value !== null
            && $this->status !== 'not_started';

        if ($shouldAlert && ! $this->alert_triggered) {
            $this->alert_triggered = true;
            $this->last_alert_at = now();
            // TODO: Send notification
        } elseif (! $shouldAlert && $this->alert_triggered) {
            $this->alert_triggered = false;
        }
    }

    public function getFormattedValue(?float $value = null): string
    {
        $value = $value ?? $this->current_value;
        if ($value === null) {
            return '-';
        }

        if ($this->unit === '%') {
            return number_format($value, 1).'%';
        }

        if ($this->unit === 'sum' || in_array($this->kpi_key, ['revenue', 'profit', 'budget', 'spend'])) {
            if ($value >= 1000000) {
                return number_format($value / 1000000, 1).'M so\'m';
            }
            if ($value >= 1000) {
                return number_format($value / 1000, 1).'K so\'m';
            }

            return number_format($value, 0).' so\'m';
        }

        return number_format($value, $value == floor($value) ? 0 : 1).($this->unit ? ' '.$this->unit : '');
    }

    public function getRemainingToTarget(): float
    {
        return max(0, $this->target_value - ($this->current_value ?? 0));
    }

    public function getFormattedRemainingToTarget(): string
    {
        return $this->getFormattedValue($this->getRemainingToTarget());
    }
}
