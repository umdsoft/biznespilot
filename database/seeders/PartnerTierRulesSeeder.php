<?php

namespace Database\Seeders;

use App\Models\Partner\PartnerTierRule;
use Illuminate\Database\Seeder;

class PartnerTierRulesSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'tier' => 'bronze',
                'name' => 'Bronze',
                'icon' => '🥉',
                'year_one_rate' => 0.10,  // 10%
                'lifetime_rate' => 0.05,  // 5%
                'min_active_referrals' => 0,
                'min_monthly_volume_uzs' => 0,
                'perks' => [
                    'Marketing pack (logo, banner, video)',
                    'Self-service dashboard',
                    'Email qo\'llab-quvvatlash',
                ],
                'sort_order' => 1,
            ],
            [
                'tier' => 'silver',
                'name' => 'Silver',
                'icon' => '🥈',
                'year_one_rate' => 0.12,
                'lifetime_rate' => 0.06,
                'min_active_referrals' => 5,
                'min_monthly_volume_uzs' => 0,
                'perks' => [
                    'Bronze perklar +',
                    'Priority email/Telegram support',
                    'Oylik hisobot webinar',
                ],
                'sort_order' => 2,
            ],
            [
                'tier' => 'gold',
                'name' => 'Gold',
                'icon' => '🥇',
                'year_one_rate' => 0.15,
                'lifetime_rate' => 0.07,
                'min_active_referrals' => 20,
                'min_monthly_volume_uzs' => 3000000,
                'perks' => [
                    'Silver perklar +',
                    'Shaxsiy partner manejer',
                    'Logoтipingiz partner sahifada',
                    'Co-branded materials',
                ],
                'sort_order' => 3,
            ],
            [
                'tier' => 'platinum',
                'name' => 'Platinum',
                'icon' => '💎',
                'year_one_rate' => 0.20,
                'lifetime_rate' => 0.10,
                'min_active_referrals' => 50,
                'min_monthly_volume_uzs' => 10000000,
                'perks' => [
                    'Gold perklar +',
                    'Payout advance (so\'rovsiz avans)',
                    'Co-branded events',
                    'Yillik champion mukofoti imkoniyati',
                    'Custom shartnoma imkoniyati',
                ],
                'sort_order' => 4,
            ],
        ];

        foreach ($tiers as $t) {
            PartnerTierRule::updateOrCreate(['tier' => $t['tier']], $t);
        }
    }
}
