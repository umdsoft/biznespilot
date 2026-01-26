<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramAutomation;
use App\Models\InstagramAutomationTrigger;
use App\Models\InstagramConversation;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

/**
 * ChatbotIntentService - Mijoz niyatini aniqlash "Miya"si
 *
 * Bu servis kelgan xabarni tahlil qilib, quyidagilarni aniqlaydi:
 * 1. Aniq moslik (Exact Match): "start", "boshlash", "stop"
 * 2. Kalit so'z (Keyword): instagram_automation_triggers dan
 * 3. Tugma bosilishi (Payload): Quick Reply payload
 * 4. Kontekst (Context): Joriy suhbat holati
 *
 * Shuningdek, maxsus intentlar (shikoyat, muammo, buyurtma) uchun
 * avtomatik Lead yaratadi va CRM ga yuboradi.
 *
 * @example
 * $intent = $intentService->detect($message, $account, $conversation);
 * $result = $intentService->handleIntent($intent, $conversation, $message);
 */
class ChatbotIntentService
{
    /**
     * Lead yaratish kerak bo'lgan intentlar
     */
    protected const LEAD_CREATING_INTENTS = [
        'complaint',      // Shikoyat
        'issue',          // Muammo
        'problem',        // Problem
        'human_handoff',  // Operator so'rovi
        'order_intent',   // Buyurtma
        'price_inquiry',  // Narx so'rovi (potentsial lead)
    ];

    /**
     * Operatorga o'tkazish kerak bo'lgan intentlar (yuqori prioritet)
     */
    protected const HANDOFF_INTENTS = [
        'complaint',
        'issue',
        'problem',
        'human_handoff',
    ];

    /**
     * Tizim so'zlari - Maxsus komandalar
     */
    protected const SYSTEM_COMMANDS = [
        // Flow boshlash
        'start_flow' => ['start', 'boshlash', 'boshla', 'начать', 'начало', 'привет', 'hi', 'hello', 'salom', 'assalom'],
        // Flow to'xtatish
        'stop_flow' => ['stop', 'to\'xtat', 'toxtat', 'bekor', 'cancel', 'отмена', 'стоп', 'chiqish', 'exit'],
        // Inson bilan bog'lanish
        'human_handoff' => ['operator', 'odam', 'inson', 'manager', 'помощь', 'help', 'yordam', 'admin', 'menejer'],
        // Orqaga qaytish
        'go_back' => ['orqaga', 'back', 'назад', 'ortga', 'qaytish'],
        // Asosiy menyu
        'main_menu' => ['menu', 'menyu', 'меню', 'bosh menyu', 'главное меню'],
    ];

    /**
     * Shikoyat/Muammo pattern'lari
     */
    protected const COMPLAINT_PATTERNS = [
        'shikoyat', 'жалоба', 'complaint',
        'muammo', 'проблема', 'problem', 'issue',
        'ishlamayapti', 'не работает', 'not working', 'buzilgan', 'broken',
        'yomon', 'плохо', 'bad', 'terrible', 'worst',
        'norozi', 'недоволен', 'unsatisfied', 'unhappy',
        'qaytarish', 'qaytaring', 'pulni', 'возврат', 'refund', 'return',
        'aldash', 'обман', 'scam', 'fraud',
        'kechikish', 'задержка', 'delay', 'late',
        'buzuq', 'сломан', 'defect', 'damaged',
    ];

    protected TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Detect user intent from message
     *
     * @param string $message Foydalanuvchi xabari
     * @param InstagramAccount $account Instagram akkaunt
     * @param InstagramConversation|null $conversation Joriy suhbat
     * @param array $metadata Qo'shimcha ma'lumotlar (payload, quick_reply, etc.)
     * @return array Intent natijasi
     */
    public function detect(
        string $message,
        InstagramAccount $account,
        ?InstagramConversation $conversation = null,
        array $metadata = []
    ): array {
        $message = trim($message);
        $messageLower = mb_strtolower($message);

        Log::debug('ChatbotIntentService: Detecting intent', [
            'message' => $message,
            'account_id' => $account->id,
            'conversation_id' => $conversation?->id,
            'has_active_automation' => $conversation?->current_automation_id !== null,
        ]);

        // 1. PAYLOAD TEKSHIRISH - Quick Reply tugmasidan kelgan bo'lsa
        if (! empty($metadata['payload'])) {
            return $this->detectPayloadIntent($metadata['payload'], $account, $conversation);
        }

        // 2. TIZIM KOMANDALARI - start, stop, help, etc.
        $systemIntent = $this->detectSystemCommand($messageLower);
        if ($systemIntent) {
            return $systemIntent;
        }

        // 3. SHIKOYAT/MUAMMO TEKSHIRISH - Yuqori prioritet
        $complaintIntent = $this->detectComplaintIntent($messageLower);
        if ($complaintIntent) {
            return $complaintIntent;
        }

        // 4. JORIY AUTOMATION KONTEKSTI - Agar suhbatda aktiv flow bo'lsa
        if ($conversation && $conversation->current_automation_id) {
            $contextIntent = $this->detectContextualIntent($message, $conversation);
            if ($contextIntent) {
                return $contextIntent;
            }
        }

        // 5. KEYWORD MATCHING - Avtomatizatsiya triggerlaridan qidirish
        $keywordIntent = $this->detectKeywordIntent($messageLower, $account);
        if ($keywordIntent) {
            return $keywordIntent;
        }

        // 6. FLOW-BASED KEYWORD - Flow nodes ichidan qidirish
        $flowIntent = $this->detectFlowKeywordIntent($messageLower, $account);
        if ($flowIntent) {
            return $flowIntent;
        }

        // 7. UMUMIY INTENT ANIQLASH - AI yordamida yoki pattern matching
        $generalIntent = $this->detectGeneralIntent($messageLower);
        if ($generalIntent) {
            return $generalIntent;
        }

        // Hech narsa topilmadi - unknown intent
        return [
            'type' => 'unknown',
            'value' => null,
            'automation' => null,
            'confidence' => 0.0,
            'message' => $message,
            'should_fallback' => true,
        ];
    }

    /**
     * Handle detected intent - Lead yaratish va javob qaytarish
     *
     * Bu metod aniqlangan intentni qayta ishlaydi:
     * - Shikoyat/muammo bo'lsa: Lead yaratib, operatorga xabar beradi
     * - Buyurtma bo'lsa: Lead yaratib, flow davom etadi
     * - Boshqa holatda: Oddiy flow davom etadi
     *
     * @param array $intent Aniqlangan intent
     * @param InstagramConversation $conversation Suhbat
     * @param string $originalMessage Asl xabar matni
     * @return array{
     *   should_continue_flow: bool,
     *   lead_created: bool,
     *   lead: ?Lead,
     *   auto_reply: ?string,
     *   handoff_required: bool
     * }
     */
    public function handleIntent(
        array $intent,
        InstagramConversation $conversation,
        string $originalMessage
    ): array {
        $intentValue = $intent['value'] ?? $intent['type'] ?? 'unknown';
        $intentType = $intent['type'] ?? 'unknown';

        Log::info('ChatbotIntentService: Handling intent', [
            'intent_type' => $intentType,
            'intent_value' => $intentValue,
            'conversation_id' => $conversation->id,
        ]);

        $result = [
            'should_continue_flow' => true,
            'lead_created' => false,
            'lead' => null,
            'auto_reply' => null,
            'handoff_required' => false,
        ];

        // 1. Lead yaratish kerak bo'lgan intentlarni tekshirish
        if ($this->shouldCreateLead($intent)) {
            $lead = $this->createLeadFromIntent($intent, $conversation, $originalMessage);

            if ($lead) {
                $result['lead_created'] = true;
                $result['lead'] = $lead;

                Log::info('ChatbotIntentService: Lead created from intent', [
                    'lead_id' => $lead->id,
                    'intent_value' => $intentValue,
                    'conversation_id' => $conversation->id,
                ]);
            }
        }

        // 2. Operatorga o'tkazish kerak bo'lgan intentlar
        if ($this->requiresHandoff($intent)) {
            $result['handoff_required'] = true;
            $result['should_continue_flow'] = false;
            $result['auto_reply'] = $this->getHandoffReplyMessage($intentValue);

            // Conversation statusini yangilash
            $conversation->update([
                'status' => 'human_requested',
                'human_requested_at' => now(),
                'human_request_reason' => $intentValue,
            ]);

            Log::info('ChatbotIntentService: Handoff required', [
                'intent_value' => $intentValue,
                'conversation_id' => $conversation->id,
            ]);
        }

        // 3. Buyurtma intenti - Lead yaratildi, lekin flow davom etadi
        if ($intentValue === 'order_intent' && $result['lead_created']) {
            $result['auto_reply'] = $this->getOrderAcknowledgeMessage();
            // Flow davom etadi
        }

        // 4. Narx so'rovi - Lead yaratildi, flow davom etadi
        if ($intentValue === 'price_inquiry' && $result['lead_created']) {
            // Auto reply yo'q, flow o'zi javob beradi
        }

        return $result;
    }

    /**
     * Lead yaratish kerakmi?
     */
    protected function shouldCreateLead(array $intent): bool
    {
        $intentValue = $intent['value'] ?? $intent['type'] ?? null;

        if (! $intentValue) {
            return false;
        }

        // System command - human_handoff
        if ($intent['type'] === 'system' && $intent['value'] === 'human_handoff') {
            return true;
        }

        // General intent
        if ($intent['type'] === 'general' && in_array($intentValue, self::LEAD_CREATING_INTENTS)) {
            return true;
        }

        // Complaint intent
        if ($intent['type'] === 'complaint') {
            return true;
        }

        return false;
    }

    /**
     * Handoff kerakmi?
     */
    protected function requiresHandoff(array $intent): bool
    {
        $intentValue = $intent['value'] ?? $intent['type'] ?? null;

        // System command - human_handoff
        if ($intent['type'] === 'system' && $intent['value'] === 'human_handoff') {
            return true;
        }

        // Complaint/Issue intentlar
        if ($intent['type'] === 'complaint') {
            return true;
        }

        // General intent - shikoyat turlari
        if ($intent['type'] === 'general' && in_array($intentValue, self::HANDOFF_INTENTS)) {
            return true;
        }

        return false;
    }

    /**
     * Intentdan Lead yaratish
     */
    protected function createLeadFromIntent(
        array $intent,
        InstagramConversation $conversation,
        string $originalMessage
    ): ?Lead {
        $intentValue = $intent['value'] ?? $intent['type'] ?? 'unknown';

        // Agar bu conversation uchun allaqachon lead yaratilgan bo'lsa
        $existingLead = $this->ticketService->getLeadForConversation($conversation);
        if ($existingLead) {
            // Mavjud leadni yangilash
            $this->ticketService->appendChatbotData($existingLead, [
                'latest_intent' => $intentValue,
                'latest_message' => $originalMessage,
                'updated_at' => now()->toISOString(),
            ]);

            // Agar yangi intent aniqroq bo'lsa, yangilash
            if (empty($existingLead->chatbot_detected_intent) || $existingLead->chatbot_detected_intent === 'unknown') {
                $this->ticketService->updateIntent($existingLead, $intentValue);
            }

            return $existingLead;
        }

        // Source type aniqlash
        $sourceType = $this->determineSourceType($conversation);

        // Yangi Lead yaratish
        return $this->ticketService->createFromChatbot($conversation, [
            'intent' => $intentValue,
            'source_type' => $sourceType,
            'first_message' => $originalMessage,
            'collected_data' => [
                'detected_intent' => $intentValue,
                'intent_confidence' => $intent['confidence'] ?? 0,
                'matched_keyword' => $intent['matched_keyword'] ?? null,
                'category' => $intent['category'] ?? null,
            ],
        ]);
    }

    /**
     * Source type aniqlash
     */
    protected function determineSourceType(InstagramConversation $conversation): string
    {
        // Conversation type dan aniqlash
        $conversationType = $conversation->conversation_type ?? 'dm';

        return match ($conversationType) {
            'comment', 'comment_reply' => 'comment',
            'story_reply' => 'story_reply',
            'story_mention' => 'story_mention',
            default => 'dm',
        };
    }

    /**
     * Handoff uchun javob xabari
     */
    protected function getHandoffReplyMessage(string $intentValue): string
    {
        $messages = [
            'complaint' => "Sizning shikoyatingiz qabul qilindi. Operatorlarimiz tez orada siz bilan bog'lanishadi. Uzr so'raymiz! 🙏",
            'issue' => "Muammo haqida xabar uchun rahmat. Mutaxassislarimiz tez orada yordam berishadi. 🔧",
            'problem' => "Muammo haqida ma'lumot oldik. Operator siz bilan tez orada bog'lanadi. 📞",
            'human_handoff' => "So'rovingiz qabul qilindi. Operatorlarimiz tez orada javob berishadi. Iltimos, biroz kuting. ⏳",
        ];

        return $messages[$intentValue] ?? "Sizning murojaatingiz operatorga yuborildi. Tez orada aloqaga chiqamiz. ✅";
    }

    /**
     * Buyurtma tasdiqlash xabari
     */
    protected function getOrderAcknowledgeMessage(): string
    {
        return "Buyurtma qilmoqchi ekanligingizni tushundim! Quyidagi savollarga javob bering: 🛒";
    }

    /**
     * Detect complaint/issue intent
     */
    protected function detectComplaintIntent(string $messageLower): ?array
    {
        foreach (self::COMPLAINT_PATTERNS as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                // Qaysi kategoriya ekanligini aniqlash
                $category = $this->categorizeComplaint($pattern);

                return [
                    'type' => 'complaint',
                    'value' => $category,
                    'automation' => null,
                    'confidence' => 0.85,
                    'matched_keyword' => $pattern,
                    'category' => 'support',
                    'requires_handoff' => true,
                ];
            }
        }

        return null;
    }

    /**
     * Shikoyat kategoriyasini aniqlash
     */
    protected function categorizeComplaint(string $pattern): string
    {
        $categories = [
            'complaint' => ['shikoyat', 'жалоба', 'complaint'],
            'issue' => ['muammo', 'проблема', 'problem', 'issue', 'ishlamayapti', 'не работает', 'not working'],
            'refund' => ['qaytarish', 'возврат', 'refund', 'return'],
            'defect' => ['buzilgan', 'broken', 'buzuq', 'сломан', 'defect', 'damaged'],
            'delay' => ['kechikish', 'задержка', 'delay', 'late'],
            'fraud' => ['aldash', 'обман', 'scam', 'fraud'],
            'dissatisfaction' => ['yomon', 'плохо', 'bad', 'terrible', 'worst', 'norozi', 'недоволен', 'unsatisfied', 'unhappy'],
        ];

        foreach ($categories as $category => $patterns) {
            if (in_array($pattern, $patterns)) {
                return $category;
            }
        }

        return 'complaint'; // Default
    }

    /**
     * Detect intent from Quick Reply payload
     */
    protected function detectPayloadIntent(
        string $payload,
        InstagramAccount $account,
        ?InstagramConversation $conversation
    ): array {
        Log::debug('ChatbotIntentService: Payload detected', ['payload' => $payload]);

        // Payload formati: ACTION:DATA yoki FLOW:node_id yoki AUTOMATION:automation_id
        $parts = explode(':', $payload, 2);
        $action = $parts[0] ?? '';
        $data = $parts[1] ?? '';

        // Flow node ga o'tish
        if ($action === 'FLOW' || $action === 'NODE') {
            return [
                'type' => 'flow_navigation',
                'value' => $data,
                'automation' => $conversation?->currentAutomation,
                'confidence' => 1.0,
                'payload' => $payload,
                'next_node_id' => $data,
            ];
        }

        // Avtomatizatsiya boshlash
        if ($action === 'AUTOMATION' || $action === 'START') {
            $automation = InstagramAutomation::find($data);
            if ($automation && $automation->account_id === $account->id) {
                return [
                    'type' => 'start_automation',
                    'value' => $automation->name,
                    'automation' => $automation,
                    'confidence' => 1.0,
                    'payload' => $payload,
                ];
            }
        }

        // Maxsus action
        if ($action === 'ACTION') {
            return [
                'type' => 'custom_action',
                'value' => $data,
                'automation' => $conversation?->currentAutomation,
                'confidence' => 1.0,
                'payload' => $payload,
                'action_name' => $data,
            ];
        }

        // Oddiy payload - intent sifatida qabul qilish
        return [
            'type' => 'payload',
            'value' => $payload,
            'automation' => $conversation?->currentAutomation,
            'confidence' => 0.9,
            'payload' => $payload,
        ];
    }

    /**
     * Detect system commands (start, stop, help, etc.)
     */
    protected function detectSystemCommand(string $messageLower): ?array
    {
        foreach (self::SYSTEM_COMMANDS as $intentType => $keywords) {
            foreach ($keywords as $keyword) {
                // Aniq moslik tekshirish
                if ($messageLower === $keyword) {
                    return [
                        'type' => 'system',
                        'value' => $intentType,
                        'automation' => null,
                        'confidence' => 1.0,
                        'matched_keyword' => $keyword,
                    ];
                }

                // Xabar boshida bo'lsa ham qabul qilish (masalan: "start kurs")
                if (str_starts_with($messageLower, $keyword . ' ')) {
                    return [
                        'type' => 'system',
                        'value' => $intentType,
                        'automation' => null,
                        'confidence' => 0.9,
                        'matched_keyword' => $keyword,
                        'additional_text' => trim(substr($messageLower, strlen($keyword))),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Detect intent based on current conversation context
     */
    protected function detectContextualIntent(string $message, InstagramConversation $conversation): ?array
    {
        // Agar suhbat "waiting" holatida bo'lsa - input kutilmoqda
        if ($conversation->status === 'waiting') {
            return [
                'type' => 'user_input',
                'value' => $message,
                'automation' => $conversation->currentAutomation,
                'confidence' => 0.95,
                'context' => 'waiting_for_input',
                'current_step' => $conversation->current_step,
            ];
        }

        // Agar aktiv flow bo'lsa va keyingi node tugma tanlashni kutayotgan bo'lsa
        // Bu holda foydalanuvchi matn yozgan - lekin bizga tugma kerak edi
        // Ushbu holatda xabarni ignore qilish yoki takror so'rash mumkin
        if ($conversation->current_automation_id) {
            // Collected data da oxirgi savol bormi tekshiramiz
            $collectedData = $conversation->collected_data ?? [];
            $lastQuestion = $collectedData['_last_question'] ?? null;

            if ($lastQuestion) {
                return [
                    'type' => 'collected_response',
                    'value' => $message,
                    'automation' => $conversation->currentAutomation,
                    'confidence' => 0.9,
                    'question_field' => $lastQuestion,
                ];
            }
        }

        return null;
    }

    /**
     * Detect keyword-based intent from automation triggers
     */
    protected function detectKeywordIntent(string $messageLower, InstagramAccount $account): ?array
    {
        // Aktiv avtomatizatsiyalarni olish
        $automations = InstagramAutomation::where('account_id', $account->id)
            ->where('status', 'active')
            ->whereHas('triggers', fn ($q) => $q->where('trigger_type', 'keyword_dm'))
            ->with('triggers')
            ->get();

        foreach ($automations as $automation) {
            foreach ($automation->triggers as $trigger) {
                if ($trigger->trigger_type !== 'keyword_dm') {
                    continue;
                }

                $matchResult = $this->matchKeywords($messageLower, $trigger);

                if ($matchResult['matched']) {
                    return [
                        'type' => 'keyword',
                        'value' => $matchResult['keyword'],
                        'automation' => $automation,
                        'confidence' => $matchResult['confidence'],
                        'trigger' => $trigger,
                        'matched_keyword' => $matchResult['keyword'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Detect keyword from flow-based automations
     */
    protected function detectFlowKeywordIntent(string $messageLower, InstagramAccount $account): ?array
    {
        // Flow-based avtomatizatsiyalarni olish
        $flowAutomations = InstagramAutomation::where('account_id', $account->id)
            ->where('status', 'active')
            ->where('is_flow_based', true)
            ->whereNotNull('flow_data')
            ->get();

        foreach ($flowAutomations as $automation) {
            $flowData = $automation->flow_data;
            $nodes = $flowData['nodes'] ?? [];

            foreach ($nodes as $node) {
                $nodeType = $node['node_type'] ?? '';

                // Faqat trigger turidagi nodelarni tekshirish
                if (! str_starts_with($nodeType, 'trigger_')) {
                    continue;
                }

                $nodeData = $node['data'] ?? [];
                $keywords = $nodeData['keywords'] ?? '';

                // Keywords ni array ga aylantirish
                $keywordList = $this->parseKeywords($keywords);

                // Agar "__all__" yoki bo'sh bo'lsa - barcha xabarlarga javob
                if ($this->isMatchAllKeyword($keywordList)) {
                    return [
                        'type' => 'flow_keyword',
                        'value' => '__all__',
                        'automation' => $automation,
                        'confidence' => 0.5,
                        'trigger_node' => $node,
                        'match_type' => 'all',
                    ];
                }

                // Keyword mosligini tekshirish
                foreach ($keywordList as $keyword) {
                    $keyword = trim(mb_strtolower($keyword));
                    if (empty($keyword)) {
                        continue;
                    }

                    // Aniq moslik
                    if ($messageLower === $keyword) {
                        return [
                            'type' => 'flow_keyword',
                            'value' => $keyword,
                            'automation' => $automation,
                            'confidence' => 1.0,
                            'trigger_node' => $node,
                            'match_type' => 'exact',
                        ];
                    }

                    // Qisman moslik (xabar ichida keyword bor)
                    if (mb_stripos($messageLower, $keyword) !== false) {
                        return [
                            'type' => 'flow_keyword',
                            'value' => $keyword,
                            'automation' => $automation,
                            'confidence' => 0.8,
                            'trigger_node' => $node,
                            'match_type' => 'partial',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Detect general intent using pattern matching
     */
    protected function detectGeneralIntent(string $messageLower): ?array
    {
        // Narx/baho so'rash
        $pricePatterns = ['narx', 'narhi', 'necha', 'qancha', 'цена', 'price', 'cost', 'baho', 'pul'];
        foreach ($pricePatterns as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                return [
                    'type' => 'general',
                    'value' => 'price_inquiry',
                    'automation' => null,
                    'confidence' => 0.7,
                    'category' => 'sales',
                    'matched_keyword' => $pattern,
                ];
            }
        }

        // Buyurtma berish
        $orderPatterns = ['buyurtma', 'zakaz', 'order', 'olmoqchiman', 'sotib olish', 'купить', 'заказ'];
        foreach ($orderPatterns as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                return [
                    'type' => 'general',
                    'value' => 'order_intent',
                    'automation' => null,
                    'confidence' => 0.7,
                    'category' => 'sales',
                    'matched_keyword' => $pattern,
                ];
            }
        }

        // Ma'lumot so'rash
        $infoPatterns = ['ma\'lumot', 'info', 'information', 'qanday', 'nima', 'what', 'как', 'что'];
        foreach ($infoPatterns as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                return [
                    'type' => 'general',
                    'value' => 'info_request',
                    'automation' => null,
                    'confidence' => 0.6,
                    'category' => 'support',
                    'matched_keyword' => $pattern,
                ];
            }
        }

        // Yetkazib berish haqida
        $deliveryPatterns = ['yetkazib', 'доставка', 'delivery', 'qachon keladi', 'когда доставят', 'tracking', 'kuzatish'];
        foreach ($deliveryPatterns as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                return [
                    'type' => 'general',
                    'value' => 'delivery_status',
                    'automation' => null,
                    'confidence' => 0.7,
                    'category' => 'support',
                    'matched_keyword' => $pattern,
                ];
            }
        }

        // Salomlashish
        $greetingPatterns = ['assalomu', 'salom', 'привет', 'здравствуйте', 'добрый', 'good morning', 'good day'];
        foreach ($greetingPatterns as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                return [
                    'type' => 'general',
                    'value' => 'greeting',
                    'automation' => null,
                    'confidence' => 0.8,
                    'category' => 'greeting',
                    'matched_keyword' => $pattern,
                ];
            }
        }

        // Rahmat
        $thanksPatterns = ['rahmat', 'спасибо', 'thanks', 'thank you', 'tashakkur', 'благодарю'];
        foreach ($thanksPatterns as $pattern) {
            if (mb_stripos($messageLower, $pattern) !== false) {
                return [
                    'type' => 'general',
                    'value' => 'thanks',
                    'automation' => null,
                    'confidence' => 0.8,
                    'category' => 'closing',
                    'matched_keyword' => $pattern,
                ];
            }
        }

        return null;
    }

    /**
     * Match keywords from trigger
     */
    protected function matchKeywords(string $messageLower, InstagramAutomationTrigger $trigger): array
    {
        $keywords = $trigger->keywords ?? [];

        if (empty($keywords)) {
            return ['matched' => false, 'keyword' => null, 'confidence' => 0];
        }

        // Keywords array bo'lmasa, parse qilish
        if (is_string($keywords)) {
            $keywords = $this->parseKeywords($keywords);
        }

        $caseSensitive = $trigger->case_sensitive ?? false;
        $exactMatch = $trigger->exact_match ?? false;

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) {
                continue;
            }

            $keywordToMatch = $caseSensitive ? $keyword : mb_strtolower($keyword);
            $messageToMatch = $caseSensitive ? $messageLower : $messageLower; // Already lowercase

            if ($exactMatch) {
                // Aniq moslik
                if ($messageToMatch === $keywordToMatch) {
                    return ['matched' => true, 'keyword' => $keyword, 'confidence' => 1.0];
                }
            } else {
                // Qisman moslik
                if (mb_stripos($messageToMatch, $keywordToMatch) !== false) {
                    $confidence = strlen($keywordToMatch) / strlen($messageToMatch);
                    $confidence = min(0.95, max(0.6, $confidence));

                    return ['matched' => true, 'keyword' => $keyword, 'confidence' => $confidence];
                }
            }
        }

        return ['matched' => false, 'keyword' => null, 'confidence' => 0];
    }

    /**
     * Parse keywords string to array
     */
    protected function parseKeywords($keywords): array
    {
        if (is_array($keywords)) {
            return $keywords;
        }

        if (! is_string($keywords) || empty($keywords)) {
            return [];
        }

        // Vergul yoki probel bilan ajratilgan
        return preg_split('/[\s,]+/', $keywords, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Check if keywords mean "match all"
     */
    protected function isMatchAllKeyword(array $keywordList): bool
    {
        if (empty($keywordList)) {
            return true;
        }

        if (count($keywordList) === 1) {
            $keyword = $keywordList[0];

            return $keyword === '__all__' || $keyword === '+' || $keyword === '*';
        }

        return false;
    }

    /**
     * Get intent type label (for UI)
     */
    public static function getIntentTypeLabel(string $type): string
    {
        return match ($type) {
            'system' => 'Tizim komandasi',
            'keyword' => 'Kalit so\'z',
            'flow_keyword' => 'Flow trigger',
            'payload' => 'Tugma bosildi',
            'flow_navigation' => 'Flow navigatsiya',
            'start_automation' => 'Avtomatizatsiya boshlash',
            'custom_action' => 'Maxsus amal',
            'user_input' => 'Foydalanuvchi javob',
            'collected_response' => 'To\'plangan ma\'lumot',
            'general' => 'Umumiy niyat',
            'complaint' => 'Shikoyat/Muammo',
            'unknown' => 'Noma\'lum',
            default => $type,
        };
    }

    /**
     * Get intent value label (for UI)
     */
    public static function getIntentValueLabel(string $value): string
    {
        return match ($value) {
            'start_flow' => 'Flow boshlash',
            'stop_flow' => 'Flow to\'xtatish',
            'human_handoff' => 'Operator so\'rovi',
            'go_back' => 'Orqaga qaytish',
            'main_menu' => 'Asosiy menyu',
            'price_inquiry' => 'Narx so\'rovi',
            'order_intent' => 'Buyurtma',
            'info_request' => 'Ma\'lumot so\'rovi',
            'delivery_status' => 'Yetkazish holati',
            'greeting' => 'Salomlashish',
            'thanks' => 'Rahmat',
            'complaint' => 'Shikoyat',
            'issue' => 'Muammo',
            'problem' => 'Muammo',
            'refund' => 'Qaytarish so\'rovi',
            'defect' => 'Nuqson/buzilish',
            'delay' => 'Kechikish',
            'fraud' => 'Aldash/firibgarlik',
            'dissatisfaction' => 'Norozilik',
            default => $value,
        };
    }

    /**
     * Check if intent should trigger a flow
     */
    public function shouldTriggerFlow(array $intent): bool
    {
        // Shikoyat/Handoff intentlari flow ni trigger qilmasligi kerak
        if ($this->requiresHandoff($intent)) {
            return false;
        }

        $triggerTypes = [
            'keyword',
            'flow_keyword',
            'start_automation',
            'payload',
            'flow_navigation',
        ];

        // System komandalar ham flowni trigger qilishi mumkin
        if ($intent['type'] === 'system' && $intent['value'] === 'start_flow') {
            return true;
        }

        return in_array($intent['type'], $triggerTypes) && ! empty($intent['automation']);
    }

    /**
     * Check if intent needs human handoff
     */
    public function needsHumanHandoff(array $intent): bool
    {
        if ($this->requiresHandoff($intent)) {
            return true;
        }

        // Confidence juda past bo'lsa
        if (($intent['confidence'] ?? 0) < 0.3 && $intent['type'] === 'unknown') {
            return true;
        }

        return false;
    }

    /**
     * Get all lead-creating intent types
     */
    public static function getLeadCreatingIntents(): array
    {
        return self::LEAD_CREATING_INTENTS;
    }

    /**
     * Get all handoff-requiring intent types
     */
    public static function getHandoffIntents(): array
    {
        return self::HANDOFF_INTENTS;
    }
}
