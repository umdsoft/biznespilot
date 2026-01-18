<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramBroadcast extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'created_by',
        'name',
        'content',
        'keyboard',
        'target_filter',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'failed_count',
        'blocked_count',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'content' => 'array',
        'keyboard' => 'array',
        'target_filter' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relations
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    // Helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canStart(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'paused']);
    }

    public function canPause(): bool
    {
        return $this->status === 'sending';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'sending', 'paused']);
    }

    public function start(): void
    {
        $this->update([
            'status' => 'sending',
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'sending']);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function schedule(\DateTimeInterface $datetime): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $datetime,
        ]);
    }

    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    public function incrementDelivered(): void
    {
        $this->increment('delivered_count');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }

    public function incrementBlocked(): void
    {
        $this->increment('blocked_count');
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    public function getDeliveryRate(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 2);
    }

    public function getBlockedRate(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->blocked_count / $this->sent_count) * 100, 2);
    }

    public function getContentText(): ?string
    {
        return data_get($this->content, 'text');
    }

    public function getContentType(): string
    {
        return data_get($this->content, 'type', 'text');
    }

    public function hasMedia(): bool
    {
        return in_array($this->getContentType(), ['photo', 'video', 'document']);
    }

    public function hasKeyboard(): bool
    {
        return ! empty($this->keyboard);
    }

    public function getFilterDescription(): string
    {
        if (empty($this->target_filter)) {
            return 'Barcha foydalanuvchilar';
        }

        $parts = [];

        if (! empty($this->target_filter['tags'])) {
            $parts[] = 'Teglar: '.implode(', ', $this->target_filter['tags']);
        }

        if (! empty($this->target_filter['exclude_blocked'])) {
            $parts[] = 'Bloklangan foydalanuvchilar chiqarilgan';
        }

        if (! empty($this->target_filter['active_after'])) {
            $parts[] = 'Oxirgi faollik: '.$this->target_filter['active_after'];
        }

        return empty($parts) ? 'Barcha foydalanuvchilar' : implode('; ', $parts);
    }
}
