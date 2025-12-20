<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'marketing_channel_id',
        'metric_date',
        'members_count',
        'new_members',
        'left_members',
        'posts_count',
        'total_views',
        'average_views',
        'reactions',
        'comments',
        'forwards',
        'shares',
        'bot_messages_sent',
        'bot_messages_received',
        'bot_commands_used',
        'bot_active_users',
        'link_clicks',
        'engagement_rate',
        'growth_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metric_date' => 'date',
        'engagement_rate' => 'decimal:2',
        'growth_rate' => 'decimal:2',
    ];

    /**
     * Get the marketing channel for this metric.
     */
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    /**
     * Calculate total engagement.
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->reactions + $this->comments + $this->forwards + $this->shares;
    }

    /**
     * Calculate engagement rate.
     */
    public function calculateEngagementRate(): float
    {
        if ($this->total_views === 0) {
            return 0;
        }

        return round(($this->getTotalEngagementAttribute() / $this->total_views) * 100, 2);
    }

    /**
     * Calculate growth rate.
     */
    public function calculateGrowthRate(): float
    {
        if ($this->members_count === 0) {
            return 0;
        }

        $netGrowth = $this->new_members - $this->left_members;
        return round(($netGrowth / $this->members_count) * 100, 2);
    }
}
