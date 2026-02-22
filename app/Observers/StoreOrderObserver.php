<?php

namespace App\Observers;

use App\Models\Store\StoreOrder;
use App\Services\Store\StoreOrderLeadService;

class StoreOrderObserver
{
    public function __construct(
        private StoreOrderLeadService $leadService
    ) {}

    /**
     * Handle the StoreOrder "created" event.
     * Yangi buyurtma → Lead yaratish
     */
    public function created(StoreOrder $order): void
    {
        $this->leadService->createLeadFromOrder($order);
    }

    /**
     * Handle the StoreOrder "updated" event.
     * Status o'zgarganda → Lead statusini sync qilish
     */
    public function updated(StoreOrder $order): void
    {
        if ($order->isDirty('status')) {
            $this->leadService->syncLeadStatusFromOrder($order);
        }
    }
}
