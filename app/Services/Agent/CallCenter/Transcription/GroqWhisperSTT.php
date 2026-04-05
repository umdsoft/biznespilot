<?php

namespace App\Services\Agent\CallCenter\Transcription;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Groq Whisper orqali ovozdan matnga aylantirish.
 * Model: whisper-large-v3-turbo — $0.04/soat, 200x tez.
 */
class GroqWhisperSTT
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Audio faylni matnga aylantirish
     *
     * @return array{success: bool, text: string, duration: float, segments: array, language: string}
     */
    public function transcribe(string $audioPath): array
    {
        $result = $this->aiService->transcribe($audioPath);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'text' => $result['text'],
            'duration' => $result['duration'] ?? 0,
            'segments' => $result['segments'] ?? [],
            'language' => $result['language'] ?? 'uz',
        ];
    }

    /**
     * Transkripsiya xarajatini hisoblash (USD)
     */
    public static function estimateCost(int $durationSeconds): float
    {
        // $0.04 per soat = $0.04/3600 per soniya
        return round($durationSeconds * 0.04 / 3600, 6);
    }
}
