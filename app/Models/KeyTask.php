<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'key_task_map_id',
        'title',
        'description',
        'success_criteria',
        'weight',
        'due_date',
        'status',
        'completion_percent',
        'completed_at',
        'completion_notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function keyTaskMap(): BelongsTo
    {
        return $this->belongsTo(KeyTaskMap::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Mark as completed
    public function markCompleted(?string $notes = null, ?string $verifiedBy = null): void
    {
        $this->status = 'completed';
        $this->completion_percent = 100;
        $this->completed_at = now();
        $this->completion_notes = $notes;

        if ($verifiedBy) {
            $this->verified_by = $verifiedBy;
            $this->verified_at = now();
        }

        $this->save();

        // Update parent map's earned bonus
        $this->keyTaskMap?->updateEarnedBonus();
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    // Get remaining days
    public function getRemainingDaysAttribute(): int
    {
        return max(0, now()->diffInDays($this->due_date, false));
    }

    // Check if overdue
    public function getIsOverdueAttribute(): bool
    {
        return now()->gt($this->due_date) && !in_array($this->status, ['completed', 'failed']);
    }

    // Check if verified
    public function getIsVerifiedAttribute(): bool
    {
        return $this->verified_by !== null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', ['completed', 'failed']);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                     ->whereNotIn('status', ['completed', 'failed']);
    }
}
