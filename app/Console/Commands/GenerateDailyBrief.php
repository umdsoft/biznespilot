<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\Analytics\Reports\DailyBriefReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Kundalik qisqa hisobot — har kuni 08:00 da ishga tushadi.
 * php artisan agent:daily-brief
 */
class GenerateDailyBrief extends Command
{
    protected $signature = 'agent:daily-brief';
    protected $description = 'Barcha bizneslar uchun kundalik qisqa hisobot yaratish';

    public function handle(DailyBriefReport $reportService): int
    {
        $this->info('Kundalik hisobot yaratilmoqda...');

        $businesses = Business::all();
        $success = 0;
        $failed = 0;

        foreach ($businesses as $business) {
            try {
                $result = $reportService->generate($business->id);

                if ($result['success']) {
                    // TODO: Foydalanuvchiga yuborish (Telegram, platforma bildirish)
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::warning("DailyBrief: {$business->id} uchun xato", ['error' => $e->getMessage()]);
                $failed++;
            }
        }

        $this->info("Tayyor: {$success} ta muvaffaqiyatli, {$failed} ta xato.");
        return self::SUCCESS;
    }
}
