<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bot\Queue\QueueBranchResource;
use App\Models\Bot\Queue\QueueBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueBranchAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $branches = QueueBranch::withCount('specialists')
            ->with('services')
            ->get();

        return response()->json([
            'branches' => QueueBranchResource::collection($branches),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'working_hours' => 'nullable|array',
            'lunch_break' => 'nullable|array',
            'lunch_break.from' => 'required_with:lunch_break|string',
            'lunch_break.to' => 'required_with:lunch_break|string',
            'slot_duration' => 'nullable|integer|min:5|max:240',
            'max_concurrent' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'uuid|exists:queue_services,id',
        ]);

        $serviceIds = $data['service_ids'] ?? [];
        unset($data['service_ids']);

        $data['business_id'] = session('current_business_id');
        $branch = QueueBranch::create($data);

        if (! empty($serviceIds)) {
            $branch->services()->sync($serviceIds);
        }

        $branch->loadCount('specialists')->load('services');

        return response()->json([
            'branch' => new QueueBranchResource($branch),
        ], 201);
    }

    public function show(QueueBranch $branch): JsonResponse
    {
        $branch->loadCount('specialists')->load('services');

        return response()->json([
            'branch' => new QueueBranchResource($branch),
        ]);
    }

    public function update(Request $request, QueueBranch $branch): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'working_hours' => 'nullable|array',
            'lunch_break' => 'nullable|array',
            'lunch_break.from' => 'required_with:lunch_break|string',
            'lunch_break.to' => 'required_with:lunch_break|string',
            'slot_duration' => 'nullable|integer|min:5|max:240',
            'max_concurrent' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'uuid|exists:queue_services,id',
        ]);

        $serviceIds = $data['service_ids'] ?? null;
        unset($data['service_ids']);

        $branch->update($data);

        if ($serviceIds !== null) {
            $branch->services()->sync($serviceIds);
        }

        $branch->loadCount('specialists')->load('services');

        return response()->json([
            'branch' => new QueueBranchResource($branch),
        ]);
    }

    public function destroy(QueueBranch $branch): JsonResponse
    {
        $branch->delete();

        return response()->json(['message' => 'Filial o\'chirildi.']);
    }
}
