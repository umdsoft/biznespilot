<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Services\ClaudeAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Content AI Enrichment Service — 3-qatlam: AI boyitish
 *
 * Algoritmik bazani Claude AI bilan boyitadi:
 * - Kuchli, emotsiyaga boy hooklar (5 xil texnika)
 * - Format-specific ssenariylar (reel, carousel, story, post)
 * - Professional captionlar (DreamBuyer psixologiyasiga asoslangan)
 * - Soha-specific uslub va emotsiya mapping
 *
 * Kontekst manbalari:
 * - Business: nomi, sohasi, maqsadlari, target_audience
 * - DreamBuyer: qo'rquvlari, orzulari, frustratsiyalari, tili
 * - IndustryContentLibrary: soha mavzulari va hooklar
 * - InstagramAlgorithmEngine: IG qoidalari
 *
 * Xavfsiz: AI ishlamasa null qaytaradi, algoritmik kontent o'zgarmaydi
 */
class ContentAIEnrichmentService
{
    /**
     * Viral hook texnikalari — har bir hook boshqacha bo'lishi uchun
     */
    private const HOOK_TECHNIQUES = [
        'shock_number' => "Hayratlanarli raqam yoki statistika bilan boshlash. Misol: '93% biznes egalari shu xatoni qiladi'",
        'personal_story' => "Shaxsiy hikoya bilan boshlash. Misol: 'Bir yil oldin men ham xuddi shu holatda edim...'",
        'secret_reveal' => "Sir ochish. Misol: 'Hech kim aytmaydigan haqiqat...' yoki 'Bu sirni bilganlar 2x ko'proq sotadi'",
        'direct_question' => "To'g'ridan-to'g'ri savol. Misol: 'Siz ham shu xatoni qilyapsizmi?' yoki 'Bu muammoni bilasizmi?'",
        'bold_statement' => "Keskin da'vo. Misol: 'Bu usulni bilmasangiz, raqobatchilaringizdan doim orqada qolasiz'",
    ];

    /**
     * Emotsiya triggerlari — maqsadga qarab qaysi emotsiyani ishlatish
     */
    private const EMOTION_MAP = [
        'educational' => ['qiziqish', 'hayrat', 'foydalilik hissi', 'o\'z-o\'zini rivojlantirish ishtiyoqi'],
        'promotional' => ['qo\'rquv (imkoniyatni boy berish)', 'ishonch', 'xavfsizlik', 'qadrlanish'],
        'engagement' => ['qiziqish', 'raqobat hissi', 'jamoaga tegishlilik', 'o\'z fikrini bildirish'],
        'behind_scenes' => ['ishonch', 'yaqinlik', 'hurmat', 'qiziqish'],
        'testimonial' => ['umid', 'ilhom', 'havas', 'ishonch'],
        'pain_point' => ['tushunilganlik hissi', 'yengillik', 'umid', 'harakat motivatsiyasi'],
    ];

    /**
     * Soha uslub xaritasi — har bir soha uchun ton va yondashuv
     */
    private const INDUSTRY_TONE = [
        'beauty' => [
            'tone' => 'iliq, do\'stona, ilhomlantiruvchi',
            'style' => 'Oddiy, samimiy tilda gapir. Mijozga eng yaqin dugonasidek maslahat ber.',
            'emotional_triggers' => 'Chiroyli bo\'lish istagi, o\'ziga ishonch, e\'tirof olish',
            'avoid' => 'Juda rasmiy til, murakkab tushunchalar',
        ],
        'restaurant' => [
            'tone' => 'ishtahani ochuvchi, samimiy, tantanali',
            'style' => 'Ta\'mni his qildiruvchi so\'zlar ishlatish. Odamning og\'zini suvini keltirsin.',
            'emotional_triggers' => 'Ishtaha, nostalgia, oila bilan vaqt o\'tkazish',
            'avoid' => 'Quruq tavsiflar, baho ro\'yxati',
        ],
        'ecommerce' => [
            'tone' => 'ishonchli, shoshilinch, foydali',
            'style' => 'Oddiy va to\'g\'ri gapir. Foyda va natijaga e\'tibor ber. Cheklangan vaqt hissi.',
            'emotional_triggers' => 'Tejash istagi, imkoniyatni boy berish qo\'rquvi, mukammal tanlash',
            'avoid' => 'Juda ko\'p texnik xususiyatlar',
        ],
        'retail' => [
            'tone' => 'do\'stona, qiziqarli, ishonchli',
            'style' => 'Mijozga do\'st sifatida maslahat ber. Mahsulot hikoyasini aytib ber.',
            'emotional_triggers' => 'Yangilik qidirish, o\'z uslubini topish, qadrlanish',
            'avoid' => 'Baho haqida juda ko\'p gapirish',
        ],
        'service' => [
            'tone' => 'professional, ishonchli, samimiy',
            'style' => 'Ekspert sifatida gapir, lekin oddiy tilda. Natija va foyda haqida gapir.',
            'emotional_triggers' => 'Ishonch, xavfsizlik, vaqt tejash, sifat kafolati',
            'avoid' => 'Juda texnik terminlar, uzun tushuntirishlar',
        ],
        'fitness' => [
            'tone' => 'energetik, motivatsion, samimiy',
            'style' => 'Odamlarni harakatga undaydigan, kuchli motivatsiya beradigan til.',
            'emotional_triggers' => 'O\'zini yaxshi his qilish, sog\'liq, kuch, o\'zgartirish istagi',
            'avoid' => 'Tanqid, uyaltirish, juda murakkab mashqlar',
        ],
        'saas' => [
            'tone' => 'professional, innovatsion, foydali',
            'style' => 'Muammoni aniq ko\'rsat, yechimni oddiy tushuntir. Raqamlar bilan isbotla.',
            'emotional_triggers' => 'Samaradorlik, vaqt tejash, raqobatchilardan ustunlik',
            'avoid' => 'Texnik jargon, abstract tushunchalar',
        ],
    ];

    public function __construct(
        private ClaudeAIService $claude,
    ) {}

    /**
     * Bitta kontent elementini AI bilan boyitish
     *
     * @return array{hooks: array, caption: string, script: ?string, cta: string}|null
     */
    public function enrichContentItem(
        array $topic,
        string $contentType,
        string $purpose,
        array $igTips,
        Business $business,
    ): ?array {
        if (! $this->claude->isAvailable()) {
            return null;
        }

        $industryName = $business->industryRelation?->name_uz ?? $business->category ?? 'Umumiy biznes';
        $industryCode = $business->industry_code ?? 'default';
        $cacheKey = $this->buildCacheKey($topic['topic'], $industryName, $contentType, $purpose);

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            // DreamBuyer kontekstini olish (cache bilan)
            $dreamBuyerContext = $this->getDreamBuyerContext($business->id);

            $systemPrompt = $this->buildSystemPrompt($business, $industryName, $industryCode, $purpose);
            $userPrompt = $this->buildUserPrompt(
                $topic, $contentType, $purpose,
                $business, $industryName, $dreamBuyerContext
            );

            $response = $this->claude->complete(
                prompt: $userPrompt,
                systemPrompt: $systemPrompt,
                maxTokens: 1500,
                useCache: false,
                usePremiumModel: false,
            );

            $parsed = $this->parseResponse($response, $contentType);

            if ($parsed) {
                Cache::put($cacheKey, $parsed, now()->addHours(12));
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::warning('ContentAIEnrichmentService: enrichment failed', [
                'topic' => $topic['topic'],
                'content_type' => $contentType,
                'purpose' => $purpose,
                'business_id' => $business->id,
                'industry' => $industryName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * DreamBuyer ma'lumotlarini olish (cache bilan)
     */
    private function getDreamBuyerContext(string $businessId): array
    {
        $cacheKey = "dream_buyer_ctx:{$businessId}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $dreamBuyer = DreamBuyer::withoutGlobalScope('business')
            ->where('business_id', $businessId)
            ->where('is_primary', true)
            ->first();

        if (! $dreamBuyer) {
            $dreamBuyer = DreamBuyer::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->orderByDesc('priority')
                ->first();
        }

        $context = [];
        if ($dreamBuyer) {
            // Accessor orqali q-ustunlardan o'qiladi:
            //   frustrations → q6_what_are_they_frustrated_with
            //   dreams → q8_what_do_they_secretly_want
            //   fears → q5_what_are_they_afraid_of
            //   where_spend_time → q3_where_do_they_hang_out
            // Qo'shimcha q-ustunlar to'g'ridan-to'g'ri:
            //   q4_what_keeps_them_up — tungi tashvishlar (fears ga qo'shimcha)
            //   q9_how_do_they_make_decisions — qaror qabul qilish uslubi
            $context = [
                'name' => $dreamBuyer->name,
                'frustrations' => $dreamBuyer->frustrations,
                'dreams' => $dreamBuyer->dreams,
                'fears' => $dreamBuyer->fears,
                'worries' => $dreamBuyer->q4_what_keeps_them_up,
                'language_style' => $dreamBuyer->language_style,
                'where_spend_time' => $dreamBuyer->where_spend_time,
                'happiness_triggers' => $dreamBuyer->happiness_triggers,
                'decision_style' => $dreamBuyer->q9_how_do_they_make_decisions,
                'pain_points' => $dreamBuyer->pain_points,
                'goals' => $dreamBuyer->goals,
            ];
            // Bo'sh qiymatlarni olib tashlash
            $context = array_filter($context, fn ($v) => ! empty($v));
        }

        Cache::put($cacheKey, $context, now()->addHours(6));

        return $context;
    }

    private function buildSystemPrompt(Business $business, string $industryName, string $industryCode, string $purpose): string
    {
        $businessName = $business->name ?? 'Biznes';
        $industryTone = self::INDUSTRY_TONE[$industryCode] ?? self::INDUSTRY_TONE['service'];
        $emotions = self::EMOTION_MAP[$purpose] ?? self::EMOTION_MAP['educational'];
        $emotionsStr = implode(', ', $emotions);

        $hookTechniques = '';
        foreach (self::HOOK_TECHNIQUES as $desc) {
            $hookTechniques .= "- {$desc}\n";
        }

        return <<<EOT
Sen O'zbekistondagi eng kuchli Instagram kontent yaratuvchisan. 500+ biznesga viral kontent yaratgansan. Har bir postning maqsadi — odam scrollni to'xtatib, oxirigacha o'qib, harakatga o'tishi.

BREND: "{$businessName}" | SOHA: {$industryName}

YOZISH USLUBI:
- Ton: {$industryTone['tone']}
- Uslub: {$industryTone['style']}
- Nimadan saqlaning: {$industryTone['avoid']}

HOOK TEXNIKALARI (3 ta hook yoz, har biri BOSHQACHA texnikada):
{$hookTechniques}
EMOTSIYA TRIGGERLARI (bu maqsad uchun ishlat):
{$emotionsStr}

MUHIM QOIDALAR:
1. FAQAT O'zbek Lotin alifbosida yoz (a-z, o', g', sh, ch)
2. Oddiy, sodda tilda yoz — bolaga tushuntirganday. Texnik terminlar ISHLATMA
3. Har bir gap emotsiya bersin — odam o'qib "ha, menga ham shunday!" desin
4. Caption uzunligi: 150-300 so'z. Qisqa gap ishlatish. Har 2-3 qatorda bo'sh qator
5. Hooklar JUDA kuchli bo'lsin — odam 0.5 soniyada to'xtashi SHART
6. CTA aniq va bitta bo'lsin — odam nima qilishini bilsin

JAVOB FORMATI — faqat JSON, boshqa hech narsa yozma:
{
    "hooks": ["hook1", "hook2", "hook3"],
    "caption": "to'liq caption matni",
    "script": "ssenariy matni yoki null",
    "cta": "harakatga chaqiruv matni"
}
EOT;
    }

    private function buildUserPrompt(
        array $topic,
        string $contentType,
        string $purpose,
        Business $business,
        string $industryName,
        array $dreamBuyerContext,
    ): string {
        $topicName = $topic['topic'];
        $painText = $topic['pain_text'] ?? null;
        $existingHooks = $topic['hooks'] ?? [];

        $formatLabel = match ($contentType) {
            'reel' => 'Qisqa video (Reel, 15-30 sekund)',
            'carousel' => 'Slaydli post (Carousel, 5-7 slayd)',
            'story' => 'Hikoya (Story, interaktiv)',
            default => 'Post (rasm + yozuv)',
        };

        $purposeLabel = match ($purpose) {
            'educational' => "O'rgatish — odam yangi narsa o'rgansin va 'buni bilmasdim!' desin",
            'promotional' => "Sotish — odam 'menga ham kerak!' deb xabar yozsin",
            'engagement' => "Faollik — odam izoh yozsin, do'stlarini belgilasin, saqlastin",
            'behind_scenes' => "Ishonch — odam 'bular haqiqiy odamlar ekan' deb his qilsin",
            'testimonial' => "Natija — odam 'men ham shunday natijaga erishsam' deb o'ylasin",
            'pain_point' => "Muammo yechimi — odam 'ha, menga ham shunday, nima qilsam bo'ladi?' desin",
            default => 'Umumiy kontent',
        };

        $prompt = "=== VAZIFA ===\n";
        $prompt .= "MAVZU: {$topicName}\n";
        $prompt .= "FORMAT: {$formatLabel}\n";
        $prompt .= "MAQSAD: {$purposeLabel}\n\n";

        // Biznes konteksti
        $prompt .= "=== BIZNES HAQIDA ===\n";
        $prompt .= "Nomi: {$business->name}\n";
        $prompt .= "Soha: {$industryName}\n";
        if ($business->target_audience) {
            $prompt .= "Maqsadli auditoriya: {$business->target_audience}\n";
        }
        if (! empty($business->main_goals)) {
            $goalsStr = is_array($business->main_goals) ? implode(', ', array_slice($business->main_goals, 0, 3)) : $business->main_goals;
            $prompt .= "Biznes maqsadlari: {$goalsStr}\n";
        }
        $prompt .= "\n";

        // DreamBuyer konteksti — eng muhim qism
        if (! empty($dreamBuyerContext)) {
            $prompt .= "=== IDEAL MIJOZ (yozuvni shu odamga qaratib yoz) ===\n";
            if (! empty($dreamBuyerContext['name'])) {
                $prompt .= "Nomi: {$dreamBuyerContext['name']}\n";
            }
            if (! empty($dreamBuyerContext['frustrations'])) {
                $prompt .= "Eng katta muammolari: {$dreamBuyerContext['frustrations']}\n";
                $prompt .= "→ Bu muammolarni HIS QILDIRUVCHI tarzda yoz!\n";
            }
            if (! empty($dreamBuyerContext['dreams'])) {
                $prompt .= "Orzulari: {$dreamBuyerContext['dreams']}\n";
                $prompt .= "→ Bu orzularga yetishishga UMID ber!\n";
            }
            if (! empty($dreamBuyerContext['fears'])) {
                $prompt .= "Qo'rquvlari: {$dreamBuyerContext['fears']}\n";
                $prompt .= "→ Qo'rquvlarini tasdiqlama, YECHIM ko'rsat!\n";
            }
            if (! empty($dreamBuyerContext['worries'])) {
                $prompt .= "Tungi tashvishlari: {$dreamBuyerContext['worries']}\n";
                $prompt .= "→ Bu tashvishlarni tushunganingni ko'rsat!\n";
            }
            if (! empty($dreamBuyerContext['pain_points'])) {
                $prompt .= "Og'riq nuqtalari: {$dreamBuyerContext['pain_points']}\n";
            }
            if (! empty($dreamBuyerContext['goals'])) {
                $prompt .= "Maqsadlari: {$dreamBuyerContext['goals']}\n";
            }
            if (! empty($dreamBuyerContext['decision_style'])) {
                $prompt .= "Qaror qabul qilish uslubi: {$dreamBuyerContext['decision_style']}\n";
                $prompt .= "→ Shu uslubga moslangan CTA yoz!\n";
            }
            if (! empty($dreamBuyerContext['language_style'])) {
                $prompt .= "Qanday til ishlatadi: {$dreamBuyerContext['language_style']}\n";
                $prompt .= "→ SHU tilda yoz — o'zini tushunilgan his qilsin!\n";
            }
            $prompt .= "\n";
        }

        // Mijoz muammosi (agar mavjud bo'lsa)
        if ($painText) {
            $prompt .= "=== MIJOZ MUAMMOSI ===\n";
            $prompt .= "{$painText}\n";
            $prompt .= "→ Bu muammoni o'qigan odam \"ha, menga ham AYNAN shunday!\" deb bosh irg'asin.\n";
            $prompt .= "→ Muammoni tasvirlab, keyin YECHIM ko'rsat.\n\n";
        }

        // Mavjud hooklar — yaxshilash uchun
        if (! empty($existingHooks)) {
            $hooksStr = implode("', '", array_slice($existingHooks, 0, 2));
            $prompt .= "=== MAVJUD HOOKLAR (bulardan 3x KUCHLIROQ yoz) ===\n";
            $prompt .= "'{$hooksStr}'\n";
            $prompt .= "→ Bu hooklar oddiy. Sen ancha kuchli, emotsiyaga boy hooklar yoz!\n\n";
        }

        // Format-specific ko'rsatmalar
        $prompt .= $this->getFormatInstructions($contentType);

        return $prompt;
    }

    private function getFormatInstructions(string $contentType): string
    {
        return match ($contentType) {
            'reel' => <<<EOT

=== QISQA VIDEO SSENARIYSI ===
15-30 soniyalik ssenariy yoz. SAHNA formatida:

SAHNA 1 (0-3 soniya): HOOK — tomoshabin to'xtashi SHART
- Ekranda: [nima ko'rsatiladi]
- Aytiladi: [nima aytiladi — qisqa, keskin]

SAHNA 2 (3-12 soniya): MUAMMO yoki QIZIQARLI FAKT
- Ekranda: [nima ko'rsatiladi]
- Aytiladi: [emotsiyaga boy gap, odam "ha!" desin]

SAHNA 3 (12-22 soniya): YECHIM yoki FOYDALI MA'LUMOT
- Ekranda: [nima ko'rsatiladi]
- Aytiladi: [aniq, foydali ma'lumot]

SAHNA 4 (22-30 soniya): CTA — harakatga chaqiruv
- Ekranda: [nima ko'rsatiladi]
- Aytiladi: [bitta aniq chaqiruv]

MUHIM: Har bir sahna qisqa, tez. Odamlar zerikmasin.
"script" maydoniga shu ssenariyni yoz.
"caption" ga video ostidagi qisqa yozuvni yoz (50-100 so'z).
EOT,

            'carousel' => <<<EOT

=== SLAYDLI POST REJASI ===
7 slaydli post yoz. Har bir slayd uchun ANIQ matn:

SLAYD 1 — COVER: Eng kuchli hook. Odam bu slaydni ko'rib surashni boshlashi SHART.
Misol: "90% odam shu 3 xatoni qiladi. Siz ham qilyapsizmi?"

SLAYD 2 — MUAMMO: Mijoz muammosini tasvirla. Odam "ha, menga ham shunday!" desin.

SLAYD 3 — 1-NUQTA: Birinchi yechim yoki fakt. Qisqa va tushunarli.

SLAYD 4 — 2-NUQTA: Ikkinchi yechim yoki fakt. Misollar bilan.

SLAYD 5 — 3-NUQTA: Uchinchi yechim yoki fakt. Eng kuchli nuqta oxirida.

SLAYD 6 — XULOSA: Asosiy fikrni takrorla. Natijani ko'rsat.

SLAYD 7 — CTA: Aniq harakatga chaqiruv. "Saqlang + ulashing + izoh yozing"

Har bir slayd uchun: matn (3-5 qator) + dizayn eslatmasi yoz.
"script" maydoniga SHU rejani yoz.
"caption" ga to'liq post yozuvini yoz (150-250 so'z).
EOT,

            'story' => <<<EOT

=== HIKOYA REJASI ===
3-5 qismli interaktiv Story rejasi yoz:

HIKOYA 1: HOOK — e'tiborni tort (rasm + matn)
HIKOYA 2: MUAMMO yoki SAVOL — interaktiv stiker bilan
  Ishlat: So'rovnoma / Viktorina / Slider / Savol stikeri
HIKOYA 3: JAVOB yoki YECHIM — foydali ma'lumot
HIKOYA 4: CTA — "Xabar yozing" yoki "Profilga o'ting"

MUHIM: Har bir hikoyada interaktiv element bo'lsin.
"script" maydoniga hikoya rejasini yoz.
"caption" ga qisqa tavsif yoz.
EOT,

            default => <<<EOT

=== POST YOZUVI ===
To'liq Instagram post captionini yoz. Tuzilishi:

1-QATOR: HOOK — eng kuchli gap. Odam scrollni to'xtatsin.
(bo'sh qator)
2-4 QATOR: Muammo yoki qiziqarli fakt. Odam o'zini tanisin.
(bo'sh qator)
5-8 QATOR: Yechim, foydali ma'lumot yoki hikoya.
(bo'sh qator)
9-10 QATOR: Xulosa + CTA.

MUHIM:
- Har 2-3 qatorda bo'sh qator qo'y
- Qisqa gaplar ishlatish — har biri alohida fikr
- Emoji ishlatish mumkin, lekin 3-4 tadan ko'p emas
- "script" maydonini null qoldir
EOT,
        };
    }

    private function parseResponse(string $response, string $contentType): ?array
    {
        // JSON ni topish
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            Log::warning('ContentAIEnrichmentService: JSON not found in response', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 300),
            ]);
            return null;
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);

        // JSON ichidagi escaped qatorlarni tozalash
        $jsonString = preg_replace('/[\x00-\x1f]/', ' ', $jsonString);

        $data = json_decode($jsonString, true);

        if (! $data || ! is_array($data)) {
            Log::warning('ContentAIEnrichmentService: Invalid JSON', [
                'json_string' => substr($jsonString, 0, 300),
                'json_error' => json_last_error_msg(),
            ]);
            return null;
        }

        // Validatsiya
        $hooks = $data['hooks'] ?? [];
        $caption = $data['caption'] ?? '';
        $script = $data['script'] ?? null;
        $cta = $data['cta'] ?? '';

        if (empty($hooks) && empty($caption)) {
            return null;
        }

        // Hooklar string massiv bo'lishi kerak
        if (! is_array($hooks)) {
            $hooks = [$hooks];
        }
        $hooks = array_filter($hooks, fn ($h) => is_string($h) && mb_strlen($h) > 10);
        $hooks = array_values(array_slice($hooks, 0, 3));

        // Script faqat reel, carousel va story uchun
        if (! in_array($contentType, ['reel', 'carousel', 'story']) && $script !== null) {
            $script = null;
        }

        // "null" stringini null ga aylantirish
        if ($script === 'null' || $script === '') {
            $script = null;
        }

        // Caption tozalash
        $caption = is_string($caption) ? trim($caption) : '';

        // CTA tozalash
        $cta = is_string($cta) ? trim($cta) : '';

        return [
            'hooks' => $hooks,
            'caption' => $caption,
            'script' => is_string($script) ? trim($script) : null,
            'cta' => $cta,
        ];
    }

    private function buildCacheKey(string $topic, string $industry, string $contentType, string $purpose): string
    {
        return 'ai_enrich:' . md5("{$topic}|{$industry}|{$contentType}|{$purpose}");
    }
}
