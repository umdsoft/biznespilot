<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TelegramChannelDailyStat — Har kuni 23:59 da yozib qo'yiladigan kunlik rollup.
 *
 * Grafiklar va digest uchun asosiy manba. Bu jadval frontend'da ApexCharts
 * ga uzatiladi.
 */
class TelegramChannelDailyStat extends Model
{
    use HasUuid;

    protected $fillable = [
        'telegram_channel_id',
        'stat_date',
        'subscriber_count',
        'new_subscribers',
        'left_subscribers',
        'net_growth',
        'posts_count',
        'total_views',
        'average_views',
        'total_reactions',
        'total_forwards',
        'total_replies',
        'engagement_rate',
        'growth_rate',
        'top_post_id',
    ];

    protected $casts = [
        // Keep stat_date as raw string 'YYYY-MM-DD' — avoids SQLite/Laravel
        // datetime-format mismatch when comparing. Cast to Carbon manually when needed.
        'subscriber_count' => 'integer',
        'new_subscribers' => 'integer',
        'left_subscribers' => 'integer',
        'net_growth' => 'integer',
        'posts_count' => 'integer',
        'total_views' => 'integer',
        'average_views' => 'integer',
        'total_reactions' => 'integer',
        'total_forwards' => 'integer',
        'total_replies' => 'integer',
        'engagement_rate' => 'decimal:2',
        'growth_rate' => 'decimal:2',
    ];

    /**
     * Always format stat_date as plain YYYY-MM-DD before storing.
     */
    public function setStatDateAttribute($value): void
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            $this->attributes['stat_date'] = $value->toDateString();
            return;
        }
        if (is_string($value)) {
            // Strip time portion if present
            $this->attributes['stat_date'] = substr($value, 0, 10);
            return;
        }
        $this->attributes['stat_date'] = (string) $value;
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class, 'telegram_channel_id');
    }

    public function topPost(): BelongsTo
    {
        return $this->belongsTo(TelegramChannelPost::class, 'top_post_id');
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('stat_date', [$from, $to]);
    }

    public function scopeForChannel(Builder $query, string $channelId): Builder
    {
        return $query->where('telegram_channel_id', $channelId);
    }
}
