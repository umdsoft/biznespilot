<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackReport extends Model
{
    use HasUuid;

    // Types
    public const TYPE_BUG = 'bug';

    public const TYPE_SUGGESTION = 'suggestion';

    public const TYPE_QUESTION = 'question';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_BUG => 'Xatolik',
        self::TYPE_SUGGESTION => 'Taklif',
        self::TYPE_QUESTION => 'Savol',
        self::TYPE_OTHER => 'Boshqa',
    ];

    public const TYPE_COLORS = [
        self::TYPE_BUG => 'red',
        self::TYPE_SUGGESTION => 'blue',
        self::TYPE_QUESTION => 'purple',
        self::TYPE_OTHER => 'gray',
    ];

    public const TYPE_ICONS = [
        self::TYPE_BUG => 'bug',
        self::TYPE_SUGGESTION => 'lightbulb',
        self::TYPE_QUESTION => 'question',
        self::TYPE_OTHER => 'chat',
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_PENDING => 'Kutilmoqda',
        self::STATUS_IN_PROGRESS => 'Ko\'rib chiqilmoqda',
        self::STATUS_RESOLVED => 'Hal qilindi',
        self::STATUS_CLOSED => 'Yopildi',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'yellow',
        self::STATUS_IN_PROGRESS => 'blue',
        self::STATUS_RESOLVED => 'green',
        self::STATUS_CLOSED => 'gray',
    ];

    // Priorities
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Past',
        self::PRIORITY_MEDIUM => 'O\'rta',
        self::PRIORITY_HIGH => 'Yuqori',
        self::PRIORITY_URGENT => 'Shoshilinch',
    ];

    public const PRIORITY_COLORS = [
        self::PRIORITY_LOW => 'green',
        self::PRIORITY_MEDIUM => 'yellow',
        self::PRIORITY_HIGH => 'orange',
        self::PRIORITY_URGENT => 'red',
    ];

    protected $fillable = [
        'user_id',
        'business_id',
        'type',
        'title',
        'description',
        'status',
        'priority',
        'admin_notes',
        'resolved_by',
        'resolved_at',
        'page_url',
        'browser_info',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FeedbackAttachment::class);
    }

    // ==================== Scopes ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    // ==================== Accessors ====================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'gray';
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->status) {
            return self::STATUSES[self::STATUS_PENDING];
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        if (! $this->status) {
            return self::STATUS_COLORS[self::STATUS_PENDING];
        }

        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getPriorityLabelAttribute(): string
    {
        if (! $this->priority) {
            return self::PRIORITIES[self::PRIORITY_MEDIUM];
        }

        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        if (! $this->priority) {
            return self::PRIORITY_COLORS[self::PRIORITY_MEDIUM];
        }

        return self::PRIORITY_COLORS[$this->priority] ?? 'gray';
    }

    public function getIsResolvedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    // ==================== Methods ====================

    public function markAsInProgress(?string $adminId = null): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function markAsResolved(string $adminId, ?string $notes = null): void
    {
        $updateData = [
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $adminId,
            'resolved_at' => now(),
        ];

        if ($notes) {
            $updateData['admin_notes'] = $notes;
        }

        $this->update($updateData);
    }

    public function close(?string $notes = null): void
    {
        $updateData = ['status' => self::STATUS_CLOSED];

        if ($notes) {
            $updateData['admin_notes'] = $notes;
        }

        $this->update($updateData);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function addNote(string $note): void
    {
        $existingNotes = $this->admin_notes ?? '';
        $timestamp = now()->format('d.m.Y H:i');
        $newNote = "[{$timestamp}] {$note}";

        $this->update([
            'admin_notes' => $existingNotes ? "{$existingNotes}\n\n{$newNote}" : $newNote,
        ]);
    }
}
