<?php

namespace App\Services;

class RecommendationEngine
{
    // Priority weights for different factors
    protected array $priorityFactors = [
        'impact' => 0.35,
        'effort' => 0.25,
        'urgency' => 0.25,
        'cost' => 0.15,
    ];

    /**
     * Generate prioritized recommendations
     */
    public function generateRecommendations(
        array $healthScore,
        array $benchmarkComparison,
        array $weaknesses,
        array $aggregatedData
    ): array {
        $recommendations = [];

        // Generate recommendations from weaknesses
        foreach ($weaknesses as $weakness) {
            $recommendation = $this->createRecommendationFromWeakness($weakness, $aggregatedData);
            if ($recommendation) {
                $recommendations[] = $recommendation;
            }
        }

        // Generate category-specific recommendations
        $categoryRecommendations = $this->generateCategoryRecommendations($healthScore);
        $recommendations = array_merge($recommendations, $categoryRecommendations);

        // Generate data-driven recommendations
        $dataRecommendations = $this->generateDataDrivenRecommendations($aggregatedData);
        $recommendations = array_merge($recommendations, $dataRecommendations);

        // Calculate priority scores and sort
        $recommendations = $this->prioritizeRecommendations($recommendations);

        // Remove duplicates and limit
        $recommendations = $this->deduplicateRecommendations($recommendations);

        // Take top 10 recommendations
        return array_slice($recommendations, 0, 10);
    }

    /**
     * Create recommendation from weakness
     */
    protected function createRecommendationFromWeakness(array $weakness, array $data): ?array
    {
        $metric = $weakness['metric'] ?? '';
        $status = $weakness['status'] ?? 'average';
        $gapPercent = abs($weakness['gap_percent'] ?? 0);

        // Get specific recommendation based on metric
        $specificRec = $this->getMetricSpecificRecommendation($metric, $status, $gapPercent, $data);

        if (! $specificRec) {
            return null;
        }

        return [
            'id' => uniqid('rec_'),
            'title' => $specificRec['title'],
            'description' => $specificRec['description'],
            'category' => $specificRec['category'],
            'type' => 'improvement',
            'priority' => $this->calculatePriorityFromGap($gapPercent, $status),
            'impact' => $specificRec['impact'],
            'effort' => $specificRec['effort'],
            'timeframe' => $specificRec['timeframe'],
            'source' => 'benchmark_comparison',
            'metric' => $metric,
            'actions' => $specificRec['actions'],
            'expected_result' => $specificRec['expected_result'],
            'resources' => $specificRec['resources'] ?? [],
        ];
    }

    /**
     * Get metric-specific recommendation
     */
    protected function getMetricSpecificRecommendation(
        string $metric,
        string $status,
        float $gapPercent,
        array $data
    ): ?array {
        $recommendations = [
            'engagement_rate' => [
                'title' => 'Engagement rate ni oshirish',
                'description' => 'Auditoriya bilan o\'zaro aloqani yaxshilash strategiyasi',
                'category' => 'content',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Kontent kalendarini yarating va doimiy post qiling',
                    'Interactive kontentlar (so\'rovnomalar, viktorinalar) yarating',
                    'Izohlar va xabarlarga tezkor javob bering',
                    'User-generated content kampaniyalarini boshlang',
                ],
                'expected_result' => 'Engagement rate 30-50% ga oshishi kutiladi',
            ],
            'follower_growth_rate' => [
                'title' => 'Obunachi o\'sish tezligini oshirish',
                'description' => 'Yangi auditoriyani jalb qilish strategiyasi',
                'category' => 'marketing',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Targetlangan reklama kampaniyalarini ishga tushiring',
                    'Influencer marketing va kolaboratsiyalar qiling',
                    'Viral kontent formatlarini sinab ko\'ring',
                    'Cross-promotion strategiyasini joriy qiling',
                ],
                'expected_result' => 'Haftalik 5-10% obunachi o\'sishi kutiladi',
            ],
            'cpl' => [
                'title' => 'Lead narxini kamaytirish',
                'description' => 'Lead generation samaradorligini oshirish',
                'category' => 'marketing',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Landing page larni A/B test qiling',
                    'Lead magnet larni yangilang va kuchaytiring',
                    'Reklama auditoriyasini aniqroq targeting qiling',
                    'Retargeting kampaniyalarini sozlang',
                ],
                'expected_result' => 'CPL 20-40% ga kamayishi kutiladi',
            ],
            'cac' => [
                'title' => 'Mijoz jalb qilish narxini optimallashtirish',
                'description' => 'Customer acquisition cost ni kamaytirish strategiyasi',
                'category' => 'sales',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Eng samarali marketing kanallarini aniqlang',
                    'Referral dasturini ishga tushiring',
                    'Sotuv jarayonini avtomatlashtiring',
                    'Lead kvalifikatsiyasini yaxshilang',
                ],
                'expected_result' => 'CAC 25-40% ga kamayishi kutiladi',
            ],
            'conversion_rate' => [
                'title' => 'Konversiya darajasini oshirish',
                'description' => 'Lead dan mijozga o\'tish samaradorligini yaxshilash',
                'category' => 'sales',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Sotuv skriptlarini yangilang',
                    'Follow-up jarayonini avtomatlashtiring',
                    'Mijoz e\'tirozlarini yechish bo\'yicha trening o\'tkazing',
                    'CRM dan to\'liq foydalanishni ta\'minlang',
                ],
                'expected_result' => 'Konversiya 15-30% ga oshishi kutiladi',
            ],
            'ctr' => [
                'title' => 'Click-through rate ni oshirish',
                'description' => 'Reklama va kontent samaradorligini yaxshilash',
                'category' => 'marketing',
                'impact' => 'medium',
                'effort' => 'low',
                'timeframe' => '1-2 hafta',
                'actions' => [
                    'Reklama sarlavhalari va vizuallarni A/B test qiling',
                    'CTA (Call to Action) larni kuchaytiring',
                    'Auditoriya segmentatsiyasini yaxshilang',
                    'Kreativ formatlarni diversifikatsiya qiling',
                ],
                'expected_result' => 'CTR 40-60% ga oshishi kutiladi',
            ],
            'roas' => [
                'title' => 'Reklama ROI ni oshirish',
                'description' => 'Return on Ad Spend ni yaxshilash',
                'category' => 'marketing',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '4-6 hafta',
                'actions' => [
                    'Eng yuqori konversiya beruvchi auditoriyalarni aniqlang',
                    'Mahsulot/xizmat bundlelarini optimallashtiring',
                    'Remarketing kampaniyalarini sozlang',
                    'Average order value ni oshirish taktikalarini qo\'llang',
                ],
                'expected_result' => 'ROAS 50-100% ga oshishi kutiladi',
            ],
            'churn_rate' => [
                'title' => 'Mijoz yo\'qotishni kamaytirish',
                'description' => 'Customer retention strategiyasini kuchaytirish',
                'category' => 'sales',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Mijoz mamnuniyati so\'rovnomasini o\'tkazing (NPS)',
                    'Loyalty dasturini ishga tushiring',
                    'Proaktiv mijoz xizmati ko\'rsating',
                    'Churn risklari bo\'yicha early warning tizimi yarating',
                ],
                'expected_result' => 'Churn rate 20-40% ga kamayishi kutiladi',
            ],
            'avg_response_time' => [
                'title' => 'Javob vaqtini qisqartirish',
                'description' => 'Mijoz so\'rovlariga tezkor javob berish',
                'category' => 'content',
                'impact' => 'medium',
                'effort' => 'low',
                'timeframe' => '1-2 hafta',
                'actions' => [
                    'Chatbot yoki avto-javob tizimini joriy qiling',
                    'Template javoblar bazasini yarating',
                    'Xabarlar monitoringini kuchaytiring',
                    'Mas\'ul shaxslar taqsimotini optimallashtiring',
                ],
                'expected_result' => 'O\'rtacha javob vaqti 50-70% ga kamayishi kutiladi',
            ],
            'content_frequency' => [
                'title' => 'Kontent chastotasini oshirish',
                'description' => 'Muntazam kontent ishlab chiqarish tizimi',
                'category' => 'content',
                'impact' => 'medium',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Kontent kalendar yarating (oylik rejalashtirish)',
                    'Kontent batch production tizimini joriy qiling',
                    'Kontent repurposing strategiyasini qo\'llang',
                    'Jamoa rollarini aniq taqsimlang',
                ],
                'expected_result' => 'Haftada 7-10 ta sifatli kontent chiqarish',
            ],
            'funnel_conversion' => [
                'title' => 'Funnel samaradorligini oshirish',
                'description' => 'Sotuv funneli konversiyasini yaxshilash',
                'category' => 'funnel',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Har bir funnel bosqichini analiz qiling',
                    'Bottleneck larni aniqlang va yo\'q qiling',
                    'Email nurturing kampaniyalarini sozlang',
                    'Retargeting strategiyasini optimallashtiring',
                ],
                'expected_result' => 'Funnel konversiyasi 30-50% ga oshishi kutiladi',
            ],
            'sales_cycle_days' => [
                'title' => 'Sotuv siklini qisqartirish',
                'description' => 'Lead dan mijozga o\'tish vaqtini kamaytirish',
                'category' => 'sales',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '4-6 hafta',
                'actions' => [
                    'Sotuv jarayonidagi keraksiz bosqichlarni olib tashlang',
                    'Avtomatlashtirish imkoniyatlarini joriy qiling',
                    'Lead scoring tizimini ishga tushiring',
                    'Fast-track takliflarni yarating',
                ],
                'expected_result' => 'Sotuv sikli 30-50% ga qisqarishi kutiladi',
            ],
            'ltv_cac_ratio' => [
                'title' => 'LTV/CAC nisbatini yaxshilash',
                'description' => 'Mijoz qiymatini oshirish va jalb narxini kamaytirish',
                'category' => 'sales',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '8-12 hafta',
                'actions' => [
                    'Upsell va cross-sell imkoniyatlarini kengaytiring',
                    'Mijoz saqlab qolish dasturlarini yarating',
                    'Subscription yoki recurring revenue modelini o\'rganing',
                    'Premium segment uchun mahsulotlar yarating',
                ],
                'expected_result' => 'LTV/CAC nisbati 3:1 dan yuqoriga oshishi kutiladi',
            ],
            'repeat_purchase_rate' => [
                'title' => 'Qayta xarid darajasini oshirish',
                'description' => 'Mavjud mijozlardan qayta sotuvlarni oshirish',
                'category' => 'sales',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Post-purchase email ketma-ketligini yarating',
                    'Loyalty bonus tizimini ishga tushiring',
                    'Maxsus takliflar (eksklyuziv) taqdim eting',
                    'Reminder va reactivation kampaniyalarini sozlang',
                ],
                'expected_result' => 'Qayta xarid darajasi 25-40% ga oshishi kutiladi',
            ],
        ];

        return $recommendations[$metric] ?? null;
    }

    /**
     * Generate category-specific recommendations
     */
    protected function generateCategoryRecommendations(array $healthScore): array
    {
        $recommendations = [];

        foreach ($healthScore['category_scores'] as $category => $data) {
            if ($data['score'] < 50) {
                $rec = $this->getCategoryRecommendation($category, $data['score']);
                if ($rec) {
                    $recommendations[] = $rec;
                }
            }
        }

        return $recommendations;
    }

    /**
     * Get category-level recommendation
     */
    protected function getCategoryRecommendation(string $category, int $score): ?array
    {
        $recommendations = [
            'marketing' => [
                'title' => 'Marketing strategiyasini qayta ko\'rib chiqish',
                'description' => 'Marketing faoliyatingiz soha standartlaridan past. Strategik o\'zgarishlar kerak.',
                'category' => 'marketing',
                'type' => 'strategic',
                'priority' => 'high',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Marketing audit o\'tkazing',
                    'Maqsadli auditoriyani qayta aniqlang',
                    'Marketing kanallarini diversifikatsiya qiling',
                    'Marketing jamoa tarkibini ko\'rib chiqing',
                ],
                'expected_result' => 'Marketing samaradorligi 50% ga oshishi',
                'source' => 'category_analysis',
            ],
            'sales' => [
                'title' => 'Sotuv jarayonini transformatsiya qilish',
                'description' => 'Sotuv ko\'rsatkichlari past. Sotuv tizimini tubdan o\'zgartirish kerak.',
                'category' => 'sales',
                'type' => 'strategic',
                'priority' => 'high',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '6-10 hafta',
                'actions' => [
                    'Sotuv jarayonini xaritalashtiring (mapping)',
                    'CRM tizimini to\'liq joriy qiling',
                    'Sotuv jamoasiga trening o\'tkazing',
                    'KPI va bonus tizimini qayta ko\'rib chiqing',
                ],
                'expected_result' => 'Sotuv hajmi 40% ga oshishi',
                'source' => 'category_analysis',
            ],
            'content' => [
                'title' => 'Kontent strategiyasini ishlab chiqish',
                'description' => 'Kontent marketing zaif. Professional yondashuv kerak.',
                'category' => 'content',
                'type' => 'strategic',
                'priority' => 'medium',
                'impact' => 'medium',
                'effort' => 'medium',
                'timeframe' => '4-6 hafta',
                'actions' => [
                    'Kontent strategiya hujjatini yarating',
                    'Kontent pillarlarini aniqlang',
                    'Production workflow ni sozlang',
                    'Kontent jamoa yoki freelancer ni jalb qiling',
                ],
                'expected_result' => 'Kontent engagement 60% ga oshishi',
                'source' => 'category_analysis',
            ],
            'funnel' => [
                'title' => 'Sotuv funnelini qayta qurish',
                'description' => 'Funnel samaradorligi past. Qayta loyihalash kerak.',
                'category' => 'funnel',
                'type' => 'strategic',
                'priority' => 'high',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '4-8 hafta',
                'actions' => [
                    'Mavjud funnel analizi o\'tkazing',
                    'Customer journey map yarating',
                    'Har bir bosqich uchun trigger va action belgilang',
                    'Automation tizimini joriy qiling',
                ],
                'expected_result' => 'Funnel konversiyasi 2x ga oshishi',
                'source' => 'category_analysis',
            ],
            'analytics' => [
                'title' => 'Analitika tizimini joriy qilish',
                'description' => 'Analitika zaif. Data-driven qarorlar uchun tizim kerak.',
                'category' => 'analytics',
                'type' => 'infrastructure',
                'priority' => 'medium',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Google Analytics / Yandex Metrica ulang',
                    'UTM tagging standartlarini joriy qiling',
                    'Haftalik hisobotlar tizimini yarating',
                    'Dashboard sozlang (asosiy KPI lar)',
                ],
                'expected_result' => 'Barcha marketing faoliyatini kuzatish imkoniyati',
                'source' => 'category_analysis',
            ],
        ];

        $rec = $recommendations[$category] ?? null;

        if ($rec) {
            $rec['id'] = uniqid('rec_');

            return $rec;
        }

        return null;
    }

    /**
     * Generate data-driven recommendations
     */
    protected function generateDataDrivenRecommendations(array $data): array
    {
        $recommendations = [];

        // Check for missing integrations
        if (! $data['integrations']['has_crm']) {
            $recommendations[] = [
                'id' => uniqid('rec_'),
                'title' => 'CRM tizimini ulash',
                'description' => 'CRM orqali sotuv jarayonini boshqarish va mijozlar bazasini saqlash',
                'category' => 'infrastructure',
                'type' => 'infrastructure',
                'priority' => 'high',
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Bitrix24 yoki AmoCRM ni tanlang',
                    'Jamoa uchun account yarating',
                    'Mavjud mijozlar bazasini import qiling',
                    'Sotuv pipeline ni sozlang',
                ],
                'expected_result' => 'Sotuv jarayoni 100% nazorat ostida bo\'ladi',
                'source' => 'data_analysis',
            ];
        }

        // Check for missing analytics
        if (! $data['integrations']['has_analytics']) {
            $recommendations[] = [
                'id' => uniqid('rec_'),
                'title' => 'Web analitika o\'rnatish',
                'description' => 'Google Analytics yoki Yandex Metrica orqali sayt trafikini kuzatish',
                'category' => 'analytics',
                'type' => 'infrastructure',
                'priority' => 'medium',
                'impact' => 'medium',
                'effort' => 'low',
                'timeframe' => '1-2 hafta',
                'actions' => [
                    'Google Analytics 4 ni saytga o\'rnating',
                    'Konversiya goal larini sozlang',
                    'E-commerce tracking ni yoqing (agar tegishli bo\'lsa)',
                    'Haftalik hisobot olish uchun alert sozlang',
                ],
                'expected_result' => 'Barcha sayt faoliyati kuzatilib, data-driven qarorlar',
                'source' => 'data_analysis',
            ];
        }

        // Check for dream buyers
        if ($data['dream_buyers']['total'] < 2) {
            $recommendations[] = [
                'id' => uniqid('rec_'),
                'title' => 'Ideal mijoz profillarini yaratish',
                'description' => 'Kamida 2-3 ta buyer persona yaratish kerak',
                'category' => 'marketing',
                'type' => 'strategic',
                'priority' => 'medium',
                'impact' => 'medium',
                'effort' => 'low',
                'timeframe' => '1-2 hafta',
                'actions' => [
                    'Mavjud eng yaxshi mijozlarni analiz qiling',
                    'Umumiy xususiyatlarni aniqlang',
                    'Har bir persona uchun profil yarating',
                    'Marketing xabarlarni personalarga moslashtiring',
                ],
                'expected_result' => 'Aniqroq targeting va yuqori konversiya',
                'source' => 'data_analysis',
            ];
        }

        // Check for competitor analysis
        if ($data['competitors']['total'] < 3) {
            $recommendations[] = [
                'id' => uniqid('rec_'),
                'title' => 'Raqobat tahlilini chuqurlashtirish',
                'description' => 'Kamida 3-5 ta raqobatchini batafsil tahlil qilish',
                'category' => 'marketing',
                'type' => 'research',
                'priority' => 'low',
                'impact' => 'medium',
                'effort' => 'medium',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Asosiy raqobatchilarni aniqlang',
                    'Ularning marketing strategiyalarini kuzating',
                    'Narxlash va positioning ni taqqoslang',
                    'Raqobatbardosh ustunliklaringizni aniqlang',
                ],
                'expected_result' => 'Bozorda aniq pozitsiyalash va differentsiatsiya',
                'source' => 'data_analysis',
            ];
        }

        // Check for high priority problems
        if ($data['problems']['high_priority'] > 3) {
            $recommendations[] = [
                'id' => uniqid('rec_'),
                'title' => 'Kritik muammolarni hal qilish',
                'description' => 'Ko\'p sonli yuqori prioritetli muammolar mavjud',
                'category' => 'operations',
                'type' => 'urgent',
                'priority' => 'critical',
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => '2-4 hafta',
                'actions' => [
                    'Barcha yuqori prioritet muammolarni ro\'yxatlang',
                    'Har biri uchun reja tuzing',
                    'Resurslarni taqsimlang',
                    'Haftalik progress kuzatuvini o\'rnating',
                ],
                'expected_result' => 'Kritik muammolarning 80% i hal qilinadi',
                'source' => 'data_analysis',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate priority based on gap
     */
    protected function calculatePriorityFromGap(float $gapPercent, string $status): string
    {
        if ($status === 'poor' || $gapPercent > 50) {
            return 'critical';
        }

        if ($gapPercent > 30) {
            return 'high';
        }

        if ($gapPercent > 15) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Prioritize recommendations using scoring
     */
    protected function prioritizeRecommendations(array $recommendations): array
    {
        foreach ($recommendations as &$rec) {
            $rec['priority_score'] = $this->calculatePriorityScore($rec);
        }

        usort($recommendations, fn ($a, $b) => $b['priority_score'] <=> $a['priority_score']);

        return $recommendations;
    }

    /**
     * Calculate priority score for recommendation
     */
    protected function calculatePriorityScore(array $recommendation): float
    {
        $impactScore = match ($recommendation['impact'] ?? 'medium') {
            'high' => 100,
            'medium' => 60,
            'low' => 30,
            default => 50,
        };

        // Lower effort = higher score
        $effortScore = match ($recommendation['effort'] ?? 'medium') {
            'low' => 100,
            'medium' => 60,
            'high' => 30,
            default => 50,
        };

        $priorityScore = match ($recommendation['priority'] ?? 'medium') {
            'critical' => 100,
            'high' => 80,
            'medium' => 50,
            'low' => 25,
            default => 50,
        };

        return ($impactScore * $this->priorityFactors['impact']) +
               ($effortScore * $this->priorityFactors['effort']) +
               ($priorityScore * $this->priorityFactors['urgency']);
    }

    /**
     * Remove duplicate recommendations
     */
    protected function deduplicateRecommendations(array $recommendations): array
    {
        $seen = [];
        $unique = [];

        foreach ($recommendations as $rec) {
            $key = $rec['category'].'_'.($rec['metric'] ?? $rec['title']);

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $rec;
            }
        }

        return $unique;
    }

    /**
     * Group recommendations by category
     */
    public function groupByCategory(array $recommendations): array
    {
        $grouped = [];

        foreach ($recommendations as $rec) {
            $category = $rec['category'] ?? 'other';

            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = $rec;
        }

        return $grouped;
    }

    /**
     * Get quick wins (high impact, low effort)
     */
    public function getQuickWins(array $recommendations, int $limit = 3): array
    {
        $quickWins = array_filter($recommendations, function ($rec) {
            return ($rec['impact'] ?? '') === 'high' && ($rec['effort'] ?? '') === 'low';
        });

        return array_slice($quickWins, 0, $limit);
    }

    /**
     * Get strategic recommendations (high impact, high effort)
     */
    public function getStrategicRecommendations(array $recommendations, int $limit = 3): array
    {
        $strategic = array_filter($recommendations, function ($rec) {
            return ($rec['impact'] ?? '') === 'high' && ($rec['effort'] ?? '') === 'high';
        });

        return array_slice($strategic, 0, $limit);
    }
}
