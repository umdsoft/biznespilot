<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyGoal extends Model
{
    use HasUuids, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'title',
        'description',
        'perspective', // BSC: finance, customers, processes, employees
        'goal_type',
        'period_type',
        'start_date',
        'end_date',
        'target_value',
        'current_value',
        'base_value', // Zero point (nol nuqtasi)
        'unit',
        'status',
        'parent_goal_id',
        'responsible_user_id',
        'department_id',
        'weight',
        'milestones',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'base_value' => 'decimal:2',
        'milestones' => 'array',
    ];

    // Relationships
    public function parentGoal(): BelongsTo
    {
        return $this->belongsTo(CompanyGoal::class, 'parent_goal_id');
    }

    public function childGoals(): HasMany
    {
        return $this->hasMany(CompanyGoal::class, 'parent_goal_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class, 'department_id');
    }

    // KPI Calculation: (Fact - Base) / (Plan - Base)
    public function getKpiScoreAttribute(): float
    {
        $plan = $this->target_value ?? 0;
        $fact = $this->current_value ?? 0;
        $base = $this->base_value ?? 0;

        if (($plan - $base) == 0) {
            return 0;
        }

        return ($fact - $base) / ($plan - $base);
    }

    public function getCompletionPercentAttribute(): float
    {
        if (!$this->target_value || $this->target_value == 0) {
            return 0;
        }

        return min(100, ($this->current_value / $this->target_value) * 100);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPerspective($query, string $perspective)
    {
        return $query->where('perspective', $perspective);
    }

    public function scopeForPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_goal_id');
    }
}
