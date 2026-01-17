<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtelAccount extends Model
{
    use HasFactory, HasUuid, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'password',
        'access_token',
        'token_expires_at',
        'caller_id',
        'extension',
        'is_active',
        'balance',
        'currency',
        'settings',
        'webhook_secret',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'integer',
        'settings' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'access_token',
        'webhook_secret',
    ];

    /**
     * Check if account is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Check if token is valid
     */
    public function hasValidToken(): bool
    {
        return !empty($this->access_token)
            && $this->token_expires_at
            && $this->token_expires_at->isFuture();
    }

    /**
     * Get API base URL
     */
    public function getApiBaseUrl(): string
    {
        return $this->getSetting('api_url', 'https://api.utel.uz');
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value
     */
    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        return $this;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 0, '.', ' ') . ' ' . $this->currency;
    }
}
