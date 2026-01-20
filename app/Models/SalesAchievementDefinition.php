<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesAchievementDefinition extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    /**
     * Kategoriyalar
     */
    public const CATEGORIES = [
        'sales' => 'Sotuv',
        'activity' => 'Faoliyat',
        'quality' => 'Sifat',
        'streak' => 'Streak',
        'milestone' => 'Bosqich',
        'special' => 'Maxsus',
    ];

    /**
     * Darajalar (tier)
     */
    public const TIERS = [
        'bronze' => [
            'name' => 'Bronza',
            'color' => '#CD7F32',
            'points_multiplier' => 1.0,
        ],
        'silver' => [
            'name' => 'Kumush',
            'color' => '#C0C0C0',
            'points_multiplier' => 1.5,
        ],
        'gold' => [
            'name' => 'Oltin',
            'color' => '#FFD700',
            'points_multiplier' => 2.0,
        ],
        'platinum' => [
            'name' => 'Platina',
            'color' => '#E5E4E2',
            'points_multiplier' => 3.0,
        ],
        'diamond' => [
            'name' => 'Olmos',
            'color' => '#B9F2FF',
            'points_multiplier' => 5.0,
        ],
    ];

    /**
     * Trigger turlari
     */
    public const TRIGGER_TYPES = [
        'threshold' => 'Chegara (bir kunda)',
        'cumulative' => 'Jami (umumiy)',
        'streak' => 'Ketma-ket',
        'milestone' => 'Bosqich',
        'special' => 'Maxsus',
    ];

    /**
     * Metrikalar
     */
    public const METRICS = [
        'leads_converted' => 'Sotuvga o\'tgan lidlar',
        'revenue' => 'Sotuv summasi',
        'calls_made' => 'Qilingan qo\'ng\'iroqlar',
        'tasks_completed' => 'Bajarilgan vazifalar',
        'meetings_held' => 'O\'tkazilgan uchrashuvlar',
        'kpi_score' => 'KPI ball',
        'streak_days' => 'Streak kunlari',
        'gold_medals' => 'Oltin medallar',
        'first_place_count' => 'Birinchi o\'rin',
    ];

    /**
     * Tizim yutuqlari (har bir biznes uchun avtomatik yaratiladi)
     */
    public const SYSTEM_ACHIEVEMENTS = [
        // Sotuv yutuqlari
        [
            'code' => 'first_sale',
            'name' => 'Birinchi sotuv',
            'description' => 'Birinchi lidni sotuvga o\'tkazdingiz',
            'category' => 'sales',
            'tier' => 'bronze',
            'points' => 50,
            'trigger_type' => 'threshold',
            'metric' => 'leads_converted',
            'target_value' => 1,
        ],
        [
            'code' => 'sales_10',
            'name' => 'Sotuvchi',
            'description' => '10 ta lid sotuvga o\'tkazildi',
            'category' => 'sales',
            'tier' => 'bronze',
            'points' => 100,
            'trigger_type' => 'cumulative',
            'metric' => 'leads_converted',
            'target_value' => 10,
        ],
        [
            'code' => 'sales_50',
            'name' => 'Professional sotuvchi',
            'description' => '50 ta lid sotuvga o\'tkazildi',
            'category' => 'sales',
            'tier' => 'silver',
            'points' => 250,
            'trigger_type' => 'cumulative',
            'metric' => 'leads_converted',
            'target_value' => 50,
        ],
        [
            'code' => 'sales_100',
            'name' => 'Sotuv ustasi',
            'description' => '100 ta lid sotuvga o\'tkazildi',
            'category' => 'sales',
            'tier' => 'gold',
            'points' => 500,
            'trigger_type' => 'cumulative',
            'metric' => 'leads_converted',
            'target_value' => 100,
        ],
        [
            'code' => 'sales_500',
            'name' => 'Sotuv legendasi',
            'description' => '500 ta lid sotuvga o\'tkazildi',
            'category' => 'sales',
            'tier' => 'diamond',
            'points' => 2000,
            'trigger_type' => 'cumulative',
            'metric' => 'leads_converted',
            'target_value' => 500,
        ],

        // Faoliyat yutuqlari
        [
            'code' => 'calls_100',
            'name' => 'Telefon ustasi',
            'description' => '100 ta qo\'ng\'iroq amalga oshirildi',
            'category' => 'activity',
            'tier' => 'bronze',
            'points' => 100,
            'trigger_type' => 'cumulative',
            'metric' => 'calls_made',
            'target_value' => 100,
        ],
        [
            'code' => 'calls_1000',
            'name' => 'Qo\'ng\'iroq mashinasi',
            'description' => '1000 ta qo\'ng\'iroq amalga oshirildi',
            'category' => 'activity',
            'tier' => 'gold',
            'points' => 500,
            'trigger_type' => 'cumulative',
            'metric' => 'calls_made',
            'target_value' => 1000,
        ],
        [
            'code' => 'tasks_100',
            'name' => 'Vazifa bajargich',
            'description' => '100 ta vazifa bajarildi',
            'category' => 'activity',
            'tier' => 'silver',
            'points' => 200,
            'trigger_type' => 'cumulative',
            'metric' => 'tasks_completed',
            'target_value' => 100,
        ],

        // Streak yutuqlari
        [
            'code' => 'streak_7',
            'name' => 'Haftalik streak',
            'description' => '7 kun ketma-ket maqsadga yetdingiz',
            'category' => 'streak',
            'tier' => 'bronze',
            'points' => 100,
            'trigger_type' => 'streak',
            'metric' => 'streak_days',
            'target_value' => 7,
        ],
        [
            'code' => 'streak_30',
            'name' => 'Oylik streak',
            'description' => '30 kun ketma-ket maqsadga yetdingiz',
            'category' => 'streak',
            'tier' => 'gold',
            'points' => 500,
            'trigger_type' => 'streak',
            'metric' => 'streak_days',
            'target_value' => 30,
        ],
        [
            'code' => 'streak_100',
            'name' => 'Streak monsteri',
            'description' => '100 kun ketma-ket maqsadga yetdingiz',
            'category' => 'streak',
            'tier' => 'diamond',
            'points' => 2000,
            'trigger_type' => 'streak',
            'metric' => 'streak_days',
            'target_value' => 100,
        ],

        // Milestone yutuqlari
        [
            'code' => 'first_gold',
            'name' => 'Oltin medal',
            'description' => 'Birinchi oltin medal oldingiz',
            'category' => 'milestone',
            'tier' => 'gold',
            'points' => 300,
            'trigger_type' => 'threshold',
            'metric' => 'gold_medals',
            'target_value' => 1,
        ],
        [
            'code' => 'champion',
            'name' => 'Chempion',
            'description' => '5 marta birinchi o\'rin oldingiz',
            'category' => 'milestone',
            'tier' => 'platinum',
            'points' => 1000,
            'trigger_type' => 'cumulative',
            'metric' => 'first_place_count',
            'target_value' => 5,
        ],

        // KPI yutuqlari
        [
            'code' => 'perfect_day',
            'name' => 'Mukammal kun',
            'description' => 'Bir kunda 100% KPI ball oldingiz',
            'category' => 'quality',
            'tier' => 'silver',
            'points' => 150,
            'trigger_type' => 'threshold',
            'metric' => 'kpi_score',
            'target_value' => 100,
        ],
    ];

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'description',
        'icon',
        'category',
        'tier',
        'points',
        'trigger_type',
        'metric',
        'target_value',
        'conditions',
        'is_repeatable',
        'max_times',
        'is_active',
        'is_system',
        'is_secret',
        'sort_order',
    ];

    protected $casts = [
        'points' => 'integer',
        'target_value' => 'decimal:2',
        'conditions' => 'array',
        'is_repeatable' => 'boolean',
        'max_times' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_secret' => 'boolean',
    ];

    /**
     * Foydalanuvchi yutuqlari
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(SalesUserAchievement::class, 'achievement_id');
    }

    /**
     * Faol yutuqlar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Kategoriya bo'yicha
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Tizim yutuqlari
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Ochiq yutuqlar (yashirin emaslar)
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_secret', false);
    }

    /**
     * Metrika bo'yicha
     */
    public function scopeForMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric', $metric);
    }

    /**
     * Tartiblangan
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('tier')->orderBy('points');
    }

    /**
     * Kategoriya labelini olish
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Tier ma'lumotlarini olish
     */
    public function getTierInfoAttribute(): array
    {
        return self::TIERS[$this->tier] ?? self::TIERS['bronze'];
    }

    /**
     * Tier labelini olish
     */
    public function getTierLabelAttribute(): string
    {
        return $this->tier_info['name'];
    }

    /**
     * Tier rangini olish
     */
    public function getTierColorAttribute(): string
    {
        return $this->tier_info['color'];
    }

    /**
     * Metrika labelini olish
     */
    public function getMetricLabelAttribute(): string
    {
        return self::METRICS[$this->metric] ?? $this->metric;
    }

    /**
     * Trigger turi labelini olish
     */
    public function getTriggerTypeLabelAttribute(): string
    {
        return self::TRIGGER_TYPES[$this->trigger_type] ?? $this->trigger_type;
    }

    /**
     * Foydalanuvchi bu yutuqni olganmi tekshirish
     */
    public function isEarnedByUser(string $userId): bool
    {
        return $this->userAchievements()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Foydalanuvchi progressini tekshirish
     */
    public function getUserProgress(string $userId, float $currentValue): array
    {
        $earned = $this->isEarnedByUser($userId);
        $percent = min(100, ($currentValue / $this->target_value) * 100);

        return [
            'earned' => $earned,
            'current_value' => $currentValue,
            'target_value' => $this->target_value,
            'percent' => round($percent, 1),
            'remaining' => max(0, $this->target_value - $currentValue),
        ];
    }

    /**
     * Biznes uchun tizim yutuqlarini yaratish
     */
    public static function createSystemAchievements(string $businessId): void
    {
        foreach (self::SYSTEM_ACHIEVEMENTS as $achievement) {
            self::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'code' => $achievement['code'],
                ],
                array_merge($achievement, [
                    'is_system' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}
