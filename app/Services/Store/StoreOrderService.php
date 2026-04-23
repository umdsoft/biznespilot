<?php

namespace App\Services\Store;

use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\StorePaymentTransaction;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreProductVariant;
use App\Models\Store\StorePromoCode;
use App\Models\Store\TelegramStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreOrderService
{
    public function __construct(
        protected StoreNotificationService $notificationService
    ) {}

    /**
     * Create order from cart with pessimistic stock lock.
     *
     * Race condition guard: items (products/variants) are locked FOR UPDATE
     * before decrement. Two concurrent checkouts for the last unit no longer
     * oversell. Notifications run AFTER the transaction commits.
     *
     * @throws \App\Exceptions\Store\OutOfStockException
     */
    public function createOrder(
        TelegramStore $store,
        StoreCustomer $customer,
        array $items,
        array $data
    ): StoreOrder {
        $order = DB::transaction(function () use ($store, $customer, $items, $data) {
            // Lock all products/variants FOR UPDATE, validate stock, then decrement
            $productIds = collect($items)->pluck('product_id')->unique()->filter()->values();
            $variantIds = collect($items)->pluck('variant_id')->unique()->filter()->values();

            $lockedProducts = $productIds->isNotEmpty()
                ? StoreProduct::whereIn('id', $productIds)
                    ->where('store_id', $store->id)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id')
                : collect();

            $lockedVariants = $variantIds->isNotEmpty()
                ? StoreProductVariant::whereIn('id', $variantIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id')
                : collect();

            // Validate stock under lock — prevents TOCTOU oversell
            foreach ($items as $item) {
                $product = $lockedProducts->get($item['product_id']);
                if (! $product || ! $product->is_active) {
                    throw new \App\Exceptions\Store\OutOfStockException(
                        "Mahsulot \"{$item['product_name']}\" mavjud emas"
                    );
                }

                if ($product->track_stock) {
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \App\Exceptions\Store\OutOfStockException(
                            "Mahsulot \"{$product->name}\" omborda yetarli emas (qoldiq: {$product->stock_quantity})"
                        );
                    }
                }

                if (! empty($item['variant_id'])) {
                    $variant = $lockedVariants->get($item['variant_id']);
                    if (! $variant || ! $variant->is_active) {
                        throw new \App\Exceptions\Store\OutOfStockException(
                            "Variant \"{$item['variant_name']}\" mavjud emas"
                        );
                    }

                    if ($product->track_stock && $variant->stock_quantity < $item['quantity']) {
                        throw new \App\Exceptions\Store\OutOfStockException(
                            "\"{$product->name}\" ({$variant->name}) varianti omborda yetarli emas"
                        );
                    }
                }
            }

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
                'delivery_type' => $data['delivery_type'] ?? 'delivery',
                'delivery_address' => $data['delivery_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'promo_code' => $data['promo_code'] ?? null,
            ]);

            // Create order items + decrement stock atomically (under lock)
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

                // Decrement using locked instances (not the stale ones from Cart)
                $product = $lockedProducts->get($item['product_id']);
                if ($product && $product->track_stock) {
                    $product->decrement('stock_quantity', $item['quantity']);
                }

                if (! empty($item['variant_id'])) {
                    $variant = $lockedVariants->get($item['variant_id']);
                    if ($variant && $product && $product->track_stock) {
                        $variant->decrement('stock_quantity', $item['quantity']);
                    }
                }
            }

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => StoreOrder::STATUS_PENDING,
                'comment' => 'Buyurtma yaratildi',
            ]);

            $customer->updateStats();

            Log::info('Store order created', [
                'order_id' => $order->id,
                'store_id' => $store->id,
                'total' => $total,
            ]);

            return $order;
        });

        // Notify AFTER transaction commits — HTTP to Telegram must never block DB lock
        dispatch(function () use ($order) {
            $this->notificationService->notifyNewOrder($order->fresh(['items', 'customer']));
        })->afterResponse();

        return $order->load(['items', 'customer']);
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
            $this->notificationService->notifyOrderStatusChange($order, $newStatus);

            // Auto-mark cash payment as paid on delivery
            if ($newStatus === StoreOrder::STATUS_DELIVERED
                && $order->payment_method === 'cash'
                && ! $order->isPaid()
            ) {
                $order->markPaid('cash');

                StorePaymentTransaction::updateOrCreate(
                    ['order_id' => $order->id, 'provider' => StorePaymentTransaction::PROVIDER_CASH],
                    [
                        'store_id' => $order->store_id,
                        'amount' => $order->total,
                        'status' => StorePaymentTransaction::STATUS_COMPLETED,
                        'paid_at' => now(),
                    ]
                );
            }

            // Handle cancellation — restore stock (product + variant)
            if ($newStatus === StoreOrder::STATUS_CANCELLED) {
                $this->restoreStock($order);
            }
        }

        return $result;
    }

    /**
     * Handle payment completion atomically with lockForUpdate.
     *
     * Called from webhook handlers. Idempotent — re-delivering the same
     * webhook payload does NOT double-mark the order or notify twice.
     */
    public function handlePaymentCompleted(StoreOrder $order, string $provider, ?string $providerTransactionId = null): void
    {
        $shouldNotify = DB::transaction(function () use ($order, $provider, $providerTransactionId) {
            // Re-read order under lock to avoid race with another webhook retry
            $fresh = StoreOrder::where('id', $order->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->isPaid()) {
                // Already paid — idempotent return, don't re-notify
                return false;
            }

            $fresh->markPaid($provider);

            StorePaymentTransaction::updateOrCreate(
                ['order_id' => $fresh->id, 'provider' => $provider],
                [
                    'store_id' => $fresh->store_id,
                    'provider_transaction_id' => $providerTransactionId,
                    'amount' => $fresh->total,
                    'status' => StorePaymentTransaction::STATUS_COMPLETED,
                    'paid_at' => now(),
                ]
            );

            if ($fresh->status === StoreOrder::STATUS_PENDING) {
                $fresh->transitionTo(StoreOrder::STATUS_CONFIRMED, 'To\'lov qabul qilindi, avtomatik tasdiqlandi');
            }

            return true;
        });

        if ($shouldNotify) {
            dispatch(function () use ($order) {
                $this->notificationService->notifyPaymentReceived($order->fresh());
            })->afterResponse();
        }
    }

    /**
     * Restore stock when order is cancelled.
     *
     * Restores both product and variant quantities (variant was missed previously,
     * causing phantom stock loss when orders with variants were cancelled).
     */
    protected function restoreStock(StoreOrder $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $product = StoreProduct::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    if ($product && $product->track_stock) {
                        $product->increment('stock_quantity', $item->quantity);
                    }
                }

                if ($item->variant_id) {
                    $variant = StoreProductVariant::where('id', $item->variant_id)
                        ->lockForUpdate()
                        ->first();
                    if ($variant) {
                        $variant->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // Restore promo code usage
            if ($order->promo_code) {
                $promo = StorePromoCode::where('store_id', $order->store_id)
                    ->where('code', strtoupper(trim($order->promo_code)))
                    ->lockForUpdate()
                    ->first();
                if ($promo && $promo->used_count > 0) {
                    $promo->decrement('used_count');
                }
            }
        });
    }

    /**
     * Get store order statistics
     */
    public function getStats(TelegramStore $store, ?string $period = 'month'): array
    {
        // All orders for per-status counts (not period-filtered)
        $allOrders = $store->orders()->get();

        // Period-filtered orders for revenue
        $periodQuery = $store->orders();
        $now = now();
        match ($period) {
            'today' => $periodQuery->whereDate('created_at', $now),
            'week' => $periodQuery->where('created_at', '>=', $now->startOfWeek()),
            'month' => $periodQuery->where('created_at', '>=', $now->startOfMonth()),
            'year' => $periodQuery->where('created_at', '>=', $now->startOfYear()),
            default => null,
        };
        $periodOrders = $periodQuery->get();
        $paidOrders = $periodOrders->where('payment_status', StoreOrder::PAYMENT_PAID);

        // Today's revenue
        $todayRevenue = $store->orders()
            ->whereDate('created_at', now())
            ->where('payment_status', StoreOrder::PAYMENT_PAID)
            ->sum('total');

        return [
            'total_orders' => $allOrders->count(),
            // Per-status counts (all time, for tabs)
            'pending' => $allOrders->where('status', StoreOrder::STATUS_PENDING)->count(),
            'confirmed' => $allOrders->where('status', StoreOrder::STATUS_CONFIRMED)->count(),
            'processing' => $allOrders->where('status', StoreOrder::STATUS_PROCESSING)->count(),
            'shipped' => $allOrders->where('status', StoreOrder::STATUS_SHIPPED)->count(),
            'delivered' => $allOrders->where('status', StoreOrder::STATUS_DELIVERED)->count(),
            'cancelled' => $allOrders->where('status', StoreOrder::STATUS_CANCELLED)->count(),
            'active_orders' => $allOrders->whereIn('status', StoreOrder::ACTIVE_STATUSES)->count(),
            // Revenue
            'today_revenue' => $todayRevenue,
            'total_revenue' => $paidOrders->sum('total'),
            'avg_order_value' => $paidOrders->count() > 0 ? round($paidOrders->avg('total'), 2) : 0,
        ];
    }
}
