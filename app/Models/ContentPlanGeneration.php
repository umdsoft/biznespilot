<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPlanGeneration extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'user_id',
        'plan_type',
        'weekly_plan_id',
        'monthly_plan_id',
        'period_start',
        'period_end',
        'input_data',
        'niche_scores_used',
        'pain_points_used',
        'algorithm_breakdown',
        'items_generated',
        'status',
        'performance_score',
        'performance_details',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'input_data' => 'array',
        'niche_scores_used' => 'array',
        'pain_points_used' => 'array',
        'algorithm_breakdown' => 'array',
        'performance_score' => 'decimal:2',
        'performance_details' => 'array',
    ];

    public const STATUSES = [
        'generated' => 'Yaratilgan',
        'approved' => 'Tasdiqlangan',
        'active' => 'Faol',
        'completed' => 'Tugallangan',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }

    public function monthlyPlan(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class);
    }

    // Scopes
    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWeekly($query)
    {
        return $query->where('plan_type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('plan_type', 'monthly');
    }

    // Helpers
    public function markApproved(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function markActive(): void
    {
        $this->update(['status' => 'active']);
    }

    public function markCompleted(float $performanceScore, array $details = []): void
    {
        $this->update([
            'status' => 'completed',
            'performance_score' => $performanceScore,
            'performance_details' => $details,
        ]);
    }
}
