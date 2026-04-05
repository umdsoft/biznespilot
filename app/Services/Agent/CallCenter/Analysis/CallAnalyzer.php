<?php

namespace App\Services\Agent\CallCenter\Analysis;

use App\Services\Agent\CallCenter\Transcription\TranscriptSegmenter;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Qo'ng'iroq tahlil xizmati.
 * Transkripsiyani 7 bosqich bo'yicha baholaydi va maslahat beradi.
 */
class CallAnalyzer
{
    private string $systemPrompt;

    public function __construct(
        private AIService $aiService,
        private TranscriptSegmenter $segmenter,
    ) {
        $promptPath = __DIR__ . '/../Prompts/call_analysis.txt';
        $this->systemPrompt = file_exists($promptPath)
            ? file_get_contents($promptPath)
            : 'Sen sotuv coach san. Qo\'ng\'iroq tahlili ber. JSON formatda.';
    }

    /**
     * Qo'ng'iroqni to'liq tahlil qilish
     *
     * @return array{success: bool, segments: array, analysis: array, overall_score: int}
     */
    public function analyze(string $transcript, string $businessId): array
    {
        try {
            // 1. Matnni bosqichlarga ajratish (qoidaga asoslangan, bepul)
            $segments = $this->segmenter->segment($transcript);

            // 2. Bosqichlar xulosasini tayyorlash
            $segmentSummary = $this->formatSegmentSummary($segments);

            // 3. Haiku bilan tahlil
            $aiResponse = $this->aiService->ask(
                prompt: "Sotuv qo'ng'iroq transkripsiyasi:\n{$transcript}\n\nBosqichlar:\n{$segmentSummary}",
                systemPrompt: $this->systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 1200,
                businessId: $businessId,
                agentType: 'call_center',
            );

            if (!$aiResponse->success) {
                return ['success' => false, 'error' => $aiResponse->error];
            }

            // 4. JSON javobni tahlil qilish
            $analysis = $this->parseAnalysisResponse($aiResponse->content);

            return [
                'success' => true,
                'segments' => $segments,
                'analysis' => $analysis,
                'overall_score' => $analysis['overall_score'] ?? 0,
                'tokens_used' => $aiResponse->tokensInput + $aiResponse->tokensOutput,
                'cost_usd' => $aiResponse->costUsd,
            ];

        } catch (\Exception $e) {
            Log::error('CallAnalyzer: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bosqichlar xulosasini matn formatida tayyorlash
     */
    private function formatSegmentSummary(array $segments): string
    {
        $lines = [];
        foreach ($segments as $key => $seg) {
            $status = $seg['detected'] ? "({$seg['sentence_count']} gap)" : '(topilmadi)';
            $lines[] = "- {$seg['label']}: {$status}";
        }
        return implode("\n", $lines);
    }

    /**
     * AI javobidan JSON tahlilni ajratish
     */
    private function parseAnalysisResponse(string $content): array
    {
        // JSON blokini topish
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $json = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        // JSON topilmasa — matndan qo'lda tahlil
        return [
            'overall_score' => 50,
            'stage_scores' => [],
            'strengths' => [],
            'improvements' => [],
            'key_moments' => [],
            'next_steps' => [],
            'raw_analysis' => $content,
        ];
    }
}
