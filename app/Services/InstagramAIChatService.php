<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

/**
 * Instagram AI Chat Service
 *
 * Integrates Instagram Direct Messages with AI-powered chatbot functionality.
 * Provides context-aware responses using DreamBuyer and Offer data.
 */
class InstagramAIChatService
{
    protected ChatbotService $chatbotService;
    protected InstagramDMService $instagramService;
    protected ClaudeAIService $claudeAI;

    public function __construct(
        ChatbotService $chatbotService,
        InstagramDMService $instagramService,
        ClaudeAIService $claudeAI
    ) {
        $this->chatbotService = $chatbotService;
        $this->instagramService = $instagramService;
        $this->claudeAI = $claudeAI;
    }

    /**
     * Process incoming Instagram DM with AI
     *
     * @param Business $business
     * @param string $senderId Instagram user ID
     * @param string $messageContent Message text
     * @param string|null $senderUsername Instagram username
     * @param array $metadata Additional metadata
     * @return array Processing result
     */
    public function processIncomingMessage(
        Business $business,
        string $senderId,
        string $messageContent,
        ?string $senderUsername = null,
        array $metadata = []
    ): array {
        try {
            Log::info('Instagram AI: Processing message', [
                'business_id' => $business->id,
                'sender_id' => $senderId,
                'message_preview' => substr($messageContent, 0, 50),
            ]);

            // Get or create customer
            $customer = $this->getOrCreateCustomer($business, $senderId, $senderUsername);

            // Add business context to metadata
            $metadata = array_merge($metadata, [
                'channel' => 'instagram',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);

            // Process message through chatbot service
            $result = $this->chatbotService->processMessage(
                $business,
                'instagram',
                $senderId,
                $messageContent,
                $senderUsername,
                $metadata
            );

            if (!$result['success']) {
                // Fallback to simple response
                return $this->sendFallbackResponse($business, $senderId, $messageContent);
            }

            // Send AI response via Instagram DM
            $sendResult = $this->sendInstagramResponse(
                $senderId,
                $result['response'],
                $result['attachments'] ?? []
            );

            return [
                'success' => true,
                'response_sent' => $sendResult !== null,
                'conversation_id' => $result['conversation_id'],
                'intent' => $result['intent'],
                'stage' => $result['stage'],
            ];

        } catch (\Exception $e) {
            Log::error('Instagram AI processing error', [
                'business_id' => $business->id,
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);

            // Send error fallback
            $this->instagramService->sendMessage(
                $senderId,
                "Kechirasiz, xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring."
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send context-aware AI response
     *
     * @param Business $business
     * @param string $recipientId Instagram user ID
     * @param string $userMessage User's message
     * @param array $context Additional context
     * @return array|null
     */
    public function sendContextAwareResponse(
        Business $business,
        string $recipientId,
        string $userMessage,
        array $context = []
    ): ?array {
        // Build context with DreamBuyer and Offer data
        $enrichedContext = $this->buildBusinessContext($business, $context);

        // Generate AI response
        $aiResponse = $this->generateAIResponse($business, $userMessage, $enrichedContext);

        // Send via Instagram
        return $this->instagramService->sendMessage($recipientId, $aiResponse);
    }

    /**
     * Generate AI response with business context
     *
     * @param Business $business
     * @param string $userMessage
     * @param array $context
     * @return string
     */
    protected function generateAIResponse(
        Business $business,
        string $userMessage,
        array $context = []
    ): string {
        // Build system prompt with business context
        $systemPrompt = $this->buildSystemPrompt($business, $context);

        // Build conversation messages
        $messages = [
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
        ];

        // Add context messages if available
        if (!empty($context['conversation_history'])) {
            $messages = array_merge($context['conversation_history'], $messages);
        }

        try {
            $response = $this->claudeAI->sendMessage(
                $messages,
                $systemPrompt,
                1024
            );

            return $response['content'] ?? "Kechirasiz, javob topa olmadim. Boshqa savol bering.";

        } catch (\Exception $e) {
            Log::error('AI response generation failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return "Kechirasiz, hozir javob berishda muammo bor. Iltimos, keyinroq urinib ko'ring.";
        }
    }

    /**
     * Build system prompt with business context
     *
     * @param Business $business
     * @param array $context
     * @return string
     */
    protected function buildSystemPrompt(Business $business, array $context = []): string
    {
        $prompt = "Siz {$business->name} biznesining professional Instagram DM yordamchisisiz.\n\n";

        // Add business description
        if ($business->description) {
            $prompt .= "Biznes haqida: {$business->description}\n\n";
        }

        // Add DreamBuyer context
        if (!empty($context['dream_buyer'])) {
            $dreamBuyer = $context['dream_buyer'];
            $demographics = $dreamBuyer['demographics'] ?? 'N/A';
            $problems = $dreamBuyer['problems'] ?? 'N/A';
            $desires = $dreamBuyer['desires'] ?? 'N/A';
            $prompt .= "Ideal Mijoz Profili:\n";
            $prompt .= "- Demografiya: {$demographics}\n";
            $prompt .= "- Muammolar: {$problems}\n";
            $prompt .= "- Xohishlar: {$desires}\n\n";
        }

        // Add active offers context
        if (!empty($context['offers'])) {
            $prompt .= "Faol Takliflar:\n";
            foreach ($context['offers'] as $offer) {
                $offerName = $offer['name'] ?? '';
                $valueProposition = $offer['value_proposition'] ?? '';
                $prompt .= "- {$offerName}: {$valueProposition}\n";
            }
            $prompt .= "\n";
        }

        // Add chatbot personality for Instagram
        $prompt .= "Siz:\n";
        $prompt .= "- Do'stona, samimiy va zamonaviy (Instagram audience uchun)\n";
        $prompt .= "- Mijoz muammolarini tushunasiz va hal qilasiz\n";
        $prompt .= "- Qisqa va aniq javoblar berasiz (Instagram DM uchun mos)\n";
        $prompt .= "- Emoji ishlatishingiz mumkin (lekin ortiqcha emas)\n";
        $prompt .= "- O'zbek tilida javob berasiz\n\n";

        $prompt .= "Vazifa: Mijoz savollariga professional javob bering va biznesga qiziqtiring.";

        return $prompt;
    }

    /**
     * Build business context with DreamBuyer and Offer data
     *
     * @param Business $business
     * @param array $additionalContext
     * @return array
     */
    protected function buildBusinessContext(Business $business, array $additionalContext = []): array
    {
        $context = $additionalContext;

        // Get primary DreamBuyer
        $dreamBuyer = DreamBuyer::where('business_id', $business->id)
            ->where('is_primary', true)
            ->first();

        if ($dreamBuyer) {
            $context['dream_buyer'] = [
                'name' => $dreamBuyer->name,
                'demographics' => $dreamBuyer->demographics,
                'problems' => $dreamBuyer->problems,
                'desires' => $dreamBuyer->desires,
                'pain_level' => $dreamBuyer->pain_level,
            ];
        }

        // Get active offers
        $offers = Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->limit(3)
            ->get(['id', 'name', 'value_proposition', 'price']);

        if ($offers->isNotEmpty()) {
            $context['offers'] = $offers->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'name' => $offer->name,
                    'value_proposition' => $offer->value_proposition,
                    'price' => $offer->price,
                ];
            })->toArray();
        }

        return $context;
    }

    /**
     * Get or create customer from Instagram contact
     *
     * @param Business $business
     * @param string $instagramId
     * @param string|null $username
     * @return Customer
     */
    protected function getOrCreateCustomer(
        Business $business,
        string $instagramId,
        ?string $username = null
    ): Customer {
        // Try to find by Instagram ID in metadata or tags
        $customer = Customer::where('business_id', $business->id)
            ->where(function ($query) use ($instagramId, $username) {
                $query->where('phone', $instagramId) // Using phone field for IG ID
                    ->orWhere('email', $username ? "{$username}@instagram" : null);
            })
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'business_id' => $business->id,
                'name' => $username ?? "Instagram User",
                'phone' => $instagramId, // Store IG ID in phone field
                'email' => $username ? "{$username}@instagram" : null,
                'source' => 'instagram',
                'tags' => ['instagram', 'dm'],
            ]);

            Log::info('Created new customer from Instagram', [
                'customer_id' => $customer->id,
                'instagram_id' => $instagramId,
            ]);
        }

        return $customer;
    }

    /**
     * Send Instagram response with attachments
     *
     * @param string $recipientId
     * @param string $message
     * @param array $attachments
     * @return array|null
     */
    protected function sendInstagramResponse(
        string $recipientId,
        string $message,
        array $attachments = []
    ): ?array {
        // Send main text message
        $result = $this->instagramService->sendMessage($recipientId, $message);

        // Send attachments if any (images, videos, etc.)
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['type']) && isset($attachment['url'])) {
                    // Instagram supports images and videos in DMs
                    if (in_array($attachment['type'], ['image', 'video'])) {
                        $this->instagramService->sendMedia(
                            $recipientId,
                            $attachment['type'],
                            $attachment['url']
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Send fallback response when chatbot fails
     *
     * @param Business $business
     * @param string $recipientId
     * @param string $originalMessage
     * @return array
     */
    protected function sendFallbackResponse(
        Business $business,
        string $recipientId,
        string $originalMessage
    ): array {
        $config = ChatbotConfig::where('business_id', $business->id)
            ->where('channel', 'instagram')
            ->first();

        $fallbackMessage = $config->fallback_message ??
            "Salom! Sizga qanday yordam bera olamiz? 😊";

        $this->instagramService->sendMessage($recipientId, $fallbackMessage);

        return [
            'success' => true,
            'response_sent' => true,
            'fallback_used' => true,
        ];
    }

    /**
     * Send automated greeting message
     *
     * @param Business $business
     * @param string $recipientId
     * @param string|null $customerName
     * @return array|null
     */
    public function sendGreeting(
        Business $business,
        string $recipientId,
        ?string $customerName = null
    ): ?array {
        $greeting = $customerName
            ? "Salom, {$customerName}! 👋 {$business->name}ga xush kelibsiz. Sizga qanday yordam bera olamiz?"
            : "Salom! 👋 {$business->name}ga xush kelibsiz. Sizga qanday yordam bera olamiz?";

        return $this->instagramService->sendMessage($recipientId, $greeting);
    }

    /**
     * Send offer recommendation based on context
     *
     * @param Business $business
     * @param string $recipientId
     * @param array $context
     * @return array|null
     */
    public function sendOfferRecommendation(
        Business $business,
        string $recipientId,
        array $context = []
    ): ?array {
        $offers = Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->limit(3)
            ->get();

        if ($offers->isEmpty()) {
            return $this->instagramService->sendMessage(
                $recipientId,
                "Hozirda maxsus takliflarimiz ustida ishlayapmiz. Tez orada ma'lumot beramiz! 🎁"
            );
        }

        $message = "Bizning eng yaxshi takliflarimiz:\n\n";
        foreach ($offers as $offer) {
            $message .= "✨ *{$offer->name}*\n";
            $message .= "{$offer->value_proposition}\n";
            if ($offer->price) {
                $message .= "💰 Narx: {$offer->price}\n";
            }
            $message .= "\n";
        }
        $message .= "Qaysi biri sizni qiziqtiradi?";

        return $this->instagramService->sendMessage($recipientId, $message);
    }

    /**
     * Handle story replies
     *
     * @param Business $business
     * @param string $senderId
     * @param string $storyId
     * @param string $replyText
     * @return array
     */
    public function handleStoryReply(
        Business $business,
        string $senderId,
        string $storyId,
        string $replyText
    ): array {
        Log::info('Instagram story reply received', [
            'business_id' => $business->id,
            'sender_id' => $senderId,
            'story_id' => $storyId,
        ]);

        // Process story reply as a regular message with context
        return $this->processIncomingMessage(
            $business,
            $senderId,
            $replyText,
            null,
            [
                'source' => 'story_reply',
                'story_id' => $storyId,
            ]
        );
    }

    /**
     * Handle quick reply buttons
     *
     * @param Business $business
     * @param string $senderId
     * @param string $payload
     * @return array
     */
    public function handleQuickReply(
        Business $business,
        string $senderId,
        string $payload
    ): array {
        // Map payloads to actions
        $actions = [
            'learn_more' => 'Ko\'proq ma\'lumot',
            'get_offer' => 'Taklif olish',
            'talk_human' => 'Operator bilan gaplashish',
            'not_interested' => 'Qiziqtirmaydi',
        ];

        $action = $actions[$payload] ?? null;

        if (!$action) {
            return ['success' => false, 'error' => 'Unknown payload'];
        }

        // Process as regular message
        return $this->processIncomingMessage(
            $business,
            $senderId,
            $action,
            null,
            ['source' => 'quick_reply', 'payload' => $payload]
        );
    }
}
