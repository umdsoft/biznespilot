<?php

namespace App\Http\Controllers\Api\Bot\Service;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Service\ServiceCategoryResource;
use App\Http\Resources\Bot\Service\ServiceMasterResource;
use App\Http\Resources\Bot\Service\ServiceTypeResource;
use App\Models\Bot\Service\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    public function categories(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');

        $categories = ServiceCategory::forBusiness($businessId)
            ->active()
            ->ordered()
            ->with(['serviceTypes' => fn ($q) => $q->active()->ordered()])
            ->get();

        return response()->json([
            'categories' => ServiceCategoryResource::collection($categories),
        ]);
    }

    public function categoryDetail(Request $request, ServiceCategory $category): JsonResponse
    {
        $category->load([
            'serviceTypes' => fn ($q) => $q->active()->ordered(),
            'masters' => fn ($q) => $q->active()->available(),
        ]);

        return response()->json([
            'category' => new ServiceCategoryResource($category),
            'service_types' => ServiceTypeResource::collection($category->serviceTypes),
            'masters' => ServiceMasterResource::collection($category->masters),
        ]);
    }
}
