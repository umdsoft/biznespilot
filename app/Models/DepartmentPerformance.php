<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Department Performance - Links all departments to sales results
 * Based on book methodology: Everyone depends on sales!
 */
class DepartmentPerformance extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $table = 'department_performance';

    protected $fillable = [
        'business_id',
        'department_id',
        'period_type',
        'period_start',
        'period_end',
        // Department's own KPIs
        'own_kpi_score',
        'own_kpi_details',
        // Linked to sales
        'sales_plan_completion',
        // Bonus weights (from book)
        'own_kpi_weight',
        'sales_link_weight',
        // Bonus
        'total_bonus_fund',
        'earned_bonus',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'own_kpi_score' => 'decimal:4',
        'own_kpi_details' => 'array',
        'sales_plan_completion' => 'decimal:2',
        'own_kpi_weight' => 'decimal:2',
        'sales_link_weight' => 'decimal:2',
        'total_bonus_fund' => 'decimal:2',
        'earned_bonus' => 'decimal:2',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class, 'department_id');
    }

    // Calculate earned bonus based on book's model
    public function calculateEarnedBonus(): float
    {
        // Own KPI contribution
        $ownBonus = $this->total_bonus_fund
            * ($this->own_kpi_weight / 100)
            * $this->own_kpi_score;

        // Sales link contribution (if sales didn't hit plan, department suffers too)
        $salesBonus = $this->total_bonus_fund
            * ($this->sales_link_weight / 100)
            * ($this->sales_plan_completion / 100);

        return round($ownBonus + $salesBonus, 2);
    }

    // Update earned bonus
    public function updateEarnedBonus(): void
    {
        $this->earned_bonus = $this->calculateEarnedBonus();
        $this->save();
    }

    // Link to sales target and sync
    public function syncWithSalesTarget(SalesTarget $salesTarget): void
    {
        $this->sales_plan_completion = $salesTarget->revenue_completion_percent;
        $this->updateEarnedBonus();
    }

    // Get performance label
    public function getPerformanceLabelAttribute(): string
    {
        $ownKpi = $this->own_kpi_score * 100;
        $sales = $this->sales_plan_completion;
        $avg = ($ownKpi + $sales) / 2;

        if ($avg >= 100) return 'A\'lo';
        if ($avg >= 80) return 'Yaxshi';
        if ($avg >= 60) return 'Qoniqarli';
        return 'Yetarli emas';
    }

    // Get bonus completion percent
    public function getBonusCompletionPercentAttribute(): float
    {
        if ($this->total_bonus_fund == 0) return 0;
        return round(($this->earned_bonus / $this->total_bonus_fund) * 100, 2);
    }

    // Scopes
    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
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

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('period_type', 'quarterly');
    }
}
