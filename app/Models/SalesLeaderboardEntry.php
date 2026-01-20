<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesLeaderboardEntry extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Medal turlari
     */
    public const MEDALS = [
        'gold' => [
            'name' => 'Oltin',
            'rank' => 1,
            'points' => 100,
            'icon' => '🥇',
        ],
        'silver' => [
            'name' => 'Kumush',
            'rank' => 2,
            'points' => 50,
            'icon' => '🥈',
        ],
        'bronze' => [
            'name' => 'Bronza',
            'rank' => 3,
            'points' => 25,
            'icon' => '🥉',
        ],
    ];

    /**
     * Davr turlari
     */
    public const PERIOD_TYPES = [
        'daily' => 'Kunlik',
        'weekly' => 'Haftalik',
        'monthly' => 'Oylik',
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'rank',
        'previous_rank',
        'rank_change',
        'total_score',
        'weighted_score',
        'kpi_scores',
        'leads_converted',
        'revenue',
        'calls_made',
        'tasks_completed',
        'conversion_rate',
        'avg_deal_size',
        'medal',
        'is_top_performer',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'rank' => 'integer',
        'previous_rank' => 'integer',
        'rank_change' => 'integer',
        'total_score' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'kpi_scores' => 'array',
        'leads_converted' => 'integer',
        'revenue' => 'decimal:2',
        'calls_made' => 'integer',
        'tasks_completed' => 'integer',
        'conversion_rate' => 'decimal:2',
        'avg_deal_size' => 'decimal:2',
        'is_top_performer' => 'boolean',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Davr bo'yicha filter
     */
    public function scopeForPeriod(Builder $query, string $periodType, ?Carbon $date = null): Builder
    {
        $date = $date ?? now();

        [$start, $end] = $this->getPeriodDates($periodType, $date);

        return $query->where('period_type', $periodType)
            ->where('period_start', $start->format('Y-m-d'));
    }

    /**
     * Joriy hafta
     */
    public function scopeCurrentWeek(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly')
            ->where('period_start', now()->startOfWeek()->format('Y-m-d'));
    }

    /**
     * Joriy oy
     */
    public function scopeCurrentMonth(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly')
            ->where('period_start', now()->startOfMonth()->format('Y-m-d'));
    }

    /**
     * Bugun
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('period_type', 'daily')
            ->where('period_start', now()->format('Y-m-d'));
    }

    /**
     * Reytingga qarab tartiblash
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderBy('rank');
    }

    /**
     * Top N foydalanuvchi
     */
    public function scopeTop(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('rank')->limit($limit);
    }

    /**
     * Medal olganlar
     */
    public function scopeWithMedal(Builder $query): Builder
    {
        return $query->whereNotNull('medal');
    }

    /**
     * Foydalanuvchi bo'yicha
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Davr sanalarini olish
     */
    protected function getPeriodDates(string $periodType, Carbon $date): array
    {
        return match ($periodType) {
            'daily' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'weekly' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'monthly' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
        };
    }

    /**
     * Medal ma'lumotlarini olish
     */
    public function getMedalInfoAttribute(): ?array
    {
        if (! $this->medal) {
            return null;
        }

        return self::MEDALS[$this->medal] ?? null;
    }

    /**
     * Medal nomini olish
     */
    public function getMedalLabelAttribute(): ?string
    {
        return $this->medal_info['name'] ?? null;
    }

    /**
     * Reyting o'zgarishi belgisi
     */
    public function getRankChangeIndicatorAttribute(): string
    {
        if ($this->rank_change > 0) {
            return '↑'.$this->rank_change;
        } elseif ($this->rank_change < 0) {
            return '↓'.abs($this->rank_change);
        }

        return '−';
    }

    /**
     * Reyting o'zgarishi rangi
     */
    public function getRankChangeColorAttribute(): string
    {
        if ($this->rank_change > 0) {
            return 'green';
        } elseif ($this->rank_change < 0) {
            return 'red';
        }

        return 'gray';
    }

    /**
     * Davr labelini olish
     */
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            'daily' => $this->period_start->format('d.m.Y'),
            'weekly' => $this->period_start->format('d.m').' - '.$this->period_end->format('d.m.Y'),
            'monthly' => $this->period_start->translatedFormat('F Y'),
            default => $this->period_start->format('d.m.Y'),
        };
    }

    /**
     * Formatli revenue
     */
    public function getFormattedRevenueAttribute(): string
    {
        return number_format($this->revenue, 0, '.', ' ').' so\'m';
    }

    /**
     * Top performermi?
     */
    public function isTopPerformer(): bool
    {
        return $this->is_top_performer || $this->rank <= 3;
    }

    /**
     * Oldingi davr bilan solishtirish uchun ma'lumot
     */
    public static function getComparison(string $businessId, string $userId, string $periodType): array
    {
        $now = now();
        $currentEntry = self::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriod($periodType, $now)
            ->first();

        $previousDate = match ($periodType) {
            'daily' => $now->copy()->subDay(),
            'weekly' => $now->copy()->subWeek(),
            'monthly' => $now->copy()->subMonth(),
            default => $now->copy()->subDay(),
        };

        $previousEntry = self::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriod($periodType, $previousDate)
            ->first();

        return [
            'current' => $currentEntry,
            'previous' => $previousEntry,
            'rank_improved' => $previousEntry && $currentEntry
                ? $previousEntry->rank > $currentEntry->rank
                : false,
            'score_improved' => $previousEntry && $currentEntry
                ? $currentEntry->total_score > $previousEntry->total_score
                : false,
        ];
    }
}
