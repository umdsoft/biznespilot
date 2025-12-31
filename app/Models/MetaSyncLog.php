<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaSyncLog extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'sync_type',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_message',
        'error_details',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_details' => 'array',
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'failed_items' => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('started_at', 'desc')->limit($limit);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['started', 'in_progress']);
    }

    /**
     * Mark as in progress
     */
    public function markInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage, ?array $errorDetails = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }
        return (int) round(($this->processed_items / $this->total_items) * 100);
    }

    /**
     * Check if sync is running
     */
    public function isRunning(): bool
    {
        return \in_array($this->status, ['started', 'in_progress']);
    }
}
