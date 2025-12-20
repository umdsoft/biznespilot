<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConversation;

class ChatbotIntentService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Detect intent from user message
     */
    public function detectIntent(
        string $message,
        Business $business,
        ?ChatbotConversation $conversation = null
    ): array {
        // Build context for Claude AI
        $context = $this->buildContext($business, $conversation);

        // Prepare the prompt for intent detection
        $prompt = $this->buildIntentDetectionPrompt($message, $context);

        // Get Claude AI response
        $response = $this->claudeAI->complete($prompt, null, 1024);

        // Parse the response
        return $this->parseIntentResponse($response);
    }

    /**
     * Build context for intent detection
     */
    private function buildContext(Business $business, ?ChatbotConversation $conversation): array
    {
        $context = [
            'business_name' => $business->name,
            'business_industry' => $business->industry,
            'business_description' => $business->description,
        ];

        if ($conversation) {
            $context['conversation_stage'] = $conversation->current_stage;
            $context['customer_name'] = $conversation->customer_name;
            $context['previous_intents'] = $conversation->messages()
                ->whereNotNull('detected_intent')
                ->pluck('detected_intent')
                ->unique()
                ->toArray();
        }

        return $context;
    }

    /**
     * Build intent detection prompt
     */
    private function buildIntentDetectionPrompt(string $message, array $context): string
    {
        $businessInfo = "Business: {$context['business_name']}";
        if (isset($context['business_industry'])) {
            $businessInfo .= " ({$context['business_industry']})";
        }

        $conversationContext = '';
        if (isset($context['conversation_stage'])) {
            $conversationContext = "\nCurrent conversation stage: {$context['conversation_stage']}";
        }

        return <<<PROMPT
You are an intent classifier for a sales chatbot. Analyze the following customer message and classify it into one of these intents:

**Available Intents:**
- GREETING: Customer is greeting or starting conversation (salom, assalomu alaykum, hello, hi, etc.)
- PRODUCT_INQUIRY: Customer asking about products/services
- PRICING: Customer asking about prices, costs, or payment
- ORDER: Customer wants to make a purchase or place an order
- COMPLAINT: Customer has a problem or complaint
- SUPPORT: Customer needs technical support or help
- HUMAN_HANDOFF: Customer explicitly requests human agent
- FEEDBACK: Customer providing feedback or review
- CANCEL: Customer wants to cancel order or stop conversation
- OTHER: None of the above

**Context:**
{$businessInfo}
{$conversationContext}

**Customer Message:**
"{$message}"

**Instructions:**
1. Classify the message into ONE primary intent
2. Extract any relevant entities (product names, prices, dates, etc.)
3. Determine the sentiment (positive, neutral, negative)
4. Provide confidence score (0.0 to 1.0)

**Response Format (JSON):**
{
    "intent": "INTENT_NAME",
    "confidence": 0.95,
    "entities": {
        "product": "Product name if mentioned",
        "price": "Price if mentioned",
        "date": "Date if mentioned"
    },
    "sentiment": "positive|neutral|negative",
    "suggested_response_type": "information|action|escalation"
}

Respond ONLY with the JSON, no additional text.
PROMPT;
    }

    /**
     * Parse Claude AI response
     */
    private function parseIntentResponse(string $response): array
    {
        // Try to extract JSON from response
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if ($json) {
                return [
                    'intent' => $json['intent'] ?? 'OTHER',
                    'confidence' => $json['confidence'] ?? 0.5,
                    'entities' => $json['entities'] ?? [],
                    'sentiment' => $json['sentiment'] ?? 'neutral',
                    'suggested_response_type' => $json['suggested_response_type'] ?? 'information',
                ];
            }
        }

        // Fallback: basic keyword matching
        return $this->fallbackIntentDetection($response);
    }

    /**
     * Fallback intent detection using keywords
     */
    private function fallbackIntentDetection(string $message): array
    {
        $message = mb_strtolower($message);

        // Greeting patterns
        if (preg_match('/(salom|assalomu alaykum|hello|hi|hey|привет)/ui', $message)) {
            return [
                'intent' => 'GREETING',
                'confidence' => 0.8,
                'entities' => [],
                'sentiment' => 'positive',
                'suggested_response_type' => 'information',
            ];
        }

        // Pricing patterns
        if (preg_match('/(narx|price|cost|қанча|сколько|payment|to\'lov)/ui', $message)) {
            return [
                'intent' => 'PRICING',
                'confidence' => 0.75,
                'entities' => [],
                'sentiment' => 'neutral',
                'suggested_response_type' => 'information',
            ];
        }

        // Order patterns
        if (preg_match('/(buyurtma|order|sotib olish|buy|purchase|xarid)/ui', $message)) {
            return [
                'intent' => 'ORDER',
                'confidence' => 0.75,
                'entities' => [],
                'sentiment' => 'positive',
                'suggested_response_type' => 'action',
            ];
        }

        // Complaint patterns
        if (preg_match('/(muammo|problem|issue|шикоят|жалоба|xato|error)/ui', $message)) {
            return [
                'intent' => 'COMPLAINT',
                'confidence' => 0.7,
                'entities' => [],
                'sentiment' => 'negative',
                'suggested_response_type' => 'escalation',
            ];
        }

        // Human handoff patterns
        if (preg_match('/(operator|human|inson|manager|мен|admin)/ui', $message)) {
            return [
                'intent' => 'HUMAN_HANDOFF',
                'confidence' => 0.8,
                'entities' => [],
                'sentiment' => 'neutral',
                'suggested_response_type' => 'escalation',
            ];
        }

        // Default
        return [
            'intent' => 'OTHER',
            'confidence' => 0.5,
            'entities' => [],
            'sentiment' => 'neutral',
            'suggested_response_type' => 'information',
        ];
    }

    /**
     * Get available intents
     */
    public static function getAvailableIntents(): array
    {
        return [
            'GREETING' => 'Salomlashish',
            'PRODUCT_INQUIRY' => 'Mahsulot haqida savol',
            'PRICING' => 'Narx so\'rovi',
            'ORDER' => 'Buyurtma berish',
            'COMPLAINT' => 'Shikoyat',
            'SUPPORT' => 'Texnik yordam',
            'HUMAN_HANDOFF' => 'Operator so\'rovi',
            'FEEDBACK' => 'Fikr-mulohaza',
            'CANCEL' => 'Bekor qilish',
            'OTHER' => 'Boshqa',
        ];
    }
}
