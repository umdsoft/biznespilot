<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\ContentPlanGeneration;
use App\Models\NicheTopicScore;
use App\Models\PainPointContentMap;
use App\Models\WeeklyPlan;
use App\Services\ContentAI\ContentPerformanceFeedback;
use App\Services\ContentAI\ContentPlanEngine;
use App\Services\ContentAI\CrossBusinessLearningService;
use App\Services\ContentAI\InstagramAlgorithmEngine;
use App\Services\ContentAI\SurveyContentBridge;
use App\Services\KPI\BusinessCategoryMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContentPlanController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected ContentPlanEngine $planEngine,
        protected CrossBusinessLearningService $crossBusiness,
        protected SurveyContentBridge $surveyBridge,
        protected InstagramAlgorithmEngine $igEngine,
        protected ContentPerformanceFeedback $feedback,
    ) {}

    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix() ?? '';

        return str_starts_with($prefix, 'business') ? 'business' : 'marketing';
    }

    /**
     * Smart Content Plan dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return redirect()->route('dashboard');
        }

        $business->load('industryRelation');

        // Oxirgi generatsiyalar
        $recentPlans = ContentPlanGeneration::where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Soha bo'yicha top mavzular
        $nicheTopics = $business->industry_id
            ? $this->crossBusiness->getTopTopicsForIndustry($business->industry_id, 10)
            : [];

        // Rising topics
        $risingTopics = $business->industry_id
            ? $this->crossBusiness->getRisingTopics($business->industry_id, 5)
            : [];

        // Og'riq nuqtalari bo'yicha tavsiyalar
        $painPointRecommendations = $this->surveyBridge->getContentRecommendationsFromPainPoints($business->id, 5);

        // Performance xulosa
        $performanceSummary = $this->feedback->getPerformanceSummary($business->id);

        // IG optimal schedule
        $igSchedule = $this->igEngine->getWeeklyScheduleTemplate($business->id);

        // Tayyor tavsiyalar — data bo'lmasa ham doim chiqaradi
        $recommendations = $this->buildRecommendations(
            $business, $nicheTopics, $painPointRecommendations, $igSchedule, $performanceSummary
        );

        return Inertia::render('Marketing/ContentAI/SmartPlan', [
            'recommendations' => $recommendations,
            'igSchedule' => $igSchedule,
            'performanceSummary' => $performanceSummary,
            'recentPlans' => $recentPlans,
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $this->resolveIndustryName($business),
            ],
            'panelType' => $this->getPanelType($request),
        ]);
    }

    /**
     * Haftalik smart plan yaratish
     */
    public function generateWeeklyPlan(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'start_date' => 'nullable|date|after_or_equal:today',
            'weekly_plan_id' => 'nullable|uuid|exists:weekly_plans,id',
        ]);

        try {
            $weeklyPlan = ! empty($validated['weekly_plan_id'])
                ? WeeklyPlan::find($validated['weekly_plan_id'])
                : null;

            $result = $this->planEngine->generateWeeklyPlan(
                $business->id,
                auth()->id(),
                $validated['start_date'] ?? null,
                $weeklyPlan
            );

            return back()->with([
                'success' => "Smart kontent reja yaratildi! {$result['items']->count()} ta kontent element.",
                'plan_generation' => $result['plan_generation'],
                'items_count' => $result['items']->count(),
                'algorithm_breakdown' => $result['algorithm_breakdown'],
            ]);
        } catch (\Throwable $e) {
            Log::error('ContentPlanController: generateWeeklyPlan failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Plan yaratishda xatolik yuz berdi: '.$e->getMessage());
        }
    }

    /**
     * Soha bo'yicha top mavzularni olish (JSON)
     */
    public function getNicheTopics(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business || ! $business->industry_id) {
            return response()->json(['topics' => [], 'message' => 'Soha aniqlanmagan']);
        }

        $contentType = $request->get('content_type');
        $limit = min((int) $request->get('limit', 15), 50);

        $topics = $this->crossBusiness->getTopTopicsForIndustry($business->industry_id, $limit, $contentType);

        return response()->json([
            'topics' => $topics,
            'industry' => $business->industryRelation?->name,
        ]);
    }

    /**
     * So'rovnoma og'riqlari bo'yicha tavsiyalar (JSON)
     */
    public function getPainPointRecommendations()
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return response()->json(['recommendations' => []]);
        }

        $recommendations = $this->surveyBridge->getContentRecommendationsFromPainPoints($business->id, 10);

        return response()->json(['recommendations' => $recommendations]);
    }

    /**
     * Pain point map larni qayta yaratish (refresh)
     */
    public function refreshPainPointMaps()
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $created = $this->surveyBridge->buildPainPointMaps($business->id);

        return response()->json([
            'success' => true,
            'maps_created' => $created,
            'message' => "{$created} ta og'riq-kontent xaritasi yaratildi",
        ]);
    }

    /**
     * Instagram algoritmiga mos tavsiyalar (JSON)
     */
    public function getIGOptimization(Request $request)
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $contentType = $request->get('content_type', 'post');
        $category = $request->get('category', 'educational');

        return response()->json([
            'schedule' => $this->igEngine->getWeeklyScheduleTemplate($business->id),
            'tips' => $this->igEngine->getContentOptimizationTips($contentType, $category),
        ]);
    }

    /**
     * Performance feedback ni ishga tushirish (published kontentni sinxronlash)
     */
    public function syncPerformance()
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $stats = $this->feedback->processPublishedContent($business->id);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'message' => "Sinxronlash: {$stats['updated']} ta kontent yangilandi",
        ]);
    }

    /**
     * Tayyor tavsiyalar ro'yxatini tuzish.
     * Real data + shablon-based fallback = doim to'liq tavsiya.
     */
    private function buildRecommendations($business, array $nicheTopics, array $painPoints, array $igSchedule, array $performance): array
    {
        $items = [];
        $industry = $this->resolveIndustryName($business);
        $contentMix = $igSchedule['content_mix'] ?? [];
        $bestHours = $igSchedule['best_times']['best_hours'] ?? [];
        $bestTime = ! empty($bestHours) ? str_pad($bestHours[0]['hour'] ?? 19, 2, '0', STR_PAD_LEFT).':00' : '19:00';

        // 1. Niche data dan tavsiyalar
        foreach (array_slice($nicheTopics, 0, 3) as $topic) {
            $items[] = [
                'topic' => $topic['topic'],
                'type' => $topic['content_type'] ?? 'post',
                'purpose' => $topic['category'] ?? 'educational',
                'hook' => null,
                'time' => $bestTime,
                'source' => 'niche',
                'score' => $topic['score'] ?? 0,
                'reason' => "Sohangizda {$topic['total_posts']} ta biznes bu mavzuda yaxshi natija ko'rsatdi",
                'problem' => "Auditoriya bu mavzu haqida yetarli ma'lumot olmaydi",
                'expected_result' => "Sohadagi muvaffaqiyatli kontent asosida yuqori engagement va reach",
                'platforms' => $this->getPlatformsForType($topic['content_type'] ?? 'post'),
            ];
        }

        // 2. Pain point dan tavsiyalar
        foreach (array_slice($painPoints, 0, 3) as $pp) {
            $topic = $pp['topics'][0] ?? $pp['pain_text'];
            $hook = $pp['hooks'][0] ?? null;
            $items[] = [
                'topic' => $topic,
                'type' => $pp['content_types'][0] ?? 'carousel',
                'purpose' => 'engagement',
                'hook' => $hook,
                'time' => $bestTime,
                'source' => 'pain_point',
                'score' => $pp['relevance'] ?? 0,
                'reason' => "Mijozlaringiz \"{$pp['pain_text']}\" muammosini ko'p eslatdi",
                'problem' => $pp['pain_text'],
                'expected_result' => "Mijozlar muammosiga javob beradi — ishonch oshadi va savollarga javob bo'ladi",
                'platforms' => $this->getPlatformsForType($pp['content_types'][0] ?? 'carousel'),
            ];
        }

        // 3. Performance data dan tavsiyalar
        if (! empty($performance['top_themes'])) {
            $topTheme = array_key_first($performance['top_themes']);
            if ($topTheme) {
                $themeData = $performance['top_themes'][$topTheme];
                $items[] = [
                    'topic' => ucfirst($topTheme)." mavzusida yangi kontent — sizda yaxshi ishlaydi",
                    'type' => 'reel',
                    'purpose' => 'educational',
                    'hook' => null,
                    'time' => $bestTime,
                    'source' => 'performance',
                    'score' => 75,
                    'reason' => "Bu tema o'rtacha ".round(($themeData['avg_engagement'] ?? 0) * 100, 1).'% engagement berdi',
                    'problem' => "Auditoriya bu mavzuga qiziqish bildirgan — davom ettirish kerak",
                    'expected_result' => "Oldingi natijalar asosida o'rtacha ".round(($themeData['avg_engagement'] ?? 0) * 100, 1)."% engagement kutiladi",
                    'platforms' => ['instagram', 'telegram'],
                ];
            }
        }

        // 4. Agar hech narsa bo'lmasa — universal shablon tavsiyalar
        if (empty($items)) {
            $items = $this->getTemplateRecommendations($industry, $bestTime, $contentMix);
        }

        // 5. Minimum 7 ta bo'lishi uchun shablonlar bilan to'ldirish
        if (count($items) < 7) {
            $templates = $this->getTemplateRecommendations($industry, $bestTime, $contentMix);
            foreach ($templates as $tpl) {
                if (count($items) >= 7) {
                    break;
                }
                $existing = array_column($items, 'topic');
                if (! in_array($tpl['topic'], $existing, true)) {
                    $items[] = $tpl;
                }
            }
        }

        // Score bo'yicha tartiblash
        usort($items, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Har biriga kun va tartib raqam berish
        $days = ['Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba', 'Yakshanba'];
        foreach ($items as $i => &$item) {
            $item['day'] = $days[$i % 7];
            $item['order'] = $i + 1;
        }

        return $items;
    }

    /**
     * Universal shablon tavsiyalar (data bo'lmaganda ham ishlaydi)
     */
    private function getTemplateRecommendations(string $industry, string $bestTime, array $contentMix): array
    {
        $templates = [
            [
                'topic' => "{$industry} sohasida mijozlar eng ko'p beriladigan 5 ta savol",
                'hook' => "Siz ham shu savolni berasizmi? Javobini o'qing!",
                'type' => 'reel',
                'purpose' => 'educational',
                'reason' => "FAQ kontent doim yuqori engagement oladi",
                'problem' => "Mijozlar tez-tez bir xil savol beradi — vaqt ketadi",
                'expected_result' => "DM dagi savollar kamayadi, auditoriya bilimi oshadi",
                'platforms' => ['instagram', 'telegram', 'youtube'],
                'score' => 85,
            ],
            [
                'topic' => "Mijoz hikoyasi: natijadan oldin va keyin",
                'hook' => "Bu natijaga ishonmaysiz, lekin haqiqat!",
                'type' => 'carousel',
                'purpose' => 'testimonial',
                'reason' => "Before/After kontent eng yuqori save rate beradi",
                'problem' => "Yangi mijozlar xizmat natijasiga ishonmaydi",
                'expected_result' => "Ishonch ortadi, sotuvga o'tish 2-3x oshadi",
                'platforms' => ['instagram', 'telegram'],
                'score' => 82,
            ],
            [
                'topic' => "Bizning jamoamiz qanday ishlaydi — sahna ortida",
                'hook' => "Hech kim ko'rmagan jarayonni ko'rsatamiz",
                'type' => 'post',
                'purpose' => 'behind_scenes',
                'reason' => "Behind-the-scenes kontent ishonch oshiradi",
                'problem' => "Auditoriya kompaniya ichki jarayonini bilmaydi",
                'expected_result' => "Brend bilan shaxsiy bog'lanish kuchayadi",
                'platforms' => ['instagram', 'telegram'],
                'score' => 78,
            ],
            [
                'topic' => "{$industry} da eng keng tarqalgan 3 ta xato",
                'hook' => "90% odam shu xatoni qiladi. Siz-chi?",
                'type' => 'story',
                'purpose' => 'educational',
                'reason' => "Xatolar haqida kontent yuqori share oladi",
                'problem' => "Mijozlar noto'g'ri qaror qilmoqda — bu biznesga ham zarar",
                'expected_result' => "Ekspert sifatida tanilasiz, share va reach oshadi",
                'platforms' => ['instagram', 'telegram', 'youtube'],
                'score' => 76,
            ],
            [
                'topic' => "Xizmatimiz/mahsulotimiz qanday ishlaydi — oddiy tilda",
                'hook' => "1 daqiqada tushunasiz!",
                'type' => 'reel',
                'purpose' => 'educational',
                'reason' => "Explainer kontent yangi auditoriyani jalb qiladi",
                'problem' => "Xizmatni tushuntirish qiyin — mijozlar tushunmaydi",
                'expected_result' => "Yangi auditoriya jalb bo'ladi, savol kamayadi",
                'platforms' => ['instagram', 'youtube'],
                'score' => 73,
            ],
            [
                'topic' => "Haftalik maslahat: bugun sinab ko'ring",
                'hook' => "Bugun sinab ko'rsangiz, ertaga natija ko'rasiz",
                'type' => 'post',
                'purpose' => 'engagement',
                'reason' => "Amaliy maslahat kontent save va share oladi",
                'problem' => "Auditoriya passiv — faqat ko'radi, harakat qilmaydi",
                'expected_result' => "Save va share oshadi, auditoriya faollashadi",
                'platforms' => ['instagram', 'telegram'],
                'score' => 70,
            ],
            [
                'topic' => "Chegirma/aksiya — faqat shu hafta",
                'hook' => "Bu imkoniyatni qo'ldan boy bermang!",
                'type' => 'carousel',
                'purpose' => 'promotional',
                'reason' => "Haftasiga 1 marta promotional kontent muvozanatni saqlaydi",
                'problem' => "Sotuvlar past — auditoriya sotib olishga undalmaydi",
                'expected_result' => "To'g'ridan-to'g'ri sotuv, DM va saytga o'tish",
                'platforms' => ['instagram', 'telegram'],
                'score' => 65,
            ],
        ];

        foreach ($templates as &$tpl) {
            $tpl['time'] = $bestTime;
            $tpl['source'] = 'algorithm';
        }

        return $templates;
    }

    /**
     * Category/Industry dan chiroyli nom olish
     */
    private function resolveIndustryName($business): string
    {
        // 1. Industry jadvalidan
        if ($business->industryRelation?->name_uz) {
            return $business->industryRelation->name_uz;
        }

        // 2. Category slug dan BusinessCategoryMapper orqali
        if ($business->category) {
            $code = BusinessCategoryMapper::getIndustryCode($business->category);

            return BusinessCategoryMapper::getIndustryName($code);
        }

        return 'Biznes';
    }

    /**
     * Kontent turiga qarab platformalar ro'yxati
     */
    private function getPlatformsForType(string $contentType): array
    {
        return match ($contentType) {
            'reel' => ['instagram', 'youtube', 'tiktok'],
            'story' => ['instagram', 'telegram'],
            'carousel' => ['instagram', 'telegram'],
            'post' => ['instagram', 'telegram', 'facebook'],
            default => ['instagram', 'telegram'],
        };
    }

    /**
     * Plan generatsiya tarixini ko'rish
     */
    public function history()
    {
        $business = $this->getCurrentBusiness();
        if (! $business) {
            return response()->json(['plans' => []]);
        }

        $plans = ContentPlanGeneration::where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'plan_type' => $plan->plan_type,
                'period' => $plan->period_start->format('d.m').' - '.$plan->period_end->format('d.m.Y'),
                'items_generated' => $plan->items_generated,
                'status' => $plan->status,
                'performance_score' => $plan->performance_score,
                'algorithm_breakdown' => $plan->algorithm_breakdown,
                'created_at' => $plan->created_at->diffForHumans(),
            ]);

        return response()->json(['plans' => $plans]);
    }
}
