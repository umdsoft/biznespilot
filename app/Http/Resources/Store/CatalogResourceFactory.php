<?php

namespace App\Http\Resources\Store;

class CatalogResourceFactory
{
    protected static array $resourceMap = [
        'ecommerce' => ProductResource::class,
        'service' => ServiceResource::class,
        'delivery' => MenuItemResource::class,
        'course' => CourseResource::class,
    ];

    protected static array $listResourceMap = [
        'ecommerce' => ProductListResource::class,
        'service' => ServiceListResource::class,
        'delivery' => MenuItemListResource::class,
        'course' => CourseListResource::class,
    ];

    public static function make(string $botType): string
    {
        return static::$resourceMap[$botType] ?? ProductResource::class;
    }

    public static function makeList(string $botType): string
    {
        return static::$listResourceMap[$botType] ?? ProductListResource::class;
    }

    public static function collection(string $botType, $items)
    {
        $resourceClass = static::makeList($botType);
        return $resourceClass::collection($items);
    }
}
