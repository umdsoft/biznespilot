<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesUserAchievement extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'achievement_id',
        'earned_at',
        'times_earned',
        'achieved_value',
        'context_data',
        'points_awarded',
        'is_seen',
        'seen_at',
        'is_pinned',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'times_earned' => 'integer',
        'achieved_value' => 'decimal:2',
        'context_data' => 'array',
        'points_awarded' => 'integer',
        'is_seen' => 'boolean',
        'seen_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Yutuq ta'rifi
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(SalesAchievementDefinition::class, 'achievement_id');
    }

    /**
     * Foydalanuvchi bo'yicha
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Ko'rilmaganlar
     */
    public function scopeUnseen(Builder $query): Builder
    {
        return $query->where('is_seen', false);
    }

    /**
     * Pinned (profilda ko'rsatilgan)
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * So'nggi olinganlar
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('earned_at')->limit($limit);
    }

    /**
     * Kategoriya bo'yicha
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->whereHas('achievement', function ($q) use ($category) {
            $q->where('category', $category);
        });
    }

    /**
     * Ko'rilgan deb belgilash
     */
    public function markAsSeen(): void
    {
        if (! $this->is_seen) {
            $this->update([
                'is_seen' => true,
                'seen_at' => now(),
            ]);
        }
    }

    /**
     * Pin qilish/olib tashlash
     */
    public function togglePin(): void
    {
        $this->update([
            'is_pinned' => ! $this->is_pinned,
        ]);
    }

    /**
     * Yutuq berish
     */
    public static function awardAchievement(
        string $businessId,
        string $userId,
        SalesAchievementDefinition $achievement,
        float $achievedValue,
        array $contextData = []
    ): self {
        // Mavjud yutuqni tekshirish
        $existing = self::forBusiness($businessId)
            ->forUser($userId)
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($existing) {
            // Takrorlanadigan yutuq bo'lsa, sonini oshirish
            if ($achievement->is_repeatable) {
                $maxTimes = $achievement->max_times;
                if (! $maxTimes || $existing->times_earned < $maxTimes) {
                    $existing->increment('times_earned');
                    $existing->update([
                        'achieved_value' => $achievedValue,
                        'context_data' => $contextData,
                        'points_awarded' => $existing->points_awarded + $achievement->points,
                    ]);

                    // Points qo'shish
                    SalesUserPoints::addPoints(
                        $businessId,
                        $userId,
                        $achievement->points,
                        'achievement',
                        $achievement->id,
                        $achievement->name.' (takror)'
                    );
                }
            }

            return $existing;
        }

        // Yangi yutuq yaratish
        $userAchievement = self::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'achievement_id' => $achievement->id,
            'earned_at' => now(),
            'times_earned' => 1,
            'achieved_value' => $achievedValue,
            'context_data' => $contextData,
            'points_awarded' => $achievement->points,
            'is_seen' => false,
        ]);

        // Points qo'shish
        SalesUserPoints::addPoints(
            $businessId,
            $userId,
            $achievement->points,
            'achievement',
            $achievement->id,
            $achievement->name
        );

        // Yutuqlar sonini yangilash
        SalesUserPoints::incrementAchievementsCount($businessId, $userId);

        return $userAchievement;
    }

    /**
     * Foydalanuvchi yutuqlari statistikasi
     */
    public static function getUserStats(string $businessId, string $userId): array
    {
        $achievements = self::forBusiness($businessId)
            ->forUser($userId)
            ->with('achievement')
            ->get();

        $byCategory = [];
        foreach (SalesAchievementDefinition::CATEGORIES as $code => $name) {
            $byCategory[$code] = [
                'name' => $name,
                'count' => $achievements->filter(fn ($a) => $a->achievement?->category === $code)->count(),
            ];
        }

        $byTier = [];
        foreach (SalesAchievementDefinition::TIERS as $code => $info) {
            $byTier[$code] = [
                'name' => $info['name'],
                'color' => $info['color'],
                'count' => $achievements->filter(fn ($a) => $a->achievement?->tier === $code)->count(),
            ];
        }

        return [
            'total' => $achievements->count(),
            'total_points' => $achievements->sum('points_awarded'),
            'unseen_count' => $achievements->where('is_seen', false)->count(),
            'by_category' => $byCategory,
            'by_tier' => $byTier,
            'recent' => $achievements->sortByDesc('earned_at')->take(5)->values(),
            'pinned' => $achievements->where('is_pinned', true)->values(),
        ];
    }
}
