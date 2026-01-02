<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YandexMetricaAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'counter_id',
        'counter_name',
        'website_url',
        'counter_status',
        'visitors',
        'visits',
        'pageviews',
        'avg_visit_duration',
        'bounce_rate',
        'depth',
        'conversion_rate',
        'goals_reached',
        'revenue',
        'is_active',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_synced_at',
        'disconnected_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'avg_visit_duration' => 'float',
        'bounce_rate' => 'float',
        'depth' => 'float',
        'conversion_rate' => 'float',
        'revenue' => 'decimal:2',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(YandexMetricaSnapshot::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(YandexMetricaDailyReport::class);
    }

    /**
     * Check if access token needs refresh
     */
    public function needsTokenRefresh(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Get engagement rate (returning visitors)
     */
    public function getEngagementRateAttribute(): float
    {
        if ($this->visitors <= 0) {
            return 0;
        }

        // Higher visits per visitor = better engagement
        return round(($this->visits / $this->visitors), 2);
    }

    /**
     * Get average revenue per visit
     */
    public function getRevenuePerVisitAttribute(): float
    {
        if ($this->visits <= 0) {
            return 0;
        }

        return round($this->revenue / $this->visits, 2);
    }
}
