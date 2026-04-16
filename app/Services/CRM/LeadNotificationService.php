<?php

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lead Notification Service — real-time xabarlar.
 *
 * Operatorlarga va manager'larga muhim hodisalar haqida xabar.
 *
 * Notification turlari:
 *   - new_lead — yangi lid keldi
 *   - missed_call — qo'ng'iroq javobsiz qolgan
 *   - hot_lead_idle — hot lead 24 soat e'tiborsiz
 *   - stale_alert — 5+ qotib qolgan lid
 *   - high_value_assigned — katta qiymatli lid biriktirildi
 */
class LeadNotificationService
{
    /**
     * Yangi lid kelganda operatorga xabar
     */
    public function notifyNewLead(Lead $lead): void
    {
        if (!$lead->assigned_to) return;

        $this->createNotification(
            userId: $lead->assigned_to,
            type: 'new_lead',
            title: "🆕 Yangi lid: {$lead->name}",
            message: "Telefon: " . ($lead->phone ?? 'yo\'q') . ", Source: " . ($lead->source?->name ?? 'noma\'lum'),
            data: ['lead_id' => $lead->id],
            priority: 'high',
        );
    }

    /**
     * Missed call — operatorga
     */
    public function notifyMissedCall(string $userId, string $callerPhone, ?string $leadId = null): void
    {
        $this->createNotification(
            userId: $userId,
            type: 'missed_call',
            title: "📞 Javobsiz qo'ng'iroq: {$callerPhone}",
            message: "Mijoz qo'ng'iroq qildi, javob bermadingiz. Qaytarib qo'ng'iroq qiling.",
            data: ['phone' => $callerPhone, 'lead_id' => $leadId],
            priority: 'urgent',
        );
    }

    /**
     * Hot lead 24 soat ignored
     */
    public function notifyHotLeadIdle(Lead $lead): void
    {
        if (!$lead->assigned_to) return;

        $this->createNotification(
            userId: $lead->assigned_to,
            type: 'hot_lead_idle',
            title: "🔥 Hot lid e'tiborsiz: {$lead->name}",
            message: "Bu lid yuqori ball ({$lead->score}), lekin 24 soat e'tibor berilmadi. Tezkor bog'laning!",
            data: ['lead_id' => $lead->id, 'score' => $lead->score],
            priority: 'urgent',
        );
    }

    /**
     * Manager uchun stale alert
     */
    public function notifyStaleAlert(string $businessId, int $count): void
    {
        $managers = $this->getManagers($businessId);

        foreach ($managers as $managerId) {
            $this->createNotification(
                userId: $managerId,
                type: 'stale_alert',
                title: "⚠️ {$count} ta qotib qolgan lid",
                message: "Operatorlar e'tibor bermayapti. Tekshiring.",
                data: ['count' => $count],
                priority: 'high',
            );
        }
    }

    /**
     * High value lead biriktirildi
     */
    public function notifyHighValueAssigned(Lead $lead, string $userId): void
    {
        $value = number_format($lead->estimated_value);
        $this->createNotification(
            userId: $userId,
            type: 'high_value_assigned',
            title: "💎 Qimmatli lid sizga biriktirildi: {$lead->name}",
            message: "Qiymati: {$value} so'm. Maxsus e'tibor bering.",
            data: ['lead_id' => $lead->id, 'value' => $lead->estimated_value],
            priority: 'high',
        );
    }

    /**
     * Universal notification yaratish
     */
    private function createNotification(string $userId, string $type, string $title, string $message, array $data = [], string $priority = 'normal'): void
    {
        try {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\CRM\\' . ucfirst(str_replace('_', '', $type)),
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'crm_type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'priority' => $priority,
                    'data' => $data,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Notification yaratishda xato', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Manager userlar (owner + admin)
     */
    private function getManagers(string $businessId): array
    {
        try {
            return DB::table('business_user')
                ->where('business_id', $businessId)
                ->whereIn('role', ['owner', 'admin'])
                ->pluck('user_id')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Foydalanuvchining o'qilmagan notification'lari
     */
    public function getUnread(string $userId, int $limit = 20): array
    {
        return DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * O'qilgan deb belgilash
     */
    public function markRead(string $notificationId): void
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }
}
