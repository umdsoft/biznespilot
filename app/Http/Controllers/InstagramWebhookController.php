<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\InstagramAccount;
use App\Services\ChatbotIntentService;
use App\Services\InstagramChatbotService;
use App\Services\InstagramDMService;
use App\Services\SocialChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * InstagramWebhookController - Instagram Webhook Handler
 *
 * Bu controller Instagram/Meta dan kelgan barcha webhook eventlarini qabul qiladi:
 * - Direct Messages (DM)
 * - Story Mentions
 * - Story Replies
 * - Comments
 * - Quick Reply (tugma bosilishi)
 *
 * Flow:
 * 1. Webhook signature tekshirish (HMAC-SHA256)
 * 2. Business va Account validatsiya
 * 3. Intent aniqlash (ChatbotIntentService)
 * 4. Flow bajarish (SocialChatbotService)
 */
class InstagramWebhookController extends Controller
{
    protected InstagramDMService $instagramService;

    protected InstagramChatbotService $chatbotService;

    protected SocialChatbotService $socialChatbotService;

    protected ChatbotIntentService $intentService;

    protected ?object $aiChatService = null;

    public function __construct(
        InstagramDMService $instagramService,
        InstagramChatbotService $chatbotService,
        SocialChatbotService $socialChatbotService,
        ChatbotIntentService $intentService
    ) {
        $this->instagramService = $instagramService;
        $this->chatbotService = $chatbotService;
        $this->socialChatbotService = $socialChatbotService;
        $this->intentService = $intentService;

        // InstagramAIChatService optional - if it exists, load it
        if (class_exists(\App\Services\InstagramAIChatService::class)) {
            $this->aiChatService = app(\App\Services\InstagramAIChatService::class);
        }
    }

    /**
     * Universal webhook handler - Meta faqat bitta URL ga webhook yuboradi
     * entry.id (Instagram Page ID) orqali qaysi biznesga tegishli ekanini aniqlaydi
     *
     * Facebook Developer → Webhooks → Callback URL: https://domain.com/webhooks/instagram
     */
    public function handleUniversal(Request $request)
    {
        try {
            // Facebook webhook verification (GET request)
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            // POST request uchun signature verification
            if (! $this->verifySignature($request)) {
                Log::warning('Instagram webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->all();

            Log::info('Instagram universal webhook received', [
                'object' => $data['object'] ?? null,
                'entry_count' => isset($data['entry']) ? count($data['entry']) : 0,
            ]);

            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $this->processEntry($entry);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Instagram universal webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Legacy: business-specific webhook handler (backward compatibility)
     */
    public function handle(Request $request, $businessId)
    {
        try {
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            if (! $this->verifySignature($request)) {
                Log::warning('Instagram webhook signature verification failed', [
                    'business_id' => $businessId,
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $business = Business::findOrFail($businessId);
            $data = $request->all();

            Log::info('Instagram webhook received (legacy route)', [
                'business_id' => $businessId,
                'object' => $data['object'] ?? null,
            ]);

            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $this->processEntry($entry, $business);
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
     * Process a single webhook entry
     * Instagram Page ID (entry.id) orqali InstagramAccount va Business ni topadi
     *
     * @param  array  $entry  Webhook entry data
     * @param  Business|null  $business  Legacy route dan kelsa, business beriladi
     */
    private function processEntry(array $entry, ?Business $business = null): void
    {
        $instagramId = $entry['id'] ?? null;

        // 1. InstagramAccount ni topish (entry.id = Instagram Page ID)
        $instagramAccount = null;
        if ($instagramId) {
            $instagramAccount = InstagramAccount::where('instagram_id', $instagramId)->first();
        }

        // 2. Agar account topilmasa va business berilgan bo'lsa (legacy route) — fallback
        if (! $instagramAccount && $business) {
            $instagramAccount = InstagramAccount::where('business_id', $business->id)->first();
        }

        // 3. Business ni aniqlash — InstagramAccount dan olish
        if (! $business && $instagramAccount) {
            $business = $instagramAccount->business;
        }

        // 4. Agar hech narsa topilmasa — log qilib skip
        if (! $instagramAccount && ! $business) {
            Log::warning('Instagram webhook: unknown instagram_id, no matching business', [
                'instagram_id' => $instagramId,
                'entry_keys' => array_keys($entry),
            ]);

            return;
        }

        $businessId = $business?->id;

        Log::info('Instagram webhook entry processing', [
            'instagram_id' => $instagramId,
            'business_id' => $businessId,
            'account_id' => $instagramAccount?->id,
        ]);

        // Process with SocialChatbotService (primary handler)
        if ($instagramAccount) {
            $this->processWithSocialChatbot($entry, $instagramAccount);
        }

        // AI chatbot + legacy processing
        if ($business) {
            $config = ChatbotConfig::where('business_id', $business->id)->first();

            if ($config && ($config->ai_enabled ?? false)) {
                $this->processWithAI($entry, $business);
            }

            if ($config && $config->instagram_enabled && $config->instagram_access_token) {
                $result = $this->instagramService->handleWebhook($entry, $business);

                if (! $result['success']) {
                    Log::warning('Instagram webhook processing failed', [
                        'business_id' => $businessId,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            }
        }
    }

    /**
     * Process with new SocialChatbotService (primary handler)
     * Intent detection + Flow execution
     *
     * IDEMPOTENCY: Har bir xabar faqat 1 marta qayta ishlanadi (60 soniya ichida)
     */
    private function processWithSocialChatbot(array $entry, InstagramAccount $account): void
    {
        try {
            $messaging = $entry['messaging'] ?? [];

            foreach ($messaging as $event) {
                $senderId = $event['sender']['id'] ?? null;
                $recipientId = $event['recipient']['id'] ?? null;
                $messageId = $event['message']['mid'] ?? null;

                // Skip if this is our own message or echo
                if ($senderId === $account->instagram_id) {
                    continue;
                }

                // Skip echo messages (our own outgoing messages)
                if (isset($event['message']['is_echo']) && $event['message']['is_echo']) {
                    Log::debug('Skipping echo message', ['sender_id' => $senderId]);

                    continue;
                }

                // ========================================
                // IDEMPOTENCY CHECK - Dublikatlarni oldini olish
                // ========================================
                if ($messageId) {
                    $cacheKey = "instagram_processed_message_{$messageId}";

                    // Agar bu xabar allaqachon qayta ishlangan bo'lsa - skip
                    if (Cache::has($cacheKey)) {
                        Log::debug('Skipping duplicate message', [
                            'message_id' => $messageId,
                            'sender_id' => $senderId,
                        ]);

                        continue;
                    }

                    // Xabarni qayta ishlangan deb belgilash (60 soniya)
                    Cache::put($cacheKey, true, 60);
                }

                // Build webhook data
                $webhookData = [
                    'type' => 'message',
                    'recipient_id' => $recipientId ?? $account->instagram_id,
                    'sender_id' => $senderId,
                    'message_id' => $event['message']['mid'] ?? null,
                    'sender_username' => null,
                    'sender_name' => null,
                    'sender_profile_picture' => null,
                ];

                // Handle Quick Reply (tugma bosildi)
                if (isset($event['message']['quick_reply'])) {
                    $payload = $event['message']['quick_reply']['payload'] ?? null;
                    $webhookData['payload'] = $payload;
                    $webhookData['message'] = $event['message']['text'] ?? $payload ?? '';
                    $webhookData['type'] = 'quick_reply';

                    Log::info('Quick Reply received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                        'payload' => $payload,
                    ]);

                    $this->socialChatbotService->processWebhook($webhookData, $account);

                    continue;
                }

                // Handle Postback (button click from templates)
                if (isset($event['postback'])) {
                    $payload = $event['postback']['payload'] ?? null;
                    $webhookData['payload'] = $payload;
                    $webhookData['message'] = $event['postback']['title'] ?? $payload ?? '';
                    $webhookData['type'] = 'postback';

                    Log::info('Postback received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                        'payload' => $payload,
                    ]);

                    $this->socialChatbotService->processWebhook($webhookData, $account);

                    continue;
                }

                // Handle text messages (DM)
                if (isset($event['message']['text']) && $senderId) {
                    $messageText = $event['message']['text'];
                    $webhookData['message'] = $messageText;

                    Log::info('DM received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                        'message' => mb_substr($messageText, 0, 100),
                    ]);

                    $this->socialChatbotService->processWebhook($webhookData, $account);

                    continue;
                }

                // Handle attachments (media)
                if (isset($event['message']['attachments']) && $senderId) {
                    $attachments = $event['message']['attachments'];
                    $webhookData['type'] = 'attachment';
                    $webhookData['attachments'] = $attachments;
                    $webhookData['message'] = '[Media]';

                    Log::info('Attachment received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                        'attachment_count' => count($attachments),
                    ]);

                    // Media xabarlarni ham process qilish
                    $this->socialChatbotService->processWebhook($webhookData, $account);

                    continue;
                }

                // Handle story mentions
                if (isset($event['story_mention']) && $senderId) {
                    $webhookData['type'] = 'story_mention';
                    $webhookData['message'] = '[Story Mention]';
                    $webhookData['story_id'] = $event['story_mention']['id'] ?? null;

                    Log::info('Story mention received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                    ]);

                    $this->socialChatbotService->processWebhook($webhookData, $account);

                    continue;
                }

                // Handle story replies
                if (isset($event['message']['reply_to']['story']) && $senderId) {
                    $webhookData['type'] = 'story_reply';
                    $webhookData['message'] = $event['message']['text'] ?? '[Story Reply]';
                    $webhookData['story_id'] = $event['message']['reply_to']['story']['id'] ?? null;

                    Log::info('Story reply received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                    ]);

                    $this->socialChatbotService->processWebhook($webhookData, $account);

                    continue;
                }

                // Handle reactions
                if (isset($event['reaction']) && $senderId) {
                    $reaction = $event['reaction'];
                    $webhookData['type'] = 'reaction';
                    $webhookData['message'] = $reaction['emoji'] ?? $reaction['reaction'] ?? '❤️';
                    $webhookData['reaction_action'] = $reaction['action'] ?? 'react'; // react or unreact

                    Log::info('Reaction received', [
                        'account_id' => $account->id,
                        'sender_id' => $senderId,
                        'reaction' => $webhookData['message'],
                    ]);

                    // Reactions ga odatda javob bermaymiz, lekin log qilamiz
                    continue;
                }
            }

            // Handle comments (different structure in webhook)
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                if (($change['field'] ?? '') === 'comments') {
                    $value = $change['value'] ?? [];

                    $webhookData = [
                        'type' => 'comment',
                        'sender_id' => $value['from']['id'] ?? null,
                        'sender_username' => $value['from']['username'] ?? null,
                        'message' => $value['text'] ?? '',
                        'media_id' => $value['media']['id'] ?? null,
                        'comment_id' => $value['id'] ?? null,
                    ];

                    Log::info('Comment received', [
                        'account_id' => $account->id,
                        'commenter' => $webhookData['sender_username'],
                        'text' => mb_substr($webhookData['message'], 0, 100),
                    ]);

                    // Comments uchun ham flow trigger qilish mumkin
                    // Lekin hozircha faqat keyword-based automations ishlaydi
                    $this->chatbotService->processWebhook([
                        'type' => 'comment',
                        'media_owner_id' => $account->instagram_id,
                        'media_id' => $webhookData['media_id'],
                        'commenter_id' => $webhookData['sender_id'],
                        'commenter_username' => $webhookData['sender_username'],
                        'text' => $webhookData['message'],
                        'comment_id' => $webhookData['comment_id'],
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('SocialChatbot processing error', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        if (! str_starts_with($signature, 'sha256=')) {
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
     */
    private function processWithAI(array $entry, Business $business): void
    {
        // Skip if AI service is not available
        if (! $this->aiChatService) {
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

                // Handle quick replies - AI service ham process qilishi mumkin
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
            ->where('category', 'instagram')
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

        $config = ChatbotConfig::updateOrCreate(
            ['business_id' => $business->id, 'platform' => 'instagram'],
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
            ->where('category', 'instagram')
            ->delete();

        // Create new templates
        foreach ($validated['templates'] as $template) {
            \App\Models\ChatbotTemplate::create([
                'business_id' => $business->id,
                'category' => 'instagram',
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
     * Test webhook endpoint (for debugging)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testWebhook(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);
        $account = InstagramAccount::where('business_id', $business->id)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'error' => 'Instagram account not found for this business',
            ], 404);
        }

        // Simulate a test message
        $testMessage = $request->input('message', 'Test message');
        $testSenderId = $request->input('sender_id', 'test_user_123');

        $webhookData = [
            'type' => 'message',
            'sender_id' => $testSenderId,
            'message' => $testMessage,
            'message_id' => 'test_' . uniqid(),
        ];

        // Detect intent
        $intent = $this->intentService->detect(
            $testMessage,
            $account,
            null,
            []
        );

        return response()->json([
            'success' => true,
            'test_message' => $testMessage,
            'detected_intent' => $intent,
            'would_trigger_flow' => $this->intentService->shouldTriggerFlow($intent),
            'needs_human' => $this->intentService->needsHumanHandoff($intent),
        ]);
    }
}
