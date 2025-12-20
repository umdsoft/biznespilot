<?php

namespace App\Services;

use App\Models\DreamBuyer;
use App\Services\ClaudeAIService;

class DreamBuyerService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Generate AI-powered Dream Buyer profile based on 9 questions
     */
    public function generateProfile(array $answers): array
    {
        $prompt = $this->buildProfilePrompt($answers);

        $response = $this->claudeAI->complete($prompt, null, 4096);

        return $this->parseProfileResponse($response);
    }

    /**
     * Build prompt for AI profile generation
     */
    protected function buildProfilePrompt(array $answers): string
    {
        return <<<PROMPT
Sen professional marketing strategsan. "Sell Like Crazy" metodologiyasi asosida Dream Buyer profilini yaratishing kerak.

**9 ta savol bo'yicha ma'lumotlar:**

1. **Qayerda vaqt o'tkazadi?**
{$answers['where_spend_time']}

2. **Ma'lumot olish uchun qayerga murojaat qiladi?**
{$answers['info_sources']}

3. **Eng katta frustratsiyalari va qiyinchiliklari?**
{$answers['frustrations']}

4. **Orzulari va umidlari?**
{$answers['dreams']}

5. **Eng katta qo'rquvlari?**
{$answers['fears']}

6. **Qaysi kommunikatsiya shaklini afzal ko'radi?**
{$answers['communication_preferences']}

7. **Qanday til va jargon ishlatadi?**
{$answers['language_style']}

8. **Kundalik hayoti qanday?**
{$answers['daily_routine']}

9. **Nima uni baxtli qiladi?**
{$answers['happiness_triggers']}

**Vazifa:**
Yuqoridagi ma'lumotlar asosida to'liq Dream Buyer profili yarating. Profil quyidagi elementlarni o'z ichiga olishi kerak:

1. **Avatar Name** - Ikki-uch so'zdan iborat profil nomi (masalan: "Tashvishli Ona Sabina", "Muvaffaqiyatga intiluvchi Jasur")
2. **Demographics** - Yosh, jins, joylashuv, kasb, daromad darajasi
3. **Psychographics** - Shaxsiy xususiyatlari, qadriyatlari, lifestyle
4. **Pain Points** - Asosiy muammolar va qiyinchiliklar (5-7 ta)
5. **Goals & Dreams** - Maqsadlar va orzular (5-7 ta)
6. **Fears & Objections** - Qo'rquvlar va e'tirozlar (5-7 ta)
7. **Communication Style** - Qanday muloqot qilish kerak
8. **Marketing Insights** - Marketing uchun maslahatlar
9. **Daily Journey** - Tipik kun rejasi
10. **Purchase Triggers** - Sotib olishga undovchi omillar

**Response Format (JSON):**
{
    "avatar_name": "Profil nomi",
    "tagline": "Qisqa tavsif (1 jumla)",
    "demographics": {
        "age_range": "Yosh oralig'i",
        "gender": "Jinsi",
        "location": "Joylashuv",
        "occupation": "Kasbi",
        "income_level": "Daromad darajasi",
        "education": "Ta'lim darajasi",
        "family_status": "Oilaviy holat"
    },
    "psychographics": {
        "personality_traits": ["Xususiyat 1", "Xususiyat 2", ...],
        "values": ["Qadriyat 1", "Qadriyat 2", ...],
        "lifestyle": "Hayot tarzi tavsifi",
        "interests": ["Qiziqish 1", "Qiziqish 2", ...]
    },
    "pain_points": [
        "Muammo 1",
        "Muammo 2",
        "Muammo 3",
        "Muammo 4",
        "Muammo 5"
    ],
    "goals_dreams": [
        "Maqsad 1",
        "Maqsad 2",
        "Maqsad 3",
        "Maqsad 4",
        "Maqsad 5"
    ],
    "fears_objections": [
        "Qo'rquv 1",
        "Qo'rquv 2",
        "Qo'rquv 3",
        "Qo'rquv 4",
        "Qo'rquv 5"
    ],
    "communication_style": {
        "preferred_channels": ["Kanal 1", "Kanal 2", ...],
        "tone": "Muloqot ohangi",
        "language_tips": ["Maslahat 1", "Maslahat 2", ...],
        "avoid": ["Ishlatmaslik kerak 1", "Ishlatmaslik kerak 2", ...]
    },
    "daily_journey": {
        "morning": "Ertalab nima qiladi",
        "afternoon": "Tushdan keyin nima qiladi",
        "evening": "Kechqurun nima qiladi",
        "peak_time": "Eng faol vaqti"
    },
    "purchase_triggers": [
        "Trigger 1",
        "Trigger 2",
        "Trigger 3",
        "Trigger 4",
        "Trigger 5"
    ],
    "marketing_insights": {
        "best_approach": "Eng yaxshi yondashuv",
        "messaging_tips": ["Tip 1", "Tip 2", ...],
        "content_ideas": ["G'oya 1", "G'oya 2", ...],
        "offer_suggestions": ["Taklif 1", "Taklif 2", ...]
    },
    "quote": "Ushbu Dream Buyerning odatiy fikri yoki gapi"
}

Faqat JSON formatda javob ber, boshqa hech narsa qo'shma.
PROMPT;
    }

    /**
     * Parse AI response into structured profile
     */
    protected function parseProfileResponse(string $response): array
    {
        // Try to extract JSON from response
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json) {
                return $json;
            }
        }

        // Fallback response if parsing fails
        return [
            'avatar_name' => 'Yangi Dream Buyer',
            'tagline' => 'Profil yaratilmadi',
            'demographics' => [],
            'psychographics' => [],
            'pain_points' => [],
            'goals_dreams' => [],
            'fears_objections' => [],
            'communication_style' => [],
            'daily_journey' => [],
            'purchase_triggers' => [],
            'marketing_insights' => [],
            'quote' => 'Profil yaratib bo\'lmadi',
        ];
    }

    /**
     * Generate marketing content suggestions based on Dream Buyer
     */
    public function generateContentIdeas(DreamBuyer $dreamBuyer): array
    {
        $profile = $dreamBuyer->data;

        $prompt = <<<PROMPT
Sen content marketing mutaxassisisan. Quyidagi Dream Buyer uchun kontent g'oyalarini yarating:

**Dream Buyer:** {$dreamBuyer->name}

**Pain Points:**
{$this->formatArray($dreamBuyer->frustrations)}

**Dreams:**
{$this->formatArray($dreamBuyer->dreams)}

**Communication Preferences:**
{$dreamBuyer->communication_preferences}

**Vazifa:**
Ushbu Dream Buyer uchun 10 ta kontent g'oyasini yarating. Har bir g'oya quyidagilarni o'z ichiga olsin:
- Content Type (post, video, story, etc.)
- Platform (Instagram, Facebook, Blog, etc.)
- Topic/Title
- Key Message
- Call to Action

**Response Format (JSON):**
{
    "content_ideas": [
        {
            "type": "Content turi",
            "platform": "Platforma",
            "title": "Sarlavha",
            "key_message": "Asosiy xabar",
            "cta": "Harakat chaqiruvi"
        },
        ...
    ]
}

Faqat JSON formatda javob ber.
PROMPT;

        $response = $this->claudeAI->complete($prompt, null, 3072);

        return $this->parseContentIdeasResponse($response);
    }

    /**
     * Parse content ideas response
     */
    protected function parseContentIdeasResponse(string $response): array
    {
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json && isset($json['content_ideas'])) {
                return $json['content_ideas'];
            }
        }

        return [];
    }

    /**
     * Generate ad copy suggestions
     */
    public function generateAdCopy(DreamBuyer $dreamBuyer, string $product): array
    {
        $prompt = <<<PROMPT
Sen ad copywriter mutaxassisisan. Quyidagi mahsulot uchun reklama matnlari yarating:

**Mahsulot/Xizmat:** {$product}

**Target Dream Buyer:**
- Frustrations: {$this->formatArray($dreamBuyer->frustrations)}
- Dreams: {$this->formatArray($dreamBuyer->dreams)}
- Fears: {$this->formatArray($dreamBuyer->fears)}
- Language Style: {$dreamBuyer->language_style}

**Vazifa:**
5 xil reklama matni variantini yarating:
1. Problem-Focused (muammoga yo'naltirilgan)
2. Dream-Focused (orzularga yo'naltirilgan)
3. Fear-Based (qo'rquvga asoslangan)
4. Social Proof (ijtimoiy dalil)
5. Urgency (shoshilinchlik)

Har biri:
- Headline (sarlavha, 5-10 so'z)
- Body (asosiy matn, 2-3 jumla)
- CTA (harakat chaqiruvi, 1 jumla)

**Response Format (JSON):**
{
    "ad_variants": [
        {
            "type": "Problem-Focused",
            "headline": "Sarlavha",
            "body": "Asosiy matn",
            "cta": "Harakat chaqiruvi"
        },
        ...
    ]
}

Faqat JSON formatda javob ber.
PROMPT;

        $response = $this->claudeAI->complete($prompt, null, 3072);

        return $this->parseAdCopyResponse($response);
    }

    /**
     * Parse ad copy response
     */
    protected function parseAdCopyResponse(string $response): array
    {
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json && isset($json['ad_variants'])) {
                return $json['ad_variants'];
            }
        }

        return [];
    }

    /**
     * Format array as string for prompts
     */
    protected function formatArray($data): string
    {
        if (is_array($data)) {
            return implode("\n", array_map(fn($item) => "- $item", $data));
        }
        return $data ?? '';
    }
}
