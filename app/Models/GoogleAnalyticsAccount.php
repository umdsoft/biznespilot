<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleAnalyticsAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'property_id',
        'property_name',
        'measurement_id',
        'website_url',
        'account_id',
        'account_name',
        'currency',
        'timezone',
        'total_users',
        'active_users',
        'sessions',
        'pageviews',
        'avg_session_duration',
        'bounce_rate',
        'conversion_rate',
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
        'avg_session_duration' => 'float',
        'bounce_rate' => 'float',
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
        return $this->hasMany(GoogleAnalyticsSnapshot::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(GoogleAnalyticsDailyReport::class);
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
     * Get engagement rate (active users / total users)
     */
    public function getEngagementRateAttribute(): float
    {
        if ($this->total_users <= 0) {
            return 0;
        }

        return round(($this->active_users / $this->total_users) * 100, 2);
    }

    /**
     * Get conversion value (revenue / sessions)
     */
    public function getConversionValueAttribute(): float
    {
        if ($this->sessions <= 0) {
            return 0;
        }

        return round($this->revenue / $this->sessions, 2);
    }
}
