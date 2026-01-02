<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class KpiDailyActual extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'kpi_code',
        'date',
        'recorded_time',
        'day_of_week',
        'planned_value',
        'actual_value',
        'unit',
        'achievement_percentage',
        'variance',
        'variance_percentage',
        'status',
        'is_on_track',
        'data_source',
        'is_verified',
        'verified_by_user_id',
        'verified_at',
        'is_estimated',
        'estimation_method',
        'notes',
        'metadata',
        'is_anomaly',
        'anomaly_type',
        'anomaly_reason',
        'financial_impact',
        'impact_type',
        'weekly_summary_id',
        'monthly_summary_id',
        'created_by_user_id',
        'updated_by_user_id',
        // Integration sync fields
        'integration_sync_id',
        'sync_status',
        'last_synced_at',
        'auto_calculated',
        'can_override',
        'sync_metadata',
        'overridden_by',
        'overridden_at',
        'original_synced_value',
        'data_quality_score',
    ];

    protected $casts = [
        'date' => 'date',
        'recorded_time' => 'datetime',
        'planned_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'is_on_track' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_estimated' => 'boolean',
        'is_anomaly' => 'boolean',
        'financial_impact' => 'decimal:2',
        'metadata' => 'array',
        // Integration sync fields
        'last_synced_at' => 'datetime',
        'auto_calculated' => 'boolean',
        'can_override' => 'boolean',
        'sync_metadata' => 'array',
        'overridden_at' => 'datetime',
        'original_synced_value' => 'decimal:2',
        'data_quality_score' => 'integer',
    ];

    /**
     * Relationships
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function kpiTemplate()
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_code', 'kpi_code');
    }

    public function weeklySummary()
    {
        return $this->belongsTo(KpiWeeklySummary::class);
    }

    public function monthlySummary()
    {
        return $this->belongsTo(KpiMonthlySummary::class);
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
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

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForWeek($query, string $weekStart)
    {
        $weekEnd = Carbon::parse($weekStart)->addDays(6)->toDateString();
        return $query->whereBetween('date', [$weekStart, $weekEnd]);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeGreen($query)
    {
        return $query->where('status', 'green');
    }

    public function scopeYellow($query)
    {
        return $query->where('status', 'yellow');
    }

    public function scopeRed($query)
    {
        return $query->where('status', 'red');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAnomalies($query)
    {
        return $query->where('is_anomaly', true);
    }

    public function scopeByDataSource($query, string $source)
    {
        return $query->where('data_source', $source);
    }

    /**
     * Boot method to auto-calculate fields
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->calculateMetrics();
        });

        static::updating(function ($model) {
            if ($model->isDirty(['planned_value', 'actual_value'])) {
                $model->calculateMetrics();
            }
        });
    }

    /**
     * Calculate performance metrics
     */
    public function calculateMetrics(): void
    {
        // Set day of week
        if ($this->date) {
            $this->day_of_week = strtolower(Carbon::parse($this->date)->format('l'));
        }

        // Calculate achievement percentage
        if ($this->planned_value > 0) {
            $this->achievement_percentage = ($this->actual_value / $this->planned_value) * 100;
        } else {
            $this->achievement_percentage = $this->actual_value > 0 ? 100 : 0;
        }

        // Calculate variance
        $this->variance = $this->actual_value - $this->planned_value;

        // Calculate variance percentage
        if ($this->planned_value > 0) {
            $this->variance_percentage = ($this->variance / $this->planned_value) * 100;
        } else {
            $this->variance_percentage = 0;
        }

        // Calculate status
        $this->calculateStatus();

        // Check if on track
        $this->is_on_track = in_array($this->status, ['green', 'yellow']);
    }

    /**
     * Calculate status based on achievement
     */
    protected function calculateStatus(): void
    {
        $template = $this->kpiTemplate;

        if ($template) {
            $this->status = $template->calculateStatus($this->achievement_percentage);
        } else {
            // Default thresholds if template not found
            if ($this->achievement_percentage >= 90) {
                $this->status = 'green';
            } elseif ($this->achievement_percentage >= 70) {
                $this->status = 'yellow';
            } else {
                $this->status = 'red';
            }
        }
    }

    /**
     * Verify this record
     */
    public function verify(int $userId = null): void
    {
        $this->is_verified = true;
        $this->verified_by_user_id = $userId;
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Mark as anomaly
     */
    public function markAsAnomaly(string $type, string $reason = null): void
    {
        $this->is_anomaly = true;
        $this->anomaly_type = $type;
        $this->anomaly_reason = $reason;
        $this->save();
    }

    /**
     * Get status emoji
     */
    public function getStatusEmoji(): string
    {
        return match($this->status) {
            'green' => '🟢',
            'yellow' => '🟡',
            'red' => '🔴',
            default => '⚪',
        };
    }

    /**
     * Get formatted value
     */
    public function getFormattedActual(): string
    {
        return $this->formatValue($this->actual_value);
    }

    public function getFormattedPlanned(): string
    {
        return $this->formatValue($this->planned_value);
    }

    protected function formatValue(float $value): string
    {
        return match($this->unit) {
            'UZS', 'mln UZS', 'ming UZS' => number_format($value, 0, '.', ' ') . ' ' . $this->unit,
            '%' => number_format($value, 1) . '%',
            'daqiqa', 'soat', 'kun' => number_format($value, 0) . ' ' . $this->unit,
            'dona' => number_format($value, 0) . ' dona',
            default => number_format($value, 2),
        };
    }

    /**
     * Get comparison with previous day
     */
    public function getPreviousDayActual()
    {
        return static::where('business_id', $this->business_id)
                    ->where('kpi_code', $this->kpi_code)
                    ->where('date', '<', $this->date)
                    ->orderBy('date', 'desc')
                    ->first();
    }

    /**
     * Calculate trend vs previous day
     */
    public function getTrendVsPreviousDay(): ?array
    {
        $previous = $this->getPreviousDayActual();

        if (!$previous) {
            return null;
        }

        $change = $this->actual_value - $previous->actual_value;
        $changePercent = $previous->actual_value > 0
            ? ($change / $previous->actual_value) * 100
            : 0;

        return [
            'previous_value' => $previous->actual_value,
            'change' => $change,
            'change_percent' => round($changePercent, 2),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'same'),
        ];
    }

    /**
     * Get summary array
     */
    public function toSummaryArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'day_of_week' => $this->day_of_week,
            'kpi_code' => $this->kpi_code,
            'planned' => (float) $this->planned_value,
            'actual' => (float) $this->actual_value,
            'achievement' => (float) $this->achievement_percentage,
            'variance' => (float) $this->variance,
            'status' => $this->status,
            'status_emoji' => $this->getStatusEmoji(),
            'is_verified' => $this->is_verified,
            'is_anomaly' => $this->is_anomaly,
        ];
    }
}
