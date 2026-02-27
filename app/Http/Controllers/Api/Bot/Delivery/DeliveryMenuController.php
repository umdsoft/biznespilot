<?php

namespace App\Http\Controllers\Api\Bot\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Delivery\DeliveryCategoryResource;
use App\Http\Resources\Bot\Delivery\DeliveryMenuDetailResource;
use App\Http\Resources\Bot\Delivery\DeliveryMenuResource;
use App\Models\Bot\Delivery\DeliveryMenuItem;
use App\Services\Bot\Delivery\DeliveryMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryMenuController extends Controller
{
    public function __construct(
        private DeliveryMenuService $menuService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $categories = $this->menuService->getMenuWithCategories($businessId);

        return response()->json([
            'categories' => DeliveryCategoryResource::collection($categories),
        ]);
    }

    public function show(DeliveryMenuItem $item): JsonResponse
    {
        $item->load(['category', 'variants', 'addons']);

        return response()->json([
            'item' => new DeliveryMenuDetailResource($item),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $businessId = $request->header('X-Business-Id');
        $items = $this->menuService->searchMenu($businessId, $request->input('q'));

        return response()->json([
            'items' => DeliveryMenuResource::collection($items),
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $items = $this->menuService->getPopularItems($businessId);

        return response()->json([
            'items' => DeliveryMenuResource::collection($items),
        ]);
    }
}
