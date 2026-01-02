<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AdIntegration extends Model
{
    protected $fillable = [
        'business_id',
        'platform',
        'account_id',
        'account_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'developer_token',
        'customer_id',
        'login_customer_id',
        'is_active',
        'last_synced_at',
        'sync_status',
        'sync_error',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'developer_token',
    ];

    /**
     * Platform types
     */
    const PLATFORM_GOOGLE_ADS = 'google_ads';
    const PLATFORM_YANDEX_DIRECT = 'yandex_direct';
    const PLATFORM_YOUTUBE = 'youtube';
    const PLATFORM_FACEBOOK = 'facebook';

    /**
     * Get the business that owns the integration.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Encrypt and set access token
     */
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt and get access token
     */
    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Encrypt and set refresh token
     */
    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt and get refresh token
     */
    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if integration needs re-authentication
     */
    public function needsReauth(): bool
    {
        return $this->isTokenExpired() && empty($this->refresh_token);
    }

    /**
     * Mark sync as started
     */
    public function markSyncStarted(): void
    {
        $this->update([
            'sync_status' => 'syncing',
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as completed
     */
    public function markSyncCompleted(): void
    {
        $this->update([
            'sync_status' => 'completed',
            'last_synced_at' => now(),
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }

    /**
     * Scope to filter by platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get platform display name
     */
    public function getPlatformNameAttribute(): string
    {
        return match ($this->platform) {
            self::PLATFORM_GOOGLE_ADS => 'Google Ads',
            self::PLATFORM_YANDEX_DIRECT => 'Yandex Direct',
            self::PLATFORM_YOUTUBE => 'YouTube',
            self::PLATFORM_FACEBOOK => 'Facebook Ads',
            default => ucfirst(str_replace('_', ' ', $this->platform)),
        };
    }
}
