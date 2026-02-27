<?php

namespace App\Http\Controllers\Api\Bot\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Service\StoreServiceRequestRequest;
use App\Http\Resources\Bot\Service\ServiceRequestListResource;
use App\Http\Resources\Bot\Service\ServiceRequestResource;
use App\Models\Bot\Service\ServiceRequest;
use App\Services\Bot\Service\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function __construct(
        private ServiceRequestService $requestService,
    ) {}

    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');

        $serviceRequest = $this->requestService->createRequest(
            $businessId,
            $request->validated()
        );

        return response()->json([
            'request' => new ServiceRequestResource($serviceRequest),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $telegramUserId = $request->input('telegram_user_id');

        $requests = ServiceRequest::forBusiness($businessId)
            ->byUser($telegramUserId)
            ->with(['category', 'serviceType', 'master'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'requests' => ServiceRequestListResource::collection($requests),
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

    public function cancel(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $cancelled = $this->requestService->cancelRequest(
            $serviceRequest,
            $request->input('reason')
        );

        if (! $cancelled) {
            return response()->json([
                'message' => 'Bu so\'rovni bekor qilib bo\'lmaydi.',
            ], 422);
        }

        return response()->json([
            'request' => new ServiceRequestResource(
                $serviceRequest->fresh(['category', 'serviceType', 'master'])
            ),
        ]);
    }

    public function approveCost(ServiceRequest $serviceRequest): JsonResponse
    {
        $approved = $this->requestService->approveCost($serviceRequest);

        if (! $approved) {
            return response()->json([
                'message' => 'Narxni tasdiqlash mumkin emas.',
            ], 422);
        }

        return response()->json([
            'request' => new ServiceRequestResource(
                $serviceRequest->fresh(['category', 'serviceType', 'master'])
            ),
        ]);
    }

    public function rate(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $rated = $this->requestService->rateRequest(
            $serviceRequest,
            $data['rating'],
            $data['review'] ?? null
        );

        if (! $rated) {
            return response()->json([
                'message' => 'Faqat bajarilgan so\'rovlarni baholash mumkin.',
            ], 422);
        }

        return response()->json([
            'request' => new ServiceRequestResource(
                $serviceRequest->fresh(['category', 'serviceType', 'master'])
            ),
        ]);
    }
}
