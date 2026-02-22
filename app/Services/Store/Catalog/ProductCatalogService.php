<?php

namespace App\Services\Store\Catalog;

use App\Contracts\Store\CatalogableInterface;
use App\Contracts\Store\CatalogServiceInterface;
use App\Models\Store\StoreProduct;
use App\Models\Store\TelegramStore;
use App\Services\Store\StoreProductService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductCatalogService implements CatalogServiceInterface
{
    public function __construct(
        protected StoreProductService $productService
    ) {}

    public function list(TelegramStore $store, array $filters = []): LengthAwarePaginator
    {
        $query = StoreProduct::where('store_id', $store->id)
            ->with(['category', 'primaryImage']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'featured' => $query->where('is_featured', true),
                'out_of_stock' => $query->where('track_stock', true)->where('stock_quantity', '<=', 0),
                default => null,
            };
        }

        $sortField = $filters['sort'] ?? 'sort_order';
        $sortDirection = $filters['direction'] ?? 'asc';
        $allowedSorts = ['name', 'price', 'created_at', 'sort_order', 'stock_quantity'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'sort_order';
        }
        $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function show(TelegramStore $store, string $id): ?CatalogableInterface
    {
        return StoreProduct::where('store_id', $store->id)
            ->with(['category', 'images', 'variants', 'approvedReviews.customer'])
            ->find($id);
    }

    public function create(TelegramStore $store, array $data): CatalogableInterface
    {
        return $this->productService->createProduct($store, $data);
    }

    public function update(CatalogableInterface $item, array $data): CatalogableInterface
    {
        return $this->productService->updateProduct($item, $data);
    }

    public function delete(CatalogableInterface $item): void
    {
        $this->productService->deleteProduct($item);
    }

    public function search(TelegramStore $store, string $query, array $filters = []): Collection
    {
        return StoreProduct::where('store_id', $store->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->active()
            ->inStock()
            ->with(['primaryImage', 'category'])
            ->limit(50)
            ->get();
    }

    public function getFilterOptions(TelegramStore $store): array
    {
        return [
            'categories' => $store->categories()->active()->orderBy('sort_order')->get(['id', 'name']),
            'price_range' => [
                'min' => (float) StoreProduct::where('store_id', $store->id)->active()->min('price') ?? 0,
                'max' => (float) StoreProduct::where('store_id', $store->id)->active()->max('price') ?? 0,
            ],
        ];
    }
}
