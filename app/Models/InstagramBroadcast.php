<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramBroadcast extends Model
{
    use HasUuid;
    protected $fillable = [
        'instagram_account_id',
        'name',
        'message',
        'media',
        'target_tags',
        'target_filters',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'opened_count',
    ];

    protected $casts = [
        'media' => 'array',
        'target_tags' => 'array',
        'target_filters' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeReady($query)
    {
        return $query->scheduled()
            ->where('scheduled_at', '<=', now());
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_SENDING,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function fail(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
        ]);
    }

    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }

    public function getDeliveryRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->opened_count / $this->sent_count) * 100, 2);
    }
}
