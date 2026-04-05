<?php

namespace App\Services\Agent\CallCenter;

use App\Services\Agent\CallCenter\Analysis\CallAnalyzer;
use App\Services\Agent\CallCenter\Performance\OperatorScorer;
use App\Services\Agent\CallCenter\Transcription\GroqWhisperSTT;
use App\Services\AI\AIResponse;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Qo'ng'iroq markazi agenti.
 *
 * Vazifalari:
 * - Qo'ng'iroq transkripsiya va tahlili
 * - Operator baholash va coaching
 * - Jamoa reytingi
 *
 * Gibrid mantiq:
 * - Operator statistikasi → bazadan (bepul)
 * - Reyting → bazadan (bepul)
 * - Qo'ng'iroq tahlili → Groq Whisper + Haiku
 * - Coaching maslahat → Haiku/Sonnet
 */
class CallCenterAgentService
{
    public function __construct(
        private AIService $aiService,
        private GroqWhisperSTT $sttService,
        private CallAnalyzer $analyzer,
        private OperatorScorer $scorer,
    ) {}

    /**
     * Dashboard dan kelgan savollarni qayta ishlash
     */
    public function handle(string $message, string $businessId, string $conversationId): AIResponse
    {
        $normalized = mb_strtolower(trim($message));

        try {
            // Reyting so'rovi — bazadan (bepul)
            if ($this->containsAny($normalized, ['reyting', 'leaderboard', 'jamoa', 'eng yaxshi operator'])) {
                return $this->getLeaderboardResponse($businessId);
            }

            // Operator statistikasi — bazadan (bepul)
            if ($this->containsAny($normalized, ['operator holati', 'operator statistika', 'operator ball'])) {
                return $this->getOperatorStatsResponse($businessId, $normalized);
            }

            // Qo'ng'iroq tahlili haqida umumiy savol
            if ($this->containsAny($normalized, ['qo\'ng\'iroq', 'call', 'tahlil', 'coaching'])) {
                return $this->getCallInsights($message, $businessId);
            }

            // Umumiy savol — AI bilan
            return $this->getGeneralAdvice($message, $businessId);

        } catch (\Exception $e) {
            Log::error('CallCenterAgent: xatolik', ['error' => $e->getMessage()]);
            return AIResponse::error($e->getMessage());
        }
    }

    /**
     * Audio faylni tahlil qilish (webhook yoki API dan)
     */
    public function analyzeCall(string $audioPath, string $businessId, ?string $operatorId = null): array
    {
        try {
            // 1. Ovozdan matnga (Groq Whisper)
            $transcription = $this->sttService->transcribe($audioPath);

            if (!$transcription['success']) {
                return ['success' => false, 'error' => 'Transkripsiya xatosi: ' . ($transcription['error'] ?? '')];
            }

            // 2. Matnni tahlil qilish (Haiku)
            $analysis = $this->analyzer->analyze($transcription['text'], $businessId);

            return [
                'success' => true,
                'transcript' => $transcription['text'],
                'duration_seconds' => (int) ($transcription['duration'] ?? 0),
                'language' => $transcription['language'] ?? 'uz',
                'analysis' => $analysis['analysis'] ?? [],
                'segments' => $analysis['segments'] ?? [],
                'overall_score' => $analysis['overall_score'] ?? 0,
                'whisper_cost' => GroqWhisperSTT::estimateCost((int) ($transcription['duration'] ?? 0)),
                'analysis_cost' => $analysis['cost_usd'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('CallCenterAgent: analyzeCall xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reyting javob (bazadan, bepul)
     */
    private function getLeaderboardResponse(string $businessId): AIResponse
    {
        $data = $this->scorer->getLeaderboard($businessId);

        if (!($data['success'] ?? false) || empty($data['leaderboard'])) {
            return AIResponse::fromDatabase("Hozircha qo'ng'iroq tahlili ma'lumotlari yo'q. Avval qo'ng'iroqlarni tahlilga yuboring.");
        }

        $response = "🏆 **Operatorlar reytingi (oxirgi 30 kun):**\n\n";
        foreach ($data['leaderboard'] as $op) {
            $medal = match ($op->rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => "#{$op->rank}" };
            $response .= "{$medal} **{$op->operator_name}** — {$op->avg_score} ball, {$op->total_calls} qo'ng'iroq, konversiya {$op->conversion_rate}%\n";
        }

        return AIResponse::fromDatabase($response);
    }

    /**
     * Operator statistikasi (bazadan, bepul)
     */
    private function getOperatorStatsResponse(string $businessId, string $message): AIResponse
    {
        // Hozircha umumiy statistika qaytaramiz
        $data = $this->scorer->getLeaderboard($businessId, 30);

        if (!($data['success'] ?? false) || empty($data['leaderboard'])) {
            return AIResponse::fromDatabase("Operator statistikasi hozircha mavjud emas.");
        }

        $totalCalls = array_sum(array_column($data['leaderboard'], 'total_calls'));
        $avgScore = count($data['leaderboard']) > 0
            ? round(array_sum(array_column($data['leaderboard'], 'avg_score')) / count($data['leaderboard']), 1)
            : 0;

        return AIResponse::fromDatabase(
            "📞 **Qo'ng'iroq markazi holati (oxirgi 30 kun):**\n\n"
            . "📊 Jami tahlil qilingan: **{$totalCalls}** ta\n"
            . "⭐ O'rtacha ball: **{$avgScore}**/100\n"
            . "👥 Operatorlar soni: **" . count($data['leaderboard']) . "**\n\n"
            . "Batafsil ma'lumot uchun \"reyting\" yozing."
        );
    }

    /**
     * Qo'ng'iroq tahlili haqida umumiy ma'lumot
     */
    private function getCallInsights(string $message, string $businessId): AIResponse
    {
        return $this->aiService->ask(
            prompt: $message,
            systemPrompt: "Sen BiznesPilot qo'ng'iroq markazi agentisan. Operator coaching va qo'ng'iroq tahlili bo'yicha maslahat ber. O'zbek tilida, qisqa va amaliy.",
            preferredModel: 'haiku',
            maxTokens: 600,
            businessId: $businessId,
            agentType: 'call_center',
        );
    }

    /**
     * Umumiy maslahat
     */
    private function getGeneralAdvice(string $message, string $businessId): AIResponse
    {
        return $this->aiService->ask(
            prompt: $message,
            systemPrompt: "Sen BiznesPilot qo'ng'iroq markazi agentisan. O'zbek tilida, qisqa javob ber.",
            preferredModel: 'haiku',
            maxTokens: 500,
            businessId: $businessId,
            agentType: 'call_center',
        );
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) return true;
        }
        return false;
    }
}
