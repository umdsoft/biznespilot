<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\Analytics\Reports\WeeklyPerformanceReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Haftalik hisobotlar — har dushanba 09:00 da ishga tushadi.
 * php artisan agent:weekly-reports
 */
class GenerateWeeklyReports extends Command
{
    protected $signature = 'agent:weekly-reports';
    protected $description = 'Barcha bizneslar uchun haftalik hisobotlar yaratish';

    public function handle(WeeklyPerformanceReport $reportService): int
    {
        $this->info('Haftalik hisobotlar yaratilmoqda...');

        $businesses = Business::where('status', 'active')->lazyById(50);
        $success = 0;
        $failed = 0;

        foreach ($businesses as $business) {
            try {
                $result = $reportService->generate($business->id);

                if ($result['success']) {
                    // TODO: Foydalanuvchiga yuborish
                    $success++;
                    $this->line("  ✓ {$business->name} — {$result['ai_tokens']} token");
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::warning("WeeklyReport: {$business->id} uchun xato", ['error' => $e->getMessage()]);
                $failed++;
            }
        }

        $this->info("Tayyor: {$success} ta muvaffaqiyatli, {$failed} ta xato.");
        return self::SUCCESS;
    }
}
