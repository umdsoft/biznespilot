<?php

namespace App\Models\Bot\Service;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRequest extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_EN_ROUTE = 'en_route';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_DIAGNOSING = 'diagnosing';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ASSIGNED,
        self::STATUS_EN_ROUTE,
        self::STATUS_ARRIVED,
        self::STATUS_DIAGNOSING,
        self::STATUS_IN_PROGRESS,
    ];

    const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_ASSIGNED => [self::STATUS_EN_ROUTE, self::STATUS_CANCELLED],
        self::STATUS_EN_ROUTE => [self::STATUS_ARRIVED],
        self::STATUS_ARRIVED => [self::STATUS_DIAGNOSING],
        self::STATUS_DIAGNOSING => [self::STATUS_IN_PROGRESS],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
    ];

    const STATUS_TIMESTAMP_MAP = [
        self::STATUS_ASSIGNED => 'assigned_at',
        self::STATUS_EN_ROUTE => 'en_route_at',
        self::STATUS_ARRIVED => 'arrived_at',
        self::STATUS_DIAGNOSING => 'diagnosing_at',
        self::STATUS_IN_PROGRESS => 'in_progress_at',
        self::STATUS_COMPLETED => 'completed_at',
        self::STATUS_CANCELLED => 'cancelled_at',
    ];

    protected $fillable = [
        'business_id', 'request_number', 'telegram_user_id',
        'customer_name', 'customer_phone',
        'category_id', 'service_type_id', 'master_id',
        'status', 'description', 'images',
        'address', 'landmark', 'lat', 'lng',
        'preferred_date', 'preferred_time_slot',
        'diagnosis_notes', 'work_description',
        'parts_used', 'labor_cost', 'parts_cost', 'total_cost',
        'cost_approved', 'payment_method', 'payment_status',
        'warranty_until', 'rating', 'review',
        'assigned_at', 'en_route_at', 'arrived_at',
        'diagnosing_at', 'in_progress_at', 'completed_at',
        'cancelled_at', 'cancel_reason',
    ];

    protected $casts = [
        'images' => 'array',
        'parts_used' => 'array',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cost_approved' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'rating' => 'integer',
        'telegram_user_id' => 'integer',
        'preferred_date' => 'date',
        'warranty_until' => 'date',
        'assigned_at' => 'datetime',
        'en_route_at' => 'datetime',
        'arrived_at' => 'datetime',
        'diagnosing_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(ServiceMaster::class, 'master_id');
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

    public static function generateRequestNumber(): string
    {
        $last = static::withoutGlobalScope('business')
            ->where('request_number', 'like', 'SR-%')
            ->orderByDesc('created_at')
            ->value('request_number');

        $num = $last ? (int) substr($last, 3) + 1 : 1;

        return 'SR-' . str_pad($num, 4, '0', STR_PAD_LEFT);
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

    public function scopeByMaster($query, string $masterId)
    {
        return $query->where('master_id', $masterId);
    }
}
