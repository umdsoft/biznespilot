<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Http\Resources\Store\CatalogResourceFactory;
use App\Models\Store\TelegramStore;
use App\Services\Store\BotTypeRegistry;
use App\Services\Store\Catalog\CatalogServiceFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreCatalogController extends Controller
{
    use HasCurrentBusiness, HasStorePanelType;

    public function __construct(
        protected CatalogServiceFactory $catalogFactory,
        protected BotTypeRegistry $registry
    ) {}

    protected function getStore(): ?TelegramStore
    {
        $business = $this->getCurrentBusiness();

        return TelegramStore::where('business_id', $business->id)->first();
    }

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

        $catalogService = $this->catalogFactory->make($store->store_type);
        $item = $catalogService->create($store, $request->all());

        return $this->storeRedirect('catalog.edit', [$item->id])
            ->with('success', $store->getCatalogLabelSingular() . ' yaratildi.');
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

        return Inertia::render('Business/Store/Catalog/Form', [
            'item' => new $resourceClass($item),
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

        $catalogService = $this->catalogFactory->make($store->store_type);
        $item = $catalogService->show($store, $id);

        if (!$item) {
            return back()->with('error', 'Topilmadi.');
        }

        $catalogService->update($item, $request->all());

        return $this->storeRedirect('catalog.edit', [$id])
            ->with('success', 'Yangilandi.');
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
