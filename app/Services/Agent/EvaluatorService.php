<?php

namespace App\Services\Agent;

use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tekshiruvchi tizimi — agent javoblarini tekshiradi.
 *
 * Xavf darajalari:
 * - Past (60%): tekshiruvsiz o'tadi (KPI ko'rsatish, bazadan javob)
 * - O'rta (30%): qoidalar + Haiku (lead holati o'zgartirish, kontent tavsiya)
 * - Yuqori (10%): qoidalar + Sonnet (narx o'zgartirish, kampaniya rejasi)
 */
class EvaluatorService
{
    // Xavf darajalari
    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';

    // Past xavfli harakatlar — tekshiruvsiz o'tadi
    private const LOW_RISK_ACTIONS = [
        'kpi_display', 'report_view', 'lead_list', 'statistics',
        'greeting', 'menu', 'simple_answer', 'database_query',
    ];

    // Yuqori xavfli harakatlar — Sonnet tekshiradi
    private const HIGH_RISK_ACTIONS = [
        'price_change', 'campaign_plan', 'budget_recommendation',
        'strategic_decision', 'bulk_action', 'financial_advice',
    ];

    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Agent javobini tekshirish
     *
     * @return array{approved: bool, risk_level: string, method: string, reason: string|null, modified_content: string|null}
     */
    public function evaluate(
        string $agentType,
        string $actionType,
        string $content,
        string $businessId,
        array $context = [],
    ): array {
        // 1. Xavf darajasini aniqlash
        $riskLevel = $this->assessRisk($actionType, $content);

        // 2. Past xavf — tekshiruvsiz o'tkazish
        if ($riskLevel === self::RISK_LOW) {
            return [
                'approved' => true,
                'risk_level' => $riskLevel,
                'method' => 'skip',
                'reason' => null,
                'modified_content' => null,
            ];
        }

        // 3. Qoidaga asoslangan tekshiruv (barcha xavf darajalari uchun)
        $ruleCheck = $this->checkWithRules($content, $agentType, $context);
        if (!$ruleCheck['passed']) {
            try {
                $this->logCheck($businessId, $agentType, $actionType, $riskLevel, 'rule', 'rejected', $ruleCheck['reason']);
            } catch (\Exception $e) {
                // Log xatosi asosiy jarayonni to'xtatmasligi kerak
            }
            return [
                'approved' => false,
                'risk_level' => $riskLevel,
                'method' => 'rule',
                'reason' => $ruleCheck['reason'],
                'modified_content' => null,
            ];
        }

        // 4. O'rta xavf — Haiku tekshiradi
        if ($riskLevel === self::RISK_MEDIUM) {
            return $this->checkWithAI($agentType, $actionType, $content, $businessId, 'haiku');
        }

        // 5. Yuqori xavf — Sonnet tekshiradi
        return $this->checkWithAI($agentType, $actionType, $content, $businessId, 'sonnet');
    }

    /**
     * Xavf darajasini aniqlash
     */
    private function assessRisk(string $actionType, string $content): string
    {
        // Past xavf
        if (in_array($actionType, self::LOW_RISK_ACTIONS)) {
            return self::RISK_LOW;
        }

        // Yuqori xavf
        if (in_array($actionType, self::HIGH_RISK_ACTIONS)) {
            return self::RISK_HIGH;
        }

        // Kontent asosida xavf darajasini aniqlash
        $contentLower = mb_strtolower($content);

        // Moliyaviy maslahat — yuqori xavf
        if ($this->containsAny($contentLower, ['narxni', 'chegirma ber', 'byudjet', 'investitsiya qil'])) {
            return self::RISK_HIGH;
        }

        // Default — o'rta xavf
        return self::RISK_MEDIUM;
    }

    /**
     * Qoidaga asoslangan tekshiruvlar (bepul)
     */
    private function checkWithRules(string $content, string $agentType, array $context): array
    {
        $contentLower = mb_strtolower($content);

        // 1. Xayoliy raqam tekshiruvi — protsent yoki raqam bilan da'vo bor,
        //    lekin kontekstda tasdiqlash yo'q
        // (Bu oddiy heuristika — to'liq tekshirish AI da)

        // 2. Xavfsizlik tekshiruvi — xavfli tavsiyalar
        $dangerousAdvice = [
            'barcha pulni', 'hamma byudjetni', '100% investitsiya',
            'kafolat beraman', 'albatta ishlaydi', 'xavf yo\'q',
        ];
        foreach ($dangerousAdvice as $phrase) {
            if (str_contains($contentLower, $phrase)) {
                return ['passed' => false, 'reason' => "Xavfli maslahat aniqlandi: \"{$phrase}\""];
            }
        }

        // 3. Shaxsiy ma'lumot tekshiruvi — agent shaxsiy ma'lumot oshkor qilmasligi kerak
        $personalDataPatterns = [
            'telefon raqami', 'passport', 'bank karta', 'parol',
        ];
        foreach ($personalDataPatterns as $pattern) {
            if (str_contains($contentLower, $pattern) && $agentType !== 'sales') {
                return ['passed' => false, 'reason' => "Shaxsiy ma'lumot oshkor qilish xavfi"];
            }
        }

        return ['passed' => true, 'reason' => null];
    }

    /**
     * AI orqali tekshirish
     */
    private function checkWithAI(
        string $agentType,
        string $actionType,
        string $content,
        string $businessId,
        string $model,
    ): array {
        $systemPrompt = <<<'PROMPT'
Sen BiznesPilot tekshiruvchisisan. Agent javobini tekshir va baho ber.
JSON formatida javob ber:
{"approved": true/false, "reason": "sabab (agar rad etilsa)", "suggestions": "taklif (agar bor bo'lsa)"}

TEKSHIRISH MEZONLARI:
1. Ma'lumot to'g'riligi — xayoliy raqam yo'qmi?
2. Mantiq izchilligi — javob savolga mos kelganmi?
3. Xavfsizlik — foydalanuvchiga zarar yetkazmaydimi?
4. Professional ton — to'g'ri va hurmatli tilmi?
PROMPT;

        try {
            $response = $this->aiService->ask(
                prompt: "Agent turi: {$agentType}\nHarakat: {$actionType}\nJavob:\n{$content}",
                systemPrompt: $systemPrompt,
                preferredModel: $model,
                maxTokens: 200,
                businessId: $businessId,
                agentType: 'evaluator',
            );

            if (!$response->success) {
                // AI ishlamasa — tasdiqlaymiz (xizmat to'xtatmaslik uchun)
                return ['approved' => true, 'risk_level' => 'medium', 'method' => 'fallback', 'reason' => null, 'modified_content' => null];
            }

            // JSON javobni tahlil qilish
            $result = $this->parseJsonResponse($response->content);
            $approved = $result['approved'] ?? true;

            $this->logCheck($businessId, $agentType, $actionType, $model === 'sonnet' ? 'high' : 'medium', $model, $approved ? 'approved' : 'rejected', $result['reason'] ?? null);

            return [
                'approved' => $approved,
                'risk_level' => $model === 'sonnet' ? self::RISK_HIGH : self::RISK_MEDIUM,
                'method' => $model,
                'reason' => $result['reason'] ?? null,
                'modified_content' => null,
            ];

        } catch (\Exception $e) {
            try {
                Log::warning('EvaluatorService: AI tekshirish xatosi', ['error' => $e->getMessage()]);
            } catch (\Exception $logError) {
                // Log facade ishlamasa ham davom etamiz
            }
            // Xato bo'lsa — tasdiqlaymiz
            return ['approved' => true, 'risk_level' => 'medium', 'method' => 'error_fallback', 'reason' => null, 'modified_content' => null];
        }
    }

    /**
     * JSON javobni tahlil qilish
     */
    private function parseJsonResponse(string $content): array
    {
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $json = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        // JSON topilmasa — tasdiqlangan deb qaraymiz
        return ['approved' => true];
    }

    /**
     * Tekshiruvni qayd qilish
     */
    private function logCheck(
        string $businessId,
        string $agentType,
        string $actionType,
        string $riskLevel,
        string $method,
        string $result,
        ?string $reason,
    ): void {
        try {
            DB::table('evaluator_checks')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'business_id' => $businessId,
                'agent_type' => $agentType,
                'action_type' => $actionType,
                'risk_level' => $riskLevel,
                'check_method' => $method,
                'input_data' => json_encode([]),
                'result' => $result,
                'rejection_reason' => $reason,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Jadval hali yaratilmagan bo'lishi mumkin — keyingi bosqichda
            // Jadval mavjud emas bo'lishi mumkin — xatolikni bostirish
        }
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
