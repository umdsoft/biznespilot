<?php

namespace App\Http\Controllers\Traits;

use App\Models\Store\TelegramStore;

/**
 * Session-aware store selection.
 * Checks session('active_store_id') first, falls back to first store.
 */
trait HasActiveStore
{
    protected function getStore(): ?TelegramStore
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return null;
        }

        $activeStoreId = session('active_store_id');

        if ($activeStoreId) {
            $store = TelegramStore::where('id', $activeStoreId)
                ->where('business_id', $business->id)
                ->first();

            if ($store) {
                return $store;
            }

            // Invalid session — clear it
            session()->forget('active_store_id');
        }

        return TelegramStore::where('business_id', $business->id)->first();
    }
}
