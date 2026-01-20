<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPointsTransaction extends Model
{
    use HasFactory, HasUuid;

    /**
     * Tranzaksiya turlari
     */
    public const TYPES = [
        'earned' => 'Qo\'shildi',
        'spent' => 'Sarflandi',
        'bonus' => 'Bonus',
        'penalty' => 'Jarima',
        'expired' => 'Muddati o\'tdi',
        'refund' => 'Qaytarildi',
    ];

    /**
     * Manba turlari
     */
    public const SOURCES = [
        'achievement' => 'Yutuq',
        'streak' => 'Streak',
        'leaderboard' => 'Reyting',
        'medal' => 'Medal',
        'bonus' => 'Bonus',
        'manual' => 'Qo\'lda',
        'reward_shop' => 'Mukofot do\'koni',
        'challenge' => 'Challange',
    ];

    protected $fillable = [
        'user_points_id',
        'type',
        'source',
        'source_id',
        'points',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * User points
     */
    public function userPoints(): BelongsTo
    {
        return $this->belongsTo(SalesUserPoints::class, 'user_points_id');
    }

    /**
     * Tranzaksiya turi bo'yicha
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Manba bo'yicha
     */
    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Sana oralig'i bo'yicha
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Faqat qo'shilganlar
     */
    public function scopeEarned(Builder $query): Builder
    {
        return $query->where('points', '>', 0);
    }

    /**
     * Faqat sarflanganlar
     */
    public function scopeSpent(Builder $query): Builder
    {
        return $query->where('points', '<', 0);
    }

    /**
     * Tranzaksiya turi labelini olish
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Manba labelini olish
     */
    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /**
     * Formatli ball
     */
    public function getFormattedPointsAttribute(): string
    {
        $prefix = $this->points > 0 ? '+' : '';

        return $prefix.$this->points;
    }

    /**
     * Rang (musbat/manfiy)
     */
    public function getPointsColorAttribute(): string
    {
        return $this->points >= 0 ? 'green' : 'red';
    }
}
