<?php

namespace App\Console\Commands\Partner;

use App\Services\Partner\PartnerCommissionService;
use Illuminate\Console\Command;

/**
 * Partner commission lifecycle — kunlik cron.
 *
 * 30 kun oldin billing to'lovi bo'lgan va hali refund qilinmagan commission'lar
 * `pending` → `available` holatiga o'tkaziladi. Shundan so'ng partner payout
 * so'rashi mumkin.
 *
 * Schedule: kunlik 02:30
 */
class PromoteCommissionsCommand extends Command
{
    protected $signature = 'partner:promote-commissions';

    protected $description = 'Promote pending partner commissions to available after clawback window';

    public function handle(PartnerCommissionService $service): int
    {
        $this->info('Promoting matured commissions...');

        $promoted = $service->promoteMaturedCommissions();

        $this->info("✓ {$promoted} commission promoted pending → available");

        return self::SUCCESS;
    }
}
