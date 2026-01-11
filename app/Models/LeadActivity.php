<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'title',
        'description',
        'changes',
        'metadata',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Activity types
     */
    public const TYPE_CREATED = 'created';
    public const TYPE_UPDATED = 'updated';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_NOTE_ADDED = 'note_added';
    public const TYPE_ASSIGNED = 'assigned';
    public const TYPE_CONTACTED = 'contacted';
    public const TYPE_TASK_CREATED = 'task_created';
    public const TYPE_TASK_COMPLETED = 'task_completed';

    /**
     * Get the lead that owns the activity.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a new activity for a lead.
     */
    public static function log(
        string $leadId,
        string $type,
        string $title,
        ?string $description = null,
        ?array $changes = null,
        ?array $metadata = null,
        ?string $userId = null
    ): self {
        return self::create([
            'lead_id' => $leadId,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'changes' => $changes,
            'metadata' => $metadata,
        ]);
    }
}
