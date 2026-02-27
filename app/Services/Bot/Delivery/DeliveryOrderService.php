<?php

namespace App\Services\Bot\Delivery;

use App\Models\Bot\Delivery\DeliveryMenuItem;
use App\Models\Bot\Delivery\DeliveryOrder;
use App\Models\Bot\Delivery\DeliveryOrderItem;
use App\Models\Bot\Delivery\DeliverySetting;
use Illuminate\Support\Facades\DB;

class DeliveryOrderService
{
    public function createOrder(string $businessId, array $data, array $items): DeliveryOrder
    {
        return DB::transaction(function () use ($businessId, $data, $items) {
            $settings = DeliverySetting::getForBusiness($businessId);

            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $menuItem = DeliveryMenuItem::findOrFail($item['menu_item_id']);
                $unitPrice = $menuItem->effective_price;

                // Apply variant price modifier
                $variantName = null;
                if (! empty($item['variant_id'])) {
                    $variant = $menuItem->variants()->find($item['variant_id']);
                    if ($variant) {
                        $unitPrice += $variant->price_modifier;
                        $variantName = $variant->name;
                    }
                }

                // Calculate addons
                $addonsTotal = 0;
                $addonsData = [];
                if (! empty($item['addon_ids'])) {
                    $addons = $menuItem->addons()->whereIn('id', $item['addon_ids'])->get();
                    foreach ($addons as $addon) {
                        $addonsTotal += $addon->price;
                        $addonsData[] = ['name' => $addon->name, 'price' => (float) $addon->price];
                    }
                }

                $qty = $item['quantity'];
                $lineTotal = ($unitPrice * $qty) + ($addonsTotal * $qty);
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'variant_name' => $variantName,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'addons' => $addonsData ?: null,
                    'addons_total' => $addonsTotal,
                    'subtotal' => $lineTotal,
                    'special_instructions' => $item['special_instructions'] ?? null,
                ];
            }

            $deliveryFee = ($data['delivery_type'] ?? 'delivery') === 'pickup'
                ? 0
                : $settings->calculateDeliveryFee($subtotal);

            $serviceFee = $settings->calculateServiceFee($subtotal);
            $discountAmount = 0; // TODO: coupon logic
            $total = $subtotal + $deliveryFee + $serviceFee - $discountAmount;

            $order = DeliveryOrder::create([
                'business_id' => $businessId,
                'order_number' => DeliveryOrder::generateOrderNumber(),
                'telegram_user_id' => $data['telegram_user_id'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'status' => DeliveryOrder::STATUS_PENDING,
                'delivery_type' => $data['delivery_type'] ?? 'delivery',
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_landmark' => $data['delivery_landmark'] ?? null,
                'delivery_lat' => $data['delivery_lat'] ?? null,
                'delivery_lng' => $data['delivery_lng'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'estimated_delivery' => $settings->estimated_delivery_max,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'service_fee' => $serviceFee,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'coupon_code' => $data['coupon_code'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($orderItems as $oi) {
                $order->items()->create($oi);
            }

            if ($settings->auto_accept_orders) {
                $order->transitionTo(DeliveryOrder::STATUS_CONFIRMED);
            }

            return $order->load('items');
        });
    }

    public function calculateTotals(string $businessId, array $items): array
    {
        $settings = DeliverySetting::getForBusiness($businessId);
        $subtotal = 0;

        foreach ($items as $item) {
            $menuItem = DeliveryMenuItem::find($item['menu_item_id']);
            if (! $menuItem) {
                continue;
            }

            $unitPrice = $menuItem->effective_price;

            if (! empty($item['variant_id'])) {
                $variant = $menuItem->variants()->find($item['variant_id']);
                if ($variant) {
                    $unitPrice += $variant->price_modifier;
                }
            }

            $addonsTotal = 0;
            if (! empty($item['addon_ids'])) {
                $addonsTotal = $menuItem->addons()
                    ->whereIn('id', $item['addon_ids'])
                    ->sum('price');
            }

            $subtotal += ($unitPrice * $item['quantity']) + ($addonsTotal * $item['quantity']);
        }

        $deliveryFee = $settings->calculateDeliveryFee($subtotal);
        $serviceFee = $settings->calculateServiceFee($subtotal);

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'total' => $subtotal + $deliveryFee + $serviceFee,
            'min_order_amount' => (float) $settings->min_order_amount,
            'free_delivery_from' => $settings->free_delivery_from ? (float) $settings->free_delivery_from : null,
        ];
    }

    public function cancelOrder(DeliveryOrder $order, ?string $reason = null): bool
    {
        if (! $order->canTransitionTo(DeliveryOrder::STATUS_CANCELLED)) {
            return false;
        }

        $order->cancel_reason = $reason;

        return $order->transitionTo(DeliveryOrder::STATUS_CANCELLED);
    }

    public function updateStatus(DeliveryOrder $order, string $newStatus): bool
    {
        return $order->transitionTo($newStatus);
    }

    public function assignCourier(DeliveryOrder $order, string $name, string $phone): bool
    {
        $order->courier_name = $name;
        $order->courier_phone = $phone;

        return $order->save();
    }
}
