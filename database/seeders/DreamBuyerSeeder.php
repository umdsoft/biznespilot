<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DreamBuyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        if ($businesses->isEmpty()) {
            $this->command->warn('No businesses found. Please run BusinessSeeder first.');
            return;
        }

        foreach ($businesses as $business) {
            $profiles = $this->getDreamBuyerProfiles($business);

            foreach ($profiles as $index => $profileData) {
                DB::table('dream_buyers')->insert([
                    'id' => Str::uuid()->toString(),
                    'business_id' => $business->id,
                    'name' => $profileData['name'],
                    'description' => $profileData['description'],
                    'age_range' => $profileData['age_range'] ?? null,
                    'gender' => $profileData['gender'] ?? null,
                    'location' => $profileData['location'] ?? 'Toshkent',
                    'occupation' => $profileData['occupation'] ?? null,
                    'income_level' => $profileData['income_level'] ?? 'medium',
                    'interests' => $profileData['interests'] ?? null,
                    'pain_points' => $profileData['pain_points'] ?? null,
                    'goals' => $profileData['goals'] ?? null,
                    'objections' => $profileData['objections'] ?? null,
                    'buying_triggers' => $profileData['buying_triggers'] ?? null,
                    'preferred_channels' => $profileData['preferred_channels'] ?? null,
                    'priority' => $index === 0 ? 'high' : 'medium',
                    'is_primary' => $index === 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info("Created Dream Buyer profiles for: {$business->name}");
        }

        $this->command->info('Dream Buyer seeding completed!');
    }

    /**
     * Get Dream Buyer profiles based on business category
     */
    protected function getDreamBuyerProfiles(Business $business): array
    {
        $profiles = [
            'Technology' => [
                [
                    'name' => 'Tech-Savvy Entrepreneur',
                    'description' => '28-40 yoshdagi startup founder yoki tech manager.',
                    'age_range' => '28-40',
                    'gender' => 'any',
                    'income_level' => 'high',
                    'occupation' => 'Startup Founder / Tech Manager',
                    'interests' => 'Zamonaviy texnologiyalar, startup, innovation, AI',
                    'pain_points' => 'Vaqt yetishmasligi, qualified mutaxassislar topish',
                    'goals' => 'Biznesni raqamlashtirish, samaradorlikni oshirish',
                    'objections' => 'Narxi, implementatsiya murakkabligi',
                    'buying_triggers' => 'ROI, vaqt tejash, konkurentsiya',
                    'preferred_channels' => 'LinkedIn, Telegram, Email',
                ],
            ],
            'Fashion & Retail' => [
                [
                    'name' => 'Young Fashion Enthusiast',
                    'description' => '18-28 yoshdagi student yoki yosh professional.',
                    'age_range' => '18-28',
                    'gender' => 'female',
                    'income_level' => 'medium',
                    'occupation' => 'Student / Young Professional',
                    'interests' => 'Fashion, Instagram, TikTok, shopping',
                    'pain_points' => 'Trend kiyimlar qimmat, o\'lchami mos kelmaydi',
                    'goals' => 'Stylish ko\'rinish, Instagram-da ajralib turish',
                    'objections' => 'Narx, sifat shubhasi',
                    'buying_triggers' => 'Chegirma, trend, influencer tavsiyasi',
                    'preferred_channels' => 'Instagram, TikTok, WhatsApp',
                ],
            ],
            'Food & Beverage' => [
                [
                    'name' => 'Health-Conscious Professional',
                    'description' => '25-40 yoshdagi office worker.',
                    'age_range' => '25-40',
                    'gender' => 'any',
                    'income_level' => 'medium-high',
                    'occupation' => 'Office Worker',
                    'interests' => 'Fitness, healthy eating, wellness',
                    'pain_points' => 'Sog\'lom ovqat topish qiyin, vaqt yo\'q',
                    'goals' => 'Sog\'lom va fit bo\'lish, energiya oshishi',
                    'objections' => 'Narx, mazasi oddiy ovqatga o\'xshamaydi',
                    'buying_triggers' => 'Sog\'liq, qulay yetkazib berish, tavsiya',
                    'preferred_channels' => 'Telegram, Instagram',
                ],
            ],
            'Education' => [
                [
                    'name' => 'Ambitious Parent',
                    'description' => '30-45 yoshdagi professional ota-ona.',
                    'age_range' => '30-45',
                    'gender' => 'any',
                    'income_level' => 'medium-high',
                    'occupation' => 'Professional',
                    'interests' => 'Farzand ta\'limi, til o\'rganish, IT',
                    'pain_points' => 'Sifatli ta\'lim qimmat, vaqt yetishmaydi',
                    'goals' => 'Farzandlar international standartda ta\'lim olishi',
                    'objections' => 'Narx, natijalar kafolati',
                    'buying_triggers' => 'Farzand kelajagi, sertifikat, do\'stlar tavsiyasi',
                    'preferred_channels' => 'WhatsApp, Facebook, Telegram',
                ],
            ],
            'Automotive' => [
                [
                    'name' => 'Car Enthusiast',
                    'description' => '25-50 yoshdagi professional.',
                    'age_range' => '25-50',
                    'gender' => 'male',
                    'income_level' => 'medium',
                    'occupation' => 'Professional',
                    'interests' => 'Cars, maintenance, driving',
                    'pain_points' => 'Ishonchli service topish qiyin, yuqori narxlar',
                    'goals' => 'Mashinani uzoq muddatga saqlash, xavfsizlik',
                    'objections' => 'Narx, ishonch masalasi',
                    'buying_triggers' => 'Kafolat, original qismlar, tavsiya',
                    'preferred_channels' => 'WhatsApp, Telegram',
                ],
            ],
        ];

        // Return profiles based on business category or default
        return $profiles[$business->category] ?? [
            [
                'name' => 'General Customer',
                'description' => '25-45 yoshdagi professional',
                'age_range' => '25-45',
                'gender' => 'any',
                'income_level' => 'medium',
                'occupation' => 'Professional',
                'interests' => 'Quality products, service',
                'pain_points' => 'Sifatli mahsulot topish, vaqt yo\'qligi',
                'goals' => 'Sifatli mahsulot olish, qulaylik',
                'objections' => 'Narx, ishonch',
                'buying_triggers' => 'Tavsiya, chegirma, sifat',
                'preferred_channels' => 'Telegram, WhatsApp',
            ],
        ];
    }
}
