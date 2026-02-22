<?php

namespace App\Services\Store\Catalog;

use App\Contracts\Store\CatalogableInterface;
use App\Contracts\Store\CatalogServiceInterface;
use App\Models\Store\StoreCourse;
use App\Models\Store\TelegramStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CourseCatalogService implements CatalogServiceInterface
{
    public function list(TelegramStore $store, array $filters = []): LengthAwarePaginator
    {
        $query = StoreCourse::where('store_id', $store->id)
            ->with(['category']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('instructor', 'like', "%{$search}%");
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

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        $sortField = $filters['sort'] ?? 'sort_order';
        $sortDirection = $filters['direction'] ?? 'asc';
        $allowedSorts = ['name', 'price', 'start_date', 'created_at', 'sort_order'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'sort_order';
        }
        $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function show(TelegramStore $store, string $id): ?CatalogableInterface
    {
        return StoreCourse::where('store_id', $store->id)
            ->with(['category', 'lessons'])
            ->find($id);
    }

    public function create(TelegramStore $store, array $data): CatalogableInterface
    {
        $course = StoreCourse::create([
            'store_id' => $store->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(4),
            'description' => $data['description'] ?? null,
            'what_you_learn' => $data['what_you_learn'] ?? null,
            'requirements' => $data['requirements'] ?? null,
            'price' => $data['price'],
            'compare_price' => $data['compare_price'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'duration_hours' => $data['duration_hours'] ?? null,
            'level' => $data['level'] ?? null,
            'instructor' => $data['instructor'] ?? null,
            'instructor_photo' => $data['instructor_photo'] ?? null,
            'max_students' => $data['max_students'] ?? null,
            'enrolled_count' => 0,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'format' => $data['format'] ?? null,
            'certificate_included' => $data['certificate_included'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'is_featured' => $data['is_featured'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
            'metadata' => $data['metadata'] ?? null,
        ]);

        return $course->load(['category']);
    }

    public function update(CatalogableInterface $item, array $data): CatalogableInterface
    {
        $updateData = collect($data)->only([
            'category_id', 'name', 'description', 'what_you_learn', 'requirements',
            'price', 'compare_price', 'image_url', 'duration_hours', 'level',
            'instructor', 'instructor_photo', 'max_students',
            'start_date', 'end_date', 'format', 'certificate_included',
            'is_active', 'is_featured', 'sort_order', 'metadata',
        ])->toArray();

        if (isset($data['name']) && $data['name'] !== $item->name) {
            $updateData['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        }

        $item->update($updateData);

        return $item->fresh(['category', 'lessons']);
    }

    public function delete(CatalogableInterface $item): void
    {
        $item->delete();
    }

    public function search(TelegramStore $store, string $query, array $filters = []): Collection
    {
        return StoreCourse::where('store_id', $store->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('instructor', 'like', "%{$query}%");
            })
            ->active()
            ->with(['category'])
            ->limit(50)
            ->get();
    }

    public function getFilterOptions(TelegramStore $store): array
    {
        $baseQuery = StoreCourse::where('store_id', $store->id)->active();

        return [
            'categories' => $store->categories()->active()->orderBy('sort_order')->get(['id', 'name']),
            'price_range' => [
                'min' => (float) (clone $baseQuery)->min('price') ?? 0,
                'max' => (float) (clone $baseQuery)->max('price') ?? 0,
            ],
            'levels' => (clone $baseQuery)
                ->whereNotNull('level')
                ->distinct()
                ->pluck('level')
                ->values()
                ->toArray(),
            'formats' => (clone $baseQuery)
                ->whereNotNull('format')
                ->distinct()
                ->pluck('format')
                ->values()
                ->toArray(),
        ];
    }
}
