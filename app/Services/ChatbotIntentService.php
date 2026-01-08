<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConversation;

/**
 * Algorithmic Intent Detection Service
 *
 * Uses keyword matching and pattern recognition for intent classification
 */
class ChatbotIntentService
{
    /**
     * Detect intent from user message using algorithmic keyword matching
     */
    public function detectIntent(
        string $message,
        Business $business,
        ?ChatbotConversation $conversation = null
    ): array {
        return $this->algorithmicIntentDetection($message);
    }

    /**
     * Algorithmic intent detection using keyword patterns
     */
    private function algorithmicIntentDetection(string $message): array
    {
        $message = mb_strtolower($message);

        // Greeting patterns (Uzbek, Russian, English)
        if (preg_match('/(salom|assalomu alaykum|hello|hi|hey|привет|здравствуйте|добрый)/ui', $message)) {
            return $this->buildResponse('GREETING', 0.85, 'positive', 'information');
        }

        // Thank you patterns
        if (preg_match('/(rahmat|thanks|thank you|спасибо|tashakkur)/ui', $message)) {
            return $this->buildResponse('FEEDBACK', 0.8, 'positive', 'information');
        }

        // Pricing patterns
        if (preg_match('/(narx|price|cost|qancha|сколько|стоит|payment|to\'lov|pul|baho)/ui', $message)) {
            return $this->buildResponse('PRICING', 0.8, 'neutral', 'information');
        }

        // Order patterns
        if (preg_match('/(buyurtma|order|sotib olish|buy|purchase|xarid|olaman|заказ|купить)/ui', $message)) {
            return $this->buildResponse('ORDER', 0.8, 'positive', 'action');
        }

        // Product inquiry patterns
        if (preg_match('/(mahsulot|product|xizmat|service|товар|услуга|nima|what|qanday|какой)/ui', $message)) {
            return $this->buildResponse('PRODUCT_INQUIRY', 0.75, 'neutral', 'information');
        }

        // Complaint patterns
        if (preg_match('/(muammo|problem|issue|shikoyat|жалоба|xato|error|yomon|плохо|broken)/ui', $message)) {
            return $this->buildResponse('COMPLAINT', 0.8, 'negative', 'escalation');
        }

        // Support patterns
        if (preg_match('/(yordam|help|support|pomosh|помощь|qanday qilaman|как)/ui', $message)) {
            return $this->buildResponse('SUPPORT', 0.75, 'neutral', 'information');
        }

        // Human handoff patterns
        if (preg_match('/(operator|human|inson|manager|менеджер|admin|odam|живой)/ui', $message)) {
            return $this->buildResponse('HUMAN_HANDOFF', 0.9, 'neutral', 'escalation');
        }

        // Cancel patterns
        if (preg_match('/(bekor|cancel|stop|отмена|to\'xtat|yoq|нет|не надо)/ui', $message)) {
            return $this->buildResponse('CANCEL', 0.75, 'negative', 'action');
        }

        // Feedback patterns
        if (preg_match('/(fikr|feedback|review|отзыв|baholash|рейтинг)/ui', $message)) {
            return $this->buildResponse('FEEDBACK', 0.7, 'neutral', 'information');
        }

        // Default - OTHER
        return $this->buildResponse('OTHER', 0.5, 'neutral', 'information');
    }

    /**
     * Build standardized response array
     */
    private function buildResponse(
        string $intent,
        float $confidence,
        string $sentiment,
        string $responseType
    ): array {
        return [
            'intent' => $intent,
            'confidence' => $confidence,
            'entities' => [],
            'sentiment' => $sentiment,
            'suggested_response_type' => $responseType,
        ];
    }

    /**
     * Get available intents with labels
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
