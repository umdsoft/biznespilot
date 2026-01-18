<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingChannel extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'type',
        'platform',
        'platform_account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'description',
        'is_active',
        'last_synced_at',
        'config',
        'metrics',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'metrics' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the marketing spends for the channel.
     */
    public function marketingSpends(): HasMany
    {
        return $this->hasMany(MarketingSpend::class, 'channel_id');
    }

    /**
     * Get the content posts for the channel.
     */
    public function contentPosts(): HasMany
    {
        return $this->hasMany(ContentPost::class, 'channel_id');
    }

    /**
     * Get the leads from this channel.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'source_id');
    }

    /**
     * Get the Instagram metrics for the channel.
     */
    public function instagramMetrics(): HasMany
    {
        return $this->hasMany(InstagramMetric::class);
    }

    /**
     * Get the Telegram metrics for the channel.
     */
    public function telegramMetrics(): HasMany
    {
        return $this->hasMany(TelegramMetric::class);
    }

    /**
     * Get the Facebook metrics for the channel.
     */
    public function facebookMetrics(): HasMany
    {
        return $this->hasMany(FacebookMetric::class);
    }

    /**
     * Get the Google Ads metrics for the channel.
     */
    public function googleAdsMetrics(): HasMany
    {
        return $this->hasMany(GoogleAdsMetric::class);
    }

    /**
     * Get the latest metrics for this channel based on channel type.
     */
    public function latestMetrics()
    {
        return match ($this->type) {
            'instagram' => $this->instagramMetrics()->latest('metric_date')->first(),
            'telegram' => $this->telegramMetrics()->latest('metric_date')->first(),
            'facebook' => $this->facebookMetrics()->latest('metric_date')->first(),
            'google_ads' => $this->googleAdsMetrics()->latest('metric_date')->first(),
            default => null,
        };
    }
}
