<?php

namespace App\Services\Agent;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Services\Agent\Analytics\AnalyticsAgentService;
use App\Services\Agent\CallCenter\CallCenterAgentService;
use App\Services\Agent\Marketing\MarketingAgentService;
use App\Services\Agent\Memory\BusinessContextMemory;
use App\Services\Agent\Memory\ShortTermMemory;
use App\Services\Agent\Sales\SalesAgentService;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Boshqaruvchi agent (Direktor) — asosiy kirish nuqtasi.
 *
 * Vazifasi:
 * 1. Foydalanuvchi savolini qabul qilish
 * 2. Qaysi agentga yo'naltirish kerakligini aniqlash
 * 3. Agentlar javobini birlashtirish
 * 4. Suhbat tarixini boshqarish
 * 5. Xotirani boshqarish
 */
class OrchestratorService
{
    public function __construct(
        private AIService $aiService,
        private AgentRouter $router,
        private AgentResponseMerger $merger,
        private ShortTermMemory $shortTermMemory,
        private BusinessContextMemory $businessContextMemory,
        private AnalyticsAgentService $analyticsAgent,
        private MarketingAgentService $marketingAgent,
        private SalesAgentService $salesAgent,
        private CallCenterAgentService $callCenterAgent,
    ) {}

    /**
     * Foydalanuvchi xabarini qayta ishlash — asosiy kirish nuqtasi
     */
    /**
     * Foydalanuvchi xabarini qayta ishlash — asosiy kirish nuqtasi.
     *
     * @param array|null $allowedAgents Ruxsat etilgan agentlar (null = barchasi)
     * @param string $dataScope Ma'lumot doirasi: all/department/own/summary
     */
    public function handleUserMessage(
        string $message,
        string $businessId,
        string $userId,
        ?string $conversationId = null,
        ?array $allowedAgents = null,
        string $dataScope = 'all',
    ): array {
        $startTime = microtime(true);

        try {
            // 1. Suhbatni olish yoki yaratish
            $conversation = $this->getOrCreateConversation($businessId, $userId, $conversationId);

            // 2. Foydalanuvchi xabarini saqlash
            $this->saveUserMessage($conversation, $businessId, $message);

            // 3. Lahzalik xotiraga qo'shish
            $this->shortTermMemory->addMessage($businessId, $conversation->id, [
                'role' => 'user',
                'content' => $message,
                'timestamp' => now()->toISOString(),
            ]);

            // 4. Yo'naltirish — qaysi agentga yuborish kerak
            $routing = $this->router->route($message, $businessId);

            // 5. Ruxsat etilgan agentlar bilan filtrlash (DRY rol cheklov)
            if ($allowedAgents !== null) {
                $routing['agents'] = array_values(array_intersect($routing['agents'], $allowedAgents));
                // Agar hech bir agent ruxsat etilmagan bo'lsa — orchestrator javob beradi
                if (empty($routing['agents'])) {
                    $routing['agents'] = [AgentRouter::AGENT_ORCHESTRATOR];
                }
            }

            // 6. Agentlarni chaqirish
            $agentResponse = $this->dispatchToAgents(
                $message,
                $businessId,
                $conversation->id,
                $routing,
            );

            // 6. Agent javobini saqlash
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->saveAgentMessage($conversation, $businessId, $agentResponse, $routing, $processingTimeMs);

            // 7. Lahzalik xotiraga javobni qo'shish
            $this->shortTermMemory->addMessage($businessId, $conversation->id, [
                'role' => 'agent',
                'content' => $agentResponse->content,
                'timestamp' => now()->toISOString(),
            ]);

            return [
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => $agentResponse->content,
                'source' => $agentResponse->source,
                'model' => $agentResponse->model,
                'tokens' => [
                    'input' => $agentResponse->tokensInput,
                    'output' => $agentResponse->tokensOutput,
                ],
                'cost_usd' => $agentResponse->costUsd,
                'routed_to' => $routing['agents'],
                'routing_method' => $routing['method'],
                'processing_time_ms' => $processingTimeMs,
            ];

        } catch (\Exception $e) {
            Log::error('OrchestratorService: xatolik', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
                'message' => mb_substr($message, 0, 100),
            ]);

            return [
                'success' => false,
                'message' => 'Kechirasiz, texnik muammo yuz berdi. Iltimos qayta urinib ko\'ring.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Agentlarni chaqirish — yo'naltirish natijasiga qarab
     */
    private function dispatchToAgents(
        string $message,
        string $businessId,
        string $conversationId,
        array $routing,
    ): AIResponse {
        $agents = $routing['agents'];

        // Boshqaruvchi o'zi javob beradi (salomlashish, oddiy savollar)
        if (count($agents) === 1 && $agents[0] === AgentRouter::AGENT_ORCHESTRATOR) {
            return $this->handleSimpleMessage($message, $businessId, $conversationId);
        }

        // Bitta agentga yuborish
        if (count($agents) === 1) {
            return $this->callAgent($agents[0], $message, $businessId, $conversationId);
        }

        // Bir nechta agentga parallel yuborish
        $responses = [];
        foreach ($agents as $agent) {
            $responses[$agent] = $this->callAgent($agent, $message, $businessId, $conversationId);
        }

        return $this->merger->merge($responses, $businessId);
    }

    /**
     * Bitta agentni chaqirish
     */
    private function callAgent(string $agentType, string $message, string $businessId, string $conversationId): AIResponse
    {
        try {
            return match ($agentType) {
                AgentRouter::AGENT_ANALYTICS => $this->analyticsAgent->handle($message, $businessId, $conversationId),
                AgentRouter::AGENT_MARKETING => $this->marketingAgent->handle($message, $businessId, $conversationId),
                AgentRouter::AGENT_SALES => $this->salesAgent->handle($message, $businessId, $conversationId),
                AgentRouter::AGENT_CALL_CENTER => $this->callCenterAgent->handle($message, $businessId, $conversationId),
                default => $this->handleWithFallbackAI($agentType, $message, $businessId),
            };
        } catch (\Exception $e) {
            Log::warning("Agent xatosi: {$agentType}", ['error' => $e->getMessage()]);
            return AIResponse::error("Agent ({$agentType}) vaqtincha ishlamayapti");
        }
    }

    /**
     * Oddiy xabarlarga javob (salomlashish, rahmat, yordam)
     */
    private function handleSimpleMessage(string $message, string $businessId, string $conversationId): AIResponse
    {
        $normalizedMessage = mb_strtolower(trim($message));

        // Salomlashish
        if ($this->containsAny($normalizedMessage, ['salom', 'assalomu', 'hello', 'hi', 'hey'])) {
            return AIResponse::fromRule(
                "Assalomu alaykum! 👋 Men BiznesPilot AI yordamchisiman. Sizga quyidagilar bo'yicha yordam bera olaman:\n\n"
                . "📊 **Tahlil** — KPI, sotuvlar, konversiya ko'rsatkichlari\n"
                . "📱 **Marketing** — kontent, ijtimoiy tarmoq, raqobatchi tahlili\n"
                . "💰 **Sotuv** — leadlar, mijozlar, buyurtmalar\n"
                . "📞 **Qo'ng'iroqlar** — operator tahlili, coaching\n\n"
                . "Savolingizni bering!"
            );
        }

        // Rahmat
        if ($this->containsAny($normalizedMessage, ['rahmat', 'raxmat', 'tashakkur', 'thanks', 'thank'])) {
            return AIResponse::fromRule("Marhamat! Yana savol bo'lsa bemalol so'rang. 😊");
        }

        // Xayrlashish
        if ($this->containsAny($normalizedMessage, ['xayr', 'ko\'rishguncha', 'bye', 'goodbye'])) {
            return AIResponse::fromRule("Ko'rishguncha! Omad tilayman! 🙌");
        }

        // Yordam
        if ($this->containsAny($normalizedMessage, ['yordam', 'help', 'nima qila', 'qanday'])) {
            return AIResponse::fromRule(
                "Men sizga quyidagilar bo'yicha yordam bera olaman:\n\n"
                . "1. 📊 \"Bugungi sotuvlar qanday?\" — KPI va tahlillar\n"
                . "2. 📱 \"Bugun nima post qilsam?\" — kontent tavsiya\n"
                . "3. 📈 \"Nega konversiya tushdi?\" — chuqur tahlil\n"
                . "4. 💰 \"Leadlar holati\" — sotuv ma'lumotlari\n"
                . "5. 📞 \"Operator baholari\" — qo'ng'iroq tahlili\n\n"
                . "Istalgan savolni o'zbek yoki rus tilida bering!"
            );
        }

        // Agar hech narsaga mos kelmasa — umumiy javob
        return AIResponse::fromRule(
            "Tushundim. Iltimos, savolingizni aniqroq bering — sizga yordam berishga tayyorman! "
            . "Masalan: \"Bugungi sotuvlar\", \"Kontent reja\", \"Leadlar holati\"."
        );
    }

    /**
     * Hali tayyor bo'lmagan agentlar uchun fallback — Haiku dan javob
     */
    private function handleWithFallbackAI(string $agentType, string $message, string $businessId): AIResponse
    {
        $systemPrompt = "Sen BiznesPilot AI yordamchisisan. {$agentType} sohasida savolga javob ber. "
            . "O'zbek tilida, qisqa va aniq javob ber. Agar aniq ma'lumot yo'q bo'lsa, umumiy maslahat ber.";

        return $this->aiService->ask(
            prompt: $message,
            systemPrompt: $systemPrompt,
            preferredModel: 'haiku',
            maxTokens: 400,
            businessId: $businessId,
            agentType: $agentType,
        );
    }

    /**
     * Suhbat olish yoki yaratish
     */
    private function getOrCreateConversation(string $businessId, string $userId, ?string $conversationId): AgentConversation
    {
        if ($conversationId) {
            $conversation = AgentConversation::find($conversationId);
            if ($conversation && $conversation->business_id === $businessId && $conversation->status === 'active') {
                return $conversation;
            }
        }

        return AgentConversation::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'status' => 'active',
            'started_at' => now(),
            'message_count' => 0,
        ]);
    }

    /**
     * Foydalanuvchi xabarini saqlash
     */
    private function saveUserMessage(AgentConversation $conversation, string $businessId, string $message): void
    {
        AgentMessage::create([
            'conversation_id' => $conversation->id,
            'business_id' => $businessId,
            'role' => 'user',
            'content' => $message,
        ]);

        $conversation->incrementMessageCount();
    }

    /**
     * Agent javobini saqlash
     */
    private function saveAgentMessage(
        AgentConversation $conversation,
        string $businessId,
        AIResponse $response,
        array $routing,
        int $processingTimeMs,
    ): void {
        $modelUsed = match (true) {
            str_contains($response->model, 'haiku') => 'haiku',
            str_contains($response->model, 'sonnet') => 'sonnet',
            default => 'none',
        };

        AgentMessage::create([
            'conversation_id' => $conversation->id,
            'business_id' => $businessId,
            'role' => 'agent',
            'content' => $response->content,
            'agent_type' => 'orchestrator',
            'model_used' => $modelUsed,
            'tokens_input' => $response->tokensInput,
            'tokens_output' => $response->tokensOutput,
            'cost_usd' => $response->costUsd,
            'routed_to' => $routing['agents'],
            'processing_time_ms' => $processingTimeMs,
        ]);

        $conversation->incrementMessageCount();
    }

    /**
     * Matnda istalgan kalit so'z bormi tekshirish
     */
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
