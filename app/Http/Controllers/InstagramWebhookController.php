<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Services\InstagramDMService;
use App\Services\InstagramAIChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    protected InstagramDMService $instagramService;
    protected InstagramAIChatService $aiChatService;

    public function __construct(
        InstagramDMService $instagramService,
        InstagramAIChatService $aiChatService
    ) {
        $this->instagramService = $instagramService;
        $this->aiChatService = $aiChatService;
    }

    /**
     * Handle incoming Instagram webhook
     */
    public function handle(Request $request, $businessId)
    {
        try {
            // Facebook webhook verification (GET request)
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            // Validate business
            $business = Business::findOrFail($businessId);

            // Validate config
            $config = ChatbotConfig::where('business_id', $business->id)->first();

            if (!$config || !$config->instagram_enabled || !$config->instagram_access_token) {
                Log::warning('Instagram webhook received but bot not configured', [
                    'business_id' => $businessId,
                ]);

                return response()->json(['error' => 'Bot not configured'], 400);
            }

            // Get webhook data
            $data = $request->all();

            Log::info('Instagram webhook received', [
                'business_id' => $businessId,
                'object' => $data['object'] ?? null,
            ]);

            // Instagram webhooks come in this format
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    // Process with AI if enabled
                    if ($config->ai_enabled ?? false) {
                        $this->processWithAI($entry, $business);
                    }

                    // Also process with standard service
                    $result = $this->instagramService->handleWebhook($entry, $business);

                    if (!$result['success']) {
                        Log::warning('Instagram webhook processing failed', [
                            'business_id' => $businessId,
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);
                    }
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Instagram webhook error', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Verify webhook (Facebook/Instagram webhook verification)
     */
    private function verifyWebhook(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        // The verify token should be set in your .env file
        $verifyToken = config('services.instagram.webhook_verify_token', 'biznespilot_webhook_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Instagram webhook verified');
            return response($challenge, 200);
        }

        Log::warning('Instagram webhook verification failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Process Instagram webhook entry with AI
     *
     * @param array $entry
     * @param Business $business
     * @return void
     */
    private function processWithAI(array $entry, Business $business): void
    {
        try {
            $messaging = $entry['messaging'] ?? [];

            foreach ($messaging as $event) {
                $senderId = $event['sender']['id'] ?? null;

                // Handle text messages
                if (isset($event['message']['text']) && $senderId) {
                    $messageText = $event['message']['text'];

                    $this->aiChatService->processIncomingMessage(
                        $business,
                        $senderId,
                        $messageText,
                        null,
                        [
                            'message_id' => $event['message']['mid'] ?? null,
                            'timestamp' => $event['timestamp'] ?? now()->timestamp,
                        ]
                    );
                }

                // Handle story replies
                elseif (isset($event['message']['reply_to']['story']) && $senderId) {
                    $storyId = $event['message']['reply_to']['story']['id'] ?? null;
                    $replyText = $event['message']['text'] ?? '';

                    if ($storyId) {
                        $this->aiChatService->handleStoryReply(
                            $business,
                            $senderId,
                            $storyId,
                            $replyText
                        );
                    }
                }

                // Handle quick replies
                elseif (isset($event['message']['quick_reply']) && $senderId) {
                    $payload = $event['message']['quick_reply']['payload'] ?? null;

                    if ($payload) {
                        $this->aiChatService->handleQuickReply(
                            $business,
                            $senderId,
                            $payload
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Instagram AI processing error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get AI chatbot configuration
     *
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAIConfig(Business $business)
    {
        $config = ChatbotConfig::firstOrCreate(
            ['business_id' => $business->id, 'channel' => 'instagram'],
            [
                'is_active' => true,
                'ai_enabled' => true,
                'auto_greet' => true,
                'greeting_message' => 'Salom! 👋 Sizga qanday yordam bera olamiz?',
                'fallback_message' => 'Kechirasiz, savol tushunilmadi. Iltimos, boshqacha so\'rang.',
                'outside_hours_message' => 'Ish vaqti tugadi. Ish kunlari 9:00-18:00 da ishlaymiz.',
                'business_hours_enabled' => false,
                'lead_auto_create' => true,
            ]
        );

        $templates = \App\Models\ChatbotTemplate::where('business_id', $business->id)
            ->where('channel', 'instagram')
            ->get(['id', 'trigger_text as trigger', 'response_text as response']);

        return response()->json([
            'success' => true,
            'config' => $config,
            'templates' => $templates,
        ]);
    }

    /**
     * Update AI chatbot configuration
     *
     * @param Request $request
     * @param Business $business
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

        $config = ChatbotConfig::updateOrCreate(
            ['business_id' => $business->id, 'channel' => 'instagram'],
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
     * @param Request $request
     * @param Business $business
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
            ->where('channel', 'instagram')
            ->delete();

        // Create new templates
        foreach ($validated['templates'] as $template) {
            \App\Models\ChatbotTemplate::create([
                'business_id' => $business->id,
                'channel' => 'instagram',
                'trigger_text' => $template['trigger'],
                'response_text' => $template['response'],
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Templates saved successfully',
        ]);
    }
}
