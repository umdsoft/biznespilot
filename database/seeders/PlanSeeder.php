<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PlanSeeder - BiznesPilot tariflarini yaratish
 *
 * 5 ta tarif:
 * 1. Free - Bepul (faqat Telegram + CRM, Instagram yo'q, Call Center AI yo'q)
 * 2. Start - Boshlash uchun (299,000 so'm)
 * 3. Standard - Rivojlanish uchun (599,000 so'm)
 * 4. Business - Tizimlashish uchun (799,000 so'm) - ENG FOYDALI
 * 5. Premium - Masshtablash uchun (1,499,000 so'm) - VIP
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // FREE - Bepul tarif (faqat Telegram + CRM, Instagram yo'q, Call Center AI yo'q)
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Bepul boshlang - CRM, lidlar va Telegram chatbot',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'business_limit' => 1,
                'team_member_limit' => 1,
                'lead_limit' => 100,
                'chatbot_channel_limit' => 1,
                'telegram_bot_limit' => 1,     // Telegram chatbot ✅
                'has_instagram' => false,       // Instagram chatbot ❌
                'audio_minutes_limit' => 0,     // Call Center AI ❌
                'ai_requests_limit' => 10,
                'storage_limit_mb' => 100,
                'instagram_dm_limit' => 0,
                'content_posts_limit' => 0,
                'has_amocrm' => false,
                'is_active' => true,
                'features' => [
                    'To\'liq CRM funksiyalari',
                    'Lidlarni boshqarish',
                    'Pipeline va voronka',
                    'Telegram chatbot (1 ta)',
                    '100 ta lid/oy',
                    'Asosiy hisobotlar',
                ],
            ],

            // START - 299,000 so'm/oy
            [
                'name' => 'Start',
                'slug' => 'start',
                'description' => 'Boshlash uchun',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'business_limit' => 1,
                'team_member_limit' => 2,
                'lead_limit' => 500,
                'chatbot_channel_limit' => 1,
                'telegram_bot_limit' => 1,
                'has_instagram' => true,        // Instagram chatbot ✅
                'audio_minutes_limit' => 60,    // Call Center AI ✅
                'ai_requests_limit' => 100,
                'storage_limit_mb' => 500,
                'instagram_dm_limit' => 200,
                'content_posts_limit' => 20,
                'has_amocrm' => false,
                'is_active' => true,
                'features' => [
                    '2 ta xodim',
                    '1 ta filial',
                    'Instagram + Telegram chatbot',
                    'Marketing ROI',
                    '500 ta lid/oy',
                    '60 daq Call Center AI',
                ],
            ],

            // STANDARD - 599,000 so'm/oy
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'Rivojlanish uchun',
                'price_monthly' => 599000,
                'price_yearly' => 5990000,
                'business_limit' => 1,
                'team_member_limit' => 5,
                'lead_limit' => 2000,
                'chatbot_channel_limit' => 2,
                'telegram_bot_limit' => 2,
                'has_instagram' => true,
                'audio_minutes_limit' => 150,
                'ai_requests_limit' => 300,
                'storage_limit_mb' => 2048,
                'instagram_dm_limit' => 500,
                'content_posts_limit' => 50,
                'has_amocrm' => false,
                'is_active' => true,
                'features' => [
                    '5 ta xodim',
                    '1 ta filial',
                    'Flow Builder (Vizual)',
                    '2,000 ta lid/oy',
                    '150 daq Call Center AI',
                ],
            ],

            // BUSINESS - 799,000 so'm/oy (ENG FOYDALI)
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Tizimlashish uchun',
                'price_monthly' => 799000,
                'price_yearly' => 7990000,
                'business_limit' => 2,
                'team_member_limit' => 10,
                'lead_limit' => 10000,
                'chatbot_channel_limit' => 5,
                'telegram_bot_limit' => 5,
                'has_instagram' => true,
                'audio_minutes_limit' => 400,
                'ai_requests_limit' => 1000,
                'storage_limit_mb' => 10240,
                'instagram_dm_limit' => 2000,
                'content_posts_limit' => 100,
                'has_amocrm' => true,
                'is_active' => true,
                'features' => [
                    '10 ta xodim',
                    '2 ta filial',
                    'HR Bot + Marketing ROI',
                    '10,000 ta lid/oy',
                    '400 daq Call Center AI',
                ],
            ],

            // PREMIUM - 1,499,000 so'm/oy (VIP)
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Masshtablash uchun',
                'price_monthly' => 1499000,
                'price_yearly' => 14990000,
                'business_limit' => 5,
                'team_member_limit' => 15,
                'lead_limit' => null,           // Cheksiz
                'chatbot_channel_limit' => null, // Cheksiz
                'telegram_bot_limit' => null,   // Cheksiz
                'has_instagram' => true,
                'audio_minutes_limit' => 1000,
                'ai_requests_limit' => null,    // Cheksiz
                'storage_limit_mb' => null,     // Cheksiz
                'instagram_dm_limit' => null,   // Cheksiz
                'content_posts_limit' => null,  // Cheksiz
                'has_amocrm' => true,
                'is_active' => true,
                'features' => [
                    '15 ta xodim',
                    '5 ta filial',
                    'AI Bot + Anti-Fraud',
                    'Cheksiz lid',
                    '1,000 daq Call Center AI',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::where('slug', $planData['slug'])->first();

            if (!$plan) {
                $plan = new Plan;
                $plan->id = Str::uuid()->toString();
            }

            $plan->fill($planData);
            $plan->save();
        }

        $this->command->info('5 ta tarif muvaffaqiyatli yaratildi/yangilandi!');
    }
}
