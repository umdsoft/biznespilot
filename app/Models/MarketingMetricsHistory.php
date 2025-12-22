<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingMetricsHistory extends Model
{
    use HasUuids;

    protected $table = 'marketing_metrics_history';

    protected $fillable = [
        'business_id',
        'marketing_metrics_id',
        'monthly_budget',
        'ad_spend',
        'website_purpose',
        'monthly_visits',
        'website_conversion',
        'active_channels',
        'best_channel',
        'top_lead_channel',
        'instagram_followers',
        'telegram_subscribers',
        'facebook_followers',
        'roi_tracking_level',
        'marketing_roi',
        'content_activities',
        'marketing_challenges',
        'additional_data',
        'recorded_at',
        'change_type',
        'note',
    ];

    protected $casts = [
        'active_channels' => 'array',
        'content_activities' => 'array',
        'additional_data' => 'array',
        'website_conversion' => 'decimal:2',
        'marketing_roi' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /**
     * Biznes bilan bog'lanish
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Joriy metrika bilan bog'lanish
     */
    public function marketingMetrics(): BelongsTo
    {
        return $this->belongsTo(MarketingMetrics::class);
    }

    /**
     * Scope: Biznes bo'yicha
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope: Sana oralig'i bo'yicha
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Oxirgi N kun
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }
}
