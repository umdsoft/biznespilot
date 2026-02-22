<?php

namespace App\Services\VideoContent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoAnalysisService
{
    protected string $apiKey;

    protected string $model;

    protected int $maxTokens;

    protected float $temperature;

    protected const API_URL = 'https://api.anthropic.com/v1/messages';

    protected const API_VERSION = '2023-06-01';

    public function __construct()
    {
        $config = config('video-content.analysis');
        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
        $this->model = $config['model'] ?? 'claude-haiku-4-5-20251001';
        $this->maxTokens = $config['max_tokens'] ?? 2000;
        $this->temperature = $config['temperature'] ?? 0.3;
    }

    /**
     * Analyze transcript and extract key points for content generation
     *
     * @return array{key_points: array, cost: float, input_tokens: int, output_tokens: int, model: string}
     *
     * @throws \Exception
     */
    public function analyze(string $transcript, ?string $videoTitle = null, int $duration = 0): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Anthropic API key sozlanmagan. ANTHROPIC_API_KEY ni .env ga qo\'shing.');
        }

        Log::info('Starting video transcript analysis', [
            'title' => $videoTitle,
            'transcript_length' => strlen($transcript),
            'duration' => $duration,
        ]);

        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->getUserPrompt($transcript, $videoTitle, $duration);

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'Content-Type' => 'application/json',
            ])
            ->post(self::API_URL, [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('Claude API error in video analysis', [
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \Exception("AI tahlil xatosi: {$error}");
        }

        $data = $response->json();
        $content = $data['content'][0]['text'] ?? '';
        $inputTokens = $data['usage']['input_tokens'] ?? 0;
        $outputTokens = $data['usage']['output_tokens'] ?? 0;

        $keyPoints = $this->parseResponse($content);
        $cost = $this->calculateCost($inputTokens, $outputTokens);

        Log::info('Video analysis completed', [
            'topic' => $keyPoints['topic'] ?? null,
            'hooks_count' => count($keyPoints['hooks'] ?? []),
            'cost' => $cost,
        ]);

        return [
            'key_points' => $keyPoints,
            'cost' => $cost,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'model' => $this->model,
        ];
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Sen professional kontent strateg va video tahlilchisisisan. Video transkriptini tahlil qilib, undan kuchli ijtimoiy tarmoq kontenti yaratish uchun kalit nuqtalarni ajratib berasan.

VAZIFANG:
1. Videoning asosiy mavzusini aniqlash
2. Eng kuchli hook'larni topish (diqqatni tortadigan jumlalar)
3. Muhim fakt va statistikalarni ajratish
4. Hikoya elementlarini topish (shaxsiy tajriba, misol, case study)
5. CTA (call to action) ni aniqlash
6. Maqsadli auditoriyani aniqlash

QOIDALAR:
- Faqat videodagi haqiqiy ma'lumotlardan foydalaning
- Hook'lar qisqa va kuchli bo'lsin (1-2 jumla)
- Faktlar aniq va tekshirilgan bo'lsin
- Hikoya elementlari emotsional bog'lanish yaratsin
- O'zbek tilidagi kontentga mos bo'lsin

JAVOB FORMATI (faqat JSON):
{
    "topic": "Videoning asosiy mavzusi",
    "hooks": [
        "Diqqatni tortadigan 1-jumla",
        "Yana bir kuchli hook",
        "Savol shaklida hook"
    ],
    "facts": [
        "Muhim statistika yoki fakt 1",
        "Muhim statistika yoki fakt 2"
    ],
    "story_elements": [
        "Shaxsiy tajriba yoki misol 1",
        "Case study yoki hikoya 2"
    ],
    "key_messages": [
        "Asosiy xabar 1",
        "Asosiy xabar 2",
        "Asosiy xabar 3"
    ],
    "cta": "Videodagi call to action",
    "target_audience": "Maqsadli auditoriya tavsifi",
    "tone": "professional/casual/motivational/educational",
    "content_angles": [
        "Bu mavzudan post yozish uchun burchak 1",
        "Carousel uchun burchak 2",
        "Reel uchun burchak 3"
    ]
}
PROMPT;
    }

    protected function getUserPrompt(string $transcript, ?string $videoTitle, int $duration): string
    {
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        $durationFormatted = $duration > 0 ? sprintf('%d:%02d', $minutes, $seconds) : 'noma\'lum';

        $titleLine = $videoTitle ? "VIDEO NOMI: {$videoTitle}" : '';

        return <<<PROMPT
{$titleLine}
DAVOMIYLIK: {$durationFormatted}

TRANSKRIPT:
{$transcript}
PROMPT;
    }

    protected function parseResponse(string $content): array
    {
        // UTF-8 tozalash (transkriptdagi noto'g'ri belgilar Claude javobiga o'tishi mumkin)
        $content = mb_convert_encoding(trim($content), 'UTF-8', 'UTF-8');
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);

        // Extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        // Clean up non-JSON characters
        $content = preg_replace('/^[^{]*/', '', $content);
        $content = preg_replace('/[^}]*$/', '', $content);

        $result = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback: JSON_INVALID_UTF8_SUBSTITUTE bilan qayta urinish
            $result = json_decode($content, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse video analysis JSON', [
                'content' => substr($content, 0, 500),
                'error' => json_last_error_msg(),
            ]);
            throw new \Exception('AI javobini parse qilib bo\'lmadi: ' . json_last_error_msg());
        }

        // Validate required fields
        if (empty($result['topic'])) {
            throw new \Exception('AI javobida mavzu (topic) topilmadi.');
        }

        return $result;
    }

    /**
     * Calculate cost — Claude Haiku 4.5: $1/1M input, $5/1M output
     */
    protected function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1_000_000) * 1.00;
        $outputCost = ($outputTokens / 1_000_000) * 5.00;

        return round($inputCost + $outputCost, 6);
    }
}
