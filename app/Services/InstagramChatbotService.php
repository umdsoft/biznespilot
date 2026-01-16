<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramAutomation;
use App\Models\InstagramAutomationAction;
use App\Models\InstagramAutomationLog;
use App\Models\InstagramAutomationTrigger;
use App\Models\InstagramConversation;
use App\Models\InstagramMessage;
use App\Models\InstagramBroadcast;
use App\Models\InstagramQuickReply;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramChatbotService
{
    protected string $graphApiUrl = 'https://graph.facebook.com/v18.0';

    /**
     * Get chatbot dashboard stats
     */
    public function getDashboardStats(string $accountId): array
    {
        $account = InstagramAccount::findOrFail($accountId);

        $today = now()->startOfDay();
        $lastWeek = now()->subDays(7)->startOfDay();
        $lastMonth = now()->subDays(30)->startOfDay();

        // Automation stats
        $automations = $account->automations();
        $totalAutomations = $automations->count();
        $activeAutomations = $automations->active()->count();

        // Trigger stats
        $totalTriggers = InstagramAutomationLog::whereIn('automation_id', $account->automations()->pluck('id'))
            ->count();
        $triggersToday = InstagramAutomationLog::whereIn('automation_id', $account->automations()->pluck('id'))
            ->where('created_at', '>=', $today)
            ->count();
        $triggersWeek = InstagramAutomationLog::whereIn('automation_id', $account->automations()->pluck('id'))
            ->where('created_at', '>=', $lastWeek)
            ->count();

        // Conversation stats
        $conversations = $account->conversations();
        $totalConversations = $conversations->count();
        $activeConversations = $conversations->active()->count();
        $needsHumanCount = $conversations->needsHuman()->count();

        // Message stats
        $totalMessages = InstagramMessage::whereIn('conversation_id', $account->conversations()->pluck('id'))
            ->count();
        $automatedMessages = InstagramMessage::whereIn('conversation_id', $account->conversations()->pluck('id'))
            ->where('is_automated', true)
            ->count();

        // Calculate automation rate
        $automationRate = $totalMessages > 0
            ? round(($automatedMessages / $totalMessages) * 100, 1)
            : 0;

        // Top performing automations
        $topAutomations = $account->automations()
            ->orderByDesc('trigger_count')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'triggers' => $a->trigger_count,
                'conversions' => $a->conversion_count,
                'conversion_rate' => $a->conversion_rate,
                'status' => $a->status,
            ]);

        // Recent conversations
        $recentConversations = $account->conversations()
            ->with('latestMessage')
            ->orderByDesc('last_message_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'username' => $c->participant_username,
                'name' => $c->display_name,
                'profile_picture' => $c->profile_picture_url,
                'last_message' => $c->latestMessage?->preview,
                'last_message_at' => $c->last_message_at?->diffForHumans(),
                'status' => $c->status,
                'needs_human' => $c->needs_human,
                'tags' => $c->tags ?? [],
            ]);

        return [
            'automations' => [
                'total' => $totalAutomations,
                'active' => $activeAutomations,
            ],
            'triggers' => [
                'total' => $totalTriggers,
                'today' => $triggersToday,
                'week' => $triggersWeek,
            ],
            'conversations' => [
                'total' => $totalConversations,
                'active' => $activeConversations,
                'needs_human' => $needsHumanCount,
            ],
            'messages' => [
                'total' => $totalMessages,
                'automated' => $automatedMessages,
                'automation_rate' => $automationRate,
            ],
            'top_automations' => $topAutomations,
            'recent_conversations' => $recentConversations,
        ];
    }

    /**
     * Get all automations for an account
     */
    public function getAutomations(string $accountId): array
    {
        $automations = InstagramAutomation::where('account_id', $accountId)
            ->with(['triggers', 'actions'])
            ->orderByDesc('updated_at')
            ->get();

        return $automations->map(fn($a) => $this->formatAutomation($a))->toArray();
    }

    /**
     * Create a new automation
     */
    public function createAutomation(string $accountId, array $data): InstagramAutomation
    {
        $automation = InstagramAutomation::create([
            'account_id' => $accountId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'type' => $data['type'] ?? 'keyword',
            'is_ai_enabled' => $data['is_ai_enabled'] ?? false,
            'settings' => $data['settings'] ?? null,
        ]);

        // Create triggers
        if (!empty($data['triggers'])) {
            foreach ($data['triggers'] as $triggerData) {
                $automation->triggers()->create([
                    'trigger_type' => $triggerData['trigger_type'],
                    'keywords' => $triggerData['keywords'] ?? null,
                    'media_id' => $triggerData['media_id'] ?? null,
                    'case_sensitive' => $triggerData['case_sensitive'] ?? false,
                    'exact_match' => $triggerData['exact_match'] ?? false,
                ]);
            }
        }

        // Create actions
        if (!empty($data['actions'])) {
            foreach ($data['actions'] as $index => $actionData) {
                $automation->actions()->create([
                    'order' => $index,
                    'action_type' => $actionData['action_type'],
                    'message_template' => $actionData['message_template'] ?? null,
                    'buttons' => $actionData['buttons'] ?? null,
                    'media' => $actionData['media'] ?? null,
                    'condition_rules' => $actionData['condition_rules'] ?? null,
                    'delay_seconds' => $actionData['delay_seconds'] ?? null,
                    'webhook_url' => $actionData['webhook_url'] ?? null,
                    'settings' => $actionData['settings'] ?? null,
                ]);
            }
        }

        return $automation->fresh(['triggers', 'actions']);
    }

    /**
     * Update an automation
     */
    public function updateAutomation(string $automationId, array $data): InstagramAutomation
    {
        $automation = InstagramAutomation::findOrFail($automationId);

        $automation->update([
            'name' => $data['name'] ?? $automation->name,
            'description' => $data['description'] ?? $automation->description,
            'status' => $data['status'] ?? $automation->status,
            'type' => $data['type'] ?? $automation->type,
            'is_ai_enabled' => $data['is_ai_enabled'] ?? $automation->is_ai_enabled,
            'settings' => $data['settings'] ?? $automation->settings,
        ]);

        // Update triggers if provided
        if (isset($data['triggers'])) {
            $automation->triggers()->delete();
            foreach ($data['triggers'] as $triggerData) {
                $automation->triggers()->create([
                    'trigger_type' => $triggerData['trigger_type'],
                    'keywords' => $triggerData['keywords'] ?? null,
                    'media_id' => $triggerData['media_id'] ?? null,
                    'case_sensitive' => $triggerData['case_sensitive'] ?? false,
                    'exact_match' => $triggerData['exact_match'] ?? false,
                ]);
            }
        }

        // Update actions if provided
        if (isset($data['actions'])) {
            $automation->actions()->delete();
            foreach ($data['actions'] as $index => $actionData) {
                $automation->actions()->create([
                    'order' => $index,
                    'action_type' => $actionData['action_type'],
                    'message_template' => $actionData['message_template'] ?? null,
                    'buttons' => $actionData['buttons'] ?? null,
                    'media' => $actionData['media'] ?? null,
                    'condition_rules' => $actionData['condition_rules'] ?? null,
                    'delay_seconds' => $actionData['delay_seconds'] ?? null,
                    'webhook_url' => $actionData['webhook_url'] ?? null,
                    'settings' => $actionData['settings'] ?? null,
                ]);
            }
        }

        return $automation->fresh(['triggers', 'actions']);
    }

    /**
     * Delete an automation
     */
    public function deleteAutomation(string $automationId): bool
    {
        $automation = InstagramAutomation::findOrFail($automationId);
        return $automation->delete();
    }

    /**
     * Toggle automation status
     */
    public function toggleAutomation(string $automationId): InstagramAutomation
    {
        $automation = InstagramAutomation::findOrFail($automationId);

        $newStatus = $automation->status === 'active' ? 'paused' : 'active';
        $automation->update(['status' => $newStatus]);

        return $automation;
    }

    /**
     * Get conversations list
     */
    public function getConversations(string $accountId, array $filters = []): array
    {
        $query = InstagramConversation::where('account_id', $accountId)
            ->with(['latestMessage', 'currentAutomation']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['needs_human'])) {
            $query->where('needs_human', true);
        }

        if (!empty($filters['tag'])) {
            $query->withTag($filters['tag']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('participant_username', 'like', "%{$search}%")
                    ->orWhere('participant_name', 'like', "%{$search}%");
            });
        }

        $conversations = $query->orderByDesc('last_message_at')
            ->paginate($filters['per_page'] ?? 20);

        return [
            'data' => $conversations->items(),
            'total' => $conversations->total(),
            'per_page' => $conversations->perPage(),
            'current_page' => $conversations->currentPage(),
            'last_page' => $conversations->lastPage(),
        ];
    }

    /**
     * Get conversation detail with messages
     */
    public function getConversation(string $conversationId): array
    {
        $conversation = InstagramConversation::with(['messages', 'currentAutomation'])
            ->findOrFail($conversationId);

        // Mark messages as read
        $conversation->messages()->incoming()->update(['is_read' => true]);

        return [
            'conversation' => [
                'id' => $conversation->id,
                'username' => $conversation->participant_username,
                'name' => $conversation->display_name,
                'profile_picture' => $conversation->profile_picture_url,
                'status' => $conversation->status,
                'tags' => $conversation->tags ?? [],
                'collected_data' => $conversation->collected_data ?? [],
                'is_bot_active' => $conversation->is_bot_active,
                'needs_human' => $conversation->needs_human,
                'current_automation' => $conversation->currentAutomation?->name,
            ],
            'messages' => $conversation->messages->map(fn($m) => [
                'id' => $m->id,
                'direction' => $m->direction,
                'type' => $m->message_type,
                'content' => $m->content,
                'media' => $m->media_data,
                'is_automated' => $m->is_automated,
                'sent_at' => $m->sent_at?->format('Y-m-d H:i:s'),
                'time_ago' => $m->time_ago,
            ]),
        ];
    }

    /**
     * Send a manual message
     */
    public function sendMessage(string $conversationId, string $message): ?InstagramMessage
    {
        $conversation = InstagramConversation::with('instagramAccount.integration')
            ->findOrFail($conversationId);

        $integration = $conversation->instagramAccount->integration;
        if (!$integration || !$integration->access_token) {
            throw new \Exception('Instagram integratsiyasi topilmadi');
        }

        // Send via Instagram API
        $response = $this->sendDM(
            $conversation->instagramAccount->instagram_id,
            $conversation->participant_id,
            $message,
            $integration->access_token
        );

        if (!$response['success']) {
            throw new \Exception($response['error'] ?? 'Xabar yuborishda xatolik');
        }

        // Save message locally
        $newMessage = InstagramMessage::create([
            'conversation_id' => $conversation->id,
            'instagram_message_id' => $response['message_id'] ?? null,
            'direction' => 'outgoing',
            'message_type' => 'text',
            'content' => $message,
            'is_automated' => false,
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $newMessage;
    }

    /**
     * Process incoming webhook event
     */
    public function processWebhook(array $data): void
    {
        // This method will be called from webhook controller
        // to process incoming messages, comments, etc.

        $eventType = $data['type'] ?? null;

        switch ($eventType) {
            case 'message':
                $this->handleIncomingMessage($data);
                break;
            case 'comment':
                $this->handleIncomingComment($data);
                break;
            case 'story_mention':
                $this->handleStoryMention($data);
                break;
            case 'story_reply':
                $this->handleStoryReply($data);
                break;
        }
    }

    /**
     * Handle incoming DM
     */
    protected function handleIncomingMessage(array $data): void
    {
        $instagramId = $data['recipient_id'] ?? null;
        $senderId = $data['sender_id'] ?? null;
        $messageText = $data['message'] ?? '';

        if (!$instagramId || !$senderId) {
            return;
        }

        $account = InstagramAccount::where('instagram_id', $instagramId)->first();
        if (!$account) {
            return;
        }

        // Generate unique conversation_id based on account and sender
        $conversationId = 'conv_' . md5($account->instagram_id . '_' . $senderId);

        // Get or create conversation
        $conversation = InstagramConversation::firstOrCreate(
            [
                'account_id' => $account->id,
                'participant_id' => $senderId,
            ],
            [
                'conversation_id' => $conversationId,
                'participant_username' => $data['sender_username'] ?? null,
                'participant_name' => $data['sender_name'] ?? null,
                'profile_picture_url' => $data['sender_profile_picture'] ?? null,
                'status' => 'active',
            ]
        );

        // Save incoming message
        $instagramMsgId = $data['message_id'] ?? ('msg_' . uniqid());
        InstagramMessage::create([
            'conversation_id' => $conversation->id,
            'instagram_message_id' => $instagramMsgId,
            'message_id' => $instagramMsgId,
            'direction' => 'incoming',
            'message_type' => 'text',
            'content' => $messageText,
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Check for matching automations
        $this->checkAndTriggerAutomations($account, $conversation, $messageText, 'keyword_dm');
    }

    /**
     * Handle incoming comment
     */
    protected function handleIncomingComment(array $data): void
    {
        $instagramId = $data['media_owner_id'] ?? null;
        $commentText = $data['text'] ?? '';
        $commenterId = $data['commenter_id'] ?? null;

        if (!$instagramId || !$commenterId) {
            return;
        }

        $account = InstagramAccount::where('instagram_id', $instagramId)->first();
        if (!$account) {
            return;
        }

        // Check for matching automations
        $this->checkAndTriggerCommentAutomations($account, $data, $commentText);
    }

    /**
     * Handle story mention
     */
    protected function handleStoryMention(array $data): void
    {
        $instagramId = $data['mentioned_user_id'] ?? null;
        $mentionerId = $data['mentioner_id'] ?? null;

        if (!$instagramId || !$mentionerId) {
            return;
        }

        $account = InstagramAccount::where('instagram_id', $instagramId)->first();
        if (!$account) {
            return;
        }

        // Find story_mention automations
        $automations = $account->automations()
            ->active()
            ->whereHas('triggers', fn($q) => $q->where('trigger_type', 'story_mention'))
            ->get();

        foreach ($automations as $automation) {
            $this->executeAutomation($automation, $account, $mentionerId, 'story_mention', $data);
        }
    }

    /**
     * Handle story reply
     */
    protected function handleStoryReply(array $data): void
    {
        $instagramId = $data['story_owner_id'] ?? null;
        $replierId = $data['replier_id'] ?? null;
        $replyText = $data['reply_text'] ?? '';

        if (!$instagramId || !$replierId) {
            return;
        }

        $account = InstagramAccount::where('instagram_id', $instagramId)->first();
        if (!$account) {
            return;
        }

        // Find story_reply automations
        $automations = $account->automations()
            ->active()
            ->whereHas('triggers', fn($q) => $q->where('trigger_type', 'story_reply'))
            ->get();

        foreach ($automations as $automation) {
            $this->executeAutomation($automation, $account, $replierId, 'story_reply', $data);
        }
    }

    /**
     * Check and trigger automations for DM keywords
     */
    protected function checkAndTriggerAutomations(
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $text,
        string $triggerType
    ): void {
        // Get active automations with matching trigger type (traditional)
        $automations = $account->automations()
            ->active()
            ->whereHas('triggers', fn($q) => $q->where('trigger_type', $triggerType))
            ->with('triggers')
            ->get();

        foreach ($automations as $automation) {
            foreach ($automation->triggers as $trigger) {
                if ($trigger->trigger_type === $triggerType && $trigger->matches($text)) {
                    $keyword = $trigger->getMatchingKeyword($text);
                    $this->executeAutomation(
                        $automation,
                        $account,
                        $conversation->participant_id,
                        $triggerType,
                        ['keyword' => $keyword, 'text' => $text],
                        $conversation
                    );
                    return; // Only trigger first matching automation
                }
            }
        }

        // Also check flow-based automations
        $this->checkAndTriggerFlowAutomations($account, $conversation, $text, $triggerType);
    }

    /**
     * Check and trigger flow-based automations
     */
    protected function checkAndTriggerFlowAutomations(
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $text,
        string $triggerType
    ): void {
        // Map trigger type to flow node type
        $nodeTypeMap = [
            'keyword_dm' => 'trigger_keyword_dm',
            'keyword_comment' => 'trigger_keyword_comment',
            'story_mention' => 'trigger_story_mention',
            'story_reply' => 'trigger_story_reply',
            'new_follower' => 'trigger_new_follower',
        ];

        $flowNodeType = $nodeTypeMap[$triggerType] ?? null;
        if (!$flowNodeType) {
            return;
        }

        // Get active flow-based automations
        $flowAutomations = $account->automations()
            ->active()
            ->where('is_flow_based', true)
            ->whereNotNull('flow_data')
            ->get();

        foreach ($flowAutomations as $automation) {
            $flowData = $automation->flow_data;
            $nodes = $flowData['nodes'] ?? [];

            // Find trigger node
            $triggerNode = null;
            foreach ($nodes as $node) {
                if (($node['node_type'] ?? '') === $flowNodeType) {
                    $triggerNode = $node;
                    break;
                }
            }

            if (!$triggerNode) {
                continue;
            }

            // Check if keywords match
            $nodeData = $triggerNode['data'] ?? [];
            $keywords = $nodeData['keywords'] ?? '';

            // Handle keywords as array or string
            $keywordList = [];
            if (is_array($keywords)) {
                $keywordList = $keywords;
            } elseif (is_string($keywords) && !empty($keywords)) {
                // Parse keywords (comma or space separated)
                $keywordList = preg_split('/[\s,]+/', $keywords, -1, PREG_SPLIT_NO_EMPTY);
            }

            // If keywords is "__all__" or empty, match all messages
            if (empty($keywordList) || (count($keywordList) === 1 && ($keywordList[0] === '__all__' || $keywordList[0] === '+'))) {
                Log::info('Flow automation triggered (match all)', [
                    'automation_id' => $automation->id,
                    'text' => $text,
                ]);
                $this->executeFlowAutomation($automation, $account, $conversation, $text, $triggerNode);
                return;
            }

            foreach ($keywordList as $keyword) {
                $keyword = trim(mb_strtolower((string)$keyword));
                if (empty($keyword) || $keyword === '+') {
                    continue;
                }

                // Check if message contains keyword
                if (mb_stripos(mb_strtolower($text), $keyword) !== false) {
                    Log::info('Flow automation triggered (keyword match)', [
                        'automation_id' => $automation->id,
                        'keyword' => $keyword,
                        'text' => $text,
                    ]);
                    $this->executeFlowAutomation($automation, $account, $conversation, $text, $triggerNode, $keyword);
                    return;
                }
            }
        }
    }

    /**
     * Execute a flow-based automation
     */
    protected function executeFlowAutomation(
        InstagramAutomation $automation,
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $text,
        array $triggerNode,
        ?string $matchedKeyword = null
    ): void {
        // Log the trigger
        $log = InstagramAutomationLog::logTrigger(
            $automation,
            $triggerNode['node_type'],
            $matchedKeyword,
            $conversation,
            ['text' => $text]
        );

        $automation->incrementTriggerCount();

        try {
            // Start automation on conversation
            $conversation->startAutomation($automation);

            $flowData = $automation->flow_data;
            $nodes = $flowData['nodes'] ?? [];
            $edges = $flowData['edges'] ?? [];

            // Build adjacency list for navigation
            $adjacencyList = [];
            foreach ($edges as $edge) {
                $sourceId = $edge['source_node_id'];
                if (!isset($adjacencyList[$sourceId])) {
                    $adjacencyList[$sourceId] = [];
                }
                $adjacencyList[$sourceId][] = [
                    'target' => $edge['target_node_id'],
                    'handle' => $edge['source_handle'] ?? null,
                ];
            }

            // Execute nodes starting from trigger
            $this->executeFlowNode(
                $triggerNode['node_id'],
                $nodes,
                $adjacencyList,
                $automation,
                $account,
                $conversation,
                ['text' => $text, 'keyword' => $matchedKeyword, 'name' => $conversation->display_name]
            );

            // End automation
            $conversation->endAutomation();
            $automation->incrementConversionCount();
            $log->markCompleted();

        } catch (\Exception $e) {
            Log::error('Flow automation execution failed', [
                'automation_id' => $automation->id,
                'error' => $e->getMessage(),
            ]);
            $log->markFailed($e->getMessage());
        }
    }

    /**
     * Execute a single flow node and continue to connected nodes
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
        // Find the node
        $node = null;
        foreach ($nodes as $n) {
            if (($n['node_id'] ?? '') === $nodeId) {
                $node = $n;
                break;
            }
        }

        if (!$node) {
            return;
        }

        $nodeType = $node['node_type'] ?? '';
        $nodeData = $node['data'] ?? [];

        Log::info('Executing flow node', [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
        ]);

        // Execute action based on node type
        $conditionResult = null;
        switch ($nodeType) {
            case 'action_send_dm':
                $message = $nodeData['message'] ?? '';
                // Replace variables - support multiple variable formats
                $name = $variables['name'] ?? '';
                $username = $conversation->participant_username ?? '';
                $message = str_replace(
                    ['{name}', '{full_name}', '{username}', '{keyword}', '{text}'],
                    [$name, $name, $username, $variables['keyword'] ?? '', $variables['text'] ?? ''],
                    $message
                );
                $this->sendFlowDM($account, $conversation, $message, $automation);
                break;

            case 'action_delay':
                $delayType = $nodeData['delay_type'] ?? 'seconds';
                $delayValue = (int)($nodeData['delay_value'] ?? 0);
                $seconds = match($delayType) {
                    'minutes' => $delayValue * 60,
                    'hours' => $delayValue * 3600,
                    default => $delayValue,
                };
                // Only wait up to 60 seconds synchronously
                if ($seconds > 0 && $seconds <= 60) {
                    sleep($seconds);
                }
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

            case 'action_send_link':
                $url = $nodeData['url'] ?? '';
                $linkMessage = $nodeData['message'] ?? 'Link';
                $fullMessage = $linkMessage . "\n" . $url;
                $this->sendFlowDM($account, $conversation, $fullMessage, $automation);
                break;

            case 'condition_is_follower':
                // For now, assume they are followers (would need API call to check)
                $conditionResult = 'yes';
                break;

            case 'action_reply_comment':
                // Comment reply handled elsewhere
                break;

            // Trigger nodes don't execute anything
            case 'trigger_keyword_dm':
            case 'trigger_keyword_comment':
            case 'trigger_story_mention':
            case 'trigger_story_reply':
            case 'trigger_new_follower':
                break;
        }

        // Continue to connected nodes
        $nextNodes = $adjacencyList[$nodeId] ?? [];
        foreach ($nextNodes as $next) {
            // If this was a condition node, only follow matching handle
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
    }

    /**
     * Send DM via flow automation
     */
    protected function sendFlowDM(
        InstagramAccount $account,
        InstagramConversation $conversation,
        string $message,
        InstagramAutomation $automation
    ): void {
        $integration = $account->integration;
        if (!$integration || !$integration->access_token) {
            Log::warning('Cannot send DM - no access token', ['account_id' => $account->id]);
            return;
        }

        $response = $this->sendDM(
            $account->instagram_id,
            $conversation->participant_id,
            $message,
            $integration->access_token
        );

        if ($response['success']) {
            // Save outgoing message
            InstagramMessage::create([
                'conversation_id' => $conversation->id,
                'automation_id' => $automation->id,
                'instagram_message_id' => $response['message_id'] ?? null,
                'direction' => 'outgoing',
                'message_type' => 'text',
                'content' => $message,
                'is_automated' => true,
                'sent_at' => now(),
            ]);

            $conversation->update(['last_message_at' => now()]);
        } else {
            Log::error('Failed to send flow DM', [
                'account_id' => $account->id,
                'error' => $response['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Check and trigger comment automations
     */
    protected function checkAndTriggerCommentAutomations(
        InstagramAccount $account,
        array $commentData,
        string $commentText
    ): void {
        $automations = $account->automations()
            ->active()
            ->whereHas('triggers', fn($q) => $q->where('trigger_type', 'keyword_comment'))
            ->with('triggers')
            ->get();

        foreach ($automations as $automation) {
            foreach ($automation->triggers as $trigger) {
                // Check media_id match if specified
                if ($trigger->media_id && $trigger->media_id !== ($commentData['media_id'] ?? null)) {
                    continue;
                }

                if ($trigger->matches($commentText)) {
                    $keyword = $trigger->getMatchingKeyword($commentText);
                    $this->executeAutomation(
                        $automation,
                        $account,
                        $commentData['commenter_id'],
                        'keyword_comment',
                        array_merge($commentData, ['keyword' => $keyword])
                    );
                    break 2;
                }
            }
        }
    }

    /**
     * Execute an automation
     */
    protected function executeAutomation(
        InstagramAutomation $automation,
        InstagramAccount $account,
        string $targetUserId,
        string $triggerType,
        array $triggerData = [],
        ?InstagramConversation $conversation = null
    ): void {
        // Log the trigger
        $log = InstagramAutomationLog::logTrigger(
            $automation,
            $triggerType,
            $triggerData['keyword'] ?? null,
            $conversation,
            $triggerData
        );

        $automation->incrementTriggerCount();

        try {
            // Get or create conversation for DM actions
            if (!$conversation) {
                $conversation = InstagramConversation::firstOrCreate(
                    [
                        'account_id' => $account->id,
                        'participant_id' => $targetUserId,
                    ],
                    [
                        'status' => 'active',
                    ]
                );
            }

            // Start automation on conversation
            $conversation->startAutomation($automation);

            // Execute actions in order
            foreach ($automation->actions as $action) {
                $this->executeAction($action, $account, $conversation, $triggerData);
                $conversation->advanceStep();
            }

            // End automation
            $conversation->endAutomation();
            $automation->incrementConversionCount();
            $log->markCompleted();

        } catch (\Exception $e) {
            Log::error('Automation execution failed', [
                'automation_id' => $automation->id,
                'error' => $e->getMessage(),
            ]);
            $log->markFailed($e->getMessage());
        }
    }

    /**
     * Execute a single action
     */
    protected function executeAction(
        InstagramAutomationAction $action,
        InstagramAccount $account,
        InstagramConversation $conversation,
        array $variables = []
    ): void {
        $integration = $account->integration;

        switch ($action->action_type) {
            case 'send_dm':
                $message = $action->parseTemplate(array_merge($variables, [
                    'name' => $conversation->participant_name ?? $conversation->participant_username ?? '',
                    'username' => $conversation->participant_username ?? '',
                ]));
                $this->sendDM(
                    $account->instagram_id,
                    $conversation->participant_id,
                    $message,
                    $integration->access_token
                );
                // Save outgoing message
                InstagramMessage::create([
                    'conversation_id' => $conversation->id,
                    'automation_id' => $action->automation_id,
                    'direction' => 'outgoing',
                    'message_type' => 'text',
                    'content' => $message,
                    'is_automated' => true,
                    'sent_at' => now(),
                ]);
                break;

            case 'delay':
                if ($action->delay_seconds > 0) {
                    sleep(min($action->delay_seconds, 60)); // Max 60 seconds sync delay
                }
                break;

            case 'add_tag':
                $tag = $action->settings['tag'] ?? null;
                if ($tag) {
                    $conversation->addTag($tag);
                }
                break;

            case 'remove_tag':
                $tag = $action->settings['tag'] ?? null;
                if ($tag) {
                    $conversation->removeTag($tag);
                }
                break;

            case 'collect_data':
                // Mark conversation as waiting for input
                $conversation->update(['status' => 'waiting']);
                break;

            case 'ai_response':
                // TODO: Implement AI response using OpenAI/Claude
                break;

            case 'webhook':
                if ($action->webhook_url) {
                    Http::post($action->webhook_url, [
                        'conversation_id' => $conversation->id,
                        'participant' => [
                            'id' => $conversation->participant_id,
                            'username' => $conversation->participant_username,
                            'name' => $conversation->participant_name,
                        ],
                        'collected_data' => $conversation->collected_data,
                        'tags' => $conversation->tags,
                    ]);
                }
                break;

            case 'reply_comment':
                // Comment reply logic
                break;
        }
    }

    /**
     * Send DM via Instagram API
     */
    protected function sendDM(string $igUserId, string $recipientId, string $message, string $accessToken): array
    {
        try {
            $response = Http::post("{$this->graphApiUrl}/{$igUserId}/messages", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message],
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'API error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format automation for API response
     */
    protected function formatAutomation(InstagramAutomation $automation): array
    {
        return [
            'id' => $automation->id,
            'name' => $automation->name,
            'description' => $automation->description,
            'status' => $automation->status,
            'type' => $automation->type,
            'is_ai_enabled' => $automation->is_ai_enabled,
            'is_flow_based' => $automation->is_flow_based,
            'flow_data' => $automation->flow_data,
            'trigger_count' => $automation->trigger_count,
            'conversion_count' => $automation->conversion_count,
            'conversion_rate' => $automation->conversion_rate,
            'triggers' => $automation->triggers->map(fn($t) => [
                'id' => $t->id,
                'trigger_type' => $t->trigger_type,
                'trigger_type_label' => InstagramAutomationTrigger::getTriggerTypeLabel($t->trigger_type),
                'keywords' => $t->keywords,
                'media_id' => $t->media_id,
                'case_sensitive' => $t->case_sensitive,
                'exact_match' => $t->exact_match,
            ]),
            'actions' => $automation->actions->map(fn($a) => [
                'id' => $a->id,
                'order' => $a->order,
                'action_type' => $a->action_type,
                'action_type_label' => InstagramAutomationAction::getActionTypeLabel($a->action_type),
                'action_type_icon' => InstagramAutomationAction::getActionTypeIcon($a->action_type),
                'message_template' => $a->message_template,
                'buttons' => $a->buttons,
                'delay_seconds' => $a->delay_seconds,
                'delay_formatted' => $a->getDelayFormatted(),
                'settings' => $a->settings,
            ]),
            'created_at' => $automation->created_at->format('Y-m-d H:i'),
            'updated_at' => $automation->updated_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * Get quick replies
     */
    public function getQuickReplies(string $accountId): array
    {
        return InstagramQuickReply::where('account_id', $accountId)
            ->orderByDesc('usage_count')
            ->get()
            ->toArray();
    }

    /**
     * Create quick reply
     */
    public function createQuickReply(string $accountId, array $data): InstagramQuickReply
    {
        return InstagramQuickReply::create([
            'account_id' => $accountId,
            'title' => $data['title'],
            'content' => $data['content'],
            'shortcut' => $data['shortcut'] ?? null,
        ]);
    }

    /**
     * Get available trigger types
     */
    public function getTriggerTypes(): array
    {
        return [
            ['value' => 'keyword_dm', 'label' => 'DM kalit so\'z', 'description' => 'DM da kalit so\'z yozilganda'],
            ['value' => 'keyword_comment', 'label' => 'Comment kalit so\'z', 'description' => 'Commentda kalit so\'z yozilganda'],
            ['value' => 'story_mention', 'label' => 'Story mention', 'description' => 'Story\'da mention qilinsa'],
            ['value' => 'story_reply', 'label' => 'Story reply', 'description' => 'Story\'ga javob berilsa'],
            ['value' => 'new_follower', 'label' => 'Yangi follower', 'description' => 'Yangi odam follow qilsa'],
        ];
    }

    /**
     * Get available action types
     */
    public function getActionTypes(): array
    {
        return [
            ['value' => 'send_dm', 'label' => 'DM yuborish', 'icon' => 'chat'],
            ['value' => 'send_dm_with_buttons', 'label' => 'Tugmali DM', 'icon' => 'squares'],
            ['value' => 'send_media', 'label' => 'Media yuborish', 'icon' => 'photo'],
            ['value' => 'delay', 'label' => 'Kutish', 'icon' => 'clock'],
            ['value' => 'add_tag', 'label' => 'Tag qo\'shish', 'icon' => 'tag'],
            ['value' => 'ai_response', 'label' => 'AI javob', 'icon' => 'sparkles'],
            ['value' => 'collect_data', 'label' => 'Ma\'lumot yig\'ish', 'icon' => 'clipboard'],
            ['value' => 'webhook', 'label' => 'Webhook', 'icon' => 'globe'],
            ['value' => 'reply_comment', 'label' => 'Commentga javob', 'icon' => 'reply'],
        ];
    }
}
