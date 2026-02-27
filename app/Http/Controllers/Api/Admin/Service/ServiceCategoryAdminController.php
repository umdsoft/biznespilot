<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Service\StoreServiceCategoryRequest;
use App\Http\Resources\Bot\Service\ServiceCategoryResource;
use App\Models\Bot\Service\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = ServiceCategory::withCount(['serviceTypes', 'masters'])
            ->ordered()
            ->get();

        return response()->json([
            'categories' => ServiceCategoryResource::collection($categories),
        ]);
    }

    public function store(StoreServiceCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = session('current_business_id');
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = ServiceCategory::create($data);

        return response()->json([
            'category' => new ServiceCategoryResource($category),
        ], 201);
    }

    public function show(ServiceCategory $category): JsonResponse
    {
        $category->loadCount(['serviceTypes', 'masters']);

        return response()->json([
            'category' => new ServiceCategoryResource($category),
        ]);
    }

    public function update(StoreServiceCategoryRequest $request, ServiceCategory $category): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json([
            'category' => new ServiceCategoryResource($category),
        ]);
    }

    public function destroy(ServiceCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Kategoriya o\'chirildi.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:service_categories,id',
        ]);

        foreach ($request->input('ids') as $index => $id) {
            ServiceCategory::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Tartib yangilandi.']);
    }
}
