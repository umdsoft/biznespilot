<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\WhatsAppAIChatService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppService $whatsappService;

    protected WhatsAppAIChatService $aiChatService;

    public function __construct(
        WhatsAppService $whatsappService,
        WhatsAppAIChatService $aiChatService
    ) {
        $this->whatsappService = $whatsappService;
        $this->aiChatService = $aiChatService;
    }

    /**
     * Handle WhatsApp webhook (GET for verification, POST for messages)
     *
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function handle(Request $request, Business $business)
    {
        // GET request - Webhook verification
        if ($request->isMethod('get')) {
            return $this->verifyWebhook($request);
        }

        // POST request - Handle incoming messages/status updates
        return $this->handleIncomingWebhook($request, $business);
    }

    /**
     * Verify webhook (called by WhatsApp during setup)
     *
     * @return string|\Illuminate\Http\JsonResponse
     */
    protected function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::info('WhatsApp webhook verification attempt', [
            'mode' => $mode,
            'token' => $token,
        ]);

        $verifiedChallenge = $this->whatsappService->verifyWebhook($mode, $token, $challenge);

        if ($verifiedChallenge) {
            Log::info('WhatsApp webhook verified successfully');

            return response($verifiedChallenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed');

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * Handle incoming webhook data with AI processing
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleIncomingWebhook(Request $request, Business $business)
    {
        // SECURITY: Verify WhatsApp webhook signature (X-Hub-Signature-256)
        if (! $this->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'business_id' => $business->id,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();

        Log::info('WhatsApp webhook received', [
            'business_id' => $business->id,
            'payload_type' => $payload['object'] ?? 'unknown',
        ]);

        // Validate payload structure
        if (! isset($payload['object']) || $payload['object'] !== 'whatsapp_business_account') {
            Log::warning('Invalid WhatsApp webhook payload', ['payload' => $payload]);

            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        // Process webhook with AI
        try {
            // Extract message data from payload
            $entry = $payload['entry'][0] ?? null;
            $changes = $entry['changes'][0] ?? null;
            $value = $changes['value'] ?? null;
            $messages = $value['messages'] ?? [];

            // Process each incoming message with AI
            foreach ($messages as $message) {
                $from = $message['from'] ?? null;
                $messageType = $message['type'] ?? 'text';

                // Only process text messages with AI
                if ($messageType === 'text' && $from) {
                    $messageContent = $message['text']['body'] ?? '';
                    $senderName = $value['contacts'][0]['profile']['name'] ?? null;

                    // Process with AI chatbot
                    $this->aiChatService->processIncomingMessage(
                        $business,
                        $from,
                        $messageContent,
                        $senderName,
                        [
                            'message_id' => $message['id'],
                            'timestamp' => $message['timestamp'] ?? now()->timestamp,
                        ]
                    );
                } elseif ($messageType === 'interactive' && $from) {
                    // Handle button replies
                    $buttonReply = $message['interactive']['button_reply'] ?? null;
                    if ($buttonReply) {
                        $this->aiChatService->handleQuickReply(
                            $business,
                            $from,
                            $buttonReply['id']
                        );
                    }
                }
            }

            // Also handle with standard webhook processor (for status updates, etc.)
            $this->whatsappService->handleWebhook($payload, $business);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Always return 200 to WhatsApp to prevent retries
            return response()->json(['status' => 'error', 'message' => 'Internal error'], 200);
        }
    }

    /**
     * Send test message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestMessage(Request $request, Business $business)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $result = $this->whatsappService->sendTextMessage(
                $validated['phone'],
                $validated['message']
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent successfully',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test message',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send template message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTemplate(Request $request, Business $business)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'template_name' => 'required|string',
            'language_code' => 'nullable|string',
            'components' => 'nullable|array',
        ]);

        try {
            $result = $this->whatsappService->sendTemplateMessage(
                $validated['phone'],
                $validated['template_name'],
                $validated['language_code'] ?? 'en',
                $validated['components'] ?? []
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template message sent successfully',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send template message',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send interactive button message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendButtons(Request $request, Business $business)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'body_text' => 'required|string',
            'buttons' => 'required|array|min:1|max:3',
            'buttons.*.id' => 'required|string',
            'buttons.*.title' => 'required|string|max:20',
            'header_text' => 'nullable|string',
            'footer_text' => 'nullable|string',
        ]);

        try {
            $result = $this->whatsappService->sendButtonMessage(
                $validated['phone'],
                $validated['body_text'],
                $validated['buttons'],
                $validated['header_text'] ?? null,
                $validated['footer_text'] ?? null
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Button message sent successfully',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send button message',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send media message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMedia(Request $request, Business $business)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'media_type' => 'required|in:image,video,document,audio',
            'media_url' => 'required|string',
            'caption' => 'nullable|string',
            'filename' => 'nullable|string',
        ]);

        try {
            $result = $this->whatsappService->sendMediaMessage(
                $validated['phone'],
                $validated['media_type'],
                $validated['media_url'],
                $validated['caption'] ?? null,
                $validated['filename'] ?? null
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Media message sent successfully',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send media message',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get webhook info and setup instructions
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWebhookInfo(Business $business)
    {
        $webhookUrl = route('webhooks.whatsapp', ['business' => $business->id]);
        $verifyToken = config('services.whatsapp.verify_token', 'your-verify-token');

        return response()->json([
            'success' => true,
            'webhook_url' => $webhookUrl,
            'verify_token' => $verifyToken,
            'setup_instructions' => [
                '1. Go to Meta Business Suite > WhatsApp > Configuration',
                '2. Click "Edit" on Webhooks',
                '3. Enter Callback URL: '.$webhookUrl,
                '4. Enter Verify Token: '.$verifyToken,
                '5. Subscribe to: messages, message_status',
                '6. Click "Verify and Save"',
            ],
        ]);
    }

    /**
     * Get AI chatbot configuration
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAIConfig(Business $business)
    {
        $config = \App\Models\ChatbotConfig::firstOrCreate(
            ['business_id' => $business->id, 'channel' => 'whatsapp'],
            [
                'is_active' => true,
                'ai_enabled' => true,
                'auto_greet' => true,
                'greeting_message' => 'Assalomu alaykum! Bizga xush kelibsiz. Sizga qanday yordam bera olamiz?',
                'fallback_message' => 'Kechirasiz, savol tushunilmadi. Iltimos, boshqacha so\'rang.',
                'outside_hours_message' => 'Ish vaqti tugadi. Ish kunlari 9:00-18:00 da ishlaymiz.',
                'business_hours_enabled' => false,
                'lead_auto_create' => true,
            ]
        );

        $templates = \App\Models\ChatbotTemplate::where('business_id', $business->id)
            ->where('category', 'whatsapp')
            ->get(['id', 'name as trigger', 'content as response']);

        return response()->json([
            'success' => true,
            'config' => $config,
            'templates' => $templates,
        ]);
    }

    /**
     * Update AI chatbot configuration
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAIConfig(Request $request, Business $business)
    {
        $validated = $request->validate([
            'is_active' => 'boolean',
            'ai_enabled' => 'boolean',
            'auto_greet' => 'boolean',
            'greeting_message' => 'nullable|string',
            'fallback_message' => 'nullable|string',
            'outside_hours_message' => 'nullable|string',
            'business_hours_enabled' => 'boolean',
            'business_hours_start' => 'nullable|string',
            'business_hours_end' => 'nullable|string',
            'lead_auto_create' => 'boolean',
            'ai_creativity_level' => 'integer|min:1|max:10',
            'use_dream_buyer_context' => 'boolean',
            'use_offer_context' => 'boolean',
        ]);

        $config = \App\Models\ChatbotConfig::updateOrCreate(
            ['business_id' => $business->id, 'platform' => 'whatsapp'],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'config' => $config,
        ]);
    }

    /**
     * Save AI templates
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAITemplates(Request $request, Business $business)
    {
        $validated = $request->validate([
            'templates' => 'required|array',
            'templates.*.trigger' => 'required|string',
            'templates.*.response' => 'required|string',
        ]);

        // Delete old templates
        \App\Models\ChatbotTemplate::where('business_id', $business->id)
            ->where('category', 'whatsapp')
            ->delete();

        // Create new templates
        foreach ($validated['templates'] as $template) {
            \App\Models\ChatbotTemplate::create([
                'business_id' => $business->id,
                'category' => 'whatsapp',
                'name' => $template['trigger'],
                'content' => $template['response'],
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Templates saved successfully',
        ]);
    }

    /**
     * Verify WhatsApp webhook signature (HMAC-SHA256)
     *
     * SECURITY: WhatsApp sends X-Hub-Signature-256 header with HMAC signature
     * Uses the same mechanism as Facebook webhooks
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            // Allow if no signature header (for backwards compatibility during setup)
            // In production, you should return false here
            Log::warning('WhatsApp webhook received without signature header');

            return true; // Change to false in production after setup
        }

        $appSecret = config('services.whatsapp.app_secret') ?? config('services.facebook.app_secret');

        if (! $appSecret) {
            Log::warning('WhatsApp app secret not configured');

            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
