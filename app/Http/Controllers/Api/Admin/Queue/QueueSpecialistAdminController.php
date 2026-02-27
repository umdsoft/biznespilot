<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Queue\QueueSpecialistResource;
use App\Models\Bot\Queue\QueueSpecialist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueSpecialistAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QueueSpecialist::with('services');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $specialists = $query->get();

        return response()->json([
            'specialists' => QueueSpecialistResource::collection($specialists),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|uuid|exists:queue_branches,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string|max:500',
            'specialization' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'uuid|exists:queue_services,id',
        ]);

        $serviceIds = $data['service_ids'] ?? [];
        unset($data['service_ids']);

        $data['business_id'] = session('current_business_id');
        $specialist = QueueSpecialist::create($data);

        if (! empty($serviceIds)) {
            $specialist->services()->sync($serviceIds);
        }

        $specialist->load('services');

        return response()->json([
            'specialist' => new QueueSpecialistResource($specialist),
        ], 201);
    }

    public function show(QueueSpecialist $specialist): JsonResponse
    {
        $specialist->load('services');

        return response()->json([
            'specialist' => new QueueSpecialistResource($specialist),
        ]);
    }

    public function update(Request $request, QueueSpecialist $specialist): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'sometimes|uuid|exists:queue_branches,id',
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string|max:500',
            'specialization' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'uuid|exists:queue_services,id',
        ]);

        $serviceIds = $data['service_ids'] ?? null;
        unset($data['service_ids']);

        $specialist->update($data);

        if ($serviceIds !== null) {
            $specialist->services()->sync($serviceIds);
        }

        $specialist->load('services');

        return response()->json([
            'specialist' => new QueueSpecialistResource($specialist),
        ]);
    }

    public function destroy(QueueSpecialist $specialist): JsonResponse
    {
        $specialist->delete();

        return response()->json(['message' => 'Mutaxassis o\'chirildi.']);
    }
}
