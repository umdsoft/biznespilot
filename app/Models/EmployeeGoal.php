<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeGoal extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'hr_employee_goals';

    protected $fillable = [
        'business_id',
        'user_id',
        'kpi_template_id',
        'title',
        'description',
        'start_date',
        'due_date',
        'status',
        'progress',
        'target_value',
        'current_value',
        'measurement_unit',
        'notes',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'progress' => 'integer',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kpiTemplate(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'active' => 'Faol',
            'completed' => 'Bajarildi',
            'cancelled' => 'Bekor qilindi',
            'overdue' => 'Muddati o\'tgan',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getProgressPercentageAttribute(): string
    {
        return $this->progress . '%';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active' && $this->due_date->isPast();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressColorAttribute(): string
    {
        if ($this->progress >= 80) return 'green';
        if ($this->progress >= 50) return 'blue';
        if ($this->progress >= 25) return 'yellow';
        return 'red';
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('due_date', '<', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
