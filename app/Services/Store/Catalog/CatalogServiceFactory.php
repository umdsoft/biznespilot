<?php

namespace App\Services\Store\Catalog;

use App\Contracts\Store\CatalogServiceInterface;
use App\Services\Store\BotTypeRegistry;
use InvalidArgumentException;

class CatalogServiceFactory
{
    public function __construct(
        protected BotTypeRegistry $registry
    ) {}

    public function make(string $botType): CatalogServiceInterface
    {
        $serviceClass = $this->registry->getServiceClass($botType);

        if (!class_exists($serviceClass)) {
            throw new InvalidArgumentException("Catalog service topilmadi: {$serviceClass}");
        }

        $service = app($serviceClass);

        if (!$service instanceof CatalogServiceInterface) {
            throw new InvalidArgumentException("{$serviceClass} CatalogServiceInterface ni implement qilishi shart");
        }

        return $service;
    }
}
