<?php

namespace App\Services\Store\Catalog;

use App\Contracts\Store\CatalogableInterface;
use App\Contracts\Store\CatalogServiceInterface;
use App\Models\Store\StoreMenuItem;
use App\Models\Store\TelegramStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class MenuItemCatalogService implements CatalogServiceInterface
{
    public function list(TelegramStore $store, array $filters = []): LengthAwarePaginator
    {
        $query = StoreMenuItem::where('store_id', $store->id)
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

        if (!empty($filters['dietary_tags']) && is_array($filters['dietary_tags'])) {
            foreach ($filters['dietary_tags'] as $tag) {
                $query->whereJsonContains('dietary_tags', $tag);
            }
        }

        $sortField = $filters['sort'] ?? 'sort_order';
        $sortDirection = $filters['direction'] ?? 'asc';
        $allowedSorts = ['name', 'price', 'created_at', 'sort_order'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'sort_order';
        }
        $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function show(TelegramStore $store, string $id): ?CatalogableInterface
    {
        return StoreMenuItem::where('store_id', $store->id)
            ->with(['category', 'modifiers'])
            ->find($id);
    }

    public function create(TelegramStore $store, array $data): CatalogableInterface
    {
        $menuItem = StoreMenuItem::create([
            'store_id' => $store->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(4),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'image_url' => $data['image_url'] ?? null,
            'preparation_time_minutes' => $data['preparation_time_minutes'] ?? null,
            'calories' => $data['calories'] ?? null,
            'portion_size' => $data['portion_size'] ?? null,
            'allergens' => $data['allergens'] ?? null,
            'dietary_tags' => $data['dietary_tags'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_featured' => $data['is_featured'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
            'metadata' => $data['metadata'] ?? null,
        ]);

        // Create modifiers if provided
        if (!empty($data['modifiers'])) {
            foreach ($data['modifiers'] as $index => $modifierData) {
                $menuItem->modifiers()->create([
                    'name' => $modifierData['name'],
                    'price' => $modifierData['price'] ?? 0,
                    'is_required' => $modifierData['is_required'] ?? false,
                    'sort_order' => $modifierData['sort_order'] ?? $index,
                ]);
            }
        }

        return $menuItem->load(['category', 'modifiers']);
    }

    public function update(CatalogableInterface $item, array $data): CatalogableInterface
    {
        $updateData = collect($data)->only([
            'category_id', 'name', 'description', 'price', 'image_url',
            'preparation_time_minutes', 'calories', 'portion_size',
            'allergens', 'dietary_tags',
            'is_active', 'is_featured', 'sort_order', 'metadata',
        ])->toArray();

        if (isset($data['name']) && $data['name'] !== $item->name) {
            $updateData['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        }

        $item->update($updateData);

        return $item->fresh(['category', 'modifiers']);
    }

    public function delete(CatalogableInterface $item): void
    {
        $item->delete();
    }

    public function search(TelegramStore $store, string $query, array $filters = []): Collection
    {
        return StoreMenuItem::where('store_id', $store->id)
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
        $menuItems = StoreMenuItem::where('store_id', $store->id)->active();

        // Collect unique dietary tags from all active menu items
        $allTags = StoreMenuItem::where('store_id', $store->id)
            ->active()
            ->whereNotNull('dietary_tags')
            ->pluck('dietary_tags')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return [
            'categories' => $store->categories()->active()->orderBy('sort_order')->get(['id', 'name']),
            'price_range' => [
                'min' => (float) StoreMenuItem::where('store_id', $store->id)->active()->min('price') ?? 0,
                'max' => (float) StoreMenuItem::where('store_id', $store->id)->active()->max('price') ?? 0,
            ],
            'dietary_tags' => $allTags,
        ];
    }
}
