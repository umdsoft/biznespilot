<?php

namespace App\Services\CallCenter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallAnalysisService
{
    protected string $apiKey;

    protected string $model;

    protected int $maxTokens;

    protected float $temperature;

    protected const API_URL = 'https://api.anthropic.com/v1/messages';

    protected const API_VERSION = '2023-06-01';

    public function __construct()
    {
        $config = config('call-center.analysis');

        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
        $this->model = $config['model'] ?? 'claude-3-5-haiku-20241022';
        $this->maxTokens = $config['max_tokens'] ?? 2000;
        $this->temperature = $config['temperature'] ?? 0.3;
    }

    /**
     * Analyze a call transcript
     *
     * @param  string  $transcript  The call transcript
     * @param  string  $operatorName  Name of the operator
     * @param  int  $duration  Call duration in seconds
     * @return array Analysis results
     *
     * @throws \Exception
     */
    public function analyze(string $transcript, string $operatorName, int $duration): array
    {
        $this->validateApiKey();

        Log::info('Starting call analysis', [
            'operator' => $operatorName,
            'duration' => $duration,
            'transcript_length' => strlen($transcript),
        ]);

        $startTime = microtime(true);

        try {
            $systemPrompt = $this->getSystemPrompt();
            $userPrompt = $this->getUserPrompt($transcript, $operatorName, $duration);

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

            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                throw new \Exception("Claude API error: {$error}");
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';
            $inputTokens = $data['usage']['input_tokens'] ?? 0;
            $outputTokens = $data['usage']['output_tokens'] ?? 0;

            // Parse JSON response
            $analysisResult = $this->parseAnalysisResponse($content);

            // Calculate cost
            $cost = $this->calculateCost($inputTokens, $outputTokens);

            Log::info('Call analysis completed', [
                'overall_score' => $analysisResult['overall_score'] ?? null,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost' => $cost,
                'processing_time_ms' => $processingTime,
            ]);

            return [
                'overall_score' => $analysisResult['overall_score'] ?? 0,
                'stage_scores' => $analysisResult['stage_scores'] ?? [],
                'anti_patterns' => $analysisResult['anti_patterns'] ?? [],
                'recommendations' => $analysisResult['recommendations'] ?? [],
                'strengths' => $analysisResult['strengths'] ?? [],
                'weaknesses' => $analysisResult['weaknesses'] ?? [],
                'formatted_transcript' => $analysisResult['formatted_transcript'] ?? null,
                'cost' => $cost,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'model' => $this->model,
                'processing_time_ms' => $processingTime,
            ];
        } catch (\Exception $e) {
            Log::error('Call analysis failed', [
                'operator' => $operatorName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the system prompt for analysis
     */
    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Sen professional sotuv qo'ng'iroqlari tahlilchisisisan. O'zbek tilidagi sotuv qo'ng'iroqlarini tahlil qilasan.

VAZIFANG:
1. Transkriptni Mijoz/Operator formatiga ajratish
2. Qo'ng'iroqni 7 ta bosqich bo'yicha baholash (0-100 ball)
3. Xatolarni aniqlash
4. Tavsiyalar berish

TRANSKRIPTNI FORMATLASH:
- Suhbatni kim gapiryotganini aniqla
- Operator - qo'ng'iroq qilgan/javob bergan xodim
- Mijoz - boshqa tomon
- Har bir gapni yangi qatordan yoz
- Format: "Operator: ..." yoki "Mijoz: ..."

7 TA BOSQICH:
1. Salomlashish (greeting) — 10%
   - Salom aytilganmi?
   - O'zini tanishtirdimi? (ism + kompaniya)
   - Vaqt so'raldimi?

2. Ehtiyoj aniqlash (discovery) — 20%
   - Savollar berilganmi?
   - Mijoz muammosi aniqlandimi?
   - Mijozga gapirish imkoni berilganmi?

3. Taqdimot (presentation) — 20%
   - Yechim mijoz muammosiga bog'landimi?
   - Foyda aniq aytildimi?
   - Misollar keltirildimi?

4. E'tirozlarni hal qilish (objection_handling) — 15%
   - E'tiroz tinglandi mi?
   - Mantiqiy javob berildimi?

5. Yopish (closing) — 15%
   - Yopish so'raldimi?
   - Aniq taklif qilindimi?

6. Munosabat qurish (rapport) — 10%
   - Mijoz ismidan foydalanilganmi?
   - Ijobiy til ishlatildimi?

7. Keyingi qadam (cta) — 10%
   - Aniq keyingi qadam belgilandimi?
   - Sana/vaqt kelishildimi?

XATOLAR (anti-patterns):
- no_discovery (critical, -15): Savolsiz sotish
- price_early (high, -10): Qiymatdan oldin narx
- weak_closing (high, -10): Aniq yopish yo'q
- no_objection_handle (high, -10): E'tirozga javob yo'q
- interruption (medium, -5): Gapni bo'lish
- monologue (medium, -5): 60+ sekund to'xtovsiz gapirish
- no_followup (medium, -5): Keyingi qadam yo'q
- negative_language (medium, -5): Salbiy so'zlar
- rushing (medium, -5): Shoshilish

JAVOB FORMATI:
Faqat JSON formatda javob ber, boshqa hech narsa yozma:
{
    "formatted_transcript": "Operator: Assalomu alaykum, BiznesPilot kompaniyasidan Akmal.\nMijoz: Vaalaykum assalom.\nOperator: Sizga yordam bera olamanmi?\nMijoz: Ha, narxlar haqida bilmoqchi edim.",
    "overall_score": 78,
    "stage_scores": {
        "greeting": 85,
        "discovery": 60,
        "presentation": 82,
        "objection_handling": 75,
        "closing": 80,
        "rapport": 90,
        "cta": 72
    },
    "anti_patterns": [
        {
            "type": "no_discovery",
            "severity": "high",
            "description": "Faqat 1 ta savol berildi",
            "suggestion": "Kamida 3-5 ta savol bering"
        }
    ],
    "recommendations": [
        "Discovery bosqichida ko'proq savol bering",
        "Narxni faqat qiymat ko'rsatgandan keyin ayting"
    ],
    "strengths": [
        "Yaxshi salomlashish",
        "Ijobiy ohang"
    ],
    "weaknesses": [
        "Kam savol",
        "Narx erta aytildi"
    ]
}
PROMPT;
    }

    /**
     * Get the user prompt with transcript
     */
    protected function getUserPrompt(string $transcript, string $operatorName, int $duration): string
    {
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        $durationFormatted = sprintf('%d:%02d', $minutes, $seconds);

        return <<<PROMPT
Operator: {$operatorName}
Davomiylik: {$durationFormatted} ({$duration} sekund)

TRANSKRIPT:
{$transcript}
PROMPT;
    }

    /**
     * Parse the analysis response from Claude
     *
     * @throws \Exception
     */
    protected function parseAnalysisResponse(string $content): array
    {
        // Try to extract JSON from the response
        $content = trim($content);

        // If response starts with ```, extract JSON
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        // Clean up any leading/trailing non-JSON characters
        $content = preg_replace('/^[^{]*/', '', $content);
        $content = preg_replace('/[^}]*$/', '', $content);

        $result = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse analysis JSON', [
                'content' => substr($content, 0, 500),
                'error' => json_last_error_msg(),
            ]);
            throw new \Exception('Failed to parse analysis response: '.json_last_error_msg());
        }

        // Validate required fields
        $requiredFields = ['overall_score', 'stage_scores'];
        foreach ($requiredFields as $field) {
            if (! isset($result[$field])) {
                throw new \Exception("Missing required field in analysis: {$field}");
            }
        }

        return $result;
    }

    /**
     * Calculate cost based on token usage
     * Claude Haiku: $0.25/1M input, $1.25/1M output
     */
    public function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $pricing = config('call-center.analysis.pricing');
        $inputCost = ($inputTokens / 1_000_000) * ($pricing['input_per_million'] ?? 0.25);
        $outputCost = ($outputTokens / 1_000_000) * ($pricing['output_per_million'] ?? 1.25);

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Estimate cost for analysis (before running)
     * Assumes ~1500 tokens input, ~800 tokens output for average call
     */
    public function estimateCost(int $transcriptLength = 5000): array
    {
        // Rough estimate: 1 token ≈ 4 characters
        $estimatedInputTokens = (int) ($transcriptLength / 4) + 500; // +500 for system prompt
        $estimatedOutputTokens = 800;

        $cost = $this->calculateCost($estimatedInputTokens, $estimatedOutputTokens);
        $uzsRate = config('call-center.currency.usd_to_uzs', 12800);

        return [
            'usd' => $cost,
            'uzs' => round($cost * $uzsRate),
            'formatted' => number_format($cost * $uzsRate, 0, '.', ' ').' so\'m',
        ];
    }

    /**
     * Validate API key is configured
     *
     * @throws \Exception
     */
    protected function validateApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Anthropic API key is not configured. Please set ANTHROPIC_API_KEY in .env');
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $this->validateApiKey();

            // Make a minimal request to verify the API key
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_URL, [
                    'model' => $this->model,
                    'max_tokens' => 10,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hi'],
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Claude API connection successful',
                    'model' => $this->model,
                ];
            }

            return [
                'success' => false,
                'error' => 'API returned status: '.$response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'api_configured' => ! empty($this->apiKey),
        ];
    }
}
