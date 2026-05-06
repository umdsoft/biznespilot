<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Agent\Reputation\ReputationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Obro' balini hisoblash — har dushanba 07:00.
 * php artisan agent:reputation-score
 */
class CalculateReputationScore extends Command
{
    protected $signature = 'agent:reputation-score';
    protected $description = 'Barcha bizneslar uchun haftalik obro\' balini hisoblash';

    public function handle(ReputationService $reputationService): int
    {
        $this->info("Obro' ballari hisoblanmoqda...");

        $businesses = Business::where('status', 'active')->lazyById(50);

        foreach ($businesses as $business) {
            try {
                $result = $reputationService->calculateReputationScore($business->id);
                if (isset($result['total_reviews']) && $result['total_reviews'] > 0) {
                    $this->line("  ✓ {$business->name}: {$result['total_reviews']} izoh, kayfiyat {$result['sentiment']}");
                }
            } catch (\Exception $e) {
                Log::warning("Reputation: {$business->id} xato", ['error' => $e->getMessage()]);
            }
        }

        $this->info('Tayyor.');
        return self::SUCCESS;
    }
}
