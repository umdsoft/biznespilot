<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService — Google Gemini AI integration.
 *
 * BiznesPilot chatbot uchun arzon alternativ. Hozirgi Claude Haiku 4.5
 * o'rniga ishlatilganda taxminan 89% xarajatni kamaytiradi:
 *   - Claude Haiku:      $0.80 / $4.00  per 1M tokens (input/output)
 *   - Gemini 2.0 Flash:  $0.10 / $0.40  per 1M tokens
 *   - Gemini 1.5 Flash:  $0.075 / $0.30  per 1M tokens
 *   - Free tier (Google AI Studio): 1M tokens/kun, 15 RPM — KREDIT KARTA KERAK EMAS
 *
 * Afzalliklar:
 *   - O'zbekistondan to'lov: Google billing Visa/Mastercard ishlaydi
 *     (DeepSeek/Xitoy o'rniga, qaysi UnionPay talab qiladi)
 *   - Free tier: kichik bizneslar uchun deyarli BEPUL
 *   - Bir xil Google account orqali Ads/GA4/YouTube bilan billing
 *
 * API: https://ai.google.dev/api (REST, native format)
 * Endpoint: generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
 *
 * Public interface ClaudeAIService::chat() bilan mos — ChatbotService
 * o'rniga ishlatilganda kod o'zgartirish kerak emas.
 */
class GeminiService
{
    private string $apiKey;

    private string $apiBase = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * Default chat model. Gemini 2.0 Flash — eng arzon, yaxshi sifat.
     * Eng arzon: 'gemini-1.5-flash-8b' ($0.0375/$0.15) — basic chat uchun.
     * Premium: 'gemini-1.5-pro' ($1.25/$5.00) — murakkab vazifalar.
     */
    private string $defaultModel = 'gemini-2.0-flash';

    private string $premiumModel = 'gemini-1.5-pro';

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', '');
        // Config orqali model override imkoniyati
        $configModel = (string) config('services.gemini.default_model', '');
        if ($configModel !== '') {
            $this->defaultModel = $configModel;
        }
    }

    /**
     * Chat with conversation history. ChatbotService::generateAIResponse
     * uchun ClaudeAIService::chat bilan bir xil signature.
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function chat(
        array $messages,
        ?string $systemPrompt = null,
        int $maxTokens = 1024,
        bool $usePremiumModel = false,
        float $temperature = 0.7
    ): string {
        if (empty($this->apiKey)) {
            Log::warning('GeminiService: API key not configured');

            return 'AI xizmati hozircha mavjud emas. API kalit sozlanmagan.';
        }

        try {
            $contents = $this->convertMessagesToGeminiFormat($messages);

            $response = $this->makeApiRequest(
                $contents,
                $systemPrompt,
                $maxTokens,
                $usePremiumModel,
                $temperature
            );

            $this->trackUsage('chat', $usePremiumModel);

            return $response;
        } catch (\Throwable $e) {
            Log::error('GeminiService::chat error', [
                'error' => $e->getMessage(),
                'messages_count' => count($messages),
            ]);

            return 'Xatolik yuz berdi: '.$this->getReadableError($e->getMessage());
        }
    }

    /**
     * Bir martalik prompt — system + user. Cache qo'llab-quvvatlanadi.
     */
    public function complete(
        string $prompt,
        ?string $systemPrompt = null,
        int $maxTokens = 1024,
        bool $useCache = true,
        bool $usePremiumModel = false
    ): string {
        if (empty($this->apiKey)) {
            return 'AI xizmati hozircha mavjud emas.';
        }

        if ($useCache) {
            $cacheKey = $this->getCacheKey($prompt, $systemPrompt);
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $contents = [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]];

            $response = $this->makeApiRequest(
                $contents,
                $systemPrompt,
                $maxTokens,
                $usePremiumModel,
                0.7
            );

            if ($useCache) {
                Cache::put(
                    $this->getCacheKey($prompt, $systemPrompt),
                    $response,
                    now()->addHours(24)
                );
            }

            $this->trackUsage('complete', $usePremiumModel);

            return $response;
        } catch (\Throwable $e) {
            Log::error('GeminiService::complete error', [
                'error' => $e->getMessage(),
            ]);

            return 'Xatolik yuz berdi.';
        }
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getStatus(): array
    {
        $date = now()->format('Y-m-d');
        $usage = Cache::get("gemini_usage:{$date}", ['requests' => 0, 'premium_requests' => 0]);

        return [
            'available' => $this->isAvailable(),
            'api_configured' => ! empty($this->apiKey),
            'default_model' => $this->defaultModel,
            'premium_model' => $this->premiumModel,
            'today_requests' => $usage['requests'],
            'today_premium_requests' => $usage['premium_requests'],
        ];
    }

    public function getUsageStats(int $days = 7): array
    {
        $stats = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $usage = Cache::get("gemini_usage:{$date}", ['requests' => 0, 'premium_requests' => 0]);
            $stats[$date] = $usage;
        }

        return $stats;
    }

    // ============================================================
    // Private helpers
    // ============================================================

    /**
     * Gemini API native format — `contents` array, har ichida `role` va `parts`.
     * `role` faqat 'user' yoki 'model' (assistant emas).
     * System prompt alohida 'systemInstruction' parametri orqali yuboriladi.
     */
    private function convertMessagesToGeminiFormat(array $messages): array
    {
        $contents = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            // Gemini 'assistant' o'rniga 'model' ishlatadi
            if ($role === 'assistant' || $role === 'bot') {
                $role = 'model';
            }
            // 'system' o'rniga alohida systemInstruction — bu yerda skip qilamiz
            if ($role === 'system') {
                continue;
            }

            $content = $msg['content'] ?? $msg['text'] ?? '';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => (string) $content]],
            ];
        }

        return $contents;
    }

    private function makeApiRequest(
        array $contents,
        ?string $systemPrompt,
        int $maxTokens,
        bool $usePremiumModel,
        float $temperature
    ): string {
        $model = $usePremiumModel ? $this->premiumModel : $this->defaultModel;
        $url = "{$this->apiBase}/models/{$model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'topP' => 0.95,
            ],
            // Safety settings — kamaytirilgan (production chatbot uchun mos)
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ],
        ];

        // System prompt alohida parametrda
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(60)
            ->retry(2, 1000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\RequestException
                    && in_array($exception->response->status(), [429, 500, 502, 503], true);
            })
            ->post($url, $payload);

        if (! $response->successful()) {
            $errorBody = $response->json() ?? [];
            $errorMessage = $errorBody['error']['message']
                ?? $errorBody['message']
                ?? $response->body();

            Log::error('GeminiService: API request failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'model' => $model,
            ]);

            throw new \RuntimeException("Gemini API xatosi: {$errorMessage}");
        }

        $data = $response->json();

        // Gemini format: candidates[0].content.parts[0].text
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return (string) $data['candidates'][0]['content']['parts'][0]['text'];
        }

        // Safety filter natijasida bloklangan bo'lishi mumkin
        $finishReason = $data['candidates'][0]['finishReason'] ?? 'unknown';
        if ($finishReason === 'SAFETY' || $finishReason === 'RECITATION') {
            Log::warning('GeminiService: response blocked by safety filter', [
                'reason' => $finishReason,
            ]);

            return 'Uzr, ushbu so\'rovga javob bera olmayman. Boshqacha so\'rang yoki operator bilan bog\'laning.';
        }

        throw new \RuntimeException('Gemini: kutilmagan API javob format');
    }

    private function getCacheKey(string $prompt, ?string $systemPrompt): string
    {
        return 'gemini_ai:'.md5($prompt.($systemPrompt ?? ''));
    }

    private function trackUsage(string $method, bool $isPremium): void
    {
        $date = now()->format('Y-m-d');
        $key = "gemini_usage:{$date}";

        $usage = Cache::get($key, ['requests' => 0, 'premium_requests' => 0]);
        $usage['requests']++;
        if ($isPremium) {
            $usage['premium_requests']++;
        }

        Cache::put($key, $usage, now()->addDays(30));
    }

    private function getReadableError(string $error): string
    {
        if (str_contains($error, 'RESOURCE_EXHAUSTED') || str_contains($error, '429')) {
            return 'So\'rovlar limiti oshdi. Iltimos, bir ozdan keyin urinib ko\'ring.';
        }
        if (str_contains($error, 'API_KEY_INVALID') || str_contains($error, 'permission')) {
            return 'API kalit noto\'g\'ri. Administrator bilan bog\'laning.';
        }
        if (str_contains($error, 'QUOTA_EXCEEDED')) {
            return 'Kunlik limit tugadi. Ertaga qaytadan urinib ko\'ring.';
        }

        return 'Texnik xatolik yuz berdi.';
    }
}
