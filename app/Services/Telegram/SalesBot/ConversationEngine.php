<?php

namespace App\Services\Telegram\SalesBot;

use App\Models\CustomerBehaviorEvent;
use App\Models\CustomerNeedProfile;
use App\Models\Lead;
use App\Models\TelegramConversation;
use App\Models\TelegramUser;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ConversationEngine — AI Maslahatchi Bot ning markaziy "miyasi".
 *
 * Vazifa: har mijoz xabarini qabul qilib, mijoz ehtiyojini tushunib,
 * mos javob qaytarish — savol yoki mahsulot tavsiyasi. Owner aralashishi
 * shart emas — to'liq avtomat.
 *
 * Ish oqimi:
 *   1. NeedExtractor — xabardan ehtiyojni extract qilish
 *   2. CustomerNeedProfile yangilanadi
 *   3. info_completeness baholash:
 *      - < 0.5 → savol berish (discovery)
 *      - >= 0.5 → Lead avtomatik yaratish (marketing uchun)
 *      - >= 0.6 → mahsulot tavsiya qilish (recommendation)
 *   4. Behavior event qaydlanadi
 *
 * Sinxron tizim bilan ishlash:
 *   - Mavjud TelegramUser, TelegramConversation, Lead modellaridan foydalanadi
 *   - StoreProduct katalogini ishlatadi
 *   - Funnel'ning bir qismi sifatida chaqirilishi mumkin (ai_consultant step)
 */
class ConversationEngine
{
    public function __construct(
        private NeedExtractor $needExtractor,
        private ProductMatcher $productMatcher,
        private AIService $aiService,
    ) {}

    /**
     * Mijoz xabarini qayta ishlash.
     *
     * @return array  ['reply' => 'matn', 'products' => [...], 'state' => 'discovery|recommend|...']
     */
    public function handleMessage(TelegramUser $user, string $message, ?TelegramConversation $conversation = null): array
    {
        try {
            // Conversation mavjudligini ta'minlash
            $conversation = $conversation ?? $this->ensureConversation($user);

            // Profile mavjudligini ta'minlash
            $profile = $this->ensureProfile($user, $conversation);

            // ── 1. AI orqali ehtiyojni extract qilish ──────────────────
            $extracted = $this->needExtractor->extract($message, $profile);
            $this->mergeProfile($profile, $extracted);

            // Behavior event
            CustomerBehaviorEvent::track($user->business_id, $user->id, CustomerBehaviorEvent::EVENT_ASKED_QUESTION, [
                'message' => mb_substr($message, 0, 200),
                'extracted' => $extracted,
            ]);

            // ── 2. Lead avtomatik yaratish (marketing data) ──────────────
            if ($profile->isReadyForLead() && ! $user->lead_id) {
                $this->createLead($user, $profile);
            }

            // E'tiroz aniqlanmadi va mahsulot tavsiya etilgan — completed_purchase emas, lekin "ready_to_buy"?
            if (! empty($extracted['ready_to_buy'])) {
                return $this->buildCheckoutResponse($user, $profile);
            }

            // E'tiroz bo'lsa, alohida handler
            $objection = $extracted['objection'] ?? $this->needExtractor->detectObjection($message);
            if ($objection) {
                CustomerBehaviorEvent::track($user->business_id, $user->id, CustomerBehaviorEvent::EVENT_OBJECTION_RAISED, [
                    'objection' => $objection,
                ]);
                return $this->buildObjectionResponse($profile, $objection);
            }

            // ── 3. Yetarli ma'lumot bormi? ─────────────────────────────
            if ($profile->isReadyForRecommendation()) {
                return $this->buildRecommendationResponse($user, $profile);
            }

            // Aks holda — keyingi savol (discovery)
            return $this->buildDiscoveryResponse($user, $profile);

        } catch (\Throwable $e) {
            Log::error('SalesBot ConversationEngine error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [
                'reply' => '🤖 Kechirasiz, hozir kichik texnik nosozlik. Bir soniya kuting yoki "operator" deb yozing — sizga shaxsiy yordam beriladi.',
                'state' => 'error',
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // RESPONSE BUILDERS — har holat uchun
    // ═══════════════════════════════════════════════════════════════

    private function buildDiscoveryResponse(TelegramUser $user, CustomerNeedProfile $profile): array
    {
        $profile->update(['current_state' => CustomerNeedProfile::STATE_DISCOVERY]);

        // AI orqali keyingi savol generate qilish
        $existingInfo = json_encode([
            'intent' => $profile->primary_intent,
            'use_case' => $profile->use_case,
            'constraints' => $profile->constraints ?? [],
            'completeness' => (float) $profile->info_completeness,
        ], JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<'TXT'
Sen do'stona sotuvchi — maslahatchisan. Mijoz bilan tabiiy suhbatda
ehtiyojini ANIQLASHIB borasan. Lekin ko'p savol bermaslik kerak —
maks 4 ta savol jami.

QOIDALAR:
- 1 ta savol ber, 1-2 jumla.
- Robot kabi gapirma.
- Mavjud ma'lumotni qayta so'rama.
- Foydali kontekst ber: "Ko'pchilik bunday mijozlar X tanlashadi — sizga ham X to'g'ri keladimi?"
- Uzbek tili. Hech qachon ruscha yoki inglizcha.

NIMA SO'RASH KERAK (info to'plash uchun):
- Foydalanish maqsadi (use_case) — "qanday vaziyatda kiyasiz/ishlatasiz?"
- Byudjet — "qancha narxda kutyapsiz?"
- O'lcham/razmer — agar kerak bo'lsa
- Brand preferences — agar kerak bo'lsa
TXT;

        $prompt = "Mijoz haqida hozir bilganlarim:\n{$existingInfo}\n\nKeyingi tabiiy savol bering (1-2 jumla, uzbek tilida).";

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: $systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 200,
                businessId: $user->business_id,
                agentType: 'sales_bot_discovery',
            );

            $reply = $response->success
                ? trim($response->content)
                : '🛍️ Sizga to\'g\'ri tanlov topishim uchun bir-ikki narsani aniqlasak. Aytib bering, qanday vaziyatda foydalanmoqchisiz?';

            return [
                'reply' => $reply,
                'state' => 'discovery',
            ];

        } catch (\Throwable $e) {
            Log::warning('Discovery AI fail', ['error' => $e->getMessage()]);
            return [
                'reply' => '🛍️ Aytib bering, qanday vaziyatda foydalanmoqchisiz? Bu sizga eng mos mahsulotni topishimga yordam beradi.',
                'state' => 'discovery',
            ];
        }
    }

    private function buildRecommendationResponse(TelegramUser $user, CustomerNeedProfile $profile): array
    {
        $profile->update(['current_state' => CustomerNeedProfile::STATE_RECOMMEND]);

        $match = $this->productMatcher->match($profile, 3);
        $products = $match['products'] ?? [];

        // Tavsiyani profile'ga saqlash (history)
        if (! empty($products)) {
            $history = $profile->recommended_products ?? [];
            $history[] = [
                'at' => now()->toIso8601String(),
                'product_ids' => array_column($products, 'id'),
            ];
            $profile->update(['recommended_products' => $history]);

            $user->update(['last_recommended_at' => now()]);
        }

        if (empty($products) || $match['fallback']) {
            return [
                'reply' => "😔 Aniq sizning so'rovingizga mos mahsulot omborda yo'q. Ammo agar tafsilotni biroz o'zgartirsangiz (masalan byudjet yoki rang) — boshqa variantlar topa olaman. Yoki menejer bilan bog'lanmoqchimisiz?",
                'state' => 'recommend',
                'products' => [],
                'fallback' => true,
            ];
        }

        // Reply matn yaratish
        $intro = "🎯 Sizning ehtiyojingizga mos " . count($products) . " ta variant tanladim:\n";
        $reply = $intro;
        foreach ($products as $i => $p) {
            $num = $i + 1;
            $price = number_format($p['price'], 0, '.', ' ');
            $reply .= "\n**{$num}. {$p['name']} — {$price} so'm**\n✓ {$p['reason']}";
        }
        $reply .= "\n\nQaysi biri qiziq? Yoki boshqa variantlarni ko'ramizmi?";

        return [
            'reply' => $reply,
            'state' => 'recommend',
            'products' => $products,
        ];
    }

    private function buildObjectionResponse(CustomerNeedProfile $profile, string $objection): array
    {
        $profile->update(['current_state' => CustomerNeedProfile::STATE_OBJECTION]);

        $blockers = $profile->blockers ?? [];
        if (! in_array($objection, $blockers)) {
            $blockers[] = $objection;
            $profile->update(['blockers' => $blockers]);
        }

        $reply = match ($objection) {
            'narx' => "💡 Tushunaman. Aytib bering, qancha byudjet ichida ko'rmoqchisiz? Sizga shu doirada eng yaxshi variantlarni topib beraman. Ba'zida arzonroq variantlar ham ajoyib bo'ladi.",
            'ishonch' => "✅ Tabiiy savol. Bizning barcha mahsulotlarimiz **rasmiy ta'minotchidan**, kafolat bilan. Agar yoqmasa — 14 kun ichida qaytaring, hech savol-javob qilmaymiz.",
            'yetkazish' => "🚚 Toshkent bo'ylab **ertangi kun** yetkazib berishimiz mumkin. Viloyatlarga 2-3 kun. Sizga qachon kerak?",
            'razmer' => "📏 Razmerni biz kafolatlaymiz — agar mos kelmasa **bepul almashtiramiz**. Ayting-chi, qaysi razmer izlayapsiz?",
            default => "🙋 Tushundim. Iltimos, keng kelib aytib bering — nima sizni shubha tug'diryapti? Hal qilamiz.",
        };

        return [
            'reply' => $reply,
            'state' => 'objection',
            'objection' => $objection,
        ];
    }

    private function buildCheckoutResponse(TelegramUser $user, CustomerNeedProfile $profile): array
    {
        $profile->update(['current_state' => CustomerNeedProfile::STATE_CHECKOUT, 'ready_to_buy' => true]);

        return [
            'reply' => "🎉 Ajoyib tanlov! To'lov bosqichiga o'tamiz. Quyidagidan birini tanlang yoki menejerga ulanish uchun \"operator\" deb yozing.",
            'state' => 'checkout',
            'cta' => 'checkout',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function ensureConversation(TelegramUser $user): TelegramConversation
    {
        $conversation = TelegramConversation::where('telegram_user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (! $conversation) {
            $conversation = TelegramConversation::create([
                'business_id' => $user->business_id,
                'telegram_bot_id' => $user->telegram_bot_id,
                'telegram_user_id' => $user->id,
                'status' => 'active',
                'started_at' => now(),
                'last_message_at' => now(),
            ]);
        }

        return $conversation;
    }

    private function ensureProfile(TelegramUser $user, TelegramConversation $conversation): CustomerNeedProfile
    {
        return CustomerNeedProfile::firstOrCreate([
            'telegram_user_id' => $user->id,
            'conversation_id' => $conversation->id,
        ], [
            'business_id' => $user->business_id,
            'current_state' => CustomerNeedProfile::STATE_GREETING,
            'info_completeness' => 0.00,
            'ready_to_buy' => false,
            'constraints' => [],
            'viewed_products' => [],
            'rejected_products' => [],
            'recommended_products' => [],
            'blockers' => [],
        ]);
    }

    /**
     * AI extract qilgan ma'lumotni profile bilan birlashtirish.
     */
    private function mergeProfile(CustomerNeedProfile $profile, array $extracted): void
    {
        $update = [];

        if (! empty($extracted['primary_intent']) && empty($profile->primary_intent)) {
            $update['primary_intent'] = $extracted['primary_intent'];
        }
        if (! empty($extracted['use_case']) && empty($profile->use_case)) {
            $update['use_case'] = $extracted['use_case'];
        }

        // Constraints — merge (mavjudini saqlab, yangisini qo'shish)
        if (! empty($extracted['constraints'])) {
            $existing = $profile->constraints ?? [];
            $update['constraints'] = array_merge($existing, array_filter($extracted['constraints']));
        }

        if (isset($extracted['info_completeness'])) {
            $newScore = (float) $extracted['info_completeness'];
            $oldScore = (float) $profile->info_completeness;
            $update['info_completeness'] = max($oldScore, $newScore); // faqat oshib boradi
        }

        if (isset($extracted['ready_to_buy'])) {
            $update['ready_to_buy'] = (bool) $extracted['ready_to_buy'];
        }

        if (! empty($update)) {
            $profile->update($update);

            // TelegramUser'ning umumiy customer_profile maydonini ham yangilash
            // (marketing dashboard'i uchun — eng oxirgi profile)
            $user = $profile->telegramUser;
            if ($user) {
                $merged = array_merge($user->customer_profile ?? [], [
                    'primary_intent' => $profile->primary_intent,
                    'use_case' => $profile->use_case,
                    'constraints' => $profile->constraints,
                    'info_completeness' => (float) $profile->info_completeness,
                    'updated_at' => now()->toIso8601String(),
                ]);
                $user->update(['customer_profile' => $merged]);
            }
        }
    }

    /**
     * Lead avtomatik yaratish — marketing va sotuv mutaxassislariga
     * mijoz haqidagi to'liq ma'lumot beradi.
     */
    private function createLead(TelegramUser $user, CustomerNeedProfile $profile): void
    {
        try {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ? '@' . $user->username : 'Telegram mijoz');

            // Lead notes: mijoz portreti
            $notes = $this->buildLeadNotes($profile);

            $lead = Lead::create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $user->business_id,
                'name' => $name,
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => 'new',
                'notes' => $notes,
                'data' => [
                    'source' => 'telegram_sales_bot',
                    'telegram_user_id' => $user->id,
                    'telegram_username' => $user->username,
                    'telegram_id' => $user->telegram_id,
                    'customer_profile' => [
                        'primary_intent' => $profile->primary_intent,
                        'use_case' => $profile->use_case,
                        'constraints' => $profile->constraints,
                        'info_completeness' => (float) $profile->info_completeness,
                    ],
                ],
                'utm_source' => 'telegram',
                'utm_medium' => 'sales_bot',
                'first_touch_at' => $user->first_interaction_at ?? now(),
                'first_touch_source' => 'telegram_bot',
            ]);

            // TelegramUser ga Lead ID ulash
            $user->update(['lead_id' => $lead->id]);

            Log::info('SalesBot: Lead avtomatik yaratildi', [
                'lead_id' => $lead->id,
                'telegram_user_id' => $user->id,
            ]);

        } catch (\Throwable $e) {
            Log::warning('SalesBot: Lead yaratish xato', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    private function buildLeadNotes(CustomerNeedProfile $profile): string
    {
        $lines = [];
        $lines[] = '🤖 AI Sotuvchi Bot orqali to\'plangan ma\'lumot:';
        $lines[] = '';

        if ($profile->primary_intent) {
            $lines[] = '🎯 Asosiy ehtiyoj: ' . $profile->primary_intent;
        }
        if ($profile->use_case) {
            $lines[] = '📋 Foydalanish maqsadi: ' . $profile->use_case;
        }

        $constraints = $profile->constraints ?? [];
        if (! empty($constraints)) {
            $lines[] = '';
            $lines[] = '📌 Cheklovlar/talablar:';
            if (! empty($constraints['budget_min']) || ! empty($constraints['budget_max'])) {
                $min = $constraints['budget_min'] ?? 0;
                $max = $constraints['budget_max'] ?? '∞';
                $lines[] = "   • Byudjet: {$min} – {$max} so'm";
            }
            if (! empty($constraints['size'])) {
                $lines[] = "   • O'lcham: {$constraints['size']}";
            }
            if (! empty($constraints['color'])) {
                $lines[] = "   • Rang: {$constraints['color']}";
            }
            if (! empty($constraints['brand'])) {
                $lines[] = "   • Brand: {$constraints['brand']}";
            }
            if (! empty($constraints['preferences'])) {
                $lines[] = '   • Afzalliklar: ' . implode(', ', $constraints['preferences']);
            }
            if (! empty($constraints['avoid'])) {
                $lines[] = '   • Qochmoqchi: ' . implode(', ', $constraints['avoid']);
            }
        }

        $blockers = $profile->blockers ?? [];
        if (! empty($blockers)) {
            $lines[] = '';
            $lines[] = '⚠️ E\'tirozlar: ' . implode(', ', $blockers);
        }

        $lines[] = '';
        $lines[] = "ℹ️ Ma'lumot to'liqligi: " . round((float) $profile->info_completeness * 100) . '%';

        return implode("\n", $lines);
    }
}
