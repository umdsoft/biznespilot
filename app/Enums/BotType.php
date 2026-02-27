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
            self::ECOMMERCE => 'E-commerce',
            self::DELIVERY => 'Yetkazib berish',
            self::QUEUE => 'Navbat / Bron',
            self::SERVICE => 'Servis / Ta\'mirlash',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ECOMMERCE => '#2563EB',
            self::DELIVERY => '#F97316',
            self::QUEUE => '#8B5CF6',
            self::SERVICE => '#0EA5E9',
        };
    }
}
