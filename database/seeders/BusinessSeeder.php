<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get test users
        $adminUser = User::where('login', 'admin')->first();
        $regularUser = User::where('login', 'user1')->first();

        if (! $adminUser || ! $regularUser) {
            $this->command->warn('Users not found. Please run TestUserSeeder first.');

            return;
        }

        // Get a plan
        $plan = Plan::first();

        if (! $plan) {
            $this->command->warn('No plans found. Please run PlanSeeder first.');

            return;
        }

        // Create demo businesses (matching migration schema)
        $businesses = [
            [
                'name' => 'Tech Solutions UZ',
                'slug' => 'tech-solutions-uz',
                'category' => 'Technology',
                'description' => 'IT xizmatlari va dasturiy ta\'minot ishlab chiqish kompaniyasi',
                'website' => 'https://techsolutions.uz',
                'phone' => '+998901234567',
                'email' => 'info@techsolutions.uz',
                'address' => 'Toshkent, Yunusobod tumani',
                'city' => 'Toshkent',
                'country' => 'Uzbekistan',
                'status' => 'active',
                'user_id' => $regularUser->id,
            ],
            [
                'name' => 'Fashion Boutique',
                'slug' => 'fashion-boutique',
                'category' => 'Fashion & Retail',
                'description' => 'Zamonaviy kiyim-kechak va aksessuarlar do\'koni',
                'website' => 'https://fashionboutique.uz',
                'phone' => '+998901234568',
                'email' => 'contact@fashionboutique.uz',
                'address' => 'Toshkent, Chilonzor tumani',
                'city' => 'Toshkent',
                'country' => 'Uzbekistan',
                'status' => 'active',
                'user_id' => $regularUser->id,
            ],
            [
                'name' => 'Healthy Food Delivery',
                'slug' => 'healthy-food-delivery',
                'category' => 'Food & Beverage',
                'description' => 'Sog\'lom va tabiiy ovqatlar yetkazib berish xizmati',
                'website' => 'https://healthyfood.uz',
                'phone' => '+998901234569',
                'email' => 'hello@healthyfood.uz',
                'address' => 'Toshkent, Mirzo Ulug\'bek tumani',
                'city' => 'Toshkent',
                'country' => 'Uzbekistan',
                'status' => 'active',
                'user_id' => $adminUser->id,
            ],
            [
                'name' => 'Education Center Plus',
                'slug' => 'education-center-plus',
                'category' => 'Education',
                'description' => 'Zamonaviy ta\'lim markazi - til kurslari va IT o\'rgatish',
                'website' => 'https://eduplus.uz',
                'phone' => '+998901234570',
                'email' => 'info@eduplus.uz',
                'address' => 'Toshkent, Yakkasaroy tumani',
                'city' => 'Toshkent',
                'country' => 'Uzbekistan',
                'status' => 'active',
                'user_id' => $adminUser->id,
            ],
            [
                'name' => 'Auto Service Pro',
                'slug' => 'auto-service-pro',
                'category' => 'Automotive',
                'description' => 'Professional avtomobil xizmat ko\'rsatish markazi',
                'website' => 'https://autoservice.uz',
                'phone' => '+998901234571',
                'email' => 'service@autoservice.uz',
                'address' => 'Toshkent, Sergeli tumani',
                'city' => 'Toshkent',
                'country' => 'Uzbekistan',
                'status' => 'inactive',
                'user_id' => $regularUser->id,
            ],
        ];

        foreach ($businesses as $businessData) {
            $business = Business::firstOrCreate(
                ['slug' => $businessData['slug']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $businessData['name'],
                    'category' => $businessData['category'],
                    'description' => $businessData['description'],
                    'website' => $businessData['website'],
                    'phone' => $businessData['phone'],
                    'email' => $businessData['email'],
                    'address' => $businessData['address'],
                    'city' => $businessData['city'],
                    'country' => $businessData['country'],
                    'status' => $businessData['status'],
                    'user_id' => $businessData['user_id'],
                ]
            );

            // Create subscription for each business
            Subscription::firstOrCreate(
                ['business_id' => $business->id],
                [
                    'id' => (string) Str::uuid(),
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'trial_ends_at' => now()->addDays(14),
                ]
            );

            $this->command->info("Created business: {$business->name}");
        }

        $this->command->info('Business seeding completed!');
    }
}
