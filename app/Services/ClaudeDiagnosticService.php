<?php

namespace App\Services;

use App\Models\AIDiagnostic;
use App\Models\Business;
use App\Models\BusinessSuccessStory;
use App\Models\IndustryBenchmark;
use App\Prompts\DiagnosticsSystemPrompt;
use App\Prompts\DiagnosticsUserPrompt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClaudeDiagnosticService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-20250514';
    private int $maxTokens = 8000;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private bool $testMode = false;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', config('services.claude.api_key'));
        $this->model = config('services.anthropic.model', 'claude-sonnet-4-20250514');

        // Handle string "true"/"false" from .env
        $testModeValue = config('services.anthropic.test_mode', false);
        $this->testMode = filter_var($testModeValue, FILTER_VALIDATE_BOOLEAN);

        Log::info('ClaudeDiagnosticService initialized', [
            'test_mode' => $this->testMode,
            'model' => $this->model,
        ]);
    }

    /**
     * Check if running in test mode
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Generate mock diagnostic data (for testing without API calls)
     */
    private function getMockAnalysisData(Business $business): array
    {
        $businessName = $business->name ?? 'Test Business';
        $score = rand(35, 75);

        return [
            'overall_score' => $score,
            'status_level' => $score >= 60 ? 'good' : ($score >= 40 ? 'medium' : 'weak'),
            'status_message' => "Sizning {$businessName} biznesingiz o'rtacha darajada. Yaxshilash uchun quyidagi tavsiyalarga amal qiling.",
            'industry_avg_score' => 55,
            'category_scores' => [
                'Marketing' => rand(25, 50),
                'Sotuvlar' => rand(30, 55),
                'Kontent' => rand(20, 45),
                'Funnel' => rand(25, 50),
            ],
            'money_loss_analysis' => [
                'monthly_loss' => 15000000,
                'yearly_loss' => 180000000,
                'daily_loss' => 500000,
                'breakdown' => [
                    ['problem' => 'Mijozlar bilan aloqa yo\'qligi', 'amount' => 5000000, 'solution_module' => '/onboarding/dream-buyer', 'solution_title' => 'Ideal Mijoz'],
                    ['problem' => 'Zaif marketing kanallari', 'amount' => 7000000, 'solution_module' => '/business/channels', 'solution_title' => 'Kanallar'],
                    ['problem' => 'Avtomatlashtirish yo\'qligi', 'amount' => 3000000, 'solution_module' => '/business/instagram-ai', 'solution_title' => 'Instagram AI'],
                ],
            ],
            'roi_calculations' => [
                'summary' => [
                    'total_investment' => [
                        'time_hours' => 5,
                        'time_value_uzs' => 250000,
                        'money_uzs' => 0,
                        'total_uzs' => 250000,
                    ],
                    'total_monthly_return' => 15000000,
                    'overall_roi_percent' => 5900,
                    'payback_days' => 1,
                ],
                'per_action' => [
                    [
                        'id' => 1,
                        'action' => 'Ideal mijozni aniqlash',
                        'priority' => 1,
                        'investment' => [
                            'time' => '30 daqiqa',
                            'time_value' => 25000,
                            'money' => 0,
                            'total' => 25000,
                        ],
                        'expected_return' => [
                            'metric' => 'Konversiya',
                            'improvement' => '+45%',
                            'monthly_gain' => 5000000,
                            'description' => 'Aniq auditoriyaga moslashtirilgan marketing',
                        ],
                        'roi_percent' => 19900,
                        'payback_days' => 1,
                        'module_route' => '/onboarding/dream-buyer',
                        'difficulty' => 'oson',
                        'verdict' => 'JUDA SAMARALI ✅',
                    ],
                    [
                        'id' => 2,
                        'action' => 'Rad qilib bo\'lmas taklif yaratish',
                        'priority' => 2,
                        'investment' => [
                            'time' => '45 daqiqa',
                            'time_value' => 37500,
                            'money' => 0,
                            'total' => 37500,
                        ],
                        'expected_return' => [
                            'metric' => 'Sotuvlar',
                            'improvement' => '+60%',
                            'monthly_gain' => 7000000,
                            'description' => 'Raqobatchilardan farqlanadigan kuchli taklif',
                        ],
                        'roi_percent' => 18567,
                        'payback_days' => 1,
                        'module_route' => '/onboarding/offer',
                        'difficulty' => 'o\'rta',
                        'verdict' => 'JUDA SAMARALI ✅',
                    ],
                    [
                        'id' => 3,
                        'action' => 'Instagram AI ulash',
                        'priority' => 3,
                        'investment' => [
                            'time' => '20 daqiqa',
                            'time_value' => 16700,
                            'money' => 0,
                            'total' => 16700,
                        ],
                        'expected_return' => [
                            'metric' => 'Javob tezligi',
                            'improvement' => '+80%',
                            'monthly_gain' => 3000000,
                            'description' => '24/7 avtomatik javob berish',
                        ],
                        'roi_percent' => 17864,
                        'payback_days' => 1,
                        'module_route' => '/business/instagram-ai',
                        'difficulty' => 'oson',
                        'verdict' => 'JUDA SAMARALI ✅',
                    ],
                ],
            ],
            'cause_effect_matrix' => [
                [
                    'id' => 1,
                    'problem' => 'Ideal mijoz aniq emas',
                    'current_impact' => 'Marketing xarajatlari samarasiz, konversiya past',
                    'monthly_loss' => 5000000,
                    'solution' => [
                        'action' => '9 ta savolga javob bering',
                        'module' => 'Dream Buyer',
                        'module_route' => '/onboarding/dream-buyer',
                        'time' => '30 daqiqa',
                        'difficulty' => 'oson',
                    ],
                    'expected_result' => [
                        'metric' => 'Konversiya',
                        'improvement' => '+45%',
                        'monthly_gain' => 5000000,
                    ],
                    'roi_percent' => 19900,
                    'payback_days' => 1,
                    'priority' => 1,
                ],
                [
                    'id' => 2,
                    'problem' => 'Taklif zaif',
                    'current_impact' => 'Mijozlar qaror qila olmaydi, sotuvlar past',
                    'monthly_loss' => 7000000,
                    'solution' => [
                        'action' => 'Rad qilib bo\'lmas taklif yarating',
                        'module' => 'Taklif',
                        'module_route' => '/onboarding/offer',
                        'time' => '45 daqiqa',
                        'difficulty' => 'o\'rta',
                    ],
                    'expected_result' => [
                        'metric' => 'Sotuvlar',
                        'improvement' => '+60%',
                        'monthly_gain' => 7000000,
                    ],
                    'roi_percent' => 18567,
                    'payback_days' => 1,
                    'priority' => 2,
                ],
                [
                    'id' => 3,
                    'problem' => 'Mijozlarga javob sekin',
                    'current_impact' => 'Leadlar yo\'qolmoqda, raqobatchilar olib ketmoqda',
                    'monthly_loss' => 3000000,
                    'solution' => [
                        'action' => 'Instagram AI yoqing',
                        'module' => 'Instagram AI',
                        'module_route' => '/business/instagram-ai',
                        'time' => '20 daqiqa',
                        'difficulty' => 'oson',
                    ],
                    'expected_result' => [
                        'metric' => 'Javob tezligi',
                        'improvement' => '+80%',
                        'monthly_gain' => 3000000,
                    ],
                    'roi_percent' => 17864,
                    'payback_days' => 1,
                    'priority' => 3,
                ],
            ],
            'quick_strategies' => [
                'marketing' => [
                    'target_audience' => '25-45 yoshdagi tadbirkorlar, Toshkent shahrida yashovchi, onlayn ta\'lim va biznes o\'sishiga qiziquvchi',
                    'content_frequency' => [
                        'instagram_posts' => 5,
                        'instagram_stories' => 3,
                        'telegram_posts' => 2,
                    ],
                    'best_times' => ['12:00', '19:00'],
                    'weekly_budget' => 1250000,
                    'expected_results' => [
                        'reach_increase' => '+60%',
                        'leads_increase' => '+40%',
                    ],
                ],
                'sales' => [
                    'current_conversion' => 2,
                    'target_conversion' => 5,
                    'pricing_recommendation' => [
                        'basic' => ['price' => 1500000, 'target_percent' => 30],
                        'standard' => ['price' => 2500000, 'target_percent' => 50],
                        'premium' => ['price' => 5000000, 'target_percent' => 20],
                    ],
                    'top_objections' => [
                        [
                            'objection' => 'Qimmat',
                            'response' => 'Bir oylik natija bilan xarajatlar 3x qaytadi. Biz kafolat beramiz.',
                            'success_rate' => 65,
                        ],
                        [
                            'objection' => 'Vaqtim yo\'q',
                            'response' => 'Kuniga faqat 30 daqiqa. Avtomatlashtirish 80% ishni bajaradi.',
                            'success_rate' => 72,
                        ],
                    ],
                ],
                'advertising' => [
                    'monthly_budget' => 5000000,
                    'channel_split' => [
                        'instagram' => ['percent' => 35, 'expected_leads' => 40],
                        'telegram' => ['percent' => 30, 'expected_leads' => 50],
                        'facebook' => ['percent' => 15, 'expected_leads' => 15],
                        'google' => ['percent' => 15, 'expected_leads' => 12],
                        'retargeting' => ['percent' => 5, 'expected_leads' => 8],
                    ],
                    'expected_roas' => 250,
                ],
            ],
            'ideal_customer_analysis' => [
                'score' => rand(20, 60),
                'completeness_percent' => rand(30, 70),
                'demographics' => '25-45 yoshdagi tadbirkorlar, Toshkent shahrida yashovchi',
                'pain_points' => ['Vaqt tanqisligi', 'Marketing bilim yo\'qligi'],
                'desires' => ['Ko\'proq mijoz', 'Daromadni oshirish'],
                'behavior' => 'Telegram va Instagram orqali axborot qidiradi, onlayn ta\'lim olishga qiziqadi',
                'channels' => ['telegram' => 60, 'instagram' => 40],
                'missing_fields' => ['frustrations', 'day_in_life'],
                'recommendation' => 'Dream Buyer bo\'limini to\'ldiring',
            ],
            'offer_strength' => [
                'score' => rand(30, 70),
                'value_score' => rand(4, 8),
                'uniqueness_score' => rand(3, 7),
                'urgency_score' => rand(2, 6),
                'guarantee_score' => rand(3, 7),
                'improvements' => [
                    'Mijozga aniq natijani ko\'rsating - "30 kunda 50% ko\'proq mijoz"',
                    'Pul qaytarish kafolatini qo\'shing',
                    'Vaqt chegarasini belgilang - "Faqat bu hafta"',
                ],
            ],
            'channels_analysis' => [
                'channels' => [
                    ['name' => 'Instagram', 'effectiveness' => 'low', 'recommendation' => 'Hozircha ulanmagan. Instagram profilingizni ulang va kontentni muntazam joylashtiring'],
                    ['name' => 'Telegram', 'effectiveness' => 'medium', 'recommendation' => 'Bot yarating va mijozlar bilan avtomatik muloqot o\'rnating'],
                    ['name' => 'Facebook', 'effectiveness' => 'high', 'recommendation' => 'Facebook Business sahifangizni faollashtiring va reklama kampaniyalarini boshlang'],
                ],
                'recommended_channels' => ['TikTok', 'YouTube Shorts', 'Google Ads'],
            ],
            'funnel_analysis' => [
                'overall_conversion' => rand(2, 8),
                'stages' => [
                    ['name' => 'Xabardorlik', 'conversion_rate' => 100, 'health' => 'good'],
                    ['name' => 'Qiziqish', 'conversion_rate' => 30, 'health' => 'warning'],
                    ['name' => 'Qaror', 'conversion_rate' => 5, 'health' => 'bad'],
                    ['name' => 'Xarid', 'conversion_rate' => 2, 'health' => 'bad'],
                ],
                'bottlenecks' => [
                    'Qiziqish -> Qaror bosqichida 83% yo\'qotish mavjud',
                    'Taklif yetarlicha kuchli emas - rad qilib bo\'lmas taklif kerak',
                    'Follow-up tizimi yo\'q - mijozlar unutilmoqda',
                ],
            ],
            'automation_analysis' => [
                'score' => rand(10, 40),
                'chatbot_enabled' => false,
                'followup_enabled' => false,
                'lost_leads_percent' => rand(40, 70),
                'recommendations' => ['Instagram AI ni yoqing', 'Avtomatik follow-up'],
            ],
            'risks' => [
                'threats' => [
                    'Raqobatchilar tez o\'sib, bozor ulushingizni olishi mumkin',
                    'Mijozlar bazasi qisqarishi - hozirgi mijozlar ketishi xavfi',
                    'Marketing samaradorligi pasayishi - pul behuda sarflanishi',
                ],
                'opportunities' => [
                    'AI avtomatlashtirish orqali xarajatlarni 40% kamaytirish',
                    'Dream Buyer metodologiyasi bilan konversiyani 2x oshirish',
                    'Instagram AI orqali 24/7 mijozlarga xizmat ko\'rsatish',
                ],
            ],
            'swot' => [
                'strengths' => [
                    'Biznes g\'oyasi innovatsion va bozorda talab yuqori',
                    'Jamoa tajribali va motivatsiyali',
                    'Mahsulot sifati raqobatchilardan ustun',
                ],
                'weaknesses' => [
                    'Marketing tizimi to\'liq shakllanmagan',
                    'Mijozlar bazasi kichik va o\'sish sekin',
                    'Avtomatlashtirish darajasi past',
                ],
                'opportunities' => [
                    'Onlayn marketing orqali yangi auditoriyaga yetish',
                    'AI texnologiyalari bilan samaradorlikni oshirish',
                    'Hamkorlik dasturlari orqali tarqatish kanallarini kengaytirish',
                ],
                'threats' => [
                    'Raqobatchilar tez o\'smoqda',
                    'Iqtisodiy beqarorlik xarid qobiliyatiga ta\'sir qilishi mumkin',
                    'Texnologik o\'zgarishlar tez sodir bo\'lmoqda',
                ],
            ],
            'action_plan' => [
                'total_time_hours' => 5,
                'total_potential_savings' => 15000000,
                'steps' => [
                    ['order' => 1, 'title' => 'Ideal mijozni aniqlang', 'module_route' => '/onboarding/dream-buyer', 'module_name' => 'Dream Buyer', 'time_minutes' => 30, 'impact_stars' => 5, 'why' => 'Barcha marketing harakatlarining asosi', 'similar_business_result' => '+45% konversiya', 'timeline' => 'today'],
                    ['order' => 2, 'title' => 'Rad qilib bo\'lmas taklif yarating', 'module_route' => '/onboarding/offer', 'module_name' => 'Taklif', 'time_minutes' => 45, 'impact_stars' => 5, 'why' => 'Mijozlarni jalb qilish uchun', 'similar_business_result' => '+60% sotuvlar', 'timeline' => 'today'],
                    ['order' => 3, 'title' => 'Instagram AI ni sozlang', 'module_route' => '/business/instagram-ai', 'module_name' => 'Instagram AI', 'time_minutes' => 20, 'impact_stars' => 4, 'why' => 'Avtomatik javob berish', 'similar_business_result' => '+80% javob tezligi', 'timeline' => 'this_week'],
                ],
            ],
            'expected_results' => [
                'now' => ['score' => $score, 'leads_weekly' => 10, 'conversion' => 2, 'revenue_change' => 0],
                '30_days' => [
                    'health_score_improvement' => 15,
                    'conversion_improvement' => 3,
                    'description' => 'Ideal mijoz aniqlanadi, taklif optimallashtiriladi, dastlabki natijalar ko\'rinadi',
                ],
                '60_days' => [
                    'health_score_improvement' => 25,
                    'revenue_improvement' => 40,
                    'description' => 'Marketing kanallari yaxshilanadi, sotuvlar barqarorlashadi, mijozlar soni oshadi',
                ],
                '90_days' => [
                    'health_score_improvement' => 35,
                    'total_revenue_increase' => 8000000,
                    'description' => 'To\'liq tizim ishlaydi, avtomatlashtirish tugallanadi, barqaror o\'sish boshlandi',
                ],
            ],
            'platform_recommendations' => [
                [
                    'module' => 'Dream Buyer',
                    'reason' => '9 ta savol orqali ideal mijozni aniqlang - barcha marketing harakatlarining asosi',
                    'priority' => 'yuqori',
                    'route' => '/onboarding/dream-buyer',
                ],
                [
                    'module' => 'Rad qilib bo\'lmas taklif',
                    'reason' => 'Taqqoslab bo\'lmaydigan taklif yarating va konversiyani oshiring',
                    'priority' => 'yuqori',
                    'route' => '/onboarding/offer',
                ],
                [
                    'module' => 'Instagram AI',
                    'reason' => 'Mijozlarga avtomatik javob berish va yo\'qotilgan leadlarni qaytarish',
                    'priority' => 'o\'rta',
                    'route' => '/business/instagram-ai',
                ],
            ],
            'recommended_videos' => [
                ['title' => 'Dream Buyer metodologiyasi', 'duration' => '15 daqiqa', 'url' => '/academy/dream-buyer', 'related_module' => 'Dream Buyer'],
            ],
        ];
    }

    /**
     * Run full diagnostics for a business
     */
    public function runDiagnostics(Business $business, bool $forceRefresh = false): AIDiagnostic
    {
        // 1. Cache tekshirish (24 soat valid)
        $cacheKey = "ai_diagnostics:{$business->id}";

        if (!$forceRefresh) {
            $cachedId = Cache::get($cacheKey);
            if ($cachedId) {
                $cached = AIDiagnostic::find($cachedId);
                if ($cached && $cached->isCompleted() && !$cached->isExpired()) {
                    return $cached;
                }
            }
        }

        // 2. Yangi diagnostika yaratish
        $diagnostic = AIDiagnostic::create([
            'business_id' => $business->id,
            'diagnostic_type' => 'onboarding',
            'status' => 'processing',
            'started_at' => now(),
            'ai_model' => $this->model,
        ]);

        try {
            // 3. Ma'lumotlarni yig'ish
            $businessData = $this->collectBusinessData($business);
            $benchmarks = $this->getIndustryBenchmarks($business);
            $successStories = $this->getSuccessStories($business);

            // 4. Prompt yaratish
            $systemPrompt = $this->getSystemPrompt();
            $userPrompt = $this->buildUserPrompt($businessData, $benchmarks, $successStories);

            // 5. Claude API ga so'rov
            $startTime = microtime(true);
            $response = $this->callClaudeApi($systemPrompt, $userPrompt);
            $generationTime = (int) ((microtime(true) - $startTime) * 1000);

            // 6. Javobni parse qilish
            $analysisData = $this->parseResponse($response['content']);

            // 7. Diagnostikani yangilash
            $diagnostic->update([
                'status' => 'completed',
                'completed_at' => now(),
                'overall_score' => $analysisData['overall_score'] ?? 0,
                'status_level' => $analysisData['status_level'] ?? 'medium',
                'status_message' => $analysisData['status_message'] ?? '',
                'industry_avg_score' => $analysisData['industry_avg_score'] ?? null,
                'money_loss_analysis' => $analysisData['money_loss_analysis'] ?? null,
                'ideal_customer_analysis' => $analysisData['ideal_customer_analysis'] ?? null,
                'offer_strength' => $analysisData['offer_strength'] ?? null,
                'channels_analysis' => $analysisData['channels_analysis'] ?? null,
                'funnel_analysis' => $analysisData['funnel_analysis'] ?? null,
                'roi_calculations' => $analysisData['roi_calculations'] ?? null,
                'cause_effect_matrix' => $analysisData['cause_effect_matrix'] ?? null,
                'quick_strategies' => $analysisData['quick_strategies'] ?? null,
                'automation_analysis' => $analysisData['automation_analysis'] ?? null,
                'risks' => $analysisData['risks'] ?? null,
                'action_plan' => $analysisData['action_plan'] ?? null,
                'expected_results' => $analysisData['expected_results'] ?? null,
                'platform_recommendations' => $analysisData['platform_recommendations'] ?? null,
                'recommended_videos' => $analysisData['recommended_videos'] ?? null,
                'tokens_used' => $response['tokens'] ?? 0,
                'generation_time_ms' => $generationTime,
                'expires_at' => now()->addDays(7),
            ]);

            // 8. Action progress yaratish
            $this->createActionProgress($diagnostic);

            // 9. Cache ga saqlash
            Cache::put($cacheKey, $diagnostic->id, now()->addHours(24));

            return $diagnostic->fresh();

        } catch (\Exception $e) {
            Log::error('Claude Diagnostic Error', [
                'business_id' => $business->id,
                'diagnostic_id' => $diagnostic->id,
                'error' => $e->getMessage(),
            ]);

            $diagnostic->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Biznes ma'lumotlarini yig'ish
     */
    private function collectBusinessData(Business $business): array
    {
        // Get first dream buyer (relationship is HasMany)
        $dreamBuyer = $business->dreamBuyers()->first();
        $maturity = $business->maturityAssessment;

        // Safely get problems (with active scope if exists)
        $problems = [];
        try {
            $problemsQuery = $business->problems();
            if (method_exists($problemsQuery->getModel(), 'scopeActive')) {
                $problemsQuery = $problemsQuery->active();
            }
            $problems = $problemsQuery->get()->map(fn($p) => [
                'category' => $p->category ?? '',
                'title' => $p->title ?? '',
                'description' => $p->description ?? '',
                'severity' => $p->severity ?? 'low',
            ])->toArray();
        } catch (\Exception $e) {
            // Ignore if problems table doesn't exist
        }

        // Safely get competitors
        $competitors = [];
        try {
            $competitors = $business->competitors()->get()->map(fn($c) => [
                'name' => $c->name ?? '',
                'strengths' => $c->strengths ?? [],
                'weaknesses' => $c->weaknesses ?? [],
            ])->toArray();
        } catch (\Exception $e) {
            // Ignore if competitors table doesn't exist
        }

        return [
            // Asosiy ma'lumotlar
            'business_name' => $business->name,
            'industry' => $business->category ?? $business->industry ?? 'Noma\'lum',
            'sub_industry' => $business->sub_category ?? $business->sub_industry ?? null,
            'business_type' => $business->business_type ?? null,
            'business_model' => $business->business_model ?? null,
            'employee_count' => $business->team_size ?? $business->employee_count ?? null,
            'location' => $business->city ?? $business->region ?? null,
            'website' => $business->website ?? null,
            'description' => $business->description ?? null,

            // Maqsadlar
            'dream_outcome' => $business->dream_outcome ?? $maturity?->business_goals ?? null,
            'main_challenges' => $maturity?->main_challenges ?? [],
            'monthly_revenue' => $maturity?->monthly_revenue_range ?? null,

            // Dream Buyer (9 savol)
            'where_spends_time' => $dreamBuyer?->where_spend_time ?? null,
            'information_sources' => $dreamBuyer?->info_sources ?? null,
            'frustrations' => $dreamBuyer?->frustrations ?? null,
            'dreams' => $dreamBuyer?->dreams ?? null,
            'fears' => $dreamBuyer?->fears ?? null,
            'preferred_channel' => $dreamBuyer?->communication_preferences ?? null,
            'language_jargon' => $dreamBuyer?->language_style ?? null,
            'day_in_life' => $dreamBuyer?->daily_routine ?? null,
            'happiness_factors' => $dreamBuyer?->happiness_triggers ?? null,

            // Integratsiyalar
            'instagram_connected' => $business->hasIntegration('instagram'),
            'instagram_stats' => $this->getInstagramStats($business),
            'telegram_connected' => $business->hasIntegration('telegram'),
            'telegram_stats' => $this->getTelegramStats($business),
            'amocrm_connected' => $business->hasIntegration('amocrm'),

            // Metrikalar
            'sales_metrics' => $business->salesMetrics?->toArray() ?? [],
            'marketing_metrics' => $business->marketingMetrics?->toArray() ?? [],

            // Muammolar
            'problems' => $problems,

            // Raqobatchilar
            'competitors' => $competitors,
        ];
    }

    /**
     * Industry benchmarks olish
     */
    private function getIndustryBenchmarks(Business $business): array
    {
        try {
            $industry = $business->category ?? $business->industry ?? 'default';
            return IndustryBenchmark::getDefaultBenchmarks($industry);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * O'xshash bizneslar muvaffaqiyat tarixi
     */
    private function getSuccessStories(Business $business, int $limit = 5): array
    {
        try {
            $industry = $business->category ?? $business->industry ?? 'default';

            return BusinessSuccessStory::where('industry', $industry)
                ->where('growth_percent', '>', 50)
                ->orderBy('growth_percent', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn($story) => $story->toApiArray())
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Instagram statistikasi
     */
    private function getInstagramStats(Business $business): array
    {
        $integration = $business->integrations()
            ->where('type', 'instagram')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return ['connected' => false];
        }

        return [
            'connected' => true,
            'followers' => $integration->metadata['followers'] ?? 0,
            'posts' => $integration->metadata['media_count'] ?? 0,
            'engagement_rate' => $integration->metadata['engagement_rate'] ?? 0,
        ];
    }

    /**
     * Telegram statistikasi
     */
    private function getTelegramStats(Business $business): array
    {
        $integration = $business->integrations()
            ->whereIn('type', ['telegram', 'telegram_channel', 'telegram_bot'])
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return ['connected' => false];
        }

        return [
            'connected' => true,
            'subscribers' => $integration->metadata['subscribers'] ?? 0,
            'type' => $integration->type,
        ];
    }

    /**
     * System Prompt
     */
    private function getSystemPrompt(): string
    {
        return DiagnosticsSystemPrompt::get();
    }

    /**
     * User Prompt yaratish
     */
    private function buildUserPrompt(array $businessData, array $benchmarks, array $successStories): string
    {
        return DiagnosticsUserPrompt::build($businessData, $benchmarks, $successStories);
    }

    /**
     * Claude API ga so'rov
     */
    private function callClaudeApi(string $systemPrompt, string $userPrompt): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(180)->post($this->apiUrl, [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
                // Prefill to force Claude to start with JSON directly
                ['role' => 'assistant', 'content' => '{']
            ]
        ]);

        if (!$response->successful()) {
            Log::error('Claude API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Claude API error: ' . $response->body());
        }

        $data = $response->json();

        // Since we prefilled with '{', prepend it to the response
        $content = '{' . ($data['content'][0]['text'] ?? '');

        return [
            'content' => $content,
            'tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
        ];
    }

    /**
     * Javobni parse qilish
     */
    private function parseResponse(string $content): array
    {
        // Log raw response for debugging
        Log::info('Claude API raw response', [
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 1000),
            'content_end' => substr($content, -500),
        ]);

        // Clean the content - remove any markdown code blocks
        $cleanContent = $content;

        // Remove ```json ... ``` wrapper if present (handle both complete and incomplete blocks)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $content, $codeBlockMatch)) {
            $cleanContent = trim($codeBlockMatch[1]);
            Log::info('Extracted from complete code block');
        } elseif (preg_match('/```(?:json)?\s*([\s\S]*)/i', $content, $codeBlockMatch)) {
            // Handle truncated code block (no closing ```)
            $cleanContent = trim($codeBlockMatch[1]);
            Log::info('Extracted from truncated code block');
        }

        // Find the outermost JSON object by matching braces
        $jsonStart = strpos($cleanContent, '{');
        if ($jsonStart === false) {
            Log::error('No JSON object found in response', ['content' => substr($content, 0, 1000)]);
            throw new \Exception('Could not find JSON in response');
        }

        // Extract JSON by counting braces
        $braceCount = 0;
        $jsonEnd = $jsonStart;
        $inString = false;
        $escapeNext = false;

        for ($i = $jsonStart; $i < strlen($cleanContent); $i++) {
            $char = $cleanContent[$i];

            if ($escapeNext) {
                $escapeNext = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $escapeNext = true;
                continue;
            }

            if ($char === '"' && !$escapeNext) {
                $inString = !$inString;
                continue;
            }

            if (!$inString) {
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        $jsonEnd = $i;
                        break;
                    }
                }
            }
        }

        // Check if JSON was truncated (braces not balanced)
        if ($braceCount > 0) {
            Log::warning('JSON appears truncated, attempting to fix', [
                'open_braces' => $braceCount,
            ]);
            // Try to close the JSON properly
            $jsonString = substr($cleanContent, $jsonStart);
            $jsonString = $this->tryCompleteJson($jsonString, $braceCount);
        } else {
            $jsonString = substr($cleanContent, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        // Try to parse
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON parse error', [
                'error' => json_last_error_msg(),
                'json_preview' => substr($jsonString, 0, 1000),
                'json_end' => substr($jsonString, -500),
            ]);

            // Try to fix common JSON issues
            $fixedJson = $this->tryFixJson($jsonString);
            $data = json_decode($fixedJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
        }

        return $data;
    }

    /**
     * Try to fix common JSON issues
     */
    private function tryFixJson(string $json): string
    {
        // Remove trailing commas before } or ]
        $json = preg_replace('/,\s*([\}\]])/', '$1', $json);

        // Fix unescaped quotes in strings (basic attempt)
        // This is tricky and might not work for all cases

        // Remove any control characters
        $json = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $json);

        return $json;
    }

    /**
     * Try to complete truncated JSON by adding missing closing braces/brackets
     */
    private function tryCompleteJson(string $json, int $missingBraces): string
    {
        // Remove any incomplete elements at the end
        // Look for patterns like: "key": or "key": " or incomplete arrays
        $json = preg_replace('/,\s*"[^"]*"?\s*:\s*[^,\}\]]*$/', '', $json);
        $json = preg_replace('/,\s*$/', '', $json);
        $json = preg_replace('/"\s*$/', '"', $json);
        $json = preg_replace('/:\s*$/', ': null', $json);

        // Count brackets too
        $openBrackets = substr_count($json, '[') - substr_count($json, ']');

        // Add missing closing brackets
        for ($i = 0; $i < $openBrackets; $i++) {
            $json .= ']';
        }

        // Add missing closing braces
        for ($i = 0; $i < $missingBraces; $i++) {
            $json .= '}';
        }

        return $json;
    }

    /**
     * Action progress yaratish
     */
    private function createActionProgress(AIDiagnostic $diagnostic): void
    {
        $steps = $diagnostic->action_plan['steps'] ?? [];

        foreach ($steps as $step) {
            $diagnostic->actionProgress()->create([
                'business_id' => $diagnostic->business_id,
                'step_order' => $step['order'] ?? 0,
                'step_title' => $step['title'] ?? '',
                'module_route' => $step['module_route'] ?? null,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Check if Claude API is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Update processing step and save to database immediately
     */
    private function updateProcessingStep(AIDiagnostic $diagnostic, string $step): void
    {
        $diagnostic->update(['processing_step' => $step]);

        // Force DB commit by refreshing
        $diagnostic->refresh();

        Log::info('Diagnostic processing step updated', [
            'diagnostic_id' => $diagnostic->id,
            'step' => $step,
        ]);
    }

    /**
     * Run diagnostics for an existing diagnostic record (called from job)
     */
    public function runDiagnosticsForExisting(AIDiagnostic $diagnostic, Business $business): AIDiagnostic
    {
        try {
            // ============================================
            // STEP 1: Ma'lumotlar yig'ilmoqda
            // ============================================
            $this->updateProcessingStep($diagnostic, 'aggregating_data');
            $diagnostic->update([
                'status' => 'processing',
                'started_at' => now(),
                'ai_model' => $this->testMode ? 'test-mode' : $this->model,
            ]);

            // Test mode: use mock data instead of Claude API
            if ($this->testMode) {
                Log::info('Running diagnostic in TEST MODE - no API call', [
                    'diagnostic_id' => $diagnostic->id,
                    'business_id' => $business->id,
                ]);

                // Simulate all steps with realistic timing for professional UX
                usleep(800000); // 0.8 second

                // ============================================
                // STEP 2: KPI lar hisoblanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'calculating_kpis');
                usleep(700000); // 0.7 second

                // ============================================
                // STEP 3: Benchmark bilan taqqoslanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'comparing_benchmarks');
                usleep(700000); // 0.7 second

                // ============================================
                // STEP 4: Ballar hisoblanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'calculating_scores');
                usleep(600000); // 0.6 second

                // ============================================
                // STEP 5: AI tahlil qilmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'ai_analysis');
                usleep(1200000); // 1.2 seconds - longest step

                // ============================================
                // STEP 6: Tavsiyalar yaratilmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'generating_recommendations');
                usleep(700000); // 0.7 second

                // ============================================
                // STEP 7: Natijalar saqlanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'saving_results');
                usleep(500000); // 0.5 second

                $analysisData = $this->getMockAnalysisData($business);
                $generationTime = 5200;
                $tokensUsed = 0;
            } else {
                // Real API mode - track each step

                // STEP 1 already done above - collecting business data
                $businessData = $this->collectBusinessData($business);

                // ============================================
                // STEP 2: KPI lar hisoblanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'calculating_kpis');
                $benchmarks = $this->getIndustryBenchmarks($business);
                $successStories = $this->getSuccessStories($business);

                // ============================================
                // STEP 3: Benchmark bilan taqqoslanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'comparing_benchmarks');
                // Small delay for visual feedback
                usleep(300000);

                // ============================================
                // STEP 4: Ballar hisoblanmoqda / Prompt yaratish
                // ============================================
                $this->updateProcessingStep($diagnostic, 'calculating_scores');
                $systemPrompt = $this->getSystemPrompt();
                $userPrompt = $this->buildUserPrompt($businessData, $benchmarks, $successStories);

                // ============================================
                // STEP 5: AI tahlil qilmoqda - Claude API call
                // ============================================
                $this->updateProcessingStep($diagnostic, 'ai_analysis');
                $startTime = microtime(true);
                $response = $this->callClaudeApi($systemPrompt, $userPrompt);
                $generationTime = (int) ((microtime(true) - $startTime) * 1000);

                // ============================================
                // STEP 6: Tavsiyalar yaratilmoqda - Parse response
                // ============================================
                $this->updateProcessingStep($diagnostic, 'generating_recommendations');
                $analysisData = $this->parseResponse($response['content']);
                $tokensUsed = $response['tokens'] ?? 0;

                // ============================================
                // STEP 7: Natijalar saqlanmoqda
                // ============================================
                $this->updateProcessingStep($diagnostic, 'saving_results');
            }

            // Final update - save all results
            $diagnostic->update([
                'status' => 'completed',
                'completed_at' => now(),
                'processing_step' => null, // Clear when done
                'overall_score' => $analysisData['overall_score'] ?? 0,
                'status_level' => $analysisData['status_level'] ?? 'medium',
                'status_message' => $analysisData['status_message'] ?? '',
                'industry_avg_score' => $analysisData['industry_avg_score'] ?? null,
                'category_scores' => $analysisData['category_scores'] ?? null,
                'money_loss_analysis' => $analysisData['money_loss_analysis'] ?? null,
                'ideal_customer_analysis' => $analysisData['ideal_customer_analysis'] ?? null,
                'offer_strength' => $analysisData['offer_strength'] ?? null,
                'channels_analysis' => $analysisData['channels_analysis'] ?? null,
                'funnel_analysis' => $analysisData['funnel_analysis'] ?? null,
                'roi_calculations' => $analysisData['roi_calculations'] ?? null,
                'cause_effect_matrix' => $analysisData['cause_effect_matrix'] ?? null,
                'quick_strategies' => $analysisData['quick_strategies'] ?? null,
                'automation_analysis' => $analysisData['automation_analysis'] ?? null,
                'risks' => $analysisData['risks'] ?? null,
                'swot' => $analysisData['swot'] ?? null,
                'action_plan' => $analysisData['action_plan'] ?? null,
                'expected_results' => $analysisData['expected_results'] ?? null,
                'platform_recommendations' => $analysisData['platform_recommendations'] ?? null,
                'recommended_videos' => $analysisData['recommended_videos'] ?? null,
                'tokens_used' => $tokensUsed,
                'generation_time_ms' => $generationTime,
                'expires_at' => now()->addDays(7),
            ]);

            // Create action progress records
            $this->createActionProgress($diagnostic);

            // Cache result
            $cacheKey = "ai_diagnostics:{$business->id}";
            Cache::put($cacheKey, $diagnostic->id, now()->addHours(24));

            Log::info('Diagnostic completed successfully', [
                'diagnostic_id' => $diagnostic->id,
                'overall_score' => $analysisData['overall_score'] ?? 0,
                'generation_time_ms' => $generationTime,
            ]);

            return $diagnostic->fresh();

        } catch (\Exception $e) {
            Log::error('Claude Diagnostic Error', [
                'business_id' => $business->id,
                'diagnostic_id' => $diagnostic->id,
                'error' => $e->getMessage(),
            ]);

            $diagnostic->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}
