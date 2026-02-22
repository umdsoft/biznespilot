<?php

namespace App\Contracts\Store;

use App\Models\Store\TelegramStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CatalogServiceInterface
{
    /**
     * Elementlar ro'yxati (pagination bilan)
     */
    public function list(TelegramStore $store, array $filters = []): LengthAwarePaginator;

    /**
     * Bitta element ko'rish
     */
    public function show(TelegramStore $store, string $id): ?CatalogableInterface;

    /**
     * Yangi element yaratish
     */
    public function create(TelegramStore $store, array $data): CatalogableInterface;

    /**
     * Elementni yangilash
     */
    public function update(CatalogableInterface $item, array $data): CatalogableInterface;

    /**
     * Elementni o'chirish
     */
    public function delete(CatalogableInterface $item): void;

    /**
     * Qidiruv
     */
    public function search(TelegramStore $store, string $query, array $filters = []): Collection;

    /**
     * Filtr opsiyalari (kategoriya, narx diapazoni, etc.)
     */
    public function getFilterOptions(TelegramStore $store): array;
}
