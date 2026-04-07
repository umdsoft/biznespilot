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
        private BusinessDataService $businessDataService,
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
     * Oddiy xabarlarga javob — FAQAT salomlashish, rahmat, xayrlashish.
     * Qolgan HAMMA narsa AI ga yuboriladi.
     */
    private function handleSimpleMessage(string $message, string $businessId, string $conversationId): AIResponse
    {
        $normalizedMessage = mb_strtolower(trim($message));

        // FAQAT qisqa salomlashish (30 belgidan kam va aniq salomlashish so'zi)
        if (mb_strlen($normalizedMessage) < 25) {
            if ($this->containsAny($normalizedMessage, ['assalomu alaykum', 'salom', 'hello'])) {
                return AIResponse::fromRule(
                    "Assalomu alaykum! Men sizning biznes yordamchingizman. Nimani bilmoqchisiz?"
                );
            }
            if ($this->containsAny($normalizedMessage, ['rahmat', 'raxmat', 'tashakkur', 'thanks'])) {
                return AIResponse::fromRule("Marhamat! Yana savol bo'lsa yozing.");
            }
            if ($this->containsAny($normalizedMessage, ['xayr', 'ko\'rishguncha', 'bye'])) {
                return AIResponse::fromRule("Omad! Kerak bo'lganda yozing.");
            }
        }

        // Qolgan HAMMA narsa — AI ga yuboriladi
        return $this->handleWithFallbackAI('orchestrator', $message, $businessId);
    }

    /**
     * Hali tayyor bo'lmagan agentlar uchun fallback — Haiku dan javob
     */
    private function handleWithFallbackAI(string $agentType, string $message, string $businessId): AIResponse
    {
        // HAQIQIY biznes ma'lumotlari — DB dan
        $businessContext = $this->businessDataService->getContextForAI($businessId, $agentType);

        $systemPrompt = "Sen BiznesPilot platformasining ichki biznes maslahatchisisan.\n\n"
            . "PLATFORMANI TUSHUN: BiznesPilot — CRM, marketing, HR, moliya, tahlil hammasini o'z ichiga olgan YAGONA platforma. "
            . "Foydalanuvchi ALLAQACHON platformada. Unga boshqa hech qanday tizim kerak EMAS.\n\n"
            . "QATTIQ TAQIQLANGAN (HECH QACHON AYTMA): CRM o'rnating, Excel, Google Sheets, Canva, Bitrix, AmoCRM, HubSpot, "
            . "Trello, tashqi bot, tashqi xizmat — BU SO'ZLARNI HECH QACHON ISHLATMA.\n\n"
            . "BUNING O'RNIGA platformaning shu bo'limlariga yo'naltir:\n"
            . "- Lidlar bo'limi — mijozlar kiritish va boshqarish\n"
            . "- Marketing bo'limi — kontent reja, kanal boshqaruvi\n"
            . "- KPI Reja — maqsadlar va ko'rsatkichlar\n"
            . "- Integratsiyalar — Instagram, Telegram, Facebook ulash\n"
            . "- HR va Xodimlar — jamoa boshqaruvi\n"
            . "- Sozlamalar — biznes profili\n\n"
            . "JAVOB USLUBI: Tajribali maslahatchi kabi gapir. O'zbek tilida, 6-10 jumla. "
            . "3 ta aniq qadam ber. Biznes sohasiga mos strategiya ber. "
            . "Oxirida 'Qaysi biridan boshlaymiz?' de.\n"
            . "FORMAT: Oddiy tekst. ** yulduzcha, ## belgisi, emoji ISHLATMA.\n\n"
            . "BIZNES MA'LUMOTLARI:\n" . $businessContext;

        try {
            return $this->aiService->ask(
                prompt: $message,
                systemPrompt: $systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 600,
                businessId: $businessId,
                agentType: $agentType,
            );
        } catch (\Exception $e) {
            return AIResponse::fromRule(
                "Hozir AI xizmatiga ulanib bo'lmadi. Iltimos savolingizni aniqroq yozing yoki keyinroq urinib ko'ring.\n\n"
                . "Masalan: \"Bugungi sotuvlar\", \"Kontent reja\", \"Leadlar holati\""
            );
        }
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
