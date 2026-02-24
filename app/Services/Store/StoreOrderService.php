<?php

namespace App\Services\Store;

use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\StorePaymentTransaction;
use App\Models\Store\TelegramStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreOrderService
{
    public function __construct(
        protected StoreNotificationService $notificationService
    ) {}

    /**
     * Create order from cart
     */
    public function createOrder(
        TelegramStore $store,
        StoreCustomer $customer,
        array $items,
        array $data
    ): StoreOrder {
        return DB::transaction(function () use ($store, $customer, $items, $data) {
            $subtotal = collect($items)->sum(fn ($item) => $item['price'] * $item['quantity']);
            $deliveryFee = $data['delivery_fee'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $total = $subtotal + $deliveryFee - $discountAmount;

            $order = StoreOrder::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'status' => StoreOrder::STATUS_PENDING,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discountAmount,
                'total' => max(0, $total),
                'payment_method' => $data['payment_method'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'promo_code' => $data['promo_code'] ?? null,
            ]);

            // Create order items
            foreach ($items as $item) {
                $orderItemData = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ];

                if (! empty($item['item_metadata'])) {
                    $orderItemData['item_metadata'] = $item['item_metadata'];
                }

                $order->items()->create($orderItemData);

                // Decrement stock
                if (isset($item['product'])) {
                    $item['product']->decrementStock($item['quantity']);
                }
                if (isset($item['variant'])) {
                    $item['variant']->decrementStock($item['quantity']);
                }
            }

            // Create initial status history
            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => StoreOrder::STATUS_PENDING,
                'comment' => 'Buyurtma yaratildi',
            ]);

            // Update customer stats
            $customer->updateStats();

            // Send notification to admin
            $this->notificationService->notifyNewOrder($order);

            Log::info('Store order created', [
                'order_id' => $order->id,
                'store_id' => $store->id,
                'total' => $total,
            ]);

            return $order->load(['items', 'customer']);
        });
    }

    /**
     * Update order status
     */
    public function updateStatus(StoreOrder $order, string $newStatus, ?string $comment = null, ?string $changedBy = null): bool
    {
        if (! $order->canTransitionTo($newStatus)) {
            return false;
        }

        $result = $order->transitionTo($newStatus, $comment, $changedBy);

        if ($result) {
            // Notify customer
            $this->notificationService->notifyOrderStatusChange($order, $newStatus);

            // Handle cancellation — restore stock
            if ($newStatus === StoreOrder::STATUS_CANCELLED) {
                $this->restoreStock($order);
            }
        }

        return $result;
    }

    /**
     * Handle payment completion
     */
    public function handlePaymentCompleted(StoreOrder $order, string $provider, ?string $providerTransactionId = null): void
    {
        $order->markPaid($provider);

        // updateOrCreate — webhook dan chaqirilganda duplikat yaratmaslik uchun
        StorePaymentTransaction::updateOrCreate(
            ['order_id' => $order->id, 'provider' => $provider],
            [
                'store_id' => $order->store_id,
                'provider_transaction_id' => $providerTransactionId,
                'amount' => $order->total,
                'status' => StorePaymentTransaction::STATUS_COMPLETED,
                'paid_at' => now(),
            ]
        );

        // Auto-confirm after payment
        if ($order->status === StoreOrder::STATUS_PENDING) {
            $order->transitionTo(StoreOrder::STATUS_CONFIRMED, 'To\'lov qabul qilindi, avtomatik tasdiqlandi');
        }

        $this->notificationService->notifyPaymentReceived($order);
    }

    /**
     * Restore stock when order is cancelled
     */
    protected function restoreStock(StoreOrder $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->incrementStock($item->quantity);
            }
        }
    }

    /**
     * Get store order statistics
     */
    public function getStats(TelegramStore $store, ?string $period = 'month'): array
    {
        $query = $store->orders();

        $now = now();
        match ($period) {
            'today' => $query->whereDate('created_at', $now),
            'week' => $query->where('created_at', '>=', $now->startOfWeek()),
            'month' => $query->where('created_at', '>=', $now->startOfMonth()),
            'year' => $query->where('created_at', '>=', $now->startOfYear()),
            default => null,
        };

        $orders = $query->get();

        $paidOrders = $orders->where('payment_status', StoreOrder::PAYMENT_PAID);

        return [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', StoreOrder::STATUS_PENDING)->count(),
            'active_orders' => $orders->whereIn('status', StoreOrder::ACTIVE_STATUSES)->count(),
            'completed_orders' => $orders->where('status', StoreOrder::STATUS_DELIVERED)->count(),
            'cancelled_orders' => $orders->where('status', StoreOrder::STATUS_CANCELLED)->count(),
            'total_revenue' => $paidOrders->sum('total'),
            'avg_order_value' => $paidOrders->count() > 0 ? round($paidOrders->avg('total'), 2) : 0,
        ];
    }
}
