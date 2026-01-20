<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\CrossModuleAttributionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * KPI Daily Entry ga revenue by source yangilash
 *
 * Sale yaratilganda yoki yangilanganda
 * ushbu job orqali revenue avtomatik hisoblaydi.
 */
class UpdateKpiDailyRevenueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        protected string $businessId,
        protected Carbon|string $date
    ) {
        if (is_string($this->date)) {
            $this->date = Carbon::parse($this->date);
        }
    }

    public function handle(CrossModuleAttributionService $attributionService): void
    {
        try {
            $attributionService->updateKpiDailyRevenue(
                $this->businessId,
                $this->date instanceof Carbon ? $this->date : Carbon::parse($this->date)
            );

            Log::info('UpdateKpiDailyRevenueJob: Revenue updated', [
                'business_id' => $this->businessId,
                'date' => $this->date instanceof Carbon ? $this->date->format('Y-m-d') : $this->date,
            ]);

        } catch (\Exception $e) {
            Log::error('UpdateKpiDailyRevenueJob: Failed to update revenue', [
                'business_id' => $this->businessId,
                'date' => $this->date instanceof Carbon ? $this->date->format('Y-m-d') : $this->date,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return [
            'marketing',
            'kpi-revenue',
            'business:' . $this->businessId,
        ];
    }
}
