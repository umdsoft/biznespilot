<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\InstagramAccount;
use App\Services\InstagramChatbotService;
use App\Services\InstagramDMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    protected InstagramDMService $instagramService;
    protected InstagramChatbotService $chatbotService;
    protected ?object $aiChatService = null;

    public function __construct(
        InstagramDMService $instagramService,
        InstagramChatbotService $chatbotService
    ) {
        $this->instagramService = $instagramService;
        $this->chatbotService = $chatbotService;

        // InstagramAIChatService optional - if it exists, load it
        if (class_exists(\App\Services\InstagramAIChatService::class)) {
            $this->aiChatService = app(\App\Services\InstagramAIChatService::class);
        }
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

            // POST request uchun signature verification
            if (!$this->verifySignature($request)) {
                Log::warning('Instagram webhook signature verification failed', [
                    'business_id' => $businessId,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            // Validate business
            $business = Business::findOrFail($businessId);

            // Get webhook data
            $data = $request->all();

            Log::info('Instagram webhook received', [
                'business_id' => $businessId,
                'object' => $data['object'] ?? null,
                'data' => $data,
            ]);

            // Instagram webhooks come in this format
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    // Get Instagram account by instagram_id from webhook entry
                    $instagramId = $entry['id'] ?? null;
                    $instagramAccount = $instagramId
                        ? InstagramAccount::where('instagram_id', $instagramId)->first()
                        : InstagramAccount::where('business_id', $business->id)->first();

                    // Process flow-based automations via InstagramChatbotService
                    if ($instagramAccount) {
                        $this->processFlowAutomations($entry, $instagramAccount);
                    }

                    // Also check for AI chatbot config
                    $config = ChatbotConfig::where('business_id', $business->id)->first();

                    // Process with AI if enabled
                    if ($config && ($config->ai_enabled ?? false)) {
                        $this->processWithAI($entry, $business);
                    }

                    // Also process with standard service (if config exists)
                    if ($config && $config->instagram_enabled && $config->instagram_access_token) {
                        $result = $this->instagramService->handleWebhook($entry, $business);

                        if (!$result['success']) {
                            Log::warning('Instagram webhook processing failed', [
                                'business_id' => $businessId,
                                'error' => $result['error'] ?? 'Unknown error',
                            ]);
                        }
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
     * Process flow-based automations
     */
    private function processFlowAutomations(array $entry, InstagramAccount $account): void
    {
        try {
            $messaging = $entry['messaging'] ?? [];
            $instagramId = $entry['id'] ?? null;

            foreach ($messaging as $event) {
                $senderId = $event['sender']['id'] ?? null;
                $recipientId = $event['recipient']['id'] ?? null;

                // Skip if this is our own message or echo
                if ($senderId === $account->instagram_id) {
                    continue;
                }

                // Skip echo messages (our own outgoing messages)
                if (isset($event['message']['is_echo']) && $event['message']['is_echo']) {
                    Log::debug('Skipping echo message', ['sender_id' => $senderId]);
                    continue;
                }

                // Handle text messages (DM)
                if (isset($event['message']['text']) && $senderId) {
                    $messageText = $event['message']['text'];

                    Log::info('Processing DM for flow automations', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                        'message' => $messageText,
                    ]);

                    $this->chatbotService->processWebhook([
                        'type' => 'message',
                        'recipient_id' => $recipientId ?? $account->instagram_id,
                        'sender_id' => $senderId,
                        'message' => $messageText,
                        'message_id' => $event['message']['mid'] ?? null,
                        'sender_username' => null,
                        'sender_name' => null,
                        'sender_profile_picture' => null,
                    ]);
                }

                // Handle story mentions
                elseif (isset($event['story_mention']) && $senderId) {
                    $this->chatbotService->processWebhook([
                        'type' => 'story_mention',
                        'mentioned_user_id' => $recipientId ?? $account->instagram_id,
                        'mentioner_id' => $senderId,
                        'story_id' => $event['story_mention']['id'] ?? null,
                    ]);
                }

                // Handle story replies
                elseif (isset($event['message']['reply_to']['story']) && $senderId) {
                    $this->chatbotService->processWebhook([
                        'type' => 'story_reply',
                        'story_owner_id' => $recipientId ?? $account->instagram_id,
                        'replier_id' => $senderId,
                        'reply_text' => $event['message']['text'] ?? '',
                        'story_id' => $event['message']['reply_to']['story']['id'] ?? null,
                    ]);
                }
            }

            // Handle comments (different structure)
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                if (($change['field'] ?? '') === 'comments') {
                    $value = $change['value'] ?? [];
                    $this->chatbotService->processWebhook([
                        'type' => 'comment',
                        'media_owner_id' => $account->instagram_id,
                        'media_id' => $value['media']['id'] ?? null,
                        'commenter_id' => $value['from']['id'] ?? null,
                        'commenter_username' => $value['from']['username'] ?? null,
                        'text' => $value['text'] ?? '',
                        'comment_id' => $value['id'] ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Flow automation processing error', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify webhook signature (HMAC-SHA256)
     * Meta/Facebook webhooks include X-Hub-Signature-256 header
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = config('services.instagram.app_secret', config('services.facebook.app_secret'));

        // Development/local muhitda signature verification'ni o'chirish
        if (app()->environment('local', 'development')) {
            Log::info('Instagram webhook signature verification skipped (development mode)');
            return true;
        }

        // Agar app_secret sozlanmagan bo'lsa, log qilib true qaytaramiz (development uchun)
        if (empty($appSecret)) {
            Log::warning('Instagram app_secret not configured - signature verification skipped');
            return true;
        }

        // Signature yo'q bo'lsa, rad etamiz
        if (empty($signature)) {
            Log::warning('Instagram webhook received without signature header');
            return false;
        }

        // Signature formatini tekshirish: sha256=HASH
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signature, 7); // "sha256=" ni olib tashlaymiz
        $payload = $request->getContent();
        $calculatedHash = hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedHash, $calculatedHash);
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
        // Skip if AI service is not available
        if (!$this->aiChatService) {
            Log::debug('Instagram AI service not available, skipping AI processing');
            return;
        }

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
