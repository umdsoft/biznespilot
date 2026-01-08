<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Models\Notification;
use App\Models\Alert;
use App\Models\GeneratedReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function send(
        Business $business,
        ?User $user,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        return Notification::create([
            'business_id' => $business->id,
            'user_id' => $user?->id,
            'type' => $type,
            'channel' => $options['channel'] ?? 'in_app',
            'title' => $title,
            'message' => $message,
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null,
            'related_type' => $options['related_type'] ?? null,
            'related_id' => $options['related_id'] ?? null,
            'priority' => $options['priority'] ?? 'medium',
        ]);
    }

    public function sendAlert(Alert $alert): Notification
    {
        return $this->send(
            $alert->business,
            null,
            'alert',
            $alert->title,
            $alert->message,
            [
                'action_url' => "/dashboard/alerts/{$alert->id}",
                'action_text' => 'Ko\'rish',
                'related_type' => Alert::class,
                'related_id' => $alert->id,
                'priority' => $alert->severity,
            ]
        );
    }

    public function sendReport(GeneratedReport $report): Notification
    {
        return $this->send(
            $report->business,
            null,
            'report',
            $report->title,
            $report->summary ?? 'Yangi hisobot tayyor.',
            [
                'action_url' => "/dashboard/reports/{$report->id}",
                'action_text' => 'Ko\'rish',
                'related_type' => GeneratedReport::class,
                'related_id' => $report->id,
                'priority' => 'medium',
            ]
        );
    }

    public function sendCelebration(Business $business, string $title, string $message): Notification
    {
        return $this->send(
            $business,
            null,
            'celebration',
            $title,
            $message,
            [
                'priority' => 'high',
            ]
        );
    }

    public function sendSystemNotification(Business $business, string $title, string $message, string $priority = 'low'): Notification
    {
        return $this->send(
            $business,
            null,
            'system',
            $title,
            $message,
            [
                'priority' => $priority,
            ]
        );
    }

    public function sendToUser(User $user, string $type, string $title, string $message, array $options = []): Notification
    {
        $business = $user->currentBusiness ?? $user->businesses()->first();

        if (!$business) {
            throw new \Exception('User has no associated business');
        }

        return $this->send($business, $user, $type, $title, $message, $options);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAsClicked(Notification $notification): void
    {
        $notification->markAsClicked();
    }

    public function markAllAsRead(Business $business, ?User $user = null): int
    {
        $query = Notification::where('business_id', $business->id)
            ->unread();

        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        }

        return $query->update(['read_at' => now()]);
    }

    public function getUnreadNotifications(Business $business, ?User $user = null, int $limit = 10): Collection
    {
        $query = Notification::where('business_id', $business->id)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        }

        return $query->get();
    }

    public function getAllNotifications(Business $business, ?User $user = null, int $limit = 50): Collection
    {
        $query = Notification::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        }

        return $query->get();
    }

    public function getUnreadCount(Business $business, ?User $user = null): int
    {
        $query = Notification::where('business_id', $business->id)
            ->unread();

        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        }

        return $query->count();
    }

    public function getNotificationsByType(Business $business, string $type, int $limit = 20): Collection
    {
        return Notification::where('business_id', $business->id)
            ->byType($type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function deleteOldNotifications(int $daysOld = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('read_at')
            ->delete();
    }

    public function sendBulkNotification(
        Collection $businesses,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): int {
        $count = 0;

        foreach ($businesses as $business) {
            try {
                $this->send($business, null, $type, $title, $message, $options);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to send bulk notification', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    public function sendTelegramNotification(Business $business, string $message): bool
    {
        // TODO: Implement Telegram bot notification
        // This would integrate with Telegram Bot API
        Log::info('Telegram notification queued', [
            'business_id' => $business->id,
            'message' => $message,
        ]);
        return true;
    }

    public function sendEmailNotification(Business $business, User $user, string $subject, string $body): bool
    {
        // TODO: Implement email notification
        // This would use Laravel's Mail facade
        Log::info('Email notification queued', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'subject' => $subject,
        ]);
        return true;
    }

    public function getNotificationPreferences(User $user): array
    {
        // Return user's notification preferences
        // Could be stored in user_settings or a separate table
        return [
            'email' => [
                'alerts' => true,
                'insights' => true,
                'reports' => true,
                'system' => false,
            ],
            'telegram' => [
                'alerts' => true,
                'insights' => false,
                'reports' => false,
                'system' => false,
            ],
            'in_app' => [
                'alerts' => true,
                'insights' => true,
                'reports' => true,
                'system' => true,
            ],
        ];
    }

    public function shouldSendNotification(User $user, string $type, string $channel): bool
    {
        $preferences = $this->getNotificationPreferences($user);
        return $preferences[$channel][$type] ?? true;
    }
}
