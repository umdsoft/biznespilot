<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TicketService - Lead/Ticket Yaratish va Boshqarish Xizmati
 *
 * Bu servis chatbotdan kelgan murojaatlarni Lead (CRM) sifatida saqlaydi.
 *
 * Flow:
 * 1. Chatbot intent ni aniqlaydi (order, complaint, consultation)
 * 2. TicketService::createFromChatbot() chaqiriladi
 * 3. Lead yaratiladi va CRM da ko'rinadi
 * 4. Operator lead ustida ishlashni boshlaydi
 *
 * @see ChatbotIntentService
 * @see SocialChatbotService
 */
class TicketService
{
    /**
     * Chatbot manba kodlari
     */
    protected const SOURCE_CODES = [
        'dm' => 'instagram_chatbot_auto',
        'comment' => 'instagram_comment',
        'story_reply' => 'instagram_story_reply',
        'story_mention' => 'instagram_story_mention',
        'handoff' => 'instagram_chatbot_handoff',
    ];

    /**
     * Intent ga mos pipeline stage mapping
     */
    protected const INTENT_STAGE_MAP = [
        'order' => 'new',
        'complaint' => 'new',
        'consultation' => 'new',
        'price_inquiry' => 'new',
        'human_handoff' => 'new',
        'product_info' => 'new',
        'delivery_status' => 'new',
    ];

    /**
     * Dublikat oldini olish uchun kutish vaqti (soniya)
     * Bir xil conversation uchun bu vaqt ichida yangi lead yaratilmaydi
     */
    protected const DUPLICATE_PREVENTION_SECONDS = 60;

    /**
     * Spam himoyasi - 1 daqiqada maksimal lead soni
     */
    protected const MAX_LEADS_PER_MINUTE = 3;

    /**
     * Chatbotdan Lead yaratish
     *
     * Dublikat himoyasi:
     * - Bir xil conversation uchun 60 soniya ichida yangi lead yaratilmaydi
     * - 1 daqiqada maksimum 3 ta lead yaratilishi mumkin (spam himoyasi)
     *
     * @param InstagramConversation $conversation Suhbat
     * @param array $data Lead ma'lumotlari
     * @return Lead|null Yaratilgan lead yoki mavjud lead
     *
     * @example
     * $lead = $ticketService->createFromChatbot($conversation, [
     *     'intent' => 'order',
     *     'source_type' => 'dm',
     *     'first_message' => 'Assalomu alaykum, buyurtma bermoqchiman',
     *     'collected_data' => ['product' => 'iPhone 15', 'quantity' => 1],
     *     'name' => 'Jasur',
     *     'phone' => '+998901234567',
     * ]);
     */
    public function createFromChatbot(InstagramConversation $conversation, array $data): ?Lead
    {
        // 1. Dublikat oldini olish - Cache Lock
        $lockKey = "lead_creation_lock:{$conversation->id}";

        if (Cache::has($lockKey)) {
            Log::info('TicketService: Duplicate lead prevented (throttled)', [
                'conversation_id' => $conversation->id,
                'intent' => $data['intent'] ?? null,
            ]);

            // Mavjud leadni qaytarish
            return Lead::where('instagram_conversation_id', $conversation->id)->first();
        }

        // 2. Spam himoyasi - 1 daqiqada maksimal lead soni
        if ($this->isSpamming($conversation->business_id)) {
            Log::warning('TicketService: Spam protection triggered', [
                'business_id' => $conversation->business_id,
                'conversation_id' => $conversation->id,
            ]);

            return Lead::where('instagram_conversation_id', $conversation->id)->first();
        }

        return DB::transaction(function () use ($conversation, $data, $lockKey) {
            // 3. Mavjud leadni tekshirish (shu conversation uchun)
            $existingLead = Lead::where('instagram_conversation_id', $conversation->id)->first();

            if ($existingLead) {
                Log::info('TicketService: Updating existing lead from chatbot', [
                    'lead_id' => $existingLead->id,
                    'conversation_id' => $conversation->id,
                ]);

                // Lock qo'yish (yangilash ham throttle qilinadi)
                Cache::put($lockKey, true, self::DUPLICATE_PREVENTION_SECONDS);

                return $this->updateExistingLead($existingLead, $data);
            }

            // 4. Manba ni aniqlash
            $sourceCode = $this->resolveSourceCode($data['source_type'] ?? 'dm', $data['intent'] ?? null);
            $source = LeadSource::where('code', $sourceCode)->first();

            // 5. Lead yaratish
            $lead = Lead::create([
                'business_id' => $conversation->business_id,
                'source_id' => $source?->id,

                // Mijoz ma'lumotlari
                'name' => $this->resolveName($conversation, $data),
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,

                // Instagram chatbot bog'lanish
                'instagram_conversation_id' => $conversation->id,
                'chatbot_source_type' => $data['source_type'] ?? 'dm',
                'chatbot_detected_intent' => $data['intent'] ?? null,
                'chatbot_first_message' => $data['first_message'] ?? null,
                'chatbot_data' => $data['collected_data'] ?? [],

                // Status
                'status' => self::INTENT_STAGE_MAP[$data['intent'] ?? 'consultation'] ?? 'new',
                'qualification_status' => 'new',

                // Attribution
                'first_touch_at' => now(),
                'first_touch_source' => 'instagram_chatbot',
                'acquisition_source_type' => 'chatbot',
            ]);

            // 6. Lock qo'yish (dublikat oldini olish)
            Cache::put($lockKey, true, self::DUPLICATE_PREVENTION_SECONDS);

            // 7. Spam counter ni yangilash
            $this->incrementLeadCounter($conversation->business_id);

            Log::info('TicketService: Lead created from chatbot', [
                'lead_id' => $lead->id,
                'business_id' => $lead->business_id,
                'conversation_id' => $conversation->id,
                'source_type' => $data['source_type'] ?? 'dm',
                'intent' => $data['intent'] ?? null,
            ]);

            return $lead;
        });
    }

    /**
     * Spam tekshirish - 1 daqiqada juda ko'p lead yaratilganmi
     */
    protected function isSpamming(string $businessId): bool
    {
        $counterKey = "lead_counter:{$businessId}";
        $count = (int) Cache::get($counterKey, 0);

        return $count >= self::MAX_LEADS_PER_MINUTE;
    }

    /**
     * Lead counter ni oshirish
     */
    protected function incrementLeadCounter(string $businessId): void
    {
        $counterKey = "lead_counter:{$businessId}";
        $count = (int) Cache::get($counterKey, 0);

        // Counter ni 60 soniyaga saqlash
        Cache::put($counterKey, $count + 1, 60);
    }

    /**
     * Human handoff uchun Lead yaratish
     *
     * Chatbot mijozni operatorga o'tkazganda chaqiriladi.
     */
    public function createForHandoff(InstagramConversation $conversation, array $collectedData = []): ?Lead
    {
        return $this->createFromChatbot($conversation, [
            'source_type' => 'handoff',
            'intent' => 'human_handoff',
            'first_message' => $conversation->messages()->oldest()->first()?->content,
            'collected_data' => $collectedData,
        ]);
    }

    /**
     * Mavjud leadni yangilash
     */
    protected function updateExistingLead(Lead $lead, array $data): Lead
    {
        $updates = [];

        // Telefon raqami yangilash
        if (! empty($data['phone']) && empty($lead->phone)) {
            $updates['phone'] = $data['phone'];
        }

        // Email yangilash
        if (! empty($data['email']) && empty($lead->email)) {
            $updates['email'] = $data['email'];
        }

        // Ism yangilash (agar hali aniq emas bo'lsa)
        if (! empty($data['name']) && ($lead->name === 'Instagram Foydalanuvchi' || empty($lead->name))) {
            $updates['name'] = $data['name'];
        }

        // Chatbot data ni birlashtirish
        if (! empty($data['collected_data'])) {
            $existingData = $lead->chatbot_data ?? [];
            $updates['chatbot_data'] = array_merge($existingData, $data['collected_data']);
        }

        // Intent yangilash (agar aniqroq bo'lsa)
        if (! empty($data['intent']) && empty($lead->chatbot_detected_intent)) {
            $updates['chatbot_detected_intent'] = $data['intent'];
        }

        if (! empty($updates)) {
            $lead->update($updates);
        }

        return $lead->fresh();
    }

    /**
     * Manba kodini aniqlash
     */
    protected function resolveSourceCode(string $sourceType, ?string $intent): string
    {
        // Human handoff uchun alohida manba
        if ($intent === 'human_handoff' || $sourceType === 'handoff') {
            return self::SOURCE_CODES['handoff'];
        }

        return self::SOURCE_CODES[$sourceType] ?? self::SOURCE_CODES['dm'];
    }

    /**
     * Lead nomini aniqlash
     */
    protected function resolveName(InstagramConversation $conversation, array $data): string
    {
        // 1. Data dan
        if (! empty($data['name'])) {
            return $data['name'];
        }

        // 2. Conversation dan (Instagram profile)
        if (! empty($conversation->participant_name)) {
            return $conversation->participant_name;
        }

        // 3. Instagram username
        if (! empty($conversation->participant_username)) {
            return '@' . $conversation->participant_username;
        }

        // 4. Default
        return 'Instagram Foydalanuvchi';
    }

    /**
     * Conversation uchun lead mavjudligini tekshirish
     */
    public function hasLeadForConversation(InstagramConversation $conversation): bool
    {
        return Lead::where('instagram_conversation_id', $conversation->id)->exists();
    }

    /**
     * Lead yaqinda yaratilganligini tekshirish (throttle check)
     */
    public function wasLeadRecentlyCreated(InstagramConversation $conversation): bool
    {
        $lockKey = "lead_creation_lock:{$conversation->id}";

        return Cache::has($lockKey);
    }

    /**
     * Force lead yaratish (throttle ni bypass qilish)
     *
     * Faqat maxsus holatlarda ishlatiladi (masalan, admin tomonidan)
     */
    public function forceCreateLead(InstagramConversation $conversation, array $data): Lead
    {
        // Lock ni tozalash
        $lockKey = "lead_creation_lock:{$conversation->id}";
        Cache::forget($lockKey);

        // Lead yaratish
        $lead = $this->createFromChatbot($conversation, $data);

        if (! $lead) {
            // Agar hali ham null bo'lsa, spam counter ni tozalash
            $counterKey = "lead_counter:{$conversation->business_id}";
            Cache::forget($counterKey);

            $lead = $this->createFromChatbot($conversation, $data);
        }

        return $lead ?? Lead::where('instagram_conversation_id', $conversation->id)->firstOrFail();
    }

    /**
     * Conversation uchun leadni olish
     */
    public function getLeadForConversation(InstagramConversation $conversation): ?Lead
    {
        return Lead::where('instagram_conversation_id', $conversation->id)->first();
    }

    /**
     * Lead ga chatbot data qo'shish
     */
    public function appendChatbotData(Lead $lead, array $newData): Lead
    {
        $existingData = $lead->chatbot_data ?? [];
        $mergedData = array_merge($existingData, $newData);

        $lead->update(['chatbot_data' => $mergedData]);

        return $lead->fresh();
    }

    /**
     * Lead intentini yangilash
     */
    public function updateIntent(Lead $lead, string $intent): Lead
    {
        $lead->update(['chatbot_detected_intent' => $intent]);

        Log::info('TicketService: Lead intent updated', [
            'lead_id' => $lead->id,
            'new_intent' => $intent,
        ]);

        return $lead->fresh();
    }

    /**
     * Chatbotdan kelgan leadlar statistikasi
     */
    public function getChatbotLeadStats(string $businessId, ?string $period = 'today'): array
    {
        $query = Lead::where('business_id', $businessId)
            ->whereNotNull('instagram_conversation_id');

        // Period filter
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
        }

        $total = $query->count();

        // By source type
        $bySourceType = (clone $query)
            ->selectRaw('chatbot_source_type, COUNT(*) as count')
            ->groupBy('chatbot_source_type')
            ->pluck('count', 'chatbot_source_type')
            ->toArray();

        // By intent
        $byIntent = (clone $query)
            ->selectRaw('chatbot_detected_intent, COUNT(*) as count')
            ->groupBy('chatbot_detected_intent')
            ->pluck('count', 'chatbot_detected_intent')
            ->toArray();

        return [
            'total' => $total,
            'by_source_type' => $bySourceType,
            'by_intent' => $byIntent,
            'period' => $period,
        ];
    }
}
