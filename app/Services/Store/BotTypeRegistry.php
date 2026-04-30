<?php

namespace App\Services\Store;

use InvalidArgumentException;

class BotTypeRegistry
{
    /**
     * Bot types that have full MiniApp frontend implementation.
     * Only these are shown in the setup wizard.
     *
     * MUHIM: 'leadcapture' MiniApp emas — u faqat Telegram chat funnel'i orqali
     * ishlaydi (katalog, savat, mahsulotlar yo'q). Lekin wizard'da ko'rinishi
     * uchun ro'yxatda turadi. WebApp menu button qo'yilmaydi (StoreSetupService
     * leadcapture turi uchun setChatMenuButton'ni o'tkazib yuboradi).
     */
    public const IMPLEMENTED_TYPES = ['ecommerce', 'service', 'delivery', 'queue', 'course', 'leadcapture'];

    /**
     * Bot types without a catalog (no products/services table to query).
     * Frontend dashboard, MiniApp catalog routes, and admin catalog UI must
     * check this and skip catalog-related operations.
     */
    public const NO_CATALOG_TYPES = ['leadcapture'];

    protected array $config;

    public function __construct()
    {
        $this->config = config('store_bot_types', []);
    }

    public function getConfig(string $botType): array
    {
        if (!isset($this->config[$botType])) {
            throw new InvalidArgumentException("Noma'lum bot turi: {$botType}");
        }

        return $this->config[$botType];
    }

    /**
     * Get the catalog Eloquent model class for this bot type.
     * Returns NULL for catalog-less types (e.g., 'leadcapture').
     * Callers MUST check for null OR use hasCatalog() before invoking.
     */
    public function getCatalogModel(string $botType): ?string
    {
        return $this->getConfig($botType)['catalog_model'] ?? null;
    }

    /**
     * Get the catalog service class for this bot type.
     * Returns NULL for catalog-less types.
     */
    public function getServiceClass(string $botType): ?string
    {
        return $this->getConfig($botType)['service_class'] ?? null;
    }

    /**
     * Whether this bot type has a catalog (products/services/etc.) table.
     * Used by MiniApp/admin/dashboard layers to decide if catalog UI is shown.
     */
    public function hasCatalog(string $botType): bool
    {
        if (in_array($botType, self::NO_CATALOG_TYPES, true)) {
            return false;
        }

        // Explicit config flag wins; otherwise fall back to catalog_model presence
        $config = $this->getConfig($botType);
        if (array_key_exists('has_catalog', $config)) {
            return (bool) $config['has_catalog'];
        }

        return ! empty($config['catalog_model']);
    }

    public function hasFeature(string $botType, string $feature): bool
    {
        return in_array($feature, $this->getConfig($botType)['features'] ?? []);
    }

    public function getAllTypes(): array
    {
        return array_keys($this->config);
    }

    public function getTypeLabel(string $botType, string $locale = 'uz'): string
    {
        $config = $this->getConfig($botType);

        return match ($locale) {
            'ru' => $config['label_ru'] ?? $config['label'],
            'uz' => $config['label_uz'] ?? $config['label'],
            default => $config['label'],
        };
    }

    public function getCatalogLabel(string $botType): string
    {
        return $this->getConfig($botType)['catalog_label'] ?? 'Katalog';
    }

    public function getCatalogLabelSingular(string $botType): string
    {
        return $this->getConfig($botType)['catalog_label_singular'] ?? 'Element';
    }

    public function getFeatures(string $botType): array
    {
        return $this->getConfig($botType)['features'] ?? [];
    }

    public function getIcon(string $botType): string
    {
        return $this->getConfig($botType)['icon'] ?? 'RectangleStackIcon';
    }

    public function getAllTypesForSelect(): array
    {
        $implemented = array_intersect_key($this->config, array_flip(static::IMPLEMENTED_TYPES));

        return static::buildTypesForSelect($implemented);
    }

    /**
     * Static version of getAllTypesForSelect — reads config directly, no DI needed.
     *
     * Usage: BotTypeRegistry::allTypesForSelect()
     */
    public static function allTypesForSelect(): array
    {
        $config = config('store_bot_types', []);
        $implemented = array_intersect_key($config, array_flip(static::IMPLEMENTED_TYPES));

        return static::buildTypesForSelect($implemented);
    }

    /**
     * Shared logic for building the types-for-select array.
     */
    protected static function buildTypesForSelect(array $config): array
    {
        $emojiMap = [
            'ShoppingCartIcon' => "\xF0\x9F\x9B\x92",      // cart
            'WrenchScrewdriverIcon' => "\xF0\x9F\x94\xA7",  // wrench
            'TruckIcon' => "\xF0\x9F\x9A\x9A",              // truck
            'AcademicCapIcon' => "\xF0\x9F\x8E\x93",        // graduation
            'HeartIcon' => "\xF0\x9F\x92\xAA",              // muscle
            'HomeModernIcon' => "\xF0\x9F\x8F\xA0",         // house
            'CalendarDaysIcon' => "\xF0\x9F\x93\x85",       // calendar
            'GlobeAltIcon' => "\xF0\x9F\x8C\x8D",           // globe
            'BoltIcon' => "\xE2\x9A\xA1",                   // bolt
            'CreditCardIcon' => "\xF0\x9F\x92\xB3",         // credit card
            'PuzzlePieceIcon' => "\xF0\x9F\xA7\xA9",        // puzzle
            'UserGroupIcon' => "\xF0\x9F\x91\xA5",          // people (lead capture)
        ];

        $result = [];

        foreach ($config as $key => $item) {
            $iconName = $item['icon'] ?? 'RectangleStackIcon';
            $result[] = [
                'key' => $key,
                'label' => $item['label'],
                'label_uz' => $item['label_uz'] ?? $item['label'],
                'label_ru' => $item['label_ru'] ?? $item['label'],
                'description' => $item['description'] ?? '',
                'icon' => $emojiMap[$iconName] ?? "\xF0\x9F\x93\xA6", // default: package
                'features' => $item['features'] ?? [],
            ];
        }

        return $result;
    }

    public function isValidType(string $botType): bool
    {
        return isset($this->config[$botType]);
    }
}
