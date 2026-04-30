<?php

namespace App\Models\Store;

use App\Enums\BotType;
use App\Models\Business;
use App\Models\TelegramBot;
use App\Services\Store\BotTypeRegistry;
use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TelegramStore extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $table = 'telegram_stores';

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'store_type',
        'name',
        'slug',
        'description',
        'logo_url',
        'banner_url',
        'currency',
        'phone',
        'address',
        'is_active',
        'settings',
        'enabled_features',
        'theme',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'enabled_features' => 'array',
        'theme' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name) . '-' . Str::random(6);
            }
            if (empty($store->store_type)) {
                $store->store_type = 'ecommerce';
            }
        });
    }

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function telegramBot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(StoreCategory::class, 'store_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class, 'store_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(StoreCustomer::class, 'store_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'store_id');
    }

    public function carts(): HasMany
    {
        return $this->hasMany(StoreCart::class, 'store_id');
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(StorePromoCode::class, 'store_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class, 'store_id');
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(StoreDeliveryZone::class, 'store_id');
    }

    public function dailyAnalytics(): HasMany
    {
        return $this->hasMany(StoreAnalyticsDaily::class, 'store_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(StorePaymentTransaction::class, 'store_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('store_type', $type);
    }

    // Bot Type helpers

    /**
     * BotType enum qaytarish (sidebar menyu, ranglar uchun)
     */
    public function getBotTypeEnum(): ?BotType
    {
        return $this->store_type ? BotType::tryFrom($this->store_type) : null;
    }

    /**
     * Sidebar menyu uchun to'liq bot type ma'lumotlari
     */
    public function getBotTypeInfo(): array
    {
        $botType = $this->getBotTypeEnum();
        if (! $botType) {
            return [];
        }

        return [
            'value' => $botType->value,
            'label' => $botType->label(),
            'icon' => $botType->icon(),
            'color' => $botType->color(),
            'bg_color' => $botType->bgColor(),
            'primary_action' => $botType->primaryActionLabel(),
            'sidebar_menu' => $botType->sidebarMenu(),
        ];
    }

    public function getBotTypeConfig(): array
    {
        return app(BotTypeRegistry::class)->getConfig($this->store_type);
    }

    public function hasFeature(string $feature): bool
    {
        if (!empty($this->enabled_features)) {
            return in_array($feature, $this->enabled_features);
        }

        return app(BotTypeRegistry::class)->hasFeature($this->store_type, $feature);
    }

    public function getEnabledFeatures(): array
    {
        if (!empty($this->enabled_features)) {
            return $this->enabled_features;
        }

        return app(BotTypeRegistry::class)->getFeatures($this->store_type);
    }

    public function getCatalogModelClass(): ?string
    {
        return app(BotTypeRegistry::class)->getCatalogModel($this->store_type);
    }

    public function getCatalogLabel(): string
    {
        return app(BotTypeRegistry::class)->getCatalogLabel($this->store_type);
    }

    public function getCatalogLabelSingular(): string
    {
        return app(BotTypeRegistry::class)->getCatalogLabelSingular($this->store_type);
    }

    /**
     * Whether this store has a catalog (products/services). False for
     * catalog-less types like 'leadcapture' — callers should check before
     * touching catalog tables/relations.
     */
    public function hasCatalog(): bool
    {
        return app(BotTypeRegistry::class)->hasCatalog($this->store_type);
    }

    public function getActiveCatalogItemsCount(): int
    {
        $modelClass = $this->getCatalogModelClass();
        if ($modelClass === null) {
            return 0;
        }

        return $modelClass::where('store_id', $this->id)
            ->where('is_active', true)
            ->count();
    }

    // Settings helpers
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    public function getThemeColor(string $key, string $default = '#2563eb'): string
    {
        return data_get($this->theme, $key, $default);
    }

    public function getMiniAppUrl(): string
    {
        return url("/miniapp/{$this->slug}");
    }

    /**
     * @deprecated Use getActiveCatalogItemsCount() instead
     */
    public function getActiveProductsCount(): int
    {
        return $this->products()->where('is_active', true)->count();
    }
}
