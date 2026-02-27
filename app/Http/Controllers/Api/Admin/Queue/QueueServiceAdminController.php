<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Queue\StoreQueueServiceRequest;
use App\Http\Resources\Bot\Queue\QueueServiceResource;
use App\Models\Bot\Queue\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueueServiceAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = QueueService::ordered()->get();

        return response()->json([
            'services' => QueueServiceResource::collection($services),
        ]);
    }

    public function store(StoreQueueServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['business_id'] = session('current_business_id');
        $data['slug'] = Str::slug($data['name']);

        $service = QueueService::create($data);

        return response()->json([
            'service' => new QueueServiceResource($service),
        ], 201);
    }

    public function show(QueueService $service): JsonResponse
    {
        return response()->json([
            'service' => new QueueServiceResource($service),
        ]);
    }

    public function update(StoreQueueServiceRequest $request, QueueService $service): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $service->update($data);

        return response()->json([
            'service' => new QueueServiceResource($service),
        ]);
    }

    public function destroy(QueueService $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Xizmat o\'chirildi.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid|exists:queue_services,id',
        ]);

        foreach ($request->input('ids') as $index => $id) {
            QueueService::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Tartib yangilandi.']);
    }
}
