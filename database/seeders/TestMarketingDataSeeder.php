<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestMarketingDataSeeder extends Seeder
{
    private string $bid = 'aa9b1e35-020b-4ec1-b1ad-ef9f931cfac8';
    private string $uid = 'b090f3d5-173d-480b-8ea5-7140004d64f2';

    public function run(): void
    {
        $this->command->info('Marketing ma\'lumotlari yaratilmoqda...');

        $this->seedOffers();
        $this->seedLeadSources();
        $this->seedContent();
        $this->seedKpiDailyEntries();
        $this->seedTodos();

        $this->command->info('✅ Marketing ma\'lumotlari tayyor!');
    }

    private function seedOffers(): void
    {
        $offers = [
            ['name' => 'Web-sayt yaratish', 'description' => 'Professional veb-sayt 15 kun ichida', 'price' => 5000000, 'type' => 'service'],
            ['name' => 'Mobil ilova ishlab chiqish', 'description' => 'iOS va Android ilova', 'price' => 15000000, 'type' => 'service'],
            ['name' => 'Biznes avtomatlashtirish', 'description' => 'Jarayonlarni avtomatlashtirish', 'price' => 8000000, 'type' => 'service'],
            ['name' => 'Telegram bot', 'description' => 'Maxsus Telegram bot', 'price' => 3000000, 'type' => 'service'],
            ['name' => 'Texnik qo\'llab-quvvatlash', 'description' => 'Oylik texnik xizmat', 'price' => 1500000, 'type' => 'service'],
        ];
        $count = 0;
        foreach ($offers as $o) {
            try {
                DB::table('offers')->updateOrInsert(
                    ['business_id' => $this->bid, 'name' => $o['name']],
                    [
                        'id' => Str::uuid(),
                        'business_id' => $this->bid,
                        'name' => $o['name'],
                        'description' => $o['description'],
                        'price' => $o['price'],
                        'type' => $o['type'],
                        'currency' => 'UZS',
                        'status' => 'active',
                        'is_active' => true,
                        'created_at' => now()->subDays(rand(30, 180)),
                        'updated_at' => now(),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $this->command->warn("  Offer xato: " . Str::limit($e->getMessage(), 80));
            }
        }
        $this->command->info("  ✓ {$count} ta taklif");
    }

    private function seedLeadSources(): void
    {
        $sources = [
            ['name' => 'Instagram', 'code' => 'instagram', 'category' => 'digital', 'is_paid' => false],
            ['name' => 'Telegram', 'code' => 'telegram', 'category' => 'digital', 'is_paid' => false],
            ['name' => 'Veb-sayt', 'code' => 'website', 'category' => 'organic', 'is_paid' => false],
            ['name' => 'Tavsiya', 'code' => 'referral', 'category' => 'referral', 'is_paid' => false],
            ['name' => 'Google Ads', 'code' => 'google_ads', 'category' => 'digital', 'is_paid' => true],
            ['name' => 'Facebook Ads', 'code' => 'facebook_ads', 'category' => 'digital', 'is_paid' => true],
        ];
        $count = 0;
        foreach ($sources as $i => $s) {
            try {
                DB::table('lead_sources')->updateOrInsert(
                    ['business_id' => $this->bid, 'code' => $s['code']],
                    [
                        'business_id' => $this->bid,
                        'code' => $s['code'],
                        'name' => $s['name'],
                        'category' => $s['category'],
                        'is_paid' => $s['is_paid'],
                        'is_active' => true,
                        'sort_order' => $i,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $this->command->warn("  Source xato: " . Str::limit($e->getMessage(), 80));
            }
        }
        $this->command->info("  ✓ {$count} ta lead manba");
    }

    private function seedContent(): void
    {
        $topics = [
            'Web-sayt yaratish afzalliklari', 'Telegram bot nima uchun kerak?',
            'Mobil ilova vs veb-sayt', 'UX dizayn asoslari', 'SEO nima?',
            'E-commerce platformalar', 'Startup uchun MVP', 'AI biznesda',
            'Kiberbezopaslik', 'Cloud xizmatlar', 'IT outsourcing',
            'Avtomatlashtirish', 'Marketing trendlari 2026', 'SMM strategiya',
            'Kontentda storytelling', 'Brending asoslari', 'Email marketing',
        ];
        $types = ['post', 'story', 'reel', 'ad', 'carousel', 'article'];
        $channels = ['instagram', 'telegram', 'facebook', 'blog'];
        $purposes = ['educate', 'inspire', 'sell', 'engage', 'announce', 'entertain'];
        $count = 0;

        for ($day = 360; $day >= 0; $day -= rand(1, 3)) {
            $date = now()->subDays($day);
            try {
                DB::table('content_generations')->insert([
                    'id' => Str::uuid(),
                    'business_id' => $this->bid,
                    'user_id' => $this->uid,
                    'topic' => $topics[array_rand($topics)],
                    'content_type' => $types[array_rand($types)],
                    'target_channel' => $channels[array_rand($channels)],
                    'purpose' => $purposes[array_rand($purposes)],
                    'generated_content' => 'Bu test kontent — ' . $topics[array_rand($topics)] . '. O\'z biznesingizni rivojlantiring!',
                    'generated_hashtags' => json_encode(['#ITxizmatlar', '#webdevelopment', '#biznespilot', '#startup', '#uzbekistan']),
                    'status' => rand(0, 10) > 1 ? 'completed' : 'pending',
                    'ai_model' => 'haiku',
                    'input_tokens' => rand(100, 400),
                    'output_tokens' => rand(200, 800),
                    'total_tokens' => rand(300, 1200),
                    'cost_usd' => rand(1, 50) / 10000,
                    'was_published' => rand(0, 10) > 3,
                    'post_engagement_rate' => rand(0, 10) > 5 ? rand(1, 15) / 10 : null,
                    'post_likes' => rand(0, 10) > 4 ? rand(10, 500) : null,
                    'post_comments' => rand(0, 10) > 6 ? rand(1, 50) : null,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
                $count++;
            } catch (\Exception $e) {
                if ($count === 0) $this->command->warn("  Content xato: " . Str::limit($e->getMessage(), 100));
            }
        }
        $this->command->info("  ✓ {$count} ta kontent");
    }

    private function seedKpiDailyEntries(): void
    {
        $count = 0;
        for ($day = 365; $day >= 0; $day--) {
            $date = now()->subDays($day);
            if ($date->isWeekend() && rand(0, 2) > 0) continue;

            try {
                $leadsDigital = rand(0, 3);
                $leadsOffline = rand(0, 1);
                $leadsReferral = rand(0, 2);
                $leadsTotal = $leadsDigital + $leadsOffline + $leadsReferral;
                $salesNew = rand(0, 2);
                $salesRepeat = rand(0, 1);
                $salesTotal = $salesNew + $salesRepeat;
                $spendDigital = rand(50, 500) * 1000;
                $spendOffline = rand(0, 100) * 1000;
                $spendTotal = $spendDigital + $spendOffline;
                $revenueTotal = rand(0, 15) * 1000000;
                $avgCheck = $salesTotal > 0 ? intval($revenueTotal / max($salesTotal, 1)) : rand(3, 12) * 1000000;

                DB::table('kpi_daily_entries')->updateOrInsert(
                    ['business_id' => $this->bid, 'date' => $date->format('Y-m-d')],
                    [
                        'business_id' => $this->bid,
                        'date' => $date->format('Y-m-d'),
                        'leads_digital' => $leadsDigital,
                        'leads_offline' => $leadsOffline,
                        'leads_referral' => $leadsReferral,
                        'leads_total' => $leadsTotal,
                        'spend_digital' => $spendDigital,
                        'spend_offline' => $spendOffline,
                        'spend_total' => $spendTotal,
                        'sales_new' => $salesNew,
                        'sales_repeat' => $salesRepeat,
                        'sales_total' => $salesTotal,
                        'revenue_total' => $revenueTotal,
                        'avg_check' => $avgCheck,
                        'conversion_rate' => $leadsTotal > 0 ? round($salesTotal / $leadsTotal * 100, 1) : 0,
                        'is_complete' => true,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                if ($count === 0) $this->command->warn("  KPI xato: " . Str::limit($e->getMessage(), 100));
            }
        }
        $this->command->info("  ✓ {$count} kunlik KPI");
    }

    private function seedTodos(): void
    {
        $todos = [
            ['title' => 'Instagram kontent rejasini tuzish', 'priority' => 'high', 'status' => 'completed'],
            ['title' => 'Yangi mijozga taklifnoma yuborish', 'priority' => 'high', 'status' => 'completed'],
            ['title' => 'Telegram kanal kontentini tayyorlash', 'priority' => 'medium', 'status' => 'completed'],
            ['title' => 'Haftalik marketing hisoboti', 'priority' => 'medium', 'status' => 'completed'],
            ['title' => 'Raqobatchilarni kuzatish', 'priority' => 'medium', 'status' => 'completed'],
            ['title' => 'SEO audit o\'tkazish', 'priority' => 'high', 'status' => 'completed'],
            ['title' => 'Yangi landing page dizayni', 'priority' => 'high', 'status' => 'in_progress'],
            ['title' => 'Email marketing kampaniya', 'priority' => 'medium', 'status' => 'in_progress'],
            ['title' => 'Video kontent tayyorlash', 'priority' => 'low', 'status' => 'in_progress'],
            ['title' => 'Oylik KPI tahlili', 'priority' => 'high', 'status' => 'pending'],
            ['title' => 'Yangi xodim uchun suhbat', 'priority' => 'medium', 'status' => 'pending'],
            ['title' => 'Marketing byudjet rejasi', 'priority' => 'high', 'status' => 'pending'],
            ['title' => 'Mijozlar so\'rovnomasi tahlili', 'priority' => 'low', 'status' => 'pending'],
            ['title' => 'Jamoaviy yig\'ilish', 'priority' => 'medium', 'status' => 'pending'],
            ['title' => 'Instagram reels strategiyasi', 'priority' => 'medium', 'status' => 'pending'],
        ];

        $count = 0;
        foreach ($todos as $todo) {
            try {
                DB::table('todos')->insert([
                    'id' => Str::uuid(),
                    'business_id' => $this->bid,
                    'created_by' => $this->uid,
                    'title' => $todo['title'],
                    'status' => $todo['status'],
                    'priority' => $todo['priority'],
                    'due_date' => $todo['status'] === 'completed' ? now()->subDays(rand(1, 30)) : now()->addDays(rand(1, 14)),
                    'completed_at' => $todo['status'] === 'completed' ? now()->subDays(rand(1, 15)) : null,
                    'created_at' => now()->subDays(rand(5, 60)),
                    'updated_at' => now(),
                ]);
                $count++;
            } catch (\Exception $e) {
                if ($count === 0) $this->command->warn("  Todo xato: " . Str::limit($e->getMessage(), 100));
            }
        }
        $this->command->info("  ✓ {$count} ta vazifa");
    }
}
