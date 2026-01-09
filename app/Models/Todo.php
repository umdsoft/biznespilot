<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    // Types
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_TEAM = 'team';
    public const TYPE_PROCESS = 'process';

    public const TYPES = [
        self::TYPE_PERSONAL => 'Shaxsiy',
        self::TYPE_TEAM => 'Jamoa',
        self::TYPE_PROCESS => 'Jarayon',
    ];

    // Priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Past',
        self::PRIORITY_MEDIUM => "O'rta",
        self::PRIORITY_HIGH => 'Yuqori',
        self::PRIORITY_URGENT => 'Shoshilinch',
    ];

    public const PRIORITY_COLORS = [
        self::PRIORITY_LOW => '#94A3B8',
        self::PRIORITY_MEDIUM => '#3B82F6',
        self::PRIORITY_HIGH => '#F59E0B',
        self::PRIORITY_URGENT => '#EF4444',
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Kutilmoqda',
        self::STATUS_IN_PROGRESS => 'Jarayonda',
        self::STATUS_COMPLETED => 'Bajarildi',
        self::STATUS_CANCELLED => 'Bekor qilindi',
    ];

    protected $fillable = [
        'business_id',
        'created_by',
        'assigned_to',
        'parent_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'due_date',
        'reminder_at',
        'completed_at',
        'order',
        'is_recurring',
        'recurrence_id',
        'template_id',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'reminder_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
    ];

    // ==================== Relationships ====================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Todo::class, 'parent_id')->orderBy('order');
    }

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(TodoRecurrence::class, 'recurrence_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TodoTemplate::class, 'template_id');
    }

    // ==================== Scopes ====================

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeNotCompleted($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeOverdue($query)
    {
        return $query->notCompleted()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeToday($query)
    {
        return $query->notCompleted()
            ->whereDate('due_date', today());
    }

    public function scopeTomorrow($query)
    {
        return $query->notCompleted()
            ->whereDate('due_date', today()->addDay());
    }

    public function scopeThisWeek($query)
    {
        return $query->notCompleted()
            ->whereBetween('due_date', [today()->addDays(2), today()->endOfWeek()]);
    }

    public function scopeUpcoming($query)
    {
        return $query->notCompleted()
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7));
    }

    public function scopeMyTodos($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
                ->orWhere('assigned_to', $userId);
        });
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopePersonal($query)
    {
        return $query->where('type', self::TYPE_PERSONAL);
    }

    public function scopeTeam($query)
    {
        return $query->where('type', self::TYPE_TEAM);
    }

    public function scopeProcess($query)
    {
        return $query->where('type', self::TYPE_PROCESS);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // ==================== Accessors ====================

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? '#94A3B8';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getProgressAttribute(): int
    {
        $subtasks = $this->subtasks;

        if ($subtasks->isEmpty()) {
            return $this->status === self::STATUS_COMPLETED ? 100 : 0;
        }

        $completed = $subtasks->where('status', self::STATUS_COMPLETED)->count();
        $total = $subtasks->count();

        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }

    public function getHasSubtasksAttribute(): bool
    {
        return $this->subtasks()->exists();
    }

    public function getSubtasksCountAttribute(): int
    {
        return $this->subtasks()->count();
    }

    public function getCompletedSubtasksCountAttribute(): int
    {
        return $this->subtasks()->where('status', self::STATUS_COMPLETED)->count();
    }

    // ==================== Methods ====================

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Also complete all subtasks
        $this->subtasks()->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function markAsPending(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'completed_at' => null,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function toggleComplete(): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            $this->markAsPending();
        } else {
            $this->markAsCompleted();
        }
    }

    public function reorder(int $newOrder): void
    {
        $this->update(['order' => $newOrder]);
    }

    public function duplicate(): Todo
    {
        $newTodo = $this->replicate(['completed_at']);
        $newTodo->status = self::STATUS_PENDING;
        $newTodo->save();

        // Duplicate subtasks
        foreach ($this->subtasks as $subtask) {
            $newSubtask = $subtask->replicate(['completed_at']);
            $newSubtask->parent_id = $newTodo->id;
            $newSubtask->status = self::STATUS_PENDING;
            $newSubtask->save();
        }

        return $newTodo;
    }

    public function addSubtask(array $data): Todo
    {
        return $this->subtasks()->create(array_merge($data, [
            'business_id' => $this->business_id,
            'created_by' => $data['created_by'] ?? $this->created_by,
            'type' => $this->type,
            'order' => $this->subtasks()->max('order') + 1,
        ]));
    }
}
