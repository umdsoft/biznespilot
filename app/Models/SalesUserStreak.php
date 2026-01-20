<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesUserStreak extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Streak turlari
     */
    public const STREAK_TYPES = [
        'daily_target' => [
            'name' => 'Kunlik maqsad',
            'description' => 'Har kuni KPI maqsadga yetish',
            'milestone_days' => [7, 14, 30, 60, 100],
        ],
        'calls' => [
            'name' => 'Qo\'ng\'iroq streak',
            'description' => 'Har kuni kamida 1 qo\'ng\'iroq',
            'milestone_days' => [7, 14, 30],
        ],
        'tasks' => [
            'name' => 'Vazifa streak',
            'description' => 'Har kuni kamida 1 vazifa bajarish',
            'milestone_days' => [7, 14, 30],
        ],
        'leads' => [
            'name' => 'Lid streak',
            'description' => 'Har kuni kamida 1 lid ishlash',
            'milestone_days' => [7, 14, 30, 60],
        ],
        'login' => [
            'name' => 'Login streak',
            'description' => 'Har kuni tizimga kirish',
            'milestone_days' => [7, 30, 100, 365],
        ],
    ];

    /**
     * Streak multiplierlar
     */
    public const STREAK_MULTIPLIERS = [
        7 => 1.1,    // 7 kun = 10% bonus
        14 => 1.15,  // 14 kun = 15% bonus
        30 => 1.2,   // 30 kun = 20% bonus
        60 => 1.25,  // 60 kun = 25% bonus
        100 => 1.3,  // 100 kun = 30% bonus
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'streak_type',
        'current_streak',
        'streak_start_date',
        'last_activity_date',
        'best_streak',
        'best_streak_start',
        'best_streak_end',
        'total_streaks',
        'total_streak_days',
        'streak_multiplier',
        'is_frozen',
        'frozen_until',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'streak_start_date' => 'date',
        'last_activity_date' => 'date',
        'best_streak' => 'integer',
        'best_streak_start' => 'date',
        'best_streak_end' => 'date',
        'total_streaks' => 'integer',
        'total_streak_days' => 'integer',
        'streak_multiplier' => 'decimal:2',
        'is_frozen' => 'boolean',
        'frozen_until' => 'date',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Streak tarixlari
     */
    public function history(): HasMany
    {
        return $this->hasMany(SalesStreakHistory::class, 'streak_id');
    }

    /**
     * Foydalanuvchi bo'yicha
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Streak turi bo'yicha
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('streak_type', $type);
    }

    /**
     * Faol streaklar (0 dan katta)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('current_streak', '>', 0);
    }

    /**
     * Streak turi ma'lumotlarini olish
     */
    public function getTypeInfoAttribute(): array
    {
        return self::STREAK_TYPES[$this->streak_type] ?? [
            'name' => $this->streak_type,
            'description' => '',
            'milestone_days' => [],
        ];
    }

    /**
     * Streak turi nomini olish
     */
    public function getTypeNameAttribute(): string
    {
        return $this->type_info['name'];
    }

    /**
     * Streak faolmi?
     */
    public function isActive(): bool
    {
        if ($this->is_frozen) {
            return true;
        }

        if (! $this->last_activity_date) {
            return false;
        }

        // Kecha yoki bugun faoliyat bo'lishi kerak
        return $this->last_activity_date->isToday() ||
               $this->last_activity_date->isYesterday();
    }

    /**
     * Streak buzilish xavfidami?
     */
    public function isAtRisk(): bool
    {
        if ($this->is_frozen || $this->current_streak === 0) {
            return false;
        }

        // Agar oxirgi faoliyat kecha bo'lsa va bugun faoliyat yo'q bo'lsa
        return $this->last_activity_date?->isYesterday() ?? false;
    }

    /**
     * Keyingi milestone
     */
    public function getNextMilestoneAttribute(): ?int
    {
        $milestones = $this->type_info['milestone_days'] ?? [];

        foreach ($milestones as $milestone) {
            if ($this->current_streak < $milestone) {
                return $milestone;
            }
        }

        return null;
    }

    /**
     * Keyingi milestonegacha qolgan kunlar
     */
    public function getDaysToMilestoneAttribute(): ?int
    {
        $nextMilestone = $this->next_milestone;

        if (! $nextMilestone) {
            return null;
        }

        return $nextMilestone - $this->current_streak;
    }

    /**
     * Multiplier hisoblash
     */
    public function calculateMultiplier(): float
    {
        $multiplier = 1.0;

        foreach (self::STREAK_MULTIPLIERS as $days => $mult) {
            if ($this->current_streak >= $days) {
                $multiplier = $mult;
            }
        }

        return $multiplier;
    }

    /**
     * Streak ni oshirish
     */
    public function incrementStreak(): void
    {
        $today = now()->startOfDay();

        // Agar bugun allaqachon oshirilgan bo'lsa
        if ($this->last_activity_date?->isSameDay($today)) {
            return;
        }

        // Agar streak buzilgan bo'lsa (kechadan oldin)
        if ($this->last_activity_date && ! $this->last_activity_date->isYesterday() && ! $this->is_frozen) {
            $this->breakStreak();

            return;
        }

        // Yangi streak boshlanishi
        if ($this->current_streak === 0) {
            $this->streak_start_date = $today;
            $this->total_streaks++;
        }

        $this->current_streak++;
        $this->total_streak_days++;
        $this->last_activity_date = $today;
        $this->streak_multiplier = $this->calculateMultiplier();

        // Eng yaxshi streak yangilash
        if ($this->current_streak > $this->best_streak) {
            $this->best_streak = $this->current_streak;
            $this->best_streak_start = $this->streak_start_date;
            $this->best_streak_end = $today;
        }

        $this->save();

        // Tarixga yozish
        $this->logHistory('increment');

        // Milestone tekshirish
        $this->checkMilestoneAchievement();
    }

    /**
     * Streak ni buzish
     */
    public function breakStreak(): void
    {
        if ($this->current_streak > 0) {
            $this->logHistory('break');
        }

        $this->current_streak = 0;
        $this->streak_start_date = null;
        $this->streak_multiplier = 1.0;
        $this->save();
    }

    /**
     * Streak ni reset qilish (alias for breakStreak)
     */
    public function reset(): void
    {
        $this->breakStreak();
    }

    /**
     * Streak ni muzlatish (ta'til)
     */
    public function freeze(int $days): void
    {
        $this->is_frozen = true;
        $this->frozen_until = now()->addDays($days);
        $this->save();

        $this->logHistory('freeze', ['days' => $days]);
    }

    /**
     * Muzlatishni bekor qilish
     */
    public function unfreeze(): void
    {
        $this->is_frozen = false;
        $this->frozen_until = null;
        $this->last_activity_date = now()->subDay(); // Bugun davom etish uchun
        $this->save();

        $this->logHistory('unfreeze');
    }

    /**
     * Tarixga yozish
     */
    protected function logHistory(string $eventType, array $data = []): void
    {
        SalesStreakHistory::create([
            'streak_id' => $this->id,
            'event_type' => $eventType,
            'streak_value' => $this->current_streak,
            'event_date' => now(),
            'event_data' => $data,
        ]);
    }

    /**
     * Milestone yutuqini tekshirish
     */
    protected function checkMilestoneAchievement(): void
    {
        $milestones = $this->type_info['milestone_days'] ?? [];

        if (in_array($this->current_streak, $milestones)) {
            // Achievement berish
            $achievementCode = "streak_{$this->current_streak}";
            $achievement = SalesAchievementDefinition::forBusiness($this->business_id)
                ->where('code', $achievementCode)
                ->first();

            if ($achievement) {
                SalesUserAchievement::awardAchievement(
                    $this->business_id,
                    $this->user_id,
                    $achievement,
                    $this->current_streak,
                    ['streak_type' => $this->streak_type]
                );
            }
        }
    }

    /**
     * Foydalanuvchi uchun streak olish yoki yaratish
     */
    public static function getOrCreate(string $businessId, string $userId, string $streakType): self
    {
        return self::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'streak_type' => $streakType,
            ],
            [
                'current_streak' => 0,
                'best_streak' => 0,
                'total_streaks' => 0,
                'total_streak_days' => 0,
                'streak_multiplier' => 1.0,
            ]
        );
    }

    /**
     * Foydalanuvchi barcha streaklarini olish
     */
    public static function getUserStreaks(string $businessId, string $userId): array
    {
        $streaks = self::forBusiness($businessId)
            ->forUser($userId)
            ->get()
            ->keyBy('streak_type');

        $result = [];
        foreach (self::STREAK_TYPES as $type => $info) {
            $streak = $streaks->get($type);
            $result[$type] = [
                'info' => $info,
                'current' => $streak?->current_streak ?? 0,
                'best' => $streak?->best_streak ?? 0,
                'is_active' => $streak?->isActive() ?? false,
                'is_at_risk' => $streak?->isAtRisk() ?? false,
                'multiplier' => $streak?->streak_multiplier ?? 1.0,
                'next_milestone' => $streak?->next_milestone,
                'days_to_milestone' => $streak?->days_to_milestone,
            ];
        }

        return $result;
    }
}
