<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CompetitorMetric extends Model
{
    use HasUuid;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'competitor_id',
        'recorded_date',
        'metric_type',
        'platform',
        'value',
        'instagram_followers',
        'instagram_following',
        'instagram_posts',
        'instagram_engagement_rate',
        'instagram_avg_likes',
        'instagram_avg_comments',
        'telegram_members',
        'telegram_posts_count',
        'telegram_engagement_rate',
        'telegram_avg_views',
        'facebook_followers',
        'facebook_likes',
        'facebook_posts',
        'facebook_engagement_rate',
        'tiktok_followers',
        'tiktok_likes',
        'tiktok_videos',
        'tiktok_engagement_rate',
        'youtube_subscribers',
        'youtube_videos',
        'youtube_total_views',
        'website_traffic',
        'website_page_views',
        'website_bounce_rate',
        'follower_growth_rate',
        'engagement_growth_rate',
        'raw_data',
        'data_source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recorded_date' => 'date',
        'instagram_engagement_rate' => 'decimal:2',
        'telegram_engagement_rate' => 'decimal:2',
        'facebook_engagement_rate' => 'decimal:2',
        'tiktok_engagement_rate' => 'decimal:2',
        'website_bounce_rate' => 'decimal:2',
        'follower_growth_rate' => 'decimal:2',
        'engagement_growth_rate' => 'decimal:2',
        'raw_data' => 'array',
    ];

    /**
     * Get the competitor that owns the metric.
     */
    public function competitor()
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('recorded_date', today());
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_date', [$startDate, $endDate]);
    }

    /**
     * Scope for latest metrics
     */
    public function scopeLatestMetrics($query)
    {
        return $query->orderBy('recorded_date', 'desc');
    }

    /**
     * Calculate growth rates compared to previous metric
     */
    public function calculateGrowthRates()
    {
        $previous = static::where('competitor_id', $this->competitor_id)
            ->where('recorded_date', '<', $this->recorded_date)
            ->orderBy('recorded_date', 'desc')
            ->first();

        if (!$previous) {
            return;
        }

        // Calculate follower growth (Instagram as primary)
        if ($this->instagram_followers && $previous->instagram_followers) {
            $this->follower_growth_rate = (($this->instagram_followers - $previous->instagram_followers) / $previous->instagram_followers) * 100;
        }

        // Calculate engagement growth
        if ($this->instagram_engagement_rate && $previous->instagram_engagement_rate) {
            $this->engagement_growth_rate = (($this->instagram_engagement_rate - $previous->instagram_engagement_rate) / $previous->instagram_engagement_rate) * 100;
        }

        $this->save();
    }
}
