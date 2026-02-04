<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramDMService
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Handle incoming Instagram webhook
     */
    public function handleWebhook(array $entry, Business $business): array
    {
        try {
            $messaging = $entry['messaging'][0] ?? null;

            if (! $messaging) {
                return ['success' => false, 'message' => 'No messaging in entry'];
            }

            $senderId = $messaging['sender']['id'] ?? null;
            $message = $messaging['message'] ?? null;

            if (! $message || ! isset($message['text'])) {
                return ['success' => false, 'message' => 'No text message'];
            }

            // Process message
            $response = $this->chatbotService->processMessage(
                business: $business,
                channel: 'instagram',
                channelUserId: $senderId,
                messageContent: $message['text'],
                metadata: [
                    'message_id' => $message['mid'] ?? null,
                    'sender' => $messaging['sender'] ?? null,
                ]
            );

            if ($response['success']) {
                $this->sendMessage($business, $senderId, $response['response']);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Instagram webhook error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send message to Instagram user
     */
    public function sendMessage(Business $business, string $recipientId, string $text): bool
    {
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (! $config || ! $config->instagram_enabled || ! $config->instagram_access_token) {
            return false;
        }

        $url = "https://graph.facebook.com/" . config('services.meta.api_version', 'v21.0') . "/{$config->instagram_page_id}/messages";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$config->instagram_access_token}",
            ])->post($url, [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $text],
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Instagram send message error', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId,
            ]);

            return false;
        }
    }
}
