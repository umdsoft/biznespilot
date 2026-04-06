<?php

namespace App\Services\Agent;

use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Agent yo'naltiruvchi — foydalanuvchi savolini qaysi agentga yuborishni aniqlaydi.
 *
 * Gibrid mantiq:
 * 1. Avval kalit so'zlar bilan aniqlash (90%+ savollar, bepul)
 * 2. Agar aniqlanmasa — Haiku ga qisqa so'rov (10% savollar)
 */
class AgentRouter
{
    // Agent turlari
    public const AGENT_ANALYTICS = 'analytics';
    public const AGENT_MARKETING = 'marketing';
    public const AGENT_SALES = 'sales';
    public const AGENT_CALL_CENTER = 'call_center';
    public const AGENT_ORCHESTRATOR = 'orchestrator'; // oddiy savollar uchun

    // Kalit so'zlar ro'yxati — agent turlariga moslashtirilgan
    private const KEYWORD_MAP = [
        self::AGENT_ANALYTICS => [
            // KPI va ko'rsatkichlar
            'kpi', 'ko\'rsatkich', 'hisobot', 'tahlil', 'statistika', 'raqam',
            'sotuv', 'sotuvlar', 'daromad', 'foyda', 'zarar', 'xarajat',
            'konversiya', 'conversion', 'cac', 'clv', 'ltv', 'roas', 'roi',
            'churn', 'o\'sish', 'pasayish', 'tushdi', 'oshdi', 'o\'zgardi',
            'solishtir', 'benchmark', 'o\'rtacha',
            // Savdo bosqichlari
            'funnel', 'bosqich', 'lead', 'lid', 'leadlar',
            // Vaqt oraligi
            'bugun', 'kecha', 'bu hafta', 'bu oy', 'oxirgi',
        ],
        self::AGENT_MARKETING => [
            // Kontent
            'kontent', 'post', 'content', 'yozuv', 'rasm', 'video', 'reels',
            'stories', 'story', 'karusel', 'carousel',
            // Kanal
            'instagram', 'telegram', 'facebook', 'ijtimoiy', 'social',
            'kanal', 'reach', 'engagement', 'like', 'comment', 'share',
            // Marketing
            'reklama', 'kampaniya', 'aksiya', 'chegirma', 'brend', 'brand',
            'trend', 'hashtag', 'target', 'auditoriya',
            // Raqobatchi
            'raqobatchi', 'competitor', 'rakiblar',
            // Reja
            'kontent reja', 'strategiya', 'rejalashtir',
        ],
        self::AGENT_SALES => [
            // Sotuv jarayoni
            'buyurtma', 'zakaz', 'order', 'narx', 'price', 'to\'lov',
            'mijoz', 'customer', 'client', 'xaridor',
            // E'tiroz
            'e\'tiroz', 'qimmat', 'arzon', 'byudjet',
            // Suhbat
            'suhbat', 'chat', 'xabar', 'message', 'javob',
            'operator', 'manager',
        ],
        self::AGENT_CALL_CENTER => [
            // Qo'ng'iroq
            'qo\'ng\'iroq', 'call', 'telefon', 'ovoz', 'audio',
            'yozuv', 'recording', 'transkripsiya', 'transcript',
            // Operator
            'operator holati', 'coaching', 'operator ball', 'operator baho',
            'reyting', 'leaderboard', 'jamoa',
        ],
    ];

    // Oddiy salomlashish va shunga o'xshash xabarlar — boshqaruvchi o'zi javob beradi
    // MUHIM: qisqa so'zlar (hi, hey) qo'shilmagan — boshqa so'zlar ichida mos kelishi mumkin
    private const SIMPLE_PATTERNS = [
        'salom', 'assalomu alaykum', 'hello',
        'rahmat', 'tashakkur', 'raxmat', 'thanks',
        'xayr', 'ko\'rishguncha', 'hayrlashish', 'bye', 'goodbye',
        'yordam', 'help', 'nima qila olasan', 'qanday ishlaysan',
        'nimadan boshla', 'birinchi qadam', 'ishni boshla', 'nima qilish kerak',
        'boshlash kerak', 'qanday boshlash', 'ko\'paytirish', 'o\'stirish',
    ];

    // Parallel agentlar kerak bo'ladigan murakkab savollar
    private const COMPLEX_PATTERNS = [
        'nega sotuvlar tushdi' => [self::AGENT_ANALYTICS, self::AGENT_SALES, self::AGENT_MARKETING],
        'biznes holati' => [self::AGENT_ANALYTICS, self::AGENT_MARKETING, self::AGENT_SALES, self::AGENT_CALL_CENTER],
        'raqobatchi aksiya' => [self::AGENT_ANALYTICS, self::AGENT_MARKETING],
        'to\'liq tahlil' => [self::AGENT_ANALYTICS, self::AGENT_MARKETING, self::AGENT_SALES],
    ];

    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Savolni tahlil qilib, qaysi agent(lar)ga yuborish kerakligini aniqlash
     *
     * @return array{agents: string[], method: string, confidence: string}
     */
    public function route(string $message, string $businessId): array
    {
        $normalizedMessage = mb_strtolower(trim($message));

        // 1-qadam: Oddiy salomlashish tekshirish
        if ($this->isSimpleMessage($normalizedMessage)) {
            return [
                'agents' => [self::AGENT_ORCHESTRATOR],
                'method' => 'rule',
                'confidence' => 'high',
            ];
        }

        // 2-qadam: Murakkab (parallel) savollarni tekshirish
        $complexMatch = $this->matchComplexPattern($normalizedMessage);
        if ($complexMatch) {
            return [
                'agents' => $complexMatch,
                'method' => 'rule',
                'confidence' => 'high',
            ];
        }

        // 3-qadam: Kalit so'zlar bilan aniqlash
        $keywordMatch = $this->matchByKeywords($normalizedMessage);
        if ($keywordMatch) {
            return [
                'agents' => [$keywordMatch],
                'method' => 'rule',
                'confidence' => 'medium',
            ];
        }

        // 4-qadam: Haiku dan so'rash (qoida aniqlamasa)
        return $this->routeWithAI($normalizedMessage, $businessId);
    }

    /**
     * Oddiy xabar ekanligini tekshirish
     */
    private function isSimpleMessage(string $message): bool
    {
        foreach (self::SIMPLE_PATTERNS as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        // Juda qisqa xabarlar ham oddiy hisoblanadi
        return mb_strlen($message) < 5;
    }

    /**
     * Murakkab naqshlarni tekshirish (parallel agentlar kerak)
     */
    private function matchComplexPattern(string $message): ?array
    {
        foreach (self::COMPLEX_PATTERNS as $pattern => $agents) {
            if (str_contains($message, $pattern)) {
                return $agents;
            }
        }
        return null;
    }

    /**
     * Kalit so'zlar bo'yicha agentni aniqlash
     */
    private function matchByKeywords(string $message): ?string
    {
        $scores = [];

        foreach (self::KEYWORD_MAP as $agent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scores[$agent] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // Eng ko'p mos kelgan agentni tanlash
        arsort($scores);
        return array_key_first($scores);
    }

    /**
     * Haiku orqali yo'naltirishni aniqlash (qoida ishlamasa)
     */
    private function routeWithAI(string $message, string $businessId): array
    {
        $systemPrompt = <<<'PROMPT'
Sen so'rovlarni tasniflovchisan. Foydalanuvchi savolini quyidagi toifalardan biriga yoki bir nechtasiga ajrat.
Faqat toifa nomlarini vergul bilan ajratib yoz, boshqa hech narsa yozma.

Toifalar:
- analytics (tahlil, KPI, hisobot, raqamlar, statistika)
- marketing (kontent, ijtimoiy tarmoq, reklama, raqobatchi)
- sales (sotuv, mijoz, buyurtma, narx, lead)
- call_center (qo'ng'iroq, telefon, operator, audio)
- orchestrator (salomlashish, oddiy savol, yordam)

Misol javoblar: "analytics", "marketing,sales", "analytics,marketing,sales"
PROMPT;

        try {
            $response = $this->aiService->ask(
                prompt: $message,
                systemPrompt: $systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 50,
                businessId: $businessId,
                agentType: 'router',
            );

            if ($response->success) {
                $agents = array_map('trim', explode(',', $response->content));
                // Faqat to'g'ri agent nomlarini filtrlash
                $validAgents = array_filter($agents, fn ($a) => in_array($a, [
                    self::AGENT_ANALYTICS, self::AGENT_MARKETING,
                    self::AGENT_SALES, self::AGENT_CALL_CENTER,
                    self::AGENT_ORCHESTRATOR,
                ]));

                if (! empty($validAgents)) {
                    return [
                        'agents' => array_values($validAgents),
                        'method' => 'ai',
                        'confidence' => 'medium',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('AgentRouter: AI yo\'naltirish xatosi', ['error' => $e->getMessage()]);
        }

        // Fallback: Tahlil agentiga yuborish
        return [
            'agents' => [self::AGENT_ANALYTICS],
            'method' => 'fallback',
            'confidence' => 'low',
        ];
    }
}
