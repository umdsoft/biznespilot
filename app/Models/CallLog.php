<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'lead_id',
        'user_id',
        'provider',
        'provider_call_id',
        'direction',
        'from_number',
        'to_number',
        'status',
        'duration',
        'wait_time',
        'recording_url',
        'notes',
        'metadata',
        'started_at',
        'answered_at',
        'ended_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_MISSED = 'missed';
    public const STATUS_BUSY = 'busy';
    public const STATUS_NO_ANSWER = 'no_answer';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Direction constants
     */
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * Provider constants
     */
    public const PROVIDER_PBX = 'pbx';
    public const PROVIDER_SIPUNI = 'sipuni';

    /**
     * Get the business that owns this call
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the lead associated with this call
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who made/received this call
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only outbound calls
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    /**
     * Scope: Only inbound calls
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * Scope: Only answered calls
     */
    public function scopeAnswered($query)
    {
        return $query->whereIn('status', [self::STATUS_ANSWERED, self::STATUS_COMPLETED]);
    }

    /**
     * Scope: Only missed calls
     */
    public function scopeMissed($query)
    {
        return $query->whereIn('status', [self::STATUS_MISSED, self::STATUS_NO_ANSWER]);
    }

    /**
     * Check if call was answered
     */
    public function wasAnswered(): bool
    {
        return in_array($this->status, [self::STATUS_ANSWERED, self::STATUS_COMPLETED]);
    }

    /**
     * Check if call is inbound
     */
    public function isInbound(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND;
    }

    /**
     * Check if call is outbound
     */
    public function isOutbound(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND;
    }

    /**
     * Get formatted duration (mm:ss)
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get status label in Uzbek
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_INITIATED => 'Boshlandi',
            self::STATUS_RINGING => 'Jiringlamoqda',
            self::STATUS_ANSWERED => 'Javob berildi',
            self::STATUS_COMPLETED => 'Tugallandi',
            self::STATUS_FAILED => 'Xatolik',
            self::STATUS_MISSED => 'O\'tkazib yuborildi',
            self::STATUS_BUSY => 'Band',
            self::STATUS_NO_ANSWER => 'Javob yo\'q',
            self::STATUS_CANCELLED => 'Bekor qilindi',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get direction label in Uzbek
     */
    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === self::DIRECTION_INBOUND ? 'Kiruvchi' : 'Chiquvchi';
    }

    /**
     * Get full descriptive label combining direction and status in Uzbek
     * Example: "Kiruvchi - Javob berildi", "Chiquvchi - Javob berilmadi"
     */
    public function getFullLabelAttribute(): string
    {
        $direction = $this->direction === self::DIRECTION_INBOUND ? 'Kiruvchi' : 'Chiquvchi';

        // Determine status description
        $statusDesc = match ($this->status) {
            self::STATUS_COMPLETED, self::STATUS_ANSWERED => 'Javob berildi',
            self::STATUS_MISSED, self::STATUS_NO_ANSWER, self::STATUS_FAILED, self::STATUS_CANCELLED => 'Javob berilmadi',
            self::STATUS_BUSY => 'Band',
            self::STATUS_RINGING => 'Jiringlamoqda',
            self::STATUS_INITIATED => 'Boshlanmoqda',
            default => $this->status,
        };

        return "{$direction} - {$statusDesc}";
    }

    /**
     * Mark call as answered
     */
    public function markAsAnswered(): void
    {
        $this->update([
            'status' => self::STATUS_ANSWERED,
            'answered_at' => now(),
        ]);
    }

    /**
     * Mark call as completed
     */
    public function markAsCompleted(int $duration = 0): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'duration' => $duration,
            'ended_at' => now(),
        ]);
    }

    /**
     * Mark call as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
            'ended_at' => now(),
        ]);
    }
}
