<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\OrderResource;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mini App Order Controller.
 *
 * Provides customer order listing and detail view.
 * All endpoints require MiniAppAuth middleware (Telegram initData).
 */
class OrderController extends Controller
{
    /**
     * GET /orders — Customer's orders list.
     */
    public function index(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $query = StoreOrder::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->with(['items.product.primaryImage'])
            ->orderByDesc('created_at');

        // Status filter
        if ($request->filled('status')) {
            $status = $request->input('status');

            if ($status === 'active') {
                $query->whereIn('status', StoreOrder::ACTIVE_STATUSES);
            } elseif ($status === 'completed') {
                $query->whereIn('status', StoreOrder::TERMINAL_STATUSES);
            } else {
                $query->where('status', $status);
            }
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 50);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /orders/{order_number} — Order detail with items and status history.
     */
    public function show(Request $request, TelegramStore $store, StoreOrder $order): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        // Verify the order belongs to this store and customer
        if ($order->store_id !== $store->id || $order->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Buyurtma topilmadi',
            ], 404);
        }

        $order->load([
            'items.product.primaryImage',
            'statusHistory',
            'paymentTransaction',
        ]);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }
}
