<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorAdStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'platform',
        'stat_date',
        'total_active_ads',
        'new_ads',
        'stopped_ads',
        'estimated_daily_spend_min',
        'estimated_daily_spend_max',
        'estimated_monthly_spend_min',
        'estimated_monthly_spend_max',
        'image_ads_count',
        'video_ads_count',
        'carousel_ads_count',
        'longest_running_ad_id',
        'longest_running_days',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'total_active_ads' => 'integer',
        'new_ads' => 'integer',
        'stopped_ads' => 'integer',
        'estimated_daily_spend_min' => 'decimal:2',
        'estimated_daily_spend_max' => 'decimal:2',
        'estimated_monthly_spend_min' => 'decimal:2',
        'estimated_monthly_spend_max' => 'decimal:2',
        'image_ads_count' => 'integer',
        'video_ads_count' => 'integer',
        'carousel_ads_count' => 'integer',
        'longest_running_days' => 'integer',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function longestRunningAd(): BelongsTo
    {
        return $this->belongsTo(CompetitorAd::class, 'longest_running_ad_id');
    }
}
