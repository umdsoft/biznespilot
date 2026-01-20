<?php

namespace App\Services;

use App\Jobs\Notifications\SendEmailNotificationJob;
use App\Jobs\Notifications\SendTelegramNotificationJob;
use App\Models\Alert;
use App\Models\Business;
use App\Models\GeneratedReport;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification via all enabled channels.
     */
    public function send(
        Business $business,
        ?User $user,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        // Create in-app notification
        $notification = Notification::create([
            'business_id' => $business->id,
            'user_id' => $user?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $options['icon'] ?? null,
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null,
        ]);

        // Send to external channels if user specified
        if ($user) {
            $this->dispatchToChannels($business, $user, $type, $title, $message, $options);
        } elseif (!empty($options['notify_all_users'])) {
            // Notify all business users
            $this->notifyAllBusinessUsers($business, $type, $title, $message, $options);
        }

        return $notification;
    }

    /**
     * Dispatch notification to enabled channels.
     */
    protected function dispatchToChannels(
        Business $business,
        User $user,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): void {
        $settings = UserNotificationSetting::getOrCreate($user->id, $business->id);

        // Check quiet hours
        if ($settings->isQuietHours() && ($options['respect_quiet_hours'] ?? true)) {
            Log::info('Notification skipped due to quiet hours', [
                'user_id' => $user->id,
                'type' => $type,
            ]);
            return;
        }

        $metadata = [
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null,
            'extra_data' => $options['extra_data'] ?? [],
        ];

        // Telegram
        if ($settings->shouldSend('telegram', $type) && $settings->telegram_chat_id) {
            $this->queueTelegramNotification(
                $business,
                $user,
                $type,
                $title,
                $message,
                array_merge($metadata, ['telegram_chat_id' => $settings->telegram_chat_id])
            );
        }

        // Email
        if ($settings->shouldSend('email', $type)) {
            $this->queueEmailNotification(
                $business,
                $user,
                $type,
                $title,
                $message,
                array_merge($metadata, ['email' => $user->email])
            );
        }
    }

    /**
     * Queue Telegram notification.
     */
    protected function queueTelegramNotification(
        Business $business,
        ?User $user,
        string $type,
        string $title,
        string $message,
        array $metadata = []
    ): NotificationDelivery {
        $delivery = NotificationDelivery::create([
            'business_id' => $business->id,
            'user_id' => $user?->id,
            'channel' => NotificationDelivery::CHANNEL_TELEGRAM,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'status' => NotificationDelivery::STATUS_PENDING,
        ]);

        SendTelegramNotificationJob::dispatch($delivery)->onQueue('notifications');

        return $delivery;
    }

    /**
     * Queue Email notification.
     */
    protected function queueEmailNotification(
        Business $business,
        ?User $user,
        string $type,
        string $title,
        string $message,
        array $metadata = []
    ): NotificationDelivery {
        $delivery = NotificationDelivery::create([
            'business_id' => $business->id,
            'user_id' => $user?->id,
            'channel' => NotificationDelivery::CHANNEL_EMAIL,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'status' => NotificationDelivery::STATUS_PENDING,
        ]);

        SendEmailNotificationJob::dispatch($delivery)->onQueue('notifications');

        return $delivery;
    }

    /**
     * Notify all users in a business.
     */
    protected function notifyAllBusinessUsers(
        Business $business,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): void {
        $users = $business->users()->get();

        foreach ($users as $user) {
            $this->dispatchToChannels($business, $user, $type, $title, $message, $options);
        }
    }

    /**
     * Send alert notification.
     */
    public function sendAlert(Alert $alert): Notification
    {
        return $this->send(
            $alert->business,
            null,
            'alert',
            $alert->title,
            $alert->message,
            [
                'icon' => 'bell-alert',
                'action_url' => "/dashboard/alerts/{$alert->id}",
                'action_text' => 'Ko\'rish',
                'notify_all_users' => true,
            ]
        );
    }

    /**
     * Send report notification.
     */
    public function sendReport(GeneratedReport $report): Notification
    {
        return $this->send(
            $report->business,
            null,
            'report',
            $report->title,
            $report->summary ?? 'Yangi hisobot tayyor.',
            [
                'icon' => 'document-chart-bar',
                'action_url' => "/dashboard/reports/{$report->id}",
                'action_text' => 'Ko\'rish',
                'notify_all_users' => true,
            ]
        );
    }

    /**
     * Send celebration notification.
     */
    public function sendCelebration(Business $business, string $title, string $message, ?User $targetUser = null): Notification
    {
        return $this->send(
            $business,
            $targetUser,
            'celebration',
            $title,
            $message,
            [
                'icon' => 'trophy',
                'notify_all_users' => !$targetUser,
            ]
        );
    }

    /**
     * Send system notification.
     */
    public function sendSystemNotification(Business $business, string $title, string $message): Notification
    {
        return $this->send(
            $business,
            null,
            'system',
            $title,
            $message,
            [
                'icon' => 'cog',
            ]
        );
    }

    /**
     * Send KPI alert notification.
     */
    public function sendKpiAlert(
        Business $business,
        User $user,
        string $title,
        string $message,
        array $kpiData = []
    ): Notification {
        return $this->send(
            $business,
            $user,
            'kpi',
            $title,
            $message,
            [
                'icon' => 'chart-bar',
                'action_url' => '/sales/kpi',
                'action_text' => 'KPI ni ko\'rish',
                'extra_data' => $kpiData,
            ]
        );
    }

    /**
     * Send task notification.
     */
    public function sendTaskNotification(
        Business $business,
        User $user,
        string $title,
        string $message,
        ?string $taskId = null
    ): Notification {
        return $this->send(
            $business,
            $user,
            'task',
            $title,
            $message,
            [
                'icon' => 'clipboard-check',
                'action_url' => $taskId ? "/tasks/{$taskId}" : '/tasks',
                'action_text' => 'Vazifani ko\'rish',
            ]
        );
    }

    /**
     * Send lead notification.
     */
    public function sendLeadNotification(
        Business $business,
        User $user,
        string $title,
        string $message,
        ?string $leadId = null,
        array $leadData = []
    ): Notification {
        return $this->send(
            $business,
            $user,
            'lead',
            $title,
            $message,
            [
                'icon' => 'user-plus',
                'action_url' => $leadId ? "/leads/{$leadId}" : '/leads',
                'action_text' => 'Lidni ko\'rish',
                'extra_data' => $leadData,
            ]
        );
    }

    /**
     * Send insight notification.
     */
    public function sendInsight(
        Business $business,
        string $title,
        string $message,
        array $insightData = []
    ): Notification {
        return $this->send(
            $business,
            null,
            'insight',
            $title,
            $message,
            [
                'icon' => 'light-bulb',
                'extra_data' => $insightData,
                'notify_all_users' => true,
            ]
        );
    }

    /**
     * Send notification to specific user.
     */
    public function sendToUser(User $user, string $type, string $title, string $message, array $options = []): Notification
    {
        $business = $user->currentBusiness ?? $user->businesses()->first();

        if (!$business) {
            throw new \Exception('User has no associated business');
        }

        return $this->send($business, $user, $type, $title, $message, $options);
    }

    /**
     * Send direct Telegram message (bypass settings).
     */
    public function sendDirectTelegram(
        Business $business,
        string $chatId,
        string $message,
        array $options = []
    ): bool {
        $delivery = $this->queueTelegramNotification(
            $business,
            null,
            $options['type'] ?? 'system',
            $options['title'] ?? 'Xabar',
            $message,
            array_merge($options, ['telegram_chat_id' => $chatId])
        );

        return true;
    }

    /**
     * Send direct Email (bypass settings).
     */
    public function sendDirectEmail(
        Business $business,
        string $email,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        $delivery = $this->queueEmailNotification(
            $business,
            null,
            $options['type'] ?? 'system',
            $subject,
            $body,
            array_merge($options, ['email' => $email])
        );

        return true;
    }

    // ============== Read Operations ==============

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

        return $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
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

    // ============== Cleanup ==============

    public function deleteOldNotifications(int $daysOld = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
            ->where('is_read', true)
            ->delete();
    }

    public function deleteOldDeliveries(int $daysOld = 90): int
    {
        return NotificationDelivery::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    // ============== Bulk Operations ==============

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
                $this->send($business, null, $type, $title, $message, array_merge($options, [
                    'notify_all_users' => true,
                ]));
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

    // ============== Statistics ==============

    public function getDeliveryStats(Business $business, int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'total' => NotificationDelivery::where('business_id', $business->id)
                ->where('created_at', '>=', $since)
                ->count(),
            'by_channel' => NotificationDelivery::where('business_id', $business->id)
                ->where('created_at', '>=', $since)
                ->selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
            'by_status' => NotificationDelivery::where('business_id', $business->id)
                ->where('created_at', '>=', $since)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'failed_rate' => $this->calculateFailedRate($business, $since),
        ];
    }

    protected function calculateFailedRate(Business $business, $since): float
    {
        $total = NotificationDelivery::where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $failed = NotificationDelivery::where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->where('status', NotificationDelivery::STATUS_FAILED)
            ->count();

        return round(($failed / $total) * 100, 2);
    }

    // ============== Preferences ==============

    public function getNotificationPreferences(User $user, Business $business): array
    {
        $settings = UserNotificationSetting::getOrCreate($user->id, $business->id);

        return [
            'telegram' => [
                'enabled' => $settings->telegram_enabled,
                'chat_id' => $settings->telegram_chat_id,
                'alerts' => $settings->telegram_alerts,
                'insights' => $settings->telegram_insights,
                'reports' => $settings->telegram_reports,
                'kpi' => $settings->telegram_kpi,
                'tasks' => $settings->telegram_tasks,
                'leads' => $settings->telegram_leads,
            ],
            'email' => [
                'enabled' => $settings->email_enabled,
                'alerts' => $settings->email_alerts,
                'insights' => $settings->email_insights,
                'reports' => $settings->email_reports,
                'kpi' => $settings->email_kpi,
                'tasks' => $settings->email_tasks,
                'leads' => $settings->email_leads,
                'digest_daily' => $settings->email_digest_daily,
                'digest_weekly' => $settings->email_digest_weekly,
            ],
            'in_app' => [
                'enabled' => $settings->in_app_enabled,
                'alerts' => $settings->in_app_alerts,
                'insights' => $settings->in_app_insights,
                'reports' => $settings->in_app_reports,
                'system' => $settings->in_app_system,
            ],
            'quiet_hours' => [
                'enabled' => $settings->quiet_hours_enabled,
                'start' => $settings->quiet_hours_start,
                'end' => $settings->quiet_hours_end,
            ],
        ];
    }

    public function updateNotificationPreferences(User $user, Business $business, array $preferences): UserNotificationSetting
    {
        $settings = UserNotificationSetting::getOrCreate($user->id, $business->id);
        $settings->update($preferences);

        return $settings->fresh();
    }

    public function shouldSendNotification(User $user, Business $business, string $type, string $channel): bool
    {
        $settings = UserNotificationSetting::getOrCreate($user->id, $business->id);

        return $settings->shouldSend($channel, $type);
    }
}
