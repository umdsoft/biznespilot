<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\ProductListResource;
use App\Http\Resources\Store\ProductResource;
use App\Models\Store\StoreProduct;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mini App Product Controller.
 *
 * Provides product listing with filters, search, and detail view.
 * All endpoints are public (no authentication required).
 */
class ProductController extends Controller
{
    /**
     * GET /products — Product listing with category filter and pagination.
     */
    public function index(Request $request, TelegramStore $store): JsonResponse
    {
        $query = $store->products()
            ->active()
            ->with(['primaryImage', 'category'])
            ->withCount('approvedReviews as reviews_count')
            ->withAvg('approvedReviews as reviews_avg_rating', 'rating');

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Category slug filter
        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category_slug'));
            });
        }

        // In stock filter
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // Featured filter
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');

        $allowedSorts = ['price', 'name', 'created_at', 'sort_order'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('sort_order', 'asc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 20), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductListResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * GET /products/search?q= — Search products by name.
     */
    public function search(Request $request, TelegramStore $store): JsonResponse
    {
        $query = $request->input('q', '');

        if (mb_strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'query' => $query,
                    'total' => 0,
                ],
            ]);
        }

        $products = $store->products()
            ->active()
            ->with(['primaryImage', 'category', 'approvedReviews'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->orderBy('sort_order')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductListResource::collection($products),
            'meta' => [
                'query' => $query,
                'total' => $products->count(),
            ],
        ]);
    }

    /**
     * GET /products/{slug} — Product detail with images, variants, and reviews.
     */
    public function show(TelegramStore $store, StoreProduct $product): JsonResponse
    {
        // Ensure product belongs to this store and is active
        if ($product->store_id !== $store->id || ! $product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Mahsulot topilmadi',
            ], 404);
        }

        $product->load([
            'images',
            'activeVariants',
            'category',
            'approvedReviews.customer',
        ]);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }
}
