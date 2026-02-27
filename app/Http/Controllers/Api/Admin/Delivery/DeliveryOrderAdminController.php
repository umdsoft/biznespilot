<?php

namespace App\Http\Controllers\Api\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Delivery\UpdateDeliveryOrderStatusRequest;
use App\Http\Resources\Bot\Delivery\DeliveryOrderResource;
use App\Models\Bot\Delivery\DeliveryOrder;
use App\Services\Bot\Delivery\DeliveryOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryOrderAdminController extends Controller
{
    public function __construct(
        private DeliveryOrderService $orderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryOrder::with('items');

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
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
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        $orders = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'orders' => DeliveryOrderResource::collection($orders),
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

        return response()->json([
            'order' => new DeliveryOrderResource($order),
        ]);
    }

    public function updateStatus(UpdateDeliveryOrderStatusRequest $request, DeliveryOrder $order): JsonResponse
    {
        $newStatus = $request->input('status');

        if ($newStatus === DeliveryOrder::STATUS_CANCELLED) {
            $this->orderService->cancelOrder($order, $request->input('cancel_reason'));
        } else {
            $this->orderService->updateStatus($order, $newStatus);
        }

        return response()->json([
            'order' => new DeliveryOrderResource($order->fresh('items')),
        ]);
    }

    public function assignCourier(Request $request, DeliveryOrder $order): JsonResponse
    {
        $data = $request->validate([
            'courier_name' => 'required|string|max:255',
            'courier_phone' => 'required|string|max:20',
        ]);

        $this->orderService->assignCourier($order, $data['courier_name'], $data['courier_phone']);

        return response()->json([
            'order' => new DeliveryOrderResource($order->fresh('items')),
        ]);
    }
}
