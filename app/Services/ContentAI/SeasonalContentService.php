<?php

namespace App\Services\ContentAI;

use Carbon\Carbon;

/**
 * Mavsumiy kontent xizmati — O'zbekiston bayramlari va mavsumiy voqealar
 *
 * AI chaqirilMAYDI, DB query yo'q — faqat PHP array va Carbon sana hisoblash.
 * RAM uchun xavfsiz (~5KB).
 */
class SeasonalContentService
{
    /**
     * O'zbekiston bayramlari — prep_days = necha kun oldin tayyorgarlik boshlash
     */
    private const UZBEK_HOLIDAYS = [
        'yangi_yil' => [
            'name' => 'Yangi yil',
            'date' => '01-01',
            'prep_days' => 14,
            'topics' => [
                'Yangi yil aksiyalari va maxsus takliflar',
                'Yilni yakunlash — eng yaxshi natijalar',
                'Yangi yil sovg\'a g\'oyalari',
                'Yangi yil maqsadlari va rejalar',
                'Mijozlarga minnatdorchilik posti',
            ],
            'hooks' => [
                'Yangi yil — YANGI IMKONIYATLAR! 🎄',
                'Yil yakunida BU natijaga erishdik...',
                'Yangi yilga MAXSUS taklif — faqat 31-dekabrgacha!',
            ],
        ],
        '8_mart' => [
            'name' => 'Xalqaro xotin-qizlar kuni',
            'date' => '03-08',
            'prep_days' => 10,
            'topics' => [
                '8-Mart sovg\'a g\'oyalari',
                'Ayollar kuni maxsus aksiya',
                'Biznesda muvaffaqiyatli ayollar hikoyasi',
                'Mijoz ayollarga tabrik va minnatdorchilik',
                'Ayollar uchun maxsus xizmatlar/mahsulotlar',
            ],
            'hooks' => [
                'Ayollar uchun ENG YAXSHI sovg\'a — bu...',
                '8-Mart MAXSUS: faqat 3 kun!',
                'Biznesimiz muvaffaqiyati ortida turgan ayollar',
            ],
        ],
        'navroz' => [
            'name' => 'Navro\'z bayrami',
            'date' => '03-21',
            'prep_days' => 10,
            'topics' => [
                'Navro\'z bayram aksiyalari',
                'Navro\'z an\'analari va biznes',
                'Bahor — yangilanish fasli, biznesda ham!',
                'Navro\'z uchun maxsus taklif',
                'Sumalak va an\'anaviy qadriyatlar — brand hikoyasi',
            ],
            'hooks' => [
                'Navro\'z MUBORAK! Va sizga MAXSUS taklif...',
                'Bahor keldi — biznesingiz ham yangilansin!',
                'Navro\'z — 5 kun dam olish. Lekin avval BU taklifni ko\'ring',
            ],
        ],
        'xotira_kuni' => [
            'name' => 'Xotira va qadrlash kuni',
            'date' => '05-09',
            'prep_days' => 5,
            'topics' => [
                'Xotira kuni — qadriyatlar haqida',
                'Oila va vatanga hurmat',
                'Biznesda qadriyatlar va mas\'uliyat',
            ],
            'hooks' => [
                'Bugun biz eslash va qadrlash kunimiz...',
                'Qadriyatlar — biznesning ham asosi',
            ],
        ],
        'mustaqillik' => [
            'name' => 'Mustaqillik kuni',
            'date' => '09-01',
            'prep_days' => 7,
            'topics' => [
                'Mustaqillik kuni tabriklari',
                'O\'zbekiston iqtisodiyoti va biznes',
                'Mahalliy brendlar — faxrimiz',
                'Mustaqillik kuni aksiyalari',
            ],
            'hooks' => [
                'Mustaqillik kuni MUBORAK! 🇺🇿',
                'O\'zbek brendi sifatida biz faxrlanamiz...',
                'Mustaqillik — bu ERKINLIK. Biznesingiz ham erkin bo\'lsin!',
            ],
        ],
        'oqituvchilar_kuni' => [
            'name' => 'O\'qituvchilar kuni',
            'date' => '10-01',
            'prep_days' => 5,
            'topics' => [
                'O\'qituvchilar kuniga maxsus taklif',
                'Ustozlarimizga minnatdorchilik',
                'Ta\'lim sohasida biznesimiz hissasi',
            ],
            'hooks' => [
                'Ustozlarimizga RAHMAT! Bugun maxsus taklif...',
                'O\'qituvchilar kuni — bilim kuchi haqida',
            ],
        ],
        'konstitutsiya_kuni' => [
            'name' => 'Konstitutsiya kuni',
            'date' => '12-08',
            'prep_days' => 3,
            'topics' => [
                'Konstitutsiya kuni tabriklari',
                'Huquqiy biznes yuritish haqida',
                'Yil oxiri rejalar va maqsadlar',
            ],
            'hooks' => [
                'Konstitutsiya kuni muborak!',
                'Qonun doirasida — kuchli biznes!',
            ],
        ],
    ];

    /**
     * Mavsumiy voqealar — sana oraliqli
     */
    private const SEASONAL_EVENTS = [
        'maktab_sezoni' => [
            'name' => 'Maktab sezoni',
            'months' => [8, 9],
            'topics' => [
                'Maktabga tayyorgarlik aksiyalari',
                'Bolalar va o\'quvchilar uchun maxsus',
                'Ota-onalar uchun maslahatlar',
                'Yangi o\'quv yili — yangi boshlanish',
            ],
            'hooks' => [
                'Maktab boshlanmoqda! Tayyormisiz?',
                'Ota-onalar DIQQATIGA: maktabga tayyorgarlik...',
            ],
        ],
        'toy_fasli' => [
            'name' => 'To\'y fasli',
            'months' => [5, 6, 7, 8],
            'topics' => [
                'To\'y mavsumi maxsus takliflari',
                'To\'yga sovg\'a g\'oyalari',
                'To\'y tayyorligi maslahatlar',
                'Yozgi bayramlar uchun xizmatlar',
            ],
            'hooks' => [
                'To\'y mavsumi boshlandi! Maxsus narxlar...',
                'To\'yga tayyorgarlik — BU ro\'yxatni saqlang!',
            ],
        ],
        'yoz_tatil' => [
            'name' => 'Yozgi ta\'til',
            'months' => [6, 7, 8],
            'topics' => [
                'Yozgi aksiyalar va chegirmalar',
                'Dam olish vaqtida foydali kontentlar',
                'Yozgi trend mahsulotlar/xizmatlar',
            ],
            'hooks' => [
                'Yoz keldi — MAXSUS takliflar boshlandi!',
                'Dam olayotganmisiz? Bu taklifni o\'tkazib yubormang!',
            ],
        ],
        'qish_fasli' => [
            'name' => 'Qish fasli',
            'months' => [12, 1, 2],
            'topics' => [
                'Qishki maxsus takliflar',
                'Sovuq kunlarda issiq takliflar',
                'Yil oxiri chegirmalar',
                'Yangi yilga tayyorgarlik',
            ],
            'hooks' => [
                'Sovuq tashqarida — ISSIQ takliflar bizda!',
                'Qish chegirmasi: faqat bu hafta!',
            ],
        ],
        'bahor_fasli' => [
            'name' => 'Bahor fasli',
            'months' => [3, 4],
            'topics' => [
                'Bahorgi yangilanish aksiyalari',
                'Yangi fasl — yangi imkoniyatlar',
                'Bahorgi trend mahsulotlar',
            ],
            'hooks' => [
                'Bahor keldi — biznesingiz ham gullaysin!',
                'Yangilanish vaqti keldi!',
            ],
        ],
    ];

    /**
     * Hafta kunlari temalari — kontentni xilma-xil qilish
     */
    private const WEEKLY_THEMES = [
        1 => 'Motivatsion dushanba — hafta boshiga ilhom va energiya',
        2 => 'Maslahat seshanba — foydali tip va trik',
        3 => 'Chorshanba hikoya — mijoz yoki shaxsiy tajriba',
        4 => 'Payshanba natija — raqamlar va muvaffaqiyatlar',
        5 => 'Juma aksiya — maxsus takliflar',
        6 => 'Shanba behind the scenes — ish jarayoni',
        0 => 'Yakshanba dam olish — yengil va ko\'ngilochar kontent',
    ];

    /**
     * Hozirgi sanaga tegishli mavsumiy topiclarni qaytaradi
     *
     * @param string $industryCode Biznes sohasi
     * @param Carbon $date Hozirgi sana
     * @param int $limit Nechta topic qaytarish
     * @return array [{topic, source, seasonal_event, hooks, total_score}]
     */
    public function getRelevantTopics(string $industryCode, Carbon $date, int $limit = 3): array
    {
        $topics = [];

        // 1. Bayramlarni tekshirish
        foreach (self::UZBEK_HOLIDAYS as $key => $holiday) {
            $holidayDate = Carbon::createFromFormat('m-d', $holiday['date'])->year($date->year);

            // Agar bayram o'tgan bo'lsa va 2 kundan ko'p bo'lsa — keyingi yilga
            $daysDiff = $date->diffInDays($holidayDate, false);

            if ($daysDiff < -2) {
                // Bayram o'tdi, keyingi yilga
                $holidayDate->addYear();
                $daysDiff = $date->diffInDays($holidayDate, false);
            }

            // prep_days oralig'ida yoki bayram kunida
            if ($daysDiff >= -2 && $daysDiff <= $holiday['prep_days']) {
                $relevance = $daysDiff <= 0 ? 100 : max(60, 100 - ($daysDiff * 5));

                foreach (array_slice($holiday['topics'], 0, 2) as $topicText) {
                    $topics[] = [
                        'topic' => $topicText,
                        'source' => 'seasonal',
                        'seasonal_event' => $holiday['name'],
                        'hooks' => $holiday['hooks'],
                        'total_score' => $relevance,
                    ];
                }
            }
        }

        // 2. Mavsumiy voqealarni tekshirish
        $currentMonth = $date->month;
        foreach (self::SEASONAL_EVENTS as $key => $event) {
            if (in_array($currentMonth, $event['months'])) {
                foreach (array_slice($event['topics'], 0, 1) as $topicText) {
                    $topics[] = [
                        'topic' => $topicText,
                        'source' => 'seasonal',
                        'seasonal_event' => $event['name'],
                        'hooks' => $event['hooks'],
                        'total_score' => 70,
                    ];
                }
            }
        }

        // Score bo'yicha saralash
        usort($topics, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        return array_slice($topics, 0, $limit);
    }

    /**
     * Bugungi hafta kuni temasini olish
     */
    public function getWeeklyTheme(Carbon $date): string
    {
        return self::WEEKLY_THEMES[$date->dayOfWeek] ?? 'Umumiy kontent';
    }
}
