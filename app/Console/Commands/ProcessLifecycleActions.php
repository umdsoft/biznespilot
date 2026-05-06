<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\Lifecycle\LifecycleManagerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mijoz umr yo'li harakatlarini bajarish — har 30 daqiqada.
 * php artisan agent:lifecycle
 */
class ProcessLifecycleActions extends Command
{
    protected $signature = 'agent:lifecycle';
    protected $description = 'Rejalashtirilgan mijoz umr yo\'li harakatlarini bajarish';

    public function handle(LifecycleManagerService $lifecycleService): int
    {
        $this->info('Umr yo\'li harakatlari bajarilmoqda...');

        // PERF: Business::all() o'rniga chunkById — 100+ biznesda memory
        // overflow va serial blokirovkani oldini olamiz.
        $totalProcessed = 0;
        Business::where('status', 'active')->chunkById(50, function ($businesses) use ($lifecycleService, &$totalProcessed) {
            foreach ($businesses as $business) {
                try {
                    $processed = $lifecycleService->processScheduledActions($business->id);
                    $totalProcessed += $processed;
                } catch (\Throwable $e) {
                    Log::warning("Lifecycle: {$business->id} xato", ['error' => $e->getMessage()]);
                }
            }
        });

        $this->info("Tayyor: {$totalProcessed} ta harakat bajarildi.");
        return self::SUCCESS;
    }
}
