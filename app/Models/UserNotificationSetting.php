<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'user_id',
        'business_id',
        // Telegram
        'telegram_enabled',
        'telegram_chat_id',
        'telegram_alerts',
        'telegram_insights',
        'telegram_reports',
        'telegram_kpi',
        'telegram_tasks',
        'telegram_leads',
        // Email
        'email_enabled',
        'email_alerts',
        'email_insights',
        'email_reports',
        'email_kpi',
        'email_tasks',
        'email_leads',
        'email_digest_daily',
        'email_digest_weekly',
        // In-app
        'in_app_enabled',
        'in_app_alerts',
        'in_app_insights',
        'in_app_reports',
        'in_app_system',
        // Quiet hours
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'telegram_enabled' => 'boolean',
        'telegram_alerts' => 'boolean',
        'telegram_insights' => 'boolean',
        'telegram_reports' => 'boolean',
        'telegram_kpi' => 'boolean',
        'telegram_tasks' => 'boolean',
        'telegram_leads' => 'boolean',
        'email_enabled' => 'boolean',
        'email_alerts' => 'boolean',
        'email_insights' => 'boolean',
        'email_reports' => 'boolean',
        'email_kpi' => 'boolean',
        'email_tasks' => 'boolean',
        'email_leads' => 'boolean',
        'email_digest_daily' => 'boolean',
        'email_digest_weekly' => 'boolean',
        'in_app_enabled' => 'boolean',
        'in_app_alerts' => 'boolean',
        'in_app_insights' => 'boolean',
        'in_app_reports' => 'boolean',
        'in_app_system' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Check if notification should be sent via channel for given type.
     */
    public function shouldSend(string $channel, string $type): bool
    {
        // Check if channel is enabled
        if (!$this->{"{$channel}_enabled"}) {
            return false;
        }

        // Check specific type setting
        $settingKey = "{$channel}_{$type}";
        if (isset($this->$settingKey)) {
            return (bool) $this->$settingKey;
        }

        // Default to true for unknown types
        return true;
    }

    /**
     * Check if currently in quiet hours.
     */
    public function isQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled || !$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        // Handle overnight quiet hours (e.g., 22:00 - 08:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * Get or create settings for user in business.
     */
    public static function getOrCreate(string $userId, string $businessId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'business_id' => $businessId],
            [
                'telegram_enabled' => false,
                'email_enabled' => true,
                'in_app_enabled' => true,
            ]
        );
    }
}
