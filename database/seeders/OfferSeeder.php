<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Offer;
use App\Models\DreamBuyer;
use Illuminate\Database\Seeder;

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
            $offers = $this->getOffersForBusiness($business, $dreamBuyer);

            foreach ($offers as $offerData) {
                Offer::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'name' => $offerData['name'],
                    ],
                    [
                        'description' => $offerData['description'],
                        'value_proposition' => $offerData['value_proposition'],
                        'target_audience' => $offerData['target_audience'] ?? null,
                        'pricing' => $offerData['pricing'],
                        'pricing_model' => $offerData['pricing_model'] ?? 'one-time',
                        'guarantees' => $offerData['guarantees'],
                        'bonuses' => $offerData['bonuses'],
                        'scarcity' => $offerData['scarcity'],
                        'urgency' => $offerData['urgency'],
                        'status' => 'active',
                        'conversion_rate' => rand(5, 25),
                        'metadata' => json_encode([
                            'dream_buyer_id' => $dreamBuyer?->id,
                        ]),
                    ]
                );
            }

            $this->command->info("Created offers for: {$business->name}");
        }

        $this->command->info('Offer seeding completed!');
    }

    /**
     * Get offers based on business industry
     */
    protected function getOffersForBusiness(Business $business, $dreamBuyer = null): array
    {
        $industryOffers = [
            'Technology' => [
                [
                    'name' => 'Startup Digital Package',
                    'description' => 'Startuplar uchun to\'liq digital yechim',
                    'value_proposition' => 'Website + CRM + Marketing Automation - 60 kun ichida. 60% chegirma!',
                    'target_audience' => 'Startuplar, SMB',
                    'pricing' => 15000000,
                    'pricing_model' => 'one-time',
                    'guarantees' => '60 kun pul qaytarish kafolati',
                    'bonuses' => "Bepul logo design\nSocial media templates\n1 yillik domain",
                    'scarcity' => 'Har oyda faqat 5 ta mijoz',
                    'urgency' => 'Yana 2 ta o\'rin qoldi',
                ],
                [
                    'name' => 'Business Automation Pro',
                    'description' => 'Biznesni to\'liq avtomatlash',
                    'value_proposition' => 'Samaradorlikni 3x oshiring. AI-powered avtomatlashtirish.',
                    'target_audience' => 'O\'rta bizneslar',
                    'pricing' => 25000000,
                    'pricing_model' => 'one-time',
                    'guarantees' => '90 kun ROI kafolati',
                    'bonuses' => "AI chatbot\nAnalytics dashboard\nOylik optimizatsiya",
                    'scarcity' => 'Premium - faqat 3 ta kompaniya',
                    'urgency' => 'Keyingi intake 2 oydan keyin',
                ],
            ],
            'Fashion & Retail' => [
                [
                    'name' => 'Summer Collection VIP',
                    'description' => 'Yozgi premium kiyimlar to\'plami',
                    'value_proposition' => '5 outfit + aksessuarlar + styling. 42% chegirma!',
                    'target_audience' => 'Yosh fashionistalar',
                    'pricing' => 3500000,
                    'pricing_model' => 'one-time',
                    'guarantees' => '14 kun qaytarish yoki almashtirish',
                    'bonuses' => "VIP card 20% discount\nBepul yetkazish\nFree alterations",
                    'scarcity' => 'Limited - 50 ta paket',
                    'urgency' => '5 kungacha',
                ],
            ],
            'Food & Beverage' => [
                [
                    'name' => '30 Days Healthy Meal Plan',
                    'description' => '30 kunlik sog\'lom ovqatlanish',
                    'value_proposition' => '90 ta taom + snacks. Fitness plan bepul!',
                    'target_audience' => 'Health-conscious professionals',
                    'pricing' => 2500000,
                    'pricing_model' => 'subscription',
                    'guarantees' => '7 kun bepul sinov',
                    'bonuses' => "Fitness plan\nRecipe book\nWater bottle\nTracking app",
                    'scarcity' => 'Kuniga 50 ta buyurtma',
                    'urgency' => 'Bugun 15% chegirma',
                ],
            ],
            'Education' => [
                [
                    'name' => 'English Premium Course',
                    'description' => '6 oylik intensive English',
                    'value_proposition' => 'IELTS 7.0+ kafolati. Native speaker bilan!',
                    'target_audience' => 'IELTS talabgоrlari',
                    'pricing' => 4000000,
                    'pricing_model' => 'one-time',
                    'guarantees' => 'IELTS 6.5 kafolati yoki bepul qayta o\'qish',
                    'bonuses' => "Mock test bepul\nConversation club\n1 yil online access",
                    'scarcity' => 'Guruhda 12 ta o\'rin',
                    'urgency' => '1 haftadan boshlanadi',
                ],
            ],
            'Automotive' => [
                [
                    'name' => 'Premium Car Service Package',
                    'description' => 'Yillik to\'liq xizmat paketi',
                    'value_proposition' => '4 ta TO + diagnostika + 12 ta yuvish. 40% tejash!',
                    'target_audience' => 'Car owners',
                    'pricing' => 3000000,
                    'pricing_model' => 'subscription',
                    'guarantees' => '1 yil ishlar kafolati',
                    'bonuses' => "24/7 roadside assistance\nBepul towing\n20% discount card",
                    'scarcity' => '100 ta paket',
                    'urgency' => '50 ta sotildi',
                ],
            ],
        ];

        return $industryOffers[$business->industry] ?? [
            [
                'name' => 'Starter Package',
                'description' => 'Boshlang\'ich paket',
                'value_proposition' => 'Eng zarur xizmatlar bitta paketda',
                'target_audience' => 'Barcha mijozlar',
                'pricing' => 1000000,
                'pricing_model' => 'one-time',
                'guarantees' => '30 kun pul qaytarish',
                'bonuses' => "Bonus 1\nBonus 2",
                'scarcity' => 'Limited offer',
                'urgency' => 'Tez tugaydi',
            ],
        ];
    }
}
