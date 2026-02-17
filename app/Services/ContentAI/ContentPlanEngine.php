<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\ContentPost;
use App\Models\ContentPlanGeneration;
use App\Models\WeeklyPlan;
use App\Services\KPI\BusinessCategoryMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
    private int $freshnessExcludedCount = 0;

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
            $successPatterns = $sources['success_patterns'] ?? [];
            $weeklyItems = $this->distributeTopicsToSlots(
                $scoredTopics,
                $igSchedule,
                $business,
                $industryCode,
                $start,
                $end,
                $weeklyPlan,
                $successPatterns
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

        // Muvaffaqiyat patternlari (qaysi format+purpose eng yaxshi ishlagan)
        $successPatterns = $this->analyzeSuccessPatterns($businessId);

        return [
            // Qatlam 1 — algoritmik (har doim bor)
            'library_topics' => $libraryTopics,
            'ig_schedule' => $igSchedule,
            // Qatlam 2 — data-driven (bo'lmasligi mumkin)
            'niche_topics' => $nicheTopics,
            'pain_topics' => $painTopics,
            'past_performance' => $pastPerformance,
            'success_patterns' => $successPatterns,
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

        // === 3) Mavsumiy mavzular ===
        $seasonalTopics = app(SeasonalContentService::class)->getRelevantTopics($industryCode, now(), 3);
        foreach ($seasonalTopics as $st) {
            $key = mb_strtolower($st['topic']);
            // Mavsumiy mavzularni yuqori ball bilan qo'shish (80)
            $dataTopics[$key] = [
                'topic' => $st['topic'],
                'category' => 'promotional',
                'content_type' => 'post',
                'source' => 'seasonal',
                'source_label' => 'Mavsumiy kontent',
                'seasonal_event' => $st['seasonal_event'],
                'niche_score' => 0,
                'pain_score' => 0,
                'performance_score' => 0,
                'trend_bonus' => 10,
                'total_score' => $st['total_score'] ?? 80,
                'hooks' => $st['hooks'] ?? [],
            ];
        }

        // === 4) Birlashtirish va dublikatlarni olib tashlash ===
        $merged = $this->mergeTopicSources($dataTopics, $libraryTopics);

        // === 5) Yaqinda ishlatilganlarni chetga surish (freshness) ===
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

        // Past performance bonus (×500 — engagement 0.04 = 20 ball)
        if (! empty($pastPerformance['top_themes'])) {
            $avgOverallEngagement = $pastPerformance['avg_engagement'] ?? 0;

            foreach ($pastPerformance['top_themes'] as $theme => $data) {
                foreach ($allTopics as $key => &$topic) {
                    if (str_contains($key, mb_strtolower($theme))) {
                        $engagementScore = min(($data['avg_engagement'] ?? 0) * 500, $weights['performance']);

                        // Format match bonus: agar eng yaxshi format bilan mos kelsa → +5
                        $bestFormat = $data['best_format'] ?? null;
                        if ($bestFormat && ($topic['content_type'] ?? '') === $bestFormat) {
                            $engagementScore = min($engagementScore + 5, $weights['performance'] + 5);
                        }

                        $topic['performance_score'] = $engagementScore;
                    }
                }
                unset($topic);
            }

            // Minimum baseline: agar umumiy engagement > 5% → hamma topicga kamida 10 ball
            if ($avgOverallEngagement > 0.05) {
                foreach ($allTopics as &$topic) {
                    if ($topic['performance_score'] < 10) {
                        $topic['performance_score'] = 10;
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
     * Yaqinda ishlatilgan mavzularni AQLLI filtrlash (3-darajali)
     *
     * 95%+ o'xshashlik → EXCLUDE (hard remove)
     * 70-95% o'xshashlik → -50 ball penalty
     * 50-70% o'xshashlik → -30 ball penalty
     */
    private function applyFreshnessFilter(array $topics, string $businessId): array
    {
        // 1) Oxirgi 30 kundagi joylangan mavzular
        $recentTitles = ContentPost::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('title')
            ->map(fn ($t) => mb_strtolower(trim($t)))
            ->filter()
            ->unique()
            ->toArray();

        // 2) Oldingi rejalardagi mavzular (content_plan_generations)
        $previousPlanTopics = ContentPlanGeneration::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('niche_scores_used')
            ->pluck('niche_scores_used')
            ->flatten()
            ->map(fn ($item) => mb_strtolower(is_array($item) ? ($item['topic'] ?? '') : (string) $item))
            ->filter()
            ->unique()
            ->toArray();

        $allRecent = array_unique(array_merge($recentTitles, $previousPlanTopics));

        if (empty($allRecent)) {
            return $topics;
        }

        $excludedCount = 0;

        foreach ($topics as $key => &$topic) {
            $topicLower = mb_strtolower($topic['topic']);
            $maxSimilarity = 0;

            foreach ($allRecent as $recentTitle) {
                $similarity = $this->calculateStringSimilarity($topicLower, $recentTitle);
                $maxSimilarity = max($maxSimilarity, $similarity);

                if ($maxSimilarity >= 0.95) {
                    break; // Allaqachon exclude — boshqa tekshirish shart emas
                }
            }

            if ($maxSimilarity >= 0.95) {
                // HARD EXCLUDE — aynan shu mavzu oldin qilingan
                unset($topics[$key]);
                $excludedCount++;
            } elseif ($maxSimilarity >= 0.70) {
                // Juda o'xshash — kuchli penalty
                $topic['total_score'] = max($topic['total_score'] - 50, 5);
                $topic['freshness_penalty'] = 50;
                $topic['similarity'] = round($maxSimilarity * 100);
            } elseif ($maxSimilarity >= 0.50) {
                // Biroz o'xshash — yengil penalty
                $topic['total_score'] = max($topic['total_score'] - 30, 5);
                $topic['freshness_penalty'] = 30;
                $topic['similarity'] = round($maxSimilarity * 100);
            }
        }
        unset($topic);

        // Tracking uchun — nechta exclude qilinganini saqlash
        $this->freshnessExcludedCount = $excludedCount;

        return array_values($topics);
    }

    /**
     * Ikki matn orasidagi o'xshashlikni hisoblash (0.0 — 1.0)
     * Levenshtein distance + token overlap kombinatsiyasi
     */
    private function calculateStringSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        // 1) Normalized Levenshtein (qisqa matnlar uchun yaxshi)
        $maxLen = max(mb_strlen($a), mb_strlen($b));
        $levenshtein = levenshtein(
            mb_substr($a, 0, 255),
            mb_substr($b, 0, 255)
        );
        $levenshteinSim = 1 - ($levenshtein / $maxLen);

        // 2) Token overlap (uzun matnlar uchun yaxshi)
        $tokensA = array_filter(explode(' ', $a));
        $tokensB = array_filter(explode(' ', $b));

        if (empty($tokensA) || empty($tokensB)) {
            return max(0, $levenshteinSim);
        }

        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));
        $jaccardSim = $union > 0 ? $intersection / $union : 0;

        // 3) Substring check — bir matn ikkinchisining ichida bo'lsa
        $substringBonus = 0;
        if (str_contains($a, $b) || str_contains($b, $a)) {
            $substringBonus = 0.3;
        }

        // Oxirgi natija: 50% levenshtein + 30% jaccard + 20% substring
        return min(1.0, ($levenshteinSim * 0.5) + ($jaccardSim * 0.3) + $substringBonus);
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
        ?WeeklyPlan $weeklyPlan,
        array $successPatterns = []
    ): array {
        // Success pattern multiplier qo'llash
        if (! empty($successPatterns)) {
            foreach ($scoredTopics as &$topic) {
                $patternKey = ($topic['content_type'] ?? 'post') . '+' . ($topic['category'] ?? 'educational');
                if (isset($successPatterns[$patternKey])) {
                    $topic['total_score'] = round($topic['total_score'] * $successPatterns[$patternKey], 2);
                    $topic['success_multiplier'] = $successPatterns[$patternKey];
                }
            }
            unset($topic);

            // Qayta saralash
            usort($scoredTopics, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);
        }

        $items = [];
        $aiCallCount = 0;
        $maxAiCalls = 4;
        $schedule = $igSchedule['schedule'] ?? [];
        $topicIndex = 0;
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $defaultTypes = ['reel', 'carousel', 'post', 'story', 'reel', 'post', 'carousel'];
        $usedCategories = [];
        $usedFormats = []; // Format xilma-xilligini kuzatish

        // Platform taqsimlash: 60% Instagram, 40% Telegram (aralash)
        $platformPool = ['instagram', 'instagram', 'instagram', 'telegram', 'telegram', 'instagram', 'telegram'];
        $platformIndex = 0;

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
                $usedFormats[] = $contentType;

                // Format xilma-xilligi: agar 5+ slot to'lgan va hali 3 xil format yo'q — formatni almashtirish
                if (count($usedFormats) >= 5 && count(array_unique($usedFormats)) < 3) {
                    $missingFormats = array_diff(['reel', 'carousel', 'post', 'story'], array_unique($usedFormats));
                    if (! empty($missingFormats)) {
                        $contentType = reset($missingFormats);
                    }
                }

                // IG algorithm tips
                $igTips = $this->igEngine->getContentOptimizationTips($contentType, $purpose);

                // GIBRID description yaratish
                $description = $this->buildHybridDescription($topic, $igTips, $industryCode);

                // Platform aniqlash
                $platform = $topic['source'] === 'seasonal'
                    ? 'instagram'  // Mavsumiy kontent Instagram da yaxshiroq ishlaydi
                    : ($platformPool[$platformIndex % count($platformPool)] ?? 'instagram');
                $platformIndex++;

                // Telegram da content type moslash
                if ($platform === 'telegram') {
                    $contentType = match ($contentType) {
                        'reel', 'story' => 'post',   // Telegram da reel/story yo'q
                        'carousel' => 'thread',        // Carousel → thread seriya
                        default => $contentType,
                    };
                }

                // 3-QATLAM: AI boyitish (ixtiyoriy, cheklangan)
                $aiContent = null;
                if ($aiCallCount < $maxAiCalls) {
                    $aiContent = $this->aiEnrichment->enrichContentItem(
                        $topic, $contentType, $purpose, $igTips, $business, $platform
                    );
                    $aiCallCount++;
                }

                // AI muvaffaqiyatli bo'lsa — captionni almashtirish
                if ($aiContent && ! empty($aiContent['caption'])) {
                    $description = $aiContent['caption'];
                }

                // GIBRID hashtags (Telegram uchun bo'sh)
                $hashtags = $platform === 'telegram' ? [] : $this->buildHybridHashtags($topic, $industryCode);

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
                    'platform' => ucfirst($platform),
                    'scheduled_at' => $date->toDateString().' '.$time.':00',
                    'hashtags' => $hashtags,
                    'seasonal_event' => $topic['seasonal_event'] ?? null,
                    'ai_suggestions' => $aiSuggestions,
                ];

                $topicIndex++;
            }
        }

        return $items;
    }

    /**
     * Keyingi mavzuni tanlash (kuchaytirilgan diversifikatsiya)
     *
     * Qoidalar:
     * - Bir xil purpose hafta ichida MAX 2 marta
     * - Kamida 3 xil format ishlatilsin
     * - Oxirgi 2 ta bilan bir xil category bo'lmasin
     */
    private function pickNextTopic(array $scoredTopics, int &$index, array $usedCategories): ?array
    {
        $totalTopics = count($scoredTopics);
        if ($totalTopics === 0) {
            return null;
        }

        // Purpose counter — har bir purpose necha marta ishlatilgan
        $purposeCount = array_count_values($usedCategories);
        $recentCategories = array_slice($usedCategories, -2);
        $maxPerPurpose = 2;

        $attempts = 0;
        $maxAttempts = $totalTopics;
        $bestFallback = null;

        while ($attempts < $maxAttempts) {
            $currentIndex = ($index + $attempts) % $totalTopics;
            $topic = $scoredTopics[$currentIndex];
            $category = $topic['category'] ?? 'educational';

            $purposeOk = ($purposeCount[$category] ?? 0) < $maxPerPurpose;
            $recentOk = ! in_array($category, $recentCategories);

            if ($purposeOk && $recentOk) {
                $index = $currentIndex + 1;
                return $topic;
            }

            // Faqat purpose cheklovi o'tsa, fallback sifatida saqlash
            if ($purposeOk && $bestFallback === null) {
                $bestFallback = ['topic' => $topic, 'index' => $currentIndex];
            }

            $attempts++;
        }

        // Fallback 1: purpose ok, lekin recent bilan bir xil
        if ($bestFallback !== null) {
            $index = $bestFallback['index'] + 1;
            return $bestFallback['topic'];
        }

        // Fallback 2: alternativ mavzu topishga harakat
        $alternative = $this->findAlternativeTopic($scoredTopics, $purposeCount, $maxPerPurpose);
        if ($alternative !== null) {
            $index++;
            return $alternative;
        }

        // Oxirgi chora: keyingi topicni olish
        $currentIndex = $index % $totalTopics;
        $index++;
        return $scoredTopics[$currentIndex];
    }

    /**
     * Alternativ mavzu topish — purpose limiti to'lganda,
     * eng kam ishlatilgan purpose dan mavzu tanlash
     */
    private function findAlternativeTopic(array $scoredTopics, array $purposeCount, int $maxPerPurpose): ?array
    {
        // Eng kam ishlatilgan purpose larni topish
        $allPurposes = ['educational', 'promotional', 'engagement', 'behind_scenes', 'testimonial'];
        $availablePurposes = [];

        foreach ($allPurposes as $purpose) {
            if (($purposeCount[$purpose] ?? 0) < $maxPerPurpose) {
                $availablePurposes[] = $purpose;
            }
        }

        if (empty($availablePurposes)) {
            return null;
        }

        // Mavjud purpose lardagi eng yuqori balli topicni topish
        foreach ($scoredTopics as $topic) {
            if (in_array($topic['category'] ?? '', $availablePurposes)) {
                return $topic;
            }
        }

        return null;
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
            'seasonal' => 0,
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
            // Yangi: Smart Freshness + Success Patterns + Diversity
            'topics_excluded' => $this->freshnessExcludedCount,
            'success_patterns' => $sources['success_patterns'] ?? [],
            'diversity_rules' => ['max_per_purpose' => 2, 'min_formats' => 3],
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

    /**
     * Muvaffaqiyat patternlarini tahlil qilish
     *
     * Oxirgi 60 kundagi published postlarni tahlil qilib,
     * qaysi format+purpose kombinatsiya eng yaxshi natija berganini aniqlaydi.
     *
     * @return array<string, float> ['carousel+educational' => 1.8, 'reel+promotional' => 1.5, ...]
     */
    private function analyzeSuccessPatterns(string $businessId): array
    {
        return Cache::remember(
            "success_patterns:{$businessId}",
            21600, // 6 soat
            function () use ($businessId) {
                $posts = ContentPost::withoutGlobalScope('business')
                    ->where('business_id', $businessId)
                    ->where('status', 'published')
                    ->where('created_at', '>=', now()->subDays(60))
                    ->whereNotNull('metrics')
                    ->select(['format', 'content_type', 'metrics'])
                    ->get();

                if ($posts->count() < 10) {
                    return [];
                }

                // Har bir post uchun engagement rate hisoblash
                $postRates = $posts->map(function ($post) {
                    $metrics = is_array($post->metrics) ? $post->metrics : [];
                    $likes = $metrics['likes'] ?? 0;
                    $comments = $metrics['comments'] ?? 0;
                    $shares = $metrics['shares'] ?? 0;
                    $views = $metrics['views'] ?? $metrics['reach'] ?? 1;

                    return [
                        'key' => ($post->format ?? 'post') . '+' . ($post->content_type ?? 'educational'),
                        'engagement' => $views > 0 ? ($likes + $comments + $shares) / $views : 0,
                    ];
                });

                // Top 20% threshold
                $sortedRates = $postRates->pluck('engagement')->sort()->values();
                $threshold = $sortedRates->get((int) floor($sortedRates->count() * 0.8), 0);

                if ($threshold <= 0) {
                    return [];
                }

                // Har bir format+purpose uchun frequency hisoblash
                $totalCounts = $postRates->countBy('key');
                $topCounts = $postRates->filter(fn ($p) => $p['engagement'] >= $threshold)->countBy('key');

                // Multiplier: top20%_frequency / overall_frequency
                $patterns = [];
                foreach ($topCounts as $key => $topCount) {
                    $total = $totalCounts->get($key, 1);
                    $topRatio = $topCount / max($topCount + ($total - $topCount), 1);
                    $overallRatio = $total / $posts->count();
                    $multiplier = $overallRatio > 0 ? $topRatio / $overallRatio : 1.0;

                    if ($multiplier > 1.1) {
                        $patterns[$key] = round(min($multiplier, 3.0), 2); // Max 3x
                    }
                }

                arsort($patterns);

                return array_slice($patterns, 0, 5, true);
            }
        );
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
