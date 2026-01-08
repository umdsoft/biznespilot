<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Stub ClaudeAIService - AI functionality disabled
 *
 * This is a placeholder service that returns fallback responses
 * since AI features have been removed from the application.
 */
class ClaudeAIService
{
    /**
     * Complete a prompt - returns fallback message
     */
    public function complete(string $prompt, ?string $systemPrompt = null, int $maxTokens = 1024): string
    {
        Log::info('ClaudeAIService::complete called but AI is disabled');

        return json_encode([
            'error' => true,
            'message' => 'AI funksiyasi hozircha mavjud emas',
        ]);
    }

    /**
     * Chat with conversation history - returns fallback message
     */
    public function chat(array $messages, ?string $systemPrompt = null, int $maxTokens = 1024): string
    {
        Log::info('ClaudeAIService::chat called but AI is disabled');

        return 'AI funksiyasi hozircha mavjud emas. Iltimos keyinroq urinib ko\'ring.';
    }

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * Get service status
     */
    public function getStatus(): array
    {
        return [
            'available' => false,
            'reason' => 'AI features are currently disabled',
        ];
    }
}
