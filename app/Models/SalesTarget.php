<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTarget extends Model
{
    use HasUuids, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'target_type', // department, team, individual
        'department_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        // Plan values
        'plan_revenue',
        'plan_deals',
        'plan_new_clients',
        'plan_margin_percent',
        // Base values (Zero point)
        'base_revenue',
        'base_deals',
        'base_new_clients',
        // Fact values
        'fact_revenue',
        'fact_deals',
        'fact_new_clients',
        'fact_margin_percent',
        'kpi_score',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'plan_revenue' => 'decimal:2',
        'plan_deals' => 'integer',
        'plan_new_clients' => 'integer',
        'plan_margin_percent' => 'decimal:2',
        'base_revenue' => 'decimal:2',
        'base_deals' => 'integer',
        'base_new_clients' => 'integer',
        'fact_revenue' => 'decimal:2',
        'fact_deals' => 'integer',
        'fact_new_clients' => 'integer',
        'fact_margin_percent' => 'decimal:2',
        'kpi_score' => 'decimal:4',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class, 'department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SalesActivity::class);
    }

    public function marketingKpis(): HasMany
    {
        return $this->hasMany(MarketingKpi::class, 'linked_sales_target_id');
    }

    // Calculate KPI Score: (Fact - Base) / (Plan - Base)
    public function calculateKpiScore(): float
    {
        $plan = $this->plan_revenue ?? 0;
        $fact = $this->fact_revenue ?? 0;
        $base = $this->base_revenue ?? 0;

        if (($plan - $base) == 0) {
            return 0;
        }

        return round(($fact - $base) / ($plan - $base), 4);
    }

    public function updateKpiScore(): void
    {
        $this->kpi_score = $this->calculateKpiScore();
        $this->save();
    }

    // Getters
    public function getRevenueCompletionPercentAttribute(): float
    {
        if (!$this->plan_revenue || $this->plan_revenue == 0) {
            return 0;
        }
        return round(($this->fact_revenue / $this->plan_revenue) * 100, 2);
    }

    public function getDealsCompletionPercentAttribute(): float
    {
        if (!$this->plan_deals || $this->plan_deals == 0) {
            return 0;
        }
        return round(($this->fact_deals / $this->plan_deals) * 100, 2);
    }

    public function getRemainingRevenueAttribute(): float
    {
        return max(0, $this->plan_revenue - $this->fact_revenue);
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, now()->diffInDays($this->period_end, false));
    }

    public function getStatusColorAttribute(): string
    {
        $completion = $this->revenue_completion_percent;

        if ($completion >= 100) return 'green';
        if ($completion >= 80) return 'yellow';
        if ($completion >= 50) return 'orange';
        return 'red';
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

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->where('period_start', '<=', now())
                     ->where('period_end', '>=', now());
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('period_type', 'quarterly');
    }
}
