<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Team\AgentPersonality;
use App\Services\Team\EmergencyDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Soatlik monitoring — har 2 soatda (08:00-22:00).
 * php artisan team:hourly-check
 */
class TeamHourlyCheck extends Command
{
    protected $signature = 'team:hourly-check';
    protected $description = 'Barcha bizneslar uchun favqulodda hodisalarni tekshirish';

    public function handle(EmergencyDetector $detector): int
    {
        $businesses = Business::where('status', 'active')->lazyById(50);
        $totalAlerts = 0;

        foreach ($businesses as $business) {
            try {
                $alerts = $detector->check($business->id);
                $totalAlerts += count($alerts);

                foreach ($alerts as $alert) {
                    $severity = $alert['severity'] ?? 'info';
                    $emoji = match ($severity) { 'urgent' => '🔴', 'warning' => '🟡', default => '🔵' };
                    $agentInfo = AgentPersonality::get($alert['detecting_agent'] ?? 'umidbek');
                    $agentLabel = "{$agentInfo['name']} ({$agentInfo['role']})";
                    $this->line("  {$emoji} {$business->name} — {$agentLabel}: {$alert['message']}");
                    // TODO: Telegram orqali egasiga yuborish (urgent bo'lsa darhol)
                }
            } catch (\Exception $e) {
                Log::warning("HourlyCheck: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        if ($totalAlerts > 0) {
            $this->warn("{$totalAlerts} ta ogohlantirish aniqlandi.");
        } else {
            $this->info('Hamma narsa normal.');
        }

        return self::SUCCESS;
    }
}
