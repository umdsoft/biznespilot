<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = \App\Models\Business::all();

        if ($businesses->isEmpty()) {
            $this->command->warn('No businesses found. Please run BusinessSeeder first.');
            return;
        }

        foreach ($businesses as $business) {
            // Campaign 1: Welcome Series (Drip)
            \App\Models\Campaign::create([
                'business_id' => $business->id,
                'name' => 'Yangi Mijozlar Uchun Xush Kelibsiz Seriyasi',
                'type' => 'drip',
                'channel' => 'whatsapp',
                'message_template' => "Assalomu alaykum {customer_name}! 🎉\n\n{business_name}ga xush kelibsiz! Biz siz bilan ishlashdan xursandmiz.\n\nBirinchi buyurtmangizda 15% chegirma olish uchun YANGI15 promokodidan foydalaning!",
                'target_audience' => json_encode(['type' => 'recent', 'days' => 7]),
                'schedule_type' => 'immediate',
                'status' => 'completed',
                'sent_count' => 45,
                'failed_count' => 2,
                'delivered_count' => 43,
                'opened_count' => 38,
                'clicked_count' => 12,
                'created_at' => now()->subDays(5),
            ]);

            // Campaign 2: Flash Sale (Broadcast)
            \App\Models\Campaign::create([
                'business_id' => $business->id,
                'name' => 'Flash Sale - 24 Soatlik Chegirma',
                'type' => 'broadcast',
                'channel' => 'all',
                'message_template' => "⚡ FLASH SALE! ⚡\n\nFaqat 24 soat davomida - {offer_name} uchun 40% CHEGIRMA!\n\nOdatdagi narx: {offer_price}\nBugun: " . number_format(1000 * 0.6, 0) . " so'm\n\nShoshiling! Taklif cheklangan! 🔥",
                'target_audience' => json_encode(['type' => 'active', 'days' => 30]),
                'schedule_type' => 'scheduled',
                'scheduled_at' => now()->addDays(2),
                'status' => 'scheduled',
                'sent_count' => 0,
                'failed_count' => 0,
                'created_at' => now()->subDays(3),
            ]);

            // Campaign 3: Re-engagement (Trigger)
            \App\Models\Campaign::create([
                'business_id' => $business->id,
                'name' => 'Qaytib Kelish Taklifnomasi',
                'type' => 'trigger',
                'channel' => 'instagram',
                'message_template' => "Salom {customer_name}! 👋\n\nSizni sog'indik! 💙\n\nQaytib kelganingiz uchun maxsus sovg'a - keyingi xaridingizda BEPUL yetkazib berish! 🎁\n\nKod: QAYTDIM",
                'target_audience' => json_encode(['type' => 'inactive', 'days' => 60]),
                'schedule_type' => 'immediate',
                'status' => 'running',
                'sent_count' => 28,
                'failed_count' => 1,
                'delivered_count' => 27,
                'opened_count' => 19,
                'clicked_count' => 8,
                'created_at' => now()->subDays(1),
            ]);

            // Campaign 4: Product Launch
            \App\Models\Campaign::create([
                'business_id' => $business->id,
                'name' => 'Yangi Mahsulot E\'loni',
                'type' => 'broadcast',
                'channel' => 'whatsapp',
                'message_template' => "🎊 YANGI MAHSULOT! 🎊\n\nDiqqat, {customer_name}!\n\n{business_name} yangi mahsulotni taqdim etadi!\n\nIlk 50 ta buyurtmachiga 25% chegirma va bepul sovg'a! 🎁\n\nBatafsil: [link]",
                'target_audience' => json_encode(['type' => 'all']),
                'schedule_type' => 'immediate',
                'status' => 'draft',
                'sent_count' => 0,
                'failed_count' => 0,
                'created_at' => now()->subHours(12),
            ]);

            // Campaign 5: Birthday Campaign
            \App\Models\Campaign::create([
                'business_id' => $business->id,
                'name' => 'Tug\'ilgan Kun Tabriknomasi',
                'type' => 'trigger',
                'channel' => 'all',
                'message_template' => "🎂 Tug'ilgan kuningiz muborak, {customer_name}! 🎉\n\n{business_name} jamoasi sizni chin dildan tabriklaydi!\n\nMaxsus sovg'a: 30% chegirma va bepul desert! 🍰\n\nKod: BIRTHDAY30",
                'target_audience' => json_encode(['type' => 'birthday']),
                'schedule_type' => 'recurring',
                'status' => 'running',
                'sent_count' => 12,
                'failed_count' => 0,
                'delivered_count' => 12,
                'opened_count' => 11,
                'clicked_count' => 9,
                'created_at' => now()->subDays(10),
            ]);

            $this->command->info("Created 5 demo campaigns for business: {$business->name}");
        }
    }
}
