<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Carbon\Carbon;

/**
 * Next Step Predictor
 *
 * Intelligent algorithm that analyzes module data and predicts
 * the most impactful next actions for business growth.
 *
 * Uses decision trees, scoring systems, and priority matrices
 * to recommend actions without AI dependency.
 */
class NextStepPredictor extends AlgorithmEngine
{
    protected string $cachePrefix = 'next_step_';
    protected int $cacheTTL = 900; // 15 minutes - fresher predictions

    protected ModuleAnalyzer $moduleAnalyzer;

    /**
     * Priority weights for different action categories
     */
    protected array $actionPriorityWeights = [
        'revenue_critical' => 1.5,    // Direct revenue impact
        'customer_retention' => 1.3,  // Customer-related
        'growth_opportunity' => 1.2,  // Growth potential
        'operational' => 1.0,         // Day-to-day operations
        'optimization' => 0.9,        // Improvements
    ];

    /**
     * Action templates with conditions and impacts
     */
    protected array $actionTemplates = [
        // Sales Actions
        'increase_sales_volume' => [
            'category' => 'revenue_critical',
            'module' => 'sales',
            'title' => 'Sotuvlar hajmini oshiring',
            'impact' => 'high',
            'effort' => 'medium',
            'timeframe' => '2-4 hafta',
        ],
        'improve_aov' => [
            'category' => 'revenue_critical',
            'module' => 'sales',
            'title' => 'O\'rtacha chek summasini oshiring',
            'impact' => 'high',
            'effort' => 'low',
            'timeframe' => '1-2 hafta',
        ],
        'analyze_sales_decline' => [
            'category' => 'revenue_critical',
            'module' => 'sales',
            'title' => 'Sotuvlar pasayishini tahlil qiling',
            'impact' => 'critical',
            'effort' => 'medium',
            'timeframe' => '1 hafta',
        ],

        // Marketing Actions
        'diversify_channels' => [
            'category' => 'growth_opportunity',
            'module' => 'marketing',
            'title' => 'Marketing kanallarini diversifikatsiya qiling',
            'impact' => 'medium',
            'effort' => 'medium',
            'timeframe' => '2-3 hafta',
        ],
        'optimize_budget' => [
            'category' => 'optimization',
            'module' => 'marketing',
            'title' => 'Marketing byudjetini optimallashtiring',
            'impact' => 'medium',
            'effort' => 'low',
            'timeframe' => '1 hafta',
        ],
        'activate_channels' => [
            'category' => 'growth_opportunity',
            'module' => 'marketing',
            'title' => 'Marketing kanallarini faollashtiring',
            'impact' => 'high',
            'effort' => 'medium',
            'timeframe' => '1-2 hafta',
        ],

        // Customer Actions
        'improve_retention' => [
            'category' => 'customer_retention',
            'module' => 'customers',
            'title' => 'Mijozlarni saqlash strategiyasini ishlab chiqing',
            'impact' => 'high',
            'effort' => 'medium',
            'timeframe' => '2-4 hafta',
        ],
        'reactivate_dormant' => [
            'category' => 'customer_retention',
            'module' => 'customers',
            'title' => 'Faol bo\'lmagan mijozlarni qayta jalb qiling',
            'impact' => 'medium',
            'effort' => 'low',
            'timeframe' => '1-2 hafta',
        ],
        'loyalty_program' => [
            'category' => 'customer_retention',
            'module' => 'customers',
            'title' => 'Sodiqlik dasturini ishga tushiring',
            'impact' => 'high',
            'effort' => 'high',
            'timeframe' => '4-6 hafta',
        ],
        'increase_frequency' => [
            'category' => 'revenue_critical',
            'module' => 'customers',
            'title' => 'Sotib olish chastotasini oshiring',
            'impact' => 'high',
            'effort' => 'medium',
            'timeframe' => '2-3 hafta',
        ],

        // Content Actions
        'increase_content_frequency' => [
            'category' => 'growth_opportunity',
            'module' => 'content',
            'title' => 'Kontent chiqarish chastotasini oshiring',
            'impact' => 'medium',
            'effort' => 'medium',
            'timeframe' => '2 hafta',
        ],
        'improve_content_consistency' => [
            'category' => 'operational',
            'module' => 'content',
            'title' => 'Kontent muntazamligini yaxshilang',
            'impact' => 'medium',
            'effort' => 'low',
            'timeframe' => '1 hafta',
        ],
        'diversify_content_types' => [
            'category' => 'optimization',
            'module' => 'content',
            'title' => 'Kontent turlarini diversifikatsiya qiling',
            'impact' => 'medium',
            'effort' => 'medium',
            'timeframe' => '2-3 hafta',
        ],

        // Funnel Actions
        'improve_conversion' => [
            'category' => 'revenue_critical',
            'module' => 'funnel',
            'title' => 'Konversiyani yaxshilang',
            'impact' => 'critical',
            'effort' => 'medium',
            'timeframe' => '2-4 hafta',
        ],
        'generate_more_leads' => [
            'category' => 'growth_opportunity',
            'module' => 'funnel',
            'title' => 'Ko\'proq lead generatsiya qiling',
            'impact' => 'high',
            'effort' => 'medium',
            'timeframe' => '2-3 hafta',
        ],
        'nurture_leads' => [
            'category' => 'operational',
            'module' => 'funnel',
            'title' => 'Lead nurturing jarayonini yaxshilang',
            'impact' => 'medium',
            'effort' => 'medium',
            'timeframe' => '2-3 hafta',
        ],
    ];

    public function __construct(ModuleAnalyzer $moduleAnalyzer)
    {
        $this->moduleAnalyzer = $moduleAnalyzer;
    }

    /**
     * Predict next steps for a business
     */
    public function predictNextSteps(Business $business, int $limit = 5): array
    {
        $cacheKey = "business_{$business->id}_predictions_{$limit}";

        return $this->cached($cacheKey, function () use ($business, $limit) {
            // Get module analysis
            $analysis = $this->moduleAnalyzer->analyzeAllModules($business);

            // Generate all potential actions
            $potentialActions = $this->generatePotentialActions($analysis);

            // Score and rank actions
            $scoredActions = $this->scoreActions($potentialActions, $analysis);

            // Select top actions
            $topActions = $this->selectTopActions($scoredActions, $limit);

            // Generate action details
            $detailedActions = $this->generateActionDetails($topActions, $analysis);

            // Calculate prediction confidence
            $confidence = $this->calculatePredictionConfidence($analysis);

            return [
                'predictions' => $detailedActions,
                'total_actions_analyzed' => count($potentialActions),
                'confidence' => $confidence,
                'business_health' => $analysis['overall_score'],
                'predicted_at' => now()->toIso8601String(),
                'valid_until' => now()->addMinutes(15)->toIso8601String(),
            ];
        });
    }

    /**
     * Generate potential actions based on module analysis
     */
    protected function generatePotentialActions(array $analysis): array
    {
        $actions = [];
        $modules = $analysis['modules'];

        // Sales-based actions
        if (isset($modules['sales'])) {
            $salesMetrics = $modules['sales']['metrics'];
            $salesTrend = $modules['sales']['trend'];

            // Revenue declining
            if ($salesMetrics['revenue_growth'] < -10) {
                $actions[] = $this->createAction('analyze_sales_decline', [
                    'reason' => "Daromad {$salesMetrics['revenue_growth']}% pasaygan",
                    'urgency' => 'critical',
                    'data' => ['revenue_growth' => $salesMetrics['revenue_growth']],
                ]);
            }

            // Low AOV
            if ($salesMetrics['current_aov'] < $salesMetrics['last_aov'] * 0.9) {
                $actions[] = $this->createAction('improve_aov', [
                    'reason' => "O'rtacha chek " . abs($salesMetrics['aov_growth']) . "% pasaygan",
                    'urgency' => 'high',
                    'data' => ['aov_change' => $salesMetrics['aov_growth']],
                ]);
            }

            // Downward trend
            if ($salesTrend['direction'] === 'down' && $salesTrend['r_squared'] > 0.5) {
                $actions[] = $this->createAction('increase_sales_volume', [
                    'reason' => "Sotuvlarda pasayish trendi (ishonchlilik: " . round($salesTrend['r_squared'] * 100) . "%)",
                    'urgency' => 'high',
                    'data' => ['trend_slope' => $salesTrend['slope'], 'r_squared' => $salesTrend['r_squared']],
                ]);
            }
        }

        // Marketing-based actions
        if (isset($modules['marketing'])) {
            $marketingMetrics = $modules['marketing']['metrics'];

            // Low channel diversity
            if ($marketingMetrics['diversity_score'] < 50) {
                $actions[] = $this->createAction('diversify_channels', [
                    'reason' => "Marketing kanallar diversifikatsiyasi past ({$marketingMetrics['diversity_score']}%)",
                    'urgency' => 'medium',
                    'data' => ['diversity_score' => $marketingMetrics['diversity_score']],
                ]);
            }

            // No active channels
            if ($marketingMetrics['active_channels'] < 2) {
                $actions[] = $this->createAction('activate_channels', [
                    'reason' => "Faol marketing kanallari kam ({$marketingMetrics['active_channels']})",
                    'urgency' => 'high',
                    'data' => ['active_channels' => $marketingMetrics['active_channels']],
                ]);
            }

            // Has budget but low results
            if ($marketingMetrics['total_budget'] > 0 && isset($modules['sales'])) {
                $salesGrowth = $modules['sales']['metrics']['revenue_growth'] ?? 0;
                if ($salesGrowth < 5) {
                    $actions[] = $this->createAction('optimize_budget', [
                        'reason' => "Marketing xarajatlari mavjud, lekin sotuvlar o'smayapti",
                        'urgency' => 'high',
                        'data' => ['budget' => $marketingMetrics['total_budget'], 'sales_growth' => $salesGrowth],
                    ]);
                }
            }
        }

        // Customer-based actions
        if (isset($modules['customers'])) {
            $customerMetrics = $modules['customers']['metrics'];
            $segments = $modules['customers']['segments'];

            // Customer decline
            if ($customerMetrics['customer_growth'] < -5) {
                $actions[] = $this->createAction('improve_retention', [
                    'reason' => "Mijozlar soni {$customerMetrics['customer_growth']}% kamaygan",
                    'urgency' => 'high',
                    'data' => ['customer_growth' => $customerMetrics['customer_growth']],
                ]);
            }

            // Low purchase frequency
            if ($customerMetrics['avg_purchase_frequency'] < 1.5) {
                $actions[] = $this->createAction('increase_frequency', [
                    'reason' => "Sotib olish chastotasi past ({$customerMetrics['avg_purchase_frequency']}x)",
                    'urgency' => 'medium',
                    'data' => ['avg_frequency' => $customerMetrics['avg_purchase_frequency']],
                ]);
            }

            // At-risk customers high
            if (($segments['at_risk'] ?? 0) > 30) {
                $actions[] = $this->createAction('reactivate_dormant', [
                    'reason' => "Xavf ostidagi mijozlar ko'p ({$segments['at_risk']}%)",
                    'urgency' => 'medium',
                    'data' => ['at_risk_percent' => $segments['at_risk']],
                ]);
            }

            // Low loyalty
            if (($segments['loyal'] ?? 0) < 15) {
                $actions[] = $this->createAction('loyalty_program', [
                    'reason' => "Sodiq mijozlar kam ({$segments['loyal']}%)",
                    'urgency' => 'medium',
                    'data' => ['loyal_percent' => $segments['loyal']],
                ]);
            }
        }

        // Content-based actions
        if (isset($modules['content'])) {
            $contentMetrics = $modules['content']['metrics'];

            // Low frequency
            if ($contentMetrics['content_frequency'] < 0.3) {
                $actions[] = $this->createAction('increase_content_frequency', [
                    'reason' => "Kontent chiqarish chastotasi juda past",
                    'urgency' => 'medium',
                    'data' => ['frequency' => $contentMetrics['content_frequency']],
                ]);
            }

            // Low consistency
            if ($contentMetrics['consistency_score'] < 50) {
                $actions[] = $this->createAction('improve_content_consistency', [
                    'reason' => "Kontent muntazamligi past ({$contentMetrics['consistency_score']}%)",
                    'urgency' => 'low',
                    'data' => ['consistency' => $contentMetrics['consistency_score']],
                ]);
            }
        }

        // Funnel-based actions
        if (isset($modules['funnel'])) {
            $funnelMetrics = $modules['funnel']['metrics'];

            // Low conversion
            if ($funnelMetrics['conversion_rate'] < 5) {
                $actions[] = $this->createAction('improve_conversion', [
                    'reason' => "Konversiya foizi past ({$funnelMetrics['conversion_rate']}%)",
                    'urgency' => 'critical',
                    'data' => ['conversion_rate' => $funnelMetrics['conversion_rate']],
                ]);
            }

            // Low leads
            if ($funnelMetrics['total_leads'] < 20) {
                $actions[] = $this->createAction('generate_more_leads', [
                    'reason' => "Lead soni kam ({$funnelMetrics['total_leads']})",
                    'urgency' => 'high',
                    'data' => ['total_leads' => $funnelMetrics['total_leads']],
                ]);
            }

            // Low funnel efficiency
            if ($funnelMetrics['funnel_efficiency'] < 30) {
                $actions[] = $this->createAction('nurture_leads', [
                    'reason' => "Funnel samaradorligi past ({$funnelMetrics['funnel_efficiency']}%)",
                    'urgency' => 'medium',
                    'data' => ['efficiency' => $funnelMetrics['funnel_efficiency']],
                ]);
            }
        }

        return $actions;
    }

    /**
     * Create an action object
     */
    protected function createAction(string $templateKey, array $context): array
    {
        $template = $this->actionTemplates[$templateKey] ?? null;

        if (!$template) {
            return [];
        }

        return [
            'key' => $templateKey,
            'template' => $template,
            'context' => $context,
            'base_score' => $this->calculateBaseScore($template, $context),
        ];
    }

    /**
     * Calculate base score for an action
     */
    protected function calculateBaseScore(array $template, array $context): float
    {
        $score = 50; // Base score

        // Impact multiplier
        $impactScores = [
            'critical' => 40,
            'high' => 30,
            'medium' => 20,
            'low' => 10,
        ];
        $score += $impactScores[$template['impact']] ?? 15;

        // Urgency bonus
        $urgencyScores = [
            'critical' => 30,
            'high' => 20,
            'medium' => 10,
            'low' => 0,
        ];
        $score += $urgencyScores[$context['urgency'] ?? 'medium'] ?? 10;

        // Category weight
        $categoryWeight = $this->actionPriorityWeights[$template['category']] ?? 1.0;
        $score *= $categoryWeight;

        // Effort penalty (higher effort = slightly lower priority for quick wins)
        $effortPenalty = [
            'high' => -10,
            'medium' => 0,
            'low' => 5,
        ];
        $score += $effortPenalty[$template['effort']] ?? 0;

        return round($score, 2);
    }

    /**
     * Score all actions with additional context
     */
    protected function scoreActions(array $actions, array $analysis): array
    {
        $overallScore = $analysis['overall_score']['score'] ?? 50;

        foreach ($actions as &$action) {
            $score = $action['base_score'];

            // Boost actions for weak modules
            $moduleScore = $analysis['modules'][$action['template']['module']]['health_score'] ?? 50;
            if ($moduleScore < 40) {
                $score *= 1.3; // 30% boost for weak modules
            } elseif ($moduleScore < 60) {
                $score *= 1.15; // 15% boost for average modules
            }

            // Boost revenue-critical actions when overall health is poor
            if ($overallScore < 50 && $action['template']['category'] === 'revenue_critical') {
                $score *= 1.25;
            }

            $action['final_score'] = round($score, 2);
        }

        // Sort by final score descending
        usort($actions, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        return $actions;
    }

    /**
     * Select top actions ensuring variety
     */
    protected function selectTopActions(array $actions, int $limit): array
    {
        $selected = [];
        $moduleCount = [];

        foreach ($actions as $action) {
            $module = $action['template']['module'];

            // Limit to 2 actions per module for variety
            if (($moduleCount[$module] ?? 0) >= 2) {
                continue;
            }

            $selected[] = $action;
            $moduleCount[$module] = ($moduleCount[$module] ?? 0) + 1;

            if (count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }

    /**
     * Generate detailed action recommendations
     */
    protected function generateActionDetails(array $actions, array $analysis): array
    {
        return array_map(function ($action, $index) use ($analysis) {
            $template = $action['template'];
            $context = $action['context'];

            return [
                'priority' => $index + 1,
                'key' => $action['key'],
                'title' => $template['title'],
                'module' => $template['module'],
                'module_label' => $this->getModuleLabel($template['module']),
                'category' => $template['category'],
                'impact' => $template['impact'],
                'effort' => $template['effort'],
                'timeframe' => $template['timeframe'],
                'reason' => $context['reason'],
                'score' => $action['final_score'],
                'steps' => $this->generateActionSteps($action['key'], $context),
                'expected_outcome' => $this->generateExpectedOutcome($action['key'], $context),
                'kpis_to_track' => $this->getKPIsToTrack($action['key']),
                'related_data' => $context['data'] ?? [],
            ];
        }, $actions, array_keys($actions));
    }

    /**
     * Generate specific steps for an action
     */
    protected function generateActionSteps(string $actionKey, array $context): array
    {
        $stepsMap = [
            'analyze_sales_decline' => [
                'Oxirgi 30 kunlik sotuvlar ma\'lumotlarini tahlil qiling',
                'Eng yaxshi va eng yomon sotilgan mahsulotlarni aniqlang',
                'Mijozlar tarkibidagi o\'zgarishlarni ko\'rib chiqing',
                'Raqobatchilar narxlarini tekshiring',
                'Marketing faoliyati va sotuvlar o\'rtasidagi bog\'liqlikni tekshiring',
            ],
            'improve_aov' => [
                'Cross-sell va upsell imkoniyatlarini aniqlang',
                'Bundle (to\'plam) takliflarini yarating',
                'VIP mijozlar uchun maxsus takliflar tayyorlang',
                'Minimal buyurtma summasini belgilang (yetkazib berish uchun)',
                'Premium mahsulotlarni targ\'ib qiling',
            ],
            'increase_sales_volume' => [
                'Yangi mijozlar jalb qilish kampaniyasini boshlang',
                'Mavjud mijozlarga maxsus takliflar yuboring',
                'Referral dasturini ishga tushiring',
                'Sotuvchilar treningini o\'tkazing',
                'Yangi sotish kanallarini sinab ko\'ring',
            ],
            'diversify_channels' => [
                'Sizning maqsadli auditoriyangiz qaysi platformalarda faol ekanini aniqlang',
                'Raqobatchilar qaysi kanallarda faol ekanini tekshiring',
                'Har bir yangi kanal uchun minimal byudjet ajrating',
                'A/B testlar o\'tkazing',
                'Natijalarni 2 hafta ichida tahlil qiling',
            ],
            'optimize_budget' => [
                'Har bir kanal bo\'yicha ROI hisoblang',
                'Eng samarasiz kanallarga xarajatni kamaytiring',
                'Eng samarali kanallarga ko\'proq byudjet ajrating',
                'Yangi kanallar uchun test byudjeti belgilang',
                'Haftalik byudjet tahlilini joriy qiling',
            ],
            'activate_channels' => [
                'Hozirda ishlatilmayotgan kanallarni aniqlang',
                'Har bir kanal uchun kontent strategiyasi tuzing',
                'Avtomatlashtirish imkoniyatlarini o\'rnatish',
                'Dastlabki kampaniyani boshlang',
                'Birinchi natijalarni 1 hafta ichida baholang',
            ],
            'improve_retention' => [
                'Mijozlar yo\'qotish sabablarini aniqlang (so\'rovnoma)',
                'Qaytgan mijozlar uchun maxsus takliflar tayyorlang',
                'Email/SMS nurturing ketma-ketligini yarating',
                'Mijoz xizmatini yaxshilang',
                'Sodiqlik dasturini ishga tushiring yoki yaxshilang',
            ],
            'reactivate_dormant' => [
                '90+ kun davomida xarid qilmagan mijozlar ro\'yxatini tuzing',
                '"Sizni sog\'indik" kampaniyasini boshlang',
                'Maxsus chegirma yoki bonus taklif qiling',
                'Personalizatsiyalangan xabar yuboring',
                'Qaytib kelganlar uchun alohida KPI kuzating',
            ],
            'loyalty_program' => [
                'Sodiqlik dasturi turini tanlang (ballar, darajalar, cashback)',
                'Mukofotlar tizimini ishlab chiqing',
                'Texnik infratuzilmani o\'rnating',
                'Mijozlarga dastur haqida xabar bering',
                'Birinchi 100 ishtirokchini jalb qiling',
            ],
            'increase_frequency' => [
                'Mijozlar xarid qilish siklini tahlil qiling',
                'Qayta xarid eslatmalari tizimini o\'rnating',
                'Muntazam xaridorlar uchun chegirmalar taklif qiling',
                'Yangi mahsulotlar haqida xabardor qiling',
                'Subscription (obuna) modelini sinab ko\'ring',
            ],
            'increase_content_frequency' => [
                'Kontent taqvimi tuzing (kamida 30 kunlik)',
                'Kontent turlari va mavzularini aniqlang',
                'Kontent ishlab chiqarish jarayonini soddalashtiring',
                'Kontent qayta ishlash (repurposing) strategiyasini qo\'llang',
                'Avtomatlashtirish vositalari ishlating',
            ],
            'improve_content_consistency' => [
                'Haftalik kontent rejasini tuzing',
                'Oldindan tayyorlanadigan kontent zahirasini yarating',
                'Posting jadvalini belgilang va unga amal qiling',
                'Kontent boshqaruv vositalaridan foydalaning',
                'Jamoa a\'zolari o\'rtasida vazifalarni taqsimlang',
            ],
            'diversify_content_types' => [
                'Hozirgi kontent turlarini tahlil qiling',
                'Auditoriyangiz qaysi formatni yaxshi qabul qilishini aniqlang',
                'Video, carousel, stories kabi formatlarni sinab ko\'ring',
                'User-generated content (UGC) dan foydalaning',
                'Har xil formatlar natijalarini solishtiring',
            ],
            'improve_conversion' => [
                'Konversiya yo\'qotilayotgan bosqichlarni aniqlang',
                'Lead qualification jarayonini yaxshilang',
                'Sotuvchilar treningini o\'tkazing',
                'Follow-up tizimini avtomatlashtiring',
                'Sotish skriptlarini optimallashtiring',
            ],
            'generate_more_leads' => [
                'Lead magnet (bepul resurs) yarating',
                'Landing page optimallashtiring',
                'Paid reklama kampaniyasini boshlang',
                'Partnership va hamkorliklarni rivojlantiring',
                'Referral dasturini faollashtiring',
            ],
            'nurture_leads' => [
                'Lead nurturing email ketma-ketligini yarating',
                'Lead scoring tizimini joriy qiling',
                'Har bir bosqich uchun alohida kontent tayyorlang',
                'Follow-up muddatlarini qisqartiring',
                'Personalizatsiyani kuchaytiring',
            ],
        ];

        return $stepsMap[$actionKey] ?? [
            'Vaziyatni batafsil tahlil qiling',
            'Maqsadlarni aniq belgilang',
            'Harakat rejasini tuzing',
            'Dastlabki qadamni boshlang',
            'Natijalarni kuzatib boring',
        ];
    }

    /**
     * Generate expected outcome for an action
     */
    protected function generateExpectedOutcome(string $actionKey, array $context): string
    {
        $outcomes = [
            'analyze_sales_decline' => 'Sotuvlar pasayish sabablarini aniqlash va tuzatish choralarini ko\'rish',
            'improve_aov' => 'O\'rtacha chek summasini 15-25% ga oshirish',
            'increase_sales_volume' => 'Sotuvlar hajmini 20-30% ga oshirish',
            'diversify_channels' => 'Marketing kanallar diversifikatsiyasini 50% ga yetkazish',
            'optimize_budget' => 'Marketing ROI ni 20-40% ga oshirish',
            'activate_channels' => 'Faol marketing kanallarini 2+ ga yetkazish',
            'improve_retention' => 'Mijozlar yo\'qotish foizini 15-25% ga kamaytirish',
            'reactivate_dormant' => 'Faol bo\'lmagan mijozlarning 10-20% ni qayta jalb qilish',
            'loyalty_program' => 'Mijozlar sodiqligini 25-35% ga oshirish',
            'increase_frequency' => 'Qayta sotib olish chastotasini 20-30% ga oshirish',
            'increase_content_frequency' => 'Kuniga 1+ kontent chiqarish',
            'improve_content_consistency' => 'Kontent muntazamligini 80%+ ga yetkazish',
            'diversify_content_types' => 'Engagement ni 25-40% ga oshirish',
            'improve_conversion' => 'Konversiya foizini 2-3x ga oshirish',
            'generate_more_leads' => 'Oylik lead sonini 50-100% ga oshirish',
            'nurture_leads' => 'Funnel samaradorligini 30-50% ga oshirish',
        ];

        return $outcomes[$actionKey] ?? 'Biznes ko\'rsatkichlarini yaxshilash';
    }

    /**
     * Get KPIs to track for an action
     */
    protected function getKPIsToTrack(string $actionKey): array
    {
        $kpisMap = [
            'analyze_sales_decline' => ['Kunlik daromad', 'Sotuvlar soni', 'O\'rtacha chek'],
            'improve_aov' => ['O\'rtacha chek (AOV)', 'Bundle sotuvlar', 'Upsell konversiyasi'],
            'increase_sales_volume' => ['Jami sotuvlar', 'Yangi mijozlar', 'Konversiya foizi'],
            'diversify_channels' => ['Kanallar soni', 'Kanal bo\'yicha traffic', 'Har kanal ROI'],
            'optimize_budget' => ['Marketing ROI', 'CPA', 'ROAS'],
            'activate_channels' => ['Faol kanallar', 'Reach', 'Engagement'],
            'improve_retention' => ['Churn rate', 'Retention rate', 'NPS'],
            'reactivate_dormant' => ['Qaytgan mijozlar', 'Reactivation rate', 'CLV'],
            'loyalty_program' => ['Dastur a\'zolari', 'Takroriy xaridlar', 'Sodiqlik ball'],
            'increase_frequency' => ['Xarid chastotasi', 'Oylik xaridlar', 'CLV'],
            'increase_content_frequency' => ['Post soni', 'Reach', 'Engagement rate'],
            'improve_content_consistency' => ['Posting days', 'Reach stability', 'Follower growth'],
            'diversify_content_types' => ['Format diversity', 'Engagement by type', 'Save rate'],
            'improve_conversion' => ['Conversion rate', 'Sales cycle', 'Win rate'],
            'generate_more_leads' => ['Lead soni', 'CPL', 'Lead quality score'],
            'nurture_leads' => ['Email open rate', 'Funnel velocity', 'MQL to SQL ratio'],
        ];

        return $kpisMap[$actionKey] ?? ['Umumiy ko\'rsatkichlar'];
    }

    /**
     * Get module label in Uzbek
     */
    protected function getModuleLabel(string $module): string
    {
        $labels = [
            'sales' => 'Sotuvlar',
            'marketing' => 'Marketing',
            'customers' => 'Mijozlar',
            'content' => 'Kontent',
            'funnel' => 'Funnel',
        ];

        return $labels[$module] ?? $module;
    }

    /**
     * Calculate prediction confidence based on data quality
     */
    protected function calculatePredictionConfidence(array $analysis): array
    {
        $dataAccuracy = $analysis['data_accuracy']['overall'] ?? 0;
        $modulesWithData = count(array_filter($analysis['modules'], fn($m) => $m['data_completeness'] > 50));
        $totalModules = count($analysis['modules']);

        $confidence = ($dataAccuracy * 0.6) + (($modulesWithData / $totalModules) * 100 * 0.4);

        return [
            'score' => round($confidence, 2),
            'level' => $confidence >= 80 ? 'high' : ($confidence >= 60 ? 'medium' : 'low'),
            'label' => $confidence >= 80 ? 'Yuqori' : ($confidence >= 60 ? 'O\'rtacha' : 'Past'),
            'factors' => [
                'data_accuracy' => $dataAccuracy,
                'modules_with_data' => $modulesWithData,
                'total_modules' => $totalModules,
            ],
        ];
    }

    /**
     * Get quick win actions (low effort, high impact)
     */
    public function getQuickWins(Business $business, int $limit = 3): array
    {
        $predictions = $this->predictNextSteps($business, 10);

        $quickWins = array_filter($predictions['predictions'], function ($action) {
            return $action['effort'] === 'low' && in_array($action['impact'], ['high', 'critical']);
        });

        return array_slice($quickWins, 0, $limit);
    }

    /**
     * Get critical actions that need immediate attention
     */
    public function getCriticalActions(Business $business): array
    {
        $predictions = $this->predictNextSteps($business, 10);

        return array_filter($predictions['predictions'], function ($action) {
            return $action['impact'] === 'critical';
        });
    }
}
