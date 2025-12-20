<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAIService
{
    /**
     * Anthropic API base URL
     */
    private const API_BASE_URL = 'https://api.anthropic.com/v1';

    /**
     * Default model
     */
    private const DEFAULT_MODEL = 'claude-sonnet-4-20250514';

    /**
     * API version
     */
    private const API_VERSION = '2023-06-01';

    /**
     * Get API key from config
     */
    private function getApiKey(): ?string
    {
        return config('services.anthropic.api_key', env('ANTHROPIC_API_KEY')) ?? '';
    }

    /**
     * Send a message to Claude and get response
     *
     * @param array $messages Array of messages [{role: 'user'|'assistant', content: ''}]
     * @param string|null $systemPrompt System prompt for context
     * @param int $maxTokens Maximum tokens in response
     * @param string|null $model Model to use
     * @return array Response with content and usage
     */
    public function sendMessage(
        array $messages,
        ?string $systemPrompt = null,
        int $maxTokens = 4096,
        ?string $model = null
    ): array {
        try {
            $payload = [
                'model' => $model ?? self::DEFAULT_MODEL,
                'max_tokens' => $maxTokens,
                'messages' => $messages,
            ];

            if ($systemPrompt) {
                $payload['system'] = $systemPrompt;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->getApiKey(),
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])->post(self::API_BASE_URL . '/messages', $payload);

            if (!$response->successful()) {
                Log::error('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();

            return [
                'content' => $data['content'][0]['text'] ?? '',
                'usage' => $data['usage'] ?? [],
                'model' => $data['model'] ?? '',
                'stop_reason' => $data['stop_reason'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send message to Claude', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Simple text completion
     *
     * @param string $prompt User prompt
     * @param string|null $systemPrompt System context
     * @param int $maxTokens Max response length
     * @return string Response text
     */
    public function complete(
        string $prompt,
        ?string $systemPrompt = null,
        int $maxTokens = 4096
    ): string {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->sendMessage($messages, $systemPrompt, $maxTokens);

        return $response['content'];
    }

    /**
     * Generate insights from business data
     *
     * @param array $businessData Marketing and sales data
     * @param string $insightType Type of insight to generate
     * @return array Structured insight data
     */
    public function generateInsight(array $businessData, string $insightType = 'general'): array
    {
        $systemPrompt = $this->getInsightSystemPrompt($insightType);

        $prompt = $this->buildInsightPrompt($businessData, $insightType);

        $response = $this->complete($prompt, $systemPrompt, 2048);

        return $this->parseInsightResponse($response, $insightType);
    }

    /**
     * Generate monthly marketing strategy
     *
     * @param array $businessData Historical data and context
     * @param int $year Target year
     * @param int $month Target month
     * @return array Strategy data
     */
    public function generateMonthlyStrategy(array $businessData, int $year, int $month): array
    {
        $systemPrompt = <<<EOT
You are an expert marketing strategist for small and medium businesses in Uzbekistan.
You analyze business data and create actionable, realistic monthly marketing strategies.
Your strategies should be practical, budget-conscious, and tailored to the Uzbek market.
Respond in JSON format with clear structure.
EOT;

        $prompt = <<<EOT
Generate a comprehensive monthly marketing strategy for {$year}-{$month}.

Business Data:
{$this->formatBusinessDataForPrompt($businessData)}

Provide a detailed strategy with:
1. Executive Summary (3-4 sentences)
2. Monthly Goals (3-5 SMART goals with specific metrics)
3. Action Plan (week-by-week tasks)
4. Focus Areas (top 3 priorities)
5. Content Strategy (what to post, when, where)
6. Advertising Strategy (budget allocation, targeting, channels)
7. Channel Strategy (which channels to prioritize)
8. Sales Targets (realistic numbers based on trends)
9. Pricing Recommendations (if applicable)
10. Offer Recommendations (promotions, discounts)
11. Recommended Budget (total and breakdown by channel)
12. Predicted Metrics (expected results)
13. Confidence Score (0-100, how confident you are)

Format as JSON with these keys:
{
    "title": "Strategy title",
    "executive_summary": "Summary text",
    "goals": [{"name": "Goal name", "target": 100, "metric": "leads"}],
    "action_plan": [{"week": 1, "tasks": ["Task 1", "Task 2"]}],
    "focus_areas": ["Area 1", "Area 2"],
    "content_strategy": {"frequency": "daily", "platforms": ["instagram"], "themes": []},
    "advertising_strategy": {"total_budget": 5000000, "channels": []},
    "channel_strategy": ["instagram", "telegram"],
    "sales_targets": {"revenue": 10000000, "leads": 50, "conversions": 15},
    "pricing_recommendations": "Text or null",
    "offer_recommendations": ["Offer 1", "Offer 2"],
    "recommended_budget": 5000000,
    "budget_breakdown": {"instagram": 2000000, "telegram": 1000000},
    "predicted_metrics": {"revenue": 10000000, "leads": 50},
    "confidence_score": 85
}
EOT;

        $response = $this->complete($prompt, $systemPrompt, 4096);

        return $this->parseJsonResponse($response);
    }

    /**
     * Chat completion with conversation history
     *
     * @param array $conversationMessages Full conversation history
     * @param string|null $systemPrompt System context
     * @return string Assistant response
     */
    public function chat(array $conversationMessages, ?string $systemPrompt = null): string
    {
        $systemPrompt = $systemPrompt ?? $this->getDefaultChatSystemPrompt();

        $response = $this->sendMessage($conversationMessages, $systemPrompt);

        return $response['content'];
    }

    /**
     * Get insight generation system prompt
     */
    private function getInsightSystemPrompt(string $type): string
    {
        $basePrompt = "You are an expert business analyst specializing in marketing and sales analytics for businesses in Uzbekistan. ";

        $typePrompts = [
            'marketing' => "Focus on marketing channel performance, content engagement, and audience growth.",
            'sales' => "Focus on sales metrics, conversion rates, and revenue optimization.",
            'content' => "Focus on content performance, engagement trends, and content strategy.",
            'customer' => "Focus on customer behavior, retention, and lifetime value.",
            'competitor' => "Focus on competitive analysis and market positioning.",
            'general' => "Provide holistic business insights across all areas.",
        ];

        return $basePrompt . ($typePrompts[$type] ?? $typePrompts['general']) .
               " Provide actionable insights in Uzbek or Russian based on context. Be concise and specific.";
    }

    /**
     * Build prompt for insight generation
     */
    private function buildInsightPrompt(array $businessData, string $type): string
    {
        $dataStr = $this->formatBusinessDataForPrompt($businessData);

        return <<<EOT
Analyze the following business data and provide 1-2 key insights:

{$dataStr}

Provide insights focused on: {$type}

For each insight, include:
1. Clear title (max 10 words)
2. Description (2-3 sentences explaining what's happening)
3. Why it matters (impact on business)
4. Recommended action (1-2 specific steps to take)
5. Priority level (urgent/high/medium/low)
6. Predicted impact if action is taken
EOT;
    }

    /**
     * Format business data for prompt
     */
    private function formatBusinessDataForPrompt(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse insight response into structured data
     */
    private function parseInsightResponse(string $response, string $type): array
    {
        // Try to extract structured data from response
        // Claude usually provides well-formatted text that can be parsed

        return [
            'type' => $type,
            'title' => $this->extractTitle($response),
            'content' => $response,
            'priority' => $this->extractPriority($response),
            'sentiment' => $this->determineSentiment($response),
            'is_actionable' => str_contains(strtolower($response), 'recommend') ||
                             str_contains(strtolower($response), 'should'),
        ];
    }

    /**
     * Extract title from response
     */
    private function extractTitle(string $response): string
    {
        // Look for first line or heading
        $lines = explode("\n", $response);
        $firstLine = trim($lines[0]);

        // Remove markdown heading markers
        $firstLine = preg_replace('/^#+\s*/', '', $firstLine);

        return substr($firstLine, 0, 100);
    }

    /**
     * Extract priority from response
     */
    private function extractPriority(string $response): string
    {
        $lower = strtolower($response);

        if (str_contains($lower, 'urgent') || str_contains($lower, 'critical')) {
            return 'urgent';
        }

        if (str_contains($lower, 'high priority') || str_contains($lower, 'important')) {
            return 'high';
        }

        if (str_contains($lower, 'low priority')) {
            return 'low';
        }

        return 'medium';
    }

    /**
     * Determine sentiment from response
     */
    private function determineSentiment(string $response): string
    {
        $lower = strtolower($response);

        $positiveWords = ['growth', 'increase', 'improvement', 'success', 'positive', 'excellent', 'great'];
        $negativeWords = ['decline', 'decrease', 'loss', 'problem', 'issue', 'negative', 'concern', 'warning'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($lower, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($lower, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        }

        if ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Parse JSON from Claude response
     */
    private function parseJsonResponse(string $response): array
    {
        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/(\{.*\})/s', $response, $matches)) {
            $json = $matches[1];
        } else {
            $json = $response;
        }

        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Failed to parse JSON response from Claude', [
                'response' => $response,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get default chat system prompt
     */
    private function getDefaultChatSystemPrompt(): string
    {
        return <<<EOT
You are BiznesPilot AI, an intelligent business assistant for small and medium businesses in Uzbekistan.

Your role:
- Help business owners understand their marketing and sales data
- Provide actionable recommendations for growth
- Answer questions about strategy, marketing, sales, and business operations
- Be conversational, friendly, and supportive
- Speak in Uzbek or Russian based on user's language
- Keep responses concise and practical
- Focus on data-driven insights and actionable advice

When analyzing data, always provide:
1. Clear explanation of what the data shows
2. Why it matters for the business
3. Specific actions they can take
4. Expected results if they follow your advice

Be encouraging and supportive while maintaining professional expertise.
EOT;
    }
}
