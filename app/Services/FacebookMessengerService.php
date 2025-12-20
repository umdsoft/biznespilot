<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookMessengerService
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Handle incoming Facebook Messenger webhook
     */
    public function handleWebhook(array $entry, Business $business): array
    {
        try {
            $messaging = $entry['messaging'][0] ?? null;

            if (!$messaging) {
                return ['success' => false, 'message' => 'No messaging in entry'];
            }

            $senderId = $messaging['sender']['id'] ?? null;
            $message = $messaging['message'] ?? null;

            if (!$message || !isset($message['text'])) {
                return ['success' => false, 'message' => 'No text message'];
            }

            // Process message
            $response = $this->chatbotService->processMessage(
                business: $business,
                channel: 'facebook',
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
            Log::error('Facebook Messenger webhook error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send message to Facebook user
     */
    public function sendMessage(Business $business, string $recipientId, string $text, array $quickReplies = []): bool
    {
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (!$config || !$config->facebook_enabled || !$config->facebook_access_token) {
            return false;
        }

        $url = "https://graph.facebook.com/v18.0/me/messages";

        $payload = [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $text],
        ];

        // Add quick replies if present
        if (!empty($quickReplies)) {
            $payload['message']['quick_replies'] = array_map(fn($reply) => [
                'content_type' => 'text',
                'title' => $reply['text'] ?? $reply['title'],
                'payload' => $reply['payload'] ?? $reply['value'],
            ], $quickReplies);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$config->facebook_access_token}",
            ])->post($url, $payload);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Facebook Messenger send error', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId,
            ]);

            return false;
        }
    }

    /**
     * Send typing indicator
     */
    public function sendTypingIndicator(Business $business, string $recipientId, bool $on = true): bool
    {
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (!$config || !$config->facebook_access_token) {
            return false;
        }

        $url = "https://graph.facebook.com/v18.0/me/messages";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$config->facebook_access_token}",
            ])->post($url, [
                'recipient' => ['id' => $recipientId],
                'sender_action' => $on ? 'typing_on' : 'typing_off',
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }
}
