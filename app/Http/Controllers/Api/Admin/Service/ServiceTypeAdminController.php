<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Service\StoreServiceTypeRequest;
use App\Http\Resources\Bot\Service\ServiceTypeResource;
use App\Models\Bot\Service\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTypeAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceType::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->ordered();

        $types = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'service_types' => ServiceTypeResource::collection($types),
            'meta' => [
                'current_page' => $types->currentPage(),
                'last_page' => $types->lastPage(),
                'total' => $types->total(),
            ],
        ]);
    }

    public function store(StoreServiceTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = session('current_business_id');

        $type = ServiceType::create($data);
        $type->load('category');

        return response()->json([
            'service_type' => new ServiceTypeResource($type),
        ], 201);
    }

    public function show(ServiceType $serviceType): JsonResponse
    {
        $serviceType->load('category');

        return response()->json([
            'service_type' => new ServiceTypeResource($serviceType),
        ]);
    }

    public function update(StoreServiceTypeRequest $request, ServiceType $serviceType): JsonResponse
    {
        $serviceType->update($request->validated());
        $serviceType->load('category');

        return response()->json([
            'service_type' => new ServiceTypeResource($serviceType),
        ]);
    }

    public function destroy(ServiceType $serviceType): JsonResponse
    {
        $serviceType->delete();

        return response()->json(['message' => 'Xizmat turi o\'chirildi.']);
    }
}
