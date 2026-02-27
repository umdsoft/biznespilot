<?php

namespace App\Services\Bot\Delivery;

use App\Models\Bot\Delivery\DeliveryOrder;
use App\Models\Bot\Delivery\DeliverySetting;

class DeliveryTrackingService
{
    public function updateStatus(DeliveryOrder $order, string $newStatus): bool
    {
        return $order->transitionTo($newStatus);
    }

    public function getEstimatedTime(DeliveryOrder $order): ?int
    {
        $settings = DeliverySetting::getForBusiness($order->business_id);

        return match ($order->status) {
            DeliveryOrder::STATUS_PENDING,
            DeliveryOrder::STATUS_CONFIRMED => $settings->estimated_delivery_max,
            DeliveryOrder::STATUS_PREPARING => (int) round($settings->estimated_delivery_max * 0.7),
            DeliveryOrder::STATUS_READY => (int) round($settings->estimated_delivery_max * 0.4),
            DeliveryOrder::STATUS_DELIVERING => (int) round($settings->estimated_delivery_max * 0.3),
            default => null,
        };
    }

    public function getTrackingInfo(DeliveryOrder $order): array
    {
        $steps = [
            [
                'status' => DeliveryOrder::STATUS_PENDING,
                'label' => 'Buyurtma qabul qilindi',
                'completed' => true,
                'time' => $order->created_at?->format('H:i'),
            ],
            [
                'status' => DeliveryOrder::STATUS_CONFIRMED,
                'label' => 'Tasdiqlandi',
                'completed' => $order->confirmed_at !== null,
                'time' => $order->confirmed_at?->format('H:i'),
            ],
            [
                'status' => DeliveryOrder::STATUS_PREPARING,
                'label' => 'Tayyorlanmoqda',
                'completed' => $order->preparing_at !== null,
                'time' => $order->preparing_at?->format('H:i'),
            ],
            [
                'status' => DeliveryOrder::STATUS_READY,
                'label' => 'Tayyor',
                'completed' => $order->ready_at !== null,
                'time' => $order->ready_at?->format('H:i'),
            ],
        ];

        if ($order->delivery_type === 'delivery') {
            $steps[] = [
                'status' => DeliveryOrder::STATUS_DELIVERING,
                'label' => 'Yetkazilmoqda',
                'completed' => $order->delivering_at !== null,
                'time' => $order->delivering_at?->format('H:i'),
            ];
            $steps[] = [
                'status' => DeliveryOrder::STATUS_DELIVERED,
                'label' => 'Yetkazildi',
                'completed' => $order->delivered_at !== null,
                'time' => $order->delivered_at?->format('H:i'),
            ];
        } else {
            $steps[] = [
                'status' => DeliveryOrder::STATUS_DELIVERED,
                'label' => 'Olib ketildi',
                'completed' => $order->delivered_at !== null,
                'time' => $order->delivered_at?->format('H:i'),
            ];
        }

        return [
            'current_status' => $order->status,
            'estimated_time' => $this->getEstimatedTime($order),
            'courier' => $order->courier_name ? [
                'name' => $order->courier_name,
                'phone' => $order->courier_phone,
            ] : null,
            'steps' => $steps,
        ];
    }
}
