<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TodoAssignee — Todo vazifaga biriktirilgan foydalanuvchi (pivot model).
 *
 * Bir vazifaga bir nechta user biriktirilishi mumkin.
 * Har user alohida is_completed bilan o'z qismini yopishi mumkin.
 */
class TodoAssignee extends Model
{
    use HasUuid;

    protected $table = 'todo_assignees';

    protected $fillable = [
        'todo_id',
        'user_id',
        'is_completed',
        'completed_at',
        'note',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * O'z qismini bajarilgan deb belgilash
     */
    public function markCompleted(?string $note = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'note' => $note,
        ]);
    }

    /**
     * Qaytarish
     */
    public function markIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);
    }

    /**
     * Scope: completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope: pending
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }
}
