<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Order Observer
 *
 * Order yaratilganda marketing attribution avtomatik qo'shiladi.
 *
 * CRITICAL BUSINESS RULE (BiznesPilot):
 * - Har bir buyurtma marketing kanaliga bog'langan bo'lishi kerak
 * - Attribution zanjiri: Order -> Customer -> Lead
 * - Attribution yo'q bo'lsa - warning log (tracking muammosi)
 */
class OrderObserver
{
    /**
     * Order yaratilayotganda - Attribution inheritance.
     */
    public function creating(Order $order): void
    {
        // Attribution allaqachon bor bo'lsa, skip
        if ($order->hasAttribution()) {
            return;
        }

        // Customer -> Lead zanjiridan attribution olish
        $this->inheritAttributionFromCustomer($order);
    }

    /**
     * Order yaratilgandan keyin - Logging va validation.
     */
    public function created(Order $order): void
    {
        // Attribution bor-yo'qligini tekshirish
        if (!$order->hasAttribution()) {
            // WARNING: Attribution yo'q - marketing tracking muammosi!
            Log::warning('OrderObserver: Order created WITHOUT marketing attribution', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'business_id' => $order->business_id,
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
            ]);
        } else {
            Log::info('OrderObserver: Order created with attribution', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'business_id' => $order->business_id,
                'campaign_id' => $order->campaign_id,
                'channel_id' => $order->marketing_channel_id,
                'lead_id' => $order->lead_id,
                'source_type' => $order->attribution_source_type,
            ]);
        }

        // Customer statistikalarini yangilash
        $this->updateCustomerStats($order);
    }

    /**
     * Order to'langanda - Customer total_spent yangilash.
     */
    public function updated(Order $order): void
    {
        // Payment status o'zgargan bo'lsa
        if ($order->isDirty('payment_status') && $order->payment_status === 'paid') {
            $this->updateCustomerStats($order);
        }
    }

    /**
     * Customer -> Lead zanjiridan attribution olish.
     */
    protected function inheritAttributionFromCustomer(Order $order): void
    {
        try {
            // Order modelidagi metodni chaqirish
            $order->inheritAttributionFromCustomer();

            Log::debug('OrderObserver: Attribution inherited from customer chain', [
                'order_id' => $order->id ?? 'new',
                'customer_id' => $order->customer_id,
                'campaign_id' => $order->campaign_id,
                'channel_id' => $order->marketing_channel_id,
                'lead_id' => $order->lead_id,
            ]);

        } catch (\Exception $e) {
            Log::error('OrderObserver: Failed to inherit attribution', [
                'order_id' => $order->id ?? 'new',
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Customer statistikalarini yangilash.
     */
    protected function updateCustomerStats(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer) {
            return;
        }

        try {
            // Orders count
            $ordersCount = $customer->orders()->count();

            // Total spent (faqat paid orderlar)
            $totalSpent = $customer->orders()
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            // Average order value
            $avgOrderValue = $ordersCount > 0 ? $totalSpent / $ordersCount : 0;

            // First/Last purchase dates
            $firstPurchase = $customer->orders()
                ->where('payment_status', 'paid')
                ->orderBy('ordered_at')
                ->first();

            $lastPurchase = $customer->orders()
                ->where('payment_status', 'paid')
                ->orderByDesc('ordered_at')
                ->first();

            $customer->updateQuietly([
                'orders_count' => $ordersCount,
                'total_spent' => $totalSpent,
                'average_order_value' => $avgOrderValue,
                'first_purchase_at' => $firstPurchase?->ordered_at ?? $customer->first_purchase_at,
                'last_purchase_at' => $lastPurchase?->ordered_at,
                'last_activity_at' => now(),
            ]);

            Log::debug('OrderObserver: Customer stats updated', [
                'customer_id' => $customer->id,
                'orders_count' => $ordersCount,
                'total_spent' => $totalSpent,
            ]);

        } catch (\Exception $e) {
            Log::error('OrderObserver: Failed to update customer stats', [
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
