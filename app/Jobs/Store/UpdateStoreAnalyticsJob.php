<?php

namespace App\Jobs\Store;

use App\Models\Store\StoreAnalyticsDaily;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateStoreAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue(config('store.queue.name', 'default'));
    }

    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        $stores = TelegramStore::where('is_active', true)->get();

        foreach ($stores as $store) {
            $this->updateStoreAnalytics($store, $yesterday);
        }

        Log::info("Store analytics updated for {$stores->count()} stores", ['date' => $yesterday]);
    }

    protected function updateStoreAnalytics(TelegramStore $store, string $date): void
    {
        $orders = StoreOrder::where('store_id', $store->id)
            ->whereDate('created_at', $date)
            ->get();

        $paidOrders = $orders->where('payment_status', StoreOrder::PAYMENT_PAID);
        $revenue = $paidOrders->sum('total');

        $newCustomerIds = $orders->pluck('customer_id')->unique();
        $returningCount = 0;
        $newCount = 0;

        foreach ($newCustomerIds as $customerId) {
            $previousOrders = StoreOrder::where('store_id', $store->id)
                ->where('customer_id', $customerId)
                ->whereDate('created_at', '<', $date)
                ->exists();

            if ($previousOrders) {
                $returningCount++;
            } else {
                $newCount++;
            }
        }

        StoreAnalyticsDaily::updateOrCreate(
            ['store_id' => $store->id, 'date' => $date],
            [
                'orders_count' => $orders->count(),
                'revenue' => $revenue,
                'avg_order_value' => $paidOrders->count() > 0 ? round($revenue / $paidOrders->count(), 2) : 0,
                'new_customers' => $newCount,
                'returning_customers' => $returningCount,
            ]
        );
    }
}
