<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Http\Resources\Store\CatalogResourceFactory;
use App\Models\Store\TelegramStore;
use App\Services\Store\BotTypeRegistry;
use App\Services\Store\Catalog\CatalogServiceFactory;
use App\Services\Store\StoreProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreCatalogController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    public function __construct(
        protected CatalogServiceFactory $catalogFactory,
        protected BotTypeRegistry $registry,
        protected StoreProductService $productService
    ) {}

    public function index(Request $request)
    {
        $store = $this->getStore();

        if (!$store) {
            return $this->redirectToStoreSetup();
        }

        $botType = $store->store_type;
        $catalogService = $this->catalogFactory->make($botType);

        $filters = $request->only(['search', 'category_id', 'status', 'sort', 'direction', 'per_page']);
        $items = $catalogService->list($store, $filters);
        $filterOptions = $catalogService->getFilterOptions($store);

        return Inertia::render('Business/Store/Catalog/Index', [
            'items' => $items,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'botType' => $botType,
            'botConfig' => $this->registry->getConfig($botType),
            'store' => $store,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    public function create()
    {
        $store = $this->getStore();

        if (!$store) {
            return $this->redirectToStoreSetup();
        }

        $botType = $store->store_type;

        return Inertia::render('Business/Store/Catalog/Form', [
            'item' => null,
            'botType' => $botType,
            'botConfig' => $this->registry->getConfig($botType),
            'categories' => $store->categories()->active()->orderBy('sort_order')->get(['id', 'name', 'parent_id']),
            'store' => $store,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    public function store(Request $request)
    {
        $store = $this->getStore();

        if (!$store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $request->validate([
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $catalogService = $this->catalogFactory->make($store->store_type);
        $item = $catalogService->create($store, $request->except(['images', 'removed_images']));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $this->productService->uploadImage($item, $file, $index === 0);
            }
        }

        return $this->storeRedirect('catalog.index')
            ->with('success', $store->getCatalogLabelSingular() . ' muvaffaqiyatli yaratildi.');
    }

    public function edit(string $id)
    {
        $store = $this->getStore();

        if (!$store) {
            return $this->redirectToStoreSetup();
        }

        $botType = $store->store_type;
        $catalogService = $this->catalogFactory->make($botType);
        $item = $catalogService->show($store, $id);

        if (!$item) {
            return $this->storeRedirect('catalog.index')
                ->with('error', 'Topilmadi.');
        }

        $resourceClass = CatalogResourceFactory::make($botType);
        $resource = new $resourceClass($item);

        return Inertia::render('Business/Store/Catalog/Form', [
            'item' => $resource->resolve(request()),
            'botType' => $botType,
            'botConfig' => $this->registry->getConfig($botType),
            'categories' => $store->categories()->active()->orderBy('sort_order')->get(['id', 'name', 'parent_id']),
            'store' => $store,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $store = $this->getStore();

        if (!$store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $request->validate([
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'string',
        ]);

        $catalogService = $this->catalogFactory->make($store->store_type);
        $item = $catalogService->show($store, $id);

        if (!$item) {
            return back()->with('error', 'Topilmadi.');
        }

        $catalogService->update($item, $request->except(['images', 'removed_images']));

        // O'chirilgan rasmlarni yo'q qilish
        if ($request->filled('removed_images')) {
            foreach ($request->removed_images as $imageId) {
                $image = $item->images()->find($imageId);
                if ($image) {
                    $this->productService->deleteImage($image);
                }
            }
            // Agar primary rasm o'chirilgan bo'lsa, birinchisini primary qilish
            $item->load('images');
            if ($item->images->count() > 0 && !$item->images->where('is_primary', true)->count()) {
                $item->images->first()->update(['is_primary' => true]);
            }
        }

        // Yangi rasmlarni yuklash
        if ($request->hasFile('images')) {
            $hasPrimary = $item->images()->where('is_primary', true)->exists();
            foreach ($request->file('images') as $index => $file) {
                $this->productService->uploadImage($item, $file, !$hasPrimary && $index === 0);
            }
        }

        return $this->storeRedirect('catalog.index')
            ->with('success', 'Muvaffaqiyatli saqlandi.');
    }

    public function toggleActive(string $id)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $catalogService = $this->catalogFactory->make($store->store_type);
        $item = $catalogService->show($store, $id);

        if (! $item) {
            return back()->with('error', 'Topilmadi.');
        }

        $item->update(['is_active' => ! $item->is_active]);

        return back()->with('success', $item->is_active ? 'Faollashtirildi.' : 'Nofaollashtirildi.');
    }

    public function destroy(string $id)
    {
        $store = $this->getStore();

        if (!$store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $catalogService = $this->catalogFactory->make($store->store_type);
        $item = $catalogService->show($store, $id);

        if (!$item) {
            return back()->with('error', 'Topilmadi.');
        }

        $catalogService->delete($item);

        return $this->storeRedirect('catalog.index')
            ->with('success', 'O\'chirildi.');
    }
}
