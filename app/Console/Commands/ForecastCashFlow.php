<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\CashFlow\CashFlowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pul oqimi bashoratini yangilash — har kuni 07:00.
 * php artisan agent:forecast-cash
 */
class ForecastCashFlow extends Command
{
    protected $signature = 'agent:forecast-cash';
    protected $description = 'Barcha bizneslar uchun 30 kunlik pul oqimi bashorati';

    public function handle(CashFlowService $cashFlowService): int
    {
        $this->info('Pul oqimi bashorati yaratilmoqda...');

        $businesses = Business::where('status', 'active')->lazyById(50);
        $dangers = 0;

        foreach ($businesses as $business) {
            try {
                $result = $cashFlowService->forecast($business->id, 30);
                if (($result['danger_dates_count'] ?? 0) > 0) {
                    $this->warn("  ⚠ {$business->name}: {$result['danger_dates_count']} ta xavfli sana");
                    $dangers++;
                }
            } catch (\Exception $e) {
                Log::warning("CashFlow: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Tayyor. {$dangers} ta biznesda xavfli sanalar aniqlandi.");
        return self::SUCCESS;
    }
}
