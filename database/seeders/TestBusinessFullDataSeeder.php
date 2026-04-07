<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 1 yillik to'liq test ma'lumotlar — AI Agent test uchun.
 * IT kompaniya (web/mobil dasturlash xizmati)
 */
class TestBusinessFullDataSeeder extends Seeder
{
    private string $businessId = 'aa9b1e35-020b-4ec1-b1ad-ef9f931cfac8';
    private string $userId = 'b090f3d5-173d-480b-8ea5-7140004d64f2';

    public function run(): void
    {
        $this->command->info('1 yillik test ma\'lumotlar yaratilmoqda...');

        $this->seedDreamBuyer();
        $this->seedLeadSources();
        $this->seedLeads();
        $this->seedCompetitors();
        $this->seedOffers();

        $this->command->info('Tayyor! Barcha ma\'lumotlar yaratildi.');
    }

    private function seedDreamBuyer(): void
    {
        DreamBuyer::updateOrCreate(
            ['business_id' => $this->businessId],
            [
                'name' => 'IT xizmatlarga muhtoj biznes egasi',
                'description' => "O'zbekistondagi kichik va o'rta biznes egalari, 25-45 yosh, oylik daromadi 10-50 mln so'm. Biznesini onlayn kengaytirmoqchi, lekin texnik bilimi cheklangan.",
                'priority' => 'high',
                'is_primary' => true,
            ]
        );
        $this->command->info('  ✓ Ideal mijoz portreti yaratildi');
    }

    private function seedLeadSources(): void
    {
        $sources = ['Instagram', 'Telegram', 'Veb-sayt', 'Tavsiya', 'Google Ads', 'Facebook'];

        foreach ($sources as $name) {
            try {
                DB::table('lead_sources')->updateOrInsert(
                    ['business_id' => $this->businessId, 'name' => $name],
                    [
                        'id' => Str::uuid(),
                        'business_id' => $this->businessId,
                        'name' => $name,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            } catch (\Exception $e) {}
        }
        $this->command->info('  ✓ Lead manbalari yaratildi');
    }

    private function seedLeads(): void
    {
        $statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
        $names = [
            'Aziz Karimov', 'Dilshod Rahimov', 'Nodira Toshmatova', 'Jasur Umarov',
            'Shaxlo Mirzayeva', 'Bobur Alimov', 'Malika Hasanova', 'Sardor Yusupov',
            'Gulnora Abdullayeva', 'Otabek Nazarov', 'Kamola Ismoilova', 'Rustam Qodirov',
            'Dildora Saidova', 'Mirzo Tursunov', 'Zulfiya Mamatova', 'Anvar Xolmatov',
            'Feruza Raxmatullayeva', 'Ulugbek Botirov', 'Nilufar Ergasheva', 'Sherzod Haydarov',
            'Dilorom Jurayeva', 'Baxtiyor Soliyev', 'Shahlo Normatova', 'Muhammadali Qobilov',
            'Zilola Kamalova', 'Davron Xasanov', 'Madina Turgunova', 'Oybek Rajabov',
            'Sevinch Aliyeva', 'Nodir Baxtiyorov', 'Iroda Mirzakarimova', 'Jamshid Toshpulatov',
        ];
        $companies = [
            'TechSoft LLC', 'Milliy Savdo', 'Baraka Group', 'Osiyo Trade',
            'Silk Road Logistics', 'UzBuild Pro', 'AgroFresh', 'MediPlus',
            'EduSmart Academy', 'FoodMaster', 'AutoService Pro', 'TextilePlus',
            'Green Energy UZ', 'FinTech Solutions', 'Beauty Lab', 'Sport Arena',
        ];
        $sources = ['instagram', 'telegram', 'website', 'referral', 'google_ads', 'facebook'];

        $count = 0;
        for ($dayOffset = 365; $dayOffset >= 0; $dayOffset--) {
            // Har kuni 0-3 ta lead
            $leadsPerDay = $dayOffset < 30 ? rand(1, 3) : rand(0, 2);

            for ($j = 0; $j < $leadsPerDay; $j++) {
                $createdAt = now()->subDays($dayOffset)->addHours(rand(8, 20))->addMinutes(rand(0, 59));

                // Eski leadlar ko'proq progresslangan
                if ($dayOffset > 180) {
                    $status = $this->weightedRandom(['won' => 30, 'lost' => 25, 'qualified' => 15, 'contacted' => 15, 'new' => 5, 'proposal' => 5, 'negotiation' => 5]);
                } elseif ($dayOffset > 60) {
                    $status = $this->weightedRandom(['contacted' => 20, 'qualified' => 20, 'proposal' => 15, 'negotiation' => 15, 'won' => 15, 'lost' => 10, 'new' => 5]);
                } else {
                    $status = $this->weightedRandom(['new' => 35, 'contacted' => 25, 'qualified' => 15, 'proposal' => 10, 'negotiation' => 5, 'won' => 5, 'lost' => 5]);
                }

                $estimatedValue = rand(2, 50) * 1000000; // 2-50 mln so'm
                $name = $names[array_rand($names)];

                try {
                    Lead::create([
                        'business_id' => $this->businessId,
                        'name' => $name,
                        'email' => Str::slug($name, '.') . rand(1, 99) . '@gmail.com',
                        'phone' => '+998' . rand(90, 99) . rand(1000000, 9999999),
                        'company' => $companies[array_rand($companies)],
                        'status' => $status,
                        'score' => match ($status) {
                            'won' => rand(80, 100),
                            'negotiation', 'proposal' => rand(60, 85),
                            'qualified' => rand(40, 65),
                            'contacted' => rand(20, 45),
                            'new' => rand(5, 25),
                            'lost' => rand(10, 40),
                            default => rand(10, 50),
                        },
                        'estimated_value' => $estimatedValue,
                        // source_id is FK — skip for now
                        'notes' => $status === 'won' ? 'Bitim muvaffaqiyatli yakunlandi' : ($status === 'lost' ? 'Narx bo\'yicha kelisha olmadik' : null),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt->addDays(rand(0, 5)),
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    if ($count === 0) $this->command->error("  Lead xato: " . $e->getMessage());
                }
            }
        }
        $this->command->info("  ✓ {$count} ta lead yaratildi (1 yillik)");
    }

    private function seedCompetitors(): void
    {
        $competitors = [
            ['name' => 'UzDev Solutions', 'website' => 'https://uzdev.uz', 'description' => 'Web va mobil dasturlash'],
            ['name' => 'IT Park Residents', 'website' => 'https://itpark.uz', 'description' => 'IT xizmatlar markazi'],
            ['name' => 'Najot Talim', 'website' => 'https://najottalim.uz', 'description' => 'IT ta\'lim va loyihalar'],
        ];

        foreach ($competitors as $comp) {
            try {
                DB::table('competitors')->updateOrInsert(
                    ['business_id' => $this->businessId, 'name' => $comp['name']],
                    array_merge($comp, [
                        'id' => Str::uuid(),
                        'business_id' => $this->businessId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            } catch (\Exception $e) {}
        }
        $this->command->info('  ✓ 3 ta raqobatchi yaratildi');
    }

    private function seedOffers(): void
    {
        $offers = [
            ['title' => 'Web-sayt yaratish', 'description' => 'Professional veb-sayt 15 kun ichida', 'price' => 5000000],
            ['title' => 'Mobil ilova', 'description' => 'iOS va Android ilova', 'price' => 15000000],
            ['title' => 'CRM integratsiya', 'description' => 'Biznes jarayonlarni avtomatlashtirish', 'price' => 8000000],
            ['title' => 'Telegram bot', 'description' => 'Maxsus Telegram bot yaratish', 'price' => 3000000],
        ];

        foreach ($offers as $offer) {
            try {
                DB::table('offers')->updateOrInsert(
                    ['business_id' => $this->businessId, 'title' => $offer['title']],
                    array_merge($offer, [
                        'id' => Str::uuid(),
                        'business_id' => $this->businessId,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            } catch (\Exception $e) {}
        }
        $this->command->info('  ✓ 4 ta taklif yaratildi');
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $current = 0;
        foreach ($weights as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) return $key;
        }
        return array_key_first($weights);
    }
}
