<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\CategoryResource;
use App\Http\Resources\Store\ProductListResource;
use App\Http\Resources\Store\StoreInfoResource;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;

/**
 * Mini App Store Info Controller.
 *
 * Provides public store info, categories tree, and featured products.
 * These endpoints do NOT require authentication.
 */
class StoreController extends Controller
{
    /**
     * GET /info — Store information (name, logo, banner, theme, contacts).
     */
    public function info(TelegramStore $store): JsonResponse
    {
        $store->load(['categories' => function ($q) {
            $q->active();
        }, 'products' => function ($q) {
            $q->active();
        }]);

        return response()->json([
            'success' => true,
            'data' => new StoreInfoResource($store),
        ]);
    }

    /**
     * GET /categories — Categories tree with product counts.
     */
    public function categories(TelegramStore $store): JsonResponse
    {
        $categories = $store->categories()
            ->active()
            ->root()
            ->ordered()
            ->with([
                'children' => function ($q) {
                    $q->active()->ordered();
                },
                'children.products' => function ($q) {
                    $q->active();
                },
                'products' => function ($q) {
                    $q->active();
                },
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * GET /featured — Featured products (is_featured = true).
     */
    public function featured(TelegramStore $store): JsonResponse
    {
        $products = $store->products()
            ->active()
            ->featured()
            ->with(['primaryImage', 'category', 'approvedReviews'])
            ->orderBy('sort_order')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductListResource::collection($products),
        ]);
    }
}
