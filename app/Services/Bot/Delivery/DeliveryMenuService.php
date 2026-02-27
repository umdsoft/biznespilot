<?php

namespace App\Services\Bot\Delivery;

use App\Models\Bot\Delivery\DeliveryCategory;
use App\Models\Bot\Delivery\DeliveryMenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DeliveryMenuService
{
    public function getMenuWithCategories(string $businessId): Collection
    {
        return DeliveryCategory::forBusiness($businessId)
            ->active()
            ->roots()
            ->ordered()
            ->with([
                'menuItems' => fn ($q) => $q->available()->ordered(),
                'children' => fn ($q) => $q->active()->ordered(),
                'children.menuItems' => fn ($q) => $q->available()->ordered(),
            ])
            ->get();
    }

    public function getMenuItems(string $businessId, array $filters = []): LengthAwarePaginator
    {
        $query = DeliveryMenuItem::forBusiness($businessId);

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        if (! empty($filters['is_popular'])) {
            $query->popular();
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $sortField = $filters['sort'] ?? 'sort_order';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortField, $sortDir);

        return $query->with(['category', 'variants', 'addons'])
            ->paginate($filters['per_page'] ?? 20);
    }

    public function searchMenu(string $businessId, string $query): Collection
    {
        return DeliveryMenuItem::forBusiness($businessId)
            ->available()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('category')
            ->limit(30)
            ->get();
    }

    public function getPopularItems(string $businessId, int $limit = 10): Collection
    {
        return DeliveryMenuItem::forBusiness($businessId)
            ->available()
            ->popular()
            ->ordered()
            ->with('category')
            ->limit($limit)
            ->get();
    }

    public function getItemDetail(string $itemId): ?DeliveryMenuItem
    {
        return DeliveryMenuItem::with(['category', 'variants' => fn ($q) => $q->ordered(), 'addons'])
            ->find($itemId);
    }
}
