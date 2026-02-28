<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreProduct;
use App\Models\Store\TelegramStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StoreCategoryController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    /**
     * List all categories for the store
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup('Avval do\'kon yarating.');
        }

        $categories = StoreCategory::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->withCount('products')->ordered();
            }])
            ->withCount('products')
            ->ordered()
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'slug' => $category->slug,
                'color' => $category->color,
                'image_url' => $category->image_url,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
                'parent_id' => $category->parent_id,
                'children' => $category->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'description' => $child->description,
                    'color' => $child->color,
                    'parent_id' => $child->parent_id,
                    'products_count' => $child->products_count ?? 0,
                ]),
                'children_count' => $category->children->count(),
                'products_count' => $category->products_count,
                'created_at' => $category->created_at?->format('d.m.Y'),
            ]);

        // Build tree structure for parent categories (only root categories as options)
        $parentOptions = StoreCategory::where('store_id', $store->id)
            ->root()
            ->active()
            ->ordered()
            ->get(['id', 'name']);

        return Inertia::render('Business/Store/Categories/Index', [
            'categories' => $categories,
            'parentOptions' => $parentOptions,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Create a new category
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
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:store_categories,id',
            'image_url' => 'nullable|string|url|max:500',
            'is_active' => 'boolean',
        ]);

        // Validate parent belongs to this store
        if (! empty($validated['parent_id'])) {
            $parentBelongsToStore = StoreCategory::where('id', $validated['parent_id'])
                ->where('store_id', $store->id)
                ->exists();

            if (! $parentBelongsToStore) {
                return back()->withErrors(['parent_id' => 'Noto\'g\'ri ota kategoriya.']);
            }
        }

        $maxSortOrder = StoreCategory::where('store_id', $store->id)->max('sort_order') ?? 0;

        StoreCategory::create([
            'store_id' => $store->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'color' => $validated['color'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $maxSortOrder + 1,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->storeRedirect('categories.index')
            ->with('success', 'Kategoriya muvaffaqiyatli yaratildi.');
    }

    /**
     * Update an existing category
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

        $category = StoreCategory::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:store_categories,id',
            'image_url' => 'nullable|string|url|max:500',
            'is_active' => 'boolean',
        ]);

        // Prevent setting self as parent
        if (! empty($validated['parent_id']) && $validated['parent_id'] === $id) {
            return back()->withErrors(['parent_id' => 'Kategoriya o\'zini ota sifatida tanlay olmaydi.']);
        }

        // Prevent setting a child as parent (circular reference)
        if (! empty($validated['parent_id'])) {
            $parentBelongsToStore = StoreCategory::where('id', $validated['parent_id'])
                ->where('store_id', $store->id)
                ->exists();

            if (! $parentBelongsToStore) {
                return back()->withErrors(['parent_id' => 'Noto\'g\'ri ota kategoriya.']);
            }

            // Check if the target parent is a child of this category
            $isChild = StoreCategory::where('parent_id', $id)
                ->where('id', $validated['parent_id'])
                ->exists();

            if ($isChild) {
                return back()->withErrors(['parent_id' => 'Bola kategoriyani ota sifatida tanlab bo\'lmaydi.']);
            }
        }

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? $category->color,
            'parent_id' => $validated['parent_id'] ?? null,
            'image_url' => $validated['image_url'] ?? $category->image_url,
            'is_active' => $validated['is_active'] ?? $category->is_active,
        ];

        // Update slug if name changed
        if ($validated['name'] !== $category->name) {
            $updateData['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);
        }

        $category->update($updateData);

        // If category is deactivated, deactivate children too
        if (! ($validated['is_active'] ?? true)) {
            StoreCategory::where('parent_id', $id)
                ->where('store_id', $store->id)
                ->update(['is_active' => false]);
        }

        return $this->storeRedirect('categories.index')
            ->with('success', 'Kategoriya muvaffaqiyatli yangilandi.');
    }

    /**
     * Delete a category
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

        $category = StoreCategory::where('store_id', $store->id)->findOrFail($id);

        // Check if category has products
        $productsCount = StoreProduct::where('category_id', $id)->count();
        if ($productsCount > 0) {
            return back()->with('error', "Bu kategoriyada {$productsCount} ta mahsulot mavjud. Avval mahsulotlarni boshqa kategoriyaga ko'chiring.");
        }

        // Check if category has children
        $childrenCount = StoreCategory::where('parent_id', $id)->count();
        if ($childrenCount > 0) {
            return back()->with('error', "Bu kategoriyada {$childrenCount} ta bola kategoriya mavjud. Avval ularni o'chiring yoki ko'chiring.");
        }

        $category->delete();

        return $this->storeRedirect('categories.index')
            ->with('success', 'Kategoriya o\'chirildi.');
    }

    /**
     * Reorder categories via drag & drop
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
            'ordered_ids.*' => 'string|exists:store_categories,id',
        ]);

        // Verify all categories belong to this store
        $storeCategoryIds = StoreCategory::where('store_id', $store->id)
            ->whereIn('id', $validated['ordered_ids'])
            ->pluck('id')
            ->toArray();

        foreach ($validated['ordered_ids'] as $index => $categoryId) {
            if (in_array($categoryId, $storeCategoryIds)) {
                StoreCategory::where('id', $categoryId)->update(['sort_order' => $index]);
            }
        }

        return back()->with('success', 'Tartib saqlandi.');
    }
}
