<?php

namespace App\Console\Commands\Partner;

use App\Models\Partner\Partner;
use App\Models\Partner\PartnerCommission;
use App\Models\Partner\PartnerTierRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Partner tier avtomatik progression — kunlik cron.
 *
 * Har bir aktiv partner uchun:
 *  - aktiv referrals soni
 *  - oxirgi 30 kunlik volume (gross_amount partner referrals'idan)
 * hisoblab, ularga mos yangi tierga ko'taradi (yoki tushuradi agar tier shartlari
 * endi bajarilmasa — lekin real biznesda DOWNGRADE qilmaslik tavsiya etiladi).
 *
 * Schedule: kunlik 03:00
 */
class SyncTiersCommand extends Command
{
    protected $signature = 'partner:sync-tiers {--downgrade : allow tier downgrade}';

    protected $description = 'Sync partner tiers based on active referrals + monthly volume';

    public function handle(): int
    {
        $this->info('Syncing partner tiers...');

        $tierRules = PartnerTierRule::where('is_active', true)
            ->orderBy('sort_order', 'desc') // eng yuqori tierdan pastga
            ->get();

        if ($tierRules->isEmpty()) {
            $this->warn('No active tier rules found.');
            return self::FAILURE;
        }

        $allowDowngrade = (bool) $this->option('downgrade');
        $changed = 0;
        $since = now()->subDays(30);

        Partner::where('status', Partner::STATUS_ACTIVE)
            ->chunkById(100, function ($partners) use ($tierRules, $allowDowngrade, $since, &$changed) {
                foreach ($partners as $partner) {
                    // 30 kunlik volume (faqat attributed/active referralsdan)
                    $monthlyVolume = (float) PartnerCommission::where('partner_id', $partner->id)
                        ->where('created_at', '>=', $since)
                        ->whereNotIn('status', [
                            PartnerCommission::STATUS_REVERSED,
                            PartnerCommission::STATUS_CLAWBACK,
                        ])
                        ->sum('gross_amount');

                    $activeRefs = (int) $partner->active_referrals_count_cached;

                    // Eng yuqori mos tier
                    $newTier = null;
                    foreach ($tierRules as $rule) {
                        if (
                            $activeRefs >= $rule->min_active_referrals &&
                            $monthlyVolume >= (float) $rule->min_monthly_volume_uzs
                        ) {
                            $newTier = $rule->tier;
                            break;
                        }
                    }

                    if (! $newTier || $newTier === $partner->tier) {
                        continue;
                    }

                    // Downgrade himoyasi — oddiy holatda faqat UPGRADE
                    $currentRule = $tierRules->firstWhere('tier', $partner->tier);
                    $newRule = $tierRules->firstWhere('tier', $newTier);
                    $isUpgrade = $newRule && $currentRule && $newRule->sort_order > $currentRule->sort_order;

                    if (! $isUpgrade && ! $allowDowngrade) {
                        continue;
                    }

                    $partner->tier = $newTier;
                    $partner->saveQuietly();
                    $changed++;

                    $this->line("  → {$partner->code}: {$partner->tier} (refs: {$activeRefs}, volume: " . number_format($monthlyVolume, 0, '', ' ') . ")");
                }
            });

        $this->info("✓ {$changed} partner tier updated");

        return self::SUCCESS;
    }
}
