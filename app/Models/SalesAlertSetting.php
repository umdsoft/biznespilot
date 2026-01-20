<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesAlertSetting extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Default sozlamalar
     */
    public const DEFAULT_SETTINGS = [
        'lead_followup' => [
            'is_enabled' => true,
            'conditions' => [
                'hours_before_penalty' => 4,
                'penalty_hours' => 24,
            ],
            'recipients' => ['operator'],
            'channels' => ['app', 'telegram'],
            'frequency' => 'instant',
        ],
        'kpi_warning' => [
            'is_enabled' => true,
            'conditions' => [
                'kpi_threshold' => 50,
                'consecutive_days' => 3,
            ],
            'recipients' => ['sales_head', 'owner'],
            'channels' => ['app'],
            'frequency' => 'daily',
        ],
        'target_reminder' => [
            'is_enabled' => true,
            'conditions' => [
                'daily_reminder_time' => '09:00',
                'weekly_reminder_day' => 'monday',
            ],
            'recipients' => ['operator'],
            'channels' => ['app'],
            'frequency' => 'daily',
            'schedule_time' => '09:00',
        ],
        'penalty_warning' => [
            'is_enabled' => true,
            'conditions' => [
                'hours_before' => 2,
            ],
            'recipients' => ['operator'],
            'channels' => ['app', 'telegram'],
            'frequency' => 'instant',
        ],
        'daily_summary' => [
            'is_enabled' => true,
            'conditions' => [],
            'recipients' => ['operator'],
            'channels' => ['app'],
            'frequency' => 'daily',
            'schedule_time' => '09:00',
        ],
        'achievement' => [
            'is_enabled' => true,
            'conditions' => [],
            'recipients' => ['operator'],
            'channels' => ['app'],
            'frequency' => 'instant',
        ],
        'streak_warning' => [
            'is_enabled' => true,
            'conditions' => [
                'warning_time' => '18:00',
            ],
            'recipients' => ['operator'],
            'channels' => ['app', 'telegram'],
            'frequency' => 'daily',
            'schedule_time' => '18:00',
        ],
        'leaderboard_change' => [
            'is_enabled' => true,
            'conditions' => [
                'min_position_change' => 3,
            ],
            'recipients' => ['operator'],
            'channels' => ['app'],
            'frequency' => 'instant',
        ],
    ];

    /**
     * Frequency turlari
     */
    public const FREQUENCIES = [
        'instant' => 'Darhol',
        'hourly' => 'Har soatda',
        'daily' => 'Kunlik',
    ];

    protected $fillable = [
        'business_id',
        'alert_type',
        'is_enabled',
        'conditions',
        'recipients',
        'channels',
        'frequency',
        'schedule_time',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'conditions' => 'array',
        'recipients' => 'array',
        'channels' => 'array',
    ];

    /**
     * Alert turi bo'yicha
     */
    public function scopeForType(Builder $query, string $alertType): Builder
    {
        return $query->where('alert_type', $alertType);
    }

    /**
     * Faol sozlamalar
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Condition qiymatini olish
     */
    public function getCondition(string $key, $default = null)
    {
        return data_get($this->conditions, $key, $default);
    }

    /**
     * Kanal yoqilganmi?
     */
    public function isChannelEnabled(string $channel): bool
    {
        return in_array($channel, $this->channels ?? []);
    }

    /**
     * Recipient qo'shilganmi?
     */
    public function hasRecipient(string $recipient): bool
    {
        return in_array($recipient, $this->recipients ?? []);
    }

    /**
     * Biznes uchun sozlamani olish yoki yaratish
     */
    public static function getOrCreate(string $businessId, string $alertType): self
    {
        $setting = self::where('business_id', $businessId)
            ->where('alert_type', $alertType)
            ->first();

        if ($setting) {
            return $setting;
        }

        // Default sozlamalardan yaratish
        $defaults = self::DEFAULT_SETTINGS[$alertType] ?? [
            'is_enabled' => true,
            'conditions' => [],
            'recipients' => ['operator'],
            'channels' => ['app'],
            'frequency' => 'instant',
        ];

        return self::create([
            'business_id' => $businessId,
            'alert_type' => $alertType,
            ...$defaults,
        ]);
    }

    /**
     * Biznes uchun barcha sozlamalarni yaratish
     */
    public static function initializeForBusiness(string $businessId): void
    {
        foreach (self::DEFAULT_SETTINGS as $alertType => $defaults) {
            self::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'alert_type' => $alertType,
                ],
                $defaults
            );
        }
    }

    /**
     * Barcha sozlamalarni olish (default bilan)
     */
    public static function getAllForBusiness(string $businessId): array
    {
        $settings = self::where('business_id', $businessId)->get()->keyBy('alert_type');

        $result = [];
        foreach (self::DEFAULT_SETTINGS as $alertType => $defaults) {
            if ($settings->has($alertType)) {
                $result[$alertType] = $settings->get($alertType);
            } else {
                $result[$alertType] = new self(array_merge(
                    ['business_id' => $businessId, 'alert_type' => $alertType],
                    $defaults
                ));
            }
        }

        return $result;
    }
}
