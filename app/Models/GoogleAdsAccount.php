<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleAdsAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'customer_id',
        'account_name',
        'currency',
        'timezone',
        'total_campaigns',
        'active_campaigns',
        'impressions',
        'clicks',
        'ctr',
        'avg_cpc',
        'cost',
        'conversions',
        'conversion_rate',
        'conversion_value',
        'roas',
        'is_active',
        'access_token',
        'refresh_token',
        'developer_token',
        'token_expires_at',
        'last_synced_at',
        'disconnected_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ctr' => 'float',
        'avg_cpc' => 'decimal:2',
        'cost' => 'decimal:2',
        'conversion_rate' => 'float',
        'conversion_value' => 'decimal:2',
        'roas' => 'float',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'developer_token',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(GoogleAdsCampaign::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(GoogleAdsSnapshot::class);
    }

    /**
     * Check if access token needs refresh
     */
    public function needsTokenRefresh(): bool
    {
        if (! $this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Get cost per conversion
     */
    public function getCostPerConversionAttribute(): float
    {
        if ($this->conversions <= 0) {
            return 0;
        }

        return round($this->cost / $this->conversions, 2);
    }

    /**
     * Get profit (conversion_value - cost)
     */
    public function getProfitAttribute(): float
    {
        return round($this->conversion_value - $this->cost, 2);
    }

    /**
     * Get ROI percentage
     */
    public function getRoiPercentageAttribute(): float
    {
        if ($this->cost <= 0) {
            return 0;
        }

        return round((($this->conversion_value - $this->cost) / $this->cost) * 100, 2);
    }
}
