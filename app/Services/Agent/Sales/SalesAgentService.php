<?php

namespace App\Services\Agent\Sales;

use App\Services\Agent\Sales\ChatHandler\MessageClassifier;
use App\Services\Agent\Sales\ChatHandler\RuleBasedResponder;
use App\Services\Agent\Sales\LeadScoring\RealTimeScorer;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sotuv agenti — ikki xil ish bajaradi:
 *
 * 1. Agent panel (dashboard) dan kelgan savollar uchun — sotuv tahlili
 * 2. Chatbot kanal (Telegram/Instagram) dan kelgan xabarlar uchun — suhbatchi
 *
 * Gibrid mantiq:
 * - Salomlashish, menyu, narx → qoidaga asoslangan (bepul)
 * - E'tiroz → sohaviy bilim bazasidan (bepul/Haiku)
 * - Murakkab savol → Haiku
 * - Strategik maslahat → Sonnet
 */
class SalesAgentService
{
    private string $objectionPrompt;

    public function __construct(
        private AIService $aiService,
        private MessageClassifier $classifier,
        private RuleBasedResponder $responder,
        private RealTimeScorer $scorer,
    ) {
        $promptPath = __DIR__ . '/Prompts/objection_response.txt';
        $this->objectionPrompt = file_exists($promptPath) ? file_get_contents($promptPath) : '';
    }

    /**
     * Agent panel dan kelgan sotuv savollari (dashboard)
     */
    public function handle(string $message, string $businessId, string $conversationId): AIResponse
    {
        $normalizedMessage = mb_strtolower(trim($message));

        try {
            // Sotuv statistikasi so'rovi
            if ($this->containsAny($normalizedMessage, ['lead', 'lid', 'leadlar', 'mijoz', 'pipeline', 'funnel'])) {
                return $this->getSalesData($normalizedMessage, $businessId);
            }

            // E'tiroz maslahat so'rovi
            if ($this->containsAny($normalizedMessage, ['e\'tiroz', 'qimmat', 'javob ber'])) {
                return $this->getObjectionAdvice($message, $businessId);
            }

            // Umumiy sotuv maslahat
            return $this->getGeneralSalesAdvice($message, $businessId);

        } catch (\Exception $e) {
            Log::error('SalesAgent: xatolik', ['error' => $e->getMessage()]);
            return AIResponse::error($e->getMessage());
        }
    }

    /**
     * Chatbot kanal dan kelgan xabar (Telegram/Instagram/Facebook)
     * Bu metod mavjud ChatbotService dan chaqiriladi
     */
    public function handleChatMessage(
        string $message,
        string $businessId,
        ?string $leadId = null,
        ?string $customerName = null,
        int $currentLeadScore = 0,
    ): array {
        // 1. Xabar turini aniqlash (qoidaga asoslangan, bepul)
        $classification = $this->classifier->classify($message);
        $messageType = $classification['type'];

        // 2. Lead ballini yangilash
        $scoring = $this->scorer->scoreMessage(
            $businessId, $leadId, $messageType,
            $classification['objection_type'], $currentLeadScore,
        );

        // 3. Javob tayyorlash
        $response = match ($messageType) {
            MessageClassifier::TYPE_GREETING => $this->responder->greetingResponse($businessId, $customerName),
            MessageClassifier::TYPE_MENU => $this->responder->menuResponse($businessId),
            MessageClassifier::TYPE_PRICE => $this->responder->priceResponse($businessId),
            MessageClassifier::TYPE_ORDER => $this->responder->orderStartResponse(),
            MessageClassifier::TYPE_OPERATOR => $this->responder->operatorHandoffResponse(),
            MessageClassifier::TYPE_OBJECTION => $this->handleObjection($message, $businessId, $classification['objection_type']),
            default => $this->handleComplexChatMessage($message, $businessId),
        };

        // Javob source ni aniqlash
        $source = in_array($messageType, [
            MessageClassifier::TYPE_GREETING, MessageClassifier::TYPE_MENU,
            MessageClassifier::TYPE_PRICE, MessageClassifier::TYPE_ORDER,
            MessageClassifier::TYPE_OPERATOR,
        ]) ? 'rule' : 'ai';

        return [
            'response' => is_string($response) ? $response : $response->content,
            'message_type' => $messageType,
            'objection_type' => $classification['objection_type'],
            'lead_score' => $scoring['new_score'],
            'score_change' => $scoring['change'],
            'score_action' => $scoring['action'],
            'source' => $source,
            'needs_operator' => $messageType === MessageClassifier::TYPE_OPERATOR || $scoring['action'] === 'notify_operator_hot',
        ];
    }

    /**
     * E'tirozni bartaraf qilish — avval bazadan, keyin AI
     */
    private function handleObjection(string $message, string $businessId, ?string $objectionType): string
    {
        // Avval sohaviy bilim bazasidan
        $industry = $this->getBusinessIndustry($businessId);
        if ($industry && $objectionType) {
            $bestResponse = DB::table('industry_objection_responses')
                ->where('industry', $industry)
                ->where('objection_type', $objectionType)
                ->orderByDesc('success_rate')
                ->first(['response_text', 'success_rate']);

            if ($bestResponse && $bestResponse->success_rate > 50) {
                return $bestResponse->response_text;
            }
        }

        // Bazada yo'q — Haiku dan so'rash
        $response = $this->aiService->ask(
            prompt: "Mijoz xabari: \"{$message}\"\nE'tiroz turi: {$objectionType}",
            systemPrompt: $this->objectionPrompt ?: 'Sen sotuv yordamchisisan. E\'tirozga javob ber. O\'zbek tilida, qisqa.',
            preferredModel: 'haiku',
            maxTokens: 300,
            businessId: $businessId,
            agentType: 'sales',
        );

        return $response->content;
    }

    /**
     * Murakkab chat xabarga AI javob
     */
    private function handleComplexChatMessage(string $message, string $businessId): string
    {
        $response = $this->aiService->ask(
            prompt: $message,
            systemPrompt: "Sen {$this->getBusinessName($businessId)} kompaniyasining do'stona yordamchisisan. Mijozga qisqa va aniq javob ber. O'zbek tilida. Agar javob bera olmasang — operatorga yo'naltir.",
            preferredModel: 'haiku',
            maxTokens: 400,
            businessId: $businessId,
            agentType: 'sales',
        );

        return $response->content;
    }

    /**
     * Sotuv statistikasi (bazadan, bepul)
     */
    private function getSalesData(string $message, string $businessId): AIResponse
    {
        try {
            $today = now()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();

            // Leadlar statistikasi
            $leads = DB::table('leads')
                ->where('business_id', $businessId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_month,
                    SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today
                ", [$monthStart, $today])
                ->first();

            // Bosqichlar bo'yicha
            $byStatus = DB::table('leads')
                ->where('business_id', $businessId)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $response = "📊 **Sotuv holati:**\n\n"
                . "👥 Jami leadlar: **{$leads->total}**\n"
                . "📅 Bu oy: **{$leads->this_month}**\n"
                . "🕐 Bugun: **{$leads->today}**\n\n"
                . "📈 **Bosqichlar bo'yicha:**\n";

            $statusLabels = [
                'new' => '🆕 Yangi',
                'contacted' => '📞 Bog\'lanilgan',
                'qualified' => '✅ Malakali',
                'proposal' => '📋 Taklif',
                'negotiation' => '🤝 Muzokara',
                'won' => '🏆 Yutilgan',
                'lost' => '❌ Yo\'qotilgan',
            ];

            foreach ($statusLabels as $status => $label) {
                $count = $byStatus[$status] ?? 0;
                $response .= "{$label}: **{$count}**\n";
            }

            return AIResponse::fromDatabase($response);
        } catch (\Exception $e) {
            return AIResponse::error($e->getMessage());
        }
    }

    /**
     * E'tiroz bo'yicha maslahat (dashboard uchun)
     */
    private function getObjectionAdvice(string $message, string $businessId): AIResponse
    {
        return $this->aiService->ask(
            prompt: $message,
            systemPrompt: "Sen sotuv coaching ekspertisan. Sotuv xodimlariga e'tirozlarni bartaraf qilish bo'yicha maslahat ber. O'zbek tilida, amaliy misollar bilan.",
            preferredModel: 'haiku',
            maxTokens: 600,
            businessId: $businessId,
            agentType: 'sales',
        );
    }

    /**
     * Umumiy sotuv maslahat
     */
    private function getGeneralSalesAdvice(string $message, string $businessId): AIResponse
    {
        return $this->aiService->ask(
            prompt: $message,
            systemPrompt: "Sen BiznesPilot sotuv agentisan. Sotuv bo'yicha maslahat ber. O'zbek tilida, qisqa va amaliy.",
            preferredModel: 'haiku',
            maxTokens: 600,
            businessId: $businessId,
            agentType: 'sales',
        );
    }

    private function getBusinessIndustry(string $businessId): ?string
    {
        return DB::table('businesses')->where('id', $businessId)->value('industry_code');
    }

    private function getBusinessName(string $businessId): string
    {
        return DB::table('businesses')->where('id', $businessId)->value('name') ?? 'Biznes';
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
