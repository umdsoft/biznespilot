<?php

namespace App\Services\AI;

use App\Models\AIUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Umumiy AI muloqot xizmati — barcha agentlar shu orqali AI bilan gaplashadi.
 * Gibrid mantiq: kesh → model tanlash → Claude API → natijani keshlash → token qayd qilish
 */
class AIService
{
    // Model identifikatorlari
    private const MODEL_HAIKU = 'claude-haiku-4-5-20251001';
    private const MODEL_SONNET = 'claude-sonnet-4-6-20250514';

    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

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
                $cached = Redis::get("ai_cache:{$cacheKey}");
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

        // 4-qadam: Claude API ga so'rov yuborish
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                Log::error('AIService: API xatosi', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return AIResponse::error("API xatosi: {$response->status()}");
            }

            $responseData = $response->json();
            $aiResponse = AIResponse::fromAPI($responseData, $model, $processingTimeMs);

            // 5-qadam: Natijani keshlash
            if ($cacheKey && $aiResponse->success) {
                try {
                    Redis::setex("ai_cache:{$cacheKey}", $cacheTTL, $aiResponse->content);
                } catch (\Exception $e) {
                    Log::warning('AIService: Redis kesh yozish xatosi', ['error' => $e->getMessage()]);
                }
            }

            // 6-qadam: Token va xarajatni qayd qilish
            $this->logUsage(
                $businessId,
                $agentType,
                $model,
                $aiResponse->tokensInput,
                $aiResponse->tokensOutput,
                $aiResponse->costUsd,
                false,
            );

            return $aiResponse;

        } catch (\Exception $e) {
            Log::error('AIService: So\'rov xatosi', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);
            return AIResponse::error($e->getMessage());
        }
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

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                return AIResponse::error("API xatosi: {$response->status()}");
            }

            $aiResponse = AIResponse::fromAPI($response->json(), $model, $processingTimeMs);

            $this->logUsage(
                $businessId,
                $agentType,
                $model,
                $aiResponse->tokensInput,
                $aiResponse->tokensOutput,
                $aiResponse->costUsd,
                false,
            );

            return $aiResponse;

        } catch (\Exception $e) {
            Log::error('AIService: Chat xatosi', ['error' => $e->getMessage()]);
            return AIResponse::error($e->getMessage());
        }
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
