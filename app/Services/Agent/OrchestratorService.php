<?php

namespace App\Services\Agent;

use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Services\Agent\Analytics\AnalyticsAgentService;
use App\Services\Agent\CallCenter\CallCenterAgentService;
use App\Services\Agent\Deliverables\DeliverableGenerator;
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
        private DeliverableGenerator $deliverableGenerator,
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

        // Multi-agent javob — EXECUTOR paradigma
        // Time budget: nginx send_timeout=60s, fastcgi=300s. Foydalanuvchi 60s'gacha kutadi.
        // Har bir Haiku call ~5-12s, max 22s+1s+22s=45s. 2 agent + Merger + Director ko'p
        // bo'lsa nginx 504 chiqaradi. Shuning uchun elapsed time bo'yicha shartli skip qilamiz.
        $totalStartTime = microtime(true);
        $TIME_BUDGET_TOTAL_S = 50;       // Jami response budjeti
        $TIME_BUDGET_MERGER_S = 30;      // Merger chaqirilishidan oldin shu vaqt o'tgan bo'lmasligi kerak
        $TIME_BUDGET_DIRECTOR_S = 35;    // Director chaqirilishidan oldin shu vaqt o'tgan bo'lmasligi kerak

        set_time_limit(180);
        ini_set('max_execution_time', 180);

        $inspector = app(\App\Services\Agent\Knowledge\BusinessDataInspector::class);
        $data = $inspector->inspect($businessId);
        $completeness = $data['completeness'] ?? 0;
        $s = $data['sales'] ?? [];

        $parts = [];
        $parts[] = "🎯 Jamoam savolingizni ko'rib chiqdi.\n";
        $parts[] = "📊 **Biznesingiz:** to'liqlik **{$completeness}%**, lidlar **" . ($s['leads_total'] ?? 0) . " ta**, yutilgan **" . ($s['leads_won'] ?? 0) . " ta**";
        $parts[] = "";

        // Eng ko'pi 2 ta agent chaqirish (timeout oldini olish)
        $maxAgents = min(count($agents), 2);
        $calledAgents = array_slice($agents, 0, $maxAgents);

        // Agent javoblarini yig'ish — Merger uchun
        $agentResponses = [];

        foreach ($calledAgents as $agent) {
            $agentName = $this->getAgentName($agent);
            $agentEmoji = $this->getAgentEmoji($agent);

            try {
                $response = $this->callAgent($agent, $message, $businessId, $conversationId);
                $agentResponses[$agent] = $response;
                $parts[] = "---";
                $parts[] = "{$agentEmoji} **{$agentName}:**\n\n{$response->content}";
            } catch (\Exception $e) {
                $parts[] = "---";
                $parts[] = "{$agentEmoji} **{$agentName}:** Hozir tahlil qilib bo'lmadi.";
                Log::warning("Multi-agent xato: {$agent}", ['error' => $e->getMessage()]);
            }
        }

        // Cross-agent xulosa — Merger orqali (agentlar orasidagi bog'liqlik)
        // Time budget: agar agentlar Merger budjetidan ko'p vaqt olgan bo'lsa skip qilamiz
        $elapsedSec = microtime(true) - $totalStartTime;
        if (count($agentResponses) >= 2 && $elapsedSec < $TIME_BUDGET_MERGER_S) {
            try {
                $merged = $this->merger->merge($agentResponses, $businessId);
                if ($merged->success && $merged->source === 'merged_ai') {
                    $parts[] = "---";
                    $parts[] = "🔗 **Jamoaviy xulosa:**\n\n{$merged->content}";
                }
            } catch (\Throwable $e) {
                Log::warning('Merger xato', ['error' => $e->getMessage()]);
            }
        } elseif (count($agentResponses) >= 2) {
            Log::info('Merger skipped — time budget exceeded', ['elapsed_s' => round($elapsedSec, 2)]);
        }

        // Director xulosa — Sonnet bilan chuqur strategik tahlil
        // Time budget: agar Director budjetidan ko'p vaqt o'tgan bo'lsa skip qilamiz
        // Sababi: Sonnet sekinroq (10-25s) + nginx send_timeout=60s → 504 xavfi
        $elapsedSec = microtime(true) - $totalStartTime;
        if ($elapsedSec < $TIME_BUDGET_DIRECTOR_S) {
            try {
                $agentSummary = implode("\n", array_slice($parts, 3));
                $directorResponse = $this->aiService->ask(
                    prompt: "Agent tavsiyalari:\n{$agentSummary}\n\n"
                        . "Chuqur strategik tahlil qil. ENG MUHIM 3 ta harakatni tanla. "
                        . "Har biri uchun: NIMA va NEGA aniq yoz. Aniq raqamlar ishlat (lidlar, foizlar, valyuta).",
                    systemPrompt: "Sen Umidbek — BiznesPilot AI bosh direktorisan. 15 yillik tajribali biznes strategisan.\n"
                        . "O'zbek tilida, professional. Sen jamoang ISHINI ko'rib chiqib, STRATEGIK qaror qabul qilasan.\n"
                        . "'Qiling' DEMA. 'Men tayyorladim', 'Biz bajardik' de. Faqat TASDIQLASH so'ra.\n"
                        . "Har harakatda: muammo, yechim, kutilgan natija (raqam bilan).\n"
                        . "Format: 🔥 3 ta strategik harakat (har biri 3-4 jumla). Oxirida: ❓ Tasdiqlaymi?",
                    preferredModel: 'sonnet',
                    maxTokens: 1500,
                    businessId: $businessId,
                    agentType: 'director',
                );
                $parts[] = "---";
                $parts[] = "✅ **Umidbek (Bosh direktor):**\n\n{$directorResponse->content}";
            } catch (\Throwable $e) {
                $parts[] = "---";
                $parts[] = "✅ **Umidbek (Bosh direktor):**\n\nYuqoridagi ishlar tayyor. **Tasdiqlaymi?**";
                Log::warning('Director xato', ['error' => $e->getMessage()]);
            }
        } else {
            // Time budget tugadi — Director'siz tayyor javob beramiz (504 oldini olish)
            $parts[] = "---";
            $parts[] = "✅ **Umidbek (Bosh direktor):**\n\nYuqoridagi tahlillar asosida harakat qiling. **Tasdiqlaymi?**";
            Log::info('Director skipped — time budget exceeded', ['elapsed_s' => round($elapsedSec, 2)]);
        }

        return AIResponse::fromRule(implode("\n", $parts));
    }

    private function getAgentName(string $agentType): string
    {
        return match ($agentType) {
            AgentRouter::AGENT_ANALYTICS => 'Jasurbek — Tahlil bo\'limi boshlig\'i',
            AgentRouter::AGENT_MARKETING => 'Imronbek — Marketing bo\'limi boshlig\'i',
            AgentRouter::AGENT_SALES => 'Salomatxon — Sotuv bo\'limi boshlig\'i',
            AgentRouter::AGENT_CALL_CENTER => 'Nodira — Sifat nazorati boshlig\'i',
            default => 'Maslahatchi',
        };
    }

    private function getAgentKey(string $agentType): string
    {
        return match ($agentType) {
            AgentRouter::AGENT_ANALYTICS => 'jasurbek',
            AgentRouter::AGENT_MARKETING => 'imronbek',
            AgentRouter::AGENT_SALES => 'salomatxon',
            AgentRouter::AGENT_CALL_CENTER => 'nodira',
            default => 'unknown',
        };
    }

    private function getAgentEmoji(string $agentType): string
    {
        return match ($agentType) {
            AgentRouter::AGENT_ANALYTICS => '📊',
            AgentRouter::AGENT_MARKETING => '📢',
            AgentRouter::AGENT_SALES => '💼',
            AgentRouter::AGENT_CALL_CENTER => '🎓',
            default => '💡',
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
     * Foydalanuvchi "ha" / "tasdiq" desa — deliverable yaratiladi va vazifalar qo'shiladi
     */
    private function handleTaskConfirmation(string $businessId): AIResponse
    {
        try {
            $results = [];

            // 1. Avval oddiy vazifalar (eski logika)
            $taskService = app(TaskProposalService::class);
            $tasks = $taskService->extractTasksFromInspection($businessId);
            if (!empty($tasks)) {
                $userId = auth()->id();
                $created = $taskService->createTasks($tasks, $businessId, $userId);
                $results[] = "✅ **{$created} ta vazifa** Vazifalar bo'limiga qo'shildi";
            }

            // 2. Deliverable'lar yaratish (kontent reja, lid javoblar, KPI hisobot)
            $deliverableResults = [];

            // Imronbek — kontent reja (haiku bilan tez)
            try {
                $contentPlan = $this->deliverableGenerator->generateContentPlan($businessId);
                if ($contentPlan) {
                    $deliverableResults[] = "📢 **Imronbek:** {$contentPlan['title']}";
                }
            } catch (\Exception $e) {
                Log::warning('Deliverable kontent xato', ['error' => $e->getMessage()]);
            }

            // Salomatxon — lid javoblar
            try {
                $leadResponses = $this->deliverableGenerator->generateLeadResponses($businessId);
                if ($leadResponses) {
                    $deliverableResults[] = "💼 **Salomatxon:** {$leadResponses['title']}";
                }
            } catch (\Exception $e) {
                Log::warning('Deliverable lid xato', ['error' => $e->getMessage()]);
            }

            // Jasurbek — KPI hisobot (bazadan — AI kerak emas)
            try {
                $kpiReport = $this->deliverableGenerator->generateKPIReport($businessId);
                if ($kpiReport) {
                    $deliverableResults[] = "📊 **Jasurbek:** {$kpiReport['title']}";
                }
            } catch (\Exception $e) {
                Log::warning('Deliverable KPI xato', ['error' => $e->getMessage()]);
            }

            // Nodira — sifat hisobot (bazadan — AI kerak emas)
            try {
                $qualityReport = $this->deliverableGenerator->generateQualityReport($businessId);
                if ($qualityReport) {
                    $deliverableResults[] = "🎓 **Nodira:** {$qualityReport['title']}";
                }
            } catch (\Exception $e) {
                Log::warning('Deliverable sifat xato', ['error' => $e->getMessage()]);
            }

            if (empty($results) && empty($deliverableResults)) {
                return AIResponse::fromRule("Hozircha qo'shimcha vazifa yo'q. Boshqa savol bo'lsa yozing.");
            }

            $response = "🎯 **Tasdiqlandi! Jamoam ishga kirishdi:**\n\n";
            if (!empty($results)) {
                $response .= implode("\n", $results) . "\n\n";
            }
            if (!empty($deliverableResults)) {
                $response .= "📦 **Tayyor mahsulotlar:**\n" . implode("\n", $deliverableResults) . "\n\n";
                $response .= "Bosh sahifa > Vazifalar va Marketing bo'limlarida ko'rishingiz mumkin.\n";
                $response .= "Tayyor mahsulotlarni tasdiqlash uchun **\"Hammasini tasdiqlash\"** tugmasini bosing.";
            }

            return AIResponse::fromRule($response);
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
        $businessContext = $this->businessDataService->getContextForAI($businessId, $agentType);

        $systemPrompt = "Sen BiznesPilot AI yordamchisisan. O'zbek tilida yoz.\n\n"
            . "QATTIQ QOIDALAR:\n"
            . "1. 'Shuni qiling' AYTMA — 'Men tayyorladim' de\n"
            . "2. Har bir jumla QISQA — 1-2 qator\n"
            . "3. Tashqi dastur (CRM, Excel, Canva) TAVSIYA QILMA\n"
            . "4. 3 ta tayyor ish natijasi taqdim et\n"
            . "5. Oxirida 'Tasdiqlaymi?' de\n"
            . "6. Markdown ishlat: **qalin**, - ro'yxat\n\n"
            . "BIZNES MA'LUMOTLARI:\n" . $businessContext;

        try {
            return $this->aiService->ask(
                prompt: $message,
                systemPrompt: $systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 1500,
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

        // Javob sifatini avtomatik baholash (Ruflo uslubi)
        try {
            $scorer = app(\App\Services\Agent\Context\ResponseQualityScorer::class);
            $quality = $scorer->score($response->content);
            Log::info('Agent javob sifati', [
                'conversation_id' => $conversation->id,
                'grade' => $quality['grade'],
                'score' => $quality['score'],
                'issues_count' => count($quality['issues']),
                'issues' => $quality['issues'],
                'model' => $modelUsed,
            ]);
        } catch (\Exception $e) {
            Log::warning('Quality score xato', ['error' => $e->getMessage()]);
        }

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
