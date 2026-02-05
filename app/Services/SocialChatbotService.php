<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramAutomation;
use App\Models\InstagramAutomationLog;
use App\Models\InstagramConversation;
use App\Models\InstagramMessage;
use App\Models\Integration;
use App\Models\SocialUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SocialChatbotService - Instagram Sales Flow Ijrochisi
 *
 * Bu servis Instagram DM orqali sotuv voronkasini boshqaradi:
 * - Xabar yuborish (oddiy matn)
 * - Tugmali xabar yuborish (Quick Replies)
 * - Flow (ssenariy) bajarish
 * - Foydalanuvchi holatini kuzatish
 *
 * Instagram 24 soatlik qoidasi:
 * - Foydalanuvchi xabar yuborgandan keyin 24 soat ichida javob berish mumkin
 * - Quick Replies faqat mobil ilovada ishlaydi
 *
 * Himoya qatlamlari:
 * - User Profile Sync: Foydalanuvchi profilini Instagram API dan yuklash
 * - 24-Hour Window: Oyna yopilganda xatolikni ushlash
 */
class SocialChatbotService
{
    protected string $graphApiUrl;

    /**
     * Maximum quick reply buttons (Instagram limit)
     */
    protected const MAX_QUICK_REPLIES = 13;

    /**
     * Maximum button title length
     */
    protected const MAX_BUTTON_TITLE_LENGTH = 20;

    /**
     * Profile cache duration (12 soat)
     */
    protected const PROFILE_CACHE_HOURS = 12;

    /**
     * 24-hour window error code from Instagram
     */
    protected const ERROR_OUTSIDE_WINDOW = 10;

    protected ChatbotIntentService $intentService;

    public function __construct(ChatbotIntentService $intentService)
    {
        $this->intentService = $intentService;
        $this->graphApiUrl = 'https://graph.facebook.com/' . config('services.meta.api_version', 'v24.0');
    }

    /**
     * Process incoming webhook message
     *
     * @param array $webhookData Webhook ma'lumotlari
     * @param InstagramAccount $account Instagram akkaunt
     * @return array Natija
     */
    public function processWebhook(array $webhookData, InstagramAccount $account): array
    {
        $senderId = $webhookData['sender_id'] ?? null;
        $message = $webhookData['message'] ?? '';
        $messageType = $webhookData['type'] ?? 'message';
        $payload = $webhookData['payload'] ?? null;

        if (! $senderId) {
            return ['success' => false, 'error' => 'Sender ID topilmadi'];
        }

        Log::info('SocialChatbotService: Processing webhook', [
            'account_id' => $account->id,
            'sender_id' => $senderId,
            'message' => $message,
            'type' => $messageType,
            'has_payload' => ! empty($payload),
        ]);

        try {
            // Suhbatni olish yoki yaratish
            $conversation = $this->getOrCreateConversation($account, $senderId, $webhookData);

            // Kiruvchi xabarni saqlash
            $this->saveIncomingMessage($conversation, $message, $webhookData);

            // Intent aniqlash
            $intent = $this->intentService->detect(
                $message,
                $account,
                $conversation,
                ['payload' => $payload]
            );

            Log::info('SocialChatbotService: Intent detected', [
                'type' => $intent['type'],
                'value' => $intent['value'],
                'confidence' => $intent['confidence'] ?? 0,
            ]);

            // Intent asosida amal bajarish
            return $this->handleIntent($intent, $account, $conversation, $message);

        } catch (\Exception $e) {
            Log::error('SocialChatbotService: Processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle detected intent
     */
    protected function handleIntent(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $originalMessage
    ): array {
        switch ($intent['type']) {
            case 'system':
                return $this->handleSystemIntent($intent, $account, $conversation);

            case 'keyword':
            case 'flow_keyword':
                return $this->handleKeywordIntent($intent, $account, $conversation, $originalMessage);

            case 'payload':
            case 'flow_navigation':
                return $this->handlePayloadIntent($intent, $account, $conversation);

            case 'start_automation':
                return $this->startAutomation($intent['automation'], $account, $conversation);

            case 'user_input':
            case 'collected_response':
                return $this->handleUserInput($intent, $account, $conversation, $originalMessage);

            case 'general':
                return $this->handleGeneralIntent($intent, $account, $conversation, $originalMessage);

            case 'unknown':
            default:
                return $this->handleUnknownIntent($intent, $account, $conversation);
        }
    }

    /**
     * Handle system commands (start, stop, help)
     */
    protected function handleSystemIntent(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation
    ): array {
        $command = $intent['value'];

        switch ($command) {
            case 'start_flow':
                // Default "Start" flow ni qidirish
                $startAutomation = $this->findStartAutomation($account);
                if ($startAutomation) {
                    return $this->startAutomation($startAutomation, $account, $conversation);
                }

                // Start flow yo'q - salomlashish xabari yuborish
                $this->sendMessage(
                    $account,
                    $conversation,
                    "Assalomu alaykum! 👋\n\nSizga qanday yordam bera olaman?",
                    null
                );

                return ['success' => true, 'action' => 'greeting_sent'];

            case 'stop_flow':
                $conversation->endAutomation();
                $this->sendMessage(
                    $account,
                    $conversation,
                    "Suhbat to'xtatildi. Yana xizmat kerak bo'lsa, yozing! 👋",
                    null
                );

                return ['success' => true, 'action' => 'flow_stopped'];

            case 'human_handoff':
                $conversation->update([
                    'needs_human' => true,
                    'is_bot_active' => false,
                ]);
                $this->sendMessage(
                    $account,
                    $conversation,
                    "Tushundim! Operatorimiz tez orada siz bilan bog'lanadi. Iltimos, biroz kuting... 👨‍💼",
                    null
                );

                return ['success' => true, 'action' => 'human_handoff'];

            case 'main_menu':
                $mainMenuAutomation = $this->findMainMenuAutomation($account);
                if ($mainMenuAutomation) {
                    return $this->startAutomation($mainMenuAutomation, $account, $conversation);
                }

                return ['success' => true, 'action' => 'no_main_menu'];

            case 'go_back':
                // TODO: Oldingi nodega qaytish logikasi
                return ['success' => true, 'action' => 'go_back'];

            default:
                return ['success' => false, 'error' => 'Unknown system command'];
        }
    }

    /**
     * Handle keyword-triggered intent
     */
    protected function handleKeywordIntent(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $originalMessage
    ): array {
        $automation = $intent['automation'];

        if (! $automation) {
            return ['success' => false, 'error' => 'Automation topilmadi'];
        }

        // Flow-based automation
        if ($automation->is_flow_based && $automation->flow_data) {
            $triggerNode = $intent['trigger_node'] ?? null;

            return $this->executeFlow(
                $automation,
                $account,
                $conversation,
                [
                    'text' => $originalMessage,
                    'keyword' => $intent['value'],
                    'name' => $conversation->display_name,
                    'username' => $conversation->participant_username,
                ],
                $triggerNode
            );
        }

        // Action-based automation
        return $this->executeActionBasedAutomation($automation, $account, $conversation, $originalMessage);
    }

    /**
     * Handle payload/button click intent
     */
    protected function handlePayloadIntent(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation
    ): array {
        // Agar next_node_id bo'lsa, shu node ga o'tish
        if (! empty($intent['next_node_id'])) {
            $automation = $intent['automation'] ?? $conversation->currentAutomation;

            if ($automation && $automation->flow_data) {
                return $this->continueFlowFromNode(
                    $automation,
                    $account,
                    $conversation,
                    $intent['next_node_id']
                );
            }
        }

        // Custom action
        if (! empty($intent['action_name'])) {
            return $this->handleCustomAction(
                $intent['action_name'],
                $account,
                $conversation,
                $intent['payload']
            );
        }

        return ['success' => true, 'action' => 'payload_processed'];
    }

    /**
     * Handle user input (when conversation is waiting for response)
     */
    protected function handleUserInput(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $userInput
    ): array {
        // Collected data ga qo'shish
        $collectedData = $conversation->collected_data ?? [];
        $questionField = $intent['question_field'] ?? 'response_' . time();

        $collectedData[$questionField] = $userInput;
        unset($collectedData['_last_question']); // Eski savolni o'chirish

        $conversation->update([
            'collected_data' => $collectedData,
            'status' => 'active',
        ]);

        // Keyingi nodega o'tish
        $automation = $conversation->currentAutomation;
        if ($automation && $automation->flow_data) {
            return $this->continueFlowToNextNode($automation, $account, $conversation);
        }

        return ['success' => true, 'action' => 'input_collected'];
    }

    /**
     * Handle general intent (price inquiry, order, etc.)
     *
     * MUHIM: Bu metod ChatbotIntentService->handleIntent() ni chaqirib,
     * lead yaratish va CRM integratsiyasini ta'minlaydi.
     */
    protected function handleGeneralIntent(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $originalMessage = ''
    ): array {
        $category = $intent['category'] ?? 'general';
        $value = $intent['value'] ?? '';

        // LEAD YARATISH: ChatbotIntentService orqali lead yaratish
        // Bu price_inquiry, order_intent va boshqa intentlar uchun
        $intentResult = $this->intentService->handleIntent($intent, $conversation, $originalMessage);

        Log::info('SocialChatbotService: Intent handled', [
            'intent_value' => $value,
            'lead_created' => $intentResult['lead_created'] ?? false,
            'lead_id' => $intentResult['lead']?->id ?? null,
            'handoff_required' => $intentResult['handoff_required'] ?? false,
        ]);

        // Agar handoff kerak bo'lsa - operatorga uzatish
        if ($intentResult['handoff_required'] ?? false) {
            if ($intentResult['auto_reply'] ?? null) {
                $this->sendMessage(
                    $account,
                    $conversation,
                    $intentResult['auto_reply'],
                    null
                );
            }

            return [
                'success' => true,
                'action' => 'handoff',
                'lead_created' => $intentResult['lead_created'],
                'lead' => $intentResult['lead'],
            ];
        }

        // Mos avtomatizatsiya qidirish
        $relevantAutomation = $this->findRelevantAutomation($account, $category, $value);

        if ($relevantAutomation) {
            return $this->startAutomation($relevantAutomation, $account, $conversation);
        }

        // Fallback javob (lead yaratilgan bo'lishi mumkin)
        $fallbackMessage = ($intentResult['lead_created'] ?? false)
            ? "Savolingiz qabul qilindi va mutaxassislarimizga yuborildi. Tez orada javob beramiz! 📩"
            : "Savolingiz qabul qilindi. Tez orada javob beramiz! 📩";

        $this->sendMessage(
            $account,
            $conversation,
            $fallbackMessage,
            null
        );

        return [
            'success' => true,
            'action' => 'general_fallback',
            'lead_created' => $intentResult['lead_created'] ?? false,
            'lead' => $intentResult['lead'] ?? null,
        ];
    }

    /**
     * Handle unknown intent
     */
    protected function handleUnknownIntent(
        array $intent,
        InstagramAccount $account,
        InstagramConversation $conversation
    ): array {
        // Agar aktiv automation bo'lsa, davom ettirish
        if ($conversation->current_automation_id) {
            $automation = $conversation->currentAutomation;
            if ($automation) {
                // Joriy nodeni takror ijro etish yoki keyingisiga o'tish
                return ['success' => true, 'action' => 'continue_flow'];
            }
        }

        // Agar needsHumanHandoff bo'lsa
        if ($this->intentService->needsHumanHandoff($intent)) {
            $conversation->update(['needs_human' => true]);

            return ['success' => true, 'action' => 'needs_human'];
        }

        // Fallback xabar
        $fallbackMessage = $this->getFallbackMessage($account);
        if ($fallbackMessage) {
            $this->sendMessage($account, $conversation, $fallbackMessage, null);
        }

        return ['success' => true, 'action' => 'fallback_sent'];
    }

    // ========================================
    // FLOW EXECUTION METHODS
    // ========================================

    /**
     * Start and execute a flow automation
     */
    public function startAutomation(
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation
    ): array {
        Log::info('SocialChatbotService: Starting automation', [
            'automation_id' => $automation->id,
            'automation_name' => $automation->name,
            'conversation_id' => $conversation->id,
        ]);

        // Log trigger
        $log = InstagramAutomationLog::logTrigger(
            $automation,
            'manual_start',
            null,
            $conversation
        );

        $automation->incrementTriggerCount();
        $conversation->startAutomation($automation);

        try {
            if ($automation->is_flow_based && $automation->flow_data) {
                $result = $this->executeFlow($automation, $account, $conversation, [
                    'name' => $conversation->display_name,
                    'username' => $conversation->participant_username,
                ]);
            } else {
                $result = $this->executeActionBasedAutomation($automation, $account, $conversation);
            }

            if ($result['success'] ?? false) {
                $automation->incrementConversionCount();
                $log->markCompleted();
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('SocialChatbotService: Automation failed', [
                'automation_id' => $automation->id,
                'error' => $e->getMessage(),
            ]);
            $log->markFailed($e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute flow-based automation
     */
    public function executeFlow(
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation,
        array $variables = [],
        ?array $startNode = null
    ): array {
        $flowData = $automation->flow_data;
        $nodes = $flowData['nodes'] ?? [];
        $edges = $flowData['edges'] ?? [];

        if (empty($nodes)) {
            return ['success' => false, 'error' => 'Flow nodes topilmadi'];
        }

        // Adjacency list yaratish (graph navigation uchun)
        $adjacencyList = $this->buildAdjacencyList($edges);

        // Boshlang'ich nodeni aniqlash
        if (! $startNode) {
            $startNode = $this->findTriggerNode($nodes);
        }

        if (! $startNode) {
            return ['success' => false, 'error' => 'Trigger node topilmadi'];
        }

        Log::info('SocialChatbotService: Executing flow', [
            'automation_id' => $automation->id,
            'start_node' => $startNode['node_id'] ?? 'unknown',
            'total_nodes' => count($nodes),
        ]);

        // Flowni bajarish
        $this->executeFlowNode(
            $startNode['node_id'],
            $nodes,
            $adjacencyList,
            $automation,
            $account,
            $conversation,
            $variables
        );

        return ['success' => true, 'action' => 'flow_executed'];
    }

    /**
     * Execute a single flow node
     */
    protected function executeFlowNode(
        string $nodeId,
        array $nodes,
        array $adjacencyList,
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation,
        array $variables
    ): void {
        // Nodeni topish
        $node = collect($nodes)->firstWhere('node_id', $nodeId);

        if (! $node) {
            Log::warning('SocialChatbotService: Node not found', ['node_id' => $nodeId]);

            return;
        }

        $nodeType = $node['node_type'] ?? '';
        $nodeData = $node['data'] ?? [];

        Log::info('SocialChatbotService: Executing node', [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
        ]);

        // Node turga qarab bajarish
        $conditionResult = null;

        switch ($nodeType) {
            // ========== ACTION NODES ==========
            case 'action_send_dm':
                $message = $this->replaceVariables($nodeData['message'] ?? '', $variables, $conversation);
                $this->sendMessage($account, $conversation, $message, $automation);
                break;

            case 'action_send_buttons':
            case 'action_send_dm_with_buttons':
                $message = $this->replaceVariables($nodeData['message'] ?? '', $variables, $conversation);
                $buttons = $nodeData['buttons'] ?? [];
                $this->sendButtons($account, $conversation, $message, $buttons, $automation);
                break;

            case 'action_send_media':
                $mediaUrl = $nodeData['media_url'] ?? '';
                $caption = $this->replaceVariables($nodeData['caption'] ?? '', $variables, $conversation);
                $this->sendMedia($account, $conversation, $mediaUrl, $caption, $automation);
                break;

            case 'action_send_link':
                $url = $nodeData['url'] ?? '';
                $linkMessage = $this->replaceVariables($nodeData['message'] ?? '', $variables, $conversation);
                $fullMessage = $linkMessage . "\n\n🔗 " . $url;
                $this->sendMessage($account, $conversation, $fullMessage, $automation);
                break;

            case 'action_delay':
                $seconds = $this->calculateDelay($nodeData);
                if ($seconds > 0 && $seconds <= 30) {
                    sleep($seconds); // Faqat 30 sekundgacha sinxron kutish
                }
                // TODO: Kattaroq delay lar uchun Job ishlatish
                break;

            case 'action_add_tag':
                $tag = $nodeData['tag'] ?? null;
                if ($tag) {
                    $conversation->addTag($tag);
                }
                break;

            case 'action_remove_tag':
                $tag = $nodeData['tag'] ?? null;
                if ($tag) {
                    $conversation->removeTag($tag);
                }
                break;

            case 'action_collect_data':
                $question = $this->replaceVariables($nodeData['question'] ?? '', $variables, $conversation);
                $fieldName = $nodeData['field_name'] ?? 'response_' . $nodeId;

                $this->sendMessage($account, $conversation, $question, $automation);

                // Suhbatni "waiting" holatiga o'tkazish
                $collectedData = $conversation->collected_data ?? [];
                $collectedData['_last_question'] = $fieldName;
                $collectedData['_waiting_node_id'] = $nodeId;

                $conversation->update([
                    'status' => 'waiting',
                    'collected_data' => $collectedData,
                ]);

                return; // Bu yerda to'xtash - foydalanuvchi javob kutiladi

            case 'action_human_handoff':
                $handoffMessage = $nodeData['message'] ?? "Operatorimiz tez orada bog'lanadi.";
                $this->sendMessage($account, $conversation, $handoffMessage, $automation);
                $conversation->update([
                    'needs_human' => true,
                    'is_bot_active' => false,
                ]);

                return; // Flow to'xtaydi

            case 'action_webhook':
                $webhookUrl = $nodeData['webhook_url'] ?? '';
                if ($webhookUrl) {
                    $this->callWebhook($webhookUrl, $conversation, $variables);
                }
                break;

            // ========== CONDITION NODES ==========
            case 'condition_is_follower':
                // TODO: API orqali follower ekanligini tekshirish
                $conditionResult = 'yes';
                break;

            case 'condition_has_tag':
                $checkTag = $nodeData['tag'] ?? '';
                $tags = $conversation->tags ?? [];
                $conditionResult = in_array($checkTag, $tags) ? 'yes' : 'no';
                break;

            case 'condition_custom':
                // Custom shart logikasi
                $conditionResult = $this->evaluateCustomCondition($nodeData, $conversation, $variables);
                break;

            // ========== TRIGGER NODES (skip) ==========
            case 'trigger_keyword_dm':
            case 'trigger_keyword_comment':
            case 'trigger_story_mention':
            case 'trigger_story_reply':
            case 'trigger_new_follower':
            case 'trigger_start':
                // Trigger nodelar hech narsa qilmaydi, faqat flow boshlanish nuqtasi
                break;
        }

        // Keyingi nodelarga o'tish
        $nextNodes = $adjacencyList[$nodeId] ?? [];

        foreach ($nextNodes as $next) {
            // Condition node bo'lsa, faqat mos handle ga o'tish
            if ($conditionResult !== null) {
                if ($next['handle'] !== $conditionResult) {
                    continue;
                }
            }

            $this->executeFlowNode(
                $next['target'],
                $nodes,
                $adjacencyList,
                $automation,
                $account,
                $conversation,
                $variables
            );
        }

        // Flow tugadi
        if (empty($nextNodes)) {
            $conversation->endAutomation();
        }
    }

    /**
     * Continue flow from specific node
     */
    public function continueFlowFromNode(
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $nodeId
    ): array {
        $flowData = $automation->flow_data;
        $nodes = $flowData['nodes'] ?? [];
        $edges = $flowData['edges'] ?? [];

        $adjacencyList = $this->buildAdjacencyList($edges);

        $variables = [
            'name' => $conversation->display_name,
            'username' => $conversation->participant_username,
        ];

        // Collected data ni variables ga qo'shish
        if ($conversation->collected_data) {
            $variables = array_merge($variables, $conversation->collected_data);
        }

        $this->executeFlowNode(
            $nodeId,
            $nodes,
            $adjacencyList,
            $automation,
            $account,
            $conversation,
            $variables
        );

        return ['success' => true, 'action' => 'flow_continued'];
    }

    /**
     * Continue flow to next connected node
     */
    protected function continueFlowToNextNode(
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation
    ): array {
        $collectedData = $conversation->collected_data ?? [];
        $waitingNodeId = $collectedData['_waiting_node_id'] ?? null;

        if (! $waitingNodeId) {
            return ['success' => false, 'error' => 'Waiting node ID topilmadi'];
        }

        $flowData = $automation->flow_data;
        $edges = $flowData['edges'] ?? [];

        // Kutilayotgan nodening keyingi nodesi ni topish
        $nextNodeId = null;
        foreach ($edges as $edge) {
            if ($edge['source_node_id'] === $waitingNodeId) {
                $nextNodeId = $edge['target_node_id'];
                break;
            }
        }

        if (! $nextNodeId) {
            $conversation->endAutomation();

            return ['success' => true, 'action' => 'flow_completed'];
        }

        // _waiting_node_id ni tozalash
        unset($collectedData['_waiting_node_id']);
        $conversation->update(['collected_data' => $collectedData]);

        return $this->continueFlowFromNode($automation, $account, $conversation, $nextNodeId);
    }

    // ========================================
    // MESSAGE SENDING METHODS
    // ========================================

    /**
     * Send simple text message
     *
     * 24-HOUR EXCEPTION HANDLING: Agar oyna yopilgan bo'lsa, xatolik ushlangan
     * va xabar 'failed' holati bilan bazaga saqlanadi.
     */
    public function sendMessage(
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $message,
        ?InstagramAutomation $automation = null
    ): array {
        $accessToken = $this->getAccessToken($account);

        if (! $accessToken) {
            Log::warning('SocialChatbotService: No access token', ['account_id' => $account->id]);

            return ['success' => false, 'error' => 'Access token topilmadi'];
        }

        try {
            $response = $this->sendInstagramDM(
                $account->instagram_id,
                $conversation->participant_id,
                ['text' => $message],
                $accessToken,
                $conversation // Pass conversation for error handling
            );

            if ($response['success']) {
                $this->saveOutgoingMessage($conversation, $message, 'text', $automation, $response['message_id'] ?? null);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('SocialChatbotService: sendMessage exception', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            // Tizim qulamasin - xatolikni qaytaramiz
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send message with Quick Reply buttons
     *
     * Instagram Quick Reply formati:
     * - Maksimum 13 ta tugma
     * - Har bir tugma title 20 belgigacha
     * - Payload 1000 belgigacha
     * - Faqat mobil ilovada ko'rinadi
     *
     * 24-HOUR EXCEPTION HANDLING: Agar oyna yopilgan bo'lsa, xatolik ushlangan
     * va xabar 'failed' holati bilan bazaga saqlanadi.
     */
    public function sendButtons(
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $message,
        array $buttons,
        ?InstagramAutomation $automation = null
    ): array {
        $accessToken = $this->getAccessToken($account);

        if (! $accessToken) {
            return ['success' => false, 'error' => 'Access token topilmadi'];
        }

        try {
            // Tugmalarni formatlash
            $quickReplies = $this->formatQuickReplies($buttons);

            if (empty($quickReplies)) {
                // Tugma yo'q - oddiy xabar yuborish
                return $this->sendMessage($account, $conversation, $message, $automation);
            }

            // Instagram API ga yuborish
            $messagePayload = [
                'text' => $message,
                'quick_replies' => $quickReplies,
            ];

            $response = $this->sendInstagramDM(
                $account->instagram_id,
                $conversation->participant_id,
                $messagePayload,
                $accessToken,
                $conversation // Pass conversation for error handling
            );

            if ($response['success']) {
                // Xabarni saqlash (tugmalar bilan)
                $this->saveOutgoingMessage(
                    $conversation,
                    $message,
                    'buttons',
                    $automation,
                    $response['message_id'] ?? null,
                    ['buttons' => $buttons]
                );

                // Web versiya uchun alternativ (matnli variant)
                // Instagram Quick Replies faqat mobileda ishlaydi
                $this->sendTextAlternativeForButtons($account, $conversation, $buttons, $automation);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('SocialChatbotService: sendButtons exception', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            // Tizim qulamasin - xatolikni qaytaramiz
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send media (image/video)
     *
     * 24-HOUR EXCEPTION HANDLING: Agar oyna yopilgan bo'lsa, xatolik ushlangan.
     */
    public function sendMedia(
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $mediaUrl,
        string $caption = '',
        ?InstagramAutomation $automation = null
    ): array {
        $accessToken = $this->getAccessToken($account);

        if (! $accessToken) {
            return ['success' => false, 'error' => 'Access token topilmadi'];
        }

        try {
            // Media turi aniqlash
            $mediaType = $this->detectMediaType($mediaUrl);

            $messagePayload = [
                'attachment' => [
                    'type' => $mediaType,
                    'payload' => [
                        'url' => $mediaUrl,
                        'is_reusable' => true,
                    ],
                ],
            ];

            $response = $this->sendInstagramDM(
                $account->instagram_id,
                $conversation->participant_id,
                $messagePayload,
                $accessToken,
                $conversation // Pass conversation for error handling
            );

            if ($response['success']) {
                $this->saveOutgoingMessage(
                    $conversation,
                    $caption ?: '[Media]',
                    'media',
                    $automation,
                    $response['message_id'] ?? null,
                    ['media_url' => $mediaUrl, 'media_type' => $mediaType]
                );

                // Agar caption bo'lsa, alohida xabar sifatida yuborish
                if ($caption) {
                    $this->sendMessage($account, $conversation, $caption, $automation);
                }
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('SocialChatbotService: sendMedia exception', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Instagram DM via Graph API
     *
     * 24-HOUR WINDOW HANDLING:
     * - Agar "(#10) This message is sent outside of allowed window" xatosi kelsa
     * - Tizim qulamaydi, xabar 'failed' statusi bilan saqlanadi
     */
    protected function sendInstagramDM(
        string $igUserId,
        string $recipientId,
        array $messagePayload,
        string $accessToken,
        ?InstagramConversation $conversation = null
    ): array {
        try {
            $response = Http::post($this->graphApiUrl . "/{$igUserId}/messages", [
                'recipient' => ['id' => $recipientId],
                'message' => $messagePayload,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                    'recipient_id' => $response->json('recipient_id'),
                ];
            }

            // ========================================
            // 24-HOUR WINDOW ERROR HANDLING
            // ========================================
            $errorCode = $response->json('error.code');
            $errorMessage = $response->json('error.message') ?? 'API error';
            $errorSubcode = $response->json('error.error_subcode');

            // 24-soatlik oyna yopilgan
            if ($errorCode === self::ERROR_OUTSIDE_WINDOW
                || str_contains(strtolower($errorMessage), 'outside of allowed window')
                || str_contains(strtolower($errorMessage), '24 hour')
            ) {
                Log::warning('SocialChatbotService: 24-hour window expired', [
                    'recipient_id' => $recipientId,
                    'error_code' => $errorCode,
                ]);

                // Xabarni failed holati bilan saqlash
                $this->saveFailedMessage(
                    $conversation,
                    $messagePayload['text'] ?? json_encode($messagePayload),
                    '24h_window',
                    $errorMessage
                );

                return [
                    'success' => false,
                    'error' => '24h_window_expired',
                    'error_message' => $errorMessage,
                    'recoverable' => false,
                ];
            }

            // Rate limiting
            if ($errorCode === 4 || $errorCode === 17 || $errorCode === 613) {
                Log::warning('SocialChatbotService: Rate limit hit', [
                    'recipient_id' => $recipientId,
                    'error_code' => $errorCode,
                ]);

                return [
                    'success' => false,
                    'error' => 'rate_limit',
                    'error_message' => $errorMessage,
                    'recoverable' => true,
                    'retry_after' => 60, // 1 daqiqadan keyin qayta urinish
                ];
            }

            // Boshqa xatoliklar
            Log::error('SocialChatbotService: Send DM failed', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error' => $errorMessage,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $errorCode,
            ];

        } catch (\Exception $e) {
            Log::error('SocialChatbotService: Send DM exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save failed message to database
     */
    protected function saveFailedMessage(
        ?InstagramConversation $conversation,
        string $content,
        string $errorReason,
        string $errorMessage
    ): void {
        if (! $conversation) {
            return;
        }

        try {
            InstagramMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outgoing',
                'message_type' => 'text',
                'content' => $content,
                'status' => 'failed',
                'is_automated' => true,
                'media_data' => [
                    'error_reason' => $errorReason,
                    'error_message' => $errorMessage,
                    'failed_at' => now()->toISOString(),
                ],
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('SocialChatbotService: Could not save failed message', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format buttons to Instagram Quick Reply format
     */
    protected function formatQuickReplies(array $buttons): array
    {
        $quickReplies = [];

        // Instagram limiti: maksimum 13 ta
        $buttons = array_slice($buttons, 0, self::MAX_QUICK_REPLIES);

        foreach ($buttons as $button) {
            // String yoki array bo'lishi mumkin
            if (is_string($button)) {
                $title = $button;
                $payload = $button;
            } else {
                $title = $button['title'] ?? $button['label'] ?? $button['text'] ?? '';
                $payload = $button['payload'] ?? $button['value'] ?? $button['action'] ?? $title;
            }

            if (empty($title)) {
                continue;
            }

            // Title uzunligi tekshirish (max 20 belgi)
            if (mb_strlen($title) > self::MAX_BUTTON_TITLE_LENGTH) {
                $title = mb_substr($title, 0, self::MAX_BUTTON_TITLE_LENGTH - 1) . '…';
            }

            $quickReplies[] = [
                'content_type' => 'text',
                'title' => $title,
                'payload' => is_string($payload) ? $payload : json_encode($payload),
            ];
        }

        return $quickReplies;
    }

    /**
     * Send text alternative for buttons (for web users)
     * Instagram Quick Replies faqat mobil ilovada ishlaydi
     */
    protected function sendTextAlternativeForButtons(
        InstagramAccount $account,
        InstagramConversation $conversation,
        array $buttons,
        ?InstagramAutomation $automation
    ): void {
        // Agar faqat 1-2 ta tugma bo'lsa, alternativ kerak emas
        if (count($buttons) <= 2) {
            return;
        }

        // Matnli alternativ yaratish
        $textOptions = [];
        foreach ($buttons as $index => $button) {
            $title = is_string($button) ? $button : ($button['title'] ?? $button['label'] ?? '');
            if ($title) {
                $textOptions[] = ($index + 1) . '. ' . $title;
            }
        }

        if (! empty($textOptions)) {
            $alternativeText = "💡 Mobil ilovada tugmalar ko'rinadi.\nYoki raqam yozib tanlang:\n\n" . implode("\n", $textOptions);

            // Bu xabarni yubormaslik ham mumkin - shunchaki log qilamiz
            Log::debug('SocialChatbotService: Button text alternative', [
                'options' => $textOptions,
            ]);
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get or create conversation with User Profile Sync
     *
     * Agar foydalanuvchi bazada yo'q bo'lsa yoki profil eskirgan bo'lsa,
     * Instagram API dan yangi ma'lumot yuklanadi.
     */
    protected function getOrCreateConversation(
        InstagramAccount $account,
        string $senderId,
        array $webhookData
    ): InstagramConversation {
        $conversation = InstagramConversation::firstOrNew(
            [
                'account_id' => $account->id,
                'participant_id' => $senderId,
            ],
            [
                'conversation_id' => $webhookData['message_id'] ?? uniqid('conv_'),
                'status' => 'active',
                'is_bot_active' => true,
            ]
        );

        // ========================================
        // USER PROFILE SYNC - Foydalanuvchi profilini yuklash
        // ========================================
        $needsProfileSync = ! $conversation->exists
            || empty($conversation->participant_name)
            || $this->isProfileStale($conversation);

        if ($needsProfileSync) {
            $profileData = $this->fetchUserProfile($senderId, $account);

            if ($profileData) {
                $conversation->participant_name = $profileData['name'] ?? $conversation->participant_name;
                $conversation->participant_username = $profileData['username'] ?? $conversation->participant_username;
                $conversation->profile_picture_url = $profileData['profile_pic'] ?? $conversation->profile_picture_url;
                $conversation->profile_synced_at = now();

                // SocialUser jadvaliga ham saqlash
                $this->syncSocialUser($senderId, $account, $profileData);
            }
        }

        // Webhook dan kelgan ma'lumotlarni qo'llash (agar API dan olmagan bo'lsak)
        if (empty($conversation->participant_username) && ! empty($webhookData['sender_username'])) {
            $conversation->participant_username = $webhookData['sender_username'];
        }
        if (empty($conversation->participant_name) && ! empty($webhookData['sender_name'])) {
            $conversation->participant_name = $webhookData['sender_name'];
        }
        if (empty($conversation->profile_picture_url) && ! empty($webhookData['sender_profile_picture'])) {
            $conversation->profile_picture_url = $webhookData['sender_profile_picture'];
        }

        $conversation->save();

        return $conversation;
    }

    /**
     * Check if user profile is stale (needs refresh)
     */
    protected function isProfileStale(InstagramConversation $conversation): bool
    {
        if (! $conversation->profile_synced_at) {
            return true;
        }

        // 12 soatdan eski bo'lsa - yangilash kerak
        return $conversation->profile_synced_at->diffInHours(now()) >= self::PROFILE_CACHE_HOURS;
    }

    /**
     * Fetch user profile from Instagram Graph API
     *
     * API: GET /{psid}?fields=name,profile_pic&access_token={token}
     */
    protected function fetchUserProfile(string $userId, InstagramAccount $account): ?array
    {
        $accessToken = $this->getAccessToken($account);

        if (! $accessToken) {
            return null;
        }

        // Cache check - bir xil so'rovlarni kamaytirish
        $cacheKey = "instagram_profile_{$userId}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::get($this->graphApiUrl . "/{$userId}", [
                'fields' => 'name,profile_pic,username',
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Cache for 12 hours
                Cache::put($cacheKey, $data, now()->addHours(self::PROFILE_CACHE_HOURS));

                Log::debug('SocialChatbotService: User profile fetched', [
                    'user_id' => $userId,
                    'name' => $data['name'] ?? null,
                ]);

                return $data;
            }

            Log::warning('SocialChatbotService: Failed to fetch user profile', [
                'user_id' => $userId,
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? 'Unknown',
            ]);

            return null;

        } catch (\Exception $e) {
            Log::warning('SocialChatbotService: Profile fetch exception', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync user to SocialUser table
     */
    protected function syncSocialUser(string $psid, InstagramAccount $account, array $profileData): void
    {
        try {
            SocialUser::updateOrCreate(
                [
                    'platform' => 'instagram',
                    'platform_user_id' => $psid,
                ],
                [
                    'business_id' => $account->business_id,
                    'account_id' => $account->id,
                    'username' => $profileData['username'] ?? null,
                    'full_name' => $profileData['name'] ?? null,
                    'profile_picture' => $profileData['profile_pic'] ?? null,
                    'last_interaction_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            // SocialUser jadvali yo'q bo'lishi mumkin - xatolikni skip qilamiz
            Log::debug('SocialChatbotService: Could not sync SocialUser', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Save incoming message
     */
    protected function saveIncomingMessage(
        InstagramConversation $conversation,
        string $message,
        array $webhookData
    ): InstagramMessage {
        $messageType = 'text';

        if (! empty($webhookData['attachments'])) {
            $messageType = 'media';
        } elseif (! empty($webhookData['payload'])) {
            $messageType = 'quick_reply';
        }

        $msg = InstagramMessage::create([
            'conversation_id' => $conversation->id,
            'instagram_message_id' => $webhookData['message_id'] ?? null,
            'direction' => 'incoming',
            'message_type' => $messageType,
            'content' => $message,
            'is_automated' => false,
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $msg;
    }

    /**
     * Save outgoing message
     */
    protected function saveOutgoingMessage(
        InstagramConversation $conversation,
        string $content,
        string $messageType,
        ?InstagramAutomation $automation,
        ?string $messageId = null,
        array $metadata = []
    ): InstagramMessage {
        $msg = InstagramMessage::create([
            'conversation_id' => $conversation->id,
            'automation_id' => $automation?->id,
            'instagram_message_id' => $messageId,
            'direction' => 'outgoing',
            'message_type' => $messageType,
            'content' => $content,
            'is_automated' => $automation !== null,
            'media_data' => ! empty($metadata) ? $metadata : null,
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $msg;
    }

    /**
     * Get access token for account
     */
    protected function getAccessToken(InstagramAccount $account): ?string
    {
        // Avval akkauntning o'z tokenini tekshirish
        if ($account->access_token) {
            return $account->access_token;
        }

        // Integration orqali olish
        $integration = $account->integration;

        return $integration?->getAccessToken();
    }

    /**
     * Build adjacency list from edges
     */
    protected function buildAdjacencyList(array $edges): array
    {
        $adjacencyList = [];

        foreach ($edges as $edge) {
            $sourceId = $edge['source_node_id'] ?? $edge['source'] ?? null;
            $targetId = $edge['target_node_id'] ?? $edge['target'] ?? null;

            if (! $sourceId || ! $targetId) {
                continue;
            }

            if (! isset($adjacencyList[$sourceId])) {
                $adjacencyList[$sourceId] = [];
            }

            $adjacencyList[$sourceId][] = [
                'target' => $targetId,
                'handle' => $edge['source_handle'] ?? $edge['sourceHandle'] ?? null,
            ];
        }

        return $adjacencyList;
    }

    /**
     * Find trigger node in flow
     */
    protected function findTriggerNode(array $nodes): ?array
    {
        foreach ($nodes as $node) {
            $nodeType = $node['node_type'] ?? '';
            if (str_starts_with($nodeType, 'trigger_')) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Replace variables in message
     */
    protected function replaceVariables(
        string $message,
        array $variables,
        InstagramConversation $conversation
    ): string {
        // Asosiy o'zgaruvchilar
        $replacements = [
            '{name}' => $variables['name'] ?? $conversation->display_name ?? 'do\'st',
            '{full_name}' => $variables['name'] ?? $conversation->display_name ?? 'do\'st',
            '{username}' => $variables['username'] ?? $conversation->participant_username ?? '',
            '{keyword}' => $variables['keyword'] ?? '',
            '{text}' => $variables['text'] ?? '',
        ];

        // Collected data dan o'zgaruvchilar
        $collectedData = $conversation->collected_data ?? [];
        foreach ($collectedData as $key => $value) {
            if (! str_starts_with($key, '_')) { // _ bilan boshlanuvchi internal fieldlarni skip
                $replacements['{' . $key . '}'] = $value;
            }
        }

        // Custom variables
        foreach ($variables as $key => $value) {
            if (is_string($value)) {
                $replacements['{' . $key . '}'] = $value;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Calculate delay in seconds
     */
    protected function calculateDelay(array $nodeData): int
    {
        $delayType = $nodeData['delay_type'] ?? 'seconds';
        $delayValue = (int) ($nodeData['delay_value'] ?? $nodeData['seconds'] ?? 0);

        return match ($delayType) {
            'minutes' => $delayValue * 60,
            'hours' => $delayValue * 3600,
            default => $delayValue,
        };
    }

    /**
     * Detect media type from URL
     */
    protected function detectMediaType(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'webm'];

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        }

        if (in_array($extension, $videoExtensions)) {
            return 'video';
        }

        return 'file';
    }

    /**
     * Find "Start" automation for account
     */
    protected function findStartAutomation(InstagramAccount $account): ?InstagramAutomation
    {
        return InstagramAutomation::where('account_id', $account->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('name', 'like', '%start%')
                    ->orWhere('name', 'like', '%boshlash%')
                    ->orWhere('type', 'welcome');
            })
            ->first();
    }

    /**
     * Find main menu automation
     */
    protected function findMainMenuAutomation(InstagramAccount $account): ?InstagramAutomation
    {
        return InstagramAutomation::where('account_id', $account->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('name', 'like', '%menu%')
                    ->orWhere('name', 'like', '%menyu%');
            })
            ->first();
    }

    /**
     * Find relevant automation for general intent
     */
    protected function findRelevantAutomation(
        InstagramAccount $account,
        string $category,
        string $value
    ): ?InstagramAutomation {
        // TODO: Kategoriya va qiymat asosida mos avtomatizatsiya qidirish
        return null;
    }

    /**
     * Get fallback message for account
     */
    protected function getFallbackMessage(InstagramAccount $account): ?string
    {
        // TODO: Account sozlamalaridan olish
        return "Tushundim! Iltimos, aniqroq savolingizni yozing yoki \"start\" deb yozing. 🙂";
    }

    /**
     * Execute action-based (non-flow) automation
     */
    protected function executeActionBasedAutomation(
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $triggerText = ''
    ): array {
        $actions = $automation->actions()->orderBy('order')->get();

        $variables = [
            'name' => $conversation->display_name,
            'username' => $conversation->participant_username,
            'text' => $triggerText,
        ];

        foreach ($actions as $action) {
            $this->executeAction($action, $account, $conversation, $variables, $automation);
            $conversation->advanceStep();
        }

        $conversation->endAutomation();

        return ['success' => true, 'action' => 'automation_executed'];
    }

    /**
     * Execute single action
     */
    protected function executeAction(
        $action,
        InstagramAccount $account,
        InstagramConversation $conversation,
        array $variables,
        InstagramAutomation $automation
    ): void {
        $actionType = $action->action_type;

        switch ($actionType) {
            case 'send_dm':
                $message = $this->replaceVariables($action->message_template ?? '', $variables, $conversation);
                $this->sendMessage($account, $conversation, $message, $automation);
                break;

            case 'send_dm_with_buttons':
                $message = $this->replaceVariables($action->message_template ?? '', $variables, $conversation);
                $buttons = $action->buttons ?? [];
                $this->sendButtons($account, $conversation, $message, $buttons, $automation);
                break;

            case 'delay':
                $seconds = $action->delay_seconds ?? 0;
                if ($seconds > 0 && $seconds <= 30) {
                    sleep($seconds);
                }
                break;

            case 'add_tag':
                $settings = $action->settings ?? [];
                $tag = $settings['tag'] ?? null;
                if ($tag) {
                    $conversation->addTag($tag);
                }
                break;
        }
    }

    /**
     * Handle custom action from payload
     */
    protected function handleCustomAction(
        string $actionName,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $payload
    ): array {
        // TODO: Custom action logikasi
        Log::info('SocialChatbotService: Custom action', [
            'action' => $actionName,
            'payload' => $payload,
        ]);

        return ['success' => true, 'action' => 'custom_action_handled'];
    }

    /**
     * Call external webhook
     */
    protected function callWebhook(string $url, InstagramConversation $conversation, array $variables): void
    {
        try {
            Http::timeout(10)->post($url, [
                'conversation_id' => $conversation->id,
                'participant' => [
                    'id' => $conversation->participant_id,
                    'username' => $conversation->participant_username,
                    'name' => $conversation->display_name,
                ],
                'collected_data' => $conversation->collected_data,
                'tags' => $conversation->tags,
                'variables' => $variables,
            ]);
        } catch (\Exception $e) {
            Log::warning('SocialChatbotService: Webhook call failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Evaluate custom condition
     */
    protected function evaluateCustomCondition(
        array $nodeData,
        InstagramConversation $conversation,
        array $variables
    ): string {
        // TODO: Custom condition logikasi
        return 'yes';
    }
}
