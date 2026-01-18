<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PbxAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    /**
     * Provider constants
     */
    public const PROVIDER_ONLINEPBX = 'onlinepbx';

    protected $fillable = [
        'business_id',
        'provider',
        'name',
        'api_url',
        'api_key',
        'api_secret',
        'caller_id',
        'extension',
        'is_active',
        'balance',
        'settings',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    /**
     * Get the business that owns this account
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope: Only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get API URL with trailing slash
     */
    public function getApiUrlAttribute($value): string
    {
        return rtrim($value, '/').'/';
    }

    /**
     * Check if account is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->api_url) && ! empty($this->api_key);
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set setting value
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Check if this is OnlinePBX account
     */
    public function isOnlinePbx(): bool
    {
        return $this->provider === self::PROVIDER_ONLINEPBX;
    }

    /**
     * Scope: Filter by provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: OnlinePBX accounts
     */
    public function scopeOnlinePbx($query)
    {
        return $query->where('provider', self::PROVIDER_ONLINEPBX);
    }

    /**
     * Get provider label in Uzbek
     */
    public function getProviderLabelAttribute(): string
    {
        return match ($this->provider) {
            self::PROVIDER_ONLINEPBX => 'OnlinePBX',
            default => $this->provider,
        };
    }
}
