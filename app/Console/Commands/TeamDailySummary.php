<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Team\MeetingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Kunlik xulosa — har kuni 18:00.
 * php artisan team:daily-summary
 */
class TeamDailySummary extends Command
{
    protected $signature = 'team:daily-summary';
    protected $description = 'Barcha bizneslar uchun kunlik xulosa';

    public function handle(MeetingService $meetingService): int
    {
        $this->info('Kunlik xulosa tayyorlanmoqda...');

        $businesses = Business::all();
        $success = 0;

        foreach ($businesses as $business) {
            try {
                $result = $meetingService->generateDailySummary($business->id);
                if ($result['success']) {
                    $this->line("  ✓ {$business->name}");
                    $success++;
                }
            } catch (\Exception $e) {
                Log::warning("DailySummary: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Tayyor: {$success}/{$businesses->count()} biznes.");
        return self::SUCCESS;
    }
}
