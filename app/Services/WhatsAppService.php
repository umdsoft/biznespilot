<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\ChatbotConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business API Integration Service
 *
 * Integrates with WhatsApp Business API (Cloud API)
 * Features:
 * - Send/receive messages
 * - Template messages
 * - Interactive messages (buttons, lists)
 * - Media messages (images, videos, documents)
 * - Webhooks handling
 */
class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiVersion;
    protected ?string $phoneNumberId;
    protected ?string $accessToken;

    public function __construct()
    {
        $this->apiUrl = 'https://graph.facebook.com';
        $this->apiVersion = 'v18.0';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id') ?? '';
        $this->accessToken = config('services.whatsapp.access_token') ?? '';
    }

    /**
     * Send text message
     *
     * @param string $to Phone number in international format (e.g., +998901234567)
     * @param string $message Text message
     * @return array|null
     */
    public function sendTextMessage(string $to, string $message): ?array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Send template message
     *
     * @param string $to
     * @param string $templateName
     * @param string $languageCode
     * @param array $components
     * @return array|null
     */
    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $languageCode = 'en',
        array $components = []
    ): ?array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Send interactive button message
     *
     * @param string $to
     * @param string $bodyText
     * @param array $buttons [[id, title], ...]
     * @param string|null $headerText
     * @param string|null $footerText
     * @return array|null
     */
    public function sendButtonMessage(
        string $to,
        string $bodyText,
        array $buttons,
        ?string $headerText = null,
        ?string $footerText = null
    ): ?array {
        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => [
                'buttons' => array_map(function ($button, $index) {
                    return [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $button['id'] ?? "btn_$index",
                            'title' => substr($button['title'], 0, 20), // Max 20 chars
                        ],
                    ];
                }, $buttons, array_keys($buttons)),
            ],
        ];

        if ($headerText) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Send interactive list message
     *
     * @param string $to
     * @param string $bodyText
     * @param string $buttonText
     * @param array $sections [[title, rows[[id, title, description]]]]
     * @param string|null $headerText
     * @param string|null $footerText
     * @return array|null
     */
    public function sendListMessage(
        string $to,
        string $bodyText,
        string $buttonText,
        array $sections,
        ?string $headerText = null,
        ?string $footerText = null
    ): ?array {
        $interactive = [
            'type' => 'list',
            'body' => ['text' => $bodyText],
            'action' => [
                'button' => substr($buttonText, 0, 20), // Max 20 chars
                'sections' => $sections,
            ],
        ];

        if ($headerText) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Send media message (image, video, document)
     *
     * @param string $to
     * @param string $mediaType image|video|document|audio
     * @param string $mediaUrl URL or Media ID
     * @param string|null $caption
     * @param string|null $filename For documents
     * @return array|null
     */
    public function sendMediaMessage(
        string $to,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null,
        ?string $filename = null
    ): ?array {
        $media = filter_var($mediaUrl, FILTER_VALIDATE_URL)
            ? ['link' => $mediaUrl]
            : ['id' => $mediaUrl];

        if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
            $media['caption'] = $caption;
        }

        if ($filename && $mediaType === 'document') {
            $media['filename'] = $filename;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => $mediaType,
            $mediaType => $media,
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Mark message as read
     *
     * @param string $messageId
     * @return array|null
     */
    public function markAsRead(string $messageId): ?array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Handle incoming webhook
     *
     * @param array $payload
     * @param Business $business
     * @return bool
     */
    public function handleWebhook(array $payload, Business $business): bool
    {
        try {
            if (!isset($payload['entry'][0]['changes'][0]['value'])) {
                return false;
            }

            $value = $payload['entry'][0]['changes'][0]['value'];

            // Handle messages
            if (isset($value['messages'])) {
                foreach ($value['messages'] as $message) {
                    $this->processIncomingMessage($message, $value['contacts'][0] ?? [], $business);
                }
            }

            // Handle status updates
            if (isset($value['statuses'])) {
                foreach ($value['statuses'] as $status) {
                    $this->processStatusUpdate($status, $business);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'business_id' => $business->id,
            ]);
            return false;
        }
    }

    /**
     * Process incoming message
     *
     * @param array $message
     * @param array $contact
     * @param Business $business
     */
    protected function processIncomingMessage(array $message, array $contact, Business $business): void
    {
        $from = $message['from'];
        $messageId = $message['id'];
        $timestamp = $message['timestamp'];

        // Get or create customer
        $customer = $this->getOrCreateCustomer($from, $contact, $business);

        // Get or create conversation
        $conversation = ChatbotConversation::firstOrCreate(
            [
                'business_id' => $business->id,
                'platform' => 'whatsapp',
                'platform_user_id' => $from,
            ],
            [
                'customer_id' => $customer->id,
                'status' => 'active',
                'metadata' => [
                    'phone' => $from,
                    'name' => $contact['profile']['name'] ?? 'WhatsApp User',
                ],
            ]
        );

        // Extract message text based on type
        $messageText = $this->extractMessageText($message);

        // Save message
        $conversation->messages()->create([
            'message' => $messageText,
            'sender' => 'customer',
            'platform_message_id' => $messageId,
            'metadata' => [
                'type' => $message['type'],
                'timestamp' => $timestamp,
                'raw_message' => $message,
            ],
        ]);

        // Mark as read
        $this->markAsRead($messageId);

        // TODO: Process with AI chatbot and send response
    }

    /**
     * Process status update (sent, delivered, read)
     *
     * @param array $status
     * @param Business $business
     */
    protected function processStatusUpdate(array $status, Business $business): void
    {
        // Update message status in database
        Log::info('WhatsApp message status update', [
            'message_id' => $status['id'],
            'status' => $status['status'],
            'business_id' => $business->id,
        ]);
    }

    /**
     * Extract text from message based on type
     *
     * @param array $message
     * @return string
     */
    protected function extractMessageText(array $message): string
    {
        $type = $message['type'];

        switch ($type) {
            case 'text':
                return $message['text']['body'];
            case 'button':
                return $message['button']['text'];
            case 'interactive':
                return $message['interactive']['button_reply']['title'] ?? $message['interactive']['list_reply']['title'] ?? '';
            case 'image':
            case 'video':
            case 'document':
            case 'audio':
                return $message[$type]['caption'] ?? "[{$type}]";
            case 'location':
                return '[Location shared]';
            case 'contacts':
                return '[Contact shared]';
            default:
                return "[Unsupported message type: {$type}]";
        }
    }

    /**
     * Get or create customer from WhatsApp contact
     *
     * @param string $phone
     * @param array $contact
     * @param Business $business
     * @return Customer
     */
    protected function getOrCreateCustomer(string $phone, array $contact, Business $business): Customer
    {
        $customer = Customer::firstOrCreate(
            [
                'business_id' => $business->id,
                'phone' => $phone,
            ],
            [
                'name' => $contact['profile']['name'] ?? 'WhatsApp User',
                'source' => 'whatsapp',
                'data' => [
                    'whatsapp_profile' => $contact['profile'] ?? [],
                ],
            ]
        );

        return $customer;
    }

    /**
     * Send API request
     *
     * @param array $payload
     * @return array|null
     */
    protected function sendRequest(array $payload): ?array
    {
        try {
            $url = "{$this->apiUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp API exception: ' . $e->getMessage(), [
                'payload' => $payload,
            ]);
            return null;
        }
    }

    /**
     * Format phone number to international format
     *
     * @param string $phone
     * @return string
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 998 (Uzbekistan), add +
        if (str_starts_with($phone, '998')) {
            return $phone;
        }

        // If starts with 0, replace with 998
        if (str_starts_with($phone, '0')) {
            return '998' . substr($phone, 1);
        }

        // Otherwise assume it's already formatted
        return $phone;
    }

    /**
     * Verify webhook token
     *
     * @param string $mode
     * @param string $token
     * @param string $challenge
     * @return string|null
     */
    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = config('services.whatsapp.verify_token', '');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }

        return null;
    }
}
