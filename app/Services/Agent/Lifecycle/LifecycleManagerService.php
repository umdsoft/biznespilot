<?php

namespace App\Services\Agent\Lifecycle;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mijoz umr yo'li boshqaruvchisi.
 * Birinchi xabardan doimiy mijozga aylanguncha — har bosqichda to'g'ri harakat.
 *
 * Gibrid: 80% shablon (bepul), 20% AI shaxsiylashtirish (Haiku)
 */
class LifecycleManagerService
{
    // Bosqich o'tish qoidalari
    private const STAGE_TRIGGERS = [
        'new' => ['condition' => 'lead_created', 'action' => 'welcome_message', 'delay_hours' => 0],
        'interested' => ['condition' => 'no_purchase_3_days', 'action' => 'followup_message', 'delay_hours' => 72],
        'first_purchase' => ['condition' => 'order_created', 'action' => 'thank_you_message', 'delay_hours' => 0],
        'feedback' => ['condition' => 'delivery_7_days', 'action' => 'feedback_request', 'delay_hours' => 168],
        'repeat' => ['condition' => 'no_purchase_30_days', 'action' => 'reengagement', 'delay_hours' => 720],
        'loyal' => ['condition' => '3_purchases', 'action' => 'loyalty_reward', 'delay_hours' => 0],
        'churning' => ['condition' => 'no_purchase_60_days', 'action' => 'win_back_offer', 'delay_hours' => 1440],
    ];

    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Mijoz bosqichini aniqlash va yangilash
     */
    public function detectAndUpdateStage(string $businessId, string $customerId): array
    {
        try {
            $lifecycle = DB::table('customer_lifecycle')
                ->where('business_id', $businessId)
                ->where('customer_id', $customerId)
                ->first();

            // Mijoz ma'lumotlari
            $purchases = DB::table('sales')
                ->where('business_id', $businessId)
                ->where('customer_id', $customerId)
                ->orderByDesc('created_at')
                ->get();

            $totalPurchases = $purchases->count();
            $lastPurchase = $purchases->first();
            $daysSinceLastPurchase = $lastPurchase
                ? now()->diffInDays($lastPurchase->created_at)
                : null;

            // Bosqichni aniqlash
            $newStage = $this->determineStage($totalPurchases, $daysSinceLastPurchase);
            $currentStage = $lifecycle->current_stage ?? 'new';

            // Agar bosqich o'zgargan bo'lsa — yangilash
            if ($newStage !== $currentStage || !$lifecycle) {
                $this->updateStage($businessId, $customerId, $newStage, $currentStage, $totalPurchases, $purchases);
            }

            return [
                'success' => true,
                'stage' => $newStage,
                'changed' => $newStage !== $currentStage,
                'total_purchases' => $totalPurchases,
                'days_since_last' => $daysSinceLastPurchase,
            ];

        } catch (\Exception $e) {
            Log::error('Lifecycle: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejalashtirilgan harakatlarni bajarish (cron)
     */
    public function processScheduledActions(string $businessId): int
    {
        $actions = DB::table('lifecycle_actions')
            ->where('business_id', $businessId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->limit(50)
            ->get();

        $processed = 0;
        foreach ($actions as $action) {
            try {
                // Xabar mazmunini tayyorlash
                $content = $action->message_content;

                // Agar shaxsiylashtirish kerak bo'lsa — Haiku dan
                if (!$content) {
                    $content = $this->getTemplateMessage($action->stage, $action->action_type, $businessId, $action->customer_id);
                }

                // TODO: Kanalga yuborish (Telegram/Instagram/Facebook)
                // Hozircha statusni yangilaymiz

                DB::table('lifecycle_actions')
                    ->where('id', $action->id)
                    ->update(['status' => 'sent', 'sent_at' => now(), 'message_content' => $content]);

                $processed++;
            } catch (\Exception $e) {
                Log::warning('Lifecycle: harakat bajarishda xato', ['action_id' => $action->id, 'error' => $e->getMessage()]);
            }
        }

        return $processed;
    }

    /**
     * Bosqichni aniqlash
     */
    private function determineStage(int $totalPurchases, ?int $daysSinceLast): string
    {
        if ($totalPurchases >= 3) return 'loyal';
        if ($totalPurchases >= 1 && $daysSinceLast !== null && $daysSinceLast >= 60) return 'churning';
        if ($totalPurchases >= 2) return 'repeat';
        if ($totalPurchases === 1 && $daysSinceLast !== null && $daysSinceLast >= 7) return 'feedback';
        if ($totalPurchases === 1) return 'first_purchase';
        if ($daysSinceLast === null) return 'new';
        return 'interested';
    }

    /**
     * Bosqichni bazada yangilash
     */
    private function updateStage(string $businessId, string $customerId, string $newStage, string $oldStage, int $totalPurchases, $purchases): void
    {
        $totalSpent = $purchases->sum('amount');

        DB::table('customer_lifecycle')->updateOrInsert(
            ['business_id' => $businessId, 'customer_id' => $customerId],
            [
                'id' => DB::table('customer_lifecycle')
                    ->where('business_id', $businessId)
                    ->where('customer_id', $customerId)
                    ->value('id') ?? \Illuminate\Support\Str::uuid()->toString(),
                'current_stage' => $newStage,
                'previous_stage' => $oldStage,
                'stage_entered_at' => now(),
                'total_purchases' => $totalPurchases,
                'total_spent' => $totalSpent,
                'last_purchase_at' => $purchases->first()?->created_at,
                'updated_at' => now(),
            ],
        );

        // Keyingi harakatni rejalashtirish
        $trigger = self::STAGE_TRIGGERS[$newStage] ?? null;
        if ($trigger) {
            $this->scheduleAction($businessId, $customerId, $newStage, $trigger);
        }
    }

    /**
     * Harakatni rejalashtirish
     */
    private function scheduleAction(string $businessId, string $customerId, string $stage, array $trigger): void
    {
        $lifecycleId = DB::table('customer_lifecycle')
            ->where('business_id', $businessId)
            ->where('customer_id', $customerId)
            ->value('id');

        if (!$lifecycleId) return;

        DB::table('lifecycle_actions')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'lifecycle_id' => $lifecycleId,
            'stage' => $stage,
            'action_type' => $trigger['action'],
            'channel' => 'telegram', // Default kanal
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours($trigger['delay_hours']),
            'created_at' => now(),
        ]);
    }

    /**
     * Shablon xabar olish
     */
    private function getTemplateMessage(string $stage, string $actionType, string $businessId, string $customerId): string
    {
        $bizName = DB::table('businesses')->where('id', $businessId)->value('name') ?? 'Biznes';
        $custName = DB::table('customers')->where('id', $customerId)->value('name') ?? '';

        return match ($actionType) {
            'welcome_message' => "Xush kelibsiz, {$custName}! 👋 {$bizName} ga qiziqish bildirganingiz uchun rahmat. Sizga 10% chegirma kodi: WELCOME10",
            'followup_message' => "Salom, {$custName}! {$bizName} dan savol bo'lsa bemalol yozing. Sizga yordam berishga tayyormiz! 😊",
            'thank_you_message' => "Rahmat, {$custName}! Buyurtmangiz qabul qilindi. Tez orada yetkazamiz! 🎉",
            'feedback_request' => "Salom, {$custName}! Mahsulotimiz yoqdimi? Izoh qoldirsangiz 5% chegirma beramiz! ⭐",
            'reengagement' => "Salom, {$custName}! Yangi mahsulotlarimiz bor. Sizga maxsus taklif: 15% chegirma! 🎁",
            'loyalty_reward' => "Tabriklaymiz, {$custName}! Siz bizning doimiy mijozimiz! 🏆 Maxsus imtiyozlar sizni kutmoqda.",
            'win_back_offer' => "Siz bizni sog'indingiz, {$custName}! 😊 Maxsus 20% chegirma bilan qaytib keling!",
            default => "Salom, {$custName}! {$bizName} dan xabar. Savolingiz bo'lsa yozing!",
        };
    }
}
