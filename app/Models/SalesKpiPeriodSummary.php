<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesKpiPeriodSummary extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Performance darajalari
     */
    public const PERFORMANCE_TIERS = [
        'exceptional' => [
            'name' => 'A\'lo natija',
            'min_score' => 90,
            'color' => 'blue',
            'icon' => 'trophy',
        ],
        'excellent' => [
            'name' => 'Juda yaxshi',
            'min_score' => 75,
            'color' => 'green',
            'icon' => 'star',
        ],
        'good' => [
            'name' => 'Yaxshi',
            'min_score' => 60,
            'color' => 'teal',
            'icon' => 'thumb-up',
        ],
        'meets' => [
            'name' => 'Maqsadga yetdi',
            'min_score' => 45,
            'color' => 'yellow',
            'icon' => 'check',
        ],
        'developing' => [
            'name' => 'Rivojlanmoqda',
            'min_score' => 30,
            'color' => 'orange',
            'icon' => 'trending-up',
        ],
        'needs_improvement' => [
            'name' => 'Yaxshilash kerak',
            'min_score' => 0,
            'color' => 'red',
            'icon' => 'alert',
        ],
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'overall_score',
        'total_weight',
        'weighted_score',
        'kpi_scores',
        'rank_in_team',
        'previous_rank',
        'rank_change',
        'performance_tier',
        'working_days',
        'active_kpis_count',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'overall_score' => 'integer',
        'total_weight' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'kpi_scores' => 'array',
        'rank_in_team' => 'integer',
        'previous_rank' => 'integer',
        'rank_change' => 'integer',
        'working_days' => 'integer',
        'active_kpis_count' => 'integer',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Davr turi bo'yicha filter
     */
    public function scopeForPeriodType(Builder $query, string $periodType): Builder
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Oylik summarylar
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->forPeriodType('monthly');
    }

    /**
     * Haftalik summarylar
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->forPeriodType('weekly');
    }

    /**
     * Foydalanuvchi bo'yicha filter
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Sana bo'yicha filter (davr boshlanish sanasi)
     */
    public function scopeForPeriodStart(Builder $query, Carbon $date): Builder
    {
        return $query->where('period_start', $date->format('Y-m-d'));
    }

    /**
     * Joriy oy
     */
    public function scopeCurrentMonth(Builder $query): Builder
    {
        return $query->monthly()
            ->forPeriodStart(now()->startOfMonth());
    }

    /**
     * Joriy hafta
     */
    public function scopeCurrentWeek(Builder $query): Builder
    {
        return $query->weekly()
            ->forPeriodStart(now()->startOfWeek());
    }

    /**
     * Top N foydalanuvchilar
     */
    public function scopeTopPerformers(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('overall_score')->limit($limit);
    }

    /**
     * Performance tier labelini olish
     */
    public function getTierLabelAttribute(): string
    {
        return self::PERFORMANCE_TIERS[$this->performance_tier]['name'] ?? 'Noma\'lum';
    }

    /**
     * Performance tier rangini olish
     */
    public function getTierColorAttribute(): string
    {
        return self::PERFORMANCE_TIERS[$this->performance_tier]['color'] ?? 'gray';
    }

    /**
     * Performance tier iconini olish
     */
    public function getTierIconAttribute(): string
    {
        return self::PERFORMANCE_TIERS[$this->performance_tier]['icon'] ?? 'user';
    }

    /**
     * Rank o'zgarishi yo'nalishi
     */
    public function getRankTrendAttribute(): string
    {
        return match (true) {
            $this->rank_change > 0 => 'up',
            $this->rank_change < 0 => 'down',
            default => 'same',
        };
    }

    /**
     * Rank o'zgarishi formatlangan
     */
    public function getFormattedRankChangeAttribute(): string
    {
        if ($this->rank_change === 0) {
            return '-';
        }

        $prefix = $this->rank_change > 0 ? '+' : '';

        return $prefix.$this->rank_change;
    }

    /**
     * Davr formati
     */
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            'weekly' => $this->period_start->format('d.m').' - '.$this->period_end->format('d.m.Y'),
            'monthly' => $this->period_start->translatedFormat('F Y'),
            'quarterly' => 'Q'.ceil($this->period_start->month / 3).' '.$this->period_start->year,
            default => $this->period_start->format('d.m.Y').' - '.$this->period_end->format('d.m.Y'),
        };
    }

    /**
     * KPI score ni ID bo'yicha olish
     */
    public function getKpiScore(string $kpiSettingId): ?array
    {
        if (! $this->kpi_scores) {
            return null;
        }

        return collect($this->kpi_scores)
            ->firstWhere('kpi_setting_id', $kpiSettingId);
    }

    /**
     * Ball bo'yicha performance tierni aniqlash
     */
    public static function determinePerformanceTier(int $score): string
    {
        foreach (self::PERFORMANCE_TIERS as $tier => $config) {
            if ($score >= $config['min_score']) {
                return $tier;
            }
        }

        return 'needs_improvement';
    }

    /**
     * Oldingi davr summary ni olish
     */
    public function getPreviousPeriodSummary(): ?self
    {
        $previousPeriodStart = match ($this->period_type) {
            'weekly' => $this->period_start->copy()->subWeek(),
            'monthly' => $this->period_start->copy()->subMonth(),
            'quarterly' => $this->period_start->copy()->subQuarter(),
            default => null,
        };

        if (! $previousPeriodStart) {
            return null;
        }

        return static::forBusiness($this->business_id)
            ->forUser($this->user_id)
            ->forPeriodType($this->period_type)
            ->forPeriodStart($previousPeriodStart)
            ->first();
    }

    /**
     * O'sish foizini hisoblash
     */
    public function getGrowthPercentage(): ?float
    {
        $previous = $this->getPreviousPeriodSummary();

        if (! $previous || $previous->overall_score === 0) {
            return null;
        }

        return round(
            (($this->overall_score - $previous->overall_score) / $previous->overall_score) * 100,
            1
        );
    }
}
