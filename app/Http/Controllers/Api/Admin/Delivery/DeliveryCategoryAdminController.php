<?php

namespace App\Http\Controllers\Api\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Delivery\StoreDeliveryCategoryRequest;
use App\Http\Resources\Bot\Delivery\DeliveryCategoryResource;
use App\Models\Bot\Delivery\DeliveryCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryCategoryAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = DeliveryCategory::withCount('menuItems')
            ->ordered()
            ->get();

        return response()->json([
            'categories' => DeliveryCategoryResource::collection($categories),
        ]);
    }

    public function store(StoreDeliveryCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = session('current_business_id');
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = DeliveryCategory::create($data);

        return response()->json([
            'category' => new DeliveryCategoryResource($category),
        ], 201);
    }

    public function show(DeliveryCategory $category): JsonResponse
    {
        $category->loadCount('menuItems');

        return response()->json([
            'category' => new DeliveryCategoryResource($category),
        ]);
    }

    public function update(StoreDeliveryCategoryRequest $request, DeliveryCategory $category): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json([
            'category' => new DeliveryCategoryResource($category),
        ]);
    }

    public function destroy(DeliveryCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Kategoriya o\'chirildi.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:delivery_categories,id',
        ]);

        foreach ($request->input('ids') as $index => $id) {
            DeliveryCategory::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Tartib yangilandi.']);
    }
}
