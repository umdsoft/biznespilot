<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Team\MeetingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Ertalabki jamoa majlisi — har kuni 08:00.
 * php artisan team:morning-standup
 */
class TeamMorningStandup extends Command
{
    protected $signature = 'team:morning-standup';
    protected $description = 'Barcha bizneslar uchun ertalabki jamoa majlisi';

    public function handle(MeetingService $meetingService): int
    {
        $this->info('Ertalabki majlis tayyorlanmoqda...');

        $businesses = Business::where('status', 'active')->lazyById(50);
        $success = 0;

        foreach ($businesses as $business) {
            try {
                $result = $meetingService->generateMorningStandup($business->id);
                if ($result['success']) {
                    $this->line("  ✓ {$business->name}");
                    // TODO: Telegram orqali biznes egasiga yuborish
                    $success++;
                }
            } catch (\Exception $e) {
                Log::warning("MorningStandup: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Tayyor: {$success}/{$businesses->count()} biznes.");
        return self::SUCCESS;
    }
}
