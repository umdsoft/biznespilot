<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesSetupProgress extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Setup bosqichlari
     */
    public const STEPS = [
        'select_template' => [
            'name' => 'Shablon tanlash',
            'description' => 'O\'zingizga mos shablonni tanlang',
            'order' => 1,
        ],
        'customize_kpi' => [
            'name' => 'KPI sozlash',
            'description' => 'KPI ko\'rsatkichlarini sozlang',
            'order' => 2,
        ],
        'set_targets' => [
            'name' => 'Maqsadlar belgilash',
            'description' => 'Oylik maqsadlarni belgilang',
            'order' => 3,
        ],
        'configure_bonus' => [
            'name' => 'Bonus sozlash',
            'description' => 'Bonus tizimini sozlang',
            'order' => 4,
        ],
        'configure_penalty' => [
            'name' => 'Jarima sozlash',
            'description' => 'Jarima qoidalarini sozlang',
            'order' => 5,
        ],
        'review_confirm' => [
            'name' => 'Tekshirish va tasdiqlash',
            'description' => 'Barcha sozlamalarni tekshiring',
            'order' => 6,
        ],
    ];

    /**
     * Holat turlari
     */
    public const STATUSES = [
        'in_progress' => 'Jarayonda',
        'completed' => 'Tugallangan',
        'skipped' => 'O\'tkazib yuborilgan',
    ];

    protected $table = 'sales_setup_progress';

    protected $fillable = [
        'business_id',
        'template_set_id',
        'completed_steps',
        'current_step',
        'progress_percent',
        'wizard_data',
        'customizations',
        'status',
        'started_at',
        'completed_at',
        'started_by',
        'completed_by',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'wizard_data' => 'array',
        'customizations' => 'array',
        'progress_percent' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Shablon
     */
    public function templateSet(): BelongsTo
    {
        return $this->belongsTo(SalesKpiTemplateSet::class, 'template_set_id');
    }

    /**
     * Boshlagan foydalanuvchi
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * Tugatgan foydalanuvchi
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Jarayondagi
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Tugallangan
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Bosqich tugallanganmi?
     */
    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->completed_steps ?? []);
    }

    /**
     * Bosqichni tugatish
     */
    public function completeStep(string $step, ?array $data = null): void
    {
        $completedSteps = $this->completed_steps ?? [];

        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        // Keyingi bosqichni aniqlash
        $nextStep = $this->getNextStep($step);

        // Progress foizini hisoblash
        $totalSteps = count(self::STEPS);
        $completedCount = count($completedSteps);
        $progressPercent = round(($completedCount / $totalSteps) * 100);

        $this->completed_steps = $completedSteps;
        $this->current_step = $nextStep;
        $this->progress_percent = $progressPercent;

        // Wizard data yangilash
        if ($data) {
            $wizardData = $this->wizard_data ?? [];
            $wizardData[$step] = $data;
            $this->wizard_data = $wizardData;
        }

        // Oxirgi bosqich tugadimi?
        if (! $nextStep) {
            $this->status = 'completed';
            $this->completed_at = now();
        }

        $this->save();
    }

    /**
     * Keyingi bosqichni olish
     */
    protected function getNextStep(string $currentStep): ?string
    {
        $steps = array_keys(self::STEPS);
        $currentIndex = array_search($currentStep, $steps);

        if ($currentIndex === false || $currentIndex >= count($steps) - 1) {
            return null;
        }

        return $steps[$currentIndex + 1];
    }

    /**
     * Joriy bosqich ma'lumotlari
     */
    public function getCurrentStepInfoAttribute(): ?array
    {
        if (! $this->current_step) {
            return null;
        }

        $stepInfo = self::STEPS[$this->current_step] ?? null;

        if (! $stepInfo) {
            return null;
        }

        return array_merge($stepInfo, [
            'code' => $this->current_step,
            'is_first' => $stepInfo['order'] === 1,
            'is_last' => $stepInfo['order'] === count(self::STEPS),
        ]);
    }

    /**
     * Barcha bosqichlar holati
     */
    public function getStepsStatusAttribute(): array
    {
        $completedSteps = $this->completed_steps ?? [];
        $currentStep = $this->current_step;

        $result = [];
        foreach (self::STEPS as $code => $info) {
            $result[$code] = [
                'name' => $info['name'],
                'description' => $info['description'],
                'order' => $info['order'],
                'status' => $this->getStepStatus($code, $completedSteps, $currentStep),
            ];
        }

        return $result;
    }

    /**
     * Bosqich holatini aniqlash
     */
    protected function getStepStatus(string $step, array $completedSteps, ?string $currentStep): string
    {
        if (in_array($step, $completedSteps)) {
            return 'completed';
        }

        if ($step === $currentStep) {
            return 'current';
        }

        return 'pending';
    }

    /**
     * Holat labelini olish
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Tugallangan bo'lsa, davom etish mumkin emas
     */
    public function canContinue(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Qayta boshlash
     */
    public function restart(string $userId): void
    {
        $this->completed_steps = [];
        $this->current_step = array_key_first(self::STEPS);
        $this->progress_percent = 0;
        $this->wizard_data = null;
        $this->status = 'in_progress';
        $this->started_at = now();
        $this->completed_at = null;
        $this->started_by = $userId;
        $this->save();
    }

    /**
     * O'tkazib yuborish
     */
    public function skip(string $userId): void
    {
        $this->status = 'skipped';
        $this->completed_at = now();
        $this->completed_by = $userId;
        $this->save();
    }

    /**
     * Biznes uchun setup progress olish yoki yaratish
     */
    public static function getOrCreate(string $businessId, string $userId): self
    {
        return self::firstOrCreate(
            ['business_id' => $businessId],
            [
                'completed_steps' => [],
                'current_step' => array_key_first(self::STEPS),
                'progress_percent' => 0,
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => $userId,
            ]
        );
    }
}
