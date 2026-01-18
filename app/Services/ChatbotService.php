<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\ChatbotConversation;
use App\Models\ChatbotKnowledge;
use App\Models\ChatbotMessage;
use App\Models\ChatbotTemplate;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected ChatbotIntentService $intentService;

    protected ChatbotFunnelService $funnelService;

    protected ClaudeAIService $claudeAI;

    public function __construct(
        ChatbotIntentService $intentService,
        ChatbotFunnelService $funnelService,
        ClaudeAIService $claudeAI
    ) {
        $this->intentService = $intentService;
        $this->funnelService = $funnelService;
        $this->claudeAI = $claudeAI;
    }

    /**
     * Process incoming message from any channel
     */
    public function processMessage(
        Business $business,
        string $channel,
        string $channelUserId,
        string $messageContent,
        ?string $channelUsername = null,
        ?array $metadata = []
    ): array {
        $startTime = microtime(true);

        try {
            // Get or create chatbot config
            $config = $this->getOrCreateConfig($business);

            if (! $config->is_active) {
                return [
                    'success' => false,
                    'message' => 'Chatbot is not active for this business',
                ];
            }

            // Check business hours
            if (! $this->isWithinBusinessHours($config)) {
                return [
                    'success' => true,
                    'response' => $config->outside_hours_message ?? 'We are currently closed. Please message us during business hours.',
                    'skip_ai' => true,
                ];
            }

            // Get or create conversation
            $conversation = $this->getOrCreateConversation(
                $business,
                $config,
                $channel,
                $channelUserId,
                $channelUsername
            );

            // Save user message
            $userMessage = $this->saveMessage($conversation, 'user', $messageContent, $metadata);

            // Detect intent
            $intentData = $this->intentService->detectIntent(
                $messageContent,
                $business,
                $conversation
            );

            // Update message with detected intent
            $userMessage->update([
                'detected_intent' => $intentData['intent'],
                'intent_data' => $intentData,
                'is_processed' => true,
                'processed_at' => now(),
            ]);

            // Update conversation
            $conversation->update([
                'detected_intent' => $intentData['intent'],
                'sentiment' => $intentData['sentiment'],
                'message_count' => $conversation->message_count + 1,
                'user_message_count' => $conversation->user_message_count + 1,
                'last_message_at' => now(),
                'last_user_message_at' => now(),
            ]);

            // Check if should progress funnel stage
            if ($this->funnelService->shouldProgressStage($conversation, $intentData)) {
                $this->funnelService->progressToNextStage($conversation);
                $conversation->refresh();
            } else {
                // Determine appropriate stage based on intent
                $newStage = $this->funnelService->determineStageFromIntent(
                    $intentData['intent'],
                    $conversation->current_stage
                );
                if ($newStage !== $conversation->current_stage) {
                    $conversation->update(['current_stage' => $newStage]);
                }
            }

            // Generate bot response
            $botResponse = $this->generateResponse(
                $business,
                $config,
                $conversation,
                $messageContent,
                $intentData
            );

            // Save bot message
            $botMessage = $this->saveMessage(
                $conversation,
                'bot',
                $botResponse['content'],
                [
                    'template_used' => $botResponse['template_used'] ?? null,
                    'response_type' => $botResponse['type'] ?? 'text',
                ]
            );

            // Update conversation stats
            $conversation->update([
                'bot_message_count' => $conversation->bot_message_count + 1,
                'last_bot_message_at' => now(),
            ]);

            // Create lead if at PURCHASE stage and has contact info
            if ($conversation->current_stage === 'PURCHASE' && $config->lead_auto_create) {
                $this->funnelService->createLeadFromConversation($conversation);
            }

            // Calculate processing time
            $processingTime = (microtime(true) - $startTime) * 1000;
            $botMessage->update(['processing_time_ms' => $processingTime]);

            return [
                'success' => true,
                'response' => $botResponse['content'],
                'conversation_id' => $conversation->id,
                'intent' => $intentData['intent'],
                'stage' => $conversation->current_stage,
                'processing_time_ms' => $processingTime,
                'attachments' => $botResponse['attachments'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Chatbot message processing error', [
                'business_id' => $business->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error processing message',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate bot response
     */
    private function generateResponse(
        Business $business,
        ChatbotConfig $config,
        ChatbotConversation $conversation,
        string $userMessage,
        array $intentData
    ): array {
        $intent = $intentData['intent'];

        // Check for knowledge base match first
        $knowledgeResponse = $this->checkKnowledgeBase($business, $userMessage, $intent);
        if ($knowledgeResponse) {
            return [
                'content' => $knowledgeResponse,
                'type' => 'text',
                'source' => 'knowledge_base',
            ];
        }

        // Check for template match
        $templateResponse = $this->checkTemplate($business, $conversation, $intent);
        if ($templateResponse) {
            return [
                'content' => $this->replaceTemplateVariables(
                    $templateResponse['content'],
                    $conversation,
                    $business
                ),
                'type' => 'text',
                'template_used' => $templateResponse['code'],
                'source' => 'template',
                'attachments' => $templateResponse['buttons'] ?? [],
            ];
        }

        // Use AI to generate response
        if ($config->use_ai_intent) {
            return $this->generateAIResponse($business, $conversation, $userMessage, $intentData);
        }

        // Fallback to default response
        return [
            'content' => $config->default_response ?? 'Rahmat! Tez orada javob beramiz.',
            'type' => 'text',
            'source' => 'default',
        ];
    }

    /**
     * Generate AI response using Claude
     */
    private function generateAIResponse(
        Business $business,
        ChatbotConversation $conversation,
        string $userMessage,
        array $intentData
    ): array {
        $systemPrompt = $this->buildAISystemPrompt($business, $conversation);

        // Get recent conversation history
        $conversationHistory = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($msg) => [
                'role' => $msg->role === 'bot' ? 'assistant' : 'user',
                'content' => $msg->content,
            ])
            ->toArray();

        try {
            $response = $this->claudeAI->chat($conversationHistory, $systemPrompt);

            return [
                'content' => $response,
                'type' => 'text',
                'source' => 'ai',
            ];
        } catch (\Exception $e) {
            Log::error('AI response generation failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'content' => 'Uzr, xatolik yuz berdi. Iltimos qaytadan urinib ko\'ring.',
                'type' => 'text',
                'source' => 'error_fallback',
            ];
        }
    }

    /**
     * Build AI system prompt
     */
    private function buildAISystemPrompt(Business $business, ChatbotConversation $conversation): string
    {
        $stageActions = $this->funnelService->getStageActions($conversation->current_stage);

        return <<<PROMPT
You are a professional sales chatbot for {$business->name}.

**Business Info:**
- Name: {$business->name}
- Industry: {$business->industry}
- Description: {$business->description}

**Current Conversation Context:**
- Stage: {$conversation->current_stage}
- Customer Name: {$conversation->customer_name}
- Stage Objectives: {$stageActions['objectives'][0]}

**Your Role:**
1. Be helpful, friendly, and professional
2. Respond in Uzbek or Russian based on user's language
3. Guide customer through sales funnel
4. Answer questions clearly and concisely
5. Collect customer information (name, phone, email) naturally
6. Move conversation toward purchase

**Important Instructions:**
- Keep responses short (2-3 sentences max)
- Ask only ONE question at a time
- Use emojis sparingly
- If user asks for human, acknowledge and say operator will contact soon
- NEVER make up information about products/pricing
- Stay in character as {$business->name} representative

Respond naturally and helpfully to the customer's message.
PROMPT;
    }

    /**
     * Check knowledge base for matching answer
     */
    private function checkKnowledgeBase(Business $business, string $message, string $intent): ?string
    {
        $knowledge = ChatbotKnowledge::where('business_id', $business->id)
            ->where('is_active', true)
            ->where(function ($query) use ($intent) {
                $query->where('intent', $intent)
                    ->orWhereNull('intent');
            })
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($knowledge as $item) {
            // Simple keyword matching
            if ($this->matchesKeywords($message, $item->keywords ?? [])) {
                $item->increment('usage_count');
                $item->update(['last_used_at' => now()]);

                return $item->answer;
            }
        }

        return null;
    }

    /**
     * Check for matching template
     */
    private function checkTemplate(Business $business, ChatbotConversation $conversation, string $intent): ?array
    {
        $template = ChatbotTemplate::where('business_id', $business->id)
            ->where('is_active', true)
            ->where(function ($query) use ($intent, $conversation) {
                $query->where('trigger_intent', $intent)
                    ->where(function ($q) use ($conversation) {
                        $q->where('trigger_stage', $conversation->current_stage)
                            ->orWhere('trigger_stage', 'ANY');
                    });
            })
            ->orderBy('is_default', 'desc')
            ->first();

        if ($template) {
            $template->increment('usage_count');
            $template->update(['last_used_at' => now()]);

            return [
                'content' => $template->content,
                'code' => $template->code,
                'buttons' => $template->buttons,
            ];
        }

        return null;
    }

    /**
     * Replace template variables
     */
    private function replaceTemplateVariables(string $content, ChatbotConversation $conversation, Business $business): string
    {
        $replacements = [
            '{customer_name}' => $conversation->customer_name ?? 'Mijoz',
            '{business_name}' => $business->name,
            '{stage}' => ChatbotFunnelService::getStageDisplayName($conversation->current_stage),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Match keywords
     */
    private function matchesKeywords(string $message, array $keywords): bool
    {
        $message = mb_strtolower($message);

        foreach ($keywords as $keyword) {
            if (mb_stripos($message, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get or create chatbot config
     */
    private function getOrCreateConfig(Business $business): ChatbotConfig
    {
        return ChatbotConfig::firstOrCreate(
            ['business_id' => $business->id],
            [
                'bot_name' => 'Assistant',
                'welcome_message' => "Assalomu alaykum! {$business->name}ga xush kelibsiz. Sizga qanday yordam bera olaman?",
                'is_active' => true,
            ]
        );
    }

    /**
     * Get or create conversation
     */
    private function getOrCreateConversation(
        Business $business,
        ChatbotConfig $config,
        string $channel,
        string $channelUserId,
        ?string $channelUsername
    ): ChatbotConversation {
        $conversation = ChatbotConversation::where('business_id', $business->id)
            ->where('channel', $channel)
            ->where('channel_user_id', $channelUserId)
            ->where('status', 'active')
            ->first();

        if (! $conversation) {
            $conversation = ChatbotConversation::create([
                'business_id' => $business->id,
                'chatbot_config_id' => $config->id,
                'channel' => $channel,
                'channel_user_id' => $channelUserId,
                'channel_username' => $channelUsername,
                'status' => 'active',
                'current_stage' => $config->default_funnel_stage,
                'started_at' => now(),
            ]);

            $config->increment('total_conversations');
        }

        return $conversation;
    }

    /**
     * Save message
     */
    private function saveMessage(
        ChatbotConversation $conversation,
        string $role,
        string $content,
        array $metadata = []
    ): ChatbotMessage {
        return ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation->id,
            'business_id' => $conversation->business_id,
            'role' => $role,
            'content' => $content,
            'detected_intent' => $metadata['detected_intent'] ?? null,
            'intent_data' => $metadata['intent_data'] ?? null,
            'template_used' => $metadata['template_used'] ?? null,
            'message_type' => $metadata['response_type'] ?? 'text',
            'attachments' => $metadata['attachments'] ?? null,
        ]);
    }

    /**
     * Check if within business hours
     */
    private function isWithinBusinessHours(ChatbotConfig $config): bool
    {
        if (! $config->business_hours) {
            return true; // 24/7 if no hours set
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('D')); // mon, tue, etc.
        $currentTime = $now->format('H:i');

        $hours = $config->business_hours;

        if (! isset($hours[$dayOfWeek])) {
            return false; // Closed this day
        }

        $dayHours = $hours[$dayOfWeek];

        return $currentTime >= $dayHours['start'] && $currentTime <= $dayHours['end'];
    }

    /**
     * Get conversation statistics
     */
    public function getConversationStats(Business $business, ?string $channel = null): array
    {
        $query = ChatbotConversation::where('business_id', $business->id);

        if ($channel) {
            $query->where('channel', $channel);
        }

        return [
            'total' => $query->count(),
            'active' => $query->where('status', 'active')->count(),
            'converted' => $query->where('is_converted', true)->count(),
            'avg_duration' => $query->whereNotNull('duration_seconds')->avg('duration_seconds'),
            'by_stage' => $query->selectRaw('current_stage, COUNT(*) as count')
                ->groupBy('current_stage')
                ->pluck('count', 'current_stage')
                ->toArray(),
        ];
    }
}
