<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\HealthMonitor\BusinessHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Biznes sog'ligini hisoblash — har dushanba 08:00.
 * php artisan agent:health-check
 */
class CalculateBusinessHealth extends Command
{
    protected $signature = 'agent:health-check';
    protected $description = 'Barcha bizneslar uchun haftalik sog\'lik balini hisoblash';

    public function handle(BusinessHealthService $healthService): int
    {
        $this->info("Biznes sog'ligi hisoblanmoqda...");

        $businesses = Business::all();
        $success = 0;

        foreach ($businesses as $business) {
            try {
                $result = $healthService->calculate($business->id);
                if ($result['success']) {
                    $grade = $result['grade'] ?? '?';
                    $score = $result['overall_score'] ?? 0;
                    $this->line("  ✓ {$business->name}: {$score}/100 ({$grade})");
                    $success++;
                }
            } catch (\Exception $e) {
                Log::warning("HealthCheck: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Tayyor: {$success}/{$businesses->count()} biznes hisoblandi.");
        return self::SUCCESS;
    }
}
