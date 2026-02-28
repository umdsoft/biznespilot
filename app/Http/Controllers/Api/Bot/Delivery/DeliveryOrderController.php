<?php

namespace App\Http\Controllers\Api\Bot\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Delivery\StoreDeliveryOrderRequest;
use App\Http\Resources\Bot\Delivery\DeliveryOrderListResource;
use App\Http\Resources\Bot\Delivery\DeliveryOrderResource;
use App\Models\Bot\Delivery\DeliveryOrder;
use App\Services\Bot\Delivery\DeliveryOrderService;
use App\Services\Bot\Delivery\DeliveryTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function __construct(
        private DeliveryOrderService $orderService,
        private DeliveryTrackingService $trackingService,
    ) {}

    public function store(StoreDeliveryOrderRequest $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $order = $this->orderService->createOrder(
            $businessId,
            $request->validated(),
            $request->input('items')
        );

        return response()->json([
            'order' => new DeliveryOrderResource($order),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $telegramUserId = $request->input('telegram_user_id');

        $orders = DeliveryOrder::forBusiness($businessId)
            ->byUser($telegramUserId)
            ->with('items')
            ->latest()
            ->paginate(20);

        return response()->json([
            'orders' => DeliveryOrderListResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(DeliveryOrder $order): JsonResponse
    {
        $order->load('items');
        $tracking = $this->trackingService->getTrackingInfo($order);

        return response()->json([
            'order' => new DeliveryOrderResource($order),
            'tracking' => $tracking,
        ]);
    }

    public function cancel(Request $request, DeliveryOrder $order): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $cancelled = $this->orderService->cancelOrder($order, $request->input('reason'));

        if (! $cancelled) {
            return response()->json([
                'message' => 'Bu buyurtmani bekor qilib bo\'lmaydi.',
            ], 422);
        }

        return response()->json([
            'order' => new DeliveryOrderResource($order->fresh('items')),
        ]);
    }

    public function track(DeliveryOrder $order): JsonResponse
    {
        return response()->json(
            $this->trackingService->getTrackingInfo($order)
        );
    }

    /**
     * Kupon kodini tekshirish (buyurtma yaratishdan oldin)
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $businessId = $request->header('X-Business-Id');

        $result = $this->orderService->validateCoupon(
            $businessId,
            $request->input('code'),
            (float) $request->input('subtotal')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
