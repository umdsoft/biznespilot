<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PlanSeeder - BiznesPilot tariflarini yaratish
 *
 * Tariflar JSON formatida limits va features bilan:
 * - limits: raqamli cheklovlar (users, branches, monthly_leads, va h.k.)
 * - features: yoqish/o'chirish mumkin bo'lgan funksiyalar
 *
 * Barcha tariflarda mavjud (cheklovsiz):
 * - Instagram/Facebook integratsiya
 * - Vizual voronka (Flow Builder)
 * - Marketing ROI hisoboti
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // START - 299,000 so'm/oy
            [
                'name' => 'Start',
                'slug' => 'start',
                'description' => 'Kichik bizneslar uchun boshlang\'ich tarif',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'currency' => 'UZS',
                'sort_order' => 1,
                'is_active' => true,
                'limits' => [
                    'users' => 2,
                    'branches' => 1,
                    'instagram_accounts' => 1,
                    'monthly_leads' => 500,
                    'ai_call_minutes' => 60,
                    'extra_call_price' => 500,
                    'chatbot_channels' => 2,
                    'telegram_bots' => 2,
                    'ai_requests' => 500,
                    'storage_mb' => 500,
                ],
                'features' => [
                    'hr_tasks' => false,
                    'hr_bot' => false,
                    'anti_fraud' => false,
                ],
            ],

            // STANDARD - 599,000 so'm/oy
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'O\'sib borayotgan bizneslar uchun standart tarif',
                'price_monthly' => 599000,
                'price_yearly' => 5990000,
                'currency' => 'UZS',
                'sort_order' => 2,
                'is_active' => true,
                'limits' => [
                    'users' => 5,
                    'branches' => 1,
                    'instagram_accounts' => 2,
                    'monthly_leads' => 2000,
                    'ai_call_minutes' => 150,
                    'extra_call_price' => 450,
                    'chatbot_channels' => 3,
                    'telegram_bots' => 3,
                    'ai_requests' => 2000,
                    'storage_mb' => 1000,
                ],
                'features' => [
                    'hr_tasks' => true,
                    'hr_bot' => false,
                    'anti_fraud' => false,
                ],
            ],

            // BUSINESS - 799,000 so'm/oy (ENG FOYDALI)
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'O\'rta va katta bizneslar uchun professional tarif',
                'price_monthly' => 799000,
                'price_yearly' => 7990000,
                'currency' => 'UZS',
                'sort_order' => 3,
                'is_active' => true,
                'limits' => [
                    'users' => 10,
                    'branches' => 2,
                    'instagram_accounts' => 3,
                    'monthly_leads' => 10000,
                    'ai_call_minutes' => 400,
                    'extra_call_price' => 400,
                    'chatbot_channels' => 5,
                    'telegram_bots' => 5,
                    'ai_requests' => 10000,
                    'storage_mb' => 5000,
                ],
                'features' => [
                    'hr_tasks' => true,
                    'hr_bot' => true,
                    'anti_fraud' => false,
                ],
            ],

            // PREMIUM - 1,499,000 so'm/oy (VIP)
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Korporativ mijozlar uchun to\'liq imkoniyatli tarif',
                'price_monthly' => 1499000,
                'price_yearly' => 14990000,
                'currency' => 'UZS',
                'sort_order' => 4,
                'is_active' => true,
                'limits' => [
                    'users' => 15,
                    'branches' => 5,
                    'instagram_accounts' => 10,
                    'monthly_leads' => null, // Cheksiz
                    'ai_call_minutes' => 1000,
                    'extra_call_price' => 300,
                    'chatbot_channels' => 20,
                    'telegram_bots' => 20,
                    'ai_requests' => 50000,
                    'storage_mb' => 50000,
                ],
                'features' => [
                    'hr_tasks' => true,
                    'hr_bot' => true,
                    'anti_fraud' => true,
                ],
            ],

            // ENTERPRISE - 4,999,000 so'm/oy (Maxsus)
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Katta korporatsiyalar uchun maxsus tarif',
                'price_monthly' => 4999000,
                'price_yearly' => 49990000,
                'currency' => 'UZS',
                'sort_order' => 5,
                'is_active' => true,
                'limits' => [
                    'users' => null, // Cheksiz
                    'branches' => null, // Cheksiz
                    'instagram_accounts' => null, // Cheksiz
                    'monthly_leads' => null, // Cheksiz
                    'ai_call_minutes' => 10000,
                    'extra_call_price' => 250,
                    'chatbot_channels' => null, // Cheksiz
                    'telegram_bots' => null, // Cheksiz
                    'ai_requests' => null, // Cheksiz
                    'storage_mb' => null, // Cheksiz
                ],
                'features' => [
                    'hr_tasks' => true,
                    'hr_bot' => true,
                    'anti_fraud' => true,
                ],
            ],
        ];

        foreach ($plans as $planData) {
            // Legacy columns for backward compatibility
            $legacyData = [
                'team_member_limit' => $planData['limits']['users'] ?? null,
                'business_limit' => $planData['limits']['branches'] ?? null,
                'lead_limit' => $planData['limits']['monthly_leads'] ?? null,
                'chatbot_channel_limit' => $planData['limits']['chatbot_channels'] ?? null,
                'telegram_bot_limit' => $planData['limits']['telegram_bots'] ?? null,
                'audio_minutes_limit' => $planData['limits']['ai_call_minutes'] ?? null,
                'ai_requests_limit' => $planData['limits']['ai_requests'] ?? null,
                'storage_limit_mb' => $planData['limits']['storage_mb'] ?? null,
            ];

            $plan = Plan::where('slug', $planData['slug'])->first();

            if (!$plan) {
                $plan = new Plan;
                $plan->id = Str::uuid()->toString();
            }

            $plan->fill(array_merge($planData, $legacyData));
            $plan->save();
        }

        $this->command->info('5 ta tarif muvaffaqiyatli yaratildi/yangilandi!');
    }
}
