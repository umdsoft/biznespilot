<?php

namespace App\Services;

use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Services\ClaudeAIService;

class OfferBuilderService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Generate AI-powered irresistible offer based on Dream Buyer
     * Uses "$100M Offers" and "Sell Like Crazy" methodologies
     */
    public function generateOffer(array $data, ?DreamBuyer $dreamBuyer = null): array
    {
        $prompt = $this->buildOfferPrompt($data, $dreamBuyer);

        $response = $this->claudeAI->complete($prompt, null, 4096);

        return $this->parseOfferResponse($response);
    }

    /**
     * Build comprehensive prompt for AI offer generation
     */
    protected function buildOfferPrompt(array $data, ?DreamBuyer $dreamBuyer = null): string
    {
        $dreamBuyerContext = '';

        if ($dreamBuyer) {
            $dreamBuyerContext = <<<CONTEXT

**DREAM BUYER PROFILI:**
- **Ism:** {$dreamBuyer->name}
- **Frustrations:** {$dreamBuyer->frustrations}
- **Dreams:** {$dreamBuyer->dreams}
- **Fears:** {$dreamBuyer->fears}
- **Communication Preferences:** {$dreamBuyer->communication_preferences}
- **Language Style:** {$dreamBuyer->language_style}
- **Purchase Triggers:** {$this->extractPurchaseTriggers($dreamBuyer)}

CONTEXT;
        }

        return <<<PROMPT
Sen professional marketing strategsan va "$100M Offers" hamda "Sell Like Crazy" metodologiyalari bo'yicha mutaxassissan.

{$dreamBuyerContext}

**MAHSULOT/XIZMAT MA'LUMOTLARI:**
- **Nomi:** {$data['product_name']}
- **Tavsif:** {$data['product_description']}
- **Asosiy foyda:** {$data['main_benefit']}
- **Narx:** {$data['price']}
- **Maqsadli auditoriya:** {$data['target_audience']}

**VAZIFA:**
"$100M Offers" va "Sell Like Crazy" metodologiyalariga asoslanib, irresistible (qarshilik ko'rsatib bo'lmaydigan) offer yarating.

Offer quyidagilarni o'z ichiga olishi kerak:

1. **CORE OFFER** - Asosiy taklif (nima oladi mijoz)
2. **VALUE PROPOSITION** - Qiymat taklifi (nima uchun bu tanlov yaxshi)
3. **OFFER STACK (Bonus paket):**
   - Bonus 1-5: Qo'shimcha qiymat (har biri alohida value bilan)
   - Har bir bonus uchun: Nom, Tavsif, Qiymat (raqamda)
4. **GUARANTEE** - Kafolat (risk reversal)
   - Kafolat turi (masalan: "30 kunlik pul qaytarish", "Natija kafolati")
   - Shartlar (qanday qilib ishlaydi)
   - Muddat (necha kun)
5. **SCARCITY** - Kamlik (cheklangan miqdor/vaqt)
6. **URGENCY** - Shoshilinchlik (nima uchun hozir harakat qilish kerak)
7. **VALUE EQUATION** - Alex Hormozi formulasi bo'yicha:
   - Dream Outcome Score (1-10): Qanchalik katta orzuga erishadi
   - Perceived Likelihood Score (1-10): Natijaga ishonch darajasi
   - Time Delay (kunlarda): Natija qancha vaqtda
   - Effort Score (1-10): Qanchalik oson/qiyin
8. **TOTAL VALUE** - Umumiy qiymat (barcha bonuslar + asosiy taklif)
9. **MARKETING COPY:**
   - Headline: Diqqatni tortuvchi sarlavha
   - Subheadline: Qo'shimcha qiziqtirish
   - Main CTA: Asosiy harakat chaqiruvi

**RESPONSE FORMAT (JSON):**
{
    "name": "Offer nomi (qisqa va ta'sirchan)",
    "core_offer": "Asosiy taklif (2-3 jumla)",
    "value_proposition": "Qiymat taklifi (nima uchun bu yaxshi, 3-4 jumla)",
    "offer_components": [
        {
            "type": "bonus",
            "name": "Bonus nomi",
            "description": "Bonus tavsifi",
            "value": 1000000,
            "order": 1,
            "is_highlighted": false
        },
        {
            "type": "bonus",
            "name": "Bonus 2",
            "description": "Tavsif",
            "value": 500000,
            "order": 2,
            "is_highlighted": true
        }
    ],
    "guarantee_type": "30 kunlik pul qaytarish kafolati",
    "guarantee_terms": "Agar 30 kun ichida natija ko'rmasa, to'liq pul qaytariladi, hech qanday savol so'ralmasdan.",
    "guarantee_period_days": 30,
    "scarcity": "Faqat 50 ta joy mavjud. Joylar tez tugaydi!",
    "urgency": "Narx 3 kundan keyin 30% oshadi. Hozir buyurtma bering va chegirmani qo'lga kiriting!",
    "dream_outcome_score": 9,
    "perceived_likelihood_score": 8,
    "time_delay_days": 30,
    "effort_score": 3,
    "total_value": 5000000,
    "pricing_model": "One-time payment / Monthly subscription / Payment plan",
    "headline": "Ta'sirchan sarlavha",
    "subheadline": "Qo'shimcha qiziqtiruvchi matn",
    "main_cta": "Hozir Boshlash",
    "status": "draft"
}

**MUHIM QOIDALAR:**
- Dream Buyer ma'lumotlarini hisobga oling
- Ularning frustrations, dreams, fears ga to'g'ridan-to'g'ri murojaat qiling
- Value Stack haqiqiy va qimmatli bo'lishi kerak
- Guarantee kuchli va xavfsiz his qilsin
- Scarcity va Urgency haqiqiy va etikaviy bo'lsin
- O'zbek tilidagi natural va professional so'zlar ishlating

Faqat JSON formatda javob ber, boshqa hech narsa qo'shma.
PROMPT;
    }

    /**
     * Extract purchase triggers from Dream Buyer data
     */
    protected function extractPurchaseTriggers(?DreamBuyer $dreamBuyer): string
    {
        if (!$dreamBuyer || !$dreamBuyer->data) {
            return 'Aniqlanmagan';
        }

        $triggers = $dreamBuyer->data['purchase_triggers'] ?? [];

        if (is_array($triggers)) {
            return implode(', ', $triggers);
        }

        return $triggers;
    }

    /**
     * Parse AI offer response
     */
    protected function parseOfferResponse(string $response): array
    {
        // Try to extract JSON from response
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json) {
                return $json;
            }
        }

        // Fallback response
        return [
            'name' => 'Yangi Offer',
            'core_offer' => 'Offer yaratilmadi',
            'value_proposition' => 'AI javob berolmadi',
            'offer_components' => [],
            'guarantee_type' => '30 kunlik kafolat',
            'guarantee_terms' => 'Standart shartlar',
            'guarantee_period_days' => 30,
            'scarcity' => '',
            'urgency' => '',
            'dream_outcome_score' => 5,
            'perceived_likelihood_score' => 5,
            'time_delay_days' => 30,
            'effort_score' => 5,
            'total_value' => 0,
            'pricing_model' => 'one-time',
            'headline' => '',
            'subheadline' => '',
            'main_cta' => 'Hozir Boshlash',
            'status' => 'draft',
        ];
    }

    /**
     * Generate variations for A/B testing
     */
    public function generateVariations(Offer $offer, int $count = 3): array
    {
        $headline = $offer->metadata['headline'] ?? '';

        $prompt = <<<PROMPT
Sen marketing strategsan. Quyidagi offer uchun {$count} ta variant yarating A/B testing uchun.

**ORIGINAL OFFER:**
- **Nom:** {$offer->name}
- **Core Offer:** {$offer->core_offer}
- **Headline:** {$headline}
- **Guarantee:** {$offer->guarantee_type}

**VAZIFA:**
Har biri boshqacha psychological trigger ishlatadigan {$count} ta variant yarating:
1. Fear-Based (qo'rquvga asoslangan)
2. Dream-Focused (orzularga yo'naltirilgan)
3. Social Proof (ijtimoiy dalil)

**RESPONSE FORMAT (JSON):**
{
    "variations": [
        {
            "variant_name": "Fear-Based Variant",
            "headline": "Yangi sarlavha",
            "subheadline": "Yangi subheadline",
            "main_cta": "Yangi CTA",
            "psychological_trigger": "fear",
            "key_changes": "Asosiy o'zgarishlar tavsifi"
        }
    ]
}

Faqat JSON formatda javob ber.
PROMPT;

        $response = $this->claudeAI->complete($prompt, null, 3072);

        return $this->parseVariationsResponse($response);
    }

    /**
     * Parse variations response
     */
    protected function parseVariationsResponse(string $response): array
    {
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json && isset($json['variations'])) {
                return $json['variations'];
            }
        }

        return [];
    }

    /**
     * Optimize offer based on performance data
     */
    public function optimizeOffer(Offer $offer, array $performanceData): array
    {
        $conversionRate = $performanceData['conversion_rate'] ?? 0;
        $avgTimeOnPage = $performanceData['avg_time_on_page'] ?? 0;
        $dropoffPoint = $performanceData['dropoff_point'] ?? 'unknown';

        $prompt = <<<PROMPT
Sen conversion optimization mutaxassisisan. Quyidagi offer ni takomillash uchun tavsiyalar bering.

**OFFER:**
- **Nom:** {$offer->name}
- **Conversion Rate:** {$conversionRate}%
- **O'rtacha sahifada vaqt:** {$avgTimeOnPage} sekund
- **Eng ko'p tushish nuqtasi:** {$dropoffPoint}

**VAZIFA:**
Conversion rate ni oshirish uchun 5-7 ta aniq tavsiya bering.

**RESPONSE FORMAT (JSON):**
{
    "optimization_suggestions": [
        {
            "area": "Headline / Guarantee / Pricing / CTA / Value Stack",
            "current_issue": "Muammo tavsifi",
            "suggestion": "Tavsiya",
            "expected_impact": "low / medium / high",
            "priority": 1
        }
    ],
    "estimated_conversion_lift": "5-10%"
}

Faqat JSON formatda javob ber.
PROMPT;

        $response = $this->claudeAI->complete($prompt, null, 3072);

        return $this->parseOptimizationResponse($response);
    }

    /**
     * Parse optimization response
     */
    protected function parseOptimizationResponse(string $response): array
    {
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json) {
                return $json;
            }
        }

        return [
            'optimization_suggestions' => [],
            'estimated_conversion_lift' => '0%',
        ];
    }

    /**
     * Calculate offer value score using Value Equation
     * Formula from "$100M Offers": (Dream Outcome × Perceived Likelihood) / (Time Delay × Effort)
     */
    public function calculateValueScore(array $data): float
    {
        $dreamOutcome = $data['dream_outcome_score'] ?? 5;
        $perceivedLikelihood = $data['perceived_likelihood_score'] ?? 5;
        $timeDelay = $data['time_delay_days'] ?? 30;
        $effort = $data['effort_score'] ?? 5;

        $denominator = ($timeDelay * $effort);
        if ($denominator == 0) {
            return 0;
        }

        return round(($dreamOutcome * $perceivedLikelihood) / $denominator, 2);
    }

    /**
     * Generate compelling guarantee copy
     */
    public function generateGuarantee(string $productType, string $targetAudience): array
    {
        $prompt = <<<PROMPT
Sen copywriting mutaxassisisan. Quyidagi mahsulot uchun kuchli guarantee (kafolat) yarating.

**Mahsulot:** {$productType}
**Auditoriya:** {$targetAudience}

**VAZIFA:**
3 xil guarantee varianti yarating:
1. Money-Back Guarantee (pul qaytarish)
2. Results Guarantee (natija kafolati)
3. Satisfaction Guarantee (qoniqish kafolati)

**RESPONSE FORMAT (JSON):**
{
    "guarantees": [
        {
            "type": "Money-Back Guarantee",
            "headline": "Qisqa sarlavha",
            "terms": "Batafsil shartlar (2-3 jumla)",
            "period_days": 30,
            "strength_score": 8
        }
    ]
}

Faqat JSON formatda javob ber.
PROMPT;

        $response = $this->claudeAI->complete($prompt, null, 2048);

        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json && isset($json['guarantees'])) {
                return $json['guarantees'];
            }
        }

        return [];
    }
}
