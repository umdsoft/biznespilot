<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Claude AI Service - Anthropic API Integration
 *
 * Features:
 * - Text completion (complete)
 * - Conversational chat (chat)
 * - Caching for repeated queries
 * - Error handling with graceful fallback
 * - Usage tracking
 */
class ClaudeAIService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $defaultModel = 'claude-3-haiku-20240307'; // Cost-effective model
    private string $premiumModel = 'claude-sonnet-4-20250514'; // For complex tasks
    private int $defaultMaxTokens = 1024;
    private float $defaultTemperature = 0.7;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
    }

    /**
     * Complete a prompt with Claude AI
     *
     * @param string $prompt The user prompt
     * @param string|null $systemPrompt Optional system prompt
     * @param int $maxTokens Maximum tokens in response
     * @param bool $useCache Whether to cache the response
     * @param bool $usePremiumModel Use premium model for complex tasks
     * @return string The AI response
     */
    public function complete(
        string $prompt,
        ?string $systemPrompt = null,
        int $maxTokens = 1024,
        bool $useCache = true,
        bool $usePremiumModel = false
    ): string {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('ClaudeAIService: API key not configured');
            return $this->getFallbackResponse('API kalit sozlanmagan');
        }

        // Check cache first
        if ($useCache) {
            $cacheKey = $this->getCacheKey($prompt, $systemPrompt);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::debug('ClaudeAIService: Returning cached response');
                return $cached;
            }
        }

        try {
            $messages = [
                ['role' => 'user', 'content' => $prompt],
            ];

            $response = $this->makeApiRequest($messages, $systemPrompt, $maxTokens, $usePremiumModel);

            // Cache successful response
            if ($useCache && $response) {
                Cache::put($this->getCacheKey($prompt, $systemPrompt), $response, now()->addHours(24));
            }

            // Track usage
            $this->trackUsage('complete', $usePremiumModel);

            return $response;

        } catch (\Exception $e) {
            Log::error('ClaudeAIService::complete error', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt),
            ]);

            return $this->getFallbackResponse($e->getMessage());
        }
    }

    /**
     * Chat with conversation history
     *
     * @param array $messages Array of messages [['role' => 'user|assistant', 'content' => '...']]
     * @param string|null $systemPrompt Optional system prompt
     * @param int $maxTokens Maximum tokens in response
     * @param bool $usePremiumModel Use premium model for complex tasks
     * @return string The AI response
     */
    public function chat(
        array $messages,
        ?string $systemPrompt = null,
        int $maxTokens = 1024,
        bool $usePremiumModel = false
    ): string {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('ClaudeAIService: API key not configured');
            return 'AI xizmati hozircha mavjud emas. API kalit sozlanmagan.';
        }

        try {
            // Ensure messages are in correct format
            $formattedMessages = $this->formatMessages($messages);

            $response = $this->makeApiRequest($formattedMessages, $systemPrompt, $maxTokens, $usePremiumModel);

            // Track usage
            $this->trackUsage('chat', $usePremiumModel);

            return $response;

        } catch (\Exception $e) {
            Log::error('ClaudeAIService::chat error', [
                'error' => $e->getMessage(),
                'messages_count' => count($messages),
            ]);

            return 'Xatolik yuz berdi: ' . $this->getReadableError($e->getMessage());
        }
    }

    /**
     * Generate Dream Buyer avatar/summary
     */
    public function generateDreamBuyerAvatar(array $dreamBuyerData): string
    {
        $systemPrompt = <<<EOT
Sen marketing ekspertizan. Berilgan ma'lumotlar asosida ideal mijoz (Dream Buyer) avatarini yaratishing kerak.
Natijani O'zbek tilida, professional va aniq qilib yoz.
Format:
- Ism va ta'rif (1-2 gap)
- Asosiy xususiyatlar (3-5 ta)
- Asosiy muammolari (3-5 ta)
- Qaror qabul qilish jarayoni
- Eng yaxshi marketing kanallari
EOT;

        $prompt = "Ideal mijoz ma'lumotlari:\n" . json_encode($dreamBuyerData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->complete($prompt, $systemPrompt, 2048, true, true);
    }

    /**
     * Generate competitor analysis insights
     */
    public function generateCompetitorInsights(array $competitorData, array $businessData): string
    {
        $systemPrompt = <<<EOT
Sen biznes tahlilchi va strategiksisan. Raqobatchi ma'lumotlarini tahlil qilib, biznes uchun amaliy tavsiyalar berishing kerak.
Natijani O'zbek tilida, qisqa va aniq qilib yoz.
Format:
- Raqobatchi kuchli tomonlari (3-5 ta)
- Raqobatchi zaif tomonlari (3-5 ta)
- Biznes uchun imkoniyatlar (3-5 ta)
- Tavsiya etiladigan strategiya (2-3 ta)
EOT;

        $prompt = "Biznes: " . json_encode($businessData, JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "Raqobatchi: " . json_encode($competitorData, JSON_UNESCAPED_UNICODE);

        return $this->complete($prompt, $systemPrompt, 2048, true, true);
    }

    /**
     * Generate marketing recommendations
     */
    public function generateMarketingRecommendations(array $analyticsData): string
    {
        $systemPrompt = <<<EOT
Sen marketing ekspertizan. Analytics ma'lumotlari asosida amaliy tavsiyalar berishing kerak.
Natijani O'zbek tilida, aniq va qisqa qilib yoz.
Har bir tavsiya uchun: nima qilish kerak, nega kerak, kutilgan natija.
EOT;

        $prompt = "Marketing analytics ma'lumotlari:\n" . json_encode($analyticsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->complete($prompt, $systemPrompt, 1024, false, false);
    }

    /**
     * Generate chatbot response
     */
    public function generateChatbotResponse(
        string $userMessage,
        array $conversationHistory,
        array $businessContext
    ): string {
        $systemPrompt = <<<EOT
Sen {$businessContext['business_name']} kompaniyasining yordamchi chatbotizan.
Mijozlarga professional va do'stona tarzda javob ber.
Agar savolga javob bera olmasang, operator bilan bog'lanishni taklif qil.
Har doim O'zbek tilida javob ber.
Kompaniya haqida: {$businessContext['description']}
EOT;

        // Add user message to history
        $conversationHistory[] = ['role' => 'user', 'content' => $userMessage];

        return $this->chat($conversationHistory, $systemPrompt, 512, false);
    }

    /**
     * Analyze sales call
     */
    public function analyzeCall(string $transcription, array $stages = []): array
    {
        $systemPrompt = <<<EOT
Sen sotish bo'yicha coach va trenersisan. Qo'ng'iroq yozuvini tahlil qilib, baho va tavsiyalar berishing kerak.
JSON formatida javob ber:
{
    "overall_score": 0-100,
    "stage_scores": {"stage_name": 0-100, ...},
    "strengths": ["...", "..."],
    "improvements": ["...", "..."],
    "key_moments": ["...", "..."],
    "next_steps": ["...", "..."]
}
EOT;

        $stagesInfo = !empty($stages) ? "\nSotish bosqichlari: " . implode(', ', $stages) : '';
        $prompt = "Qo'ng'iroq yozuvi:{$stagesInfo}\n\n{$transcription}";

        $response = $this->complete($prompt, $systemPrompt, 2048, false, true);

        // Try to parse JSON response
        try {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                return json_decode($jsonString, true) ?? ['raw_response' => $response];
            }
        } catch (\Exception $e) {
            Log::warning('ClaudeAIService: Could not parse call analysis JSON', ['response' => $response]);
        }

        return ['raw_response' => $response];
    }

    /**
     * Make API request to Anthropic
     */
    private function makeApiRequest(
        array $messages,
        ?string $systemPrompt,
        int $maxTokens,
        bool $usePremiumModel
    ): string {
        $model = $usePremiumModel ? $this->premiumModel : $this->defaultModel;

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])
        ->timeout(60)
        ->retry(2, 1000, function ($exception) {
            // Retry on rate limit or server errors
            return $exception instanceof \Illuminate\Http\Client\RequestException
                && in_array($exception->response->status(), [429, 500, 502, 503]);
        })
        ->post($this->apiUrl, $payload);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? $response->body();

            Log::error('ClaudeAIService: API request failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'model' => $model,
            ]);

            throw new \Exception("API xatosi: {$errorMessage}");
        }

        $data = $response->json();

        // Extract text from response
        if (isset($data['content'][0]['text'])) {
            return $data['content'][0]['text'];
        }

        throw new \Exception('Unexpected API response format');
    }

    /**
     * Format messages for API
     */
    private function formatMessages(array $messages): array
    {
        return array_map(function ($message) {
            return [
                'role' => $message['role'] ?? 'user',
                'content' => $message['content'] ?? $message['text'] ?? '',
            ];
        }, $messages);
    }

    /**
     * Get cache key for prompt
     */
    private function getCacheKey(string $prompt, ?string $systemPrompt): string
    {
        return 'claude_ai:' . md5($prompt . ($systemPrompt ?? ''));
    }

    /**
     * Track API usage for monitoring
     */
    private function trackUsage(string $method, bool $isPremium): void
    {
        $date = now()->format('Y-m-d');
        $key = "claude_usage:{$date}";

        $usage = Cache::get($key, ['requests' => 0, 'premium_requests' => 0]);
        $usage['requests']++;
        if ($isPremium) {
            $usage['premium_requests']++;
        }

        Cache::put($key, $usage, now()->addDays(30));
    }

    /**
     * Get readable error message
     */
    private function getReadableError(string $error): string
    {
        if (str_contains($error, 'rate_limit')) {
            return 'So\'rovlar limiti oshdi. Iltimos, bir ozdan keyin urinib ko\'ring.';
        }
        if (str_contains($error, 'invalid_api_key')) {
            return 'API kalit noto\'g\'ri. Administrator bilan bog\'laning.';
        }
        if (str_contains($error, 'overloaded')) {
            return 'Server band. Iltimos, keyinroq urinib ko\'ring.';
        }

        return 'Texnik xatolik yuz berdi.';
    }

    /**
     * Get fallback response when AI is unavailable
     */
    private function getFallbackResponse(string $reason = ''): string
    {
        return json_encode([
            'error' => true,
            'message' => 'AI xizmati hozircha mavjud emas',
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get service status
     */
    public function getStatus(): array
    {
        $date = now()->format('Y-m-d');
        $usage = Cache::get("claude_usage:{$date}", ['requests' => 0, 'premium_requests' => 0]);

        return [
            'available' => $this->isAvailable(),
            'api_configured' => !empty($this->apiKey),
            'default_model' => $this->defaultModel,
            'premium_model' => $this->premiumModel,
            'today_requests' => $usage['requests'],
            'today_premium_requests' => $usage['premium_requests'],
        ];
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(int $days = 7): array
    {
        $stats = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $usage = Cache::get("claude_usage:{$date}", ['requests' => 0, 'premium_requests' => 0]);
            $stats[$date] = $usage;
        }

        return $stats;
    }
}
