<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Service\UpdateServiceRequestStatusRequest;
use App\Http\Resources\Bot\Service\ServiceRequestResource;
use App\Models\Bot\Service\ServiceRequest;
use App\Services\Bot\Service\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestAdminController extends Controller
{
    public function __construct(
        private ServiceRequestService $requestService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::with(['category', 'serviceType', 'master']);

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('master_id')) {
            $query->byMaster($request->input('master_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('request_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        $requests = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'requests' => ServiceRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function show(ServiceRequest $serviceRequest): JsonResponse
    {
        $serviceRequest->load(['category', 'serviceType', 'master']);

        return response()->json([
            'request' => new ServiceRequestResource($serviceRequest),
        ]);
    }

    public function assign(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'master_id' => 'required|uuid|exists:service_masters,id',
        ]);

        $assigned = $this->requestService->assignMaster($serviceRequest, $data['master_id']);

        if (! $assigned) {
            return response()->json([
                'message' => 'Usta tayinlash mumkin emas.',
            ], 422);
        }

        return response()->json([
            'request' => new ServiceRequestResource(
                $serviceRequest->fresh(['category', 'serviceType', 'master'])
            ),
        ]);
    }

    public function updateStatus(UpdateServiceRequestStatusRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $newStatus = $request->input('status');

        if ($newStatus === ServiceRequest::STATUS_CANCELLED) {
            $this->requestService->cancelRequest($serviceRequest, $request->input('cancel_reason'));
        } elseif ($newStatus === ServiceRequest::STATUS_COMPLETED) {
            $this->requestService->complete($serviceRequest);
        } else {
            $this->requestService->updateStatus($serviceRequest, $newStatus);
        }

        return response()->json([
            'request' => new ServiceRequestResource(
                $serviceRequest->fresh(['category', 'serviceType', 'master'])
            ),
        ]);
    }

    public function setCost(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'labor_cost' => 'required|numeric|min:0',
            'parts_used' => 'nullable|array',
            'parts_used.*.name' => 'required_with:parts_used|string|max:255',
            'parts_used.*.price' => 'required_with:parts_used|numeric|min:0',
        ]);

        $this->requestService->setCost(
            $serviceRequest,
            $data['labor_cost'],
            $data['parts_used'] ?? []
        );

        return response()->json([
            'request' => new ServiceRequestResource(
                $serviceRequest->fresh(['category', 'serviceType', 'master'])
            ),
        ]);
    }
}
