<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enhanced Vacancy Card based on book methodology:
 * - Clear purpose (why this position is needed)
 * - Main tasks with expected KPIs
 * - Employee type needed (Thinker vs Doer)
 * - Motivation structure preview
 * - Trial period KPIs
 */
class VacancyCard extends Model
{
    use HasUuids, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'department_id',
        'position_id',
        'reports_to_user_id',
        'title',
        'purpose', // Why this position is needed
        'main_tasks',
        'kpi_requirements',
        'competencies',
        'employee_type_needed', // thinker, doer, mixed
        'salary_from',
        'salary_to',
        'motivation_scheme_id',
        'motivation_description',
        'positions_count',
        'filled_count',
        'needed_by',
        'priority',
        'status',
        'trial_period_days',
        'trial_kpis',
        'created_by',
    ];

    protected $casts = [
        'main_tasks' => 'array',
        'kpi_requirements' => 'array',
        'competencies' => 'array',
        'salary_from' => 'decimal:2',
        'salary_to' => 'decimal:2',
        'needed_by' => 'date',
        'trial_kpis' => 'array',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(OrgPosition::class, 'position_id');
    }

    public function reportsToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reports_to_user_id');
    }

    public function motivationScheme(): BelongsTo
    {
        return $this->belongsTo(MotivationScheme::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function interviewProtocols(): HasMany
    {
        return $this->hasMany(InterviewProtocol::class);
    }

    // Get employee type label
    public function getEmployeeTypeLabelAttribute(): string
    {
        return match($this->employee_type_needed) {
            'thinker' => 'Думатель (Mustaqil qaror qiluvchi)',
            'doer' => 'Делатель (Ijrochi)',
            'mixed' => 'Aralash',
            default => $this->employee_type_needed,
        };
    }

    // Get priority color
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'open' => 'blue',
            'in_progress' => 'yellow',
            'filled' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    // Get remaining positions to fill
    public function getRemainingPositionsAttribute(): int
    {
        return max(0, $this->positions_count - $this->filled_count);
    }

    // Get salary range formatted
    public function getSalaryRangeAttribute(): string
    {
        if (!$this->salary_from && !$this->salary_to) {
            return 'Kelishiladi';
        }

        if ($this->salary_from && $this->salary_to) {
            return number_format($this->salary_from, 0) . ' - ' . number_format($this->salary_to, 0);
        }

        if ($this->salary_from) {
            return number_format($this->salary_from, 0) . '+';
        }

        return 'Kelishiladi';
    }

    // Get days until needed
    public function getDaysUntilNeededAttribute(): ?int
    {
        if (!$this->needed_by) {
            return null;
        }

        return now()->diffInDays($this->needed_by, false);
    }

    // Check if overdue
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->needed_by) {
            return false;
        }

        return now()->gt($this->needed_by) && $this->status !== 'filled';
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeNeedingThinker($query)
    {
        return $query->where('employee_type_needed', 'thinker');
    }

    public function scopeNeedingDoer($query)
    {
        return $query->where('employee_type_needed', 'doer');
    }
}
