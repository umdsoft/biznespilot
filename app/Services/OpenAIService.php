<?php

namespace App\Services;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    protected $userSettings;

    public function __construct()
    {
        $this->userSettings = UserSetting::where('user_id', Auth::id())->first();
    }

    /**
     * Generate text using OpenAI
     */
    public function generateText(string $prompt, array $context = [], int $creativity = 7): string
    {
        if (!$this->userSettings || !$this->userSettings->openai_api_key) {
            throw new \Exception('OpenAI API kaliti topilmadi.');
        }

        $apiKey = $this->userSettings->getDecryptedOpenAIKey();

        // Create a new OpenAI client with user's API key
        $client = \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();

        $model = $this->userSettings->preferred_ai_model ?? 'gpt-4';
        if (!str_contains($model, 'gpt')) {
            $model = 'gpt-4';
        }

        // Convert creativity (1-10) to temperature (0-2)
        $temperature = ($creativity / 10) * 2;

        try {
            $response = $client->chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Siz professional biznes va marketing maslahatchi sifatida harakat qilasiz. O\'zbek tilida aniq, amaliy va foydali maslahatlar berasiz.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $temperature,
                'max_tokens' => 2000,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            throw new \Exception('OpenAI API xatosi: ' . $e->getMessage());
        }
    }

    /**
     * Check if OpenAI is available for the user
     */
    public function isAvailable(): bool
    {
        return $this->userSettings && !empty($this->userSettings->openai_api_key);
    }
}
