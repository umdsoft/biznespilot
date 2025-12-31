<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\DreamBuyer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfferSeeder extends Seeder
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
            $dreamBuyer = DreamBuyer::where('business_id', $business->id)->first();
            $offers = $this->getOffersForBusiness($business);

            foreach ($offers as $offerData) {
                DB::table('offers')->insert([
                    'id' => Str::uuid()->toString(),
                    'business_id' => $business->id,
                    'dream_buyer_id' => $dreamBuyer?->id,
                    'name' => $offerData['name'],
                    'description' => $offerData['description'],
                    'type' => $offerData['type'] ?? 'product',
                    'price' => $offerData['price'],
                    'currency' => 'UZS',
                    'dream_outcome_score' => rand(7, 10),
                    'dream_outcome_description' => $offerData['dream_outcome'] ?? null,
                    'perceived_likelihood_score' => rand(6, 9),
                    'perceived_likelihood_description' => $offerData['guarantee'] ?? 'Sifat kafolati',
                    'time_delay_score' => rand(5, 8),
                    'time_delay_description' => $offerData['delivery'] ?? 'Tez yetkazish',
                    'effort_sacrifice_score' => rand(6, 9),
                    'effort_sacrifice_description' => 'Qulay buyurtma',
                    'value_score' => rand(70, 95) / 10.0,
                    'bonuses' => json_encode($offerData['bonuses'] ?? []),
                    'guarantees' => json_encode($offerData['guarantees'] ?? []),
                    'urgency_scarcity' => json_encode($offerData['urgency'] ?? []),
                    'status' => 'active',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info("Created offers for: {$business->name}");
        }

        $this->command->info('Offer seeding completed!');
    }

    /**
     * Get offers based on business category
     */
    protected function getOffersForBusiness(Business $business): array
    {
        $categoryOffers = [
            'Technology' => [
                [
                    'name' => 'Startup Digital Package',
                    'description' => 'Startuplar uchun to\'liq digital yechim - Website + CRM + Marketing',
                    'type' => 'service',
                    'price' => 15000000,
                    'dream_outcome' => 'Biznesingiz onlayn holatga keladi va mijozlar oqimi boshlanadi',
                    'guarantee' => '60 kun pul qaytarish kafolati',
                    'delivery' => '60 kun ichida tayyor',
                    'bonuses' => ['Bepul logo design', 'Social media templates', '1 yillik domain'],
                    'guarantees' => ['Pul qaytarish', 'Sifat kafolati'],
                    'urgency' => ['message' => 'Har oyda faqat 5 ta mijoz', 'type' => 'scarcity'],
                ],
                [
                    'name' => 'Business Automation Pro',
                    'description' => 'AI-powered biznes avtomatlashtirish xizmati',
                    'type' => 'service',
                    'price' => 25000000,
                    'dream_outcome' => 'Samaradorlikni 3x oshiring',
                    'guarantee' => '90 kun ROI kafolati',
                    'delivery' => '90 kun ichida implement',
                    'bonuses' => ['AI chatbot', 'Analytics dashboard', 'Oylik optimizatsiya'],
                    'guarantees' => ['ROI kafolati', 'Sifat kafolati'],
                    'urgency' => ['message' => 'Premium - faqat 3 ta kompaniya', 'type' => 'scarcity'],
                ],
            ],
            'Fashion & Retail' => [
                [
                    'name' => 'Summer Collection VIP',
                    'description' => 'Yozgi premium kiyimlar to\'plami - 5 outfit + aksessuarlar',
                    'type' => 'product',
                    'price' => 3500000,
                    'dream_outcome' => 'Stylish va unique ko\'rinish',
                    'guarantee' => '14 kun qaytarish yoki almashtirish',
                    'delivery' => 'Bepul yetkazish',
                    'bonuses' => ['VIP card 20% discount', 'Free alterations'],
                    'guarantees' => ['Qaytarish kafolati', 'Sifat kafolati'],
                    'urgency' => ['message' => 'Limited - 50 ta paket', 'type' => 'scarcity'],
                ],
            ],
            'Food & Beverage' => [
                [
                    'name' => '30 Days Healthy Meal Plan',
                    'description' => '30 kunlik sog\'lom ovqatlanish - 90 ta taom + snacks',
                    'type' => 'subscription',
                    'price' => 2500000,
                    'dream_outcome' => 'Sog\'lom va fit bo\'lish',
                    'guarantee' => '7 kun bepul sinov',
                    'delivery' => 'Kunlik yetkazish',
                    'bonuses' => ['Fitness plan', 'Recipe book', 'Water bottle', 'Tracking app'],
                    'guarantees' => ['Bepul sinov', 'Sifat kafolati'],
                    'urgency' => ['message' => 'Bugun 15% chegirma', 'type' => 'urgency'],
                ],
            ],
            'Education' => [
                [
                    'name' => 'English Premium Course',
                    'description' => '6 oylik intensive English - IELTS 7.0+ kafolati',
                    'type' => 'service',
                    'price' => 4000000,
                    'dream_outcome' => 'IELTS 7.0+ ball olish',
                    'guarantee' => 'IELTS 6.5 kafolati yoki bepul qayta o\'qish',
                    'delivery' => '6 oy intensive kurs',
                    'bonuses' => ['Mock test bepul', 'Conversation club', '1 yil online access'],
                    'guarantees' => ['Natija kafolati', 'Bepul qayta o\'qish'],
                    'urgency' => ['message' => 'Guruhda 12 ta o\'rin', 'type' => 'scarcity'],
                ],
            ],
            'Automotive' => [
                [
                    'name' => 'Premium Car Service Package',
                    'description' => 'Yillik to\'liq xizmat paketi - 4 ta TO + diagnostika + 12 ta yuvish',
                    'type' => 'subscription',
                    'price' => 3000000,
                    'dream_outcome' => 'Mashinangiz doim ideal holatda',
                    'guarantee' => '1 yil ishlar kafolati',
                    'delivery' => 'Har chorakda xizmat',
                    'bonuses' => ['24/7 roadside assistance', 'Bepul towing', '20% discount card'],
                    'guarantees' => ['Ish kafolati', 'Original qismlar'],
                    'urgency' => ['message' => '50 ta sotildi', 'type' => 'scarcity'],
                ],
            ],
        ];

        return $categoryOffers[$business->category] ?? [
            [
                'name' => 'Starter Package',
                'description' => 'Boshlang\'ich paket - eng zarur xizmatlar bitta paketda',
                'type' => 'product',
                'price' => 1000000,
                'dream_outcome' => 'Sifatli xizmat olish',
                'guarantee' => '30 kun pul qaytarish',
                'delivery' => 'Tez xizmat',
                'bonuses' => ['Bonus 1', 'Bonus 2'],
                'guarantees' => ['Pul qaytarish'],
                'urgency' => ['message' => 'Limited offer', 'type' => 'scarcity'],
            ],
        ];
    }
}
