<?php

namespace App\Services;

use App\Models\ChatbotConversation;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Log;

class ChatbotFunnelService
{
    /**
     * Sales funnel stages in order
     */
    const STAGES = [
        'AWARENESS',
        'INTEREST',
        'CONSIDERATION',
        'INTENT',
        'PURCHASE',
        'POST_PURCHASE'
    ];

    /**
     * Determine appropriate funnel stage based on intent
     */
    public function determineStageFromIntent(string $intent, string $currentStage): string
    {
        $stageMap = [
            'GREETING' => 'AWARENESS',
            'PRODUCT_INQUIRY' => 'INTEREST',
            'PRICING' => 'CONSIDERATION',
            'ORDER' => 'INTENT',
            'COMPLAINT' => $currentStage, // Keep current stage
            'SUPPORT' => $currentStage, // Keep current stage
            'HUMAN_HANDOFF' => $currentStage, // Keep current stage
            'FEEDBACK' => 'POST_PURCHASE',
            'CANCEL' => $currentStage, // Keep current stage
            'OTHER' => $currentStage, // Keep current stage
        ];

        $suggestedStage = $stageMap[$intent] ?? $currentStage;

        // Only progress forward, never backward (except for explicit cases)
        if ($this->getStageIndex($suggestedStage) > $this->getStageIndex($currentStage)) {
            return $suggestedStage;
        }

        return $currentStage;
    }

    /**
     * Progress conversation to next stage
     */
    public function progressToNextStage(ChatbotConversation $conversation): bool
    {
        $currentIndex = $this->getStageIndex($conversation->current_stage);

        if ($currentIndex < count(self::STAGES) - 1) {
            $nextStage = self::STAGES[$currentIndex + 1];
            $conversation->update(['current_stage' => $nextStage]);
            return true;
        }

        return false; // Already at final stage
    }

    /**
     * Check if conversation should progress to next stage based on conditions
     */
    public function shouldProgressStage(ChatbotConversation $conversation, array $intentData): bool
    {
        $currentStage = $conversation->current_stage;
        $intent = $intentData['intent'];
        $confidence = $intentData['confidence'] ?? 0;

        // Require high confidence for stage progression
        if ($confidence < 0.7) {
            return false;
        }

        return match ($currentStage) {
            'AWARENESS' => in_array($intent, ['PRODUCT_INQUIRY', 'PRICING']),
            'INTEREST' => in_array($intent, ['PRICING', 'ORDER']),
            'CONSIDERATION' => $intent === 'ORDER',
            'INTENT' => false, // Manual progression after order placement
            'PURCHASE' => false, // Manual progression after purchase confirmation
            'POST_PURCHASE' => false, // Final stage
            default => false,
        };
    }

    /**
     * Get recommended response type for current stage
     */
    public function getStageRecommendedResponse(string $stage, string $intent): string
    {
        return match ($stage) {
            'AWARENESS' => match ($intent) {
                'GREETING' => 'welcome_menu',
                default => 'product_catalog',
            },
            'INTEREST' => match ($intent) {
                'PRODUCT_INQUIRY' => 'product_details',
                'PRICING' => 'pricing_info',
                default => 'hvco_offer',
            },
            'CONSIDERATION' => match ($intent) {
                'PRICING' => 'pricing_details',
                default => 'grand_slam_offer',
            },
            'INTENT' => match ($intent) {
                'ORDER' => 'order_process',
                default => 'closing_sequence',
            },
            'PURCHASE' => 'order_confirmation',
            'POST_PURCHASE' => match ($intent) {
                'FEEDBACK' => 'thank_you',
                default => 'upsell_referral',
            },
            default => 'default_response',
        };
    }

    /**
     * Get stage-specific prompts and actions
     */
    public function getStageActions(string $stage): array
    {
        return match ($stage) {
            'AWARENESS' => [
                'objectives' => ['Attract attention', 'Build awareness'],
                'actions' => [
                    'Send welcome message',
                    'Show product menu',
                    'Explain what business offers',
                ],
                'templates' => ['welcome', 'introduction', 'menu'],
            ],
            'INTEREST' => [
                'objectives' => ['Generate interest', 'Educate customer'],
                'actions' => [
                    'Show product details',
                    'Share benefits',
                    'Offer HVCO (High-Value Content Offer)',
                ],
                'templates' => ['product_info', 'benefits', 'hvco'],
            ],
            'CONSIDERATION' => [
                'objectives' => ['Build desire', 'Address concerns'],
                'actions' => [
                    'Present pricing',
                    'Show social proof',
                    'Answer FAQs',
                    'Offer guarantees',
                ],
                'templates' => ['pricing', 'testimonials', 'faq', 'guarantee'],
            ],
            'INTENT' => [
                'objectives' => ['Close sale', 'Facilitate purchase'],
                'actions' => [
                    'Present Grand Slam Offer',
                    'Create urgency',
                    'Make ordering easy',
                ],
                'templates' => ['grand_slam', 'urgency', 'order_form'],
            ],
            'PURCHASE' => [
                'objectives' => ['Confirm order', 'Set expectations'],
                'actions' => [
                    'Confirm order details',
                    'Collect payment',
                    'Set delivery expectations',
                    'Create lead/customer record',
                ],
                'templates' => ['order_confirmation', 'payment', 'next_steps'],
            ],
            'POST_PURCHASE' => [
                'objectives' => ['Maximize LTV', 'Get referrals'],
                'actions' => [
                    'Request feedback',
                    'Offer upsell/cross-sell',
                    'Request referrals',
                ],
                'templates' => ['feedback_request', 'upsell', 'referral'],
            ],
            default => [
                'objectives' => ['Maintain conversation'],
                'actions' => ['Respond appropriately'],
                'templates' => ['default'],
            ],
        };
    }

    /**
     * Create lead when conversation reaches PURCHASE stage
     */
    public function createLeadFromConversation(ChatbotConversation $conversation): ?Lead
    {
        if ($conversation->lead_id) {
            return $conversation->lead; // Already has a lead
        }

        if ($conversation->customer_email || $conversation->customer_phone) {
            // Get or create appropriate source for the channel
            $source = $this->getOrCreateChatbotSource($conversation->business_id, $conversation->channel);

            $lead = Lead::create([
                'business_id' => $conversation->business_id,
                'source_id' => $source?->id,
                'name' => $conversation->customer_name ?? 'Chatbot Lead',
                'email' => $conversation->customer_email,
                'phone' => $conversation->customer_phone,
                'status' => 'new',
                'notes' => "Auto-created from chatbot conversation #{$conversation->id}",
                'data' => [
                    'source' => 'chatbot_' . $conversation->channel,
                    'conversation_id' => $conversation->id,
                    'platform' => $conversation->channel,
                ],
            ]);

            $conversation->update(['lead_id' => $lead->id]);

            return $lead;
        }

        return null;
    }

    /**
     * Get or create lead source for chatbot channel
     */
    protected function getOrCreateChatbotSource(string $businessId, string $channel): ?LeadSource
    {
        $channelConfig = [
            'instagram' => [
                'code' => 'instagram_chatbot',
                'name' => 'Instagram Chatbot',
                'icon' => 'instagram',
                'color' => '#E1306C',
            ],
            'telegram' => [
                'code' => 'telegram_bot',
                'name' => 'Telegram Bot',
                'icon' => 'telegram',
                'color' => '#0088cc',
            ],
            'facebook' => [
                'code' => 'facebook_chatbot',
                'name' => 'Facebook Chatbot',
                'icon' => 'facebook',
                'color' => '#1877F2',
            ],
            'whatsapp' => [
                'code' => 'whatsapp_chatbot',
                'name' => 'WhatsApp Chatbot',
                'icon' => 'whatsapp',
                'color' => '#25D366',
            ],
        ];

        $config = $channelConfig[$channel] ?? [
            'code' => 'chatbot_' . $channel,
            'name' => ucfirst($channel) . ' Chatbot',
            'icon' => 'chat',
            'color' => '#6366F1',
        ];

        // First try to find by code
        $source = LeadSource::where('business_id', $businessId)
            ->where('code', $config['code'])
            ->first();

        if ($source) {
            return $source;
        }

        // Fallback: search by name
        $source = LeadSource::where('business_id', $businessId)
            ->where('name', 'like', '%' . ucfirst($channel) . '%')
            ->first();

        if ($source) {
            return $source;
        }

        // Create new source
        try {
            $source = LeadSource::create([
                'business_id' => $businessId,
                'code' => $config['code'] . '_' . substr($businessId, 0, 8),
                'name' => $config['name'],
                'category' => 'digital',
                'icon' => $config['icon'],
                'color' => $config['color'],
                'is_paid' => false,
                'is_trackable' => true,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning('LeadSource creation failed for chatbot', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            // Try with timestamp
            try {
                $source = LeadSource::create([
                    'business_id' => $businessId,
                    'code' => $config['code'] . '_' . time(),
                    'name' => $config['name'],
                    'category' => 'digital',
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'is_paid' => false,
                    'is_trackable' => true,
                    'is_active' => true,
                ]);
            } catch (\Exception $e2) {
                Log::error('LeadSource creation failed completely', ['error' => $e2->getMessage()]);
                return null;
            }
        }

        return $source;
    }

    /**
     * Get stage index
     */
    private function getStageIndex(string $stage): int
    {
        $index = array_search($stage, self::STAGES);
        return $index !== false ? $index : 0;
    }

    /**
     * Get stage display name
     */
    public static function getStageDisplayName(string $stage): string
    {
        return match ($stage) {
            'AWARENESS' => 'Xabardorlik',
            'INTEREST' => 'Qiziqish',
            'CONSIDERATION' => 'Qarab chiqish',
            'INTENT' => 'Niyat',
            'PURCHASE' => 'Xarid',
            'POST_PURCHASE' => 'Xariddan keyin',
            default => $stage,
        };
    }

    /**
     * Get stage progress percentage
     */
    public static function getStageProgress(string $stage): int
    {
        $stageMap = [
            'AWARENESS' => 15,
            'INTEREST' => 35,
            'CONSIDERATION' => 55,
            'INTENT' => 75,
            'PURCHASE' => 90,
            'POST_PURCHASE' => 100,
        ];

        return $stageMap[$stage] ?? 0;
    }
}
