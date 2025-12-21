<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp AI Chat Service
 *
 * Integrates WhatsApp messaging with AI-powered chatbot functionality.
 * Provides context-aware responses using DreamBuyer and Offer data.
 */
class WhatsAppAIChatService
{
    protected ChatbotService $chatbotService;
    protected WhatsAppService $whatsappService;
    protected ClaudeAIService $claudeAI;

    public function __construct(
        ChatbotService $chatbotService,
        WhatsAppService $whatsappService,
        ClaudeAIService $claudeAI
    ) {
        $this->chatbotService = $chatbotService;
        $this->whatsappService = $whatsappService;
        $this->claudeAI = $claudeAI;
    }

    /**
     * Process incoming WhatsApp message with AI
     *
     * @param Business $business
     * @param string $from Phone number
     * @param string $messageContent Message text
     * @param string|null $senderName Sender name from WhatsApp profile
     * @param array $metadata Additional metadata
     * @return array Processing result
     */
    public function processIncomingMessage(
        Business $business,
        string $from,
        string $messageContent,
        ?string $senderName = null,
        array $metadata = []
    ): array {
        try {
            Log::info('WhatsApp AI: Processing message', [
                'business_id' => $business->id,
                'from' => $from,
                'message_preview' => substr($messageContent, 0, 50),
            ]);

            // Get or create customer
            $customer = $this->getOrCreateCustomer($business, $from, $senderName);

            // Add business context to metadata
            $metadata = array_merge($metadata, [
                'channel' => 'whatsapp',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);

            // Process message through chatbot service
            $result = $this->chatbotService->processMessage(
                $business,
                'whatsapp',
                $from,
                $messageContent,
                $senderName,
                $metadata
            );

            if (!$result['success']) {
                // Fallback to simple response
                return $this->sendFallbackResponse($business, $from, $messageContent);
            }

            // Send AI response via WhatsApp
            $sendResult = $this->sendWhatsAppResponse(
                $from,
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
            Log::error('WhatsApp AI processing error', [
                'business_id' => $business->id,
                'from' => $from,
                'error' => $e->getMessage(),
            ]);

            // Send error fallback
            $this->whatsappService->sendTextMessage(
                $from,
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
     * @param string $to Phone number
     * @param string $userMessage User's message
     * @param array $context Additional context
     * @return array|null
     */
    public function sendContextAwareResponse(
        Business $business,
        string $to,
        string $userMessage,
        array $context = []
    ): ?array {
        // Build context with DreamBuyer and Offer data
        $enrichedContext = $this->buildBusinessContext($business, $context);

        // Generate AI response
        $aiResponse = $this->generateAIResponse($business, $userMessage, $enrichedContext);

        // Send via WhatsApp
        return $this->whatsappService->sendTextMessage($to, $aiResponse);
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
        $prompt = "Siz {$business->name} biznesining professional WhatsApp yordamchisisiz.\n\n";

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

        // Add chatbot personality
        $prompt .= "Siz:\n";
        $prompt .= "- Do'stona va professional\n";
        $prompt .= "- Mijoz muammolarini tushunasiz va hal qilasiz\n";
        $prompt .= "- Qisqa va aniq javoblar berasiz\n";
        $prompt .= "- WhatsApp uchun mos formatda yozasiz (emoji kam)\n";
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
     * Get or create customer from WhatsApp contact
     *
     * @param Business $business
     * @param string $phoneNumber
     * @param string|null $name
     * @return Customer
     */
    protected function getOrCreateCustomer(
        Business $business,
        string $phoneNumber,
        ?string $name = null
    ): Customer {
        $customer = Customer::where('business_id', $business->id)
            ->where('phone', $phoneNumber)
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'business_id' => $business->id,
                'name' => $name ?? "WhatsApp User ($phoneNumber)",
                'phone' => $phoneNumber,
                'source' => 'whatsapp',
                'tags' => ['whatsapp'],
            ]);

            Log::info('Created new customer from WhatsApp', [
                'customer_id' => $customer->id,
                'phone' => $phoneNumber,
            ]);
        }

        return $customer;
    }

    /**
     * Send WhatsApp response with attachments
     *
     * @param string $to
     * @param string $message
     * @param array $attachments
     * @return array|null
     */
    protected function sendWhatsAppResponse(
        string $to,
        string $message,
        array $attachments = []
    ): ?array {
        // Send main text message
        $result = $this->whatsappService->sendTextMessage($to, $message);

        // Send attachments if any
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['type']) && isset($attachment['url'])) {
                    $this->whatsappService->sendMediaMessage(
                        $to,
                        $attachment['type'],
                        $attachment['url'],
                        $attachment['caption'] ?? null
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Send fallback response when chatbot fails
     *
     * @param Business $business
     * @param string $to
     * @param string $originalMessage
     * @return array
     */
    protected function sendFallbackResponse(
        Business $business,
        string $to,
        string $originalMessage
    ): array {
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        $fallbackMessage = $config->fallback_message ??
            "Xush kelibsiz! Sizga qanday yordam bera olamiz?";

        $this->whatsappService->sendTextMessage($to, $fallbackMessage);

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
     * @param string $to
     * @param string|null $customerName
     * @return array|null
     */
    public function sendGreeting(
        Business $business,
        string $to,
        ?string $customerName = null
    ): ?array {
        $greeting = $customerName
            ? "Assalomu alaykum, {$customerName}! {$business->name}ga xush kelibsiz. Sizga qanday yordam bera olamiz?"
            : "Assalomu alaykum! {$business->name}ga xush kelibsiz. Sizga qanday yordam bera olamiz?";

        return $this->whatsappService->sendTextMessage($to, $greeting);
    }

    /**
     * Send offer recommendation based on context
     *
     * @param Business $business
     * @param string $to
     * @param array $context
     * @return array|null
     */
    public function sendOfferRecommendation(
        Business $business,
        string $to,
        array $context = []
    ): ?array {
        $offers = Offer::where('business_id', $business->id)
            ->where('status', 'active')
            ->limit(3)
            ->get();

        if ($offers->isEmpty()) {
            return $this->whatsappService->sendTextMessage(
                $to,
                "Hozirda maxsus takliflarimiz ustida ishlayapmiz. Tez orada ma'lumot beramiz!"
            );
        }

        $message = "Bizning eng yaxshi takliflarimiz:\n\n";
        foreach ($offers as $offer) {
            $message .= "✨ *{$offer->name}*\n";
            $message .= "{$offer->value_proposition}\n";
            if ($offer->price) {
                $message .= "Narx: {$offer->price}\n";
            }
            $message .= "\n";
        }
        $message .= "Qaysi biri sizni qiziqtiradi?";

        return $this->whatsappService->sendTextMessage($to, $message);
    }

    /**
     * Handle quick reply buttons
     *
     * @param Business $business
     * @param string $to
     * @param string $buttonId
     * @return array
     */
    public function handleQuickReply(
        Business $business,
        string $to,
        string $buttonId
    ): array {
        // Map button IDs to actions
        $actions = [
            'learn_more' => 'Ko\'proq ma\'lumot',
            'get_offer' => 'Taklif olish',
            'talk_human' => 'Operator bilan gaplashish',
            'not_interested' => 'Qiziqtirmaydi',
        ];

        $action = $actions[$buttonId] ?? null;

        if (!$action) {
            return ['success' => false, 'error' => 'Unknown button'];
        }

        // Process as regular message
        return $this->processIncomingMessage(
            $business,
            $to,
            $action,
            null,
            ['source' => 'quick_reply', 'button_id' => $buttonId]
        );
    }
}
