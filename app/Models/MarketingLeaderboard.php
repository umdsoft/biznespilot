<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingLeaderboard extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $table = 'marketing_leaderboard';

    protected $fillable = [
        'business_id',
        'user_id',
        'period_start',
        'period_end',
        'period_type',
        'overall_rank',
        'leads_rank',
        'conversion_rank',
        'roi_rank',
        'overall_score',
        'leads_score',
        'conversion_score',
        'roi_score',
        'achievements',
        'xp_earned',
        'coins_earned',
        'current_streak',
        'best_streak',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'overall_rank' => 'integer',
        'leads_rank' => 'integer',
        'conversion_rank' => 'integer',
        'roi_rank' => 'integer',
        'overall_score' => 'decimal:2',
        'leads_score' => 'decimal:2',
        'conversion_score' => 'decimal:2',
        'roi_score' => 'decimal:2',
        'achievements' => 'array',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'current_streak' => 'integer',
        'best_streak' => 'integer',
    ];

    // Achievements
    public const ACHIEVEMENT_TOP_PERFORMER = 'top_performer';
    public const ACHIEVEMENT_MOST_IMPROVED = 'most_improved';
    public const ACHIEVEMENT_STREAK_MASTER = 'streak_master';
    public const ACHIEVEMENT_ROI_CHAMPION = 'roi_champion';
    public const ACHIEVEMENT_LEAD_GENERATOR = 'lead_generator';
    public const ACHIEVEMENT_CONSISTENT = 'consistent';

    public const ACHIEVEMENTS = [
        self::ACHIEVEMENT_TOP_PERFORMER => ['name' => 'Top Performer', 'icon' => 'trophy', 'xp' => 100],
        self::ACHIEVEMENT_MOST_IMPROVED => ['name' => 'Eng ko\'p o\'sgan', 'icon' => 'trending-up', 'xp' => 50],
        self::ACHIEVEMENT_STREAK_MASTER => ['name' => 'Streak Master', 'icon' => 'fire', 'xp' => 75],
        self::ACHIEVEMENT_ROI_CHAMPION => ['name' => 'ROI Champion', 'icon' => 'chart-bar', 'xp' => 80],
        self::ACHIEVEMENT_LEAD_GENERATOR => ['name' => 'Lead Generator', 'icon' => 'users', 'xp' => 60],
        self::ACHIEVEMENT_CONSISTENT => ['name' => 'Barqaror', 'icon' => 'check-circle', 'xp' => 40],
    ];

    // RELATIONSHIPS

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // SCOPES

    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeForPeriod(Builder $query, $periodStart): Builder
    {
        return $query->where('period_start', $periodStart);
    }

    public function scopeTopPerformers(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('overall_rank')->limit($limit);
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // HELPERS

    public function isTopPerformer(): bool
    {
        return $this->overall_rank <= 3;
    }

    public function hasAchievement(string $achievement): bool
    {
        return in_array($achievement, $this->achievements ?? []);
    }

    public function getRankBadge(): string
    {
        return match ($this->overall_rank) {
            1 => '🥇',
            2 => '🥈',
            3 => '🥉',
            default => "#{$this->overall_rank}",
        };
    }

    public function getRankColor(): string
    {
        return match ($this->overall_rank) {
            1 => 'yellow',
            2 => 'gray',
            3 => 'orange',
            default => 'blue',
        };
    }

    public function addAchievement(string $achievement): void
    {
        $achievements = $this->achievements ?? [];
        if (!in_array($achievement, $achievements)) {
            $achievements[] = $achievement;
            $xpToAdd = self::ACHIEVEMENTS[$achievement]['xp'] ?? 0;
            $this->update([
                'achievements' => $achievements,
                'xp_earned' => $this->xp_earned + $xpToAdd,
            ]);
        }
    }
}
