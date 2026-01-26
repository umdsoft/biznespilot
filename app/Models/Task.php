<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'lead_id',
        'user_id',
        'assigned_to',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'due_date',
        'reminder_at',
        'completed_at',
        'result',
        'stagnant_alert_sent_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'reminder_at' => 'datetime',
        'completed_at' => 'datetime',
        'stagnant_alert_sent_at' => 'datetime',
    ];

    /**
     * Task types with labels
     */
    public const TYPES = [
        'call' => 'Qo\'ng\'iroq',
        'meeting' => 'Uchrashuv',
        'email' => 'Email',
        'task' => 'Vazifa',
        'follow_up' => 'Qayta aloqa',
        'other' => 'Boshqa',
    ];

    /**
     * Priority levels with labels
     */
    public const PRIORITIES = [
        'low' => 'Past',
        'medium' => 'O\'rtacha',
        'high' => 'Yuqori',
        'urgent' => 'Shoshilinch',
    ];

    /**
     * Status options with labels
     */
    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'in_progress' => 'Jarayonda',
        'completed' => 'Bajarildi',
        'cancelled' => 'Bekor qilindi',
    ];

    /**
     * Get the business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the creator
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assigned user
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Alias for assignedUser (for CheckStagnantTasksJob compatibility)
     */
    public function assignee(): BelongsTo
    {
        return $this->assignedUser();
    }

    /**
     * Alias for user (task creator)
     */
    public function creator(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Scope: Pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    /**
     * Scope: Today's tasks
     */
    public function scopeToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    /**
     * Scope: Upcoming tasks (next 7 days)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(7)]);
    }

    /**
     * Scope: By assigned user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Check if task is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?string $result = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result' => $result,
        ]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
