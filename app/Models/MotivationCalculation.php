<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotivationCalculation extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'employee_motivation_id',
        'period_type',
        'period_start',
        'period_end',
        'fixed_salary',
        'soft_salary_earned',
        'soft_salary_max',
        'bonus_earned',
        'bonus_max',
        'penalties',
        'total_earned',
        'kpi_score',
        'soft_salary_completion',
        'calculation_details',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'fixed_salary' => 'decimal:2',
        'soft_salary_earned' => 'decimal:2',
        'soft_salary_max' => 'decimal:2',
        'bonus_earned' => 'decimal:2',
        'bonus_max' => 'decimal:2',
        'penalties' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'kpi_score' => 'decimal:4',
        'soft_salary_completion' => 'decimal:2',
        'calculation_details' => 'array',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employeeMotivation(): BelongsTo
    {
        return $this->belongsTo(EmployeeMotivation::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Calculate total
    public function calculateTotal(): float
    {
        return $this->fixed_salary
            + $this->soft_salary_earned
            + $this->bonus_earned
            - $this->penalties;
    }

    public function recalculateTotal(): void
    {
        $this->total_earned = $this->calculateTotal();
        $this->save();
    }

    // Get soft salary completion as formatted string
    public function getSoftSalaryCompletionFormattedAttribute(): string
    {
        return number_format($this->soft_salary_completion, 0) . '%';
    }

    // Get KPI score as formatted string
    public function getKpiScoreFormattedAttribute(): string
    {
        return number_format($this->kpi_score * 100, 1) . '%';
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'calculated' => 'blue',
            'approved' => 'green',
            'paid' => 'purple',
            default => 'gray',
        };
    }

    // Scopes
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                     ->where('period_end', '<=', $endDate);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
