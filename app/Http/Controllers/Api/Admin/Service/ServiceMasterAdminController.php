<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Service\StoreServiceMasterRequest;
use App\Http\Resources\Bot\Service\ServiceMasterResource;
use App\Models\Bot\Service\ServiceMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceMasterAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceMaster::with('categories');

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('service_categories.id', $request->input('category_id'));
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $sortField = $request->input('sort', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortField, $sortDir);

        $masters = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'masters' => ServiceMasterResource::collection($masters),
            'meta' => [
                'current_page' => $masters->currentPage(),
                'last_page' => $masters->lastPage(),
                'total' => $masters->total(),
            ],
        ]);
    }

    public function store(StoreServiceMasterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = session('current_business_id');

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $master = ServiceMaster::create($data);

        if (! empty($categoryIds)) {
            $master->categories()->sync($categoryIds);
        }

        $master->load('categories');

        return response()->json([
            'master' => new ServiceMasterResource($master),
        ], 201);
    }

    public function show(ServiceMaster $master): JsonResponse
    {
        $master->load('categories');

        return response()->json([
            'master' => new ServiceMasterResource($master),
        ]);
    }

    public function update(StoreServiceMasterRequest $request, ServiceMaster $master): JsonResponse
    {
        $data = $request->validated();

        $categoryIds = $data['category_ids'] ?? null;
        unset($data['category_ids']);

        $master->update($data);

        if ($categoryIds !== null) {
            $master->categories()->sync($categoryIds);
        }

        $master->load('categories');

        return response()->json([
            'master' => new ServiceMasterResource($master),
        ]);
    }

    public function destroy(ServiceMaster $master): JsonResponse
    {
        $master->delete();

        return response()->json(['message' => 'Usta o\'chirildi.']);
    }

    public function toggle(ServiceMaster $master): JsonResponse
    {
        $master->is_active = ! $master->is_active;
        $master->save();

        return response()->json([
            'is_active' => $master->is_active,
        ]);
    }
}
