<?php

namespace App\Services\AI;

/**
 * AI javob formati — barcha AI chaqiriqlar natijasi shu formatda qaytadi.
 * Keshdan, qoidadan yoki API dan kelgan javoblarni yagona formatga keltiradi.
 */
class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $tokensInput,
        public readonly int $tokensOutput,
        public readonly float $costUsd,
        public readonly string $source, // 'cache', 'rule', 'api'
        public readonly int $processingTimeMs = 0,
        public readonly bool $success = true,
        public readonly ?string $error = null,
    ) {}

    /**
     * Keshdan kelgan javob
     */
    public static function fromCache(string $content): self
    {
        return new self(
            content: $content,
            model: 'none',
            tokensInput: 0,
            tokensOutput: 0,
            costUsd: 0,
            source: 'cache',
        );
    }

    /**
     * Qoidaga asoslangan javob (AI chaqirilmaydi)
     */
    public static function fromRule(string $content): self
    {
        return new self(
            content: $content,
            model: 'none',
            tokensInput: 0,
            tokensOutput: 0,
            costUsd: 0,
            source: 'rule',
        );
    }

    /**
     * Bazadan kelgan javob (AI chaqirilmaydi)
     */
    public static function fromDatabase(string $content): self
    {
        return new self(
            content: $content,
            model: 'none',
            tokensInput: 0,
            tokensOutput: 0,
            costUsd: 0,
            source: 'database',
        );
    }

    /**
     * Claude API dan kelgan javob
     */
    public static function fromAPI(array $responseData, string $model, int $processingTimeMs = 0): self
    {
        $content = $responseData['content'][0]['text'] ?? '';
        $inputTokens = $responseData['usage']['input_tokens'] ?? 0;
        $outputTokens = $responseData['usage']['output_tokens'] ?? 0;

        return new self(
            content: $content,
            model: $model,
            tokensInput: $inputTokens,
            tokensOutput: $outputTokens,
            costUsd: self::calculateCost($model, $inputTokens, $outputTokens),
            source: 'api',
            processingTimeMs: $processingTimeMs,
        );
    }

    /**
     * Xatolik javob
     */
    public static function error(string $errorMessage): self
    {
        return new self(
            content: 'Kechirasiz, texnik muammo yuz berdi. Iltimos qayta urinib ko\'ring.',
            model: 'none',
            tokensInput: 0,
            tokensOutput: 0,
            costUsd: 0,
            source: 'error',
            success: false,
            error: $errorMessage,
        );
    }

    /**
     * Model va tokenlar asosida xarajatni hisoblash (USD)
     * Narxlar: https://docs.anthropic.com/en/docs/about-claude/pricing
     */
    public static function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        // Haiku 4.5: $0.80/1M input, $4.00/1M output
        // Sonnet 4.6: $3.00/1M input, $15.00/1M output
        $pricing = match (true) {
            str_contains($model, 'haiku') => ['input' => 0.80, 'output' => 4.00],
            str_contains($model, 'sonnet') => ['input' => 3.00, 'output' => 15.00],
            default => ['input' => 0.80, 'output' => 4.00], // default Haiku narxi
        };

        return ($inputTokens * $pricing['input'] / 1_000_000)
             + ($outputTokens * $pricing['output'] / 1_000_000);
    }

    /**
     * Massivga aylantirish (saqlash uchun)
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'tokens_input' => $this->tokensInput,
            'tokens_output' => $this->tokensOutput,
            'cost_usd' => $this->costUsd,
            'source' => $this->source,
            'processing_time_ms' => $this->processingTimeMs,
            'success' => $this->success,
            'error' => $this->error,
        ];
    }
}
