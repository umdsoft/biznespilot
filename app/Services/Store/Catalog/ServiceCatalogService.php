<?php

namespace App\Services\Store\Catalog;

use App\Contracts\Store\CatalogableInterface;
use App\Contracts\Store\CatalogServiceInterface;
use App\Models\Store\StoreService;
use App\Models\Store\TelegramStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ServiceCatalogService implements CatalogServiceInterface
{
    public function list(TelegramStore $store, array $filters = []): LengthAwarePaginator
    {
        $query = StoreService::where('store_id', $store->id)
            ->with(['category']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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
                default => null,
            };
        }

        if (!empty($filters['requires_staff'])) {
            $query->where('requires_staff', true);
        }

        $sortField = $filters['sort'] ?? 'sort_order';
        $sortDirection = $filters['direction'] ?? 'asc';
        $allowedSorts = ['name', 'price', 'duration_minutes', 'created_at', 'sort_order'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'sort_order';
        }
        $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function show(TelegramStore $store, string $id): ?CatalogableInterface
    {
        return StoreService::where('store_id', $store->id)
            ->with(['category'])
            ->find($id);
    }

    public function create(TelegramStore $store, array $data): CatalogableInterface
    {
        return StoreService::create([
            'store_id' => $store->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(4),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'compare_price' => $data['compare_price'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'max_capacity' => $data['max_capacity'] ?? null,
            'requires_staff' => $data['requires_staff'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'is_featured' => $data['is_featured'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function update(CatalogableInterface $item, array $data): CatalogableInterface
    {
        $updateData = collect($data)->only([
            'category_id', 'name', 'description', 'price', 'compare_price',
            'image_url', 'duration_minutes', 'max_capacity', 'requires_staff',
            'is_active', 'is_featured', 'sort_order', 'metadata',
        ])->toArray();

        if (isset($data['name']) && $data['name'] !== $item->name) {
            $updateData['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        }

        $item->update($updateData);

        return $item->fresh(['category']);
    }

    public function delete(CatalogableInterface $item): void
    {
        $item->delete();
    }

    public function search(TelegramStore $store, string $query, array $filters = []): Collection
    {
        return StoreService::where('store_id', $store->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->active()
            ->with(['category'])
            ->limit(50)
            ->get();
    }

    public function getFilterOptions(TelegramStore $store): array
    {
        return [
            'categories' => $store->categories()->active()->orderBy('sort_order')->get(['id', 'name']),
            'price_range' => [
                'min' => (float) StoreService::where('store_id', $store->id)->active()->min('price') ?? 0,
                'max' => (float) StoreService::where('store_id', $store->id)->active()->max('price') ?? 0,
            ],
        ];
    }
}
