<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\DreamBuyer;
use Illuminate\Database\Seeder;

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
                DreamBuyer::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'name' => $profileData['name'],
                    ],
                    [
                        'description' => $profileData['description'],
                        'data' => json_encode($profileData['demographics'] ?? []),
                        'priority' => $index + 1,
                        'is_primary' => $index === 0,
                        'where_spend_time' => $profileData['where_spend_time'],
                        'info_sources' => $profileData['info_sources'],
                        'frustrations' => $profileData['frustrations'],
                        'dreams' => $profileData['dreams'],
                        'fears' => $profileData['fears'],
                        'communication_preferences' => $profileData['communication_preferences'],
                        'language_style' => $profileData['language_style'],
                        'daily_routine' => $profileData['daily_routine'],
                        'happiness_triggers' => $profileData['happiness_triggers'],
                    ]
                );
            }

            $this->command->info("Created Dream Buyer profiles for: {$business->name}");
        }

        $this->command->info('Dream Buyer seeding completed!');
    }

    /**
     * Get Dream Buyer profiles based on business industry
     */
    protected function getDreamBuyerProfiles(Business $business): array
    {
        $profiles = [
            'Technology' => [
                [
                    'name' => 'Tech-Savvy Entrepreneur',
                    'description' => '28-40 yoshdagi startup founder yoki tech manager. Yuqori daromadli, zamonaviy texnologiyalarga qiziquvchi.',
                    'where_spend_time' => 'LinkedIn, Telegram tech channels, GitHub, ProductHunt, tech conferences',
                    'info_sources' => 'TechCrunch, LinkedIn articles, YouTube tech reviews, startup podcasts',
                    'frustrations' => "Vaqt yetishmasligi, zamonaviy texnologiyalarni joriy qilish qiyinligi, qualified mutaxassislar topish",
                    'dreams' => "Biznesni to'liq raqamlashtirish, samaradorlikni 3x oshirish, bozorda leader bo'lish",
                    'fears' => "Raqobatchilardan ortda qolish, ma'lumotlar yo'qolishi, cyber attacks",
                    'communication_preferences' => 'Email (detailed), LinkedIn, Zoom meetings. Data-driven communication with ROI calculations',
                    'language_style' => 'Technical jargon: ROI, KPI, automation, API, integration, scalability',
                    'daily_routine' => "06:00-09:00: Sport va news, 09:00-18:00: Work va meetings, 20:00-22:00: O'rganish",
                    'happiness_triggers' => "Yangi texnologiya implement, productivity oshishi, industry recognition",
                    'demographics' => ['age' => '28-40', 'income' => 'high', 'location' => 'Toshkent'],
                ],
            ],
            'Fashion & Retail' => [
                [
                    'name' => 'Young Fashion Enthusiast',
                    'description' => '18-28 yoshdagi student yoki yosh professional. Instagram va TikTokda faol, trendlarga e\'tibor beradi.',
                    'where_spend_time' => 'Instagram, TikTok, Pinterest, YouTube fashion channels, shopping malls',
                    'info_sources' => 'Instagram influencers, fashion bloggers, TikTok trends, YouTube haul videos',
                    'frustrations' => "Trend kiyimlar qimmat, o'lchami mos kelmaydi, onlayn xaridda ishonch kam",
                    'dreams' => "Har doim stylish va unique ko'rinish, Instagram-da ajralib turish, quality kiyimlar",
                    'fears' => "Fashion trends-dan ortda qolish, do'stlari orasida tanqid qilinishi",
                    'communication_preferences' => 'Instagram DM, WhatsApp, visual content (photos/videos)',
                    'language_style' => 'Casual, emoji-rich, trendy words: lit, vibe, aesthetic, on fleek',
                    'daily_routine' => "07:00-09:00: Social media check, 09:00-17:00: Work/study, 19:00-23:00: Instagram scrolling",
                    'happiness_triggers' => "Yangi kiyim olish, compliment olish, Instagram-da ko'p like olish",
                    'demographics' => ['age' => '18-28', 'income' => 'medium', 'location' => 'Toshkent'],
                ],
            ],
            'Food & Beverage' => [
                [
                    'name' => 'Health-Conscious Professional',
                    'description' => '25-40 yoshdagi office worker. Sog\'lom turmush tarzini afzal ko\'radi, fitness bilan shug\'ullanadi.',
                    'where_spend_time' => 'Fitness clubs, health food stores, Instagram (fitness accounts), LinkedIn',
                    'info_sources' => 'Health blogs, fitness influencers, nutrition apps, wellness podcasts',
                    'frustrations' => "Sog'lom ovqat topish qiyin, vaqt yo'q, kaloriya hisoblash murakkab",
                    'dreams' => "Sog'lom va fit bo'lish, vazn nazorati, energiya oshishi",
                    'fears' => "Sog'likning yomonlashishi, ortiqcha vazn, tez-tez kasal bo'lish",
                    'communication_preferences' => 'Telegram, Instagram, mobile app notifications',
                    'language_style' => 'Health terms: calories, organic, protein, gluten-free, keto',
                    'daily_routine' => "06:00-07:00: Fitness, 07:00-09:00: Healthy breakfast, 18:00-19:00: Evening workout",
                    'happiness_triggers' => "Vazn kamayishi, fitness goals achieve qilish, energiya oshishi",
                    'demographics' => ['age' => '25-40', 'income' => 'medium-high', 'location' => 'Toshkent'],
                ],
            ],
            'Education' => [
                [
                    'name' => 'Ambitious Parent',
                    'description' => '30-45 yoshdagi professional ota-ona. Farzandlarining kelajagi uchun tashvishlanadi.',
                    'where_spend_time' => 'Facebook parent groups, Telegram education channels, school events',
                    'info_sources' => 'Education blogs, parent forums, teacher recommendations, Facebook groups',
                    'frustrations' => "Sifatli ta'lim qimmat, zamonaviy bilim yetishmaydi, vaqt yo'q",
                    'dreams' => "Farzandlar international standartda ta'lim olishi, yaxshi karyera",
                    'fears' => "Farzandlar ortda qolishi, imkoniyatlarni boy berish, kelajakda muvaffaqiyatsizlik",
                    'communication_preferences' => 'WhatsApp, Facebook, phone calls, face-to-face meetings',
                    'language_style' => 'Education-focused: IELTS, international certification, qualified teachers',
                    'daily_routine' => "07:00-09:00: Farzandlarni tayyorlash, 18:00-21:00: Homework help, uy vazifasi",
                    'happiness_triggers' => "Farzandlarning yutuqlari, yaxshi baholar, teacher appreciation",
                    'demographics' => ['age' => '30-45', 'income' => 'medium-high', 'location' => 'Toshkent'],
                ],
            ],
            'Automotive' => [
                [
                    'name' => 'Car Enthusiast',
                    'description' => '25-50 yoshdagi professional. Mashinasini yaxshi holatda saqlashni xohlaydi.',
                    'where_spend_time' => 'Car forums, Telegram auto groups, YouTube car channels, auto shows',
                    'info_sources' => 'YouTube mechanic channels, car forums, Telegram communities',
                    'frustrations' => "Ishonchli service topish qiyin, yuqori narxlar, sifatsiz qismlar",
                    'dreams' => "Mashinani uzoq muddatga saqlash, xavfsizlik, value retention",
                    'fears' => "Mashinaning buzilishi, yo'lda qolish, qimmat ta'mirlash",
                    'communication_preferences' => 'WhatsApp, Telegram, phone calls',
                    'language_style' => 'Car terms: original parts, warranty, diagnostics, maintenance',
                    'daily_routine' => "Har kuni mashina bilan ishga, hafta oxirida car wash",
                    'happiness_triggers' => "Mashina yaxshi ishlashi, quality service olish, muammosiz haydash",
                    'demographics' => ['age' => '25-50', 'income' => 'medium', 'location' => 'Toshkent'],
                ],
            ],
        ];

        return $profiles[$business->industry] ?? [
            [
                'name' => 'General Customer',
                'description' => '25-45 yoshdagi professional',
                'where_spend_time' => 'Instagram, Telegram, Facebook',
                'info_sources' => 'Social media, recommendations',
                'frustrations' => "Sifatli mahsulot topish, vaqt yo'qligi, narx",
                'dreams' => "Sifatli mahsulot olish, qulaylik",
                'fears' => "Pulni bekorga sarflash, sifatsiz xizmat",
                'communication_preferences' => 'Telegram, WhatsApp',
                'language_style' => 'O\'zbek tili, oddiy so\'zlar',
                'daily_routine' => "09:00-18:00: Work",
                'happiness_triggers' => "Yaxshi xizmat, sifatli mahsulot",
                'demographics' => ['age' => '25-45', 'income' => 'medium', 'location' => 'Toshkent'],
            ],
        ];
    }
}
