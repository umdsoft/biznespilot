<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * O'zbekiston bayramlari va mavsumiy voqealar.
 * php artisan db:seed --class=LocalCalendarSeeder
 */
class LocalCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'event_name' => 'Yangi yil',
                'event_type' => 'commercial',
                'fixed_date' => '01-01',
                'preparation_days' => 21,
                'impact_industries' => ['retail', 'food', 'beauty', 'entertainment', 'education'],
                'impact_description' => 'Yilning eng katta savdo davri, 4-7x o\'sish. Sovg\'a, bayram kiyimi, oziq-ovqat.',
            ],
            [
                'event_name' => 'Vatanni himoya qiluvchilar kuni',
                'event_type' => 'national_holiday',
                'fixed_date' => '01-14',
                'preparation_days' => 7,
                'impact_industries' => ['retail', 'flowers', 'restaurants'],
                'impact_description' => 'Erkaklar uchun sovg\'a sotuvlari oshadi.',
            ],
            [
                'event_name' => 'Xalqaro xotin-qizlar kuni',
                'event_type' => 'national_holiday',
                'fixed_date' => '03-08',
                'preparation_days' => 14,
                'impact_industries' => ['beauty', 'retail', 'flowers', 'restaurants', 'jewelry'],
                'impact_description' => 'Sovg\'a sotuvlari 3-5x oshadi, restoran bron 2x, gul va parfyumeriya.',
            ],
            [
                'event_name' => 'Navruz',
                'event_type' => 'national_holiday',
                'fixed_date' => '03-21',
                'preparation_days' => 14,
                'impact_industries' => ['food', 'retail', 'tourism', 'education', 'clothing'],
                'impact_description' => 'Bayram oldi savdo, oilaviy xaridlar, sumalyak tayyorlash. Katta bayram.',
            ],
            [
                'event_name' => 'Ro\'za hayit',
                'event_type' => 'religious',
                'fixed_date' => null,
                'is_lunar' => true,
                'typical_month' => 4,
                'preparation_days' => 14,
                'impact_industries' => ['food', 'retail', 'clothing', 'beauty'],
                'impact_description' => 'Hayit oldi kiyim va oziq-ovqat sotuvlari 2-3x. Sovg\'a almashinuvi.',
            ],
            [
                'event_name' => 'Xotira va qadrlash kuni',
                'event_type' => 'national_holiday',
                'fixed_date' => '05-09',
                'preparation_days' => 7,
                'impact_industries' => ['flowers', 'retail'],
                'impact_description' => 'Gul va esdalik sovg\'alar sotuvlari.',
            ],
            [
                'event_name' => 'Qurbon hayit',
                'event_type' => 'religious',
                'fixed_date' => null,
                'is_lunar' => true,
                'typical_month' => 6,
                'preparation_days' => 14,
                'impact_industries' => ['food', 'retail', 'clothing', 'tourism', 'livestock'],
                'impact_description' => 'Qurbonlik xarajatlari, sayohat, kiyim-kechak sotuvlari oshadi.',
            ],
            [
                'event_name' => 'Mustaqillik kuni',
                'event_type' => 'national_holiday',
                'fixed_date' => '09-01',
                'preparation_days' => 14,
                'impact_industries' => ['retail', 'education', 'stationery', 'clothing'],
                'impact_description' => 'Maktab tayyorgarligi, bayram savdolari. Ta\'lim sohasi uchun eng katta davr.',
            ],
            [
                'event_name' => 'Bilimlar kuni',
                'event_type' => 'education',
                'fixed_date' => '09-01',
                'preparation_days' => 21,
                'impact_industries' => ['education', 'retail', 'stationery', 'it_courses'],
                'impact_description' => 'Maktab va kurs ro\'yxatga olish 5x oshadi. Kantstovari, forma sotuvlari.',
            ],
            [
                'event_name' => 'O\'qituvchilar kuni',
                'event_type' => 'national_holiday',
                'fixed_date' => '10-01',
                'preparation_days' => 7,
                'impact_industries' => ['education', 'retail', 'flowers', 'gifts'],
                'impact_description' => 'Sovg\'a sotuvlari, ta\'lim sohasi aksiyalari.',
            ],
            [
                'event_name' => 'Konstitutsiya kuni',
                'event_type' => 'national_holiday',
                'fixed_date' => '12-08',
                'preparation_days' => 7,
                'impact_industries' => ['retail'],
                'impact_description' => 'Bayram chegirmalari va aksiyalar.',
            ],
            [
                'event_name' => 'Yangi yil oldi savdo mavsumi',
                'event_type' => 'commercial',
                'fixed_date' => '12-15',
                'preparation_days' => 14,
                'impact_industries' => ['retail', 'food', 'beauty', 'entertainment', 'electronics'],
                'impact_description' => 'Eng faol savdo davri. Black Friday uslubidagi chegirmalar, sovg\'a xaridlari.',
            ],
        ];

        foreach ($events as $event) {
            DB::table('local_calendar')->insert([
                'id' => Str::uuid()->toString(),
                'event_name' => $event['event_name'],
                'event_type' => $event['event_type'],
                'fixed_date' => $event['fixed_date'] ?? null,
                'is_lunar' => $event['is_lunar'] ?? false,
                'typical_month' => $event['typical_month'] ?? null,
                'year_date' => null,
                'preparation_days' => $event['preparation_days'],
                'impact_industries' => json_encode($event['impact_industries']),
                'impact_description' => $event['impact_description'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
