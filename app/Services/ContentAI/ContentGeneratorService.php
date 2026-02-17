<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\ContentGeneration;
use App\Models\ContentIdea;
use App\Models\ContentStyleGuide;
use App\Models\ContentTemplate;
use App\Models\Offer;
use App\Models\PainPointContentMap;
use App\Services\KPI\BusinessCategoryMapper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ContentGeneratorService - AI orqali kontent generatsiya qilish
 *
 * Bu service biznes style guide va namuna postlarga asoslanib,
 * Claude Haiku orqali yangi kontent yaratadi.
 */
class ContentGeneratorService
{
    protected string $apiKey;
    protected string $model = 'claude-haiku-4-5-20251001';
    protected string $apiUrl = 'https://api.anthropic.com/v1/messages';

    protected ContentStyleGuideService $styleGuideService;

    public function __construct(ContentStyleGuideService $styleGuideService)
    {
        $this->apiKey = config('services.anthropic.api_key', '');
        $this->styleGuideService = $styleGuideService;
    }

    /**
     * Yangi kontent generatsiya qilish
     */
    public function generate(
        string $businessId,
        string $userId,
        string $topic,
        string $contentType = 'post',
        string $purpose = 'engage',
        ?string $targetChannel = null,
        ?string $additionalPrompt = null,
        ?string $offerId = null
    ): ContentGeneration {
        // Input sanitization (prompt injection himoyasi)
        $topic = $this->sanitizeUserInput($topic, 300);
        $additionalPrompt = $additionalPrompt ? $this->sanitizeUserInput($additionalPrompt, 500) : null;

        // Generation record yaratish
        $generation = ContentGeneration::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'offer_id' => $offerId,
            'topic' => $topic,
            'prompt' => $additionalPrompt,
            'content_type' => $contentType,
            'purpose' => $purpose,
            'target_channel' => $targetChannel,
            'status' => 'generating',
            'ai_model' => $this->model,
        ]);

        try {
            // Style guide olish
            $styleGuide = ContentStyleGuide::getOrCreate($businessId);

            // Offer yuklash
            $offer = $offerId ? Offer::with('components')->find($offerId) : null;

            // Namuna postlarni topish
            $referenceTemplates = $this->findReferenceTemplates($businessId, $purpose, $targetChannel);

            // System prompt (cacheable — marketing framework va qoidalar)
            $systemPrompt = $this->buildSystemPrompt($purpose, $contentType, $styleGuide);

            // User prompt (dinamik — mavzu, namunalar, offer)
            $prompt = $this->buildGenerationPrompt(
                $styleGuide,
                $topic,
                $contentType,
                $purpose,
                $targetChannel,
                $referenceTemplates,
                $additionalPrompt,
                $offer
            );

            // AI dan javob olish
            $response = $this->callClaudeApi($prompt, $styleGuide->creativity_level, 1500, $systemPrompt);

            // Javobni parse qilish
            $result = $this->parseGenerationResponse($response);

            // Generation ni yangilash
            $generation->update([
                'status' => 'completed',
                'generated_content' => $result['content'],
                'generated_hashtags' => $result['hashtags'] ?? [],
                'generated_variations' => $result['variations'] ?? [],
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
                'reference_template_ids' => $referenceTemplates->pluck('id')->toArray(),
            ]);

            $generation->updateCost();

            Log::info('ContentGeneratorService: Content generated successfully', [
                'generation_id' => $generation->id,
                'business_id' => $businessId,
                'tokens' => $generation->total_tokens,
                'cost' => $generation->cost_usd,
            ]);

            return $generation;

        } catch (\RuntimeException $e) {
            // User-friendly xabar (callClaudeApi dan)
            Log::warning('ContentAI: Generation failed (user-facing)', [
                'generation_id' => $generation->id,
                'error' => $e->getMessage(),
            ]);
            $generation->markFailed($e->getMessage());
            return $generation;

        } catch (\Exception $e) {
            Log::error('ContentAI: Generation failed (unexpected)', [
                'generation_id' => $generation->id,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);
            $generation->markFailed('Kontent yaratishda xatolik yuz berdi. Qayta urinib ko\'ring.');
            return $generation;
        }
    }

    /**
     * A/B test variantlarini generatsiya qilish
     */
    public function generateVariations(
        string $businessId,
        string $userId,
        string $topic,
        int $variationsCount = 3,
        ?string $targetChannel = null
    ): ContentGeneration {
        $generation = ContentGeneration::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'topic' => $topic,
            'content_type' => 'post',
            'purpose' => 'engage',
            'target_channel' => $targetChannel,
            'status' => 'generating',
            'ai_model' => $this->model,
        ]);

        try {
            $styleGuide = ContentStyleGuide::getOrCreate($businessId);

            $prompt = $this->buildVariationsPrompt($styleGuide, $topic, $variationsCount, $targetChannel);

            $response = $this->callClaudeApi($prompt, 0.9, 2000); // Higher temperature for variety

            $result = $this->parseVariationsResponse($response);

            $generation->update([
                'status' => 'completed',
                'generated_content' => $result['variations'][0]['content'] ?? '',
                'generated_variations' => $result['variations'],
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
            ]);

            $generation->updateCost();

            return $generation;

        } catch (\Exception $e) {
            $generation->markFailed($e->getMessage());
            return $generation;
        }
    }

    /**
     * Mavjud kontentni qayta yozish
     */
    public function rewrite(
        string $businessId,
        string $userId,
        string $originalContent,
        string $style = 'improve',
        ?string $targetChannel = null
    ): ContentGeneration {
        $generation = ContentGeneration::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'topic' => 'Rewrite: ' . mb_substr($originalContent, 0, 50),
            'prompt' => $originalContent,
            'content_type' => 'post',
            'purpose' => 'engage',
            'target_channel' => $targetChannel,
            'status' => 'generating',
            'ai_model' => $this->model,
        ]);

        try {
            $styleGuide = ContentStyleGuide::getOrCreate($businessId);

            $prompt = $this->buildRewritePrompt($styleGuide, $originalContent, $style, $targetChannel);

            $response = $this->callClaudeApi($prompt, $styleGuide->creativity_level, 1000);

            $result = $this->parseGenerationResponse($response);

            $generation->update([
                'status' => 'completed',
                'generated_content' => $result['content'],
                'generated_hashtags' => $result['hashtags'] ?? [],
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
            ]);

            $generation->updateCost();

            return $generation;

        } catch (\Exception $e) {
            $generation->markFailed($e->getMessage());
            return $generation;
        }
    }

    /**
     * 10 ta kontent g'oyasi — avval DB dan, keyin AI dan
     *
     * Algoritm:
     * 1. force=false → DB da 10+ ta mos g'oya bormi? → DB dan random 10 ta (AI chaqirilMAYDI)
     * 2. force=true yoki DB da kam → AI dan 10 ta generatsiya → DB ga saqlash → qaytarish
     * 3. Har safar AI chaqirilganda yangi g'oyalar DB ga yoziladi
     * 4. Vaqt o'tishi bilan AI kamroq chaqiriladi (token tejash)
     */
    public function generateIdeas(
        string $businessId,
        string $userId,
        string $contentType = 'post',
        string $purpose = 'engage',
        ?string $targetChannel = null,
        ?string $offerId = null,
        bool $forceNew = false
    ): array {
        try {
            $business = Business::find($businessId);
            $industry = $business ? BusinessCategoryMapper::detectFromBusiness($business) : 'default';

            // ==========================================
            // 1-QADAM: DB dan mavjud g'oyalarni tekshirish
            // Smart sort: tur+maqsad mos > faqat tur mos > faqat maqsad mos > boshqa
            // ==========================================
            if (!$forceNew) {
                $totalCached = ContentIdea::where('business_id', $businessId)
                    ->where('is_active', true)
                    ->count();

                if ($totalCached >= 10) {
                    // Smart sort: aniq moslik → qisman moslik → boshqalar
                    $cachedIdeas = ContentIdea::where('business_id', $businessId)
                        ->where('is_active', true)
                        ->orderByRaw("
                            CASE
                                WHEN content_type = ? AND purpose = ? THEN 0
                                WHEN content_type = ? THEN 1
                                WHEN purpose = ? THEN 2
                                ELSE 3
                            END ASC, times_used ASC, RAND()
                        ", [$contentType, $purpose, $contentType, $purpose])
                        ->limit(10)
                        ->get()
                        ->map(fn($idea) => [
                            'id' => $idea->id,
                            'topic' => $idea->title,
                            'hook' => $idea->description,
                            'angle' => $idea->category ?? '',
                            'content_type' => $idea->content_type,
                            'purpose' => $idea->purpose,
                            'from_cache' => true,
                            'times_used' => $idea->times_used,
                            'quality_score' => $idea->quality_score,
                        ])
                        ->values()
                        ->toArray();

                    Log::info('ContentGeneratorService: Ideas from DB cache (smart sort)', [
                        'business_id' => $businessId,
                        'total_cached' => $totalCached,
                        'requested' => "{$contentType}+{$purpose}",
                        'returned' => count($cachedIdeas),
                    ]);

                    return [
                        'ideas' => $cachedIdeas,
                        'from_cache' => true,
                        'cached_total' => $totalCached,
                        'tokens' => ['input' => 0, 'output' => 0],
                    ];
                }
            }

            // ==========================================
            // 2-QADAM: AI dan yangi g'oyalar generatsiya
            // ==========================================
            $ideas = $this->callAiForIdeas($business, $industry, $contentType, $purpose, $targetChannel, $offerId);

            // ==========================================
            // 3-QADAM: Yangi g'oyalarni DB ga saqlash
            // ==========================================
            $savedCount = 0;
            foreach ($ideas['parsed'] as $idea) {
                if (empty($idea['topic'])) continue;

                // Dublikat tekshirish (bir xil title bo'lmasin)
                $exists = ContentIdea::where('business_id', $businessId)
                    ->where('title', $idea['topic'])
                    ->exists();

                if (!$exists) {
                    ContentIdea::create([
                        'business_id' => $businessId,
                        'created_by_user_id' => $userId,
                        'title' => $idea['topic'],
                        'description' => $idea['hook'] ?? '',
                        'content_type' => $contentType,
                        'purpose' => $purpose,
                        'category' => $idea['angle'] ?? null,
                        'is_active' => true,
                    ]);
                    $savedCount++;
                }
            }

            Log::info('ContentGeneratorService: Ideas generated & saved', [
                'business_id' => $businessId,
                'generated' => count($ideas['parsed']),
                'saved_new' => $savedCount,
            ]);

            // Frontend uchun format
            $formattedIdeas = collect($ideas['parsed'])->map(function ($idea, $index) {
                return [
                    'id' => $index + 1,
                    'topic' => $idea['topic'] ?? '',
                    'hook' => $idea['hook'] ?? '',
                    'angle' => $idea['angle'] ?? '',
                    'from_cache' => false,
                ];
            })->values()->toArray();

            return [
                'ideas' => array_slice($formattedIdeas, 0, 10),
                'from_cache' => false,
                'cached_total' => ContentIdea::where('business_id', $businessId)
                    ->where('is_active', true)
                    ->count(),
                'tokens' => $ideas['tokens'],
            ];

        } catch (\Exception $e) {
            Log::error('ContentGeneratorService: Ideas generation failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * AI dan g'oyalar so'rash (ichki metod)
     */
    protected function callAiForIdeas(
        Business $business,
        string $industry,
        string $contentType,
        string $purpose,
        ?string $targetChannel,
        ?string $offerId
    ): array {
        // Sohaviy mavzularni olish
        $industryLibrary = new IndustryContentLibrary();
        $industryTopics = $industryLibrary->getTopicsForIndustry($industry, 5);
        $topicContext = '';
        if (!empty($industryTopics)) {
            $topicContext = "Soha mavzulari (referens sifatida):\n";
            foreach ($industryTopics as $t) {
                $topicContext .= "- {$t['topic']}\n";
            }
        }

        // StyleGuide dan brand kontekst
        $styleGuide = ContentStyleGuide::getOrCreate($business->id);
        $brandContext = '';
        if ($styleGuide->content_pillars && count($styleGuide->content_pillars) > 0) {
            $brandContext = "Brand kontent ustunlari: " . implode(', ', array_slice($styleGuide->content_pillars, 0, 5));
        }

        // Offer kontekst
        $offerContext = '';
        if ($offerId) {
            $offer = Offer::find($offerId);
            if ($offer) {
                $offerContext = "Taklif: {$offer->name}";
                if ($offer->core_offer) $offerContext .= " — {$offer->core_offer}";
            }
        }

        $contentTypeLabel = match ($contentType) {
            'post' => 'post',
            'story' => 'story',
            'reel' => 'reel/video',
            'ad' => 'reklama',
            'carousel' => 'karusel',
            'article' => 'maqola',
            'poll' => 'so\'rovnoma/poll',
            'thread' => 'seriya/thread (5 qismli)',
            'ugc_brief' => 'UGC brief (mijozdan kontent so\'rash)',
            'collab' => 'collab post (hamkorlik kontent)',
            default => 'post',
        };

        $purposeLabel = match ($purpose) {
            'engage' => 'jalb qilish (engagement)',
            'educate' => "o'rgatish (ta'lim)",
            'sell' => 'sotish (konvertatsiya)',
            'inspire' => 'ilhomlantirish',
            'announce' => "e'lon qilish",
            'entertain' => "ko'ngil ochish (viral)",
            default => 'jalb qilish',
        };

        $channelLabel = match ($targetChannel) {
            'instagram' => 'Instagram',
            'telegram' => 'Telegram',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok',
            default => 'ijtimoiy tarmoqlar',
        };

        // Biznes profili konteksti
        $businessContext = "Biznes: {$business->name}";
        if ($business->category) $businessContext .= "\nKategoriya: {$business->category}";
        if ($business->description) $businessContext .= "\nTavsif: {$business->description}";
        if ($business->target_audience) $businessContext .= "\nMaqsadli auditoriya: {$business->target_audience}";
        if ($business->business_type) $businessContext .= "\nBiznes turi: {$business->business_type}";
        if ($business->business_model) $businessContext .= "\nBiznes modeli: {$business->business_model}";

        // DB dagi mavjud g'oyalar — takrorlanmasin
        $existingTitles = ContentIdea::where('business_id', $business->id)
            ->where('content_type', $contentType)
            ->where('purpose', $purpose)
            ->where('is_active', true)
            ->pluck('title')
            ->take(20)
            ->implode(', ');

        $excludeContext = '';
        if ($existingTitles) {
            $excludeContext = "\n\nBU MAVZULAR ALLAQACHON BOR, TAKRORLAMAGIN:\n{$existingTitles}";
        }

        $systemPrompt = <<<SYSTEM
Sen O'zbekiston bozorida ishlaydigan professional marketing kontent strategisan.
Vazifang: biznesga tegishli kontent g'oyalari yaratish.
Har bir g'oya:
- topic: mavzu sarlavhasi (10-20 so'z)
- hook: birinchi jumla — o'quvchini to'xtatadigan (1 jumla)
- angle: qanday yondashuvda yoziladi (5-10 so'z)
O'zbekcha yoz. Faqat JSON array qaytaring, boshqa hech narsa yozmang.
SYSTEM;

        $prompt = <<<PROMPT
BIZNES PROFILI:
{$businessContext}

{$topicContext}
{$brandContext}
{$offerContext}

Soha: {$industry}
Kontent turi: {$contentTypeLabel}
Maqsad: {$purposeLabel}
Kanal: {$channelLabel}
{$excludeContext}

Shu biznesga TO'G'RIDAN-TO'G'RI tegishli 10 ta YANGI kontent g'oyasi yoz. G'oyalar biznesning sohasi, mahsulotlari va auditoriyasiga mos bo'lishi SHART.

JSON formatda qaytaring:
[
  {"id": 1, "topic": "...", "hook": "...", "angle": "..."},
  {"id": 2, "topic": "...", "hook": "...", "angle": "..."}
]
PROMPT;

        $totalTokens = ['input' => 0, 'output' => 0];
        $ideas = [];

        // 1-urinish
        $response = $this->callClaudeApi($prompt, 0.9, 1500, $systemPrompt);
        $totalTokens['input'] += $response['usage']['input_tokens'] ?? 0;
        $totalTokens['output'] += $response['usage']['output_tokens'] ?? 0;
        $ideas = $this->parseIdeasJson($response['text']);

        // 2-urinish — agar birinchi safar parse bo'lmasa (1 retry)
        if (empty($ideas)) {
            Log::warning('ContentGeneratorService: Ideas JSON parse failed, retrying', [
                'business_id' => $business->id,
                'raw_text' => mb_substr($response['text'], 0, 300),
            ]);

            $response = $this->callClaudeApi($prompt, 0.7, 1500, $systemPrompt);
            $totalTokens['input'] += $response['usage']['input_tokens'] ?? 0;
            $totalTokens['output'] += $response['usage']['output_tokens'] ?? 0;
            $ideas = $this->parseIdeasJson($response['text']);
        }

        // 3-fallback — IndustryLibrary dan g'oyalar (0 token)
        if (empty($ideas)) {
            Log::warning('ContentGeneratorService: Ideas retry also failed, using IndustryLibrary fallback', [
                'business_id' => $business->id,
            ]);
            $ideas = $this->getIndustryFallbackIdeas($industry, $contentType, $purpose);
        }

        return [
            'parsed' => $ideas,
            'tokens' => $totalTokens,
        ];
    }

    /**
     * Hashtag generatsiya qilish
     */
    public function generateHashtags(string $businessId, string $content, int $count = 10): array
    {
        $styleGuide = ContentStyleGuide::getOrCreate($businessId);

        $existingHashtags = $styleGuide->common_hashtags ?? [];

        $prompt = <<<PROMPT
Quyidagi kontent uchun {$count} ta hashtag tavsiya qil.

KONTENT:
{$content}

MAVJUD BRANDLI HASHTAGLAR (imkon bo'lsa ishlatilsin):
{$this->formatArray($existingHashtags)}

Faqat hashtaglarni ro'yxat qilib ber (har biri # bilan):
PROMPT;

        try {
            $response = $this->callClaudeApi($prompt, 0.5, 300);

            preg_match_all('/#[\w\d_]+/u', $response, $matches);

            return array_slice($matches[0], 0, $count);

        } catch (\Exception $e) {
            return array_slice($existingHashtags, 0, $count);
        }
    }

    /**
     * Reference templatelarni topish
     */
    protected function findReferenceTemplates(
        string $businessId,
        string $purpose,
        ?string $channel
    ): \Illuminate\Support\Collection {
        $query = ContentTemplate::where('business_id', $businessId)
            ->usable()
            ->orderByPerformance();

        // Purpose bo'yicha filter
        if ($purpose) {
            $query->where(function ($q) use ($purpose) {
                $q->where('purpose', $purpose)
                    ->orWhere('is_top_performer', true);
            });
        }

        // Channel bo'yicha filter
        if ($channel) {
            $query->where(function ($q) use ($channel) {
                $q->where('target_channel', $channel)
                    ->orWhereNull('target_channel');
            });
        }

        return $query->limit(5)->get();
    }

    /**
     * System prompt — statik qoidalar (Anthropic cache uchun optimallashtirilgan)
     * Bu qism har safar bir xil bo'lgani uchun API tomonida cache ishlaydi
     */
    protected function buildSystemPrompt(string $purpose, string $contentType, ContentStyleGuide $styleGuide): string
    {
        $marketingFramework = $this->getMarketingFramework($purpose);
        $contentFormula = $this->getContentFormula($purpose, $contentType);

        return <<<SYSTEM
Sen O'zbekiston bozorida 500+ biznesga kontent yaratgan professional SMM mutaxassis va marketologsan. DotCom Secrets, Expert Secrets va Traffic Secrets kitoblari asosida ishlaysan. O'zbek iste'molchisi psixologiyasini chuqur tushunasan.

O'ZBEK BOZORI KONTEKSTI:
- Auditoriya: 18-45 yosh, asosan mobil telefondan foydalanadi
- Ishonch omili: oilaviy qadriyatlar, jamoa fikri, mahalliy obro' muhim
- Qaror qilish: oila/do'stlar maslahati, narx-sifat nisbati, "sinab ko'rgan odam" fikri hal qiladi
- Xarid psixologiyasi: "chegirma" va "cheklangan vaqt" kuchli ishlaydi, "bepul sinov" juda yaxshi
- Til: oddiy, samimiy, do'stona. Informal kontentda "sen" ko'proq ishlaydi
- Madaniy trigger: oilaviy qadriyatlar, mehmonnavarlik, hurmat, halollik, mahalla obro'si

KOPYWRITING TEXNIKALARI:
- Pattern Interrupt: kutilmagan gap/raqam bilan to'xtatish ("347 ta mijoz shu usulda natija oldi")
- Curiosity Gap: ochilmagan ma'lumot hissi ("3-qadam eng muhim — ko'pchilik bilmaydi...")
- Open Loop: hikoyani boshlash, yechimni oxirida berish — oxirigacha o'qishi SHART
- Specificity: "ko'p" emas "347 ta", "yaxshi" emas "3 kun ichida 2x natija"
- Social Proof: "500+ mijoz", "97% qaytib keladi", "3 yillik tajriba"
- Loss Aversion: "boy berasiz" "o'tkazib yuborasiz" — yo'qotish qo'rquvi sotishdan 2x kuchliroq

ENGAGEMENT PSIXOLOGIYASI:
- SAVE qildirish: ro'yxatlar, qadamlar, formulalar — "keyin kerak bo'ladi" hissi
- SHARE qildirish: emotsional yoki "do'stimga ham kerak" degan foydali kontent
- COMMENT yozdirish: "men ham shunday!" tushunilganlik hissi yoki savol berish
- LIKE oldirish: estetik rasm + emotsional birinchi jumla

MARKETING FRAMEWORK:
{$marketingFramework}

KONTENT FORMULASI:
{$contentFormula}

MUHIM QOIDALAR:
1. Brand style guide ga mos ton va uslubda yoz
2. Post uzunligi: {$styleGuide->min_post_length}-{$styleGuide->max_post_length} belgi
3. Emoji ishlatish: {$styleGuide->emoji_frequency}
4. Oxirida aniq CTA bo'lsin ({$styleGuide->cta_style} uslubda)
5. O'zbekcha yoz, oddiy tushunariladigan tilda — texnik term ishlatma
6. HOOK birinchi jumlada — 1.5 soniyada to'xtatish SHART
7. Har bir jumladan keyin bo'sh qator — o'qish oson bo'lsin
8. Specificity: umumiy gaplar emas, ANIQ raqamlar va faktlar ishlat
9. Birinchi shaxs ("men", "biz") va ikkinchi shaxs ("sen") aralash — shaxsiy his qildirish
10. Faqat kontent yoz, boshqa izoh kerak emas
SYSTEM;
    }

    /**
     * Generation prompt yaratish
     */
    protected function buildGenerationPrompt(
        ContentStyleGuide $styleGuide,
        string $topic,
        string $contentType,
        string $purpose,
        ?string $channel,
        $referenceTemplates,
        ?string $additionalPrompt,
        ?Offer $offer = null
    ): string {
        $styleContext = $styleGuide->buildPromptContext();

        $channelGuidelines = $this->getChannelGuidelines($channel);

        $purposeGuidelines = $this->getPurposeGuidelines($purpose);

        $offerContext = $offer ? $this->buildOfferContext($offer) : '';

        $examples = '';
        foreach ($referenceTemplates as $template) {
            $examples .= $template->buildContextForGeneration() . "\n\n";
        }

        // PainPoint — auditoriya og'riq nuqtalari
        $painPointContext = $this->buildPainPointContext($styleGuide->business_id, $purpose);

        $prompt = <<<PROMPT
{$styleContext}

{$offerContext}

{$painPointContext}

MAVZU: {$topic}

MAQSAD: {$purposeGuidelines}

KANAL: {$channelGuidelines}

NAMUNA POSTLAR (shu uslubda yoz):
{$examples}

QOSHIMCHA KO'RSATMALAR:
{$additionalPrompt}

Shu ma'lumotlar asosida {$contentType} formatida kontent yoz. Faqat kontent yoz, boshqa izoh kerak emas.
PROMPT;

        return $prompt;
    }

    /**
     * PainPoint kontekstini AI prompt uchun formatlash
     * Auditoriya og'riq nuqtalari — kontent maqsadiga qarab eng moslarini olish
     */
    protected function buildPainPointContext(string $businessId, string $purpose): string
    {
        // Maqsadga mos kategoriyalarni tanlash
        $categories = match ($purpose) {
            'sell' => ['frustrations', 'fears', 'dreams'],
            'educate' => ['frustrations', 'daily_routine'],
            'inspire' => ['dreams', 'happiness_triggers'],
            'engage' => ['daily_routine', 'happiness_triggers'],
            'entertain' => ['daily_routine', 'happiness_triggers'],
            default => ['frustrations', 'dreams'],
        };

        $painPoints = PainPointContentMap::where('business_id', $businessId)
            ->active()
            ->whereIn('pain_point_category', $categories)
            ->orderByDesc('relevance_score')
            ->limit(5)
            ->get();

        if ($painPoints->isEmpty()) {
            return '';
        }

        $context = "AUDITORIYA OG'RIQ NUQTALARI (bu hissiyotlarga tegadigan kontent yoz):\n";
        foreach ($painPoints as $pp) {
            $categoryLabel = PainPointContentMap::CATEGORIES[$pp->pain_point_category] ?? $pp->pain_point_category;
            $context .= "- [{$categoryLabel}] {$pp->pain_point_text}\n";
            if (!empty($pp->suggested_hooks)) {
                $hook = is_array($pp->suggested_hooks) ? $pp->suggested_hooks[0] : $pp->suggested_hooks;
                $context .= "  Hook maslahat: {$hook}\n";
            }
        }

        return $context;
    }

    /**
     * Offer kontekstini AI prompt uchun formatlash
     */
    protected function buildOfferContext(Offer $offer): string
    {
        $context = "TAKLIF MA'LUMOTLARI (bu taklifni postda targ'ib qiling):\n";
        $context .= "Nomi: {$offer->name}\n";

        if ($offer->core_offer) {
            $context .= "Asosiy taklif: {$offer->core_offer}\n";
        }

        if ($offer->value_proposition) {
            $context .= "Qiymat: {$offer->value_proposition}\n";
        }

        if ($offer->target_audience) {
            $context .= "Maqsadli auditoriya: {$offer->target_audience}\n";
        }

        if ($offer->pricing) {
            $priceText = number_format((float) $offer->pricing, 0, ',', ' ');
            $context .= "Narx: {$priceText} so'm ({$offer->pricing_model})\n";
        }

        if ($offer->total_value && $offer->total_value > ($offer->pricing ?? 0)) {
            $valueText = number_format((float) $offer->total_value, 0, ',', ' ');
            $context .= "Umumiy qiymat: {$valueText} so'm\n";
        }

        if ($offer->guarantee_type) {
            $context .= "Kafolat: {$offer->guarantee_type}";
            if ($offer->guarantee_terms) {
                $context .= " - {$offer->guarantee_terms}";
            }
            if ($offer->guarantee_period_days) {
                $context .= " ({$offer->guarantee_period_days} kun)";
            }
            $context .= "\n";
        }

        if ($offer->components && $offer->components->count() > 0) {
            $context .= "Bonuslar:\n";
            foreach ($offer->components as $component) {
                $context .= "- {$component->name}";
                if ($component->value) {
                    $context .= " (" . number_format((float) $component->value, 0, ',', ' ') . " so'm qiymat)";
                }
                $context .= "\n";
            }
        }

        if ($offer->scarcity) {
            $context .= "Kamlik: {$offer->scarcity}\n";
        }

        if ($offer->urgency) {
            $context .= "Shoshilinchlik: {$offer->urgency}\n";
        }

        $context .= "\nTaklifning asosiy jihatlarini postda aks ettiring. Narx, kafolat, bonuslar va cheklovlarni ta'kidlang.\n";

        return $context;
    }

    /**
     * Variations prompt yaratish
     */
    protected function buildVariationsPrompt(
        ContentStyleGuide $styleGuide,
        string $topic,
        int $count,
        ?string $channel
    ): string {
        $styleContext = $styleGuide->buildPromptContext();
        $channelGuidelines = $this->getChannelGuidelines($channel);

        return <<<PROMPT
Sen DotCom Secrets asosida ishlaydigan professional marketing strategisan.

{$styleContext}

MAVZU: {$topic}
KANAL: {$channelGuidelines}

{$count} ta turli xil post varianti yoz. Har bir variant boshqa marketing formulasi bilan yozilsin:
1. SAVOL HOOK — og'riq nuqtasiga tegadigan savol bilan boshlash ("...dan charchadingizmi?")
2. STATISTIKA HOOK — hayratlanarli raqam bilan boshlash ("90% biznes egalari...")
3. HIKOYA HOOK — shaxsiy/mijoz tajribasi bilan boshlash ("Kecha bir mijozim aytdi...")

Har birida Hook → Story → CTA tuzilmasi bo'lsin. O'zbekcha yoz.

Har bir variant uchun JSON formatda javob ber:
[
  {"hook_type": "question", "content": "..."},
  {"hook_type": "statistic", "content": "..."},
  {"hook_type": "story", "content": "..."}
]

Faqat JSON array qaytaring.
PROMPT;
    }

    /**
     * Rewrite prompt yaratish
     */
    protected function buildRewritePrompt(
        ContentStyleGuide $styleGuide,
        string $originalContent,
        string $style,
        ?string $channel
    ): string {
        $styleContext = $styleGuide->buildPromptContext();

        $styleInstruction = match ($style) {
            'improve' => 'Yaxshiroq va professional qilib qayta yoz',
            'shorter' => 'Qisqaroq va aniqroq qilib qayta yoz',
            'longer' => 'Batafsilroq va kengaytirilgan qilib qayta yoz',
            'engaging' => 'Ko\'proq engagement oladigan qilib qayta yoz',
            'formal' => 'Rasmiyroq va professional qilib qayta yoz',
            'casual' => 'Oddiyroq va do\'stona qilib qayta yoz',
            default => 'Yaxshiroq qilib qayta yoz',
        };

        return <<<PROMPT
{$styleContext}

ORIGINAL KONTENT:
{$originalContent}

VAZIFA: {$styleInstruction}

Faqat qayta yozilgan kontentni ber, boshqa izoh kerak emas.
PROMPT;
    }

    /**
     * Claude API ga so'rov yuborish
     *
     * Structured error handling:
     * - Timeout → foydalanuvchiga "qayta urinib ko'ring"
     * - Rate limit (429) → "biroz kutib turing"
     * - Auth error (401) → admin ga log
     * - Server error (5xx) → "tizim xatosi"
     */
    protected function callClaudeApi(string $prompt, float $temperature = 0.7, int $maxTokens = 1000, ?string $systemPrompt = null): array
    {
        if (empty($this->apiKey)) {
            Log::error('ContentAI: API kalit sozlanmagan', ['key_empty' => true]);
            throw new \RuntimeException('AI xizmat vaqtincha ishlamayapti. Administrator bilan bog\'laning.');
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ];

        // System prompt — Anthropic prompt caching uchun (har safar bir xil system
        // message kelganda cache ishlaydi, ~90% input token tejash)
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('ContentAI: API timeout/connection', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new \RuntimeException('AI server javob bermayapti. Iltimos, qayta urinib ko\'ring.');
        }

        if ($response->successful()) {
            $data = $response->json();
            return [
                'text' => $data['content'][0]['text'] ?? '',
                'usage' => $data['usage'] ?? [],
            ];
        }

        // Structured error handling by status code
        $status = $response->status();
        $body = $response->body();

        match (true) {
            $status === 401 => Log::error('ContentAI: Auth xato — API kalit noto\'g\'ri', [
                'status' => 401,
            ]),
            $status === 429 => Log::warning('ContentAI: Rate limit', [
                'status' => 429,
                'retry_after' => $response->header('retry-after'),
            ]),
            $status === 529 => Log::warning('ContentAI: API overloaded', ['status' => 529]),
            $status >= 500 => Log::error('ContentAI: Server xato', [
                'status' => $status,
                'body' => mb_substr($body, 0, 300),
            ]),
            default => Log::error('ContentAI: Noma\'lum xato', [
                'status' => $status,
                'body' => mb_substr($body, 0, 300),
            ]),
        };

        $userMessage = match (true) {
            $status === 401 => 'AI xizmat sozlamalari noto\'g\'ri. Administrator bilan bog\'laning.',
            $status === 429 => 'AI xizmat band. Iltimos, 30 soniya kutib qayta urinib ko\'ring.',
            $status === 529 => 'AI server haddan tashqari band. Biroz kutib qayta urining.',
            $status >= 500 => 'AI tizimida vaqtinchalik xatolik. Qayta urinib ko\'ring.',
            default => 'AI so\'rov bajarilmadi. Qayta urinib ko\'ring.',
        };

        throw new \RuntimeException($userMessage);
    }

    /**
     * Generation javobini parse qilish
     */
    protected function parseGenerationResponse(array $response): array
    {
        $text = $response['text'];

        // Hashtaglarni ajratish
        preg_match_all('/#[\w\d_]+/u', $text, $hashtagMatches);
        $hashtags = $hashtagMatches[0] ?? [];

        // Hashtaglarni kontentdan olib tashlash (oxiridagi)
        $content = preg_replace('/(\s*#[\w\d_]+)+\s*$/u', '', $text);
        $content = trim($content);

        return [
            'content' => $content,
            'hashtags' => $hashtags,
            'usage' => $response['usage'],
        ];
    }

    /**
     * Variations javobini parse qilish
     */
    protected function parseVariationsResponse(array $response): array
    {
        $text = $response['text'];

        // JSON array topish
        preg_match('/\[[\s\S]*\]/m', $text, $matches);

        $variations = [];

        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                $variations = $decoded;
            }
        }

        // Agar parse qilinmasa - oddiy matn sifatida
        if (empty($variations)) {
            $variations = [
                ['hook_type' => 'general', 'content' => $text]
            ];
        }

        return [
            'variations' => $variations,
            'usage' => $response['usage'],
        ];
    }

    /**
     * DotCom Secrets marketing framework — maqsadga qarab
     */
    protected function getMarketingFramework(string $purpose): string
    {
        return match ($purpose) {
            'sell' => <<<'FW'
STAR/STORY/SOLUTION formulasi (O'zbek bozori uchun moslashtirilgan):
1. HOOK (Pattern Interrupt) — og'riq nuqtasi yoki orzuni aniqlash:
   - Savol: "...dan charchadingizmi?" / "Nega ... ishlamayapti?"
   - Raqam: "347 ta mijoz shu usulda natija oldi"
   - Provokatsiya: "Bu xatoni 90% tadbirkor qiladi"
2. MUAMMO KUCHAYTIRISH — og'riq nuqtasini chuqurlashtirib ko'rsatish:
   - Oqibatlarni tasvirla: vaqt yo'qotish, pul sarflash, imkoniyatdan mahrum bo'lish
   - O'zbek konteksti: raqobatchilar oldinga ketmoqda, bozordagi o'rnini yo'qotish xavfi
3. HIKOYA — Epiphany Bridge (shaxsiy yoki mijoz tajribasi):
   - Orqa fon: "Men/Bizning mijoz ham aynan shunday vaziyatda edi..."
   - Devor: "Hamma usulni sinab ko'rdi — natija YO'Q..."
   - Epifaniya: "Keyin BIR NARSA tushundi..." (merak uyg'ot)
   - Natija: ANIQ raqam — "3 haftada 2x sotuvga chiqdi" (umumiy emas, konkret)
4. YECHIM — mahsulotni FOYDA orqali taqdim et:
   - Xususiyatlarni emas, NATIJAni ko'rsat
   - "Siz ... olasiz" (nima qilasiz emas, nima olasiz)
5. QIYMAT PIRAMIDASI (Value Stack):
   - Asosiy taklif + 2-3 bonus + umumiy qiymat
   - O'zbek uchun: "barchasi ... so'mga" (qiymatni ko'rsatib, narxni past ko'rsat)
6. KAFOLAT — risk inversiyasi:
   - "Natija bo'lmasa — pulni qaytaramiz" / "7 kun sinab ko'ring"
   - O'zbek uchun: "1000+ kishi ishongan" ijtimoiy dalil ham ishlaydi
7. CHEKLOV (urgency + scarcity):
   - Muddat: "Bugunga qadar / Bu hafta oxirigacha"
   - Joy: "Faqat 15 ta joy qoldi / Keyingi guruh 2 oydan keyin"
   - O'zbek uchun: "chegirma" va "cheklangan vaqt" JUDA kuchli ishlaydi
8. CTA — aniq 1 ta harakat: "Hozir DM yozing" / "Havolani bosing" / "+998 ... ga qo'ng'iroq qiling"
FW,
            'educate' => <<<'FW'
TA'LIM KONTENTI formulasi (Ekspert ishonchi qurish):
1. HOOK — diqqatni tortish (birini tanlang):
   - "Ko'pchilik bilmaydi, lekin..." (curiosity gap)
   - Raqam: "10 ta xato — 7-chisi eng xavfli" (specificity)
   - Savol: "Nima uchun ... ishlamayapti?" (muammo aniqlash)
2. MUAMMO — noto'g'ri tushunchani ko'rsatish:
   - "Ko'pchilik ... deb o'ylaydi, LEKIN aslida..."
   - Xato yondashuvning OQIBATLARINI ko'rsat (vaqt/pul yo'qotish)
3. EKSPERT MASLAHATI — 3-5 ta AMALIY qadam:
   - Har bir qadamni raqamla
   - Har bir qadamda ANIQ harakat (umumiy maslahat emas)
   - Misol: "1. Har kuni soat 9:00 da ... qiling" (vaqt, harakat, natija)
4. DALIL — ishonchlilik:
   - Raqamlar: "Bu usulda 200+ kishi natija oldi"
   - Tajriba: "5 yillik tajribamda shu eng yaxshi ishladi"
   - Ilmiy/amaliy asos: "Tadqiqotlar ko'rsatadiki..."
5. CTA — "Saqlang va kerak bo'lganda foydalaning ✅":
   - SAVE triggerini ishlatish: ro'yxat, formulalar, qadamlar = SAVE qilish istagi
   - "Do'stingizga ham yuboring — foydali bo'ladi"
Maqsad: auditoriya sizni EKSPERT deb tan olsin, saqlashni va ulashishni xohlasin
FW,
            'inspire' => <<<'FW'
EPIPHANY BRIDGE formulasi (Transformatsiya hikoyasi):
1. HOOK — emotsional boshlanish:
   - "Bir yil oldin men/u ... edi" (o'quvchi o'zini ko'rsin)
   - "Hamma 'bo'lmaydi' degan paytda..." (qiyinchilik)
   - Raqam: "0 dan 10 millionga: real hikoya"
2. ORQA FON — o'quvchi o'zini taniydigan holat:
   - Aniq detallar: vaqt, joy, holat (hissiyot yarating)
   - O'zbek konteksti: oilaviy bosim, moliyaviy qiyinchilik, "odamlar nima deydi"
3. DEVOR — eng qiyin payt:
   - "Hamma narsa ishlamadi..." / "Umid uzgan payt..."
   - Hissiyotlarni tasvirla: qo'rquv, shubha, charchoq
4. EPIFANIYA — AHA moment:
   - "Keyin BIR NARSA tushundim..." (merak oching)
   - Oddiy, ammo chuqur tushuncha — o'quvchi ham "ha, to'g'ri-ku!" desin
5. NATIJA — transformatsiya:
   - ANIQ raqam yoki holat: "Endi oyiga ... topaman" / "100+ kishi menga murojaat qiladi"
   - Oldin/keyin kontrasti
6. XULOSA — o'quvchiga ko'prik:
   - "Siz ham buni qila olasiz" — ishontiring
   - 1 ta oddiy qadam bering: "Bugundan boshlang: ..."
   - CTA: "Sizning hikoyangiz qanday? Izohda yozing"
Maqsad: o'quvchi "men ham qila olaman!" deb his qilsin
FW,
            'engage' => <<<'FW'
ENGAGEMENT formulasi (Algoritm uchun OLTIN kontent):
MUHIM: 1 comment = 5 like qiymati algoritmda. Komment olish = reach oshishi.
1. HOOK — muhokamaga tortish (birini tanlang):
   - Provokatsion bayonot: "Reklama qilmasdan sotish mumkin. Kim rozi?"
   - Tanlov: "A yoki B — qaysi biri to'g'ri?"
   - Savol: "Siz ham shunday qilasizmi?"
   - Poll: "1 - ... / 2 - ... / 3 - ..." (emoji bilan)
2. KONTEKST — nima uchun bu muhim:
   - O'z tajribangizdan misol keltiring
   - "Men 50+ biznes bilan ishlaganimda shu masalada fikrlar bo'linadi..."
3. O'Z POZITSIYANGIZ — qisqacha fikr bildiring:
   - Biroz provokatsion bo'lsin — "Men A tarafdaman, chunki..."
   - Har ikkala tarafning dalilini ko'rsating — muhokama ochilsin
4. ENGAGEMENT TRIGGER (kamida 2 tasini ishlating):
   - Savol: "Siz qaysi tarafdamiz? Izohda yozing"
   - Tag: "Do'stingizni belgilang — uning fikrini bilmoqchimisiz?"
   - Choice: "1 yoki 2 — emoji bilan javob bering"
   - Challenge: "Kim rozi — ❤️, kim rozi emas — 🔥"
5. CTA — engagement harakati:
   - "Izohda yozing" (specific: "1 SO'Z bilan javob bering")
   - "Saqlang" triggerini ham qo'shing (ro'yxat yoki formula bo'lsa)
Maqsad: kommentlar va sharelarni maksimal ko'paytirish
FW,
            'announce' => <<<'FW'
E'LON formulasi (Yangilik kuchi bilan sotish):
1. HOOK — e'tibor tortish:
   - "🔥 YANGILIK!" / "MUHIM O'ZGARISH!" / "KO'P SO'RAGAN EDINGIZ..."
   - Raqam bilan: "6 oy kutgandan keyin — TAYYOR!"
2. YANGILIK — nima o'zgargan/nima yangi:
   - 1-2 jumlada aniq aytish: nima, kim uchun
   - Vizuallashtirilgan: oldin → keyin / eski → yangi
3. FOYDA — bu o'quvchiga nima beradi:
   - "Endi siz ... qila olasiz"
   - "Bu sizga ... ni tejaydi / ... ni osonlashtiradi"
   - O'zbek uchun: narx-sifat nisbati, vaqt tejash, "qo'shimcha bonus"
4. SHARTLAR — aniq ma'lumot:
   - Qachon: sana, vaqt
   - Qayerda: manzil / link
   - Narx: "... dan boshlab" / "Bepul" / "Maxsus narx: ..."
5. URGENCY — shoshilinchlik yaratish:
   - "Faqat 3 kun" / "Dastlabki 50 kishi uchun"
   - "Early bird narx: ... gacha" / "Keyin narx ko'tariladi"
6. CTA — aniq qadamlar:
   - "DM yozing 'INFO'" / "Bio dagi linkni bosing" / "Hoziroq ro'yxatdan o'ting"
FW,
            'entertain' => <<<'FW'
VIRAL KONTENT formulasi (Share + Save + Reach):
1. HOOK — kutilmagan boshlanish:
   - "Bugun ... bo'ldi va men hayratda qoldim 😂"
   - "Hech kim kutmagan narsa sodir bo'ldi..."
   - Meme format: tanish holat + kutilmagan munosabat
2. HIKOYA — qisqa va emotsional:
   - Maksimum 5-7 jumla
   - Kulgili YOKI hayajonli YOKI nostalgik
   - O'zbek konteksti: tanish holatlar (bazarda savdolashish, mehmonlar, to'y)
3. TWIST — kutilmagan burilish:
   - Oxirgi 1-2 jumla hamma narsani o'zgartirsin
   - "...va shu payt tushundimki, biz ham aynan shunday qilamiz 😅"
4. BRAND BOG'LASH — nozik:
   - Hikoyani mahsulot/xizmatga tabiiy bog'lash
   - "Shuning uchun biz ... yaratdik" (majburiy emas, o'rinli bo'lsa)
5. SHARE TRIGGER:
   - "Do'stingizni tag qiling — u HAM shunday 😂"
   - "Kim o'zini taniydigan bo'lsa — repost qilsin"
   - "Saqlang — keyin yana kulasiz 😄"
Maqsad: viral bo'lish. Share va save = eng kuchli algoritm signallari
FW,
            default => <<<'FW'
HOOK → STORY → OFFER formulasi:
1. HOOK — o'quvchini to'xtatadigan birinchi jumla (savol/raqam/bayonot)
2. STORY — hissiyot uyg'otadigan hikoya yoki holat (aniq detallar bilan)
3. OFFER — aniq foyda va harakatga chaqiruv (1 ta CTA)
FW,
        };
    }

    /**
     * Maqsad va kontent turiga qarab aniq formula
     */
    protected function getContentFormula(string $purpose, string $contentType): string
    {
        // Sotish uchun kontent
        if ($purpose === 'sell') {
            return match ($contentType) {
                'ad' => <<<'F'
TARGET REKLAMA FORMULASI:
Jumlа 1: HOOK — og'riq nuqtasi yoki orzuni aniqlash ("...dan charchadingizmi?")
Jumla 2-3: MUAMMO kuchaytirish — "Ko'pchilik... qiladi, lekin natija bo'lmaydi"
Jumla 4-5: YECHIM — "Biz... yaratdik/topildik/o'rgatamiz"
Jumla 6-7: DALILLAR — raqamlar, mijozlar soni, natijalar ("500+ kishi allaqachon...")
Jumla 8: URGENCY — "Faqat ... gacha / Joy cheklangan / Bugunoq"
Jumla 9: CTA — "Hozir yozing / Havolani bosing"
F,
                'carousel' => <<<'F'
KARUSEL FORMULASI (har bir slayd):
Slayd 1: HOOK — kuchli sarlavha + vizual
Slayd 2: MUAMMO — auditoriya og'riq nuqtasi
Slayd 3-4: YECHIM — taklifingiz qanday hal qiladi
Slayd 5: DALIL — raqamlar yoki natija
Slayd 6: CHEKLOV — urgency/scarcity
Slayd 7: CTA — aniq harakat
Oxirgi post matni: Karusel mazmunini qisqa takrorlash + CTA
F,
                default => <<<'F'
SOTISH POST FORMULASI:
1. HOOK (1 jumla) — muammo/savol/statistika
2. Muammo tasvirlash (2-3 jumla) — "Tanishmisiz bu holat bilan?"
3. Hikoya (3-5 jumla) — mijoz yoki shaxsiy tajriba
4. Yechim (2-3 jumla) — mahsulot/xizmat taqdimoti
5. Dalillar (2 jumla) — raqamlar, natijalar
6. Taklif (2 jumla) — narx, bonus, kafolat
7. Urgency (1 jumla) — muddat yoki cheklov
8. CTA (1 jumla) — aniq harakat
F,
            };
        }

        // Ta'lim kontenti uchun
        if ($purpose === 'educate') {
            return <<<'F'
TA'LIM POST FORMULASI:
1. HOOK — "Ko'pchilik bu xatoni qiladi..." / hayratlanarli fakt
2. Muammo — noto'g'ri yondashuv va uning oqibatlari
3. To'g'ri yo'l — 3-5 ta amaliy qadam (raqamlangan)
4. Dalil — nima uchun bu ishlaydi (tajriba, raqamlar)
5. CTA — "Saqlang va kerak bo'lganda foydalaning"
F;
        }

        // Ilhomlantirish uchun
        if ($purpose === 'inspire') {
            return <<<'F'
ILHOMLANTIRISH FORMULASI (Epiphany Bridge):
1. HOOK — emotsional boshlanish
2. "Men/U ... edi" — oldingi holat tasvirlash
3. "Lekin..." — qiyinchilik, devor
4. "Keyin tushundim..." — epifaniya momenti
5. "Natija..." — transformatsiya
6. "Siz ham..." — o'quvchiga ko'prik, harakat chaqiruvi
F;
        }

        // Engagement uchun
        if ($purpose === 'engage') {
            return match ($contentType) {
                'poll' => <<<'F'
SO'ROVNOMA/POLL FORMULASI:
1. SAVOL — qiziqarli, provokatsion yoki amaliy:
   - "Qaysi biri to'g'ri?" / "Siz qaysi tarafdamiz?"
   - Hech kim befarq qolmaydigan mavzu tanlang
2. VARIANTLAR — 2-4 ta tanlov:
   - Har biri emoji bilan (1️⃣ 2️⃣ 3️⃣ yoki ❤️ 🔥 👍)
   - Telegram: reaction bilan javob berish / Instagram: comment bilan
3. KONTEKST — nima uchun bu savol muhim:
   - 1-2 jumla — o'z tajribangiz yoki qiziqarli fakt
4. ENGAGEMENT HOOK:
   - "Javobingizni izohda yozing va SABABINI tushuntiring"
   - "Kim ko'pchilik bilan rozi bo'lsa — ❤️ bosing"
5. FORWARD/SHARE TRIGGER:
   - "Do'stlaringizga ham yuboring — ularning javobi sizni hayratga soladi"
F,
                'thread' => <<<'F'
SERIYA/THREAD FORMULASI (5 qismli):
QISM 1/5 — HOOK + VA'DA:
- Kuchli sarlavha: "5 ta sir / 7 ta qadam / 3 ta xato..."
- Va'da: "Oxirigacha o'qisangiz — ... ni bilib olasiz"
- "🧵 Seriyani saqlang — kerak bo'ladi"

QISM 2/5 — MUAMMO:
- Muammoni aniq tasvirlang
- O'quvchi "ha, men ham shunday!" desin

QISM 3/5 — ASOSIY QIYMAT:
- Eng muhim ma'lumot/maslahat/qadam
- "Bu eng KO'P xato qilinadigan joy..."

QISM 4/5 — AMALIY QADAM:
- Konkret harakat: "Bugundan boshlang: ..."
- Misol yoki formula bering

QISM 5/5 — XULOSA + CTA:
- Qisqa xulosa
- "Seriyani saqlang va do'stlaringizga yuboring"
- Keyingi seriya haqida ishora: "Ertaga: ..."
F,
                default => <<<'F'
ENGAGEMENT POST FORMULASI:
1. HOOK — provokatsion savol yoki tanlov
2. KONTEKST — nima uchun bu muhim (1-2 jumla)
3. O'Z POZITSIYA — qisqacha o'z fikringiz
4. SAVOL — "Siz nima deysiz? Izohda yozing"
5. TAG — "Do'stingizni belgilang"
F,
            };
        }

        // UGC brief — har qanday maqsad uchun
        if ($contentType === 'ugc_brief') {
            return <<<'F'
UGC BRIEF FORMULASI (mijozdan kontent so'rash):
1. CHAQIRUV — "Bizning mijozlarimiz! Sizga MAXSUS taklif:"
2. TOPSHIRIQ — nima qilish kerak:
   - "Mahsulotimiz bilan rasm/video oling"
   - "Tajribangizni yozing / Hikoyangizni ulashing"
   - Aniq format: story, post, video, rasm
3. SHARTLAR:
   - Hashtag: #BrandName yoki maxsus hashtag
   - Tag: @brandaccount ni belgilash
   - Muddat: "... gacha"
4. MUKOFOT:
   - "Eng yaxshi 3 ta kontent egasiga: ..." (aniq sovg'a)
   - "Har bir qatnashchi ... oladi" (kichik bonus)
5. MISOL — qanday ko'rinishi kerakligini ko'rsating
6. CTA — "Hoziroq boshlang! DM da savollaringizga javob beramiz"
F;
        }

        // Collab post — har qanday maqsad uchun
        if ($contentType === 'collab') {
            return <<<'F'
COLLAB POST FORMULASI (hamkorlik kontent):
1. SARLAVHA — ikkala brand nomlari:
   - "@brand1 × @brand2" formati
   - "Biz birlashdik!" / "Maxsus hamkorlik!"
2. MUAMMO — ikkala auditoriyaga tegishli muammo:
   - Har ikkala brand mijozlari tushunishi kerak
3. YECHIM — hamkorlik natijasi:
   - "Birgalikda ... yaratdik / tayyorladik / taklif qilamiz"
   - Har bir brandning kuchi ta'kidlansin
4. TAKLIF — maxsus collab taklif:
   - Faqat hamkorlik vaqtida amal qiladi
   - Ikkala brand mijozlariga foyda
5. CTA — ikkala accountni follow qilishga undash:
   - "@brand1 va @brand2 ni kuzatib boring"
   - "Collab maxsus: faqat ... gacha"
F;
        }

        // Default formula
        return <<<'F'
UMUMIY KONTENT FORMULASI:
1. HOOK — diqqatni tortadigan birinchi jumla
2. ASOSIY FIKR — qiymat berish (ma'lumot, hikoya, taklif)
3. DALIL — ishonchlilik (raqamlar, tajriba, testimonial)
4. CTA — aniq harakat (saqlash, ulashish, yozish, qo'ng'iroq)
F;
    }

    /**
     * Kanal bo'yicha ko'rsatmalar
     */
    protected function getChannelGuidelines(?string $channel): string
    {
        return match ($channel) {
            'instagram' => <<<'CH'
INSTAGRAM POST QOIDALARI:
- Birinchi jumla = HOOK. Feedda scroll qilayotgan odamni TO'XTATISHI SHART
- Har jumladan keyin bo'sh qator (o'qish oson bo'lsin)
- Emoji STRATEGIK ishlatilsin: har paragraf boshida 1 ta, yoki muhim nuqtalarda. Haddan oshirma
- Uzunlik: 300-2200 belgi (algoritm uzunroq postlarni yoqtiradi — save vaqtini oshiradi)
- Oxirida 5-15 hashtag (aralash: 3 ta katta, 5 ta o'rta, 5 ta niche)
- Carousel: har slaydga KUCHLI sarlavha + asosiy fikr. 1-slayd = HOOK, oxirgi = CTA
- Reel caption: 150 belgigacha, trending sound tavsiyasi, 3-5 hashtag
- Story: savol sticker, poll, countdown, swipe up CTA
CH,
            'telegram' => <<<'CH'
TELEGRAM KANAL POST QOIDALARI:
- Sarlavha: **Bold** formatda, 1-2 qator, kuchli HOOK
- Matn strukturasi: Bold sarlavha → asosiy matn → xulosa/CTA
- **Bold** muhim so'zlar uchun, _italic_ ta'kidlash uchun
- Uzunlik: 300-800 so'z (Telegram o'quvchilari uzun kontentni yoqtiradi)
- Paragraflar orasida bo'sh qator — o'qish qulay bo'lsin
- HASHTAG ISHLATMA! Telegram da hashtag professional ko'rinmaydi va foydasiz
- Emoji: faqat sarlavha va muhim punktlarda (1-3 ta max). Instagram darajasida EMAS
- Ro'yxat formati juda yaxshi ishlaydi: raqamli yoki •/— bilan
- Reaction bait: post oxirida "Foydali bo'lsa — 👍 bosing" / "Kim rozi — 🔥"
- Forward bait: "Do'stlaringizga ham yuboring — foydali bo'ladi"
- Seriya format: "1/5 qism" — o'quvchilarni qaytarib keladi
- Link embed: havolani matn ichiga joylashtiring (alohida qator emas)
- Eng yaxshi vaqt: 9:00-10:00, 13:00-14:00, 20:00-21:00
- Post oxirida "📌 Kanalga obuna bo'ling" yoki kanal linkini qo'shing
CH,
            'facebook' => 'Facebook post uchun: Muhokama ochadigan. Share qilishga arziydigan. 1-3 hashtag. Savol bilan tugatish. Do\'stlarni tag qilishga undash.',
            'tiktok' => 'TikTok caption uchun: Juda qisqa (150 belgi). Trending sound tavsiyasi. Emoji ko\'p. 3-5 trending hashtag. Hook birinchi 2 sekundda.',
            default => 'Ijtimoiy tarmoq posti uchun: Hook → Story → CTA formatida. Har jumladan keyin bo\'sh qator.',
        };
    }

    /**
     * Maqsad bo'yicha ko'rsatmalar
     */
    protected function getPurposeGuidelines(string $purpose): string
    {
        return match ($purpose) {
            'educate' => <<<'PG'
TA'LIM KONTENTI MAQSADI:
1. O'quvchiga AMALIY foyda berish — umumiy maslahat emas, ANIQ qadamlar
2. Noto'g'ri tushunchani buzish: "Ko'pchilik ... deb o'ylaydi, LEKIN..."
3. 3-5 ta raqamlangan qadam (Haiku uchun: raqamli ro'yxat = yaxshiroq natija)
4. Har qadamda KONKRET harakat: "...ni qiling" (umumiy "yaxshilang" emas)
5. Ekspert ishonchi: raqamlar, tajriba, dalillar keltiring
6. SAVE trigger: "Bu ro'yxatni saqlang — kerak bo'ladi"
PG,
            'inspire' => <<<'PG'
ILHOMLANTIRISH MAQSADI (Epiphany Bridge):
1. O'quvchi O'ZINI taniydigan holat tasvirlash (yoshi, holati, muammosi)
2. Qiyinchilikni HISSIY tasvirlash: qo'rquv, shubha, "odamlar nima deydi"
3. AHA moment: oddiy, ammo chuqur tushuncha — "ha, to'g'ri-ku!"
4. Transformatsiya: oldin → keyin ANIQ kontrast (raqamlar bilan)
5. "SIZ HAM qila olasiz" — ishontirish va 1 ta oddiy qadam berish
6. Emotsional ta'sir: o'quvchi "men ham shunday qila olaman!" deb his qilsin
PG,
            'sell' => <<<'PG'
SOTISH MAQSADI (Konvertatsiya):
1. Og'riq nuqtasini ANIQLAB, KUCHAYTIRIB ko'rsating
2. FOYDA ta'kidlang (natijani), xususiyatlarni EMAS
3. Epiphany Bridge hikoya: muammo → yechim → natija (ANIQ raqam)
4. Ijtimoiy dalillar: "500+ mijoz", "97% natija oldi", "3 yillik tajriba"
5. Risk inversiyasi: kafolat, bepul sinov, "natija bo'lmasa..."
6. Urgency + Scarcity: muddat, cheklangan joy, maxsus narx
7. CTA: 1 ta ANIQ harakat ("DM yozing" / "Linkni bosing" / "Qo'ng'iroq qiling")
PG,
            'engage' => <<<'PG'
ENGAGEMENT MAQSADI (Algoritm uchun eng muhim):
1. Komment olish #1 maqsad — 1 comment = 5 like qiymati algoritmda
2. Savol YOKI tanlov bering — "A yoki B?"
3. Provokatsion bayonot: muhokama ochsin, o'quvchi javob berISHNI xohlasin
4. Poll format: "1 — ... / 2 — ... / 3 — ..." (emoji bilan javob berish oson)
5. Tag trigger: "Do'stingizni belgilang — uning fikrini bilmoqchimisiz?"
6. O'z fikringizni ham aytib, muhokamani boshlang
PG,
            'announce' => <<<'PG'
E'LON MAQSADI:
1. HOOK: kuchli birinchi jumla — "MUHIM!" / "KO'P SO'RAGAN EDINGIZ..."
2. Yangilik: NMA o'zgargan — 1-2 jumlada ANIQ
3. FOYDA: bu o'quvchiga nima beradi (natijaga fokus)
4. Shartlar: qachon, qayerda, narx — ANIQ ma'lumot
5. Urgency: "Faqat 3 kun" / "Dastlabki 50 kishi uchun"
6. CTA: "Hoziroq ... qiling" — 1 ta aniq harakat
PG,
            'entertain' => <<<'PG'
KO'NGIL OCHISH MAQSADI (Viral):
1. Kutilmagan boshlanish — o'quvchi "nima?" deb to'xtasin
2. Qisqa hikoya: max 5-7 jumla, emotsional, kulgili yoki hayajonli
3. O'zbek konteksti: tanish holatlar (bozor, mehmon, to'y, qo'shnilar)
4. TWIST: oxirgi 1-2 jumla hamma narsani o'zgartirsin
5. Brand bog'lash: tabiiy, majburiy emas
6. Share trigger: "Do'stingizni tag qiling — u HAM shunday 😂"
PG,
            default => <<<'PG'
UMUMIY MAQSAD:
1. Hook: o'quvchini to'xtatadigan birinchi jumla
2. Story: hissiyot uyg'otadigan hikoya (aniq detallar bilan)
3. Offer: foyda ko'rsatish va 1 ta CTA
PG,
        };
    }

    /**
     * Array ni formatlash
     */
    protected function formatArray(array $items): string
    {
        return implode(', ', array_slice($items, 0, 10));
    }

    /**
     * AI javobidan JSON ideas array ni parse qilish
     * Partial parsing: 10 tadan 7 tasi valid bo'lsa — 7 tasini qaytaradi
     */
    protected function parseIdeasJson(string $text): array
    {
        // 1. To'liq JSON array topish
        preg_match('/\[[\s\S]*\]/m', $text, $matches);

        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded) && !empty($decoded)) {
                return $this->validateIdeas($decoded);
            }

            // 2. Truncated JSON — oxirgi to'liq object dan kesish
            $fixed = $matches[0];
            $lastBrace = strrpos($fixed, '}');
            if ($lastBrace !== false) {
                $fixed = substr($fixed, 0, $lastBrace + 1) . ']';
                $decoded = json_decode($fixed, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return $this->validateIdeas($decoded);
                }
            }
        }

        // 3. Individual objects parse — har bir {...} ni alohida parse qilish
        preg_match_all('/\{[^{}]+\}/m', $text, $objectMatches);
        if (!empty($objectMatches[0])) {
            $ideas = [];
            foreach ($objectMatches[0] as $jsonObj) {
                $obj = json_decode($jsonObj, true);
                if (is_array($obj) && !empty($obj['topic'])) {
                    $ideas[] = $obj;
                }
            }
            if (!empty($ideas)) {
                return $this->validateIdeas($ideas);
            }
        }

        return [];
    }

    /**
     * Ideas array ni validatsiya — faqat topic bor bo'lganlarni qoldirish
     */
    protected function validateIdeas(array $ideas): array
    {
        return array_values(array_filter($ideas, function ($idea) {
            return is_array($idea) && !empty($idea['topic']) && mb_strlen($idea['topic']) > 5;
        }));
    }

    /**
     * IndustryLibrary dan fallback g'oyalar (0 token)
     */
    protected function getIndustryFallbackIdeas(string $industry, string $contentType, string $purpose): array
    {
        $library = new IndustryContentLibrary();
        $topics = $library->getTopicsForIndustry($industry, 10);

        if (empty($topics)) {
            $topics = $library->getTopicsForIndustry('default', 10);
        }

        return collect($topics)->map(function ($topic, $index) use ($purpose) {
            $hookPrefix = match ($purpose) {
                'sell' => 'Bilasizmi, ',
                'educate' => 'Ko\'pchilik bilmaydi: ',
                'inspire' => 'Muvaffaqiyat sirri: ',
                'engage' => 'Siz qanday o\'ylaysiz? ',
                'announce' => 'Yangilik! ',
                'entertain' => 'Bu sizni hayratga soladi: ',
                default => '',
            };

            return [
                'id' => $index + 1,
                'topic' => $topic['topic'] ?? $topic['title'] ?? "Mavzu #{$index}",
                'hook' => $hookPrefix . ($topic['hook'] ?? $topic['topic'] ?? ''),
                'angle' => $topic['angle'] ?? $topic['content_type'] ?? $purpose,
            ];
        })->take(10)->values()->toArray();
    }

    /**
     * User input ni prompt injection dan tozalash
     */
    protected function sanitizeUserInput(string $input, int $maxLength = 500): string
    {
        // Uzunlik limiti
        $input = mb_substr(trim($input), 0, $maxLength);

        // Xavfli ko'rsatmalarni o'chirish
        $dangerous = [
            '/ignore\s+(all\s+)?(previous|above|prior)\s+(instructions?|prompts?|rules?)/iu',
            '/disregard\s+(all\s+)?(previous|above|prior)/iu',
            '/you\s+are\s+now\s+a/iu',
            '/new\s+instructions?:/iu',
            '/system\s*:\s*/iu',
            '/\bDAN\b\s*:/iu',
            '/act\s+as\s+(if|a)\s/iu',
            '/forget\s+(everything|all|previous)/iu',
        ];

        foreach ($dangerous as $pattern) {
            $input = preg_replace($pattern, '[filtered]', $input);
        }

        return $input;
    }
}
