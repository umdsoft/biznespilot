<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPipelineRule extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Trigger turlari
     */
    public const TRIGGER_TYPES = [
        'activity_created' => [
            'name' => 'Faoliyat qo\'shilganda',
            'description' => 'Lid uchun yangi faoliyat (qo\'ng\'iroq, email, uchrashuv) qo\'shilganda',
            'conditions' => ['activity_type'],
        ],
        'task_completed' => [
            'name' => 'Vazifa bajarilganda',
            'description' => 'Lid bilan bog\'liq vazifa bajarilganda',
            'conditions' => ['task_type'],
        ],
        'field_changed' => [
            'name' => 'Maydon o\'zgarganda',
            'description' => 'Lid maydonlari o\'zgarganda',
            'conditions' => ['field_name', 'field_value'],
        ],
        'time_based' => [
            'name' => 'Vaqt o\'tganda',
            'description' => 'Ma\'lum vaqt davomida o\'zgarish bo\'lmaganda',
            'conditions' => ['days_inactive'],
        ],
    ];

    /**
     * Default qoidalar (shablon)
     */
    public const DEFAULT_RULES = [
        [
            'name' => 'Birinchi qo\'ng\'iroq qilinganda',
            'trigger_type' => 'activity_created',
            'trigger_conditions' => ['activity_type' => 'call'],
            'to_stage_slug' => 'contacted',
            'priority' => 10,
        ],
        [
            'name' => 'Uchrashuv bo\'lganda',
            'trigger_type' => 'task_completed',
            'trigger_conditions' => ['task_type' => 'meeting'],
            'to_stage_slug' => 'meeting_held',
            'priority' => 20,
        ],
        [
            'name' => 'Demo o\'tkazilganda',
            'trigger_type' => 'task_completed',
            'trigger_conditions' => ['task_type' => 'demo'],
            'to_stage_slug' => 'demo_completed',
            'priority' => 30,
        ],
        [
            'name' => 'Taklif yuborilganda',
            'trigger_type' => 'task_completed',
            'trigger_conditions' => ['task_type' => 'proposal'],
            'to_stage_slug' => 'proposal_sent',
            'priority' => 40,
        ],
    ];

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'trigger_type',
        'trigger_conditions',
        'from_stage_id',
        'to_stage_id',
        'is_active',
        'priority',
        'times_triggered',
        'last_triggered_at',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'times_triggered' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Boshlang'ich bosqich
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_id');
    }

    /**
     * Maqsad bosqich
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_id');
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
     * Priority bo'yicha tartiblash
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority');
    }

    /**
     * Trigger turi ma'lumotlarini olish
     */
    public function getTriggerTypeInfoAttribute(): array
    {
        return self::TRIGGER_TYPES[$this->trigger_type] ?? [
            'name' => $this->trigger_type,
            'description' => '',
            'conditions' => [],
        ];
    }

    /**
     * Condition qiymatini olish
     */
    public function getCondition(string $key, $default = null)
    {
        return data_get($this->trigger_conditions, $key, $default);
    }

    /**
     * Qoidani ishga tushirish
     */
    public function incrementTriggerCount(): void
    {
        $this->increment('times_triggered');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Qoida shartlari mos kelishini tekshirish
     */
    public function matchesConditions(array $eventData): bool
    {
        if (empty($this->trigger_conditions)) {
            return true;
        }

        foreach ($this->trigger_conditions as $key => $value) {
            $eventValue = data_get($eventData, $key);

            // Agar shart mos kelmasa
            if ($eventValue !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Biznes uchun default qoidalarni yaratish
     */
    public static function initializeForBusiness(string $businessId): void
    {
        $stages = PipelineStage::where('business_id', $businessId)
            ->get()
            ->keyBy('slug');

        foreach (self::DEFAULT_RULES as $ruleData) {
            $toStage = $stages->get($ruleData['to_stage_slug']);

            if (! $toStage) {
                continue;
            }

            self::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'name' => $ruleData['name'],
                ],
                [
                    'trigger_type' => $ruleData['trigger_type'],
                    'trigger_conditions' => $ruleData['trigger_conditions'],
                    'to_stage_id' => $toStage->id,
                    'priority' => $ruleData['priority'],
                    'is_active' => true,
                ]
            );
        }
    }
}
