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

        // Oddiy birlashtirish — agentlar soniga qarab
        if (count($responses) <= 2) {
            // 2 ta agentning javobini shunchaki birlashtirish
            $mergedContent = implode("\n\n---\n\n", $parts);

            return new AIResponse(
                content: $mergedContent,
                model: 'merged',
                tokensInput: $totalInput,
                tokensOutput: $totalOutput,
                costUsd: $totalCost,
                source: 'merged',
                processingTimeMs: $totalTime,
            );
        }

        // 3+ agent bo'lsa — Haiku bilan qisqartirib birlashtirish
        $rawText = implode("\n\n", $parts);

        $summaryResponse = $this->aiService->ask(
            prompt: "Quyidagi tahlillarni bitta izchil javobga birlashtir. Takrorlanishlarni olib tashla, eng muhim ma'lumotlarni birinchi o'ringa qo'y:\n\n{$rawText}",
            systemPrompt: 'Sen BiznesPilot AI yordamchisisan. Bir nechta agent tahlilini foydalanuvchi uchun qulay formatda birlashtir. O\'zbek tilida yoz. Qisqa va aniq bo\'l.',
            preferredModel: 'haiku',
            maxTokens: 1500,
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
