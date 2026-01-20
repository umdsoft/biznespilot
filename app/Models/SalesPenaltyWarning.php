<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesPenaltyWarning extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Ogohlantirish turlari
     */
    public const WARNING_TYPES = [
        'system' => 'Tizim ogohlantirishlari',
        'verbal' => 'Og\'zaki ogohlantirish',
        'written' => 'Yozma ogohlantirish',
        'final' => 'Oxirgi ogohlantirish',
    ];

    /**
     * Warning statuslari
     */
    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'warned' => 'Ogohlantirildi',
        'resolved' => 'Hal qilindi',
        'converted' => 'Jarimaga aylandi',
        'cancelled' => 'Bekor qilindi',
    ];

    protected $fillable = [
        'business_id',
        'penalty_rule_id',
        'rule_code',
        'user_id',
        'warning_type',
        'reason',
        'description',
        'related_type',
        'related_id',
        'warning_number',
        'status',
        'issued_by',
        'acknowledged_at',
        'expires_at',
        'deadline_at',
        'auto_convert',
        'converted_at',
    ];

    protected $casts = [
        'warning_number' => 'integer',
        'acknowledged_at' => 'datetime',
        'expires_at' => 'datetime',
        'deadline_at' => 'datetime',
        'auto_convert' => 'boolean',
        'converted_at' => 'datetime',
    ];

    /**
     * Jarima qoidasi
     */
    public function penaltyRule(): BelongsTo
    {
        return $this->belongsTo(SalesPenaltyRule::class, 'penalty_rule_id');
    }

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kim tomonidan berilgan
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Bog'liq model
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }

    /**
     * Faol ogohlantirishlar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Muddati o'tgan
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Ko'rilmagan
     */
    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * Foydalanuvchi uchun
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Qoida uchun
     */
    public function scopeForRule(Builder $query, string $ruleId): Builder
    {
        return $query->where('penalty_rule_id', $ruleId);
    }

    /**
     * Turi labelini olish
     */
    public function getTypeLabelAttribute(): string
    {
        return self::WARNING_TYPES[$this->warning_type] ?? $this->warning_type;
    }

    /**
     * Faolmi
     */
    public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return now()->isBefore($this->expires_at);
    }

    /**
     * Muddati o'tdimi
     */
    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    /**
     * Ko'rganini belgilash
     */
    public function acknowledge(): void
    {
        if ($this->acknowledged_at === null) {
            $this->update([
                'acknowledged_at' => now(),
            ]);
        }
    }

    /**
     * Qolgan muddat
     */
    public function getRemainingTimeAttribute(): ?string
    {
        if (! $this->expires_at || $this->isExpired()) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Ogohlantirish darajasi (1/3, 2/3, 3/3)
     */
    public function getWarningLevelAttribute(): string
    {
        $rule = $this->penaltyRule;
        $total = $rule ? $rule->warnings_before_penalty : 3;

        return "{$this->warning_number}/{$total}";
    }

    /**
     * Jiddiylik darajasi
     */
    public function getSeverityAttribute(): string
    {
        $rule = $this->penaltyRule;
        $total = $rule ? $rule->warnings_before_penalty : 3;
        $percent = ($this->warning_number / $total) * 100;

        return match (true) {
            $percent >= 100 => 'critical',
            $percent >= 66 => 'high',
            $percent >= 33 => 'medium',
            default => 'low',
        };
    }

    /**
     * Severity color
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            default => 'blue',
        };
    }
}
