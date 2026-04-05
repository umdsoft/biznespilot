<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\SeasonalPlanner\SeasonalPlannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Kelayotgan bayramlarni tekshirish — har yakshanba 10:00.
 * php artisan agent:check-seasons
 */
class CheckUpcomingSeasons extends Command
{
    protected $signature = 'agent:check-seasons';
    protected $description = 'Keyingi 30 kun ichidagi bayramlar va mavsumiy voqealarni tekshirish';

    public function handle(SeasonalPlannerService $planner): int
    {
        $this->info('Mavsumiy voqealar tekshirilmoqda...');

        $businesses = Business::all();
        $totalEvents = 0;

        foreach ($businesses as $business) {
            try {
                $result = $planner->checkUpcomingEvents($business->id);
                $events = $result['events'] ?? [];

                foreach ($events as $event) {
                    if ($event['needs_preparation']) {
                        $plan = $planner->generateCampaignPlan($business->id, $event);
                        if ($plan['success']) {
                            $this->line("  🎉 {$business->name}: {$event['name']} ({$event['days_until']} kun) — reja tayyor");
                            $totalEvents++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Seasons: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Tayyor: {$totalEvents} ta bayram uchun reja tayyorlandi.");
        return self::SUCCESS;
    }
}
