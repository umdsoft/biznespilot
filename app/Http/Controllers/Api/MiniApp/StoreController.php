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
        $store->load([
            // Categories with pre-computed active product count — kills N+1 in
            // StoreInfoResource (was doing one COUNT per category per request).
            'categories' => function ($q) {
                $q->active()->ordered()
                    ->withCount(['products as active_products_count' => function ($qq) {
                        $qq->where('is_active', true);
                    }]);
            },
            'products' => function ($q) {
                $q->active()->with('primaryImage');
            },
        ]);

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
            // Pre-compute child product counts at query time instead of firing
            // one COUNT(*) per category in the Resource (was N+1 before).
            ->withCount(['products as active_products_count' => function ($q) {
                $q->where('is_active', true);
            }])
            ->with([
                'children' => function ($q) {
                    $q->active()->ordered()
                        ->withCount(['products as active_products_count' => function ($qq) {
                            $qq->where('is_active', true);
                        }]);
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
            ->with(['primaryImage', 'category'])
            // Aggregate reviews at query time — avoids hydrating every review row
            // just to call ->avg()/->count() in the resource.
            ->withCount('approvedReviews as reviews_count')
            ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
            ->orderBy('sort_order')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductListResource::collection($products),
        ]);
    }
}
