<?php

namespace App\Services\Telegram;

use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\TelegramBot;
use App\Models\TelegramConversation;
use App\Models\TelegramDailyStat;
use App\Models\TelegramFunnel;
use App\Models\TelegramFunnelStep;
use App\Models\TelegramMessage;
use App\Models\TelegramTrigger;
use App\Models\TelegramUser;
use App\Models\TelegramUserState;
use App\Services\SubscriptionGate;
use Illuminate\Support\Facades\Log;

class FunnelEngineService
{
    protected TelegramApiService $api;

    protected TelegramBot $bot;

    protected TelegramUser $user;

    protected TelegramUserState $state;

    protected TelegramConversation $conversation;

    public function __construct(TelegramBot $bot, TelegramUser $user)
    {
        $this->bot = $bot;
        $this->user = $user;
        $this->api = new TelegramApiService($bot);
        $this->state = $user->state ?? $this->createState();
        $this->conversation = $this->getOrCreateConversation();
    }

    protected function createState(): TelegramUserState
    {
        return TelegramUserState::create([
            'telegram_user_id' => $this->user->id,
            'waiting_for' => 'none',
        ]);
    }

    protected function getOrCreateConversation(): TelegramConversation
    {
        $conversation = TelegramConversation::where('telegram_user_id', $this->user->id)
            ->where('telegram_bot_id', $this->bot->id)
            ->whereIn('status', ['active', 'handoff'])
            ->first();

        if (! $conversation) {
            $conversation = TelegramConversation::create([
                'business_id' => $this->bot->business_id,
                'telegram_user_id' => $this->user->id,
                'telegram_bot_id' => $this->bot->id,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $this->incrementDailyStat('conversations_started');
        }

        return $conversation;
    }

    /**
     * Process incoming message
     */
    public function processMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $messageText = $this->getMessageText($message);

        Log::info('FunnelEngine: Processing message', [
            'bot_id' => $this->bot->id,
            'user_id' => $this->user->id,
            'chat_id' => $chatId,
            'text' => $messageText,
            'is_command' => $this->isCommand($message),
        ]);

        // Send typing action if enabled
        if ($this->bot->hasTypingAction()) {
            $this->api->sendChatAction($chatId, 'typing');
            usleep($this->bot->getTypingDelay() * 1000);
        }

        // Log incoming message
        $this->logMessage($message, 'incoming');
        $this->incrementDailyStat('messages_in');

        // Check if in handoff mode
        if ($this->conversation->isHandoff()) {
            return; // Let operator handle
        }

        // Check if waiting for input FIRST (before handling contact separately)
        if ($this->state->isWaitingForInput()) {
            // If contact message and waiting for phone/contact input, process as input
            if (isset($message['contact'])) {
                $waitingFor = $this->state->waiting_for;
                if (in_array($waitingFor, ['phone', 'contact', 'text', 'any'])) {
                    $this->processInput($message);

                    return;
                }
            }
            $this->processInput($message);

            return;
        }

        // Handle contact message (phone number) - only if NOT in funnel input mode
        if (isset($message['contact'])) {
            $this->handleContactMessage($message['contact']);

            return;
        }

        // Check for command
        if ($this->isCommand($message)) {
            $this->processCommand($message);

            return;
        }

        // Check for triggers from TelegramTrigger table
        $trigger = $this->findTrigger($this->getMessageText($message));
        if ($trigger) {
            $this->processTrigger($trigger, $message);

            return;
        }

        // Check for trigger_keyword steps in funnels
        $funnelTrigger = $this->findFunnelByKeyword($messageText);
        if ($funnelTrigger) {
            $this->startFunnelFromTriggerStep($funnelTrigger['funnel'], $funnelTrigger['step']);

            return;
        }

        // Send fallback message
        $this->sendFallbackMessage($chatId);
    }

    /**
     * Process callback query
     */
    public function processCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $callbackData = $callbackQuery['data'];
        $callbackQueryId = $callbackQuery['id'];

        // Store callback query ID for later use (e.g., showing alerts)
        $context = $this->state->context ?? [];
        $context['last_callback_id'] = $callbackQueryId;
        $this->state->update(['context' => $context]);

        // Log callback as message
        $this->logCallbackMessage($callbackQuery);

        // Check if in handoff mode
        if ($this->conversation->isHandoff()) {
            $this->api->answerCallbackQuery($callbackQueryId);

            return;
        }

        // Parse callback data
        $parsed = $this->parseCallbackData($callbackData);

        // recheck_subscribe handles its own callback answer (for showing alerts)
        $answerCallback = true;

        switch ($parsed['action'] ?? null) {
            case 'step':
                $this->goToStep($parsed['step_id']);
                break;

            case 'funnel':
                $this->startFunnel($parsed['funnel_id']);
                break;

            case 'input':
                $this->processCallbackInput($parsed['value'], $parsed['field'] ?? null);
                break;

            case 'handoff':
                $this->requestHandoff($parsed['reason'] ?? null);
                break;

            case 'restart':
                $this->restartFunnel();
                break;

            case 'quiz_answer':
                $this->processQuizAnswer($parsed['step_id'], $parsed['option_index']);
                break;

            case 'recheck_subscribe':
                $this->recheckSubscription($parsed['step_id'], $callbackQueryId);
                $answerCallback = false; // handled internally
                break;

            default:
                // Check triggers for callback data
                $trigger = $this->findCallbackTrigger($callbackData);
                if ($trigger) {
                    $this->processTrigger($trigger, ['callback_query' => $callbackQuery]);
                }
        }

        // Answer callback query to remove loading state
        if ($answerCallback) {
            $this->api->answerCallbackQuery($callbackQueryId);
        }
    }

    /**
     * Process command
     */
    protected function processCommand(array $message): void
    {
        $text = $this->getMessageText($message);
        $command = $this->extractCommand($text);
        $args = $this->extractCommandArgs($text);

        // Find command trigger
        $trigger = TelegramTrigger::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->where('type', 'command')
            ->where('value', $command)
            ->first();

        if ($trigger) {
            $this->processTrigger($trigger, $message);

            return;
        }

        // Default command handlers
        switch ($command) {
            case '/start':
                $this->handleStartCommand($args);
                break;

            case '/help':
                $this->sendHelpMessage($message['chat']['id']);
                break;

            case '/cancel':
                $this->cancelCurrentFunnel($message['chat']['id']);
                break;

            default:
                $this->sendFallbackMessage($message['chat']['id']);
        }
    }

    /**
     * Handle /start command
     */
    protected function handleStartCommand(?string $payload): void
    {
        $chatId = $this->user->telegram_id;

        Log::info('FunnelEngine: Handling /start command', [
            'bot_id' => $this->bot->id,
            'bot_username' => $this->bot->bot_username,
            'user_id' => $this->user->id,
            'chat_id' => $chatId,
            'payload' => $payload,
            'default_funnel_id' => $this->bot->default_funnel_id,
        ]);

        // Check for deep link payload
        if ($payload) {
            $trigger = TelegramTrigger::where('telegram_bot_id', $this->bot->id)
                ->where('is_active', true)
                ->where('type', 'start_payload')
                ->where('value', $payload)
                ->first();

            if ($trigger) {
                $this->processTrigger($trigger, ['payload' => $payload]);

                return;
            }
        }

        // Check for default start trigger
        $trigger = TelegramTrigger::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->where('type', 'command')
            ->where('value', '/start')
            ->first();

        if ($trigger) {
            $this->processTrigger($trigger, []);

            return;
        }

        // Check for default funnel
        if ($this->bot->default_funnel_id) {
            $defaultFunnel = TelegramFunnel::find($this->bot->default_funnel_id);
            if ($defaultFunnel && $defaultFunnel->is_active) {
                Log::info('Starting default funnel', [
                    'bot_id' => $this->bot->id,
                    'funnel_id' => $defaultFunnel->id,
                    'user_id' => $this->user->id,
                ]);
                $this->startFunnel($defaultFunnel->id);

                return;
            }
        }

        // If no default funnel set, try to start first active funnel
        $firstActiveFunnel = TelegramFunnel::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->first();

        if ($firstActiveFunnel) {
            Log::info('Starting first active funnel', [
                'bot_id' => $this->bot->id,
                'funnel_id' => $firstActiveFunnel->id,
                'user_id' => $this->user->id,
            ]);
            $this->startFunnel($firstActiveFunnel->id);

            return;
        }

        // Send welcome message with contact request if enabled (fallback)
        $welcomeMessage = $this->bot->getWelcomeMessage();
        $requestContact = $this->bot->getSettingValue('request_contact_on_start', true);

        if ($requestContact && ! $this->user->phone) {
            // Send welcome with contact request keyboard
            $keyboard = TelegramApiService::buildReplyKeyboard(
                [[TelegramApiService::buildContactButton('📱 Telefon raqamni yuborish')]],
                true,
                true,
                'Telefon raqamingizni yuboring'
            );
            $this->api->sendMessage($chatId, $welcomeMessage."\n\n📲 Davom etish uchun telefon raqamingizni yuboring:", $keyboard);
        } else {
            $this->api->sendMessage($chatId, $welcomeMessage);
        }
    }

    /**
     * Process trigger
     */
    protected function processTrigger(TelegramTrigger $trigger, array $context): void
    {
        $this->incrementTriggerStat($trigger->id);

        if ($trigger->funnel_id) {
            $this->startFunnel($trigger->funnel_id, $trigger->step_id);
        } elseif ($trigger->step_id) {
            $this->goToStep($trigger->step_id);
        }
    }

    /**
     * Start funnel
     */
    public function startFunnel(string $funnelId, ?string $stepId = null): void
    {
        Log::info('FunnelEngine: startFunnel called', [
            'funnel_id' => $funnelId,
            'step_id' => $stepId,
            'user_id' => $this->user->id,
        ]);

        $funnel = TelegramFunnel::find($funnelId);

        if (! $funnel || ! $funnel->is_active) {
            Log::warning('FunnelEngine: Funnel not found or inactive', [
                'funnel_id' => $funnelId,
                'funnel_exists' => $funnel !== null,
                'is_active' => $funnel?->is_active,
                'user_id' => $this->user->id,
            ]);

            return;
        }

        // Get first step or specified step
        $step = $stepId
            ? TelegramFunnelStep::find($stepId)
            : $funnel->firstStep();

        Log::info('FunnelEngine: Got first step', [
            'funnel_id' => $funnelId,
            'funnel_name' => $funnel->name,
            'first_step_id' => $funnel->first_step_id,
            'step_found' => $step !== null,
            'step_id' => $step?->id,
            'step_name' => $step?->name,
            'step_type' => $step?->step_type,
        ]);

        if (! $step) {
            Log::warning('FunnelEngine: Funnel has no steps', [
                'funnel_id' => $funnelId,
                'funnel_name' => $funnel->name,
            ]);

            return;
        }

        // Reset state and start funnel
        $this->state->update([
            'current_funnel_id' => $funnel->id,
            'current_step_id' => $step->id,
            'collected_data' => [],
            'waiting_for' => 'none',
            'context' => [],
        ]);

        // Update conversation
        $this->conversation->update(['started_funnel_id' => $funnel->id]);

        // Increment funnel start stat
        $this->incrementFunnelStat($funnel->id, 'started');

        // Execute step
        $this->executeStep($step);
    }

    /**
     * Go to step
     */
    public function goToStep(string $stepId): void
    {
        $step = TelegramFunnelStep::find($stepId);

        if (! $step) {
            Log::warning('Step not found', ['step_id' => $stepId]);

            return;
        }

        $this->state->moveTo($step);
        $this->executeStep($step);
    }

    /**
     * Execute step
     */
    protected function executeStep(TelegramFunnelStep $step): void
    {
        $chatId = $this->user->telegram_id;

        // Handle special step types
        switch ($step->step_type) {
            case 'condition':
                $this->executeConditionStep($step);

                return;

            case 'subscribe_check':
                $this->executeSubscribeCheckStep($step);

                return;

            case 'quiz':
                $this->executeQuizStep($step);

                return;

            case 'ab_test':
                $this->executeABTestStep($step);

                return;

            case 'tag':
                $this->executeTagStep($step);

                return;

            case 'trigger_keyword':
                // Trigger keyword step - just proceed to next step
                // This step is primarily used as an entry point
                $this->executeTriggerKeywordStep($step);

                return;
        }

        // Process content variables
        $content = $this->processVariables($step->getContent());
        $keyboard = $step->hasKeyboard() ? $step->keyboard : null;

        // Send typing action
        if ($this->bot->hasTypingAction()) {
            $this->api->sendChatAction($chatId, 'typing');
            usleep($this->bot->getTypingDelay() * 1000);
        }

        // Send step content based on type
        $result = $this->sendStepContent($chatId, $step, $content, $keyboard);

        if ($result['success']) {
            // Store message info for later editing
            $this->state->update([
                'last_message_id' => $result['result']['message_id'] ?? null,
                'last_message_chat_id' => $chatId,
            ]);

            // Log outgoing message
            $this->logOutgoingMessage($step, $content, $keyboard);
            $this->incrementDailyStat('messages_out');
        }

        // Set waiting state if step expects input
        if ($step->input_type !== 'none') {
            $this->state->update(['waiting_for' => $step->input_type]);
        } else {
            // Auto-advance if no input required and has next step
            if ($step->next_step_id) {
                // Add small delay before next step
                usleep(500000); // 500ms
                $this->goToStep($step->next_step_id);
            } elseif ($step->action_type !== 'none') {
                $this->executeAction($step);
            }
        }
    }

    /**
     * Execute condition step - evaluate condition and branch accordingly
     */
    protected function executeConditionStep(TelegramFunnelStep $step): void
    {
        $condition = $step->condition ?? [];
        $result = $this->evaluateCondition($condition);

        Log::info('Condition evaluated', [
            'step_id' => $step->id,
            'condition' => $condition,
            'result' => $result,
            'user_id' => $this->user->id,
        ]);

        // Go to appropriate branch
        if ($result) {
            if ($step->condition_true_step_id) {
                usleep(200000); // 200ms delay
                $this->goToStep($step->condition_true_step_id);
            }
        } else {
            if ($step->condition_false_step_id) {
                usleep(200000); // 200ms delay
                $this->goToStep($step->condition_false_step_id);
            }
        }
    }

    /**
     * Evaluate a condition against user data
     */
    protected function evaluateCondition(array $condition): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '';
        $expectedValue = $condition['value'] ?? '';

        // Handle custom field
        if ($field === 'custom_field') {
            $field = $condition['custom_field'] ?? '';
        }

        // Get actual value from user or collected data
        $actualValue = $this->getFieldValue($field);

        // Evaluate based on operator
        return match ($operator) {
            'equals' => $actualValue == $expectedValue,
            'not_equals' => $actualValue != $expectedValue,
            'contains' => is_string($actualValue) && str_contains(strtolower($actualValue), strtolower($expectedValue)),
            'not_contains' => is_string($actualValue) && ! str_contains(strtolower($actualValue), strtolower($expectedValue)),
            'starts_with' => is_string($actualValue) && str_starts_with(strtolower($actualValue), strtolower($expectedValue)),
            'ends_with' => is_string($actualValue) && str_ends_with(strtolower($actualValue), strtolower($expectedValue)),
            'is_set' => ! empty($actualValue),
            'is_empty' => empty($actualValue),
            'greater_than' => is_numeric($actualValue) && $actualValue > $expectedValue,
            'less_than' => is_numeric($actualValue) && $actualValue < $expectedValue,
            'greater_or_equal' => is_numeric($actualValue) && $actualValue >= $expectedValue,
            'less_or_equal' => is_numeric($actualValue) && $actualValue <= $expectedValue,
            'is_true' => filter_var($actualValue, FILTER_VALIDATE_BOOLEAN),
            'is_false' => ! filter_var($actualValue, FILTER_VALIDATE_BOOLEAN),
            default => false,
        };
    }

    /**
     * Get field value from user data or collected data
     */
    protected function getFieldValue(string $field): mixed
    {
        // First check collected data
        $collectedData = $this->state->collected_data ?? [];
        if (isset($collectedData[$field])) {
            return $collectedData[$field];
        }

        // Check for marketing-specific fields
        if ($field === 'has_tag') {
            return ! empty($this->user->tags);
        }
        if ($field === 'quiz_answer') {
            return $collectedData['quiz_answer'] ?? null;
        }
        if ($field === 'interaction_count') {
            return TelegramMessage::where('telegram_user_id', $this->user->id)->count();
        }

        // Then check user model
        return match ($field) {
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'username' => $this->user->username,
            'phone' => $this->user->phone,
            'email' => $this->user->email ?? null,
            'language_code' => $this->user->language_code,
            'is_premium' => $this->user->is_premium ?? false,
            'user_id' => $this->user->telegram_id,
            default => $this->user->custom_data[$field] ?? null,
        };
    }

    /**
     * Execute subscribe check step - check if user is subscribed to a channel
     */
    protected function executeSubscribeCheckStep(TelegramFunnelStep $step): void
    {
        $config = $step->subscribe_check ?? [];
        $channelUsername = $config['channel_username'] ?? '';

        if (empty($channelUsername)) {
            // No channel configured, skip to next step
            if ($step->subscribe_true_step_id) {
                $this->goToStep($step->subscribe_true_step_id);
            }

            return;
        }

        // Check subscription using getChatMember API
        $channelId = '@'.ltrim($channelUsername, '@');
        $result = $this->api->getChatMember($channelId, $this->user->telegram_id);

        $isSubscribed = false;
        if ($result['success']) {
            $status = $result['result']['status'] ?? 'left';
            $isSubscribed = in_array($status, ['member', 'administrator', 'creator']);
        }

        Log::info('Subscribe check', [
            'user_id' => $this->user->id,
            'channel' => $channelUsername,
            'is_subscribed' => $isSubscribed,
        ]);

        if ($isSubscribed) {
            // User is subscribed
            if ($step->subscribe_true_step_id) {
                usleep(200000);
                $this->goToStep($step->subscribe_true_step_id);
            }
        } else {
            // User is not subscribed - send message with subscribe button
            $chatId = $this->user->telegram_id;
            $message = $config['not_subscribed_message'] ?? "Davom etish uchun kanalga obuna bo'ling!";
            $buttonText = $config['subscribe_button_text'] ?? "Obuna bo'lish";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => $buttonText, 'url' => "https://t.me/{$channelUsername}"],
                    ],
                    [
                        ['text' => 'Tekshirish ✓', 'callback_data' => "recheck_subscribe:{$step->id}"],
                    ],
                ],
            ];

            $this->api->sendMessage($chatId, $message, [
                'reply_markup' => json_encode($keyboard),
            ]);

            // Save step ID to recheck later
            $this->state->update([
                'waiting_for' => 'subscribe_check',
                'current_step_id' => $step->id,
            ]);
        }
    }

    /**
     * Execute quiz step - send question with options
     */
    protected function executeQuizStep(TelegramFunnelStep $step): void
    {
        $chatId = $this->user->telegram_id;
        $quiz = $step->quiz ?? [];
        $question = $quiz['question'] ?? 'Savol';
        $options = $quiz['options'] ?? [];

        if (empty($options)) {
            // No options, skip
            return;
        }

        // Build inline keyboard with options
        $buttons = [];
        foreach ($options as $i => $option) {
            $buttons[] = [
                ['text' => $option['text'] ?? 'Variant '.($i + 1), 'callback_data' => "quiz_answer:{$step->id}:{$i}"],
            ];
        }

        $keyboard = ['inline_keyboard' => $buttons];

        // Send typing action
        if ($this->bot->hasTypingAction()) {
            $this->api->sendChatAction($chatId, 'typing');
            usleep($this->bot->getTypingDelay() * 1000);
        }

        $result = $this->api->sendMessage($chatId, $question, [
            'reply_markup' => json_encode($keyboard),
        ]);

        if ($result['success']) {
            $this->state->update([
                'last_message_id' => $result['result']['message_id'] ?? null,
                'last_message_chat_id' => $chatId,
                'waiting_for' => 'quiz_answer',
                'current_step_id' => $step->id,
            ]);
        }
    }

    /**
     * Execute A/B test step - randomly select a variant
     */
    protected function executeABTestStep(TelegramFunnelStep $step): void
    {
        $abTest = $step->ab_test ?? [];
        $variants = $abTest['variants'] ?? [];

        if (empty($variants)) {
            return;
        }

        // Calculate random selection based on percentages
        $random = mt_rand(1, 100);
        $cumulative = 0;
        $selectedVariant = null;

        foreach ($variants as $variant) {
            $cumulative += $variant['percentage'] ?? 0;
            if ($random <= $cumulative) {
                $selectedVariant = $variant;
                break;
            }
        }

        // Fallback to first variant
        if (! $selectedVariant) {
            $selectedVariant = $variants[0];
        }

        Log::info('A/B test variant selected', [
            'user_id' => $this->user->id,
            'step_id' => $step->id,
            'variant' => $selectedVariant['name'] ?? 'Unknown',
            'random' => $random,
        ]);

        // Save variant selection to user data for analytics
        $collectedData = $this->state->collected_data ?? [];
        $collectedData["ab_test_{$step->id}"] = $selectedVariant['name'] ?? 'A';
        $this->state->update(['collected_data' => $collectedData]);

        // Go to selected variant's next step
        if (! empty($selectedVariant['next_step_id'])) {
            usleep(200000);
            $this->goToStep($selectedVariant['next_step_id']);
        }
    }

    /**
     * Execute tag step - add or remove tags from user
     */
    protected function executeTagStep(TelegramFunnelStep $step): void
    {
        $tagConfig = $step->tag ?? [];
        $action = $tagConfig['action'] ?? 'add';
        $tags = $tagConfig['tags'] ?? [];

        if (empty($tags)) {
            // No tags configured, just continue to next step
            if ($step->next_step_id) {
                usleep(200000);
                $this->goToStep($step->next_step_id);
            }

            return;
        }

        // Get current user tags
        $currentTags = $this->user->tags ?? [];
        if (! is_array($currentTags)) {
            $currentTags = [];
        }

        if ($action === 'add') {
            // Add new tags
            $currentTags = array_unique(array_merge($currentTags, $tags));
        } else {
            // Remove tags
            $currentTags = array_diff($currentTags, $tags);
        }

        // Update user tags
        $this->user->update(['tags' => array_values($currentTags)]);

        Log::info('User tags updated', [
            'user_id' => $this->user->id,
            'action' => $action,
            'tags' => $tags,
            'current_tags' => $currentTags,
        ]);

        // Continue to next step
        if ($step->next_step_id) {
            usleep(200000);
            $this->goToStep($step->next_step_id);
        }
    }

    /**
     * Execute trigger keyword step - just proceed to next step
     * This step is primarily used as an entry point for keyword-triggered funnels
     */
    protected function executeTriggerKeywordStep(TelegramFunnelStep $step): void
    {
        Log::info('Executing trigger keyword step', [
            'step_id' => $step->id,
            'user_id' => $this->user->id,
            'trigger' => $step->trigger,
        ]);

        // Continue to next step if available
        if ($step->next_step_id) {
            usleep(200000);
            $this->goToStep($step->next_step_id);
        }
    }

    /**
     * Send step content
     */
    protected function sendStepContent(
        int $chatId,
        TelegramFunnelStep $step,
        array $content,
        ?array $keyboard
    ): array {
        $type = $content['type'] ?? 'text';
        $text = $content['text'] ?? '';
        $caption = $content['caption'] ?? $text;
        $fileId = $content['file_id'] ?? $content['url'] ?? null;

        // Build keyboard if needed
        $replyMarkup = null;
        if ($keyboard) {
            $replyMarkup = $this->buildKeyboard($keyboard, $step);
        }

        switch ($type) {
            case 'photo':
                return $this->api->sendPhoto($chatId, $fileId, $caption, $replyMarkup);

            case 'video':
                return $this->api->sendVideo($chatId, $fileId, $caption, $replyMarkup);

            case 'voice':
                return $this->api->sendVoice($chatId, $fileId, $caption, $replyMarkup);

            case 'video_note':
                return $this->api->sendVideoNote($chatId, $fileId, $content['duration'] ?? null, $replyMarkup);

            case 'document':
                return $this->api->sendDocument($chatId, $fileId, $caption, $replyMarkup);

            case 'location':
                return $this->api->sendLocation(
                    $chatId,
                    $content['latitude'],
                    $content['longitude'],
                    $replyMarkup
                );

            default:
                return $this->api->sendMessage($chatId, $text, $replyMarkup);
        }
    }

    /**
     * Build keyboard from step configuration
     */
    protected function buildKeyboard(array $keyboard, TelegramFunnelStep $step): array
    {
        $type = $keyboard['type'] ?? 'inline';
        $buttons = $keyboard['buttons'] ?? [];

        if ($type === 'inline') {
            $rows = [];
            foreach ($buttons as $row) {
                $buttonRow = [];
                foreach ($row as $button) {
                    $btn = ['text' => $button['text'] ?? 'Button'];
                    $actionType = $button['action_type'] ?? 'next_step';

                    switch ($actionType) {
                        case 'url':
                            if (! empty($button['url'])) {
                                $btn['url'] = $button['url'];
                            }
                            break;

                        case 'callback':
                            $btn['callback_data'] = $button['callback_data'] ?? $button['text'];
                            break;

                        case 'go_to_step':
                            if (! empty($button['next_step_id'])) {
                                $btn['callback_data'] = "step:{$button['next_step_id']}";
                            } else {
                                $btn['callback_data'] = 'step:next';
                            }
                            break;

                        case 'next_step':
                        default:
                            // Default: go to the node's next_step_id or finish
                            if ($step->next_step_id) {
                                $btn['callback_data'] = "step:{$step->next_step_id}";
                            } else {
                                $btn['callback_data'] = 'finish';
                            }
                            break;
                    }

                    // Fallback: if no action was set, use callback_data from button if available
                    if (! isset($btn['callback_data']) && ! isset($btn['url'])) {
                        if (! empty($button['callback_data'])) {
                            $btn['callback_data'] = $button['callback_data'];
                        } elseif (! empty($button['next_step_id'])) {
                            $btn['callback_data'] = "step:{$button['next_step_id']}";
                        } elseif (! empty($button['value'])) {
                            $field = $step->input_field ?? 'choice';
                            $btn['callback_data'] = "input:{$field}:{$button['value']}";
                        } else {
                            $btn['callback_data'] = $button['text'] ?? 'action';
                        }
                    }

                    $buttonRow[] = $btn;
                }
                if (! empty($buttonRow)) {
                    $rows[] = $buttonRow;
                }
            }

            return TelegramApiService::buildInlineKeyboard($rows);
        }

        // Reply keyboard
        $rows = [];
        foreach ($buttons as $row) {
            $buttonRow = [];
            foreach ($row as $button) {
                $actionType = $button['action_type'] ?? null;

                if ($actionType === 'request_contact' || ! empty($button['request_contact'])) {
                    $buttonRow[] = TelegramApiService::buildContactButton($button['text'] ?? '📱 Kontakt yuborish');
                } elseif ($actionType === 'request_location' || ! empty($button['request_location'])) {
                    $buttonRow[] = TelegramApiService::buildLocationButton($button['text'] ?? '📍 Lokatsiya yuborish');
                } else {
                    $buttonRow[] = $button['text'] ?? 'Button';
                }
            }
            if (! empty($buttonRow)) {
                $rows[] = $buttonRow;
            }
        }

        return TelegramApiService::buildReplyKeyboard(
            $rows,
            true,
            $keyboard['one_time'] ?? false,
            $keyboard['placeholder'] ?? null
        );
    }

    /**
     * Process user input
     */
    protected function processInput(array $message): void
    {
        $step = TelegramFunnelStep::find($this->state->current_step_id);

        if (! $step) {
            $this->state->update(['waiting_for' => 'none']);

            return;
        }

        $inputType = $step->input_type;
        $inputField = $step->input_field ?? $step->name;

        // Extract input based on type
        $value = $this->extractInput($message, $inputType);

        if ($value === null) {
            // Invalid input, send error message
            $this->sendValidationError($step, $message['chat']['id']);

            return;
        }

        // Validate input
        if (! $this->validateInput($value, $step)) {
            $this->sendValidationError($step, $message['chat']['id']);

            return;
        }

        // Store collected data
        $this->state->setCollectedValue($inputField, $value);

        // If phone input and contact was received, also update user's phone
        if ($inputType === 'phone' && isset($message['contact'])) {
            $this->user->update([
                'phone' => $message['contact']['phone_number'],
                'first_name' => $message['contact']['first_name'] ?? $this->user->first_name,
                'last_name' => $message['contact']['last_name'] ?? $this->user->last_name,
            ]);

            Log::info('User phone updated from funnel input', [
                'user_id' => $this->user->id,
                'phone' => $message['contact']['phone_number'],
            ]);
        }

        // Clear waiting state
        $this->state->update(['waiting_for' => 'none']);

        // Move to next step or execute action
        if ($step->next_step_id) {
            $this->goToStep($step->next_step_id);
        } elseif ($step->action_type !== 'none') {
            $this->executeAction($step);
        }
    }

    /**
     * Process callback input
     */
    protected function processCallbackInput(string $value, ?string $field): void
    {
        $step = TelegramFunnelStep::find($this->state->current_step_id);

        if (! $step) {
            return;
        }

        $inputField = $field ?? $step->input_field ?? $step->name;

        // Store value
        $this->state->setCollectedValue($inputField, $value);
        $this->state->update(['waiting_for' => 'none']);

        // Move to next step or execute action
        if ($step->next_step_id) {
            $this->goToStep($step->next_step_id);
        } elseif ($step->action_type !== 'none') {
            $this->executeAction($step);
        }
    }

    /**
     * Extract input from message
     */
    protected function extractInput(array $message, string $type): mixed
    {
        switch ($type) {
            case 'text':
            case 'email':
            case 'number':
                return $message['text'] ?? null;

            case 'phone':
                // Phone can be text or contact
                if (isset($message['contact'])) {
                    return $message['contact']['phone_number'];
                }

                return $message['text'] ?? null;

            case 'contact':
                if (isset($message['contact'])) {
                    return [
                        'phone' => $message['contact']['phone_number'],
                        'first_name' => $message['contact']['first_name'] ?? null,
                        'last_name' => $message['contact']['last_name'] ?? null,
                    ];
                }

                return null;

            case 'location':
                if (isset($message['location'])) {
                    return [
                        'latitude' => $message['location']['latitude'],
                        'longitude' => $message['location']['longitude'],
                    ];
                }

                return null;

            case 'photo':
                if (isset($message['photo'])) {
                    $photo = end($message['photo']);

                    return $photo['file_id'];
                }

                return null;

            case 'document':
                return $message['document']['file_id'] ?? null;

            default:
                return $message['text'] ?? null;
        }
    }

    /**
     * Validate input
     */
    protected function validateInput(mixed $value, TelegramFunnelStep $step): bool
    {
        $validation = $step->validation ?? [];
        $inputType = $step->input_type;

        // Type-specific validation
        switch ($inputType) {
            case 'email':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
                break;

            case 'phone':
                if (! preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $value)) {
                    return false;
                }
                break;

            case 'number':
                if (! is_numeric($value)) {
                    return false;
                }
                $value = (float) $value;
                if (isset($validation['min']) && $value < $validation['min']) {
                    return false;
                }
                if (isset($validation['max']) && $value > $validation['max']) {
                    return false;
                }
                break;

            case 'text':
                if (isset($validation['min_length']) && strlen($value) < $validation['min_length']) {
                    return false;
                }
                if (isset($validation['max_length']) && strlen($value) > $validation['max_length']) {
                    return false;
                }
                if (isset($validation['pattern']) && ! preg_match($validation['pattern'], $value)) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Send validation error
     */
    protected function sendValidationError(TelegramFunnelStep $step, int $chatId): void
    {
        $errorMessage = $step->getValidationError() ?? "Noto'g'ri format. Iltimos qaytadan urinib ko'ring.";
        $this->api->sendMessage($chatId, $errorMessage);
    }

    /**
     * Execute step action
     */
    protected function executeAction(TelegramFunnelStep $step): void
    {
        switch ($step->action_type) {
            case 'create_lead':
                $this->createLead($step);
                break;

            case 'update_user':
                $this->updateUserFromCollected($step);
                break;

            case 'handoff':
                $this->requestHandoff($step->action_config['reason'] ?? null);
                break;

            case 'send_notification':
                $this->sendNotification($step);
                break;

            case 'webhook':
                $this->sendWebhook($step);
                break;
        }

        // Complete funnel
        $this->completeFunnel();
    }

    /**
     * Create lead from collected data
     * Tarif limitini tekshiradi - monthly_leads
     */
    protected function createLead(TelegramFunnelStep $step): void
    {
        $config = $step->action_config ?? [];
        $collectedData = $this->state->collected_data ?? [];

        // TARIF LIMITI: Oylik lid limitini tekshirish
        $business = Business::find($this->bot->business_id);
        if ($business) {
            $gate = app(SubscriptionGate::class);
            if (!$gate->canAdd($business, 'monthly_leads')) {
                Log::warning('Lead creation blocked - monthly limit reached', [
                    'business_id' => $this->bot->business_id,
                    'bot_id' => $this->bot->id,
                    'telegram_user_id' => $this->user->telegram_id,
                ]);
                // Lead yaratmasdan davom etish - foydalanuvchiga xabar berilmaydi
                // Chunki bu avtomatik jarayon
                return;
            }
        }

        // Get source_id from config or create default Telegram source
        $sourceId = $config['source_id'] ?? null;
        if (! $sourceId) {
            $source = $this->getOrCreateTelegramSource();
            $sourceId = $source?->id;
        }

        $leadData = [
            'business_id' => $this->bot->business_id,
            'source_id' => $sourceId,
            'name' => $collectedData[$config['name_field'] ?? 'name'] ?? $this->user->getFullName(),
            'phone' => $collectedData[$config['phone_field'] ?? 'phone'] ?? $this->user->phone,
            'email' => $collectedData[$config['email_field'] ?? 'email'] ?? null,
            'status' => 'new',
            'notes' => json_encode([
                'telegram_user_id' => $this->user->telegram_id,
                'funnel' => $this->state->currentFunnel?->name,
                'collected_data' => $collectedData,
            ]),
            'data' => [
                'source' => 'telegram_funnel',
                'bot_id' => $this->bot->id,
                'funnel_id' => $this->state->current_funnel_id,
                'telegram_user_id' => $this->user->telegram_id,
            ],
        ];

        $lead = Lead::create($leadData);

        // Link lead to conversation
        $this->conversation->update(['lead_id' => $lead->id]);

        // Update user
        $this->user->update(['lead_id' => $lead->id]);

        // Increment stat
        $this->incrementDailyStat('leads_captured');
        $this->incrementFunnelStat($this->state->current_funnel_id, 'leads');
    }

    /**
     * Get or create Telegram lead source
     */
    protected function getOrCreateTelegramSource(): ?LeadSource
    {
        $businessId = $this->bot->business_id;

        // First try to find by code
        $source = LeadSource::where('business_id', $businessId)
            ->where('code', 'telegram_bot')
            ->first();

        if ($source) {
            return $source;
        }

        // Fallback: search by name
        $source = LeadSource::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('name', 'like', '%telegram%')
                    ->orWhere('name', 'like', '%Telegram%');
            })
            ->first();

        if ($source) {
            return $source;
        }

        // Create new source
        try {
            $source = LeadSource::create([
                'business_id' => $businessId,
                'code' => 'telegram_bot_'.substr($businessId, 0, 8),
                'name' => 'Telegram Bot',
                'category' => 'digital',
                'icon' => 'telegram',
                'color' => '#0088cc',
                'is_paid' => false,
                'is_trackable' => true,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning('LeadSource creation failed for Telegram', [
                'error' => $e->getMessage(),
            ]);

            try {
                $source = LeadSource::create([
                    'business_id' => $businessId,
                    'code' => 'telegram_bot_'.time(),
                    'name' => 'Telegram Bot',
                    'category' => 'digital',
                    'icon' => 'telegram',
                    'color' => '#0088cc',
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
     * Update user from collected data
     */
    protected function updateUserFromCollected(TelegramFunnelStep $step): void
    {
        $config = $step->action_config ?? [];
        $collectedData = $this->state->collected_data ?? [];

        $updateData = [];

        foreach ($config['field_mapping'] ?? [] as $sourceField => $userField) {
            if (isset($collectedData[$sourceField])) {
                if ($userField === 'custom_data') {
                    $customData = $this->user->custom_data ?? [];
                    $customData[$sourceField] = $collectedData[$sourceField];
                    $updateData['custom_data'] = $customData;
                } else {
                    $updateData[$userField] = $collectedData[$sourceField];
                }
            }
        }

        if (! empty($updateData)) {
            $this->user->update($updateData);
        }
    }

    /**
     * Request handoff to operator
     */
    protected function requestHandoff(?string $reason): void
    {
        $this->conversation->requestHandoff($reason);
        $this->incrementDailyStat('handoffs');

        // Send confirmation to user
        $message = $this->bot->getSettingValue('handoff_message',
            "Sizni operator bilan bog'laymiz. Iltimos kuting..."
        );
        $this->api->sendMessage($this->user->telegram_id, $message);
    }

    /**
     * Complete funnel
     */
    protected function completeFunnel(): void
    {
        $funnelId = $this->state->current_funnel_id;

        // Increment completed stat
        if ($funnelId) {
            $this->incrementFunnelStat($funnelId, 'completed');
        }

        // Reset state
        $this->state->reset();

        // Send completion message if configured
        $funnel = TelegramFunnel::find($funnelId);
        if ($funnel && $funnel->completion_message) {
            $message = $this->processVariables(['text' => $funnel->completion_message]);
            $this->api->sendMessage($this->user->telegram_id, $message['text']);
        }
    }

    /**
     * Cancel current funnel
     */
    protected function cancelCurrentFunnel(int $chatId): void
    {
        if ($this->state->isInFunnel()) {
            $this->state->reset();
            $this->api->sendMessage($chatId, 'Jarayon bekor qilindi.');
        } else {
            $this->api->sendMessage($chatId, "Hozirda hech qanday jarayon yo'q.");
        }
    }

    /**
     * Restart current funnel
     */
    protected function restartFunnel(): void
    {
        if ($this->state->current_funnel_id) {
            $this->startFunnel($this->state->current_funnel_id);
        }
    }

    /**
     * Process quiz answer callback
     */
    protected function processQuizAnswer(string $stepId, int $optionIndex): void
    {
        $step = TelegramFunnelStep::find($stepId);

        if (! $step || $step->step_type !== 'quiz') {
            return;
        }

        $quiz = $step->quiz ?? [];
        $options = $quiz['options'] ?? [];

        if (! isset($options[$optionIndex])) {
            return;
        }

        $selectedOption = $options[$optionIndex];

        Log::info('Quiz answer received', [
            'user_id' => $this->user->id,
            'step_id' => $stepId,
            'option_index' => $optionIndex,
            'option_text' => $selectedOption['text'] ?? '',
        ]);

        // Save answer to collected data
        $collectedData = $this->state->collected_data ?? [];
        $saveField = $quiz['save_answer_to'] ?? 'quiz_answer';
        $collectedData[$saveField] = $selectedOption['text'] ?? 'Variant '.($optionIndex + 1);
        $collectedData[$saveField.'_index'] = $optionIndex;
        $this->state->update([
            'collected_data' => $collectedData,
            'waiting_for' => 'none',
        ]);

        // Go to the option's next step if configured
        if (! empty($selectedOption['next_step_id'])) {
            usleep(200000);
            $this->goToStep($selectedOption['next_step_id']);
        } elseif ($step->next_step_id) {
            // Fall back to default next step
            usleep(200000);
            $this->goToStep($step->next_step_id);
        }
    }

    /**
     * Recheck subscription status after user clicks "Check" button
     */
    protected function recheckSubscription(string $stepId, string $callbackQueryId): void
    {
        $step = TelegramFunnelStep::find($stepId);

        if (! $step || $step->step_type !== 'subscribe_check') {
            $this->api->answerCallbackQuery($callbackQueryId);

            return;
        }

        $config = $step->subscribe_check ?? [];
        $channelUsername = $config['channel_username'] ?? '';

        if (empty($channelUsername)) {
            $this->api->answerCallbackQuery($callbackQueryId);

            return;
        }

        // Check subscription again
        $channelId = '@'.ltrim($channelUsername, '@');
        $result = $this->api->getChatMember($channelId, $this->user->telegram_id);

        $isSubscribed = false;
        if ($result['success']) {
            $status = $result['result']['status'] ?? 'left';
            $isSubscribed = in_array($status, ['member', 'administrator', 'creator']);
        }

        Log::info('Recheck subscription', [
            'user_id' => $this->user->id,
            'channel' => $channelUsername,
            'is_subscribed' => $isSubscribed,
        ]);

        $chatId = $this->user->telegram_id;

        if ($isSubscribed) {
            // User is now subscribed - answer callback and continue
            $this->api->answerCallbackQuery($callbackQueryId, '✅ Obuna tasdiqlandi!');
            $this->api->sendMessage($chatId, '✅ Obuna tasdiqlandi! Davom etamiz...');
            $this->state->update(['waiting_for' => 'none']);

            if ($step->subscribe_true_step_id) {
                usleep(500000);
                $this->goToStep($step->subscribe_true_step_id);
            }
        } else {
            // Still not subscribed - show alert popup
            $this->api->answerCallbackQuery(
                $callbackQueryId,
                "❌ Siz hali kanalga obuna bo'lmadingiz!",
                true
            );
        }
    }

    /**
     * Find trigger for text
     */
    protected function findTrigger(string $text): ?TelegramTrigger
    {
        $triggers = TelegramTrigger::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->whereIn('type', ['keyword', 'text'])
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($triggers as $trigger) {
            if ($trigger->matches($text)) {
                return $trigger;
            }
        }

        return null;
    }

    /**
     * Find trigger for callback data
     */
    protected function findCallbackTrigger(string $callbackData): ?TelegramTrigger
    {
        return TelegramTrigger::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->where('type', 'callback')
            ->where('value', $callbackData)
            ->first();
    }

    /**
     * Find funnel by trigger_keyword step
     */
    protected function findFunnelByKeyword(string $text): ?array
    {
        $text = mb_strtolower(trim($text));

        if (empty($text)) {
            return null;
        }

        // Get all active funnels for this bot
        $funnels = TelegramFunnel::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->get();

        foreach ($funnels as $funnel) {
            // Find trigger_keyword steps in this funnel
            $triggerSteps = TelegramFunnelStep::where('funnel_id', $funnel->id)
                ->where('step_type', 'trigger_keyword')
                ->get();

            foreach ($triggerSteps as $step) {
                $trigger = $step->trigger ?? [];

                // Check if is_all_messages is true
                if (! empty($trigger['is_all_messages'])) {
                    Log::info('Trigger matched: is_all_messages', [
                        'funnel_id' => $funnel->id,
                        'step_id' => $step->id,
                    ]);

                    return ['funnel' => $funnel, 'step' => $step];
                }

                // Check keywords
                $keywords = $trigger['keywords'] ?? '';
                $matchType = $trigger['match_type'] ?? 'contains';

                if (empty($keywords)) {
                    continue;
                }

                // Split keywords by comma or newline
                $keywordList = preg_split('/[,\n]+/', $keywords);
                $keywordList = array_map(fn ($k) => mb_strtolower(trim($k)), $keywordList);
                $keywordList = array_filter($keywordList);

                foreach ($keywordList as $keyword) {
                    $matched = match ($matchType) {
                        'exact' => $text === $keyword,
                        'contains' => str_contains($text, $keyword),
                        'starts_with' => str_starts_with($text, $keyword),
                        'ends_with' => str_ends_with($text, $keyword),
                        'regex' => @preg_match('/'.$keyword.'/iu', $text) === 1,
                        default => str_contains($text, $keyword),
                    };

                    if ($matched) {
                        Log::info('Trigger keyword matched', [
                            'funnel_id' => $funnel->id,
                            'step_id' => $step->id,
                            'keyword' => $keyword,
                            'match_type' => $matchType,
                            'text' => $text,
                        ]);

                        return ['funnel' => $funnel, 'step' => $step];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Start funnel from a trigger_keyword step
     */
    protected function startFunnelFromTriggerStep(TelegramFunnel $funnel, TelegramFunnelStep $triggerStep): void
    {
        Log::info('Starting funnel from trigger step', [
            'funnel_id' => $funnel->id,
            'funnel_name' => $funnel->name,
            'trigger_step_id' => $triggerStep->id,
            'user_id' => $this->user->id,
        ]);

        // Reset state and set funnel
        $this->state->update([
            'current_funnel_id' => $funnel->id,
            'current_step_id' => $triggerStep->id,
            'collected_data' => [],
            'waiting_for' => 'none',
            'context' => [],
        ]);

        // Update conversation
        $this->conversation->update(['started_funnel_id' => $funnel->id]);

        // Increment funnel start stat
        $this->incrementFunnelStat($funnel->id, 'started');

        // Go to the trigger's next step or execute the trigger step
        if ($triggerStep->next_step_id) {
            $this->goToStep($triggerStep->next_step_id);
        } else {
            // If no next step, send a message or do nothing
            Log::warning('Trigger step has no next_step_id', [
                'step_id' => $triggerStep->id,
            ]);
        }
    }

    /**
     * Process variables in content
     */
    protected function processVariables(array $content): array
    {
        $replacements = [
            '{first_name}' => $this->user->first_name ?? '',
            '{last_name}' => $this->user->last_name ?? '',
            '{full_name}' => $this->user->getFullName(),
            '{username}' => $this->user->username ? '@'.$this->user->username : '',
            '{phone}' => $this->user->phone ?? '',
            '{bot_name}' => $this->bot->bot_first_name ?? '',
        ];

        // Add collected data variables
        foreach ($this->state->collected_data ?? [] as $key => $value) {
            if (is_string($value)) {
                $replacements['{'.$key.'}'] = $value;
            }
        }

        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $content[$key] = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $value
                );
            }
        }

        return $content;
    }

    /**
     * Send fallback message
     */
    protected function sendFallbackMessage(int $chatId): void
    {
        $message = $this->bot->getFallbackMessage();
        $this->api->sendMessage($chatId, $message);
    }

    /**
     * Send help message
     */
    protected function sendHelpMessage(int $chatId): void
    {
        $message = $this->bot->getSettingValue('help_message',
            "Yordam kerakmi? /start buyrug'ini yuboring."
        );
        $this->api->sendMessage($chatId, $message);
    }

    /**
     * Handle contact message (phone number received)
     */
    protected function handleContactMessage(array $contact): void
    {
        $chatId = $this->user->telegram_id;
        $phone = $contact['phone_number'] ?? null;

        if ($phone) {
            // Update user phone
            $this->user->update([
                'phone' => $phone,
                'first_name' => $contact['first_name'] ?? $this->user->first_name,
                'last_name' => $contact['last_name'] ?? $this->user->last_name,
            ]);

            // Send confirmation with remove keyboard
            $keyboard = TelegramApiService::buildRemoveKeyboard();
            $thankYouMessage = $this->bot->getSettingValue('contact_received_message',
                "✅ Rahmat! Telefon raqamingiz saqlandi.\n\nEndi siz bilan bog'lanishimiz mumkin."
            );
            $this->api->sendMessage($chatId, $thankYouMessage, $keyboard);

            Log::info('User phone saved', [
                'bot_id' => $this->bot->id,
                'user_id' => $this->user->id,
                'phone' => $phone,
            ]);
        }
    }

    // Helper methods

    protected function isCommand(array $message): bool
    {
        $text = $message['text'] ?? '';

        return str_starts_with($text, '/');
    }

    protected function extractCommand(string $text): string
    {
        $parts = explode(' ', $text, 2);
        $command = $parts[0];

        // Remove @botusername if present
        if (str_contains($command, '@')) {
            $command = explode('@', $command)[0];
        }

        return strtolower($command);
    }

    protected function extractCommandArgs(string $text): ?string
    {
        $parts = explode(' ', $text, 2);

        return $parts[1] ?? null;
    }

    protected function getMessageText(array $message): string
    {
        return $message['text'] ?? $message['caption'] ?? '';
    }

    protected function parseCallbackData(string $data): array
    {
        $parts = explode(':', $data);
        $result = ['action' => $parts[0] ?? null];

        switch ($result['action']) {
            case 'step':
                $result['step_id'] = $parts[1] ?? null;
                break;
            case 'funnel':
                $result['funnel_id'] = $parts[1] ?? null;
                break;
            case 'input':
                $result['field'] = $parts[1] ?? null;
                $result['value'] = $parts[2] ?? null;
                break;
            case 'handoff':
                $result['reason'] = $parts[1] ?? null;
                break;
            case 'quiz_answer':
                $result['step_id'] = $parts[1] ?? null;
                $result['option_index'] = (int) ($parts[2] ?? 0);
                break;
            case 'recheck_subscribe':
                $result['step_id'] = $parts[1] ?? null;
                break;
        }

        return $result;
    }

    // Logging methods

    protected function logMessage(array $message, string $direction): void
    {
        $contentType = $this->determineContentType($message);

        TelegramMessage::create([
            'conversation_id' => $this->conversation->id,
            'telegram_message_id' => $message['message_id'] ?? null,
            'telegram_chat_id' => $message['chat']['id'],
            'direction' => $direction,
            'sender_type' => $direction === 'incoming' ? 'user' : 'bot',
            'content_type' => $contentType,
            'content' => $this->extractMessageContent($message, $contentType),
            'funnel_id' => $this->state->current_funnel_id,
            'step_id' => $this->state->current_step_id,
        ]);

        $this->conversation->updateLastMessageAt();
    }

    protected function logOutgoingMessage(TelegramFunnelStep $step, array $content, ?array $keyboard): void
    {
        TelegramMessage::create([
            'conversation_id' => $this->conversation->id,
            'telegram_chat_id' => $this->user->telegram_id,
            'direction' => 'outgoing',
            'sender_type' => 'bot',
            'content_type' => $content['type'] ?? 'text',
            'content' => $content,
            'keyboard' => $keyboard,
            'funnel_id' => $this->state->current_funnel_id,
            'step_id' => $step->id,
        ]);

        $this->conversation->updateLastMessageAt();
    }

    protected function logCallbackMessage(array $callbackQuery): void
    {
        TelegramMessage::create([
            'conversation_id' => $this->conversation->id,
            'telegram_message_id' => $callbackQuery['message']['message_id'] ?? null,
            'telegram_chat_id' => $callbackQuery['message']['chat']['id'],
            'direction' => 'incoming',
            'sender_type' => 'user',
            'content_type' => 'callback_query',
            'content' => [
                'callback_data' => $callbackQuery['data'],
                'message_text' => $callbackQuery['message']['text'] ?? null,
            ],
            'funnel_id' => $this->state->current_funnel_id,
            'step_id' => $this->state->current_step_id,
        ]);

        $this->conversation->updateLastMessageAt();
    }

    protected function determineContentType(array $message): string
    {
        if (isset($message['photo'])) {
            return 'photo';
        }
        if (isset($message['video'])) {
            return 'video';
        }
        if (isset($message['document'])) {
            return 'document';
        }
        if (isset($message['voice'])) {
            return 'voice';
        }
        if (isset($message['audio'])) {
            return 'audio';
        }
        if (isset($message['sticker'])) {
            return 'sticker';
        }
        if (isset($message['location'])) {
            return 'location';
        }
        if (isset($message['contact'])) {
            return 'contact';
        }
        if (str_starts_with($message['text'] ?? '', '/')) {
            return 'command';
        }

        return 'text';
    }

    protected function extractMessageContent(array $message, string $type): array
    {
        $content = [];

        switch ($type) {
            case 'text':
                $content['text'] = $message['text'] ?? '';
                break;
            case 'command':
                $text = $message['text'] ?? '';
                $parts = explode(' ', $text, 2);
                $content['command'] = $parts[0];
                $content['args'] = $parts[1] ?? null;
                break;
            case 'photo':
                $photo = end($message['photo']);
                $content['file_id'] = $photo['file_id'];
                $content['caption'] = $message['caption'] ?? null;
                break;
            case 'video':
                $content['file_id'] = $message['video']['file_id'];
                $content['caption'] = $message['caption'] ?? null;
                break;
            case 'document':
                $content['file_id'] = $message['document']['file_id'];
                $content['file_name'] = $message['document']['file_name'] ?? null;
                $content['caption'] = $message['caption'] ?? null;
                break;
            case 'voice':
                $content['file_id'] = $message['voice']['file_id'];
                $content['duration'] = $message['voice']['duration'] ?? null;
                break;
            case 'location':
                $content['latitude'] = $message['location']['latitude'];
                $content['longitude'] = $message['location']['longitude'];
                break;
            case 'contact':
                $content['phone_number'] = $message['contact']['phone_number'];
                $content['first_name'] = $message['contact']['first_name'] ?? null;
                $content['last_name'] = $message['contact']['last_name'] ?? null;
                break;
        }

        return $content;
    }

    // Stats methods

    protected function incrementDailyStat(string $field, int $count = 1): void
    {
        $stat = TelegramDailyStat::getOrCreateForToday($this->bot);

        switch ($field) {
            case 'messages_in':
                $stat->incrementMessagesIn($count);
                break;
            case 'messages_out':
                $stat->incrementMessagesOut($count);
                break;
            case 'conversations_started':
                $stat->incrementConversationsStarted($count);
                break;
            case 'leads_captured':
                $stat->incrementLeadsCaptured($count);
                break;
            case 'handoffs':
                $stat->incrementHandoffs($count);
                break;
        }
    }

    protected function incrementFunnelStat(string $funnelId, string $key, int $count = 1): void
    {
        $stat = TelegramDailyStat::getOrCreateForToday($this->bot);
        $stat->incrementFunnelStat($funnelId, $key, $count);
    }

    protected function incrementTriggerStat(string $triggerId, int $count = 1): void
    {
        $stat = TelegramDailyStat::getOrCreateForToday($this->bot);
        $stat->incrementTriggerStat($triggerId, $count);
    }

    // Notification methods

    protected function sendNotification(TelegramFunnelStep $step): void
    {
        // TODO: Implement notification to operators
        $config = $step->action_config ?? [];
        Log::info('Funnel notification', [
            'step_id' => $step->id,
            'user_id' => $this->user->id,
            'config' => $config,
            'collected_data' => $this->state->collected_data,
        ]);
    }

    protected function sendWebhook(TelegramFunnelStep $step): void
    {
        // TODO: Implement webhook sending
        $config = $step->action_config ?? [];
        Log::info('Funnel webhook', [
            'step_id' => $step->id,
            'user_id' => $this->user->id,
            'config' => $config,
            'collected_data' => $this->state->collected_data,
        ]);
    }
}
