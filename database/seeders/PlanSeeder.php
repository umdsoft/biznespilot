<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                'price' => 299000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
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
                'sort_order' => 1,
            ],
            [
                'name' => 'Starter (Yearly)',
                'slug' => 'starter-yearly',
                'description' => 'Ideal for startups and small businesses just getting started - yearly plan',
                'price' => 2990000,
                'currency' => 'UZS',
                'billing_period' => 'yearly',
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
                'sort_order' => 2,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for growing businesses that need more features',
                'price' => 499000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
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
                'sort_order' => 3,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For established businesses ready to scale',
                'price' => 999000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
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
                'sort_order' => 4,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'description' => 'Enterprise solution for unlimited growth',
                'price' => 2490000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'business_limit' => 15,
                'team_member_limit' => null, // unlimited
                'lead_limit' => null, // unlimited
                'chatbot_channel_limit' => null, // unlimited
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
                'sort_order' => 5,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::where('slug', $planData['slug'])->first();

            if (! $plan) {
                $plan = new Plan;
                $plan->id = Str::uuid()->toString();
            }

            $plan->fill($planData);
            $plan->save();
        }
    }
}
