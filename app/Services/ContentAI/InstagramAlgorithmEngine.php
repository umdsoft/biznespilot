<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\InstagramDailyInsight;
use App\Models\InstagramMedia;
use Illuminate\Support\Facades\Log;

/**
 * Instagram Algorithm Engine
 *
 * Instagram algoritmiga mos kontent qoidalarini belgilaydi.
 * 100% ichki algoritm — AI API chaqirilMAYDI.
 *
 * Instagram 2025-2026 algorithmic signals:
 * 1. Save rate (eng muhim signal)
 * 2. Share rate (ikkinchi muhim)
 * 3. First hour engagement
 * 4. Watch time (Reels uchun)
 * 5. Dwell time (Carousel uchun)
 */
class InstagramAlgorithmEngine
{
    // Instagram optimal parametrlar
    private const OPTIMAL_HASHTAGS = ['min' => 5, 'max' => 15, 'ideal' => 8];
    private const OPTIMAL_CAPTION_LENGTH = ['min' => 100, 'max' => 500, 'ideal' => 200];
    private const OPTIMAL_POSTS_PER_WEEK = ['min' => 3, 'max' => 7, 'ideal' => 5];
    private const OPTIMAL_STORIES_PER_DAY = ['min' => 1, 'max' => 7, 'ideal' => 3];
    private const OPTIMAL_REELS_PER_WEEK = ['min' => 2, 'max' => 5, 'ideal' => 3];

    // Content type mix (ideal ratio)
    private const CONTENT_MIX = [
        'reel' => 0.40,     // 40% Reels (eng yuqori reach)
        'carousel' => 0.25, // 25% Carousel (eng yuqori save rate)
        'post' => 0.20,     // 20% Single image
        'story' => 0.15,    // 15% Stories (engagement)
    ];

    // Content purpose mix (80/20 qoidasi)
    private const PURPOSE_MIX = [
        'educational' => 0.35,   // 35% — ta'lim (eng ko'p save)
        'engagement' => 0.25,    // 25% — savol/poll (eng ko'p comment)
        'behind_scenes' => 0.15, // 15% — sahna ortida (ishonch)
        'promotional' => 0.15,   // 15% — reklama (sotish)
        'testimonial' => 0.10,   // 10% — mijoz fikri (social proof)
    ];

    /**
     * Haftalik kontent reja uchun IG-optimized slot tuzilmasini olish
     *
     * @return array<string, array{slots: array, tips: array}>
     */
    public function getWeeklyScheduleTemplate(string $businessId): array
    {
        try {
            $bestTimes = $this->analyzeBestPostingTimes($businessId);
            $bestDays = $this->analyzeBestDays($businessId);
            $contentMix = $this->getOptimalContentMix($businessId);

            $schedule = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            // Haftalik slot taqsimoti
            $weeklySlots = $this->distributeWeeklySlots($contentMix, $bestDays);

            foreach ($days as $day) {
                $schedule[$day] = [
                    'slots' => $weeklySlots[$day] ?? [],
                    'priority' => $bestDays[$day] ?? 50,
                    'stories_target' => self::OPTIMAL_STORIES_PER_DAY['ideal'],
                ];
            }

            return [
                'schedule' => $schedule,
                'best_times' => $bestTimes,
                'content_mix' => $contentMix,
                'tips' => $this->getAlgorithmTips($businessId),
            ];
        } catch (\Throwable $e) {
            Log::error('InstagramAlgorithmEngine: getWeeklySchedule failed', ['error' => $e->getMessage()]);

            return $this->getDefaultSchedule();
        }
    }

    /**
     * Bitta kontent item uchun IG-optimized tavsiyalar
     */
    public function getContentOptimizationTips(string $contentType, string $category): array
    {
        $tips = [
            'caption_rules' => $this->getCaptionRules($contentType),
            'hashtag_rules' => $this->getHashtagRules(),
            'content_type_tips' => $this->getContentTypeTips($contentType),
            'algorithm_signals' => $this->getAlgorithmSignals($contentType),
            'cta_suggestions' => $this->getCTASuggestions($category),
        ];

        return $tips;
    }

    /**
     * Instagram metrikalari asosida eng yaxshi posting vaqtlarini aniqlash
     */
    public function analyzeBestPostingTimes(string $businessId): array
    {
        try {
            $business = Business::withoutGlobalScopes()->find($businessId);
            $accountId = $business?->instagram_account_id;

            if (! $accountId) {
                return $this->getDefaultBestTimes();
            }

            // Oxirgi 60 kundagi postlarni soat bo'yicha guruhlash
            $mediaByHour = InstagramMedia::where('account_id', $accountId)
                ->where('posted_at', '>=', now()->subDays(60))
                ->whereNotNull('engagement_rate')
                ->get()
                ->groupBy(fn ($m) => $m->posted_at->format('H'));

            if ($mediaByHour->isEmpty()) {
                return $this->getDefaultBestTimes();
            }

            $hourlyScores = [];
            foreach ($mediaByHour as $hour => $media) {
                $hourlyScores[$hour] = [
                    'hour' => (int) $hour,
                    'avg_engagement' => round($media->avg('engagement_rate'), 4),
                    'posts_count' => $media->count(),
                    'avg_reach' => (int) round($media->avg('reach')),
                ];
            }

            // Eng yaxshi 3 soatni olish
            uasort($hourlyScores, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

            return [
                'best_hours' => array_slice(array_values($hourlyScores), 0, 3),
                'all_hours' => $hourlyScores,
                'data_source' => 'instagram_media',
                'data_range_days' => 60,
            ];
        } catch (\Throwable $e) {
            Log::error('InstagramAlgorithmEngine: analyzeBestTimes failed', ['error' => $e->getMessage()]);

            return $this->getDefaultBestTimes();
        }
    }

    /**
     * Eng yaxshi kunlarni aniqlash (kun bo'yicha engagement)
     */
    public function analyzeBestDays(string $businessId): array
    {
        try {
            $business = Business::withoutGlobalScopes()->find($businessId);
            $accountId = $business?->instagram_account_id;

            if (! $accountId) {
                return $this->getDefaultBestDays();
            }

            $mediaByDay = InstagramMedia::where('account_id', $accountId)
                ->where('posted_at', '>=', now()->subDays(60))
                ->whereNotNull('engagement_rate')
                ->get()
                ->groupBy(fn ($m) => strtolower($m->posted_at->format('l')));

            if ($mediaByDay->isEmpty()) {
                return $this->getDefaultBestDays();
            }

            $dayScores = [];
            foreach ($mediaByDay as $day => $media) {
                $dayScores[$day] = round($media->avg('engagement_rate') * 10, 0); // 0-100 scale
            }

            return $dayScores;
        } catch (\Throwable $e) {
            return $this->getDefaultBestDays();
        }
    }

    /**
     * Biznes uchun optimal kontent mix ni hisoblash
     */
    public function getOptimalContentMix(string $businessId): array
    {
        try {
            $business = Business::withoutGlobalScopes()->find($businessId);
            $accountId = $business?->instagram_account_id;

            if (! $accountId) {
                return self::CONTENT_MIX;
            }

            // Qaysi tur eng yaxshi natija berganini tekshirish
            $mediaByType = InstagramMedia::where('account_id', $accountId)
                ->where('posted_at', '>=', now()->subDays(90))
                ->whereNotNull('engagement_rate')
                ->get()
                ->groupBy(function ($m) {
                    if ($m->is_reel) {
                        return 'reel';
                    }
                    if ($m->media_type === 'CAROUSEL_ALBUM') {
                        return 'carousel';
                    }
                    if ($m->is_story) {
                        return 'story';
                    }

                    return 'post';
                });

            if ($mediaByType->isEmpty()) {
                return self::CONTENT_MIX;
            }

            // Eng yaxshi natija bergan turga ko'proq joy ajratish
            $typeScores = [];
            $totalScore = 0;
            foreach ($mediaByType as $type => $media) {
                $score = $media->avg('engagement_rate') * $media->count();
                $typeScores[$type] = max($score, 0.01);
                $totalScore += $typeScores[$type];
            }

            // Normalize to 0-1
            $mix = [];
            foreach (self::CONTENT_MIX as $type => $defaultRatio) {
                if (isset($typeScores[$type]) && $totalScore > 0) {
                    // 60% data-driven + 40% default ratio
                    $dataRatio = $typeScores[$type] / $totalScore;
                    $mix[$type] = round(($dataRatio * 0.6) + ($defaultRatio * 0.4), 2);
                } else {
                    $mix[$type] = $defaultRatio;
                }
            }

            // Normalize to sum = 1.0
            $sum = array_sum($mix);
            if ($sum > 0) {
                foreach ($mix as $type => $ratio) {
                    $mix[$type] = round($ratio / $sum, 2);
                }
            }

            return $mix;
        } catch (\Throwable $e) {
            return self::CONTENT_MIX;
        }
    }

    /**
     * Haftalik slotlarni kunlarga taqsimlash
     */
    private function distributeWeeklySlots(array $contentMix, array $dayScores): array
    {
        $totalPosts = self::OPTIMAL_POSTS_PER_WEEK['ideal'];
        $slots = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // Content type pool yaratish
        $typePool = [];
        foreach ($contentMix as $type => $ratio) {
            if ($type === 'story') {
                continue; // Story har kuni alohida
            }
            $count = max(1, round($totalPosts * $ratio));
            for ($i = 0; $i < $count; $i++) {
                $typePool[] = $type;
            }
        }

        // Pool ni to'g'ri hajmga keltirish
        shuffle($typePool);
        $typePool = array_slice($typePool, 0, $totalPosts);

        // Eng yaxshi kunlarga birinchi navbatda tayinlash
        arsort($dayScores);
        $topDays = array_slice(array_keys($dayScores), 0, $totalPosts);

        foreach ($days as $day) {
            $slots[$day] = [];
        }

        $poolIndex = 0;
        foreach ($topDays as $day) {
            if ($poolIndex < count($typePool)) {
                $slots[$day][] = [
                    'content_type' => $typePool[$poolIndex],
                    'time' => $this->getDefaultTimeForType($typePool[$poolIndex]),
                    'purpose' => $this->assignPurpose($poolIndex, $totalPosts),
                ];
                $poolIndex++;
            }
        }

        return $slots;
    }

    private function assignPurpose(int $index, int $total): string
    {
        $purposes = array_keys(self::PURPOSE_MIX);
        $ratios = array_values(self::PURPOSE_MIX);

        $cumulative = 0;
        $position = ($index + 1) / max($total, 1);

        foreach ($ratios as $i => $ratio) {
            $cumulative += $ratio;
            if ($position <= $cumulative) {
                return $purposes[$i];
            }
        }

        return 'educational';
    }

    private function getDefaultTimeForType(string $type): string
    {
        return match ($type) {
            'reel' => '20:00',
            'carousel' => '18:00',
            'story' => '09:00',
            default => '18:00',
        };
    }

    private function getCaptionRules(string $contentType): array
    {
        $rules = [
            'Birinchi qatorni qiziqarli boshlang — savol yoki hayratlanarli raqam bilan',
            'Yozuv uzunligi '.self::OPTIMAL_CAPTION_LENGTH['min'].'-'.self::OPTIMAL_CAPTION_LENGTH['max'].' belgi orasida bo\'lsin',
            'Oxirida harakatga chaqiring — saqlang, ulashing yoki fikr yozing deb so\'rang',
            'Har 2-3 qatorda bo\'sh qator qo\'ying — o\'qish oson bo\'ladi',
        ];

        if ($contentType === 'reel') {
            $rules[] = "Qisqa video ostidagi yozuv qisqa bo'lsin (50-150 belgi). Asosiy ma'no videoning o'zida bo'lsin";
        }

        if ($contentType === 'carousel') {
            $rules[] = "Slaydli postda yozuv uzunroq bo'lishi mumkin (300-500 belgi). Har bir slayd haqida qisqa izoh yozing";
        }

        return $rules;
    }

    private function getHashtagRules(): array
    {
        return [
            'count' => self::OPTIMAL_HASHTAGS,
            'strategy' => [
                '40% — sohangizga oid teglar (aniq auditoriyaga yetadi)',
                '30% — mahalliy teglar (#tashkent, #uzbekistan)',
                '20% — umumiy teglar (ko\'proq odamga ko\'rinadi)',
                '10% — o\'z brendingiz tegi',
            ],
            'placement' => 'Izoh yoki post ostidagi yozuv oxirida',
            'avoid' => 'Taqiqlangan va keraksiz teglardan saqlaning',
        ];
    }

    private function getContentTypeTips(string $contentType): array
    {
        return match ($contentType) {
            'reel' => [
                '15-30 sekund davom etsin — qisqa video ko\'proq odam oxirigacha ko\'radi',
                'Birinchi 3 sekundda e\'tiborni torting — aks holda odamlar o\'tkazib yuboradi',
                "Videoga yozuv qo'shing — ovozini o'chirib ko'radiganlar ham tushunsin",
                "Mashhur musiqadan foydalaning — 3 barobar ko'proq odamga ko'rinadi",
                "Oxirida chaqiring: 'Obuna bo'ling', 'Saqlang', 'Do'stlaringizga ulashing'",
                "Ko'rish vaqti va ulashishlar — Instagramga eng muhim ko'rsatkich",
            ],
            'carousel' => [
                '5-10 ta slayd qiling (eng yaxshisi 7 ta)',
                'Birinchi slayd qiziqarli bo\'lsin — savol yoki hayratlanarli raqam bilan',
                "Oxirgi slaydda harakatga chaqiring: 'Saqlang', 'Do'stlaringizga ulashing'",
                "Bir xil dizayn va o'z brendingiz ranglarini ishlating",
                "Slaydlarni surish va saqlashlar — Instagramga eng muhim ko'rsatkich",
            ],
            'story' => [
                'Kuniga 3-7 ta hikoya joylang',
                "So'rovnoma, viktorina, savol va baholash qo'shing — odamlar ko'proq ishtirok etadi",
                "Ish jarayonini ko'rsating — odamlar ko'proq ishonadi",
                "Javoblar va so'rovnomada ishtirok — Instagramga eng muhim ko'rsatkich",
            ],
            default => [
                'Sifatli rasm ishlating (1080x1080 yoki 1080x1350 o\'lchamda)',
                "Odamlar yuzli rasmlar 38% ko'proq e'tibor oladi",
                "Saqlashlar va izohlar — Instagramga eng muhim ko'rsatkich",
            ],
        };
    }

    private function getAlgorithmSignals(string $contentType): array
    {
        $signals = [
            'saqlash' => ['weight' => 35, 'description' => 'Necha kishi saqlagan — eng muhim ko\'rsatkich'],
            'ulashish' => ['weight' => 25, 'description' => 'Necha kishi do\'stlariga yuborgan — tarqalish imkoniyati'],
            'izohlar' => ['weight' => 20, 'description' => 'Izohlar soni — kontentingiz qanchalik qiziqtirganini ko\'rsatadi'],
            'layklar' => ['weight' => 10, 'description' => 'Yoqtirishlar soni — asosiy e\'tibor ko\'rsatkichi'],
        ];

        if ($contentType === 'reel') {
            $signals['korish_vaqti'] = ['weight' => 30, 'description' => "Videoni oxirigacha ko'rganlar soni — qancha ko'p bo'lsa, shuncha yaxshi"];
            $signals['saqlash']['weight'] = 25;
        }

        return $signals;
    }

    private function getCTASuggestions(string $category): array
    {
        return match ($category) {
            'educational' => [
                "Foydali bo'lsa, saqlang!",
                "Do'stlaringizga ham ulashing!",
                "Qaysi maslahat eng foydali bo'ldi? Izohda yozing!",
            ],
            'engagement' => [
                "Siz qaysi birini tanlaysiz? Izohda yozing!",
                "Fikringizni bildiring!",
                "Do'stingizni belgilang!",
            ],
            'promotional' => [
                "Batafsil ma'lumot uchun xabar yozing!",
                "Profildagi havola orqali buyurtma bering!",
                "Hoziroq bog'laning!",
            ],
            'behind_scenes' => [
                "Yana nima ko'rishni xohlaysiz?",
                "Ish jarayonida yana nima qiziq? Xabar yozing!",
            ],
            default => [
                "Saqlang — kerak bo'ladi!",
                "Do'stlaringizga ulashing!",
            ],
        };
    }

    private function getAlgorithmTips(string $businessId): array
    {
        return [
            'Qisqa videolarni haftada kamida 3 marta joylang — 2-3 barobar ko\'proq odamga ko\'rinadi',
            'Slaydli postlar eng ko\'p saqlanadi — foydali ma\'lumot berishda ishlating',
            "Birinchi 1 soatda qancha odam yoqtirsa, shuncha yaxshi — joylashtirish vaqtini to'g'ri tanlang",
            "Har bir postda aniq chaqiruv bo'lsin: saqlang, ulashing yoki fikr bildiring deb so'rang",
            "Hikoyalarda so'rovnoma va viktorina qo'shsangiz, 15-25% ko'proq odamga ko'rinadi",
            "Mashhur musiqalarni qisqa videolarda ishlating — Instagramning bosh sahifasiga tushish imkoniyati",
            "Teglar 8-12 ta bo'lsin: 40% sohangizga oid, 30% mahalliy, 20% umumiy, 10% brendingiz",
        ];
    }

    private function getDefaultBestTimes(): array
    {
        return [
            'best_hours' => [
                ['hour' => 18, 'avg_engagement' => 0, 'posts_count' => 0],
                ['hour' => 20, 'avg_engagement' => 0, 'posts_count' => 0],
                ['hour' => 12, 'avg_engagement' => 0, 'posts_count' => 0],
            ],
            'data_source' => 'default',
            'data_range_days' => 0,
        ];
    }

    private function getDefaultBestDays(): array
    {
        return [
            'monday' => 60, 'tuesday' => 70, 'wednesday' => 75,
            'thursday' => 80, 'friday' => 65, 'saturday' => 55, 'sunday' => 50,
        ];
    }

    private function getDefaultSchedule(): array
    {
        $schedule = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $types = ['reel', 'post', 'carousel', 'reel', 'post', 'carousel', 'reel'];

        foreach ($days as $i => $day) {
            $schedule[$day] = [
                'slots' => $i < 5 ? [[
                    'content_type' => $types[$i],
                    'time' => $this->getDefaultTimeForType($types[$i]),
                    'purpose' => array_keys(self::PURPOSE_MIX)[$i % count(self::PURPOSE_MIX)],
                ]] : [],
                'priority' => $i < 5 ? 70 : 40,
                'stories_target' => 3,
            ];
        }

        return [
            'schedule' => $schedule,
            'best_times' => $this->getDefaultBestTimes(),
            'content_mix' => self::CONTENT_MIX,
            'tips' => $this->getAlgorithmTips(''),
        ];
    }
}
