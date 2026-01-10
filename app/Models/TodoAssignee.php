<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoAssignee extends Model
{
    use HasUuid;

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

    // ==================== Relationships ====================

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Methods ====================

    public function markAsCompleted(?string $note = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'note' => $note,
        ]);

        // Update parent todo's completed count
        $this->todo->updateAssigneeCounts();
    }

    public function markAsIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        // Update parent todo's completed count
        $this->todo->updateAssigneeCounts();
    }

    public function toggleComplete(): void
    {
        if ($this->is_completed) {
            $this->markAsIncomplete();
        } else {
            $this->markAsCompleted();
        }
    }
}
