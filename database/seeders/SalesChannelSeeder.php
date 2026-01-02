<?php

namespace Database\Seeders;

use App\Models\SalesChannel;
use Illuminate\Database\Seeder;

class SalesChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            // Retail
            [
                'code' => 'retail_store',
                'name' => "Do'kon",
                'type' => 'retail',
                'icon' => 'store',
                'commission_percent' => 0,
                'sort_order' => 1,
            ],
            [
                'code' => 'showroom',
                'name' => 'Showroom',
                'type' => 'retail',
                'icon' => 'home',
                'commission_percent' => 0,
                'sort_order' => 2,
            ],

            // Online
            [
                'code' => 'website',
                'name' => 'Website',
                'type' => 'online',
                'icon' => 'globe',
                'commission_percent' => 0,
                'sort_order' => 10,
            ],
            [
                'code' => 'instagram_shop',
                'name' => 'Instagram Shop',
                'type' => 'online',
                'icon' => 'instagram',
                'commission_percent' => 0,
                'sort_order' => 11,
            ],
            [
                'code' => 'telegram_bot',
                'name' => 'Telegram Bot',
                'type' => 'online',
                'icon' => 'telegram',
                'commission_percent' => 0,
                'sort_order' => 12,
            ],
            [
                'code' => 'marketplace',
                'name' => 'Marketplace (Uzum, etc)',
                'type' => 'online',
                'icon' => 'shopping-bag',
                'commission_percent' => 15,
                'sort_order' => 13,
            ],

            // Wholesale
            [
                'code' => 'wholesale',
                'name' => 'Ulgurji sotish',
                'type' => 'wholesale',
                'icon' => 'package',
                'commission_percent' => 0,
                'sort_order' => 20,
            ],

            // Agent
            [
                'code' => 'sales_agent',
                'name' => 'Sotuv agenti',
                'type' => 'agent',
                'icon' => 'user',
                'commission_percent' => 10,
                'sort_order' => 30,
            ],
            [
                'code' => 'distributor',
                'name' => 'Distribyutor',
                'type' => 'agent',
                'icon' => 'truck',
                'commission_percent' => 20,
                'sort_order' => 31,
            ],

            // B2B
            [
                'code' => 'corporate',
                'name' => 'Korporativ mijozlar',
                'type' => 'b2b',
                'icon' => 'building',
                'commission_percent' => 0,
                'sort_order' => 40,
            ],
            [
                'code' => 'government',
                'name' => 'Davlat tashkilotlari',
                'type' => 'b2b',
                'icon' => 'landmark',
                'commission_percent' => 0,
                'sort_order' => 41,
            ],
        ];

        foreach ($channels as $channel) {
            SalesChannel::updateOrCreate(
                ['code' => $channel['code']],
                array_merge($channel, ['is_active' => true])
            );
        }
    }
}
