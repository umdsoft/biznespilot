<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Lead;
use App\Models\DreamBuyer;
use App\Models\Offer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LeadSeeder extends Seeder
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
            $offer = Offer::where('business_id', $business->id)->first();

            // Create leads with different statuses for funnel analysis
            $this->createLeadsForBusiness($business, $dreamBuyer, $offer);

            $this->command->info("Created leads for: {$business->name}");
        }

        $this->command->info('Lead seeding completed!');
    }

    /**
     * Create leads with various statuses for funnel analysis
     */
    protected function createLeadsForBusiness(Business $business, $dreamBuyer = null, $offer = null): void
    {
        $sources = ['website', 'instagram', 'facebook', 'telegram', 'referral', 'google_ads'];
        $statuses = [
            'new' => 15,           // 15 new leads
            'contacted' => 10,     // 10 contacted
            'qualified' => 8,      // 8 qualified
            'proposal' => 5,       // 5 got proposals
            'negotiation' => 3,    // 3 in negotiation
            'converted' => 2,      // 2 converted (won)
            'lost' => 3,           // 3 lost
        ];

        $leadNames = [
            'Sardor Aliyev', 'Nodira Karimova', 'Botir Rahimov', 'Zilola Tursunova',
            'Jamshid Abdullayev', 'Munisa Narzullayeva', 'Otabek Yusupov', 'Zarina Hasanova',
            'Rustam Ergashev', 'Dilfuza Saidova', 'Aziz Umarov', 'Malika Ibragimova',
            'Davron Sharipov', 'Gulnora Fayzullayeva', 'Akmal Kadirov', 'Sevara Mirzayeva',
            'Timur Rashidov', 'Kamola Nazarova', 'Sherzod Mahmudov', 'Feruza Aminova',
            'Jasur Kholmatov', 'Dildora Ismailova', 'Farrukh Sadikov', 'Nilufar Qodirova',
            'Sanjar Ortikov', 'Zebo Hakimova', 'Ulugbek Karimov', 'Sitora Jurayeva',
            'Bekzod Toshmatov', 'Mokhinur Usmonova', 'Dilshod Asadov', 'Maftuna Rahmonova',
            'Shohruh Normatov', 'Adolat Qosimova', 'Nodir Azimov', 'Hilola Baratova',
            'Jahongir Hamidov', 'Shahnoza Nurmatova', 'Mansur Yuldashev', 'Fotima Sultanova',
            'Ravshan Davlatov', 'Nasiba Gafurova', 'Umid Solijonov', 'Zulfiya Boqiyeva',
            'Abbos Maxmudov', 'Diyora Rakhimova', 'Shavkat Qurbonov', 'Feruza Aminova',
        ];

        $phones = [];
        for ($i = 0; $i < 50; $i++) {
            $phones[] = '+99890' . rand(1000000, 9999999);
        }

        $emailDomains = ['gmail.com', 'mail.ru', 'inbox.uz', 'yandex.ru'];

        $leadIndex = 0;

        foreach ($statuses as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                if ($leadIndex >= count($leadNames)) {
                    $leadIndex = 0; // Reset if we run out of names
                }

                $name = $leadNames[$leadIndex];
                $phone = $phones[$leadIndex];
                $email = strtolower(str_replace(' ', '.', $name)) . '@' . $emailDomains[array_rand($emailDomains)];

                // Generate dates based on status (newer leads are "new", older ones are "converted" or "lost")
                $createdDaysAgo = $this->getDaysAgoByStatus($status);
                $createdAt = Carbon::now()->subDays($createdDaysAgo);

                $leadData = [
                    'uuid' => (string) Str::uuid(),
                    'business_id' => $business->id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'status' => $status === 'converted' ? 'won' : $status,
                    'score' => $this->getScoreByStatus($status),
                    'estimated_value' => $this->getValueByStatus($status, $offer?->pricing ?? 1000000),
                    'notes' => $this->getNotesForStatus($status, $name),
                    'last_contacted_at' => $this->getLastContactDate($status, $createdAt),
                    'data' => json_encode([
                        'dream_buyer_id' => $dreamBuyer?->id,
                        'offer_id' => $offer?->id,
                        'source' => $sources[array_rand($sources)],
                        'next_followup' => $this->getNextFollowupDate($status)?->toDateTimeString(),
                    ]),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                // Add conversion date for won leads
                if ($status === 'converted') {
                    $leadData['converted_at'] = Carbon::now()->subDays(rand(1, 5));
                } elseif ($status === 'lost') {
                    $leadData['notes'] .= "\n\nLost reason: " . $this->getLostReason();
                }

                Lead::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'phone' => $phone,
                    ],
                    $leadData
                );

                $leadIndex++;
            }
        }
    }

    /**
     * Get days ago based on status (older leads have progressed more)
     */
    protected function getDaysAgoByStatus(string $status): int
    {
        $daysMap = [
            'new' => rand(1, 3),
            'contacted' => rand(3, 7),
            'qualified' => rand(7, 14),
            'proposal' => rand(14, 21),
            'negotiation' => rand(21, 30),
            'converted' => rand(30, 60),
            'lost' => rand(15, 45),
        ];

        return $daysMap[$status] ?? rand(1, 30);
    }

    /**
     * Get lead score based on status
     */
    protected function getScoreByStatus(string $status): int
    {
        $scoreMap = [
            'new' => rand(20, 40),
            'contacted' => rand(40, 60),
            'qualified' => rand(60, 80),
            'proposal' => rand(70, 85),
            'negotiation' => rand(80, 95),
            'converted' => rand(90, 100),
            'lost' => rand(10, 50),
        ];

        return $scoreMap[$status] ?? 50;
    }

    /**
     * Get estimated value based on status and offer price
     */
    protected function getValueByStatus(string $status, int $offerPrice): ?float
    {
        if (in_array($status, ['new', 'contacted'])) {
            return null; // No value estimated yet
        }

        $multipliers = [
            'qualified' => 1.0,
            'proposal' => 1.0,
            'negotiation' => 0.9, // Might negotiate lower
            'converted' => 1.0,
            'lost' => 0.0,
        ];

        $multiplier = $multipliers[$status] ?? 1.0;

        return $offerPrice * $multiplier;
    }

    /**
     * Get notes based on status
     */
    protected function getNotesForStatus(string $status, string $name): ?string
    {
        $notesMap = [
            'new' => "{$name} veb-sayt orqali murojaat qildi. Xizmatlar haqida ko'proq ma'lumot so'radi.",
            'contacted' => "Birinchi qo'ng'iroq qilindi. {$name} qiziqdi, batafsil taqdimot so'radi.",
            'qualified' => "Budget tasdiqlandi. Decision maker bilan uchrashish rejalashtirildi.",
            'proposal' => "Taklif yuborildi. {$name} ko'rib chiqmoqda, 1 hafta ichida javob beradi.",
            'negotiation' => "Narx bo'yicha muzokara. {$name} 10-15% chegirma so'rayapti.",
            'converted' => "Shartnoma imzolandi! {$name} mijoz bo'ldi. To'lov qabul qilindi.",
            'lost' => "Yo'qotildi. Boshqa yechim tanladi yoki byudjet yetmadi.",
        ];

        return $notesMap[$status] ?? null;
    }

    /**
     * Get last contact date based on status
     */
    protected function getLastContactDate(string $status, Carbon $createdAt): ?Carbon
    {
        if ($status === 'new') {
            return null;
        }

        return Carbon::now()->subDays(rand(1, 7));
    }

    /**
     * Get next followup date based on status
     */
    protected function getNextFollowupDate(string $status): ?Carbon
    {
        if (in_array($status, ['converted', 'lost'])) {
            return null;
        }

        return Carbon::now()->addDays(rand(1, 5));
    }

    /**
     * Get random lost reason
     */
    protected function getLostReason(): string
    {
        $reasons = [
            'Narx juda yuqori',
            'Boshqa kompaniya tanladi',
            'Byudjet yetmadi',
            'Hozir kerak emas',
            'Javobi yo\'q (ghost)',
            'Raqobatchi arzonroq taklif qildi',
            'Ichki yechim topdi',
            'Loyiha bekor qilindi',
        ];

        return $reasons[array_rand($reasons)];
    }
}
