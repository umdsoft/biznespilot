<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPenaltyRule extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    /**
     * Jarima kategoriyalari
     */
    public const CATEGORIES = [
        'crm_discipline' => 'CRM intizomi',
        'performance' => 'Samaradorlik',
        'attendance' => 'Davomat',
        'customer_service' => 'Mijozlarga xizmat',
    ];

    /**
     * Trigger turlari
     */
    public const TRIGGER_TYPES = [
        'auto' => 'Avtomatik',
        'manual' => 'Qo\'lda',
    ];

    /**
     * Trigger eventlari
     */
    public const TRIGGER_EVENTS = [
        'lead_not_contacted_24h' => 'Lid 24 soat ichida kontakt qilinmadi',
        'lead_not_contacted_48h' => 'Lid 48 soat ichida kontakt qilinmadi',
        'crm_not_filled' => 'CRM to\'ldirilmagan',
        'task_overdue' => 'Vazifa muddati o\'tdi',
        'task_overdue_3_days' => 'Vazifa 3 kundan ortiq kechiktirildi',
        'low_kpi_3_days' => 'KPI 3 kun ketma-ket past',
        'missed_call' => 'O\'tkazib yuborilgan qo\'ng\'iroq',
        'no_activity_24h' => '24 soat faoliyat yo\'q',
    ];

    /**
     * Jarima turlari
     */
    public const PENALTY_TYPES = [
        'fixed' => 'Belgilangan summa',
        'percentage_of_bonus' => 'Bonus foizi',
        'warning_only' => 'Faqat ogohlantirish',
    ];

    /**
     * Ogohlantirish turlari
     */
    public const WARNING_TYPES = [
        'system' => 'Tizim',
        'verbal' => 'Og\'zaki',
        'written' => 'Yozma',
        'final' => 'Oxirgi ogohlantirish',
    ];

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'category',
        'trigger_type',
        'trigger_event',
        'trigger_conditions',
        'penalty_type',
        'penalty_amount',
        'penalty_percentage',
        'warning_before_penalty',
        'warnings_before_penalty',
        'warning_validity_days',
        'max_per_day',
        'max_per_month',
        'allow_appeal',
        'appeal_deadline_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'penalty_amount' => 'decimal:2',
        'penalty_percentage' => 'decimal:2',
        'warning_before_penalty' => 'boolean',
        'warnings_before_penalty' => 'integer',
        'warning_validity_days' => 'integer',
        'max_per_day' => 'integer',
        'max_per_month' => 'integer',
        'allow_appeal' => 'boolean',
        'appeal_deadline_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Berilgan jarimalar
     */
    public function penalties(): HasMany
    {
        return $this->hasMany(SalesPenalty::class, 'penalty_rule_id');
    }

    /**
     * Ogohlantirishlar
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(SalesPenaltyWarning::class, 'penalty_rule_id');
    }

    /**
     * Faol qoidalar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Avtomatik triggerlar
     */
    public function scopeAutoTrigger(Builder $query): Builder
    {
        return $query->where('trigger_type', 'auto');
    }

    /**
     * Event bo'yicha
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('trigger_event', $event);
    }

    /**
     * Kategoriya labelini olish
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Trigger event labelini olish
     */
    public function getTriggerEventLabelAttribute(): string
    {
        return self::TRIGGER_EVENTS[$this->trigger_event] ?? $this->trigger_event;
    }

    /**
     * Jarima turi labelini olish
     */
    public function getPenaltyTypeLabelAttribute(): string
    {
        return self::PENALTY_TYPES[$this->penalty_type] ?? $this->penalty_type;
    }

    /**
     * Trigger condition qiymatini olish
     */
    public function getConditionValue(string $key, $default = null)
    {
        return $this->trigger_conditions[$key] ?? $default;
    }

    /**
     * Foydalanuvchi uchun bugungi jarimalar sonini olish
     */
    public function getTodayPenaltiesCount(string $userId): int
    {
        return $this->penalties()
            ->where('user_id', $userId)
            ->whereDate('triggered_at', today())
            ->count();
    }

    /**
     * Foydalanuvchi uchun oylik jarimalar sonini olish
     */
    public function getMonthlyPenaltiesCount(string $userId): int
    {
        return $this->penalties()
            ->where('user_id', $userId)
            ->whereBetween('triggered_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * Kunlik limit tekshirish
     */
    public function canIssuePenalty(string $userId): array
    {
        $canIssue = true;
        $reason = null;

        if ($this->max_per_day !== null) {
            $todayCount = $this->getTodayPenaltiesCount($userId);
            if ($todayCount >= $this->max_per_day) {
                $canIssue = false;
                $reason = "Kunlik limit ({$this->max_per_day}) ga yetildi";
            }
        }

        if ($canIssue && $this->max_per_month !== null) {
            $monthCount = $this->getMonthlyPenaltiesCount($userId);
            if ($monthCount >= $this->max_per_month) {
                $canIssue = false;
                $reason = "Oylik limit ({$this->max_per_month}) ga yetildi";
            }
        }

        return [
            'can_issue' => $canIssue,
            'reason' => $reason,
        ];
    }

    /**
     * Foydalanuvchining faol ogohlantirishlar sonini olish
     */
    public function getActiveWarningsCount(string $userId): int
    {
        return $this->warnings()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /**
     * Ogohlantirish kerakmi yoki to'g'ridan-to'g'ri jarima
     */
    public function shouldIssueWarning(string $userId): bool
    {
        if (! $this->warning_before_penalty) {
            return false;
        }

        $activeWarnings = $this->getActiveWarningsCount($userId);

        return $activeWarnings < $this->warnings_before_penalty;
    }

    /**
     * Jarima summasini hisoblash
     */
    public function calculatePenaltyAmount(float $bonusAmount = 0): float
    {
        return match ($this->penalty_type) {
            'fixed' => $this->penalty_amount,
            'percentage_of_bonus' => $bonusAmount * ($this->penalty_percentage / 100),
            'warning_only' => 0,
            default => 0,
        };
    }
}
