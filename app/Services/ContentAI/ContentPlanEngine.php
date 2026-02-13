<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\ContentPost;
use App\Models\ContentPlanGeneration;
use App\Models\WeeklyPlan;
use App\Services\KPI\BusinessCategoryMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Content Plan Engine — "Smart Content Loop" ning asosiy miyasi (GIBRID)
 *
 * GIBRID ARXITEKTURA:
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  1-QATLAM: Algoritmik Baza (har doim ishlaydi, AI kerak emas) │
 * │  - IndustryContentLibrary → soha mavzulari (100% shablon)      │
 * │  - InstagramAlgorithmEngine → IG qoidalar (100% qoidalar)      │
 * │  - Scoring + Ranking → ball berish (100% matematik)            │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  2-QATLAM: Ma'lumotga asoslangan (data bo'lganda yaxshilanadi) │
 * │  - CrossBusinessLearning → soha muvaffaqiyatlari (SQL)         │
 * │  - SurveyContentBridge → mijoz og'riqlari (SQL)                │
 * │  - ContentPerformanceFeedback → oldingi natijalar (SQL)        │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  3-QATLAM: AI boyitish (ixtiyoriy, opsional)                  │
 * │  - ContentAIEnrichmentService → Claude AI orqali boyitish     │
 * │  - Kuchli hooklar, ssenariylar, captionlar                    │
 * │  - AI ishlamasa algoritmik kontent o'zgarmaydi                │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * Hozirgi holat: 1-qatlam + 2-qatlam + 3-qatlam = gibrid algoritm + AI
 */
class ContentPlanEngine
{
    public function __construct(
        private CrossBusinessLearningService $crossBusiness,
        private SurveyContentBridge $surveyBridge,
        private InstagramAlgorithmEngine $igEngine,
        private ContentPerformanceFeedback $feedback,
        private IndustryContentLibrary $industryLibrary,
        private ContentAIEnrichmentService $aiEnrichment,
    ) {}

    /**
     * Haftalik smart kontent reja yaratish (GIBRID)
     *
     * @return array{plan_generation: ContentPlanGeneration, items: Collection, algorithm_breakdown: array}
     */
    public function generateWeeklyPlan(
        string $businessId,
        string $userId,
        ?string $startDate = null,
        ?WeeklyPlan $weeklyPlan = null
    ): array {
        $start = $startDate ? Carbon::parse($startDate)->startOfWeek() : now()->startOfWeek()->addWeek();
        $end = $start->copy()->endOfWeek();

        try {
            $business = Business::withoutGlobalScopes()->with(['industryRelation'])->findOrFail($businessId);
            $industryCode = $this->resolveIndustryCode($business);

            // === MANBALARNI YIG'ISH ===
            $sources = $this->collectAllSources($business, $businessId);

            // === MAVZULARNI BIRLASHTIRISH VA BAHOLASH ===
            $scoredTopics = $this->buildHybridTopicList(
                $sources,
                $business,
                $industryCode,
                $businessId
            );

            // === HAFTALIK SLOTLARGA TAQSIMLASH ===
            $igSchedule = $sources['ig_schedule'];
            $weeklyItems = $this->distributeTopicsToSlots(
                $scoredTopics,
                $igSchedule,
                $business,
                $industryCode,
                $start,
                $end,
                $weeklyPlan
            );

            // === SAQLASH ===
            $items = DB::transaction(function () use ($weeklyItems, $businessId, $userId) {
                $created = collect();
                foreach ($weeklyItems as $itemData) {
                    $item = ContentPost::create(array_merge($itemData, [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'status' => 'scheduled',
                    ]));
                    $created->push($item);
                }

                return $created;
            });

            // === TARIX SAQLASH ===
            $aiEnrichedCount = collect($weeklyItems)->filter(fn ($item) => ($item['ai_suggestions']['is_ai_generated'] ?? false))->count();
            $algorithmBreakdown = $this->buildAlgorithmBreakdown($sources, $scoredTopics, $igSchedule, $aiEnrichedCount);

            $planGeneration = ContentPlanGeneration::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'plan_type' => 'weekly',
                'weekly_plan_id' => $weeklyPlan?->id,
                'monthly_plan_id' => $weeklyPlan?->monthly_plan_id,
                'period_start' => $start,
                'period_end' => $end,
                'input_data' => [
                    'industry_id' => $business->industry_id,
                    'industry_name' => $business->industryRelation?->name_uz,
                    'industry_code' => $industryCode,
                    'business_type' => $business->business_type,
                    'category' => $business->category,
                    'hybrid_mode' => true,
                ],
                'niche_scores_used' => array_slice($sources['niche_topics'], 0, 5),
                'pain_points_used' => array_slice($sources['pain_topics'], 0, 5),
                'algorithm_breakdown' => $algorithmBreakdown,
                'items_generated' => $items->count(),
                'status' => 'generated',
            ]);

            return [
                'plan_generation' => $planGeneration,
                'items' => $items,
                'algorithm_breakdown' => $algorithmBreakdown,
            ];
        } catch (\Throwable $e) {
            Log::error('ContentPlanEngine: generateWeeklyPlan failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Barcha manbalardan ma'lumot yig'ish
     */
    private function collectAllSources(Business $business, string $businessId): array
    {
        // 1-qatlam: Algoritmik baza (HAR DOIM mavjud)
        $industryCode = $this->resolveIndustryCode($business);
        $libraryTopics = $this->industryLibrary->getTopicsForIndustry($industryCode, 20);
        $igSchedule = $this->igEngine->getWeeklyScheduleTemplate($businessId);

        // 2-qatlam: Data-driven (mavjud bo'lsa)
        $nicheTopics = $this->collectNicheTopics($business);
        $painTopics = $this->collectPainPointTopics($businessId);
        $pastPerformance = $this->feedback->getPerformanceSummary($businessId);

        return [
            // Qatlam 1 — algoritmik (har doim bor)
            'library_topics' => $libraryTopics,
            'ig_schedule' => $igSchedule,
            // Qatlam 2 — data-driven (bo'lmasligi mumkin)
            'niche_topics' => $nicheTopics,
            'pain_topics' => $painTopics,
            'past_performance' => $pastPerformance,
        ];
    }

    /**
     * GIBRID MAVZU RO'YXATINI TUZISH
     *
     * Uch qavatli birlashtirish:
     * 1. Data-driven topics (niche + pain) — eng yuqori ustuvorlik
     * 2. Industry library topics — algoritmik baza
     * 3. Freshness filter — yaqinda ishlatilganlarni chetga surish
     */
    private function buildHybridTopicList(
        array $sources,
        Business $business,
        string $industryCode,
        string $businessId
    ): array {
        $weights = $this->getScoringWeights();

        // === 1) Data-driven mavzular (agar ma'lumot bo'lsa) ===
        $dataTopics = $this->scoreDataDrivenTopics(
            $sources['niche_topics'],
            $sources['pain_topics'],
            $sources['past_performance'],
            $weights
        );

        // === 2) Algoritmik kutubxona mavzulari (HAR DOIM mavjud) ===
        $libraryTopics = $this->scoreLibraryTopics(
            $sources['library_topics'],
            $industryCode,
            $weights
        );

        // === 3) Birlashtirish va dublikatlarni olib tashlash ===
        $merged = $this->mergeTopicSources($dataTopics, $libraryTopics);

        // === 4) Yaqinda ishlatilganlarni chetga surish (freshness) ===
        $merged = $this->applyFreshnessFilter($merged, $businessId);

        // Ball bo'yicha saralash
        uasort($merged, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        return array_values($merged);
    }

    /**
     * Data-driven (niche + pain point) mavzularni baholash
     */
    private function scoreDataDrivenTopics(
        array $nicheTopics,
        array $painPointTopics,
        array $pastPerformance,
        array $weights
    ): array {
        $allTopics = [];

        // Niche topics
        foreach ($nicheTopics as $topic) {
            $key = mb_strtolower($topic['topic']);
            $allTopics[$key] = [
                'topic' => $topic['topic'],
                'category' => $topic['category'] ?? 'educational',
                'content_type' => $topic['content_type'] ?? 'post',
                'source' => 'niche_learning',
                'source_label' => 'Soha tahlili',
                'niche_score' => ($topic['score'] / 100) * $weights['niche'],
                'pain_score' => 0,
                'performance_score' => 0,
                'trend_bonus' => $topic['trend'] === 'rising' ? $weights['trend'] : ($topic['trend'] === 'stable' ? $weights['trend'] * 0.5 : 0),
                'total_score' => 0,
                'hashtags' => $topic['sample_hashtags'] ?? [],
                'best_times' => $topic['best_posting_times'] ?? [],
                'hooks' => [],
                'pain_text' => null,
                'description_template' => null,
                'hashtag_seeds' => [],
            ];
        }

        // Pain point topics
        foreach ($painPointTopics as $pp) {
            foreach ($pp['topics'] ?? [] as $topic) {
                $key = mb_strtolower($topic);
                if (isset($allTopics[$key])) {
                    $allTopics[$key]['pain_score'] = ($pp['relevance'] / 100) * $weights['pain_point'];
                    $allTopics[$key]['hooks'] = array_merge($allTopics[$key]['hooks'], $pp['hooks'] ?? []);
                    $allTopics[$key]['pain_text'] = $pp['pain_text'];
                    $allTopics[$key]['source'] = 'niche_and_pain';
                    $allTopics[$key]['source_label'] = 'Soha + Mijoz tahlili';
                } else {
                    $allTopics[$key] = [
                        'topic' => $topic,
                        'category' => $pp['category'] ?? 'pain_point',
                        'content_type' => $pp['content_types'][0] ?? 'post',
                        'source' => 'pain_point',
                        'source_label' => 'Mijoz so\'rovnomasi',
                        'niche_score' => 0,
                        'pain_score' => ($pp['relevance'] / 100) * $weights['pain_point'],
                        'performance_score' => 0,
                        'trend_bonus' => 0,
                        'total_score' => 0,
                        'hashtags' => [],
                        'best_times' => [],
                        'hooks' => $pp['hooks'] ?? [],
                        'pain_text' => $pp['pain_text'],
                        'description_template' => null,
                        'hashtag_seeds' => [],
                    ];
                }
            }
        }

        // Past performance bonus
        if (! empty($pastPerformance['top_themes'])) {
            foreach ($pastPerformance['top_themes'] as $theme => $data) {
                foreach ($allTopics as $key => &$topic) {
                    if (str_contains($key, mb_strtolower($theme))) {
                        $topic['performance_score'] = min(($data['avg_engagement'] ?? 0) * 5, $weights['performance']);
                    }
                }
                unset($topic);
            }
        }

        // Yakuniy ball
        foreach ($allTopics as &$topic) {
            $topic['total_score'] = round(
                $topic['niche_score'] + $topic['pain_score'] + $topic['performance_score'] + $topic['trend_bonus'],
                2
            );
        }
        unset($topic);

        return $allTopics;
    }

    /**
     * Industry library mavzularini baholash (algoritmik baza)
     */
    private function scoreLibraryTopics(array $libraryTopics, string $industryCode, array $weights): array
    {
        $topics = [];
        $baseScore = 50; // Algoritmik mavzular boshlang'ich balli

        foreach ($libraryTopics as $index => $libTopic) {
            $key = mb_strtolower($libTopic['topic']);

            // Kategoriya va content_type diversity bonus
            $diversityBonus = ($index < 5) ? 10 : ($index < 10 ? 5 : 0);

            $topics[$key] = [
                'topic' => $libTopic['topic'],
                'category' => $libTopic['category'] ?? 'educational',
                'content_type' => $libTopic['content_type'] ?? 'post',
                'source' => 'algorithm',
                'source_label' => 'Ichki algoritm',
                'niche_score' => 0,
                'pain_score' => 0,
                'performance_score' => 0,
                'trend_bonus' => 0,
                'total_score' => $baseScore + $diversityBonus - $index,
                'hashtags' => [],
                'best_times' => [],
                'hooks' => $libTopic['hooks'] ?? [],
                'pain_text' => null,
                'description_template' => $libTopic['description_template'] ?? null,
                'hashtag_seeds' => $libTopic['hashtag_seeds'] ?? [],
            ];
        }

        return $topics;
    }

    /**
     * Data-driven va library mavzularni birlashtirish
     * Data-driven mavzular ustunlik oladi, library mavzular to'ldiradi
     */
    private function mergeTopicSources(array $dataTopics, array $libraryTopics): array
    {
        $merged = $dataTopics;

        foreach ($libraryTopics as $key => $libTopic) {
            if (isset($merged[$key])) {
                // Data-driven allaqachon bor — library dan hooks va template ni olish
                if (empty($merged[$key]['hooks']) && ! empty($libTopic['hooks'])) {
                    $merged[$key]['hooks'] = $libTopic['hooks'];
                }
                if (empty($merged[$key]['description_template'])) {
                    $merged[$key]['description_template'] = $libTopic['description_template'];
                }
                if (empty($merged[$key]['hashtag_seeds'])) {
                    $merged[$key]['hashtag_seeds'] = $libTopic['hashtag_seeds'];
                }
            } else {
                // Yangi mavzu — library dan qo'shish
                $merged[$key] = $libTopic;
            }
        }

        return $merged;
    }

    /**
     * Yaqinda joylangan mavzularni chetga surish (takrorlanishning oldini olish)
     */
    private function applyFreshnessFilter(array $topics, string $businessId): array
    {
        // Oxirgi 14 kunda joylangan mavzular
        $recentTitles = ContentPost::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(14))
            ->pluck('title')
            ->map(fn ($t) => mb_strtolower($t))
            ->toArray();

        if (empty($recentTitles)) {
            return $topics;
        }

        foreach ($topics as $key => &$topic) {
            $topicLower = mb_strtolower($topic['topic']);
            foreach ($recentTitles as $recentTitle) {
                // Agar o'xshash mavzu yaqinda ishlatilgan bo'lsa — ballni pasaytirish
                if ($topicLower === $recentTitle || str_contains($topicLower, $recentTitle) || str_contains($recentTitle, $topicLower)) {
                    $topic['total_score'] = max($topic['total_score'] - 30, 5);
                    $topic['freshness_penalty'] = true;
                    break;
                }
            }
        }
        unset($topic);

        return $topics;
    }

    /**
     * Mavzularni haftalik slotlarga taqsimlash (GIBRID)
     */
    private function distributeTopicsToSlots(
        array $scoredTopics,
        array $igSchedule,
        Business $business,
        string $industryCode,
        Carbon $start,
        Carbon $end,
        ?WeeklyPlan $weeklyPlan
    ): array {
        $items = [];
        $aiCallCount = 0;
        $maxAiCalls = 4; // Tezlik uchun: bitta plan da max 4 ta post AI bilan boyitiladi
        $schedule = $igSchedule['schedule'] ?? [];
        $topicIndex = 0;
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $defaultTypes = ['reel', 'carousel', 'post', 'story', 'reel', 'post', 'carousel'];
        $usedCategories = []; // Hafta ichida bir xil category takrorlanmasin

        foreach ($days as $dayIndex => $day) {
            $date = $start->copy()->addDays($dayIndex);

            if ($date->gt($end)) {
                break;
            }

            $daySchedule = $schedule[$day] ?? [];
            $slots = $daySchedule['slots'] ?? [];

            if (empty($slots)) {
                $slots = [['content_type' => $defaultTypes[$dayIndex] ?? 'post', 'time' => '18:00']];
            }

            foreach ($slots as $slot) {
                // Mavzu tanlash (diversifikatsiya bilan)
                $topic = $this->pickNextTopic($scoredTopics, $topicIndex, $usedCategories);
                if (! $topic) {
                    continue;
                }

                $contentType = $slot['content_type'] ?? $topic['content_type'] ?? 'post';
                $purpose = $slot['purpose'] ?? $topic['category'] ?? 'educational';
                $time = $slot['time'] ?? '18:00';

                $usedCategories[] = $topic['category'];

                // IG algorithm tips
                $igTips = $this->igEngine->getContentOptimizationTips($contentType, $purpose);

                // GIBRID description yaratish
                $description = $this->buildHybridDescription($topic, $igTips, $industryCode);

                // 3-QATLAM: AI boyitish (ixtiyoriy, cheklangan)
                $aiContent = null;
                if ($aiCallCount < $maxAiCalls) {
                    $aiContent = $this->aiEnrichment->enrichContentItem(
                        $topic, $contentType, $purpose, $igTips, $business
                    );
                    $aiCallCount++;
                }

                // AI muvaffaqiyatli bo'lsa — captionni almashtirish
                if ($aiContent && ! empty($aiContent['caption'])) {
                    $description = $aiContent['caption'];
                }

                // GIBRID hashtags
                $hashtags = $this->buildHybridHashtags($topic, $industryCode);

                // Source tracking — qayerdan kelgani
                $sourceInfo = $this->buildSourceInfo($topic);

                // AI suggestions tuzish
                $aiSuggestions = [
                    'is_ai_generated' => $aiContent !== null,
                    'generation_method' => $aiContent ? 'hybrid_algorithm_plus_ai' : 'hybrid_algorithm',
                    'source' => $sourceInfo['source'],
                    'source_label' => $sourceInfo['label'],
                    'source_details' => $sourceInfo['details'],
                    'confidence' => $sourceInfo['confidence'],
                    'hooks' => array_slice($topic['hooks'] ?? [], 0, 3),
                    'content_tips' => $igTips['content_type_tips'] ?? [],
                    'caption_rules' => $igTips['caption_rules'] ?? [],
                    'cta_suggestions' => $igTips['cta_suggestions'] ?? [],
                    'algorithm_signals' => $igTips['algorithm_signals'] ?? [],
                    'niche_score' => $topic['niche_score'] ?? 0,
                    'pain_text' => $topic['pain_text'] ?? null,
                    'total_score' => $topic['total_score'] ?? 0,
                    'goal' => $this->mapPurposeToGoal($purpose),
                    'priority' => $this->calculatePriority($topic),
                ];

                // AI ma'lumotlarini qo'shish
                if ($aiContent) {
                    $aiSuggestions['ai_hooks'] = $aiContent['hooks'];
                    $aiSuggestions['ai_caption'] = $aiContent['caption'];
                    $aiSuggestions['ai_script'] = $aiContent['script'];
                    $aiSuggestions['ai_cta'] = $aiContent['cta'];
                }

                $items[] = [
                    'title' => $topic['topic'],
                    'content' => $description,
                    'content_type' => $this->mapCategoryToContentType($purpose),
                    'format' => $this->mapContentTypeToFormat($contentType),
                    'type' => $this->mapContentTypeToFormat($contentType),
                    'platform' => 'Instagram',
                    'scheduled_at' => $date->toDateString().' '.$time.':00',
                    'hashtags' => $hashtags,
                    'ai_suggestions' => $aiSuggestions,
                ];

                $topicIndex++;
            }
        }

        return $items;
    }

    /**
     * Keyingi mavzuni tanlash (diversifikatsiya bilan)
     * Bir xil category ketma-ket kelmasligi uchun
     */
    private function pickNextTopic(array $scoredTopics, int &$index, array $usedCategories): ?array
    {
        $totalTopics = count($scoredTopics);
        if ($totalTopics === 0) {
            return null;
        }

        // Oxirgi 2 ta ishlatilgan category ni tekshirish
        $recentCategories = array_slice($usedCategories, -2);
        $attempts = 0;
        $maxAttempts = $totalTopics;

        while ($attempts < $maxAttempts) {
            $currentIndex = ($index + $attempts) % $totalTopics;
            $topic = $scoredTopics[$currentIndex];

            // Agar oxirgi 2 tasi bilan bir xil bo'lmasa — tanlash
            if (! in_array($topic['category'], $recentCategories) || $attempts >= $totalTopics - 1) {
                $index = $currentIndex + 1;

                return $topic;
            }

            $attempts++;
        }

        // Fallback: birinchi topicni olish
        $index++;

        return $scoredTopics[0] ?? null;
    }

    /**
     * GIBRID description yaratish
     *
     * Ustuvorlik: description_template > pain_text+hooks > generic
     */
    private function buildHybridDescription(array $topic, array $igTips, string $industryCode): string
    {
        $parts = [];

        // 1) Algoritmik shablon description (agar library dan bo'lsa)
        if (! empty($topic['description_template'])) {
            $industryName = BusinessCategoryMapper::getIndustryName($industryCode);
            $desc = str_replace(
                ['{industry}', '{topic}'],
                [$industryName, $topic['topic']],
                $topic['description_template']
            );
            $parts[] = $desc;
        }

        // 2) Pain point (agar survey dan ma'lumot bo'lsa)
        if (! empty($topic['pain_text'])) {
            $parts[] = "Mijoz muammosi: {$topic['pain_text']}";
        }

        // 3) Hook — boshlash uchun g'oya
        if (! empty($topic['hooks'])) {
            $parts[] = "Boshlash uchun g'oya: {$topic['hooks'][0]}";
        }

        // 4) Harakatga chaqiruv
        $ctaSuggestions = $igTips['cta_suggestions'] ?? [];
        if (! empty($ctaSuggestions)) {
            $parts[] = $ctaSuggestions[0];
        }

        // 5) Source ma'lumoti
        if ($topic['source'] === 'niche_learning') {
            $nicheScore = $topic['niche_score'] ?? 0;
            $parts[] = "Sohadagi muvaffaqiyatli kontent asosida tavsiya (Ishonch: {$nicheScore}%)";
        } elseif ($topic['source'] === 'niche_and_pain') {
            $parts[] = "Soha tahlili + mijoz so'rovnomasi asosida tavsiya";
        } elseif ($topic['source'] === 'algorithm') {
            $parts[] = "Ichki algoritm va soha tajribasi asosida tavsiya";
        }

        return implode("\n\n", $parts) ?: $topic['topic'];
    }

    /**
     * GIBRID hashtag yaratish
     */
    private function buildHybridHashtags(array $topic, string $industryCode): array
    {
        $hashtags = [];

        // 1) Niche dan kelgan hashtaglar
        if (! empty($topic['hashtags'])) {
            $hashtags = array_merge($hashtags, $topic['hashtags']);
        }

        // 2) Library dan seed hashtaglar
        if (! empty($topic['hashtag_seeds'])) {
            foreach ($topic['hashtag_seeds'] as $seed) {
                if (! in_array($seed, $hashtags)) {
                    $hashtags[] = $seed;
                }
            }
        }

        // 3) Soha umumiy hashtaglari
        $industryHashtags = $this->getIndustryHashtags($industryCode);
        foreach ($industryHashtags as $tag) {
            if (! in_array($tag, $hashtags)) {
                $hashtags[] = $tag;
            }
        }

        return array_slice($hashtags, 0, 12);
    }

    /**
     * Source ma'lumotini tuzish (transparency uchun)
     */
    private function buildSourceInfo(array $topic): array
    {
        $source = $topic['source'] ?? 'algorithm';

        return match ($source) {
            'niche_learning' => [
                'source' => 'niche_learning',
                'label' => 'Soha tahlili',
                'details' => 'Shu sohadagi muvaffaqiyatli bizneslar tajribasi asosida',
                'confidence' => min(90, 50 + intval($topic['niche_score'] ?? 0)),
            ],
            'pain_point' => [
                'source' => 'pain_point',
                'label' => 'Mijoz so\'rovnomasi',
                'details' => 'Sizning mijozlaringiz og\'riqlari asosida',
                'confidence' => min(85, 40 + intval($topic['pain_score'] ?? 0)),
            ],
            'niche_and_pain' => [
                'source' => 'niche_and_pain',
                'label' => 'Soha + Mijoz tahlili',
                'details' => 'Soha muvaffaqiyatlari va mijoz og\'riqlari birlashtirilgan',
                'confidence' => min(95, 60 + intval($topic['niche_score'] ?? 0) + intval($topic['pain_score'] ?? 0)),
            ],
            'algorithm' => [
                'source' => 'algorithm',
                'label' => 'Ichki algoritm',
                'details' => 'Soha tajribasi va Instagram qoidalari asosida',
                'confidence' => 70,
            ],
            default => [
                'source' => 'algorithm',
                'label' => 'Ichki algoritm',
                'details' => 'Algoritmik tavsiya',
                'confidence' => 60,
            ],
        };
    }

    /**
     * Algorithm breakdown (tarix uchun)
     */
    private function buildAlgorithmBreakdown(array $sources, array $scoredTopics, array $igSchedule, int $aiEnrichedCount = 0): array
    {
        $sourceDistribution = [
            'niche_learning' => 0,
            'pain_point' => 0,
            'niche_and_pain' => 0,
            'algorithm' => 0,
        ];

        foreach ($scoredTopics as $topic) {
            $src = $topic['source'] ?? 'algorithm';
            if (isset($sourceDistribution[$src])) {
                $sourceDistribution[$src]++;
            }
        }

        return [
            'mode' => $aiEnrichedCount > 0 ? 'hybrid_algorithm_plus_ai' : 'hybrid_algorithm',
            'niche_topics_count' => count($sources['niche_topics']),
            'pain_points_count' => count($sources['pain_topics']),
            'library_topics_count' => count($sources['library_topics']),
            'ig_data_source' => $igSchedule['best_times']['data_source'] ?? 'default',
            'past_performance_available' => ! empty($sources['past_performance']),
            'scoring_weights' => $this->getScoringWeights(),
            'source_distribution' => $sourceDistribution,
            'ai_used' => $aiEnrichedCount > 0,
            'ai_enriched_count' => $aiEnrichedCount,
        ];
    }

    // ================================================================
    // YORDAMCHI METHODLAR
    // ================================================================

    private function collectNicheTopics(Business $business): array
    {
        if (! $business->industry_id) {
            return [];
        }

        return $this->crossBusiness->getTopTopicsForIndustry($business->industry_id, 20);
    }

    private function collectPainPointTopics(string $businessId): array
    {
        return $this->surveyBridge->getContentRecommendationsFromPainPoints($businessId, 15);
    }

    private function resolveIndustryCode(Business $business): string
    {
        return BusinessCategoryMapper::getIndustryCode(
            $business->category,
            $business->industryRelation?->name_uz,
            $business->business_type
        );
    }

    /**
     * Soha umumiy hashtaglari
     */
    private function getIndustryHashtags(string $industryCode): array
    {
        return match ($industryCode) {
            'beauty' => ['beauty', 'gozallik', 'salon', 'tashkent', 'uzbekistan'],
            'restaurant' => ['food', 'taom', 'restoran', 'tashkentfood', 'uzbekfood'],
            'ecommerce' => ['onlineshopping', 'onlinesavdo', 'delivery', 'uzbekistan'],
            'retail' => ['shopping', 'xarid', 'dokon', 'tashkent'],
            'service' => ['xizmat', 'service', 'professional', 'tashkent'],
            'saas' => ['tech', 'saas', 'startup', 'innovation'],
            'fitness' => ['fitness', 'gym', 'sport', 'health', 'tashkent'],
            default => ['biznes', 'tashkent', 'uzbekistan', 'business'],
        };
    }

    private function mapCategoryToContentType(string $category): string
    {
        return match ($category) {
            'educational' => 'educational',
            'promotional' => 'promotional',
            'behind_scenes' => 'behind_scenes',
            'engagement' => 'entertaining',
            'testimonial' => 'inspirational',
            'pain_point' => 'educational',
            default => 'educational',
        };
    }

    private function mapContentTypeToFormat(string $contentType): string
    {
        return match ($contentType) {
            'reel' => 'short_video',
            'carousel' => 'carousel',
            'story' => 'story',
            'post' => 'single_image',
            default => 'text_post',
        };
    }

    private function mapPurposeToGoal(string $purpose): string
    {
        return match ($purpose) {
            'educational' => 'education',
            'promotional' => 'conversion',
            'engagement' => 'engagement',
            'behind_scenes' => 'awareness',
            'testimonial' => 'conversion',
            'pain_point' => 'engagement',
            default => 'engagement',
        };
    }

    private function calculatePriority(array $topic): int
    {
        if ($topic['total_score'] >= 70) {
            return 3;
        }
        if ($topic['total_score'] >= 40) {
            return 2;
        }

        return 1;
    }

    private function getScoringWeights(): array
    {
        return [
            'niche' => 35,
            'pain_point' => 30,
            'performance' => 20,
            'trend' => 15,
        ];
    }
}
