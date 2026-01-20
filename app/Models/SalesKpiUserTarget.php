<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesKpiUserTarget extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Holat turlari
     */
    public const STATUSES = [
        'active' => 'Faol',
        'completed' => 'Tugallangan',
        'cancelled' => 'Bekor qilingan',
    ];

    protected $fillable = [
        'business_id',
        'kpi_setting_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'target_value',
        'adjusted_target',
        'adjustment_reason',
        'achieved_value',
        'achievement_percent',
        'score',
        'set_by',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'target_value' => 'decimal:2',
        'adjusted_target' => 'decimal:2',
        'achieved_value' => 'decimal:2',
        'achievement_percent' => 'decimal:2',
        'score' => 'integer',
    ];

    /**
     * KPI sozlamasi
     */
    public function kpiSetting(): BelongsTo
    {
        return $this->belongsTo(SalesKpiSetting::class, 'kpi_setting_id');
    }

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kim tomonidan belgilangan
     */
    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * Faol maqsadlar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Davr bo'yicha filter
     */
    public function scopeForPeriod(Builder $query, string $periodType, ?Carbon $date = null): Builder
    {
        $date = $date ?? now();

        return $query->where('period_type', $periodType)
            ->where('period_start', '<=', $date)
            ->where('period_end', '>=', $date);
    }

    /**
     * Foydalanuvchi bo'yicha filter
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Joriy oy maqsadlari
     */
    public function scopeCurrentMonth(Builder $query): Builder
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $query->where('period_type', 'monthly')
            ->where('period_start', $startOfMonth->format('Y-m-d'))
            ->where('period_end', $endOfMonth->format('Y-m-d'));
    }

    /**
     * Joriy hafta maqsadlari
     */
    public function scopeCurrentWeek(Builder $query): Builder
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return $query->where('period_type', 'weekly')
            ->where('period_start', $startOfWeek->format('Y-m-d'))
            ->where('period_end', $endOfWeek->format('Y-m-d'));
    }

    /**
     * Bugungi maqsadlar
     */
    public function scopeToday(Builder $query): Builder
    {
        $today = now()->format('Y-m-d');

        return $query->where('period_type', 'daily')
            ->where('period_start', $today);
    }

    /**
     * Maqsad faolmi?
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        return $now->between($this->period_start, $this->period_end->endOfDay());
    }

    /**
     * Maqsad muddati o'tdimi?
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->period_end->endOfDay());
    }

    /**
     * Joriy maqsad qiymatini olish (adjusted yoki original)
     */
    public function getEffectiveTargetAttribute(): float
    {
        return $this->adjusted_target ?? $this->target_value;
    }

    /**
     * Achievement foizini hisoblash
     */
    public function calculateAchievementPercent(): float
    {
        if ($this->effective_target <= 0) {
            return 0;
        }

        return round(($this->achieved_value / $this->effective_target) * 100, 2);
    }

    /**
     * Ballni yangilash
     */
    public function updateScore(): void
    {
        $kpiSetting = $this->kpiSetting;

        if (! $kpiSetting) {
            return;
        }

        $this->achievement_percent = $this->calculateAchievementPercent();
        $this->score = $kpiSetting->calculateScore($this->achieved_value);
        $this->save();
    }

    /**
     * Achieved value ni yangilash
     */
    public function updateAchievedValue(float $value): void
    {
        $this->achieved_value = $value;
        $this->updateScore();
    }

    /**
     * Maqsadni o'zgartirish
     */
    public function adjustTarget(float $newTarget, ?string $reason = null): void
    {
        $this->adjusted_target = $newTarget;
        $this->adjustment_reason = $reason;
        $this->save();

        // Ballni qayta hisoblash
        $this->updateScore();
    }

    /**
     * Davr tugaganini belgilash
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Bekor qilish
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Performance darajasini olish
     */
    public function getPerformanceLevelAttribute(): string
    {
        $kpiSetting = $this->kpiSetting;

        if (! $kpiSetting) {
            return 'unknown';
        }

        return $kpiSetting->getPerformanceLevel($this->achieved_value);
    }

    /**
     * Performance darajasi labelini olish
     */
    public function getPerformanceLevelLabelAttribute(): string
    {
        return SalesKpiSetting::getPerformanceLevelLabel($this->performance_level);
    }

    /**
     * Qolgan kunlar soni
     */
    public function getRemainingDaysAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->period_end, false);
    }

    /**
     * Progress color (UI uchun)
     */
    public function getProgressColorAttribute(): string
    {
        return match (true) {
            $this->achievement_percent >= 100 => 'green',
            $this->achievement_percent >= 80 => 'yellow',
            $this->achievement_percent >= 50 => 'orange',
            default => 'red',
        };
    }

    /**
     * Davr formatini olish
     */
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            'daily' => $this->period_start->format('d.m.Y'),
            'weekly' => $this->period_start->format('d.m').' - '.$this->period_end->format('d.m.Y'),
            'monthly' => $this->period_start->translatedFormat('F Y'),
            default => $this->period_start->format('d.m.Y').' - '.$this->period_end->format('d.m.Y'),
        };
    }

    /**
     * Holat labelini olish
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
