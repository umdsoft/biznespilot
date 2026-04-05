<?php

namespace App\Services\Agent\Voice;

use App\Services\Agent\OrchestratorService;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ovozli AI yordamchi — Telegram orqali ovozli xabar bilan gaplashish.
 * Oqim: Ovoz → Whisper (matn) → Agent → Javob → TTS (ovoz)
 *
 * Gibrid: 70% oddiy savollar bazadan (bepul), 30% AI
 */
class VoiceAgentService
{
    public function __construct(
        private AIService $aiService,
        private OrchestratorService $orchestrator,
    ) {}

    /**
     * Ovozli xabarni qayta ishlash
     */
    public function processVoiceMessage(
        string $audioPath,
        string $businessId,
        string $userId,
        ?string $conversationId = null,
    ): array {
        $startTime = microtime(true);

        try {
            // 1. Ovozdan matnga (Groq Whisper)
            $transcription = $this->aiService->transcribe($audioPath);

            if (!$transcription['success']) {
                return ['success' => false, 'error' => 'Ovozni aniqlash xatosi'];
            }

            $text = $transcription['text'];
            $duration = (int) ($transcription['duration'] ?? 0);
            $language = $transcription['language'] ?? 'uz';

            // 2. Matni agent tizimiga yuborish
            $agentResult = $this->orchestrator->handleUserMessage(
                message: $text,
                businessId: $businessId,
                userId: $userId,
                conversationId: $conversationId,
            );

            $responseText = $agentResult['message'] ?? 'Javob olishda xatolik.';

            // 3. Javobni ovozli formatga moslashtirish
            $spokenResponse = $this->formatForSpeech($responseText, $language);

            // 4. TTS (hozircha faqat matn qaytaramiz — TTS integratsiya keyingi bosqichda)
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $whisperCost = round($duration * 0.04 / 3600, 6);

            // 5. Saqlash
            DB::table('voice_interactions')->insert([
                'id' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'user_id' => $userId,
                'conversation_id' => $agentResult['conversation_id'] ?? null,
                'audio_input_url' => $audioPath,
                'audio_input_duration_sec' => $duration,
                'transcript_text' => $text,
                'detected_language' => $language,
                'response_text' => $spokenResponse,
                'whisper_cost_usd' => $whisperCost,
                'total_cost_usd' => $whisperCost + ($agentResult['cost_usd'] ?? 0),
                'processing_time_ms' => $processingTimeMs,
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'transcript' => $text,
                'language' => $language,
                'response_text' => $spokenResponse,
                'audio_output_url' => null, // TTS keyingi bosqichda
                'conversation_id' => $agentResult['conversation_id'] ?? null,
                'processing_time_ms' => $processingTimeMs,
            ];

        } catch (\Exception $e) {
            Log::error('VoiceAgent: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Javobni ovozli suhbat uchun formatlash
     * Yozma javob → qisqa, tabiiy gaplashish uslubi
     */
    private function formatForSpeech(string $text, string $language): string
    {
        // Markdown belgilarini olib tashlash
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/#{1,3}\s/', '', $text);
        $text = preg_replace('/[-•]\s/', '', $text);
        $text = str_replace(['📊', '📈', '📉', '💰', '💵', '👥', '🏆', '📅', '🕐', '➡️', '✅', '⚠️', '❌', '🎯', '📱', '✍️', '#️⃣', '⏰', '💡', '🔍', '🚀', '☀️', '🙌', '😊', '👋', '🎉'], '', $text);

        // Raqamlarni oddiy formatga (1,200,000 → 1 million 200 ming)
        $text = preg_replace_callback('/(\d{1,3}(,\d{3})+)/', function ($matches) {
            $number = (int) str_replace(',', '', $matches[0]);
            return $this->numberToWords($number);
        }, $text);

        // Juda uzun bo'lsa qisqartirish
        if (mb_strlen($text) > 500) {
            $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $text = implode('. ', array_slice($sentences, 0, 5)) . '.';
        }

        return trim($text);
    }

    /**
     * Raqamni so'zga aylantirish (soddalashtirilgan)
     */
    private function numberToWords(int $number): string
    {
        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $remainder = $number % 1000000;
            $result = "{$millions} million";
            if ($remainder >= 1000) {
                $thousands = intdiv($remainder, 1000);
                $result .= " {$thousands} ming";
            }
            return $result;
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            return "{$thousands} ming";
        }

        return (string) $number;
    }
}
