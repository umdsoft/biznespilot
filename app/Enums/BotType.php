<?php

namespace App\Enums;

enum BotType: string
{
    case ECOMMERCE = 'ecommerce';
    case DELIVERY = 'delivery';
    case QUEUE = 'queue';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::ECOMMERCE => 'Online do\'kon',
            self::DELIVERY => 'Yetkazib berish',
            self::QUEUE => 'Navbat / Bron',
            self::SERVICE => 'Servis xizmati',
        };
    }

    /**
     * Heroicons nomi (Vue da @heroicons/vue/24/outline dan import)
     */
    public function icon(): string
    {
        return match ($this) {
            self::ECOMMERCE => 'ShoppingBagIcon',
            self::DELIVERY => 'TruckIcon',
            self::QUEUE => 'CalendarDaysIcon',
            self::SERVICE => 'WrenchScrewdriverIcon',
        };
    }

    /**
     * Aksent rang (text, border, active state)
     */
    public function color(): string
    {
        return match ($this) {
            self::ECOMMERCE => '#2563EB',
            self::DELIVERY => '#F97316',
            self::QUEUE => '#8B5CF6',
            self::SERVICE => '#0EA5E9',
        };
    }

    /**
     * Background rang (badge, light bg)
     */
    public function bgColor(): string
    {
        return match ($this) {
            self::ECOMMERCE => '#DBEAFE',
            self::DELIVERY => '#FFF7ED',
            self::QUEUE => '#F5F3FF',
            self::SERVICE => '#F0F9FF',
        };
    }

    /**
     * Botlar jadvalidagi asosiy amal tugmasi nomi
     */
    public function primaryActionLabel(): string
    {
        return match ($this) {
            self::ECOMMERCE => 'Do\'kon',
            self::DELIVERY => 'Menyu',
            self::QUEUE => 'Bronlar',
            self::SERVICE => 'Arizalar',
        };
    }

    /**
     * Sidebar menyu — har bir bot turi uchun unikal navigation
     * label: ko'rinadigan nom
     * icon: Heroicons nomi
     * routeSuffix: /business/store/{suffix} ga qo'shiladi
     */
    public function sidebarMenu(): array
    {
        return match ($this) {
            self::ECOMMERCE => [
                ['label' => 'Dashboard', 'icon' => 'ChartBarSquareIcon', 'routeSuffix' => 'dashboard'],
                ['label' => 'Do\'kon', 'icon' => 'BuildingStorefrontIcon', 'routeSuffix' => 'catalog'],
                ['label' => 'Buyurtmalar', 'icon' => 'ClipboardDocumentListIcon', 'routeSuffix' => 'orders', 'badge' => 'pending_orders'],
                ['label' => 'Mijozlar', 'icon' => 'UsersIcon', 'routeSuffix' => 'customers'],
                ['label' => 'Katalog', 'icon' => 'CubeIcon', 'routeSuffix' => 'catalog'],
                ['label' => 'Promo kodlar', 'icon' => 'TagIcon', 'routeSuffix' => 'promo-codes'],
                ['label' => 'Sozlamalar', 'icon' => 'CogIcon', 'routeSuffix' => 'settings'],
            ],
            self::DELIVERY => [
                ['label' => 'Dashboard', 'icon' => 'ChartBarSquareIcon', 'routeSuffix' => 'dashboard'],
                ['label' => 'Menyu', 'icon' => 'ClipboardDocumentListIcon', 'routeSuffix' => 'catalog'],
                ['label' => 'Buyurtmalar', 'icon' => 'ShoppingCartIcon', 'routeSuffix' => 'orders', 'badge' => 'pending_orders'],
                ['label' => 'Kategoriyalar', 'icon' => 'FolderIcon', 'routeSuffix' => 'categories'],
                ['label' => 'Yetkazish zonalari', 'icon' => 'MapPinIcon', 'routeSuffix' => 'settings/delivery-zones'],
                ['label' => 'Sozlamalar', 'icon' => 'CogIcon', 'routeSuffix' => 'settings'],
            ],
            self::QUEUE => [
                ['label' => 'Dashboard', 'icon' => 'ChartBarSquareIcon', 'routeSuffix' => 'dashboard'],
                ['label' => 'Xizmatlar', 'icon' => 'WrenchIcon', 'routeSuffix' => 'catalog'],
                ['label' => 'Bronlar', 'icon' => 'CalendarDaysIcon', 'routeSuffix' => 'orders', 'badge' => 'pending_orders'],
                ['label' => 'Filiallar', 'icon' => 'BuildingOfficeIcon', 'routeSuffix' => 'categories'],
                ['label' => 'Mutaxassislar', 'icon' => 'UserGroupIcon', 'routeSuffix' => 'customers'],
                ['label' => 'Sozlamalar', 'icon' => 'CogIcon', 'routeSuffix' => 'settings'],
            ],
            self::SERVICE => [
                ['label' => 'Dashboard', 'icon' => 'ChartBarSquareIcon', 'routeSuffix' => 'dashboard'],
                ['label' => 'Kategoriyalar', 'icon' => 'FolderIcon', 'routeSuffix' => 'categories'],
                ['label' => 'Arizalar', 'icon' => 'DocumentTextIcon', 'routeSuffix' => 'orders', 'badge' => 'pending_orders'],
                ['label' => 'Ustalar', 'icon' => 'UserGroupIcon', 'routeSuffix' => 'customers'],
                ['label' => 'Xizmat turlari', 'icon' => 'ListBulletIcon', 'routeSuffix' => 'catalog'],
                ['label' => 'Sozlamalar', 'icon' => 'CogIcon', 'routeSuffix' => 'settings'],
            ],
        };
    }

    /**
     * Barcha turlarni select/dropdown uchun array sifatida qaytarish
     */
    public static function toArray(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'icon' => $type->icon(),
            'color' => $type->color(),
            'bg_color' => $type->bgColor(),
        ], self::cases());
    }
}
