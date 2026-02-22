<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\CatalogResourceFactory;
use App\Models\Store\TelegramStore;
use App\Services\Store\Catalog\CatalogServiceFactory;
use Illuminate\Http\Request;

class MiniAppCatalogController extends Controller
{
    public function __construct(
        protected CatalogServiceFactory $catalogFactory
    ) {}

    public function index(Request $request, TelegramStore $store)
    {
        $catalogService = $this->catalogFactory->make($store->store_type);

        $filters = $request->only(['category_id', 'sort', 'direction']);
        $filters['per_page'] = 20;
        $filters['status'] = 'active';
        $items = $catalogService->list($store, $filters);

        return CatalogResourceFactory::collection($store->store_type, $items);
    }

    public function show(TelegramStore $store, string $slug)
    {
        $modelClass = $store->getCatalogModelClass();

        $item = $modelClass::where('store_id', $store->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $resourceClass = CatalogResourceFactory::make($store->store_type);

        return new $resourceClass($item);
    }

    public function search(Request $request, TelegramStore $store)
    {
        $query = $request->input('q', '');

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $catalogService = $this->catalogFactory->make($store->store_type);
        $results = $catalogService->search($store, $query);

        return CatalogResourceFactory::collection($store->store_type, $results);
    }

    public function featured(TelegramStore $store)
    {
        $modelClass = $store->getCatalogModelClass();

        $items = $modelClass::where('store_id', $store->id)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->limit(10)
            ->get();

        return CatalogResourceFactory::collection($store->store_type, $items);
    }

    public function filterOptions(TelegramStore $store)
    {
        $catalogService = $this->catalogFactory->make($store->store_type);

        return response()->json($catalogService->getFilterOptions($store));
    }
}
