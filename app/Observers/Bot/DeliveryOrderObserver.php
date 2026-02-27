<?php

namespace App\Observers\Bot;

use App\Models\Bot\Delivery\DeliveryOrder;
use App\Services\Bot\Delivery\DeliveryStatsService;
use Illuminate\Support\Facades\Log;

class DeliveryOrderObserver
{
    public function creating(DeliveryOrder $order): void
    {
        if (! $order->order_number) {
            $order->order_number = DeliveryOrder::generateOrderNumber();
        }
    }

    public function created(DeliveryOrder $order): void
    {
        Log::info("Delivery order created: {$order->order_number}", [
            'business_id' => $order->business_id,
            'total' => $order->total,
        ]);
    }

    public function updated(DeliveryOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $newStatus = $order->status;

        Log::info("Delivery order status changed: {$order->order_number} → {$newStatus}");

        if ($newStatus === DeliveryOrder::STATUS_DELIVERED) {
            try {
                app(DeliveryStatsService::class)
                    ->calculateDailyStats($order->business_id, now()->toDateString());
            } catch (\Throwable $e) {
                Log::error("Failed to update delivery stats: {$e->getMessage()}");
            }
        }
    }
}
