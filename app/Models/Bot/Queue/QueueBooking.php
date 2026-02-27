<?php

namespace App\Models\Bot\Queue;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QueueBooking extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PROGRESS,
    ];

    const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
    ];

    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_NO_SHOW],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
    ];

    const STATUS_TIMESTAMP_MAP = [
        self::STATUS_CONFIRMED => 'confirmed_at',
        self::STATUS_IN_PROGRESS => 'started_at',
        self::STATUS_COMPLETED => 'completed_at',
        self::STATUS_CANCELLED => 'cancelled_at',
    ];

    protected $fillable = [
        'business_id', 'booking_number', 'telegram_user_id',
        'customer_name', 'customer_phone',
        'service_id', 'branch_id', 'specialist_id',
        'date', 'start_time', 'end_time', 'queue_number',
        'status', 'people_ahead', 'estimated_wait',
        'price', 'payment_status', 'payment_method', 'notes',
        'reminder_sent', 'rating', 'review',
        'confirmed_at', 'started_at', 'completed_at',
        'cancelled_at', 'cancel_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'queue_number' => 'integer',
        'people_ahead' => 'integer',
        'estimated_wait' => 'integer',
        'price' => 'decimal:2',
        'reminder_sent' => 'boolean',
        'rating' => 'integer',
        'telegram_user_id' => 'integer',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(QueueService::class, 'service_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(QueueBranch::class, 'branch_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(QueueSpecialist::class, 'specialist_id');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(QueueTimeSlot::class, 'booking_id');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function transitionTo(string $newStatus): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        $tsField = self::STATUS_TIMESTAMP_MAP[$newStatus] ?? null;
        if ($tsField) {
            $this->{$tsField} = now();
        }

        return $this->save();
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, int $telegramUserId)
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    public static function generateBookingNumber(): string
    {
        $last = static::withoutGlobalScope('business')
            ->where('booking_number', 'like', 'NV-%')
            ->orderByDesc('created_at')
            ->value('booking_number');

        $num = $last ? (int) substr($last, 3) + 1 : 1;

        return 'NV-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
