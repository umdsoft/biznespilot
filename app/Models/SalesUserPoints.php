<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesUserPoints extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Daraja tizimi
     */
    public const LEVELS = [
        1 => ['name' => 'Yangi', 'xp_required' => 0, 'color' => '#9CA3AF'],
        2 => ['name' => 'Boshlang\'ich', 'xp_required' => 100, 'color' => '#10B981'],
        3 => ['name' => 'O\'rta', 'xp_required' => 300, 'color' => '#3B82F6'],
        4 => ['name' => 'Tajribali', 'xp_required' => 600, 'color' => '#8B5CF6'],
        5 => ['name' => 'Professional', 'xp_required' => 1000, 'color' => '#F59E0B'],
        6 => ['name' => 'Expert', 'xp_required' => 1500, 'color' => '#EF4444'],
        7 => ['name' => 'Master', 'xp_required' => 2500, 'color' => '#EC4899'],
        8 => ['name' => 'Grand Master', 'xp_required' => 4000, 'color' => '#F97316'],
        9 => ['name' => 'Legend', 'xp_required' => 6000, 'color' => '#14B8A6'],
        10 => ['name' => 'Champion', 'xp_required' => 10000, 'color' => '#FFD700'],
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'total_points',
        'available_points',
        'spent_points',
        'level',
        'experience',
        'next_level_xp',
        'achievements_count',
        'best_rank',
        'gold_medals',
        'silver_medals',
        'bronze_medals',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'available_points' => 'integer',
        'spent_points' => 'integer',
        'level' => 'integer',
        'experience' => 'integer',
        'next_level_xp' => 'integer',
        'achievements_count' => 'integer',
        'best_rank' => 'integer',
        'gold_medals' => 'integer',
        'silver_medals' => 'integer',
        'bronze_medals' => 'integer',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tranzaksiyalar
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SalesPointsTransaction::class, 'user_points_id');
    }

    /**
     * Foydalanuvchi bo'yicha
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Darajaga qarab tartiblash
     */
    public function scopeByLevel(Builder $query): Builder
    {
        return $query->orderByDesc('level')->orderByDesc('experience');
    }

    /**
     * Ballga qarab tartiblash
     */
    public function scopeByPoints(Builder $query): Builder
    {
        return $query->orderByDesc('total_points');
    }

    /**
     * Daraja ma'lumotlarini olish
     */
    public function getLevelInfoAttribute(): array
    {
        return self::LEVELS[$this->level] ?? self::LEVELS[1];
    }

    /**
     * Daraja nomini olish
     */
    public function getLevelNameAttribute(): string
    {
        return $this->level_info['name'];
    }

    /**
     * Daraja rangini olish
     */
    public function getLevelColorAttribute(): string
    {
        return $this->level_info['color'];
    }

    /**
     * Keyingi daraja ma'lumotlari
     */
    public function getNextLevelInfoAttribute(): ?array
    {
        $nextLevel = $this->level + 1;

        return self::LEVELS[$nextLevel] ?? null;
    }

    /**
     * Keyingi darajagacha foiz
     */
    public function getLevelProgressAttribute(): float
    {
        if (! $this->next_level_info) {
            return 100;
        }

        $currentLevelXp = self::LEVELS[$this->level]['xp_required'];
        $nextLevelXp = $this->next_level_info['xp_required'];
        $range = $nextLevelXp - $currentLevelXp;

        if ($range <= 0) {
            return 100;
        }

        $progress = $this->experience - $currentLevelXp;

        return min(100, round(($progress / $range) * 100, 1));
    }

    /**
     * Jami medallar
     */
    public function getTotalMedalsAttribute(): int
    {
        return $this->gold_medals + $this->silver_medals + $this->bronze_medals;
    }

    /**
     * Ball qo'shish
     */
    public static function addPoints(
        string $businessId,
        string $userId,
        int $points,
        string $source,
        ?string $sourceId = null,
        ?string $description = null
    ): void {
        $userPoints = self::getOrCreate($businessId, $userId);

        $userPoints->total_points += $points;
        $userPoints->available_points += $points;
        $userPoints->experience += $points;

        // Daraja tekshirish
        $userPoints->checkLevelUp();
        $userPoints->save();

        // Tranzaksiya yozish
        SalesPointsTransaction::create([
            'user_points_id' => $userPoints->id,
            'type' => 'earned',
            'source' => $source,
            'source_id' => $sourceId,
            'points' => $points,
            'balance_after' => $userPoints->available_points,
            'description' => $description,
        ]);
    }

    /**
     * Ball ayirish (sarflash)
     */
    public function spendPoints(int $points, string $source, ?string $description = null): bool
    {
        if ($this->available_points < $points) {
            return false;
        }

        $this->available_points -= $points;
        $this->spent_points += $points;
        $this->save();

        SalesPointsTransaction::create([
            'user_points_id' => $this->id,
            'type' => 'spent',
            'source' => $source,
            'points' => -$points,
            'balance_after' => $this->available_points,
            'description' => $description,
        ]);

        return true;
    }

    /**
     * Daraja tekshirish va oshirish
     */
    protected function checkLevelUp(): void
    {
        $maxLevel = max(array_keys(self::LEVELS));

        while ($this->level < $maxLevel) {
            $nextLevel = $this->level + 1;
            $nextLevelXp = self::LEVELS[$nextLevel]['xp_required'];

            if ($this->experience >= $nextLevelXp) {
                $this->level = $nextLevel;
                $this->next_level_xp = self::LEVELS[$nextLevel + 1]['xp_required'] ?? null;
            } else {
                break;
            }
        }
    }

    /**
     * Yutuqlar sonini oshirish
     */
    public static function incrementAchievementsCount(string $businessId, string $userId): void
    {
        $userPoints = self::getOrCreate($businessId, $userId);
        $userPoints->increment('achievements_count');
    }

    /**
     * Medal qo'shish
     */
    public static function addMedal(string $businessId, string $userId, string $medal): void
    {
        $userPoints = self::getOrCreate($businessId, $userId);

        $column = match ($medal) {
            'gold' => 'gold_medals',
            'silver' => 'silver_medals',
            'bronze' => 'bronze_medals',
            default => null,
        };

        if ($column) {
            $userPoints->increment($column);
        }

        // Best rank yangilash (gold = 1, silver = 2, bronze = 3)
        $rank = match ($medal) {
            'gold' => 1,
            'silver' => 2,
            'bronze' => 3,
            default => null,
        };

        if ($rank && (! $userPoints->best_rank || $rank < $userPoints->best_rank)) {
            $userPoints->update(['best_rank' => $rank]);
        }

        // Medal uchun ball
        $medalPoints = SalesLeaderboardEntry::MEDALS[$medal]['points'] ?? 0;
        if ($medalPoints > 0) {
            self::addPoints($businessId, $userId, $medalPoints, 'medal', null, "Medal: $medal");
        }
    }

    /**
     * Foydalanuvchi uchun points olish yoki yaratish
     */
    public static function getOrCreate(string $businessId, string $userId): self
    {
        return self::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
            ],
            [
                'total_points' => 0,
                'available_points' => 0,
                'spent_points' => 0,
                'level' => 1,
                'experience' => 0,
                'next_level_xp' => self::LEVELS[2]['xp_required'],
                'achievements_count' => 0,
                'gold_medals' => 0,
                'silver_medals' => 0,
                'bronze_medals' => 0,
            ]
        );
    }

    /**
     * Leaderboard (pointlar bo'yicha)
     */
    public static function getLeaderboard(string $businessId, int $limit = 10): \Illuminate\Support\Collection
    {
        return self::forBusiness($businessId)
            ->with('user:id,name')
            ->byPoints()
            ->limit($limit)
            ->get()
            ->map(function ($entry, $index) {
                $entry->rank = $index + 1;

                return $entry;
            });
    }
}
