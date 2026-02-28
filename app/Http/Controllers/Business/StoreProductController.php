<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreProductImage;
use App\Models\Store\TelegramStore;
use App\Services\Store\StoreProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreProductController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    public function __construct(
        protected StoreProductService $productService
    ) {}

    /**
     * List products with pagination and filters
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup('Avval do\'kon yarating.');
        }

        $query = StoreProduct::where('store_id', $store->id)
            ->with(['category', 'primaryImage', 'images']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Status filter
        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'out_of_stock' => $query->where('track_stock', true)->where('stock_quantity', '<=', 0),
                'featured' => $query->where('is_featured', true),
                default => null,
            };
        }

        // Sorting
        $sortField = $request->input('sort', 'sort_order');
        $sortDirection = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'price', 'stock_quantity', 'sort_order', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('sort_order', 'asc');
        }

        $products = $query->paginate(20)->through(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'compare_price' => $product->compare_price,
            'sku' => $product->sku,
            'stock_quantity' => $product->stock_quantity,
            'track_stock' => $product->track_stock,
            'is_active' => $product->is_active,
            'is_featured' => $product->is_featured,
            'sort_order' => $product->sort_order,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'primary_image' => $product->primaryImage?->image_url,
            'images_count' => $product->images->count(),
            'has_discount' => $product->hasDiscount(),
            'discount_percent' => $product->getDiscountPercent(),
            'is_in_stock' => $product->isInStock(),
            'created_at' => $product->created_at?->format('d.m.Y'),
        ]);

        $categories = StoreCategory::where('store_id', $store->id)
            ->active()
            ->ordered()
            ->get(['id', 'name']);

        return Inertia::render('Business/Store/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status', 'sort', 'direction']),
            'stats' => [
                'total' => StoreProduct::where('store_id', $store->id)->count(),
                'active' => StoreProduct::where('store_id', $store->id)->where('is_active', true)->count(),
                'out_of_stock' => StoreProduct::where('store_id', $store->id)
                    ->where('track_stock', true)
                    ->where('stock_quantity', '<=', 0)
                    ->count(),
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Show product creation form
     */
    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup();
        }

        $categories = StoreCategory::where('store_id', $store->id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'parent_id']);

        return Inertia::render('Business/Store/Products/Form', [
            'product' => null,
            'categories' => $categories,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Store a new product
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_with:variants|string|max:255',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.attributes' => 'nullable',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Validate category belongs to this store
        if (! empty($validated['category_id'])) {
            $categoryBelongsToStore = StoreCategory::where('id', $validated['category_id'])
                ->where('store_id', $store->id)
                ->exists();

            if (! $categoryBelongsToStore) {
                return back()->withErrors(['category_id' => 'Noto\'g\'ri kategoriya.']);
            }
        }

        $product = $this->productService->createProduct($store, $validated);

        // Upload images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $this->productService->uploadImage($product, $file, $index === 0);
            }
        }

        return $this->storeRedirect('products.index')
            ->with('success', 'Mahsulot muvaffaqiyatli yaratildi.');
    }

    /**
     * Show product details
     */
    public function show(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup();
        }

        $product = StoreProduct::where('store_id', $store->id)
            ->with(['category', 'images', 'variants', 'approvedReviews'])
            ->findOrFail($id);

        $orderItemsCount = $product->orderItems()->count();
        $averageRating = $product->getAverageRating();

        return Inertia::render('Business/Store/Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'compare_price' => $product->compare_price,
                'sku' => $product->sku,
                'stock_quantity' => $product->stock_quantity,
                'track_stock' => $product->track_stock,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'sort_order' => $product->sort_order,
                'metadata' => $product->metadata,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_url' => $img->image_url,
                    'sort_order' => $img->sort_order,
                    'is_primary' => $img->is_primary,
                ]),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'stock_quantity' => $v->stock_quantity,
                    'is_active' => $v->is_active,
                    'attributes' => $v->attributes,
                ]),
                'reviews_count' => $product->approvedReviews->count(),
                'average_rating' => $averageRating,
                'orders_count' => $orderItemsCount,
                'has_discount' => $product->hasDiscount(),
                'discount_percent' => $product->getDiscountPercent(),
                'created_at' => $product->created_at?->format('d.m.Y H:i'),
            ],
            'categories' => StoreCategory::where('store_id', $store->id)
                ->active()
                ->ordered()
                ->get(['id', 'name', 'parent_id']),
            'readOnly' => true,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Show product edit form
     */
    public function edit(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup();
        }

        $product = StoreProduct::where('store_id', $store->id)
            ->with(['category', 'images', 'variants'])
            ->findOrFail($id);

        return Inertia::render('Business/Store/Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'compare_price' => $product->compare_price,
                'sku' => $product->sku,
                'stock_quantity' => $product->stock_quantity,
                'track_stock' => $product->track_stock,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'sort_order' => $product->sort_order,
                'metadata' => $product->metadata,
                'category_id' => $product->category_id,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_url' => $img->image_url,
                    'sort_order' => $img->sort_order,
                    'is_primary' => $img->is_primary,
                ]),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'stock_quantity' => $v->stock_quantity,
                    'is_active' => $v->is_active,
                    'attributes' => $v->attributes,
                ]),
                'created_at' => $product->created_at?->format('d.m.Y H:i'),
                'updated_at' => $product->updated_at?->format('d.m.Y H:i'),
            ],
            'categories' => StoreCategory::where('store_id', $store->id)
                ->active()
                ->ordered()
                ->get(['id', 'name', 'parent_id']),
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Update a product
     */
    public function update(Request $request, string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $product = StoreProduct::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'string',
        ]);

        // Validate category belongs to this store
        if (! empty($validated['category_id'])) {
            $categoryBelongsToStore = StoreCategory::where('id', $validated['category_id'])
                ->where('store_id', $store->id)
                ->exists();

            if (! $categoryBelongsToStore) {
                return back()->withErrors(['category_id' => 'Noto\'g\'ri kategoriya.']);
            }
        }

        $this->productService->updateProduct($product, $validated);

        // Remove deleted images
        if (! empty($validated['removed_images'])) {
            foreach ($validated['removed_images'] as $imageId) {
                $image = StoreProductImage::where('product_id', $product->id)->find($imageId);
                if ($image) {
                    $wasPrimary = $image->is_primary;
                    $this->productService->deleteImage($image);

                    if ($wasPrimary) {
                        $nextImage = StoreProductImage::where('product_id', $product->id)
                            ->orderBy('sort_order')
                            ->first();
                        $nextImage?->update(['is_primary' => true]);
                    }
                }
            }
        }

        // Upload new images
        if ($request->hasFile('images')) {
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            foreach ($request->file('images') as $index => $file) {
                $this->productService->uploadImage($product, $file, ! $hasPrimary && $index === 0);
            }
        }

        return $this->storeRedirect('products.index')
            ->with('success', 'Mahsulot muvaffaqiyatli yangilandi.');
    }

    /**
     * Delete a product
     */
    public function destroy(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $product = StoreProduct::where('store_id', $store->id)->findOrFail($id);

        // Check if product has active orders
        $activeOrderItems = $product->orderItems()
            ->whereHas('order', function ($q) {
                $q->whereIn('status', \App\Models\Store\StoreOrder::ACTIVE_STATUSES);
            })
            ->exists();

        if ($activeOrderItems) {
            return back()->with('error', 'Bu mahsulotga faol buyurtmalar mavjud. Avval buyurtmalarni yakunlang.');
        }

        $this->productService->deleteProduct($product);

        return $this->storeRedirect('products.index')
            ->with('success', 'Mahsulot o\'chirildi.');
    }

    /**
     * Upload an image for a product
     */
    public function uploadImage(Request $request, string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $product = StoreProduct::where('store_id', $store->id)->findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'is_primary' => 'boolean',
        ]);

        $image = $this->productService->uploadImage(
            $product,
            $request->file('image'),
            $request->boolean('is_primary', false)
        );

        return back()->with('success', 'Rasm yuklandi.');
    }

    /**
     * Delete a product image
     */
    public function deleteImage(string $imageId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $image = StoreProductImage::whereHas('product', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        })->findOrFail($imageId);

        $wasPrimary = $image->is_primary;
        $productId = $image->product_id;

        $this->productService->deleteImage($image);

        // If deleted image was primary, set next image as primary
        if ($wasPrimary) {
            $nextImage = StoreProductImage::where('product_id', $productId)
                ->orderBy('sort_order')
                ->first();

            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Rasm o\'chirildi.');
    }

    /**
     * Toggle product active status
     */
    public function toggleActive(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $product = StoreProduct::where('store_id', $store->id)->findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Mahsulot faollashtirildi.' : 'Mahsulot nofaollashtirildi.');
    }

    /**
     * Reorder products via drag & drop
     */
    public function reorder(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'string|exists:store_products,id',
        ]);

        // Verify all products belong to this store
        $storeProductIds = StoreProduct::where('store_id', $store->id)
            ->whereIn('id', $validated['ordered_ids'])
            ->pluck('id')
            ->toArray();

        $validIds = array_intersect($validated['ordered_ids'], $storeProductIds);

        $this->productService->reorderProducts($validIds);

        return back()->with('success', 'Tartib saqlandi.');
    }
}
