<?php

namespace App\Services;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ClaudeService
{
    protected $userSettings;
    protected $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->userSettings = UserSetting::where('user_id', Auth::id())->first();
    }

    /**
     * Generate text using Claude (Anthropic)
     */
    public function generateText(string $prompt, array $context = [], int $creativity = 7): string
    {
        if (!$this->userSettings || !$this->userSettings->claude_api_key) {
            throw new \Exception('Claude API kaliti topilmadi.');
        }

        $apiKey = $this->userSettings->getDecryptedClaudeKey();
        $model = $this->userSettings->preferred_ai_model ?? 'claude-3-sonnet-20240229';

        if (!str_contains($model, 'claude')) {
            $model = 'claude-3-sonnet-20240229';
        } elseif ($model === 'claude-3-opus') {
            $model = 'claude-3-opus-20240229';
        } elseif ($model === 'claude-3-sonnet') {
            $model = 'claude-3-sonnet-20240229';
        }

        // Convert creativity (1-10) to temperature (0-1)
        $temperature = $creativity / 10;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => $model,
                'max_tokens' => 2000,
                'temperature' => $temperature,
                'system' => 'Siz professional biznes va marketing maslahatchi sifatida harakat qilasiz. O\'zbek tilida aniq, amaliy va foydali maslahatlar berasiz.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if ($response->failed()) {
                throw new \Exception('Claude API xatosi: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['content'][0]['text'])) {
                throw new \Exception('Claude API javobida xatolik.');
            }

            return $data['content'][0]['text'];
        } catch (\Exception $e) {
            throw new \Exception('Claude API xatosi: ' . $e->getMessage());
        }
    }

    /**
     * Check if Claude is available for the user
     */
    public function isAvailable(): bool
    {
        return $this->userSettings && !empty($this->userSettings->claude_api_key);
    }
}
