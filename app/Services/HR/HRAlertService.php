<?php

namespace App\Services\HR;

use App\Models\Business;
use App\Models\HRAlert;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * HRAlertService - HR ogohlantirishlarini boshqarish
 *
 * Vazifalar:
 * 1. HR alertlarni yaratish
 * 2. Alert prioritylarni boshqarish
 * 3. Notification yuborish
 * 4. Alert tarixini saqlash
 */
class HRAlertService
{
    /**
     * Yangi alert yaratish
     */
    public function createAlert(
        Business $business,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): ?HRAlert {
        try {
            $alert = HRAlert::create([
                'business_id' => $business->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'priority' => $options['priority'] ?? 'medium',
                'status' => HRAlert::STATUS_NEW,
                'user_id' => $options['user_id'] ?? null,
                'related_user_id' => $options['related_user_id'] ?? ($options['data']['employee_id'] ?? null),
                'is_celebration' => $options['is_celebration'] ?? false,
                'data' => $options['data'] ?? null,
                'recommended_actions' => $options['recommended_actions'] ?? null,
            ]);

            Log::info('HRAlertService: Alert yaratildi', [
                'alert_id' => $alert->id,
                'type' => $type,
                'priority' => $options['priority'] ?? 'medium',
            ]);

            // Agar urgent bo'lsa - darhol notification yuborish
            if (($options['priority'] ?? 'medium') === 'urgent') {
                $this->sendUrgentNotification($alert);
            }

            return $alert;
        } catch (\Exception $e) {
            Log::error('HRAlertService: Alert yaratishda xato', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);
            return null;
        }
    }

    /**
     * Bir nechta foydalanuvchiga alert yuborish
     */
    public function createAlertForUsers(
        Business $business,
        array $userIds,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): array {
        $alerts = [];

        foreach ($userIds as $userId) {
            $options['user_id'] = $userId;
            $alert = $this->createAlert($business, $type, $title, $message, $options);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * HR va Manager larga alert yuborish
     */
    public function createAlertForHRAndManagers(
        Business $business,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): array {
        // HR va manager rollariga ega foydalanuvchilarni olish
        $hrUsers = $business->users()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['hr', 'hr_manager', 'admin', 'owner']);
            })
            ->pluck('users.id')
            ->toArray();

        return $this->createAlertForUsers($business, $hrUsers, $type, $title, $message, $options);
    }

    /**
     * Yangi (o'qilmagan) alertlarni olish
     */
    public function getUnreadAlerts(Business $business, ?string $userId = null, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $query = HRAlert::where('business_id', $business->id)
            ->whereIn('status', [HRAlert::STATUS_NEW, HRAlert::STATUS_SEEN])
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Faol (hal qilinmagan) alertlarni olish
     */
    public function getActiveAlerts(Business $business, ?string $userId = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = HRAlert::where('business_id', $business->id)
            ->where('status', '!=', HRAlert::STATUS_RESOLVED)
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Alertni ko'rilgan deb belgilash
     */
    public function markAsSeen(HRAlert $alert): bool
    {
        return $alert->markAsSeen();
    }

    /**
     * Alertni tan olingan deb belgilash
     */
    public function markAsAcknowledged(HRAlert $alert): bool
    {
        return $alert->markAsAcknowledged();
    }

    /**
     * Alertni hal qilingan deb belgilash
     */
    public function markAsResolved(HRAlert $alert, string $resolvedById): bool
    {
        return $alert->markAsResolved($resolvedById);
    }

    /**
     * Barcha alertlarni ko'rilgan deb belgilash
     */
    public function markAllAsSeen(Business $business, string $userId): int
    {
        return HRAlert::where('business_id', $business->id)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            })
            ->where('status', HRAlert::STATUS_NEW)
            ->update([
                'status' => HRAlert::STATUS_SEEN,
                'seen_at' => now(),
            ]);
    }

    /**
     * Priority bo'yicha faol alertlarni olish
     */
    public function getAlertsByPriority(Business $business, string $priority): \Illuminate\Database\Eloquent\Collection
    {
        return HRAlert::where('business_id', $business->id)
            ->where('priority', $priority)
            ->where('status', '!=', HRAlert::STATUS_RESOLVED)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Urgent notification yuborish
     */
    protected function sendUrgentNotification(HRAlert $alert): void
    {
        // Bu yerda email, push notification yoki boshqa kanallar orqali
        // urgent xabar yuborish logikasi bo'ladi
        Log::info('HRAlertService: Urgent notification yuborildi', [
            'alert_id' => $alert->id,
            'user_id' => $alert->user_id,
        ]);
    }

    /**
     * Eski alertlarni tozalash (faqat hal qilinganlarni)
     */
    public function cleanupOldAlerts(Business $business, int $daysOld = 30): int
    {
        return HRAlert::where('business_id', $business->id)
            ->where('status', HRAlert::STATUS_RESOLVED)
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Alert statistikasi
     */
    public function getAlertStats(Business $business): array
    {
        $stats = HRAlert::where('business_id', $business->id)
            ->selectRaw("
                priority,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status != 'resolved' THEN 1 ELSE 0 END) as active_count
            ")
            ->groupBy('priority')
            ->get()
            ->keyBy('priority');

        return [
            'urgent' => [
                'total' => $stats->get('urgent')?->total ?? 0,
                'new' => $stats->get('urgent')?->new_count ?? 0,
                'active' => $stats->get('urgent')?->active_count ?? 0,
            ],
            'high' => [
                'total' => $stats->get('high')?->total ?? 0,
                'new' => $stats->get('high')?->new_count ?? 0,
                'active' => $stats->get('high')?->active_count ?? 0,
            ],
            'medium' => [
                'total' => $stats->get('medium')?->total ?? 0,
                'new' => $stats->get('medium')?->new_count ?? 0,
                'active' => $stats->get('medium')?->active_count ?? 0,
            ],
            'low' => [
                'total' => $stats->get('low')?->total ?? 0,
                'new' => $stats->get('low')?->new_count ?? 0,
                'active' => $stats->get('low')?->active_count ?? 0,
            ],
            'summary' => [
                'total_new' => HRAlert::where('business_id', $business->id)
                    ->where('status', HRAlert::STATUS_NEW)->count(),
                'total_active' => HRAlert::where('business_id', $business->id)
                    ->where('status', '!=', HRAlert::STATUS_RESOLVED)->count(),
                'celebrations' => HRAlert::where('business_id', $business->id)
                    ->where('is_celebration', true)
                    ->where('status', '!=', HRAlert::STATUS_RESOLVED)->count(),
            ],
        ];
    }
}
