<?php

namespace App\Http\Controllers\Api\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Delivery\StoreDeliveryMenuItemRequest;
use App\Http\Resources\Bot\Delivery\DeliveryMenuDetailResource;
use App\Http\Resources\Bot\Delivery\DeliveryMenuResource;
use App\Models\Bot\Delivery\DeliveryMenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryMenuItemAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryMenuItem::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortField, $sortDir);

        $items = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'items' => DeliveryMenuResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(StoreDeliveryMenuItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = session('current_business_id');
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $variants = $data['variants'] ?? [];
        $addons = $data['addons'] ?? [];
        unset($data['variants'], $data['addons']);

        $item = DeliveryMenuItem::create($data);

        foreach ($variants as $variant) {
            $item->variants()->create($variant);
        }

        foreach ($addons as $addon) {
            $item->addons()->create($addon);
        }

        $item->load(['category', 'variants', 'addons']);

        return response()->json([
            'item' => new DeliveryMenuDetailResource($item),
        ], 201);
    }

    public function show(DeliveryMenuItem $menuItem): JsonResponse
    {
        $menuItem->load(['category', 'variants', 'addons']);

        return response()->json([
            'item' => new DeliveryMenuDetailResource($menuItem),
        ]);
    }

    public function update(StoreDeliveryMenuItemRequest $request, DeliveryMenuItem $menuItem): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $variants = $data['variants'] ?? null;
        $addons = $data['addons'] ?? null;
        unset($data['variants'], $data['addons']);

        $menuItem->update($data);

        if ($variants !== null) {
            $menuItem->variants()->delete();
            foreach ($variants as $variant) {
                $menuItem->variants()->create($variant);
            }
        }

        if ($addons !== null) {
            $menuItem->addons()->delete();
            foreach ($addons as $addon) {
                $menuItem->addons()->create($addon);
            }
        }

        $menuItem->load(['category', 'variants', 'addons']);

        return response()->json([
            'item' => new DeliveryMenuDetailResource($menuItem),
        ]);
    }

    public function destroy(DeliveryMenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return response()->json(['message' => 'Mahsulot o\'chirildi.']);
    }

    public function toggle(DeliveryMenuItem $menuItem): JsonResponse
    {
        $menuItem->is_available = ! $menuItem->is_available;
        $menuItem->save();

        return response()->json([
            'is_available' => $menuItem->is_available,
        ]);
    }
}
