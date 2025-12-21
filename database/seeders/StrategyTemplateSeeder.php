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
                'slug' => 'standard-annual',
                'description' => 'Har qanday biznes uchun mos keladigan yillik strategiya shabloni',
                'type' => 'annual',
                'is_default' => true,
                'goals_template' => json_encode([
                    ['name' => 'Yillik daromad', 'metric' => 'revenue', 'growth_percent' => 30],
                    ['name' => 'Yangi mijozlar', 'metric' => 'customers', 'growth_percent' => 25],
                    ['name' => 'Brend tanilishi', 'metric' => 'brand_awareness', 'growth_percent' => 40],
                ]),
                'kpis_template' => json_encode([
                    ['key' => 'revenue', 'name' => 'Daromad', 'category' => 'revenue'],
                    ['key' => 'leads', 'name' => 'Lidlar', 'category' => 'marketing'],
                    ['key' => 'customers', 'name' => 'Mijozlar', 'category' => 'sales'],
                    ['key' => 'retention_rate', 'name' => 'Saqlash darajasi', 'category' => 'customer'],
                ]),
                'budget_template' => json_encode([
                    ['category' => 'marketing', 'percent' => 40],
                    ['category' => 'advertising', 'percent' => 30],
                    ['category' => 'content', 'percent' => 15],
                    ['category' => 'tools', 'percent' => 10],
                    ['category' => 'other', 'percent' => 5],
                ]),
                'channels_template' => json_encode([
                    'instagram' => 35, 'telegram' => 30, 'facebook' => 20, 'google' => 15
                ]),
                'icon' => 'calendar',
                'color' => 'indigo',
            ],
            [
                'name' => 'E-commerce Yillik Strategiya',
                'slug' => 'ecommerce-annual',
                'description' => 'Onlayn do\'konlar uchun maxsus yillik strategiya',
                'type' => 'annual',
                'industry' => 'e-commerce',
                'goals_template' => json_encode([
                    ['name' => 'Onlayn savdo', 'metric' => 'revenue', 'growth_percent' => 50],
                    ['name' => 'Buyurtmalar soni', 'metric' => 'orders', 'growth_percent' => 40],
                    ['name' => 'Qaytib keluvchi mijozlar', 'metric' => 'repeat_customers', 'growth_percent' => 30],
                ]),
                'kpis_template' => json_encode([
                    ['key' => 'revenue', 'name' => 'Savdo', 'category' => 'revenue'],
                    ['key' => 'aov', 'name' => 'O\'rtacha buyurtma', 'category' => 'sales'],
                    ['key' => 'conversion_rate', 'name' => 'Konversiya', 'category' => 'sales'],
                    ['key' => 'cart_abandonment', 'name' => 'Savatcha tashlab ketish', 'category' => 'sales'],
                ]),
                'icon' => 'shopping-cart',
                'color' => 'green',
            ],
            // Quarterly Templates
            [
                'name' => 'Standart Choraklik Reja',
                'slug' => 'standard-quarterly',
                'description' => 'Har chorak uchun asosiy maqsadlar va harakatlar',
                'type' => 'quarterly',
                'is_default' => true,
                'goals_template' => json_encode([
                    ['name' => 'Choraklik daromad', 'metric' => 'revenue'],
                    ['name' => 'Yangi lidlar', 'metric' => 'leads'],
                    ['name' => 'Kontent chiqarish', 'metric' => 'content_pieces'],
                ]),
                'activities_template' => json_encode([
                    ['week' => 1, 'focus' => 'Planning va tayyorgarlik'],
                    ['week' => 2, 'focus' => 'Kampaniya boshlash'],
                    ['week' => 3, 'focus' => 'Optimizatsiya'],
                    ['week' => 4, 'focus' => 'Baholash va tahlil'],
                ]),
                'icon' => 'chart-bar',
                'color' => 'blue',
            ],
            [
                'name' => 'O\'sish Chorakligi',
                'slug' => 'growth-quarterly',
                'description' => 'Tez o\'sishga yo\'naltirilgan intensiv chorak',
                'type' => 'quarterly',
                'goals_template' => json_encode([
                    ['name' => 'Aggressiv o\'sish', 'metric' => 'revenue', 'growth_percent' => 25],
                    ['name' => 'Yangi kanallar', 'metric' => 'new_channels', 'target' => 2],
                    ['name' => 'A/B testlar', 'metric' => 'experiments', 'target' => 10],
                ]),
                'icon' => 'trending-up',
                'color' => 'emerald',
            ],
            // Monthly Templates
            [
                'name' => 'Standart Oylik Reja',
                'slug' => 'standard-monthly',
                'description' => 'Har oy uchun haftalik taqsimot bilan',
                'type' => 'monthly',
                'is_default' => true,
                'goals_template' => json_encode([
                    ['name' => 'Oylik daromad', 'metric' => 'revenue'],
                    ['name' => 'Yangi lidlar', 'metric' => 'leads'],
                    ['name' => 'Kontent', 'metric' => 'posts'],
                ]),
                'content_template' => json_encode([
                    'posts_per_week' => 5,
                    'stories_per_day' => 3,
                    'reels_per_week' => 2,
                    'articles_per_month' => 2,
                ]),
                'icon' => 'calendar-days',
                'color' => 'violet',
            ],
            [
                'name' => 'Kampaniya Oyi',
                'slug' => 'campaign-monthly',
                'description' => 'Maxsus aktsiya yoki kampaniya uchun oy',
                'type' => 'monthly',
                'goals_template' => json_encode([
                    ['name' => 'Kampaniya savdosi', 'metric' => 'campaign_revenue'],
                    ['name' => 'Kampaniya lidlari', 'metric' => 'campaign_leads'],
                    ['name' => 'ROI', 'metric' => 'roi'],
                ]),
                'activities_template' => json_encode([
                    ['phase' => 'Teaser', 'week' => 1],
                    ['phase' => 'Launch', 'week' => 2],
                    ['phase' => 'Push', 'week' => 3],
                    ['phase' => 'Close', 'week' => 4],
                ]),
                'icon' => 'megaphone',
                'color' => 'orange',
            ],
            // Weekly Templates
            [
                'name' => 'Standart Haftalik Reja',
                'slug' => 'standard-weekly',
                'description' => 'Har hafta uchun kunlik taqsimot',
                'type' => 'weekly',
                'is_default' => true,
                'content_template' => json_encode([
                    'monday' => ['type' => 'post', 'theme' => 'Motivational'],
                    'tuesday' => ['type' => 'story', 'theme' => 'Behind the scenes'],
                    'wednesday' => ['type' => 'reel', 'theme' => 'Educational'],
                    'thursday' => ['type' => 'post', 'theme' => 'Product showcase'],
                    'friday' => ['type' => 'post', 'theme' => 'Customer story'],
                    'saturday' => ['type' => 'story', 'theme' => 'Weekend special'],
                    'sunday' => ['type' => 'rest', 'theme' => 'Planning'],
                ]),
                'icon' => 'clock',
                'color' => 'cyan',
            ],
            // Content Templates
            [
                'name' => 'Instagram Kontent Kalendar',
                'slug' => 'instagram-content',
                'description' => 'Instagram uchun optimal kontent rejasi',
                'type' => 'content',
                'is_default' => true,
                'content_template' => json_encode([
                    'feed_posts' => ['frequency' => 'daily', 'best_times' => ['09:00', '18:00', '21:00']],
                    'stories' => ['frequency' => '3-5/day', 'types' => ['polls', 'questions', 'behind_scenes']],
                    'reels' => ['frequency' => '3-4/week', 'duration' => '15-30 sec'],
                    'carousels' => ['frequency' => '2-3/week', 'slides' => '5-10'],
                ]),
                'channels_template' => json_encode(['instagram' => 100]),
                'icon' => 'photo',
                'color' => 'pink',
            ],
            [
                'name' => 'Telegram Kontent Kalendar',
                'slug' => 'telegram-content',
                'description' => 'Telegram kanal uchun kontent rejasi',
                'type' => 'content',
                'content_template' => json_encode([
                    'posts' => ['frequency' => '2-3/day', 'best_times' => ['08:00', '13:00', '19:00']],
                    'polls' => ['frequency' => '2-3/week'],
                    'voice' => ['frequency' => '1-2/week'],
                    'video' => ['frequency' => '1/week'],
                ]),
                'channels_template' => json_encode(['telegram' => 100]),
                'icon' => 'chat',
                'color' => 'sky',
            ],
            [
                'name' => 'Multi-kanal Kontent',
                'slug' => 'multi-channel-content',
                'description' => 'Bir nechta kanal uchun sinxronlashtirilgan kontent',
                'type' => 'content',
                'content_template' => json_encode([
                    'hero_content' => ['frequency' => 'weekly', 'repurpose_to' => ['instagram', 'telegram', 'facebook']],
                    'daily_stories' => ['platforms' => ['instagram', 'facebook']],
                    'long_form' => ['platforms' => ['telegram', 'blog'], 'frequency' => 'weekly'],
                ]),
                'channels_template' => json_encode([
                    'instagram' => 40, 'telegram' => 35, 'facebook' => 25
                ]),
                'icon' => 'squares-2x2',
                'color' => 'purple',
            ],
        ];

        foreach ($templates as $template) {
            DB::table('strategy_templates')->insert([
                'uuid' => Str::uuid(),
                'name' => $template['name'],
                'slug' => $template['slug'],
                'description' => $template['description'],
                'type' => $template['type'],
                'industry' => $template['industry'] ?? null,
                'business_size' => $template['business_size'] ?? null,
                'is_default' => $template['is_default'] ?? false,
                'is_active' => true,
                'is_premium' => $template['is_premium'] ?? false,
                'goals_template' => $template['goals_template'] ?? null,
                'kpis_template' => $template['kpis_template'] ?? null,
                'budget_template' => $template['budget_template'] ?? null,
                'content_template' => $template['content_template'] ?? null,
                'channels_template' => $template['channels_template'] ?? null,
                'activities_template' => $template['activities_template'] ?? null,
                'icon' => $template['icon'] ?? null,
                'color' => $template['color'] ?? 'gray',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
