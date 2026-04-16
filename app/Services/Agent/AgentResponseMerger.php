<?php

namespace App\Services\Agent;

use App\Services\AI\AIResponse;
use App\Services\AI\AIService;

/**
 * Agent javoblarini birlashtiruvchi.
 * Bir nechta agent javob bersa — ularni yagona matnda birlashtiradi.
 */
class AgentResponseMerger
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Bir nechta agent javobini birlashtirish
     *
     * @param array<string, AIResponse> $responses Agent turi => javob
     */
    public function merge(array $responses, string $businessId): AIResponse
    {
        // Agar bitta agent bo'lsa — to'g'ridan-to'g'ri qaytarish
        if (count($responses) === 1) {
            return reset($responses);
        }

        // Muvaffaqiyatli javoblarni filtrlash
        $successfulResponses = array_filter($responses, fn (AIResponse $r) => $r->success);

        if (empty($successfulResponses)) {
            return AIResponse::error('Barcha agentlar xato qaytardi');
        }

        if (count($successfulResponses) === 1) {
            return reset($successfulResponses);
        }

        // Bir nechta javobni birlashtirish
        return $this->mergeMultiple($successfulResponses, $businessId);
    }

    /**
     * Bir nechta muvaffaqiyatli javobni birlashtirish
     *
     * @param array<string, AIResponse> $responses
     */
    private function mergeMultiple(array $responses, string $businessId): AIResponse
    {
        // Agentlar javoblarini matn sifatida yig'ish
        $parts = [];
        $totalInput = 0;
        $totalOutput = 0;
        $totalCost = 0.0;
        $totalTime = 0;

        foreach ($responses as $agentType => $response) {
            $label = $this->getAgentLabel($agentType);
            $parts[] = "**{$label}:**\n{$response->content}";
            $totalInput += $response->tokensInput;
            $totalOutput += $response->tokensOutput;
            $totalCost += $response->costUsd;
            $totalTime += $response->processingTimeMs;
        }

        // 2+ agent — Haiku bilan jamoaviy xulosa chiqarish
        $rawText = implode("\n\n", $parts);

        $summaryResponse = $this->aiService->ask(
            prompt: "Quyidagi agentlar tahlillarini chuqur o'qi. Cross-insights top — nima umumiy, nima farq, qaysi muammolar bog'liq.\n\n"
                . "Tahlillar:\n{$rawText}\n\n"
                . "Javobing: 3-4 jumla, eng muhim KROSS-INSIGHT (agentlar birlashgan xulosa). Aniq raqamlar ishlat.",
            systemPrompt: "Sen BiznesPilot AI koordinatorisan. Bir nechta agent tahlilini O'QIB, ular orasidagi BOG'LIQLIKni topasan. "
                . "Masalan: \"Marketing ROAS past (1.2x), sotuv konversiya past (9%), bu ikkisi bog'liq — ideal mijoz aniqlanmagan\". "
                . "O'zbek tilida. Qisqa va aniq. HECH QACHON shunchaki takrorlama.",
            preferredModel: 'haiku',
            maxTokens: 800,
            businessId: $businessId,
            agentType: 'merger',
        );

        if ($summaryResponse->success) {
            return new AIResponse(
                content: $summaryResponse->content,
                model: $summaryResponse->model,
                tokensInput: $totalInput + $summaryResponse->tokensInput,
                tokensOutput: $totalOutput + $summaryResponse->tokensOutput,
                costUsd: $totalCost + $summaryResponse->costUsd,
                source: 'merged_ai',
                processingTimeMs: $totalTime + $summaryResponse->processingTimeMs,
            );
        }

        // AI birlashtirish ishlamasa — oddiy birlashtirish
        return new AIResponse(
            content: implode("\n\n---\n\n", $parts),
            model: 'merged',
            tokensInput: $totalInput,
            tokensOutput: $totalOutput,
            costUsd: $totalCost,
            source: 'merged',
            processingTimeMs: $totalTime,
        );
    }

    /**
     * Agent turi uchun o'zbek tilidagi nom
     */
    private function getAgentLabel(string $agentType): string
    {
        return match ($agentType) {
            'analytics' => 'Tahlil',
            'marketing' => 'Marketing',
            'sales' => 'Sotuv',
            'call_center' => 'Qo\'ng\'iroq markazi',
            default => ucfirst($agentType),
        };
    }
}
