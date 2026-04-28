<?php

namespace App\Services\AI;

use App\Models\AIUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Umumiy AI muloqot xizmati — barcha agentlar shu orqali AI bilan gaplashadi.
 * Gibrid mantiq: kesh → model tanlash → Claude API → natijani keshlash → token qayd qilish
 */
class AIService
{
    // Model identifikatorlari
    private const MODEL_HAIKU = 'claude-haiku-4-5-20251001';
    private const MODEL_SONNET = 'claude-sonnet-4-5-20250514';

    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    // Production'da bir AI chaqiruv max ~45s (22s + 1s sleep + 22s) qiladi.
    // Avval 2 retry + 45s timeout = 135s+ edi → nginx 504 Gateway Timeout.
    private int $maxRetries = 1;
    private int $httpTimeout = 22;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    /**
     * Asosiy so'rov metodi — gibrid mantiq bilan
     *
     * @param string $prompt Foydalanuvchi so'rovi
     * @param string $systemPrompt Tizim ko'rsatmasi
     * @param string $preferredModel 'haiku' yoki 'sonnet'
     * @param int $maxTokens Maksimal javob tokenlari
     * @param string|null $cacheKey Kesh kaliti (null = keshlanmaydi)
     * @param int $cacheTTL Kesh muddati (soniya)
     * @param int|null $businessId Biznes ID (token qayd qilish uchun)
     * @param string $agentType Agent turi (token qayd qilish uchun)
     */
    public function ask(
        string $prompt,
        string $systemPrompt,
        string $preferredModel = 'haiku',
        int $maxTokens = 1000,
        ?string $cacheKey = null,
        int $cacheTTL = 3600,
        ?string $businessId = null,
        string $agentType = 'general',
    ): AIResponse {
        // 1-qadam: Keshdan tekshirish
        if ($cacheKey) {
            try {
                $cached = Cache::get("ai_cache:{$cacheKey}");
                if ($cached) {
                    Log::debug('AIService: keshdan javob qaytarildi', ['key' => $cacheKey]);

                    // Kesh hit ni qayd qilish
                    $this->logUsage($businessId, $agentType, 'none', 0, 0, 0, true);

                    return AIResponse::fromCache($cached);
                }
            } catch (\Exception $e) {
                // Redis ishlamasa davom etamiz
                Log::warning('AIService: Redis kesh xatosi', ['error' => $e->getMessage()]);
            }
        }

        // 2-qadam: API kalit tekshirish
        if (empty($this->apiKey)) {
            Log::error('AIService: ANTHROPIC_API_KEY sozlanmagan');
            return AIResponse::error('API kalit sozlanmagan');
        }

        // 3-qadam: Model tanlash
        $model = $this->resolveModel($preferredModel);

        // 4-qadam: Claude API ga retry bilan so'rov yuborish
        $startTime = microtime(true);
        $lastError = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            // Agar oldingi urinish muvaffaqiyatsiz bo'lsa — Haiku ga fallback
            $currentModel = ($attempt > 0 && $model !== self::MODEL_HAIKU) ? self::MODEL_HAIKU : $model;

            try {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout($this->httpTimeout)->connectTimeout(5)->post($this->apiUrl, [
                    'model' => $currentModel,
                    'max_tokens' => $maxTokens,
                    'system' => $systemPrompt,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

                if ($response->successful()) {
                    $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                    $responseData = $response->json();
                    $aiResponse = AIResponse::fromAPI($responseData, $currentModel, $processingTimeMs);

                    // 5-qadam: Natijani keshlash
                    if ($cacheKey && $aiResponse->success) {
                        try {
                            Cache::put("ai_cache:{$cacheKey}", $aiResponse->content, $cacheTTL);
                        } catch (\Exception $e) {
                            Log::warning('AIService: kesh yozish xatosi', ['error' => $e->getMessage()]);
                        }
                    }

                    // 6-qadam: Token va xarajatni qayd qilish
                    $this->logUsage(
                        $businessId,
                        $agentType,
                        $currentModel,
                        $aiResponse->tokensInput,
                        $aiResponse->tokensOutput,
                        $aiResponse->costUsd,
                        false,
                    );

                    if ($attempt > 0) {
                        Log::info("AIService: {$attempt}-urinishda {$currentModel} bilan muvaffaqiyatli javob olindi");
                    }

                    return $aiResponse;
                }

                // API xatosi — log yozib retry
                $lastError = "API {$response->status()}: " . mb_substr($response->body(), 0, 200);
                Log::warning("AIService: API xatosi (urinish {$attempt})", [
                    'status' => $response->status(),
                    'model' => $currentModel,
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("AIService: So'rov exception (urinish {$attempt})", [
                    'error' => $e->getMessage(),
                    'model' => $currentModel,
                ]);
            }

            // Retry oldidan kutish (exponential backoff)
            if ($attempt < $this->maxRetries) {
                usleep(500000 * ($attempt + 1)); // 0.5s, 1s
            }
        }

        Log::error('AIService: Barcha urinishlar muvaffaqiyatsiz', ['last_error' => $lastError]);
        return AIResponse::error($lastError ?? 'API javob bermadi');
    }

    /**
     * Suhbat tarixi bilan so'rov (bir nechta xabar)
     */
    public function chat(
        array $messages,
        string $systemPrompt,
        string $preferredModel = 'haiku',
        int $maxTokens = 1000,
        ?string $businessId = null,
        string $agentType = 'general',
    ): AIResponse {
        if (empty($this->apiKey)) {
            return AIResponse::error('API kalit sozlanmagan');
        }

        $model = $this->resolveModel($preferredModel);
        $startTime = microtime(true);

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            $currentModel = ($attempt > 0 && $model !== self::MODEL_HAIKU) ? self::MODEL_HAIKU : $model;

            try {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout($this->httpTimeout)->connectTimeout(5)->post($this->apiUrl, [
                    'model' => $currentModel,
                    'max_tokens' => $maxTokens,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                ]);

                if ($response->successful()) {
                    $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                    $aiResponse = AIResponse::fromAPI($response->json(), $currentModel, $processingTimeMs);

                    $this->logUsage($businessId, $agentType, $currentModel,
                        $aiResponse->tokensInput, $aiResponse->tokensOutput, $aiResponse->costUsd, false);

                    return $aiResponse;
                }

                Log::warning("AIService: Chat API xatosi (urinish {$attempt})", [
                    'status' => $response->status(), 'model' => $currentModel,
                ]);
            } catch (\Exception $e) {
                Log::warning("AIService: Chat exception (urinish {$attempt})", ['error' => $e->getMessage()]);
            }

            if ($attempt < $this->maxRetries) {
                usleep(500000 * ($attempt + 1));
            }
        }

        return AIResponse::error('Chat API javob bermadi');
    }

    /**
     * Groq Whisper orqali ovozdan matnga aylantirish
     */
    public function transcribe(string $audioPath): array
    {
        $groqKey = config('services.groq.api_key', '');

        if (empty($groqKey)) {
            Log::error('AIService: GROQ_API_KEY sozlanmagan');
            return ['success' => false, 'error' => 'Groq API kalit sozlanmagan'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$groqKey}",
            ])->attach(
                'file', file_get_contents($audioPath), basename($audioPath)
            )->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                'model' => 'whisper-large-v3-turbo',
                'language' => 'uz',
                'response_format' => 'verbose_json',
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'error' => "Groq API xatosi: {$response->status()}"];
            }

            return [
                'success' => true,
                'text' => $response->json('text', ''),
                'duration' => $response->json('duration', 0),
                'segments' => $response->json('segments', []),
            ];

        } catch (\Exception $e) {
            Log::error('AIService: Transcribe xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Model nomini to'liq identifikatorga aylantirish
     */
    private function resolveModel(string $preferred): string
    {
        return match ($preferred) {
            'sonnet' => self::MODEL_SONNET,
            'haiku' => self::MODEL_HAIKU,
            default => self::MODEL_HAIKU,
        };
    }

    /**
     * Token va xarajatni qayd qilish
     */
    private function logUsage(
        ?string $businessId,
        string $agentType,
        string $model,
        int $tokensInput,
        int $tokensOutput,
        float $costUsd,
        bool $cacheHit,
    ): void {
        try {
            AIUsageLog::create([
                'business_id' => $businessId,
                'agent_type' => $agentType,
                'model' => $model ?: 'none',
                'tokens_input' => $tokensInput,
                'tokens_output' => $tokensOutput,
                'cost_usd' => $costUsd,
                'cache_hit' => $cacheHit,
            ]);
        } catch (\Exception $e) {
            // Token qayd qilish xatosi asosiy jarayonni to'xtatmasligi kerak
            Log::warning('AIService: Token qayd qilish xatosi', ['error' => $e->getMessage()]);
        }
    }
}
