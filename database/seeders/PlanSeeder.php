<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Ideal for startups and small businesses just getting started',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'business_limit' => 1,
                'team_member_limit' => 1,
                'lead_limit' => 100,
                'chatbot_channel_limit' => 0,
                'has_amocrm' => false,
                'features' => [
                    'Business Profile',
                    'Dream Buyer',
                    'Marketing Channels',
                    'Sales Funnel',
                    'Basic Reports',
                    'AI Insights (Limited)',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for growing businesses that need more features',
                'price_monthly' => 499000,
                'price_yearly' => 4990000,
                'business_limit' => 2,
                'team_member_limit' => 3,
                'lead_limit' => 500,
                'chatbot_channel_limit' => 1,
                'has_amocrm' => false,
                'features' => [
                    'All Starter features',
                    '2 Businesses',
                    '3 Team Members',
                    'Chatbot (1 channel)',
                    'Competitor Tracking',
                    'Content Calendar',
                    'Advanced Reports',
                    'AI Insights',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For established businesses ready to scale',
                'price_monthly' => 999000,
                'price_yearly' => 9990000,
                'business_limit' => 5,
                'team_member_limit' => 10,
                'lead_limit' => 2000,
                'chatbot_channel_limit' => 5,
                'has_amocrm' => true,
                'features' => [
                    'All Basic features',
                    '5 Businesses',
                    '10 Team Members',
                    'Chatbot (5 channels)',
                    'AmoCRM Integration',
                    'Grand Slam Offers',
                    'HVCO Management',
                    'Advanced AI Insights',
                    'Custom Reports',
                    'API Access',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'description' => 'Enterprise solution for unlimited growth',
                'price_monthly' => 2490000,
                'price_yearly' => 24900000,
                'business_limit' => 15,
                'team_member_limit' => -1, // unlimited
                'lead_limit' => -1, // unlimited
                'chatbot_channel_limit' => -1, // unlimited
                'has_amocrm' => true,
                'features' => [
                    'All Growth features',
                    '15 Businesses',
                    'Unlimited Team Members',
                    'Unlimited Leads',
                    'Unlimited Chatbot Channels',
                    'All Integrations',
                    'Priority Support',
                    'Dedicated Account Manager',
                    'Custom AI Training',
                    'White-label Options',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
