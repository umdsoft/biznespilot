<?php

namespace App\Observers;

use App\Jobs\Marketing\UpdateKpiDailyRevenueJob;
use App\Models\Sale;
use App\Services\Marketing\CrossModuleAttributionService;
use Illuminate\Support\Facades\Log;

/**
 * Sale Observer
 *
 * Sale yaratilganda va yangilanganda attribution
 * va KPI revenue yangilanishini boshqaradi.
 */
class SaleObserver
{
    /**
     * Sale yaratilganda
     */
    public function creating(Sale $sale): void
    {
        // Attribution qo'shish (save oldidan)
        $this->addAttribution($sale);
    }

    /**
     * Sale yaratilgandan keyin
     */
    public function created(Sale $sale): void
    {
        Log::info('SaleObserver: Sale created', [
            'sale_id' => $sale->id,
            'business_id' => $sale->business_id,
            'amount' => $sale->amount,
            'source_type' => $sale->attribution_source_type,
            'lead_id' => $sale->lead_id,
        ]);

        // KPI Daily Revenue yangilash (async)
        if ($sale->sale_date) {
            UpdateKpiDailyRevenueJob::dispatch(
                $sale->business_id,
                $sale->sale_date
            );
        }
    }

    /**
     * Sale yangilanganda
     */
    public function updated(Sale $sale): void
    {
        // Status completed ga o'zgargan bo'lsa
        if ($sale->isDirty('status') && $sale->status === 'completed') {
            Log::info('SaleObserver: Sale completed', [
                'sale_id' => $sale->id,
                'amount' => $sale->amount,
            ]);

            // KPI yangilash
            if ($sale->sale_date) {
                UpdateKpiDailyRevenueJob::dispatch(
                    $sale->business_id,
                    $sale->sale_date
                );
            }
        }

        // Amount o'zgargan bo'lsa
        if ($sale->isDirty('amount')) {
            if ($sale->sale_date) {
                UpdateKpiDailyRevenueJob::dispatch(
                    $sale->business_id,
                    $sale->sale_date
                );
            }
        }
    }

    /**
     * Attribution qo'shish
     */
    protected function addAttribution(Sale $sale): void
    {
        // Agar attribution allaqachon bor bo'lsa, skip
        if ($sale->attribution_source_type) {
            return;
        }

        try {
            $attributionService = app(CrossModuleAttributionService::class);
            $attribution = $attributionService->attributeSale($sale);

            $sale->attribution_source_type = $attribution['source_type'];
            $sale->acquisition_cost = $attribution['acquisition_cost'];
            $sale->attributed_spend = $attribution['attributed_spend'];

            // Profit hisoblash
            if ($sale->profit === null && $sale->amount) {
                $totalCost = ($sale->cost ?? 0) + ($sale->acquisition_cost ?? 0);
                $sale->profit = $sale->amount - $totalCost;
            }

        } catch (\Exception $e) {
            Log::error('SaleObserver: Failed to add attribution', [
                'sale_id' => $sale->id ?? 'new',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
