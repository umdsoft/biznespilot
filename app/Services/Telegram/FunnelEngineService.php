<?php

namespace App\Services\Telegram;

use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\TelegramBot;
use App\Models\TelegramConversation;
use App\Models\TelegramDailyStat;
use App\Models\TelegramFunnel;
use App\Models\TelegramFunnelStep;
use App\Models\TelegramMessage;
use App\Models\TelegramTrigger;
use App\Models\TelegramUser;
use App\Models\TelegramUserState;
use App\Models\Store\TelegramStore;
use App\Services\SubscriptionGate;
use Illuminate\Support\Facades\Log;

class FunnelEngineService
{
    protected TelegramApiService $api;

    protected TelegramBot $bot;

    protected TelegramUser $user;

    protected TelegramUserState $state;

    protected TelegramConversation $conversation;

    /**
     * Bitta webhook request davomida ishlagan step'lar ro'yxati.
     * Tag → Tag self-loop yoki condition → condition cycle'dan himoya qiladi.
     * PHP-FPM stack overflow oldini oladi.
     */
    protected array $visitedSteps = [];

    /**
     * Maksimum step-chain uzunligi — bundan katta bo'lsa loop detect etiladi.
     */
    protected const MAX_STEP_CHAIN = 50;

    public function __construct(TelegramBot $bot, TelegramUser $user)
    {
        $this->bot = $bot;
        $this->user = $user;
        $this->api = new TelegramApiService($bot);
        $this->state = $user->state ?? $this->createState();
        $this->conversation = $this->getOrCreateConversation();

        // Agar kontekst (conversation) Business Connection orqali bo'lgan bo'lsa —
        // API servisini shu connection'ga bog'laymiz, shunda barcha sendMessage'lar
        // business account nomidan yuboriladi.
        if (! empty($this->conversation->business_connection_id)) {
            $this->api->forBusinessConnection($this->conversation->business_connection_id);
        }
    }

    /**
     * ExecuteFunnelStepJob chaqirganda manual override — conversation loaded bo'lmasligi
     * yoki eski bo'lishi mumkin. Job payload'idan keladigan ishonchli connectionId.
     */
    public function setBusinessConnection(?string $connectionId): self
    {
        $this->api->forBusinessConnection($connectionId);
        return $this;
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
                // Handle menu callbacks from /start inline keyboard
                if (str_starts_with($callbackData, 'menu_')) {
                    $this->handleMenuCallback($callbackData, $chatId, $callbackQueryId);
                    $answerCallback = false; // handled internally
                    break;
                }

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

        // Professional /start flow — yangi yoki qaytgan foydalanuvchi
        $store = TelegramStore::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->first();

        // SECURITY: user-controlled fields (first_name) Telegram HTML parse_mode bilan render
        // qilinadi. Agar user ismida `<a href="evil">` bo'lsa — clickable link bo'lib ko'rinadi.
        // Shuning uchun inline interpolationdan avval HTML special charachters'ni escape qilamiz.
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $botName = $esc($this->bot->bot_first_name ?: $this->bot->bot_username);
        $userName = $this->user->first_name ? $esc($this->user->first_name) : '';
        $isNewUser = ! $this->user->first_interaction_at
            || $this->user->first_interaction_at->diffInMinutes(now()) < 2;

        // Welcome text
        if ($isNewUser) {
            $text = "👋 Assalomu alaykum" . ($userName ? ", <b>{$userName}</b>" : '') . "!\n\n";
            $text .= "🤖 <b>{$botName}</b> ga xush kelibsiz!\n";
            if ($store) {
                $storeName = $esc($store->name);
                $text .= "\n🛍 <b>{$storeName}</b> — sizning shaxsiy do'koningiz.\n";
                if ($store->description) {
                    $text .= $esc($store->description) . "\n";
                }
            }
            $text .= "\nQuyidagi menyudan kerakli bo'limni tanlang:";
        } else {
            $text = "👋 Qaytganingiz bilan" . ($userName ? ", <b>{$userName}</b>" : '') . "!\n\n";
            $text .= "Quyidagi menyudan kerakli bo'limni tanlang:";
        }

        // Inline keyboard menu
        $buttons = [];

        if ($store) {
            $miniAppUrl = config('app.url') . "/miniapp/{$store->slug}";
            $buttons[] = [
                ['text' => '🛒 Katalog', 'web_app' => ['url' => $miniAppUrl]],
            ];
            $buttons[] = [
                ['text' => '🏷 Aksiyalar', 'callback_data' => 'menu_deals'],
                ['text' => '📦 Buyurtmalarim', 'callback_data' => 'menu_orders'],
            ];
        }

        $buttons[] = [
            ['text' => '📞 Bog\'lanish', 'callback_data' => 'menu_contact'],
            ['text' => 'ℹ️ Biz haqimizda', 'callback_data' => 'menu_about'],
        ];

        $keyboard = ['inline_keyboard' => $buttons];
        $this->api->sendMessage($chatId, $text, $keyboard, 'HTML');
    }

    /**
     * Handle menu callback from /start inline keyboard
     */
    public function handleMenuCallback(string $action, int $chatId, string $callbackQueryId): void
    {
        $store = TelegramStore::where('telegram_bot_id', $this->bot->id)
            ->where('is_active', true)
            ->first();

        // SECURITY: parse_mode=HTML bilan yuboriladi — business'ning admin-kiritadigan
        // matnlarini ham escape qilamiz. Admin malicious emasligini taxmin qilmaymiz
        // (defense-in-depth) — lekin asosan user-controlled fields HTML injection oldini
        // olish uchun.
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $botName = $esc($this->bot->bot_first_name ?: $this->bot->bot_username);
        $business = $this->bot->business;

        switch ($action) {
            case 'menu_deals':
                $text = "🏷 <b>Aksiyalar va maxsus takliflar</b>\n\n";
                $text .= "Hozircha faol aksiyalar yo'q.\nYangi aksiyalar haqida birinchi bo'lib xabar olish uchun kuzatib boring!";
                $keyboard = ['inline_keyboard' => [
                    [['text' => '◀️ Asosiy menyu', 'callback_data' => 'menu_main']],
                ]];
                $this->api->sendMessage($chatId, $text, $keyboard, 'HTML');
                break;

            case 'menu_orders':
                $text = "📦 <b>Buyurtmalarim</b>\n\n";
                $text .= "Buyurtmalaringizni do'kon orqali ko'rishingiz mumkin.";
                $buttons = [];
                if ($store) {
                    $miniAppUrl = config('app.url') . "/miniapp/{$store->slug}";
                    $buttons[] = [['text' => '🛒 Do\'konni ochish', 'web_app' => ['url' => $miniAppUrl]]];
                }
                $buttons[] = [['text' => '◀️ Asosiy menyu', 'callback_data' => 'menu_main']];
                $this->api->sendMessage($chatId, $text, ['inline_keyboard' => $buttons], 'HTML');
                break;

            case 'menu_contact':
                $text = "📞 <b>Bog'lanish</b>\n\n";
                if ($business) {
                    if ($business->phone) {
                        $text .= "📱 Telefon: " . $esc($business->phone) . "\n";
                    }
                    if ($business->email) {
                        $text .= "📧 Email: " . $esc($business->email) . "\n";
                    }
                    if ($business->address) {
                        $text .= "📍 Manzil: " . $esc($business->address) . "\n";
                    }
                    if ($business->website) {
                        $text .= "🌐 Sayt: " . $esc($business->website) . "\n";
                    }
                }
                if ($text === "📞 <b>Bog'lanish</b>\n\n") {
                    $text .= "Bog'lanish ma'lumotlari tez orada qo'shiladi.";
                }
                $keyboard = ['inline_keyboard' => [
                    [['text' => '◀️ Asosiy menyu', 'callback_data' => 'menu_main']],
                ]];
                $this->api->sendMessage($chatId, $text, $keyboard, 'HTML');
                break;

            case 'menu_about':
                $text = "ℹ️ <b>Biz haqimizda</b>\n\n";
                $text .= "<b>{$botName}</b>";
                if ($business && $business->description) {
                    $text .= "\n\n" . $esc($business->description);
                } elseif ($store && $store->description) {
                    $text .= "\n\n" . $esc($store->description);
                }
                $keyboard = ['inline_keyboard' => [
                    [['text' => '◀️ Asosiy menyu', 'callback_data' => 'menu_main']],
                ]];
                $this->api->sendMessage($chatId, $text, $keyboard, 'HTML');
                break;

            case 'menu_main':
                // Re-send the /start menu
                $this->handleStartCommand(null);
                break;
        }

        $this->api->answerCallbackQuery($callbackQueryId);
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

        // SECURITY: tenant scoping — faqat shu bot'ga tegishli funnel'ni olish.
        // Aks holda malicious user callback_data'da boshqa biznes funnel_id'sini
        // kiritib, cross-tenant o'tish qilishi mumkin.
        $funnel = TelegramFunnel::where('id', $funnelId)
            ->where('telegram_bot_id', $this->bot->id)
            ->first();

        if (! $funnel || ! $funnel->is_active) {
            Log::warning('FunnelEngine: Funnel not found, inactive, or cross-tenant', [
                'funnel_id' => $funnelId,
                'bot_id' => $this->bot->id,
                'funnel_exists' => $funnel !== null,
                'is_active' => $funnel?->is_active,
                'user_id' => $this->user->id,
            ]);

            return;
        }

        // Get first step or specified step — step ham scope ichida bo'lishi kerak
        $step = $stepId
            ? $this->findScopedStep($stepId, $funnel->id)
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
     * Go to step — cross-tenant scoping HIMOYA QILINGAN.
     *
     * Step faqat shu bot'ning faol funnel'iga tegishli bo'lishi kerak.
     * Malicious callback_data="step:<boshqa tenant UUID>" bloklanadi.
     */
    public function goToStep(string $stepId): void
    {
        $step = $this->findScopedStep($stepId);

        if (! $step) {
            Log::warning('FunnelEngine: Step not found or cross-tenant access blocked', [
                'step_id' => $stepId,
                'bot_id' => $this->bot->id,
                'user_id' => $this->user->id,
            ]);

            return;
        }

        $this->state->moveTo($step);
        $this->executeStep($step);
    }

    /**
     * Step'ni bu bot'ning funnel'lari ichidan topish — tenant scoping.
     *
     * Optional $funnelId berilsa, step shu funnel ichida bo'lishi kerak.
     * Barcha kritik step topish joylarida ishlatilishi kerak.
     */
    protected function findScopedStep(string $stepId, ?string $funnelId = null): ?TelegramFunnelStep
    {
        $query = TelegramFunnelStep::where('id', $stepId)
            ->whereHas('funnel', function ($q) use ($funnelId) {
                $q->where('telegram_bot_id', $this->bot->id);
                if ($funnelId !== null) {
                    $q->where('id', $funnelId);
                }
            });

        return $query->first();
    }

    /**
     * Execute step
     */
    protected function executeStep(TelegramFunnelStep $step): void
    {
        // LOOP GUARD — step bir webhook davomida ikki marta ishlasa yoki zanjir
        // MAX_STEP_CHAIN dan oshsa, to'xtatamiz. Bu tag→tag, condition→condition,
        // yoki misconfigured A/B variant→o'zi o'ziga pointing holatlardan himoya qiladi.
        $stepKey = (string) $step->id;
        if (isset($this->visitedSteps[$stepKey])) {
            Log::warning('FunnelEngine: loop detected — step visited twice in same run', [
                'step_id' => $stepKey,
                'user_id' => $this->user->id,
                'visited' => array_keys($this->visitedSteps),
            ]);
            return;
        }
        if (count($this->visitedSteps) >= self::MAX_STEP_CHAIN) {
            Log::warning('FunnelEngine: step chain exceeded limit — aborting', [
                'limit' => self::MAX_STEP_CHAIN,
                'user_id' => $this->user->id,
                'last_step' => $stepKey,
            ]);
            return;
        }
        $this->visitedSteps[$stepKey] = true;

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

            case 'delay':
                // Delay step — kechikish bilan keyingi step'ga o'tish.
                // delay_seconds yoki delay_ms qiymatlari qabul qilinadi.
                $this->executeDelayStep($step);

                return;

            case 'action':
                // Action step — create_lead, send_notification, webhook, va h.k.
                $this->executeAction($step);
                if ($step->next_step_id) {
                    usleep(200000);
                    $this->goToStep($step->next_step_id);
                }
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
        $collectedData = $this->state->collected_data ?? [];

        // has_tag:<tag_name> — spetsifik tag nomini tekshirish.
        // "has_tag" yolg'iz bo'lsa — eski xulq (har qanday tag mavjudmi).
        if (str_starts_with($field, 'has_tag:')) {
            $needle = substr($field, strlen('has_tag:'));
            $needle = trim($needle);
            $tags = is_array($this->user->tags) ? $this->user->tags : [];
            return in_array($needle, $tags, true);
        }

        // Dotted notation: `user.first_name`, `variables.score`, `lead.phone`, `context.xxx`.
        if (str_contains($field, '.')) {
            [$ns, $path] = explode('.', $field, 2);
            switch ($ns) {
                case 'user':
                    return $this->resolveUserField($path);
                case 'lead':
                    return $this->resolveLeadField($path);
                case 'variables':
                case 'vars':
                case 'collected':
                    return data_get($collectedData, $path);
                case 'bot':
                    return data_get([
                        'name' => $this->bot->bot_first_name,
                        'username' => $this->bot->bot_username,
                        'id' => $this->bot->id,
                    ], $path);
            }
        }

        // First check collected data (bare key).
        if (array_key_exists($field, $collectedData)) {
            return $collectedData[$field];
        }

        if ($field === 'has_tag') {
            return ! empty($this->user->tags);
        }
        if ($field === 'quiz_answer') {
            return $collectedData['quiz_answer'] ?? null;
        }
        if ($field === 'interaction_count') {
            return TelegramMessage::where('telegram_user_id', $this->user->id)->count();
        }

        // Bare key → user model fallback.
        return $this->resolveUserField($field) ?? ($this->user->custom_data[$field] ?? null);
    }

    protected function resolveUserField(string $path): mixed
    {
        return match ($path) {
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'username' => $this->user->username,
            'phone' => $this->user->phone,
            'email' => $this->user->email ?? null,
            'language_code' => $this->user->language_code,
            'is_premium' => (bool) ($this->user->is_premium ?? false),
            'id', 'user_id', 'telegram_id' => $this->user->telegram_id,
            default => data_get($this->user->custom_data ?? [], $path),
        };
    }

    protected function resolveLeadField(string $path): mixed
    {
        if (empty($this->user->lead_id)) {
            return null;
        }
        $lead = Lead::find($this->user->lead_id);
        if (! $lead) {
            return null;
        }
        return data_get($lead->toArray(), $path);
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

            // URL injection oldini olish — faqat xavfsiz belgilar.
            $cleanChannel = preg_replace('/[^a-zA-Z0-9_]/', '', ltrim($channelUsername, '@'));
            if ($cleanChannel === '') {
                Log::warning('FunnelEngine: subscribe_check invalid channel_username', [
                    'step_id' => $step->id,
                    'raw' => $channelUsername,
                ]);
                return;
            }

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => $buttonText, 'url' => "https://t.me/{$cleanChannel}"],
                    ],
                    [
                        ['text' => 'Tekshirish ✓', 'callback_data' => "recheck_subscribe:{$step->id}"],
                    ],
                ],
            ];

            // TUZATISH: sendMessage($chatId, $text, $keyboard) — $keyboard to'g'ridan-to'g'ri
            // inline_keyboard arrayi, sendMessage ichida json_encode qilinadi.
            // Avval bu yerda `['reply_markup' => json_encode($keyboard)]` bo'lgan — bu double-wrap
            // edi va Telegram tugmalarni ko'rsatmay qo'ygan.
            $this->api->sendMessage($chatId, $message, $keyboard);

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

        // $keyboard to'g'ridan-to'g'ri — sendMessage ichida json_encode qilinadi.
        $result = $this->api->sendMessage($chatId, $question, $keyboard);

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

        $stickyKey = "ab_test_{$step->id}";
        $collectedData = $this->state->collected_data ?? [];
        $selectedVariant = null;

        // Agar foydalanuvchi ushbu step'ga avval kirgan bo'lsa — xuddi o'sha variant.
        // Bu funnel'ga qayta kirganda (recheck / restart) A/B natijalar buzilmasligiga kafolat beradi.
        if (! empty($collectedData[$stickyKey])) {
            $previousName = $collectedData[$stickyKey];
            foreach ($variants as $v) {
                if (($v['name'] ?? null) === $previousName) {
                    $selectedVariant = $v;
                    break;
                }
            }
        }

        // Birinchi kirish — deterministic bucket (crc32 user_id + step_id).
        // Shu user har doim bir xil variantga tushadi. mt_rand() o'rniga.
        if (! $selectedVariant) {
            $bucketSeed = (string) $this->user->id . ':' . (string) $step->id;
            $bucket = (int) (crc32($bucketSeed) % 100) + 1; // 1..100
            $cumulative = 0;
            foreach ($variants as $variant) {
                $cumulative += (int) ($variant['percentage'] ?? 0);
                if ($bucket <= $cumulative) {
                    $selectedVariant = $variant;
                    break;
                }
            }
            if (! $selectedVariant) {
                $selectedVariant = $variants[0];
            }
        }

        Log::info('A/B test variant selected', [
            'user_id' => $this->user->id,
            'step_id' => $step->id,
            'variant' => $selectedVariant['name'] ?? 'Unknown',
            'sticky' => isset($collectedData[$stickyKey]),
        ]);

        // Sticky yozuv — keyingi re-entry'larda o'qiladi.
        if (! isset($collectedData[$stickyKey])) {
            $collectedData[$stickyKey] = $selectedVariant['name'] ?? 'A';
            $this->state->update(['collected_data' => $collectedData]);
        }

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
     * Execute delay step — kechikish bilan keyingi step'ga o'tish.
     *
     * Hozirgi implementatsiya: sinxron `usleep()` ishlatadi (eski inline
     * behaviour). Bu PHP-FPM worker'ni bloklaydi — 5+ soniyalik delay
     * bo'lsa webhook timeout'ga tushishi mumkin. TIER-3 refactor'da bu
     * `ExecuteFunnelStepJob::dispatch()->delay(...)` bo'lishi kerak.
     *
     * Qabul qilinadigan maydonlar (prioritet bo'yicha):
     *   - `delay_ms` (integer, millisekund — DB column)
     *   - `content.delay_seconds` (integer, sekund — builder yuboradi)
     *   - default: 0
     */
    protected function executeDelayStep(TelegramFunnelStep $step): void
    {
        $content = $step->getContent() ?? [];
        $delayMs = 0;

        if (! empty($step->delay_ms)) {
            $delayMs = (int) $step->delay_ms;
        } elseif (isset($content['delay_seconds'])) {
            $delayMs = (int) $content['delay_seconds'] * 1000;
        } elseif (isset($content['delay_ms'])) {
            $delayMs = (int) $content['delay_ms'];
        }

        // Maksimum 1 soat delay — nazorat ostida saqlaymiz.
        $delayMs = max(0, min($delayMs, 3600_000));

        if (! $step->next_step_id) {
            Log::info('FunnelEngine: delay step has no next_step_id — terminal, completing funnel', [
                'step_id' => $step->id,
            ]);
            $this->completeFunnel();
            return;
        }

        // Qisqa delay (<1s) — inline. PHP-FPM ga deyarli ta'sir qilmaydi.
        if ($delayMs > 0 && $delayMs < 1000) {
            usleep($delayMs * 1000);
            $this->goToStep($step->next_step_id);
            return;
        }

        // Uzun delay — queue'ga dispatch qilib darhol webhook'dan chiqamiz.
        // `delay()` uchun sekund; ms dan konvertatsiya.
        $delaySeconds = (int) ceil($delayMs / 1000);

        // Current step'ni belgilab qo'yamiz — job idempotency uchun.
        $this->state->update(['current_step_id' => $step->id]);

        \App\Jobs\ExecuteFunnelStepJob::dispatch(
            (string) $this->bot->id,
            (string) $this->user->id,
            (string) $step->next_step_id,
            (string) $step->id, // expectedCurrentStepId — job ishga tushganda shu bo'lishi kerak
            $this->conversation->business_connection_id, // Business Bot connection propagation
        )->delay(now()->addSeconds($delaySeconds));

        Log::info('FunnelEngine: delay step dispatched', [
            'step_id' => $step->id,
            'next_step_id' => $step->next_step_id,
            'delay_seconds' => $delaySeconds,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Job tomonidan chaqiriladi — kelgusi step'ni bajarish uchun.
     * Bu public, chunki ExecuteFunnelStepJob uni boshqa process'da chaqiradi.
     *
     * Cross-tenant tekshiruv job ichida allaqachon bajarilgan — shu yerda
     * step'ni to'g'ridan-to'g'ri ishga tushiramiz (goToStep pipeline'iga kiramiz).
     */
    public function executeDeferredStep(string $stepId): void
    {
        $step = $this->findScopedStep($stepId);
        if (! $step) {
            Log::warning('FunnelEngine: deferred step not found', [
                'step_id' => $stepId,
                'bot_id' => $this->bot->id,
            ]);
            return;
        }

        $this->executeStep($step);
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
        // `??` empty string'ni null emas deb qabul qiladi — shu bois file_id='' holatda
        // url forward qilinmasdi. Bu yerda empty tekshirish bilan to'g'ri fallback:
        // Telegram file_id oldin, agar u bo'sh bo'lsa — URL.
        $fileId = null;
        if (! empty($content['file_id'])) {
            $fileId = $content['file_id'];
        } elseif (! empty($content['url'])) {
            $fileId = $content['url'];
        }

        // parse_mode — UI content.parse_mode ni forward qilamiz. Default HTML.
        // Telegram qabul qiladi: 'HTML', 'Markdown', 'MarkdownV2'.
        $allowedParseModes = ['HTML', 'Markdown', 'MarkdownV2'];
        $parseMode = $content['parse_mode'] ?? 'HTML';
        if (! in_array($parseMode, $allowedParseModes, true)) {
            $parseMode = 'HTML';
        }

        // Build keyboard if needed
        $replyMarkup = null;
        if ($keyboard) {
            $replyMarkup = $this->buildKeyboard($keyboard, $step);
            // Bo'sh inline_keyboard Telegram tomonidan rad etiladi — fallback null.
            if (isset($replyMarkup['inline_keyboard']) && empty(array_filter($replyMarkup['inline_keyboard']))) {
                $replyMarkup = null;
            }
        }

        // Media turlari uchun file_id/url bo'sh bo'lsa — matnga tushirib yuboramiz (silent fail oldini olish).
        $mediaTypes = ['photo', 'video', 'voice', 'video_note', 'document'];
        if (in_array($type, $mediaTypes, true) && empty($fileId)) {
            Log::warning('FunnelEngine: media step has no file/url — fallback to text', [
                'step_id' => $step->id,
                'type' => $type,
            ]);
            $type = 'text';
        }

        switch ($type) {
            case 'photo':
                return $this->api->sendPhoto($chatId, $fileId, $caption, $replyMarkup, $parseMode);

            case 'video':
                return $this->api->sendVideo($chatId, $fileId, $caption, $replyMarkup, $parseMode);

            case 'voice':
                return $this->api->sendVoice($chatId, $fileId, $caption, $replyMarkup, $parseMode);

            case 'video_note':
                return $this->api->sendVideoNote($chatId, $fileId, $content['duration'] ?? null, $replyMarkup);

            case 'document':
                return $this->api->sendDocument($chatId, $fileId, $caption, $replyMarkup, $parseMode);

            case 'location':
                if (! isset($content['latitude'], $content['longitude'])) {
                    Log::warning('FunnelEngine: location step missing coords — fallback to text', [
                        'step_id' => $step->id,
                    ]);
                    return $this->api->sendMessage($chatId, $text !== '' ? $text : 'Lokatsiya belgilanmagan', $replyMarkup, $parseMode);
                }
                return $this->api->sendLocation(
                    $chatId,
                    (float) $content['latitude'],
                    (float) $content['longitude'],
                    $replyMarkup
                );

            default:
                // Bo'sh matn bilan sendMessage Telegram'da 400 — fallback placeholder.
                $body = $text !== '' ? $text : '...';
                return $this->api->sendMessage($chatId, $body, $replyMarkup, $parseMode);
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

        $validation = $step->validation ?? [];
        $retryLimit = isset($validation['retry_count']) && $validation['retry_count'] !== null
            ? (int) $validation['retry_count']
            : null;

        $retryKey = "__retry:{$step->id}";
        $currentRetries = (int) ($this->state->collected_data[$retryKey] ?? 0);

        if ($value === null) {
            $this->handleInputFailure($step, $message['chat']['id'], $retryKey, $currentRetries, $retryLimit);
            return;
        }

        // Validate input
        if (! $this->validateInput($value, $step)) {
            $this->handleInputFailure($step, $message['chat']['id'], $retryKey, $currentRetries, $retryLimit);
            return;
        }

        // Validatsiya muvaffaqiyatli — retry counter'ni tozalaymiz.
        if ($currentRetries > 0) {
            $cleared = $this->state->collected_data ?? [];
            unset($cleared[$retryKey]);
            $this->state->update(['collected_data' => $cleared]);
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

        // Required — bo'sh bo'lsa rad qilish.
        if (! empty($validation['required'])) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                return false;
            }
        }

        // Hamma input type uchun umumiy uzunlik tekshiruvi (skalar bo'lsa).
        if (is_scalar($value)) {
            $strValue = (string) $value;
            $length = mb_strlen($strValue);
            if (isset($validation['min_length']) && $length < (int) $validation['min_length']) {
                return false;
            }
            if (isset($validation['max_length']) && $length > (int) $validation['max_length']) {
                return false;
            }
            if (! empty($validation['pattern'])) {
                // Xavfsiz regex: UI pattern faqat o'zagi — delimiter + `u` modifier qo'shamiz.
                // Noto'g'ri regex `@preg_match` bilan qoplansa E_WARNING keltiradi; biz yo'q desak ham PHP 8'da `preg_last_error()` tekshirsak bas.
                $pattern = '@' . str_replace('@', '\\@', (string) $validation['pattern']) . '@u';
                $result = @preg_match($pattern, $strValue);
                if ($result === false) {
                    // Invalid regex — admin xatosi; logga qo'yib true qaytaramiz (blokirovka qilmaslik uchun).
                    Log::warning('FunnelEngine: invalid validation regex', [
                        'step_id' => $step->id,
                        'pattern' => $validation['pattern'],
                    ]);
                    return true;
                }
                if ($result === 0) {
                    return false;
                }
            }
        }

        // Type-specific validation
        switch ($inputType) {
            case 'email':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
                break;

            case 'phone':
                if (! preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', (string) $value)) {
                    return false;
                }
                break;

            case 'number':
                if (! is_numeric($value)) {
                    return false;
                }
                $numeric = (float) $value;
                if (isset($validation['min']) && $numeric < (float) $validation['min']) {
                    return false;
                }
                if (isset($validation['max']) && $numeric > (float) $validation['max']) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Noto'g'ri input uchun markaziy handler — retry_count'ni oshiradi, limit
     * oshgan bo'lsa funnel'ni to'xtatadi, aks holda xato xabari yuboradi.
     */
    protected function handleInputFailure(
        TelegramFunnelStep $step,
        int $chatId,
        string $retryKey,
        int $currentRetries,
        ?int $retryLimit
    ): void {
        $newCount = $currentRetries + 1;

        // Limit oshgan bo'lsa — waiting_for ni tozalab, funnel'dan chiqaramiz.
        if ($retryLimit !== null && $newCount > $retryLimit) {
            Log::info('FunnelEngine: input retry limit exceeded', [
                'step_id' => $step->id,
                'user_id' => $this->user->id,
                'limit' => $retryLimit,
            ]);

            // Counter'ni tozalaymiz.
            $cleared = $this->state->collected_data ?? [];
            unset($cleared[$retryKey]);
            $this->state->update([
                'collected_data' => $cleared,
                'waiting_for' => 'none',
            ]);

            // Final xatolik xabari va completeFunnel.
            $finalMsg = $step->getValidationErrorMessage() ?? "Kiritish noto'g'ri. Keyinroq qayta urinib ko'ring.";
            $this->api->sendMessage($chatId, $finalMsg);
            $this->completeFunnel();
            return;
        }

        // Hali limit ostida — counter'ni oshirib, error xabar yuboramiz.
        $updatedData = $this->state->collected_data ?? [];
        $updatedData[$retryKey] = $newCount;
        $this->state->update(['collected_data' => $updatedData]);

        $this->sendValidationError($step, $chatId);
    }

    /**
     * Send validation error
     */
    protected function sendValidationError(TelegramFunnelStep $step, int $chatId): void
    {
        $errorMessage = $step->getValidationErrorMessage() ?? "Noto'g'ri format. Iltimos qaytadan urinib ko'ring.";
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

        // Complete the funnel ONLY if this is a terminal step (no next step).
        // Otherwise preserve collected_data and let the caller navigate to next_step_id.
        if (! $step->next_step_id) {
            $this->completeFunnel();
        }
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

        // Status biznesning birinchi PipelineStage slug'idan olinadi.
        // Eski "new" hardcoded bo'lgani sabab, biznes pipeline'ini o'zgartirsa
        // lead ko'rinmay qolar edi.
        $defaultStatus = PipelineStage::where('business_id', $this->bot->business_id)
            ->orderBy('order')
            ->value('slug') ?? 'new';

        // Agar shu TelegramUser uchun allaqachon Lead mavjud bo'lsa — qayta yaratmasdan
        // mavjud record'ni yangilaymiz. Bu race condition va ikkita parallel webhook
        // update'ni dedup qiladi.
        $existingLead = null;
        if (! empty($this->user->lead_id)) {
            $existingLead = Lead::where('id', $this->user->lead_id)
                ->where('business_id', $this->bot->business_id)
                ->first();
        }
        if (! $existingLead) {
            // Fallback qidirish — collected_data da phone/email yozilgan bo'lsa.
            $phone = $collectedData[$config['phone_field'] ?? 'phone'] ?? $this->user->phone;
            $email = $collectedData[$config['email_field'] ?? 'email'] ?? null;
            $query = Lead::where('business_id', $this->bot->business_id);
            if ($phone) {
                $query->where('phone', $phone);
            } elseif ($email) {
                $query->where('email', $email);
            } else {
                $query->whereJsonContains('data->telegram_user_id', $this->user->telegram_id);
            }
            $existingLead = $query->first();
        }

        $leadData = [
            'business_id' => $this->bot->business_id,
            'source_id' => $sourceId,
            'name' => $collectedData[$config['name_field'] ?? 'name'] ?? $this->user->getFullName(),
            'phone' => $collectedData[$config['phone_field'] ?? 'phone'] ?? $this->user->phone,
            'email' => $collectedData[$config['email_field'] ?? 'email'] ?? null,
            'status' => $defaultStatus,
            'notes' => json_encode([
                'telegram_user_id' => $this->user->telegram_id,
                'funnel' => $this->state->currentFunnel?->name,
                'collected_data' => $collectedData,
            ], JSON_UNESCAPED_UNICODE),
            'data' => [
                'source' => 'telegram_funnel',
                'bot_id' => $this->bot->id,
                'funnel_id' => $this->state->current_funnel_id,
                'telegram_user_id' => $this->user->telegram_id,
            ],
        ];

        if ($existingLead) {
            // Mavjud lead uchun — faqat qo'shimcha qiymatlar bilan to'ldiramiz:
            // bo'sh/null field'lar eski qiymatni saqlaydi; business_id, source_id, status'ni
            // o'zgartirmaymiz (context'da stage allaqachon o'zgargan bo'lishi mumkin).
            $fillable = [];
            foreach (['name', 'phone', 'email'] as $f) {
                if (! empty($leadData[$f])) {
                    $fillable[$f] = $leadData[$f];
                }
            }
            // `notes` va `data` — har safar yangilanadi (funnel progress uchun).
            $fillable['notes'] = $leadData['notes'];
            $fillable['data'] = array_merge($existingLead->data ?? [], $leadData['data']);
            $existingLead->update($fillable);
            $lead = $existingLead;
        } else {
            $lead = Lead::create($leadData);
            $this->incrementDailyStat('leads_captured');
            $this->incrementFunnelStat($this->state->current_funnel_id, 'leads');
        }

        // Link lead to conversation
        $this->conversation->update(['lead_id' => $lead->id]);

        // Update user
        $this->user->update(['lead_id' => $lead->id]);
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
        // SECURITY: step faqat shu bot'ning funnel'iga tegishli bo'lishi kerak
        $step = $this->findScopedStep($stepId);

        if (! $step || $step->step_type !== 'quiz') {
            Log::warning('FunnelEngine: Quiz answer rejected (not found or cross-tenant)', [
                'step_id' => $stepId,
                'bot_id' => $this->bot->id,
            ]);
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

        // Correct-answer rejimi: correct_option_index o'rnatilgan bo'lsa — score yig'iladi,
        // natija collected_data'ga yoziladi, routing correct_step_id/wrong_step_id orqali boradi.
        $hasCorrectMode = array_key_exists('correct_option_index', $quiz)
            && $quiz['correct_option_index'] !== null
            && $quiz['correct_option_index'] !== '';
        $isCorrect = null;
        if ($hasCorrectMode) {
            $correctIdx = (int) $quiz['correct_option_index'];
            $isCorrect = ($optionIndex === $correctIdx);
            $collectedData[$saveField.'_is_correct'] = $isCorrect;

            if ($isCorrect) {
                $scoreField = $quiz['score_field'] ?? 'quiz_score';
                $scoreOnCorrect = (int) ($quiz['score_on_correct'] ?? 1);
                $currentScore = (int) ($collectedData[$scoreField] ?? 0);
                $collectedData[$scoreField] = $currentScore + $scoreOnCorrect;
            }
        }

        $this->state->update([
            'collected_data' => $collectedData,
            'waiting_for' => 'none',
        ]);

        // Routing priority:
        //   1. correct-mode'da — correct_step_id / wrong_step_id
        //   2. optionning o'z next_step_id
        //   3. step-level fallback next_step_id
        $targetStepId = null;
        if ($hasCorrectMode) {
            $targetStepId = $isCorrect
                ? ($quiz['correct_step_id'] ?? null)
                : ($quiz['wrong_step_id'] ?? null);
        }
        if (! $targetStepId && ! empty($selectedOption['next_step_id'])) {
            $targetStepId = $selectedOption['next_step_id'];
        }
        if (! $targetStepId && $step->next_step_id) {
            $targetStepId = $step->next_step_id;
        }

        if ($targetStepId) {
            usleep(200000);
            $this->goToStep($targetStepId);
        }
    }

    /**
     * Recheck subscription status after user clicks "Check" button
     */
    protected function recheckSubscription(string $stepId, string $callbackQueryId): void
    {
        // SECURITY: tenant scoping
        $step = $this->findScopedStep($stepId);

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
     * Process variables in content.
     *
     * SECURITY: foydalanuvchidan olingan qiymatlar (first_name, last_name,
     * quiz javoblari) Telegram tomonidan parse_mode='HTML' bilan render
     * qilinadi. Agar user ismida `<a href="evil">` bo'lsa — clickable link
     * bo'lib ko'rinadi (XSS/social engineering). Shuning uchun HTML special
     * characters'ni escape qilamiz.
     *
     * Telegram HTML escape: faqat `< > &` kerak.
     */
    protected function processVariables(array $content): array
    {
        $esc = fn ($v) => str_replace(
            ['&', '<', '>'],
            ['&amp;', '&lt;', '&gt;'],
            (string) $v
        );

        $replacements = [
            '{first_name}' => $esc($this->user->first_name ?? ''),
            '{last_name}' => $esc($this->user->last_name ?? ''),
            '{full_name}' => $esc($this->user->getFullName()),
            '{username}' => $this->user->username ? '@'.$esc($this->user->username) : '',
            '{phone}' => $esc($this->user->phone ?? ''),
            '{bot_name}' => $esc($this->bot->bot_first_name ?? ''),
        ];

        // Add collected data variables (user-provided quiz answers, inputs, etc.)
        foreach ($this->state->collected_data ?? [] as $key => $value) {
            if (is_string($value)) {
                $replacements['{'.$key.'}'] = $esc($value);
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

    /**
     * SSRF himoyasi — tashqariga yuborilayotgan URL private/loopback/link-local
     * IP'ga resolve qilmasligini tekshiradi.
     *
     * Bloklaydi:
     *  - loopback: 127.0.0.0/8, ::1
     *  - private: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
     *  - link-local / cloud metadata: 169.254.0.0/16 (AWS metadata 169.254.169.254)
     *  - IPv6 private: fc00::/7, fe80::/10
     *  - multicast/reserved
     */
    protected function isUrlSafeForOutbound(string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            return false;
        }
        $host = $parts['host'];

        // DNS orqali barcha IP'larga resolve — birortasi ham xavfli bo'lmasin.
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        $ips = [];
        if (is_array($records)) {
            foreach ($records as $r) {
                if (! empty($r['ip'])) {
                    $ips[] = $r['ip'];
                } elseif (! empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }
        // Agar host allaqachon IP bo'lsa — uning o'zini ishlatamiz.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        }
        if (empty($ips)) {
            // DNS fail — ruxsat bermaymiz (xavfsizlik default).
            return false;
        }

        foreach ($ips as $ip) {
            // filter_var FILTER_VALIDATE_IP with no_priv/no_res flags — barcha private/reserved/loopback'ni bloklaydi.
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }
        return true;
    }

    // Notification methods

    /**
     * Notify operators via the system/support bot about a funnel event.
     *
     * action_config maydonlari:
     *   - operator_ids: int[] (optional) — biznesning qaysi user'lariga yuboriladi.
     *                   Bo'sh bo'lsa hamma `telegram_chat_id` o'rnatilgan user'larga.
     *   - message: string (optional) — operator ko'radigan xabar matni.
     *              `{variables}` placeholder'lari processVariables orqali ishlaydi.
     *   - include_collected_data: bool (default true) — collected_data'ni xabarga qo'shadi.
     */
    protected function sendNotification(TelegramFunnelStep $step): void
    {
        $config = $step->action_config ?? [];
        $message = $config['message'] ?? '🔔 Funnel event';
        $includeData = (bool) ($config['include_collected_data'] ?? true);

        // Placeholder'larni to'ldirib olamiz (first_name, collected inputs, va h.k.)
        $processed = $this->processVariables(['text' => $message]);
        $body = $processed['text'] ?? $message;

        if ($includeData && ! empty($this->state->collected_data)) {
            $lines = [];
            foreach ($this->state->collected_data as $k => $v) {
                // Internal `__` prefix'li kalitlar (retry counter va h.k.) operatorga ko'rsatilmasligi kerak.
                if (is_string($k) && str_starts_with($k, '__')) {
                    continue;
                }
                if (is_scalar($v)) {
                    $lines[] = htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
                }
            }
            if ($lines) {
                $body .= "\n\n" . implode("\n", $lines);
            }
        }

        // Adresatlarni topamiz — aniq operator_ids yoki biznesning barcha aktiv user'lari.
        $operatorIds = $config['operator_ids'] ?? [];
        $business = Business::find($this->bot->business_id);
        if (! $business) {
            Log::warning('Funnel notification: business not found', ['bot_id' => $this->bot->id]);
            return;
        }

        $query = $business->users()->whereNotNull('telegram_chat_id');
        if (! empty($operatorIds) && is_array($operatorIds)) {
            $query->whereIn('users.id', $operatorIds);
        }
        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            Log::info('Funnel notification: no recipients with telegram_chat_id', [
                'bot_id' => $this->bot->id,
                'business_id' => $business->id,
            ]);
            return;
        }

        foreach ($recipients as $user) {
            $this->api->sendMessage((int) $user->telegram_chat_id, $body);
        }

        Log::info('Funnel notification delivered', [
            'step_id' => $step->id,
            'recipients' => $recipients->count(),
            'business_id' => $business->id,
        ]);
    }

    /**
     * Fire outbound webhook (HTTP POST) with optional HMAC signature.
     *
     * action_config maydonlari:
     *   - url: string (required) — endpoint
     *   - method: string ('POST' default, 'PUT' yoki 'PATCH')
     *   - headers: array (optional) — qo'shimcha header'lar
     *   - secret: string (optional) — HMAC-SHA256 imzo uchun.
     *             `X-BiznesPilot-Signature: sha256=<hex>` header qo'shiladi.
     *   - include_user: bool (default true) — user payload'da bo'ladi
     *
     * Xavfsizlik: 5s timeout, TLS verify enabled, faqat http(s) sxemalari.
     */
    protected function sendWebhook(TelegramFunnelStep $step): void
    {
        $config = $step->action_config ?? [];
        $url = $config['url'] ?? null;

        if (! $url || ! is_string($url) || ! preg_match('#^https?://#i', $url)) {
            Log::warning('Funnel webhook: invalid or missing url', [
                'step_id' => $step->id,
                'url' => $url,
            ]);
            return;
        }

        // SSRF himoyasi — xost DNS orqali private/loopback/link-local IP'ga
        // resolve qilmasligi kerak. Bu AWS metadata (169.254.169.254),
        // internal API'lar (10.x / 192.168.x / 172.16-31.x), va localhost'ni
        // bloklaydi. Webhook payload'i collected_data + user PII'ni olib
        // tashqariga chiqaradi, shuning uchun DNS rebinding hujumiga
        // qarshi ham bir marta resolve qilib tekshiramiz.
        if (! $this->isUrlSafeForOutbound($url)) {
            Log::warning('Funnel webhook: unsafe URL blocked', [
                'step_id' => $step->id,
                'url' => $url,
            ]);
            return;
        }

        $method = strtoupper($config['method'] ?? 'POST');
        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $method = 'POST';
        }

        // Engine'ning ichki `__retry:...` counter'larini va boshqa `__` prefix'li
        // maydonlarni tashqariga chiqarmaymiz — bular internal state.
        $cleanCollected = [];
        foreach (($this->state->collected_data ?? []) as $k => $v) {
            if (is_string($k) && str_starts_with($k, '__')) {
                continue;
            }
            $cleanCollected[$k] = $v;
        }

        $payload = [
            'event' => 'funnel.step',
            'timestamp' => now()->toIso8601String(),
            'bot_id' => $this->bot->id,
            'business_id' => $this->bot->business_id,
            'funnel_id' => $this->state->current_funnel_id,
            'step_id' => $step->id,
            'step_name' => $step->name,
            'collected_data' => $cleanCollected,
        ];

        if ((bool) ($config['include_user'] ?? true)) {
            $payload['user'] = [
                'telegram_id' => $this->user->telegram_id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'username' => $this->user->username,
                'phone' => $this->user->phone,
                'language_code' => $this->user->language_code,
            ];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'BiznesPilot-Funnel/1.0',
        ];
        if (! empty($config['headers']) && is_array($config['headers'])) {
            foreach ($config['headers'] as $name => $value) {
                if (is_string($name) && is_scalar($value)) {
                    $headers[$name] = (string) $value;
                }
            }
        }

        // HMAC-SHA256 imzo (ixtiyoriy). Controller saqlashda `enc:v1:<cipher>` prefix
        // bilan encrypt qilgan — shu yerda decrypt qilamiz. Legacy plain secret ham qo'llab-quvvatlanadi.
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (! empty($config['secret']) && is_string($config['secret'])) {
            $secret = $config['secret'];
            if (str_starts_with($secret, 'enc:v1:')) {
                try {
                    $secret = \Illuminate\Support\Facades\Crypt::decryptString(substr($secret, 7));
                } catch (\Throwable $e) {
                    Log::warning('Funnel webhook: secret decrypt failed', ['step_id' => $step->id]);
                    $secret = null;
                }
            }
            if ($secret) {
                $signature = hash_hmac('sha256', $body, $secret);
                $headers['X-BiznesPilot-Signature'] = 'sha256=' . $signature;
            }
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withOptions([
                    'verify' => true,
                    'connect_timeout' => 5,
                    'timeout' => 10,
                    'http_errors' => false,
                    // Redirect'ni o'chiramiz — aks holda server public URL'dan
                    // 127.0.0.1 ga 302 qilib SSRF filtrlarini aylanib o'tishi mumkin.
                    'allow_redirects' => false,
                ])
                ->withBody($body, 'application/json')
                ->send($method, $url);

            Log::info('Funnel webhook sent', [
                'step_id' => $step->id,
                'url' => $url,
                'status' => $response->status(),
            ]);

            if ($response->status() >= 400) {
                Log::warning('Funnel webhook non-2xx response', [
                    'step_id' => $step->id,
                    'url' => $url,
                    'status' => $response->status(),
                    'body_preview' => substr((string) $response->body(), 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Funnel webhook failed', [
                'step_id' => $step->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
