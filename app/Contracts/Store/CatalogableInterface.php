<?php

namespace App\Contracts\Store;

interface CatalogableInterface
{
    /**
     * Katalog element nomi (barcha tipdagi elementlar uchun umumiy)
     */
    public function getCatalogName(): string;

    /**
     * Narx (asosiy narx)
     */
    public function getCatalogPrice(): float;

    /**
     * Asosiy rasm URL
     */
    public function getCatalogImage(): ?string;

    /**
     * Element mavjudmi (sotuvda/faol)
     */
    public function isAvailable(): bool;

    /**
     * Tavsif
     */
    public function getCatalogDescription(): ?string;

    /**
     * Tip-spesifik atributlar (duration, stock, area, etc.)
     */
    public function getCatalogAttributes(): array;

    /**
     * Qidiruv uchun massiv
     */
    public function toSearchableArray(): array;

    /**
     * Katalog turi identifikatori (product, service, menu_item, etc.)
     */
    public function getCatalogType(): string;
}
