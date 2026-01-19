<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\CompetitorInsight;
use App\Models\DreamBuyer;
use App\Models\Offer;
use Illuminate\Support\Collection;

/**
 * CompetitorInsightsService - Biznes egasi uchun oddiy va tushunarli tavsiyalar
 *
 * Maqsad: Biznes egasi (marketolog bo'lmagan odam) uchun
 * "Bugun nima qilishim kerak?" savoliga aniq javob berish
 */
class CompetitorInsightsService
{
    public const TYPE_PRICE = 'price';
    public const TYPE_MARKETING = 'marketing';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_OPPORTUNITY = 'opportunity';
    public const TYPE_THREAT = 'threat';
    public const TYPE_SALES_SCRIPT = 'sales_script';
    public const TYPE_POSITIONING = 'positioning';
    public const TYPE_CONTENT = 'content';

    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    /**
     * Asosiy metod - tavsiyalar generatsiya qilish
     */
    public function generateInsights(Business $business): array
    {
        $competitors = $this->getCompetitors($business);
        $dreamBuyer = $this->getDreamBuyer($business);
        $offers = $this->getOffers($business);

        if ($competitors->isEmpty()) {
            return [
                'insights' => [],
                'summary' => [
                    'total_insights' => 0,
                    'headline' => 'Avval raqobatchilarni qo\'shing',
                ],
                'action_items' => [],
            ];
        }

        $insights = collect();

        // 1. Eng muhim - Raqobatchilarga qarshi ustunliklar
        $insights = $insights->merge($this->findCompetitiveAdvantages($business, $competitors));

        // 2. Narx imkoniyatlari
        $insights = $insights->merge($this->analyzePrices($business, $competitors));

        // 3. Marketing imkoniyatlari (oddiy)
        $insights = $insights->merge($this->findMarketingOpportunities($business, $competitors));

        // 4. Ogohlantirishlar
        $insights = $insights->merge($this->findThreats($business, $competitors));

        // 5. Kontent g'oyalari (agar DreamBuyer bo'lsa)
        if ($dreamBuyer) {
            $insights = $insights->merge($this->generateContentIdeas($business, $dreamBuyer));
        }

        // Duplikatlarni olib tashlash
        $uniqueInsights = $this->removeDuplicates($insights);

        // Muhimlik bo'yicha tartiblash
        $sortedInsights = $this->sortInsights($uniqueInsights);

        // Saqlash
        $this->saveInsights($business, $sortedInsights);

        // Sotuv skriptlari
        $salesScripts = $this->generateSalesScripts($business, $competitors, $dreamBuyer);

        return [
            'insights' => $sortedInsights->toArray(),
            'summary' => $this->createSummary($sortedInsights, $competitors->count()),
            'action_items' => $this->getTopActions($sortedInsights),
            'sales_scripts' => $salesScripts,
            'competitors_count' => $competitors->count(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Raqobatchilarga qarshi ustunliklarni topish
     */
    protected function findCompetitiveAdvantages(Business $business, Collection $competitors): Collection
    {
        $insights = collect();
        $businessSwot = $business->swot_data ?? [];
        $ourStrengths = $businessSwot['strengths'] ?? [];

        foreach ($competitors as $competitor) {
            $compSwot = $competitor->swot_data ?? [];
            $compWeaknesses = $compSwot['weaknesses'] ?? $competitor->weaknesses ?? [];

            if (empty($compWeaknesses)) {
                continue;
            }

            // Har bir raqobatchi zaif tomoniga qarang
            foreach ($compWeaknesses as $weakness) {
                // Bizning kuchli tomonimiz bilan solishtiramiz
                $ourAdvantage = $this->matchStrengthToWeakness($weakness, $ourStrengths);

                if ($ourAdvantage) {
                    $insights->push([
                        'type' => self::TYPE_POSITIONING,
                        'priority' => self::PRIORITY_HIGH,
                        'title' => sprintf('"%s" ustunligingizni reklama qiling', $ourAdvantage),
                        'competitor' => $competitor->name,
                        'description' => sprintf(
                            '%s mijozlari "%s" deb shikoyat qilishadi. Sizda esa "%s" bor - bu katta ustunlik!',
                            $competitor->name,
                            $weakness,
                            $ourAdvantage
                        ),
                        'recommendation' => 'Bu ustunlikni reklamada va sotuvda ta\'kidlang',
                        'action' => [
                            'text' => 'Quyidagilarni qiling:',
                            'steps' => [
                                sprintf('Instagram bio ga "%s" yozing', $ourAdvantage),
                                'Shu haqda post yoki video qiling',
                                'Sotuvchilaringiz har bir suhbatda aytsin',
                            ],
                        ],
                        'data' => [
                            'competitor_weakness' => $weakness,
                            'our_strength' => $ourAdvantage,
                        ],
                    ]);
                } else {
                    // Umumiy imkoniyat
                    $insights->push([
                        'type' => self::TYPE_OPPORTUNITY,
                        'priority' => self::PRIORITY_MEDIUM,
                        'title' => sprintf('%s ning zaif tomoni - sizga imkoniyat', $competitor->name),
                        'competitor' => $competitor->name,
                        'description' => sprintf(
                            '%s mijozlari "%s" deb norozilik bildiradi. Agar sizda bu muammo yo\'q bo\'lsa - reklama qiling!',
                            $competitor->name,
                            $weakness
                        ),
                        'recommendation' => 'Bu sohada o\'zingizni yaxshiroq ko\'rsating',
                        'action' => [
                            'text' => sprintf('"%s" muammosini siz qanday hal qilasiz? Shu haqda gapiring.', $weakness),
                        ],
                        'data' => ['competitor_weakness' => $weakness],
                    ]);
                }
            }
        }

        return $insights;
    }

    /**
     * Narxlarni tahlil qilish
     */
    protected function analyzePrices(Business $business, Collection $competitors): Collection
    {
        $insights = collect();
        $ourPrice = $this->getBusinessPrice($business);

        if (!$ourPrice) {
            return $insights;
        }

        $cheaperThan = [];
        $moreExpensiveThan = [];

        foreach ($competitors as $competitor) {
            $compPrice = $competitor->pricing['average']
                ?? $competitor->pricing['min']
                ?? $competitor->price_range
                ?? null;

            if (!$compPrice || $compPrice <= 0) {
                continue;
            }

            $diff = (($ourPrice - $compPrice) / $compPrice) * 100;

            if ($diff < -10) {
                // Biz arzonroqmiz
                $cheaperThan[] = [
                    'name' => $competitor->name,
                    'diff' => abs(round($diff)),
                    'their_price' => $compPrice,
                ];
            } elseif ($diff > 15) {
                // Biz qimmatroqmiz
                $moreExpensiveThan[] = [
                    'name' => $competitor->name,
                    'diff' => round($diff),
                    'their_price' => $compPrice,
                ];
            }
        }

        // Agar arzonroq bo'lsak
        if (!empty($cheaperThan)) {
            $names = array_column($cheaperThan, 'name');
            $avgDiff = round(array_sum(array_column($cheaperThan, 'diff')) / count($cheaperThan));

            $insights->push([
                'type' => self::TYPE_PRICE,
                'priority' => self::PRIORITY_HIGH,
                'title' => 'Narx ustunligingiz bor - foydalaning!',
                'competitor' => implode(', ', array_slice($names, 0, 2)),
                'description' => sprintf(
                    'Siz %s dan o\'rtacha %d%% arzonroqsiz. Bu katta ustunlik - reklama qiling!',
                    implode(', ', array_slice($names, 0, 2)),
                    $avgDiff
                ),
                'recommendation' => 'Narx ustunligini reklamada ishlatilsin',
                'action' => [
                    'text' => 'Quyidagi xabarlarni reklamada ishlatilsin:',
                    'ad_examples' => [
                        sprintf('Xuddi shu sifat - %d%% arzon!', $avgDiff),
                        'Nega ko\'proq to\'laysiz?',
                        sprintf('Eng qulay narx - %s so\'m', number_format($ourPrice, 0, '.', ' ')),
                    ],
                ],
                'data' => ['cheaper_than' => $cheaperThan],
            ]);
        }

        // Agar qimmatroq bo'lsak
        if (!empty($moreExpensiveThan)) {
            $avgDiff = round(array_sum(array_column($moreExpensiveThan, 'diff')) / count($moreExpensiveThan));

            $insights->push([
                'type' => self::TYPE_THREAT,
                'priority' => $avgDiff > 30 ? self::PRIORITY_HIGH : self::PRIORITY_MEDIUM,
                'title' => sprintf('Narxingiz raqobatchilardan %d%% yuqori', $avgDiff),
                'competitor' => $moreExpensiveThan[0]['name'],
                'description' => sprintf(
                    'Mijozlar boshqalardan arzonroq topishi mumkin. Nima uchun sizni tanlashlari kerakligini tushuntiring.',
                    $avgDiff
                ),
                'recommendation' => 'Qimmatroq ekanligizni asoslang yoki narxni ko\'rib chiqing',
                'action' => [
                    'text' => 'Ikki variantdan birini tanlang:',
                    'options' => [
                        [
                            'title' => '1. Qiymat qo\'shing',
                            'description' => 'Kafolat, bepul yetkazish, qo\'shimcha xizmat',
                        ],
                        [
                            'title' => '2. Narxni optimallashtiring',
                            'description' => 'Chegirmalar, paketli takliflar',
                        ],
                    ],
                ],
                'data' => ['more_expensive_than' => $moreExpensiveThan],
            ]);
        }

        return $insights;
    }

    /**
     * Marketing imkoniyatlarini topish
     */
    protected function findMarketingOpportunities(Business $business, Collection $competitors): Collection
    {
        $insights = collect();

        // Qaysi kanallarda raqobatchilar yo'q
        $competitorChannels = [];
        foreach ($competitors as $competitor) {
            $latestMetric = $competitor->metrics->first();

            if ($competitor->instagram_handle || ($latestMetric && $latestMetric->instagram_followers > 0)) {
                $competitorChannels['instagram'] = true;
            }
            if ($competitor->telegram_handle || ($latestMetric && $latestMetric->telegram_members > 0)) {
                $competitorChannels['telegram'] = true;
            }
            if ($competitor->tiktok_handle) {
                $competitorChannels['tiktok'] = true;
            }
        }

        // TikTok bo'sh
        if (!isset($competitorChannels['tiktok'])) {
            $insights->push([
                'type' => self::TYPE_OPPORTUNITY,
                'priority' => self::PRIORITY_HIGH,
                'title' => 'TikTok da birinchi bo\'ling!',
                'competitor' => null,
                'description' => 'Raqobatchilaringizning hech biri TikTokda yo\'q. Bu katta imkoniyat - birinchi bo\'lib oching!',
                'recommendation' => 'TikTok akkaunt oching va kontent boshlang',
                'action' => [
                    'text' => 'Boshlash rejasi:',
                    'steps' => [
                        'Bugun: TikTok biznes akkaunt oching',
                        'Ertaga: 3 ta oddiy video yozib oling',
                        'Har kuni: 1 ta video joylashtiring',
                    ],
                    'video_ideas' => [
                        'Mahsulotingizni 15 sekundda ko\'rsating',
                        'Mijoz savollariga javob bering',
                        'Ish jarayonini ko\'rsating',
                    ],
                ],
                'data' => [],
            ]);
        }

        // Telegram bo'sh (agar raqobatchilar bo'lsa)
        if (isset($competitorChannels['telegram']) && !$business->telegram_members) {
            $insights->push([
                'type' => self::TYPE_OPPORTUNITY,
                'priority' => self::PRIORITY_MEDIUM,
                'title' => 'Telegram kanal oching',
                'competitor' => null,
                'description' => 'Raqobatchilaringiz Telegramda mijozlar bilan bog\'lanishmoqda. Siz ham o\'sha yerda bo\'lishingiz kerak.',
                'recommendation' => 'Telegram kanal oching',
                'action' => [
                    'text' => 'Oddiy qadamlar:',
                    'steps' => [
                        '1. Telegram kanal oching',
                        '2. Mavjud mijozlaringizga link yuboring',
                        '3. Har kuni 1-2 ta foydali post qiling',
                    ],
                ],
                'data' => [],
            ]);
        }

        return $insights;
    }

    /**
     * Tahdidlarni topish
     */
    protected function findThreats(Business $business, Collection $competitors): Collection
    {
        $insights = collect();

        foreach ($competitors as $competitor) {
            // Kritik raqobatchi
            if ($competitor->threat_level === 'critical') {
                $insights->push([
                    'type' => self::TYPE_THREAT,
                    'priority' => self::PRIORITY_HIGH,
                    'title' => sprintf('%s - kuchli raqobatchi, kuzatib boring', $competitor->name),
                    'competitor' => $competitor->name,
                    'description' => sprintf(
                        '%s sizning asosiy raqobatchingiz. Ular nima qilayotganini kuzatib boring va tezda javob bering.',
                        $competitor->name
                    ),
                    'recommendation' => 'Haftalik monitoring qiling',
                    'action' => [
                        'text' => 'Har hafta tekshiring:',
                        'checklist' => [
                            'Yangi narxlar va aksiyalar',
                            'Yangi mahsulot/xizmatlar',
                            'Instagram/Telegram yangiliklari',
                        ],
                    ],
                    'data' => ['threat_level' => 'critical'],
                ]);
            }

            // Tez o'sish
            $metrics = $competitor->metrics;
            if ($metrics->count() >= 2) {
                $latest = $metrics->first();
                $previous = $metrics->skip(7)->first() ?? $metrics->last();

                if ($latest && $previous && $previous->instagram_followers > 100) {
                    $growth = (($latest->instagram_followers - $previous->instagram_followers) / $previous->instagram_followers) * 100;

                    if ($growth > 15) {
                        $insights->push([
                            'type' => self::TYPE_THREAT,
                            'priority' => self::PRIORITY_MEDIUM,
                            'title' => sprintf('%s tez o\'smoqda', $competitor->name),
                            'competitor' => $competitor->name,
                            'description' => sprintf(
                                '%s ning auditoriyasi %.0f%% o\'sdi. Nimani to\'g\'ri qilishyapti? O\'rganib oling!',
                                $competitor->name,
                                $growth
                            ),
                            'recommendation' => 'Ularning postlarini ko\'rib chiqing',
                            'action' => [
                                'text' => sprintf('%s Instagram sahifasiga kiring va oxirgi 10 ta postini ko\'ring. Nima qilyapti?', $competitor->name),
                            ],
                            'data' => ['growth' => round($growth)],
                        ]);
                    }
                }
            }
        }

        return $insights;
    }

    /**
     * Kontent g'oyalari (DreamBuyer asosida)
     */
    protected function generateContentIdeas(Business $business, DreamBuyer $dreamBuyer): Collection
    {
        $insights = collect();

        $painPoints = $dreamBuyer->pain_points ?? [];
        $desires = $dreamBuyer->desires ?? [];

        if (empty($painPoints) && empty($desires)) {
            return $insights;
        }

        $contentIdeas = [];

        // Muammolardan kontent
        foreach (array_slice($painPoints, 0, 3) as $pain) {
            $contentIdeas[] = [
                'topic' => $pain,
                'idea' => sprintf('"%s" muammosini qanday hal qilasiz - video yoki post qiling', $pain),
            ];
        }

        // Xohishlardan kontent
        foreach (array_slice($desires, 0, 2) as $desire) {
            $contentIdeas[] = [
                'topic' => $desire,
                'idea' => sprintf('Qanday qilib "%s"ga erishish mumkin - maslahat bering', $desire),
            ];
        }

        if (!empty($contentIdeas)) {
            $insights->push([
                'type' => self::TYPE_CONTENT,
                'priority' => self::PRIORITY_MEDIUM,
                'title' => sprintf('%d ta kontent g\'oya tayyor', count($contentIdeas)),
                'competitor' => null,
                'description' => 'Ideal mijozingiz uchun maxsus kontent g\'oyalari. Shu haftada 2-3 tasini qiling.',
                'recommendation' => 'Har hafta 2-3 ta kontent joylashtiring',
                'action' => [
                    'text' => 'Quyidagi mavzularda post/video qiling:',
                    'content_ideas' => $contentIdeas,
                ],
                'data' => [
                    'ideas_count' => count($contentIdeas),
                    'based_on' => 'dream_buyer',
                ],
            ]);
        }

        return $insights;
    }

    /**
     * Sotuv skriptlari
     */
    public function generateSalesScripts(Business $business, Collection $competitors, ?DreamBuyer $dreamBuyer = null): array
    {
        $scripts = [];
        $ourPrice = $this->getBusinessPrice($business);

        foreach ($competitors as $competitor) {
            $compSwot = $competitor->swot_data ?? [];
            $weaknesses = $compSwot['weaknesses'] ?? $competitor->weaknesses ?? [];
            $compPrice = $competitor->pricing['average'] ?? null;

            if (empty($weaknesses) && !$compPrice) {
                continue;
            }

            $script = [
                'competitor' => $competitor->name,
                'trigger' => sprintf('Mijoz "%s" haqida gapirganda', $competitor->name),
                'response' => sprintf(
                    'Ha, %s ham yaxshi tanlov. Lekin ko\'p mijozlarimiz avval ularga borgan, keyin bizga o\'tishgan. Sababi...',
                    $competitor->name
                ),
                'points' => [],
                'closing' => 'Sizga qaysi biri muhimroq? Shu asosida tanlay olasiz.',
            ];

            // Zaif tomonlardan foydalanish
            foreach (array_slice($weaknesses, 0, 2) as $weakness) {
                $script['points'][] = [
                    'their_weakness' => $weakness,
                    'our_advantage' => $this->createAdvantageResponse($weakness),
                ];
            }

            // Narx ustunligi
            if ($ourPrice && $compPrice && $ourPrice < $compPrice) {
                $diff = round((($compPrice - $ourPrice) / $compPrice) * 100);
                $script['price_point'] = [
                    'text' => sprintf('Bundan tashqari, biz %d%% tejamkormiz', $diff),
                    'diff' => $diff,
                ];
            }

            $scripts[] = $script;
        }

        // Umumiy e'tirozlar (agar DreamBuyer bo'lsa)
        if ($dreamBuyer) {
            $objections = $dreamBuyer->objections ?? [];
            if (!empty($objections)) {
                $objectionScripts = [];
                foreach (array_slice($objections, 0, 5) as $objection) {
                    $objectionScripts[] = [
                        'objection' => $objection,
                        'response' => $this->createObjectionResponse($objection),
                    ];
                }
                // E'tirozlarni alohida array sifatida qo'shamiz, lekin indexed array ni buzmaymiz
                if (!empty($objectionScripts)) {
                    $scripts[] = [
                        'competitor' => 'Umumiy e\'tirozlar',
                        'trigger' => 'Mijoz e\'tiroz bildirganida',
                        'response' => 'Quyidagi javoblardan foydalaning',
                        'points' => array_map(fn($o) => [
                            'their_weakness' => $o['objection'],
                            'our_advantage' => $o['response'],
                        ], $objectionScripts),
                        'closing' => 'Tushunaman. Yana qanday savollaringiz bor?',
                    ];
                }
            }
        }

        return $scripts;
    }

    // ===== HELPER METODLAR =====

    protected function getCompetitors(Business $business): Collection
    {
        return Competitor::where('business_id', $business->id)
            ->where('status', 'active')
            ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(14)])
            ->get();
    }

    protected function getDreamBuyer(Business $business): ?DreamBuyer
    {
        return DreamBuyer::where('business_id', $business->id)
            ->where('is_primary', true)
            ->first()
            ?? DreamBuyer::where('business_id', $business->id)->first();
    }

    protected function getOffers(Business $business): Collection
    {
        return Offer::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();
    }

    protected function getBusinessPrice(Business $business): ?float
    {
        return $business->average_price ?? $business->price_range_max ?? null;
    }

    protected function matchStrengthToWeakness(string $weakness, array $strengths): ?string
    {
        $mappings = [
            'qimmat' => ['arzon', 'qulay narx', 'tejamkor'],
            'narx' => ['arzon', 'qulay narx', 'tejamkor'],
            'sifat' => ['sifatli', 'yuqori sifat', 'premium'],
            'sekin' => ['tez', 'operativ', 'tezkor'],
            'xizmat' => ['xizmat', 'mijoz', 'yordamchi'],
            'yetkazib' => ['yetkazib', 'tez yetkazib'],
            'kafolat' => ['kafolat', 'ishonchli'],
        ];

        $weaknessLower = mb_strtolower($weakness);

        foreach ($strengths as $strength) {
            $strengthLower = mb_strtolower($strength);

            foreach ($mappings as $keyword => $related) {
                if (str_contains($weaknessLower, $keyword)) {
                    foreach ($related as $term) {
                        if (str_contains($strengthLower, $term)) {
                            return $strength;
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function createAdvantageResponse(string $weakness): string
    {
        $weaknessLower = mb_strtolower($weakness);

        $responses = [
            'narx' => 'Bizda narx sifatga mos - ortiqcha to\'lamasiz',
            'qimmat' => 'Bizda narx sifatga mos - ortiqcha to\'lamasiz',
            'sifat' => 'Biz har bir buyurtmani tekshiramiz',
            'sekin' => 'Biz tez ishlaymiz',
            'xizmat' => 'Bizda har bir mijozga alohida yondoshuv',
            'kafolat' => 'Bizda to\'liq kafolat bor',
            'yetkazib' => 'Bizda tez yetkazib berish',
        ];

        foreach ($responses as $keyword => $response) {
            if (str_contains($weaknessLower, $keyword)) {
                return $response;
            }
        }

        return 'Bizda bu masala yaxshi yo\'lga qo\'yilgan';
    }

    protected function createObjectionResponse(string $objection): string
    {
        $objectionLower = mb_strtolower($objection);

        if (str_contains($objectionLower, 'qimmat') || str_contains($objectionLower, 'narx')) {
            return 'Tushunaman. Lekin bizning mahsulot sizga qancha tejaydi yoki qancha daromad keltiradi - hisoblaylik?';
        }

        if (str_contains($objectionLower, 'o\'ylab')) {
            return 'Albatta. Ayting, qanday ma\'lumot sizga qaror qilishda yordam beradi?';
        }

        if (str_contains($objectionLower, 'boshqa')) {
            return 'Yaxshi yondoshuv. Bizning asosiy farqimiz...';
        }

        return 'Tushunaman. Sizga batafsilroq tushuntiray...';
    }

    protected function removeDuplicates(Collection $insights): Collection
    {
        $seen = [];
        return $insights->filter(function ($insight) use (&$seen) {
            $key = $insight['type'] . '_' . ($insight['competitor'] ?? '') . '_' . substr($insight['title'], 0, 30);
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;
            return true;
        });
    }

    protected function sortInsights(Collection $insights): Collection
    {
        return $insights->sortBy(function ($insight) {
            $priority = ['high' => 1, 'medium' => 2, 'low' => 3];
            $type = ['threat' => 0, 'positioning' => 1, 'price' => 2, 'opportunity' => 3, 'marketing' => 4, 'content' => 5];

            return ($priority[$insight['priority']] ?? 3) * 10 + ($type[$insight['type']] ?? 9);
        })->values();
    }

    protected function saveInsights(Business $business, Collection $insights): void
    {
        // Eskisini arxivlash
        CompetitorInsight::where('business_id', $business->id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        // Yangilarini saqlash
        foreach ($insights as $insight) {
            CompetitorInsight::create([
                'business_id' => $business->id,
                'type' => $insight['type'],
                'priority' => $insight['priority'],
                'title' => $insight['title'],
                'competitor_name' => $insight['competitor'],
                'description' => $insight['description'],
                'recommendation' => $insight['recommendation'],
                'action_data' => $insight['action'] ?? null,
                'raw_data' => $insight['data'] ?? null,
                'status' => 'active',
            ]);
        }

        $business->update(['insights_generated_at' => now()]);
    }

    protected function createSummary(Collection $insights, int $competitorsCount): array
    {
        $high = $insights->where('priority', 'high')->count();
        $threats = $insights->where('type', self::TYPE_THREAT)->count();
        $opportunities = $insights->where('type', self::TYPE_OPPORTUNITY)->count() +
                        $insights->where('type', self::TYPE_POSITIONING)->count();

        $headline = 'Tavsiyalar tayyor';

        if ($threats > 0) {
            $headline = sprintf('%d ta ogohlantirish - diqqat bering', $threats);
        } elseif ($high > 0) {
            $headline = sprintf('%d ta muhim tavsiya - bugun boshlang', $high);
        } elseif ($opportunities > 0) {
            $headline = sprintf('%d ta imkoniyat - foydalaning', $opportunities);
        }

        return [
            'total_insights' => $insights->count(),
            'high_priority' => $high,
            'threats' => $threats,
            'opportunities' => $opportunities,
            'competitors_analyzed' => $competitorsCount,
            'headline' => $headline,
        ];
    }

    protected function getTopActions(Collection $insights): array
    {
        return $insights
            ->where('priority', 'high')
            ->take(5)
            ->map(fn ($i) => [
                'id' => null, // Saqlanganidan keyin ID qo'shiladi
                'title' => $i['title'],
                'action_text' => $i['action']['text'] ?? $i['recommendation'],
                'type' => $i['type'],
                'competitor_name' => $i['competitor'],
            ])
            ->values()
            ->toArray();
    }
}
