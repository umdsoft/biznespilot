<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiMonthlyStrategy extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'year',
        'month',
        'period_label',
        'title',
        'executive_summary',
        'goals',
        'action_plan',
        'focus_areas',
        'content_strategy',
        'advertising_strategy',
        'channel_strategy',
        'sales_targets',
        'pricing_recommendations',
        'offer_recommendations',
        'recommended_budget',
        'budget_breakdown',
        'predicted_metrics',
        'confidence_score',
        'status',
        'generated_at',
        'approved_at',
        'completed_at',
        'actual_results',
        'success_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'goals' => 'array',
        'action_plan' => 'array',
        'focus_areas' => 'array',
        'content_strategy' => 'array',
        'advertising_strategy' => 'array',
        'channel_strategy' => 'array',
        'sales_targets' => 'array',
        'pricing_recommendations' => 'array',
        'offer_recommendations' => 'array',
        'recommended_budget' => 'decimal:2',
        'budget_breakdown' => 'array',
        'predicted_metrics' => 'array',
        'confidence_score' => 'decimal:2',
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'actual_results' => 'array',
        'success_rate' => 'decimal:2',
    ];

    /**
     * Approve the strategy.
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark strategy as completed.
     */
    public function complete(array $actualResults): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_results' => $actualResults,
        ]);

        $this->calculateSuccessRate();
    }

    /**
     * Calculate success rate based on actual vs predicted.
     */
    public function calculateSuccessRate(): void
    {
        if (!$this->actual_results || !$this->predicted_metrics) {
            return;
        }

        $predicted = $this->predicted_metrics;
        $actual = $this->actual_results;

        $achievements = [];

        foreach ($predicted as $key => $predictedValue) {
            if (isset($actual[$key]) && is_numeric($predictedValue) && is_numeric($actual[$key])) {
                if ($predictedValue > 0) {
                    $achievements[] = ($actual[$key] / $predictedValue) * 100;
                }
            }
        }

        if (!empty($achievements)) {
            $successRate = array_sum($achievements) / count($achievements);
            $this->update(['success_rate' => round($successRate, 2)]);
        }
    }

    /**
     * Archive the strategy.
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Scope for active strategies.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed strategies.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
            ->where('month', now()->month);
    }

    /**
     * Scope by year.
     */
    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Get formatted period.
     */
    public function getFormattedPeriodAttribute(): string
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
    }
}
