<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StrategyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Annual Templates
            [
                'name' => 'Standart Yillik Strategiya',
                'type' => 'annual',
                'description' => 'Har qanday biznes uchun mos keladigan yillik strategiya shabloni',
                'template_data' => json_encode([
                    'is_default' => true,
                    'icon' => 'calendar',
                    'color' => 'indigo',
                    'goals_template' => [
                        ['name' => 'Yillik daromad', 'metric' => 'revenue', 'growth_percent' => 30],
                        ['name' => 'Yangi mijozlar', 'metric' => 'customers', 'growth_percent' => 25],
                        ['name' => 'Brend tanilishi', 'metric' => 'brand_awareness', 'growth_percent' => 40],
                    ],
                    'kpis_template' => [
                        ['key' => 'revenue', 'name' => 'Daromad', 'category' => 'revenue'],
                        ['key' => 'leads', 'name' => 'Lidlar', 'category' => 'marketing'],
                        ['key' => 'customers', 'name' => 'Mijozlar', 'category' => 'sales'],
                        ['key' => 'retention_rate', 'name' => 'Saqlash darajasi', 'category' => 'customer'],
                    ],
                    'budget_template' => [
                        ['category' => 'marketing', 'percent' => 40],
                        ['category' => 'advertising', 'percent' => 30],
                        ['category' => 'content', 'percent' => 15],
                        ['category' => 'tools', 'percent' => 10],
                        ['category' => 'other', 'percent' => 5],
                    ],
                    'channels_template' => [
                        'instagram' => 35,
                        'telegram' => 30,
                        'facebook' => 20,
                        'google' => 15,
                    ],
                ]),
            ],
            [
                'name' => 'E-commerce Yillik Strategiya',
                'type' => 'annual',
                'description' => 'Onlayn do\'konlar uchun maxsus yillik strategiya',
                'template_data' => json_encode([
                    'industry' => 'e-commerce',
                    'icon' => 'shopping-cart',
                    'color' => 'green',
                    'goals_template' => [
                        ['name' => 'Onlayn savdo', 'metric' => 'revenue', 'growth_percent' => 50],
                        ['name' => 'Buyurtmalar soni', 'metric' => 'orders', 'growth_percent' => 40],
                        ['name' => 'Qaytib keluvchi mijozlar', 'metric' => 'repeat_customers', 'growth_percent' => 30],
                    ],
                    'kpis_template' => [
                        ['key' => 'revenue', 'name' => 'Savdo', 'category' => 'revenue'],
                        ['key' => 'aov', 'name' => 'O\'rtacha buyurtma', 'category' => 'sales'],
                        ['key' => 'conversion_rate', 'name' => 'Konversiya', 'category' => 'sales'],
                        ['key' => 'cart_abandonment', 'name' => 'Savatcha tashlab ketish', 'category' => 'sales'],
                    ],
                ]),
            ],
            // Quarterly Templates
            [
                'name' => 'Standart Choraklik Reja',
                'type' => 'quarterly',
                'description' => 'Har chorak uchun asosiy maqsadlar va harakatlar',
                'template_data' => json_encode([
                    'is_default' => true,
                    'icon' => 'chart-bar',
                    'color' => 'blue',
                    'goals_template' => [
                        ['name' => 'Choraklik daromad', 'metric' => 'revenue'],
                        ['name' => 'Yangi lidlar', 'metric' => 'leads'],
                        ['name' => 'Kontent chiqarish', 'metric' => 'content_pieces'],
                    ],
                    'activities_template' => [
                        ['week' => 1, 'focus' => 'Planning va tayyorgarlik'],
                        ['week' => 2, 'focus' => 'Kampaniya boshlash'],
                        ['week' => 3, 'focus' => 'Optimizatsiya'],
                        ['week' => 4, 'focus' => 'Baholash va tahlil'],
                    ],
                ]),
            ],
            [
                'name' => 'O\'sish Chorakligi',
                'type' => 'quarterly',
                'description' => 'Tez o\'sishga yo\'naltirilgan intensiv chorak',
                'template_data' => json_encode([
                    'icon' => 'trending-up',
                    'color' => 'emerald',
                    'goals_template' => [
                        ['name' => 'Aggressiv o\'sish', 'metric' => 'revenue', 'growth_percent' => 25],
                        ['name' => 'Yangi kanallar', 'metric' => 'new_channels', 'target' => 2],
                        ['name' => 'A/B testlar', 'metric' => 'experiments', 'target' => 10],
                    ],
                ]),
            ],
            // Monthly Templates
            [
                'name' => 'Standart Oylik Reja',
                'type' => 'monthly',
                'description' => 'Har oy uchun haftalik taqsimot bilan',
                'template_data' => json_encode([
                    'is_default' => true,
                    'icon' => 'calendar-days',
                    'color' => 'violet',
                    'goals_template' => [
                        ['name' => 'Oylik daromad', 'metric' => 'revenue'],
                        ['name' => 'Yangi lidlar', 'metric' => 'leads'],
                        ['name' => 'Kontent', 'metric' => 'posts'],
                    ],
                    'content_template' => [
                        'posts_per_week' => 5,
                        'stories_per_day' => 3,
                        'reels_per_week' => 2,
                        'articles_per_month' => 2,
                    ],
                ]),
            ],
            [
                'name' => 'Kampaniya Oyi',
                'type' => 'monthly',
                'description' => 'Maxsus aktsiya yoki kampaniya uchun oy',
                'template_data' => json_encode([
                    'icon' => 'megaphone',
                    'color' => 'orange',
                    'goals_template' => [
                        ['name' => 'Kampaniya savdosi', 'metric' => 'campaign_revenue'],
                        ['name' => 'Kampaniya lidlari', 'metric' => 'campaign_leads'],
                        ['name' => 'ROI', 'metric' => 'roi'],
                    ],
                    'activities_template' => [
                        ['phase' => 'Teaser', 'week' => 1],
                        ['phase' => 'Launch', 'week' => 2],
                        ['phase' => 'Push', 'week' => 3],
                        ['phase' => 'Close', 'week' => 4],
                    ],
                ]),
            ],
            // Weekly Templates
            [
                'name' => 'Standart Haftalik Reja',
                'type' => 'weekly',
                'description' => 'Har hafta uchun kunlik taqsimot',
                'template_data' => json_encode([
                    'is_default' => true,
                    'icon' => 'clock',
                    'color' => 'cyan',
                    'content_template' => [
                        'monday' => ['type' => 'post', 'theme' => 'Motivational'],
                        'tuesday' => ['type' => 'story', 'theme' => 'Behind the scenes'],
                        'wednesday' => ['type' => 'reel', 'theme' => 'Educational'],
                        'thursday' => ['type' => 'post', 'theme' => 'Product showcase'],
                        'friday' => ['type' => 'post', 'theme' => 'Customer story'],
                        'saturday' => ['type' => 'story', 'theme' => 'Weekend special'],
                        'sunday' => ['type' => 'rest', 'theme' => 'Planning'],
                    ],
                ]),
            ],
            // Content Templates
            [
                'name' => 'Instagram Kontent Kalendar',
                'type' => 'content',
                'description' => 'Instagram uchun optimal kontent rejasi',
                'template_data' => json_encode([
                    'is_default' => true,
                    'icon' => 'photo',
                    'color' => 'pink',
                    'content_template' => [
                        'feed_posts' => ['frequency' => 'daily', 'best_times' => ['09:00', '18:00', '21:00']],
                        'stories' => ['frequency' => '3-5/day', 'types' => ['polls', 'questions', 'behind_scenes']],
                        'reels' => ['frequency' => '3-4/week', 'duration' => '15-30 sec'],
                        'carousels' => ['frequency' => '2-3/week', 'slides' => '5-10'],
                    ],
                    'channels_template' => ['instagram' => 100],
                ]),
            ],
            [
                'name' => 'Telegram Kontent Kalendar',
                'type' => 'content',
                'description' => 'Telegram kanal uchun kontent rejasi',
                'template_data' => json_encode([
                    'icon' => 'chat',
                    'color' => 'sky',
                    'content_template' => [
                        'posts' => ['frequency' => '2-3/day', 'best_times' => ['08:00', '13:00', '19:00']],
                        'polls' => ['frequency' => '2-3/week'],
                        'voice' => ['frequency' => '1-2/week'],
                        'video' => ['frequency' => '1/week'],
                    ],
                    'channels_template' => ['telegram' => 100],
                ]),
            ],
            [
                'name' => 'Multi-kanal Kontent',
                'type' => 'content',
                'description' => 'Bir nechta kanal uchun sinxronlashtirilgan kontent',
                'template_data' => json_encode([
                    'icon' => 'squares-2x2',
                    'color' => 'purple',
                    'content_template' => [
                        'hero_content' => ['frequency' => 'weekly', 'repurpose_to' => ['instagram', 'telegram', 'facebook']],
                        'daily_stories' => ['platforms' => ['instagram', 'facebook']],
                        'long_form' => ['platforms' => ['telegram', 'blog'], 'frequency' => 'weekly'],
                    ],
                    'channels_template' => [
                        'instagram' => 40,
                        'telegram' => 35,
                        'facebook' => 25,
                    ],
                ]),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('strategy_templates')->insert([
                'id' => Str::uuid()->toString(),
                'industry_id' => null,
                'name' => $template['name'],
                'type' => $template['type'],
                'description' => $template['description'],
                'template_data' => $template['template_data'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
