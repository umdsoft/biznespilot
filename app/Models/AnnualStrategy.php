<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualStrategy extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'business_id',
        'diagnostic_id',
        'year',
        'title',
        'status',
        'vision_statement',
        'mission_statement',
        'executive_summary',
        'strategic_goals',
        'okrs',
        'focus_areas',
        'growth_drivers',
        'risk_factors',
        'revenue_target',
        'profit_target',
        'annual_budget',
        'budget_by_quarter',
        'lead_target',
        'customer_target',
        'cac_target',
        'ltv_target',
        'primary_channels',
        'channel_budget_allocation',
        'ai_recommendations',
        'ai_summary',
        'confidence_score',
        'completion_percent',
        'milestones',
        'actual_results',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'strategic_goals' => 'array',
        'okrs' => 'array',
        'focus_areas' => 'array',
        'growth_drivers' => 'array',
        'risk_factors' => 'array',
        'budget_by_quarter' => 'array',
        'primary_channels' => 'array',
        'channel_budget_allocation' => 'array',
        'ai_recommendations' => 'array',
        'milestones' => 'array',
        'actual_results' => 'array',
        'revenue_target' => 'decimal:2',
        'profit_target' => 'decimal:2',
        'annual_budget' => 'decimal:2',
        'cac_target' => 'decimal:2',
        'ltv_target' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft' => 'Qoralama',
        'active' => 'Faol',
        'completed' => 'Tugallangan',
        'archived' => 'Arxivlangan',
    ];

    // Relationships
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(AIDiagnostic::class, 'diagnostic_id');
    }

    public function quarterlyPlans(): HasMany
    {
        return $this->hasMany(QuarterlyPlan::class);
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

    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year);
    }

    // Helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
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
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getProgressColor(): string
    {
        $percent = $this->completion_percent ?? 0;
        if ($percent >= 80) {
            return 'green';
        }
        if ($percent >= 50) {
            return 'blue';
        }
        if ($percent >= 25) {
            return 'yellow';
        }

        return 'gray';
    }

    public function updateProgress(): void
    {
        $quarters = $this->quarterlyPlans()->count();
        if ($quarters === 0) {
            $this->update(['completion_percent' => 0]);

            return;
        }

        $completed = $this->quarterlyPlans()->where('status', 'completed')->count();
        $percent = round(($completed / $quarters) * 100);
        $this->update(['completion_percent' => $percent]);
    }

    public function getQuarterBudget(int $quarter): float
    {
        return $this->budget_by_quarter[$quarter - 1] ?? ($this->annual_budget / 4);
    }
}
