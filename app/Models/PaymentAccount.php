<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    // Providers
    public const PROVIDER_PAYME = 'payme';

    public const PROVIDER_CLICK = 'click';

    public const PROVIDERS = [
        self::PROVIDER_PAYME => 'Payme',
        self::PROVIDER_CLICK => 'Click',
    ];

    // Payme URLs
    public const PAYME_CHECKOUT_URL = 'https://checkout.paycom.uz';

    public const PAYME_TEST_CHECKOUT_URL = 'https://test.paycom.uz';

    // Click URLs
    public const CLICK_API_URL = 'https://api.click.uz/v2/merchant';

    public const CLICK_CHECKOUT_URL = 'https://my.click.uz/services/pay';

    protected $fillable = [
        'business_id',
        'provider',
        'name',
        'merchant_id',
        'merchant_key',
        'service_id',
        'merchant_user_id',
        'secret_key',
        'is_active',
        'is_test_mode',
        'settings',
        'last_transaction_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'settings' => 'array',
        'last_transaction_at' => 'datetime',
    ];

    protected $hidden = [
        'merchant_key',
        'secret_key',
    ];

    // ==================== Relationships ====================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePayme($query)
    {
        return $query->where('provider', self::PROVIDER_PAYME);
    }

    public function scopeClick($query)
    {
        return $query->where('provider', self::PROVIDER_CLICK);
    }

    // ==================== Accessors ====================

    public function getProviderLabelAttribute(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }

    public function getCheckoutUrlAttribute(): string
    {
        if ($this->provider === self::PROVIDER_PAYME) {
            return $this->is_test_mode
                ? self::PAYME_TEST_CHECKOUT_URL
                : self::PAYME_CHECKOUT_URL;
        }

        return self::CLICK_CHECKOUT_URL;
    }

    // ==================== Methods ====================

    /**
     * Check if account is properly configured
     */
    public function isConfigured(): bool
    {
        if ($this->provider === self::PROVIDER_PAYME) {
            return ! empty($this->merchant_id) && ! empty($this->merchant_key);
        }

        return ! empty($this->service_id) && ! empty($this->secret_key);
    }

    /**
     * Check if this is Payme account
     */
    public function isPayme(): bool
    {
        return $this->provider === self::PROVIDER_PAYME;
    }

    /**
     * Check if this is Click account
     */
    public function isClick(): bool
    {
        return $this->provider === self::PROVIDER_CLICK;
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
     * Update last transaction timestamp
     */
    public function touchLastTransaction(): void
    {
        $this->update(['last_transaction_at' => now()]);
    }
}
