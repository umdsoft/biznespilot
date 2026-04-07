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

        // Salomlashish — oddiy javob
        if (count($agents) === 1 && $agents[0] === AgentRouter::AGENT_ORCHESTRATOR) {
            return $this->handleSimpleMessage($message, $businessId, $conversationId);
        }

        // Bitta agent
        if (count($agents) === 1) {
            $agentName = $this->getAgentName($agents[0]);
            $response = $this->callAgent($agents[0], $message, $businessId, $conversationId);
            // Agent nomini javobga qo'shish
            return AIResponse::fromRule("{$agentName} tahlili:\n\n{$response->content}");
        }

        // Multi-agent — har bir agent alohida javob beradi, keyin birlashtirish
        $combinedParts = [];
        $totalTokensIn = 0;
        $totalTokensOut = 0;
        $totalCost = 0.0;

        foreach ($agents as $agent) {
            $agentName = $this->getAgentName($agent);
            try {
                $response = $this->callAgent($agent, $message, $businessId, $conversationId);
                $combinedParts[] = "{$agentName} tahlili:\n{$response->content}";
                $totalTokensIn += $response->tokensInput;
                $totalTokensOut += $response->tokensOutput;
                $totalCost += $response->costUsd;
            } catch (\Exception $e) {
                $combinedParts[] = "{$agentName}: hozir javob bera olmadim.";
            }
        }

        // Direktor xulosasi
        $inspectorSummary = app(\App\Services\Agent\Knowledge\BusinessDataInspector::class)->getTextSummary($businessId);
        $combinedParts[] = "---\nYakuniy xulosa:\n" . $inspectorSummary;
        $combinedParts[] = "Qaysi biridan boshlaymiz? Yoki shu vazifalarni Vazifalar bo'limiga qo'shaymi?";

        $combinedText = implode("\n\n", $combinedParts);

        return AIResponse::fromRule($combinedText);
    }

    private function getAgentName(string $agentType): string
    {
        return match ($agentType) {
            AgentRouter::AGENT_ANALYTICS => 'Jasurbek (Tahlil)',
            AgentRouter::AGENT_MARKETING => 'Imronbek (Marketing)',
            AgentRouter::AGENT_SALES => 'Salomatxon (Sotuv)',
            AgentRouter::AGENT_CALL_CENTER => 'Nodira (Sifat nazorati)',
            default => 'Maslahatchi',
        };
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

        // Vazifa tasdiqlash — "ha", "qo'sh", "vazifaga qo'sh"
        if (mb_strlen($normalizedMessage) < 30 && $this->containsAny($normalizedMessage, ['ha', 'qo\'sh', 'vazifa', 'qo\'shib', 'tasdiq', 'bajar'])) {
            return $this->handleTaskConfirmation($businessId);
        }

        // FAQAT qisqa salomlashish
        if (mb_strlen($normalizedMessage) < 25) {
            if ($this->containsAny($normalizedMessage, ['assalomu alaykum', 'salom', 'hello'])) {
                return AIResponse::fromRule(
                    "Assalomu alaykum! Men sizning biznes yordamchingizman. Jamoamda Imronbek (marketing), Salomatxon (sotuv), Jasurbek (tahlil) va Nodira (sifat) bor. Nimani bilmoqchisiz?"
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
     * Foydalanuvchi "ha" desa — vazifalar yaratiladi
     */
    private function handleTaskConfirmation(string $businessId): AIResponse
    {
        try {
            $taskService = app(TaskProposalService::class);
            $tasks = $taskService->extractTasksFromInspection($businessId);

            if (empty($tasks)) {
                return AIResponse::fromRule("Hozircha qo'shimcha vazifa yo'q — ma'lumotlaringiz yetarli darajada to'ldirilgan. Boshqa savol bo'lsa yozing.");
            }

            $userId = auth()->id();
            $created = $taskService->createTasks($tasks, $businessId, $userId);

            $taskList = collect($tasks)->map(fn($t, $i) => ($i + 1) . ". {$t['title']}")->implode("\n");

            return AIResponse::fromRule(
                "{$created} ta vazifa Vazifalar bo'limiga qo'shildi:\n\n{$taskList}\n\n"
                . "Bosh sahifa > Vazifalar bo'limidan to'liq ko'rishingiz mumkin. Har bir vazifaga sana belgilangan."
            );
        } catch (\Exception $e) {
            Log::warning('Task confirmation xato', ['error' => $e->getMessage()]);
            return AIResponse::fromRule("Vazifalarni qo'shishda xatolik yuz berdi. Iltimos qayta urinib ko'ring.");
        }
    }

    /**
     * Hali tayyor bo'lmagan agentlar uchun fallback — Haiku dan javob
     */
    private function handleWithFallbackAI(string $agentType, string $message, string $businessId): AIResponse
    {
        // HAQIQIY biznes ma'lumotlari — DB dan
        $businessContext = $this->businessDataService->getContextForAI($businessId, $agentType);

        $platformContext = \App\Services\Agent\Knowledge\PlatformKnowledge::getSystemContext();

        $systemPrompt = $platformContext . "\n\nBIZNES MA'LUMOTLARI:\n" . $businessContext;

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
