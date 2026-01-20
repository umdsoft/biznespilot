<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesStreakHistory extends Model
{
    use HasFactory, HasUuid;

    /**
     * Event turlari
     */
    public const EVENT_TYPES = [
        'increment' => 'Oshirildi',
        'break' => 'Buzildi',
        'freeze' => 'Muzlatildi',
        'unfreeze' => 'Ochildi',
        'milestone' => 'Bosqich',
    ];

    protected $table = 'sales_streak_history';

    protected $fillable = [
        'streak_id',
        'event_type',
        'streak_value',
        'event_date',
        'event_data',
    ];

    protected $casts = [
        'streak_value' => 'integer',
        'event_date' => 'date',
        'event_data' => 'array',
    ];

    /**
     * Streak
     */
    public function streak(): BelongsTo
    {
        return $this->belongsTo(SalesUserStreak::class, 'streak_id');
    }

    /**
     * Event turi bo'yicha
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }

    /**
     * Sana oralig'i bo'yicha
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    /**
     * Event turi labelini olish
     */
    public function getEventTypeLabelAttribute(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }
}
