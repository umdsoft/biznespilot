<?php

namespace App\Jobs\Store;

use App\Models\Store\StoreOrder;
use App\Services\Store\StoreNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoCancelExpiredOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue(config('store.queue.name', 'default'));
    }

    public function handle(StoreNotificationService $notificationService): void
    {
        $hours = config('store.order.auto_cancel_hours', 24);

        $expiredOrders = StoreOrder::where('status', StoreOrder::STATUS_PENDING)
            ->where('payment_status', 'pending')
            ->where('created_at', '<', now()->subHours($hours))
            ->get();

        $count = 0;

        foreach ($expiredOrders as $order) {
            $order->transitionTo(
                StoreOrder::STATUS_CANCELLED,
                "Avtomatik bekor qilindi ({$hours} soat ichida to'lov qilinmadi)"
            );

            // Restore stock
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->incrementStock($item->quantity);
                }
            }

            $notificationService->notifyOrderStatusChange($order, StoreOrder::STATUS_CANCELLED);
            $count++;
        }

        if ($count > 0) {
            Log::info("Auto-cancelled {$count} expired store orders");
        }
    }
}
