<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineAutomationRule extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Trigger turlari
     */
    public const TRIGGER_TYPES = [
        'call_log_created' => [
            'name' => 'Qo\'ng\'iroq qilindi',
            'icon' => 'phone',
            'conditions' => ['direction', 'status', 'duration_min'],
        ],
        'task_created' => [
            'name' => 'Vazifa yaratildi',
            'icon' => 'clipboard-list',
            'conditions' => ['type', 'priority'],
        ],
        'task_completed' => [
            'name' => 'Vazifa bajarildi',
            'icon' => 'check-circle',
            'conditions' => ['type', 'result'],
        ],
        'message_sent' => [
            'name' => 'Xabar yuborildi',
            'icon' => 'chat-bubble-left',
            'conditions' => ['channel'],
        ],
        'lead_lost' => [
            'name' => 'Lead yo\'qotildi',
            'icon' => 'x-circle',
            'conditions' => ['lost_reason'],
        ],
        'sale_created' => [
            'name' => 'Sotuv amalga oshdi',
            'icon' => 'currency-dollar',
            'conditions' => ['amount_min'],
        ],
    ];

    protected $fillable = [
        'business_id',
        'trigger_type',
        'trigger_conditions',
        'from_stage_slug',
        'to_stage_slug',
        'only_if_current_stage',
        'prevent_backward',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'only_if_current_stage' => 'boolean',
        'prevent_backward' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * From stage
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_slug', 'slug')
            ->where('business_id', $this->business_id);
    }

    /**
     * To stage
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_slug', 'slug')
            ->where('business_id', $this->business_id);
    }

    /**
     * Faol qoidalar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Trigger turi bo'yicha
     */
    public function scopeForTrigger(Builder $query, string $triggerType): Builder
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Trigger turi ma'lumotlarini olish
     */
    public function getTriggerInfoAttribute(): array
    {
        return self::TRIGGER_TYPES[$this->trigger_type] ?? [
            'name' => $this->trigger_type,
            'icon' => 'cog',
            'conditions' => [],
        ];
    }

    /**
     * Condition to'g'ri kelishini tekshirish
     */
    public function matchesConditions(array $context): bool
    {
        if (empty($this->trigger_conditions)) {
            return true;
        }

        foreach ($this->trigger_conditions as $key => $value) {
            // duration_min va amount_min uchun maxsus tekshirish
            if ($key === 'duration_min') {
                if (! isset($context['duration']) || $context['duration'] < $value) {
                    return false;
                }

                continue;
            }

            if ($key === 'amount_min') {
                if (! isset($context['amount']) || $context['amount'] < $value) {
                    return false;
                }

                continue;
            }

            // Oddiy tenglik tekshirish
            if (! isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Yangi business uchun standart qoidalarni yaratish
     */
    public static function createDefaultRules(Business $business): void
    {
        $defaultRules = [
            // Birinchi outbound call → Bog'lanildi
            [
                'trigger_type' => 'call_log_created',
                'trigger_conditions' => ['direction' => 'outbound'],
                'from_stage_slug' => 'new',
                'to_stage_slug' => 'contacted',
                'only_if_current_stage' => true,
                'priority' => 10,
            ],
            // Javob berilgan call → Bog'lanildi (har qanday stage dan)
            [
                'trigger_type' => 'call_log_created',
                'trigger_conditions' => ['direction' => 'outbound', 'status' => 'answered'],
                'from_stage_slug' => null,
                'to_stage_slug' => 'contacted',
                'only_if_current_stage' => false,
                'priority' => 5,
            ],
            // Meeting task yaratildi → Uchrashuv belgilandi
            [
                'trigger_type' => 'task_created',
                'trigger_conditions' => ['type' => 'meeting'],
                'from_stage_slug' => null,
                'to_stage_slug' => 'meeting_scheduled',
                'only_if_current_stage' => false,
                'priority' => 10,
            ],
            // Meeting completed → Uchrashuvga keldi
            [
                'trigger_type' => 'task_completed',
                'trigger_conditions' => ['type' => 'meeting'],
                'from_stage_slug' => null,
                'to_stage_slug' => 'meeting_held',
                'only_if_current_stage' => false,
                'priority' => 10,
            ],
            // Proposal task completed → Taklif yuborildi
            [
                'trigger_type' => 'task_completed',
                'trigger_conditions' => ['type' => 'proposal'],
                'from_stage_slug' => null,
                'to_stage_slug' => 'proposal_sent',
                'only_if_current_stage' => false,
                'priority' => 10,
            ],
            // Sale created → Won
            [
                'trigger_type' => 'sale_created',
                'trigger_conditions' => [],
                'from_stage_slug' => null,
                'to_stage_slug' => 'won',
                'only_if_current_stage' => false,
                'prevent_backward' => false, // Won ga har doim o'tsin
                'priority' => 100,
            ],
            // Lost reason → Lost
            [
                'trigger_type' => 'lead_lost',
                'trigger_conditions' => [],
                'from_stage_slug' => null,
                'to_stage_slug' => 'lost',
                'only_if_current_stage' => false,
                'prevent_backward' => false,
                'priority' => 100,
            ],
        ];

        foreach ($defaultRules as $rule) {
            self::create([
                'business_id' => $business->id,
                ...$rule,
                'is_active' => true,
            ]);
        }
    }
}
