<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesKpiSetting extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    /**
     * KPI turlari va ularning o'zbekcha nomlari
     */
    public const KPI_TYPES = [
        // Natija KPIlar (Lagging)
        'leads_converted' => 'Sotuvga o\'tgan lidlar',
        'revenue' => 'Umumiy sotuv summasi',
        'deals_count' => 'Yopilgan bitimlar',
        'conversion_rate' => 'Konversiya foizi',
        'avg_deal_size' => 'O\'rtacha bitim summasi',

        // Faoliyat KPIlar (Leading)
        'calls_made' => 'Qilingan qo\'ng\'iroqlar',
        'calls_answered' => 'Javob berilgan qo\'ng\'iroqlar',
        'call_duration' => 'Umumiy suhbat vaqti',
        'tasks_completed' => 'Bajarilgan vazifalar',
        'meetings_held' => 'O\'tkazilgan uchrashuvlar',
        'proposals_sent' => 'Yuborilgan takliflar',

        // Sifat KPIlar
        'response_time' => 'O\'rtacha javob vaqti',
        'crm_compliance' => 'CRM to\'ldirilishi',
        'lead_touch_rate' => 'Lidlar bilan aloqa chastotasi',
        'lost_rate' => 'Yo\'qotilgan lidlar foizi',
    ];

    /**
     * KPI kategoriyalari
     */
    public const KPI_CATEGORIES = [
        'result' => [
            'name' => 'Natija KPIlar',
            'default_weight' => 50,
            'types' => ['leads_converted', 'revenue', 'deals_count', 'conversion_rate', 'avg_deal_size'],
        ],
        'activity' => [
            'name' => 'Faoliyat KPIlar',
            'default_weight' => 30,
            'types' => ['calls_made', 'calls_answered', 'call_duration', 'tasks_completed', 'meetings_held', 'proposals_sent'],
        ],
        'quality' => [
            'name' => 'Sifat KPIlar',
            'default_weight' => 20,
            'types' => ['response_time', 'crm_compliance', 'lead_touch_rate', 'lost_rate'],
        ],
    ];

    /**
     * O'lchov birliklari
     */
    public const MEASUREMENT_UNITS = [
        'count' => 'Soni',
        'currency' => 'Pul (so\'m)',
        'percentage' => 'Foiz (%)',
        'minutes' => 'Daqiqa',
        'hours' => 'Soat',
    ];

    /**
     * Hisoblash usullari
     */
    public const CALCULATION_METHODS = [
        'sum' => 'Yig\'indi',
        'average' => 'O\'rtacha',
        'count' => 'Soni',
        'rate' => 'Nisbat',
        'min' => 'Minimal',
        'max' => 'Maksimal',
    ];

    /**
     * Ma'lumot manbalari
     */
    public const DATA_SOURCES = [
        'leads' => 'Lidlar',
        'tasks' => 'Vazifalar',
        'calls' => 'Qo\'ng\'iroqlar',
        'orders' => 'Buyurtmalar',
        'manual' => 'Qo\'lda kiritish',
        'auto' => 'Avtomatik',
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
        'kpi_type',
        'name',
        'description',
        'measurement_unit',
        'calculation_method',
        'data_source',
        'period_type',
        'weight',
        'target_min',
        'target_good',
        'target_excellent',
        'scoring_formula',
        'applies_to_roles',
        'applies_to_departments',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'target_min' => 'decimal:2',
        'target_good' => 'decimal:2',
        'target_excellent' => 'decimal:2',
        'scoring_formula' => 'array',
        'applies_to_roles' => 'array',
        'applies_to_departments' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Foydalanuvchi maqsadlari
     */
    public function userTargets(): HasMany
    {
        return $this->hasMany(SalesKpiUserTarget::class, 'kpi_setting_id');
    }

    /**
     * Kunlik snapshotlar
     */
    public function dailySnapshots(): HasMany
    {
        return $this->hasMany(SalesKpiDailySnapshot::class, 'kpi_setting_id');
    }

    /**
     * Faqat faol KPIlar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * KPI turi bo'yicha filter
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('kpi_type', $type);
    }

    /**
     * Kategoriya bo'yicha filter
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        $types = self::KPI_CATEGORIES[$category]['types'] ?? [];

        return $query->whereIn('kpi_type', $types);
    }

    /**
     * Tartiblangan ro'yxat
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Rol uchun qo'llaniladigan KPIlar
     */
    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('applies_to_roles')
                ->orWhereJsonContains('applies_to_roles', $role);
        });
    }

    /**
     * KPI turi labelini olish
     */
    public function getTypeLabelAttribute(): string
    {
        return self::KPI_TYPES[$this->kpi_type] ?? $this->kpi_type;
    }

    /**
     * O'lchov birligi labelini olish
     */
    public function getMeasurementLabelAttribute(): string
    {
        return self::MEASUREMENT_UNITS[$this->measurement_unit] ?? $this->measurement_unit;
    }

    /**
     * Kategoriyani aniqlash
     */
    public function getCategoryAttribute(): ?string
    {
        foreach (self::KPI_CATEGORIES as $category => $data) {
            if (in_array($this->kpi_type, $data['types'])) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Kategoriya nomini olish
     */
    public function getCategoryLabelAttribute(): string
    {
        $category = $this->category;

        return self::KPI_CATEGORIES[$category]['name'] ?? 'Boshqa';
    }

    /**
     * Maqsad qiymatini formatlash
     */
    public function formatValue(float $value): string
    {
        return match ($this->measurement_unit) {
            'currency' => number_format($value, 0, '.', ' ').' so\'m',
            'percentage' => number_format($value, 1).'%',
            'minutes' => number_format($value, 0).' daq',
            'hours' => number_format($value, 1).' soat',
            default => number_format($value, 0),
        };
    }

    /**
     * Ball hisoblash (0-100)
     * Achievement: actual qiymatning target ga nisbati
     */
    public function calculateScore(float $actual): int
    {
        if (! $this->target_min || $this->target_min <= 0) {
            return 0;
        }

        // Actual qiymatning minimal maqsadga nisbati
        $achievementPercent = ($actual / $this->target_min) * 100;

        // Scoring formulasi:
        // 0-49% achievement = 0-25 ball
        // 50-79% achievement = 25-50 ball
        // 80-99% achievement = 50-70 ball
        // 100-119% achievement = 70-85 ball
        // 120%+ achievement = 85-100 ball

        return match (true) {
            $achievementPercent >= 120 => min(100, 85 + (int) (($achievementPercent - 120) / 20 * 15)),
            $achievementPercent >= 100 => 70 + (int) (($achievementPercent - 100) / 20 * 15),
            $achievementPercent >= 80 => 50 + (int) (($achievementPercent - 80) / 20 * 20),
            $achievementPercent >= 50 => 25 + (int) (($achievementPercent - 50) / 30 * 25),
            $achievementPercent > 0 => (int) ($achievementPercent / 50 * 25),
            default => 0,
        };
    }

    /**
     * Performance darajasini aniqlash
     */
    public function getPerformanceLevel(float $actual): string
    {
        if (! $this->target_min) {
            return 'unknown';
        }

        $percent = ($actual / $this->target_min) * 100;

        return match (true) {
            $percent >= 120 => 'exceptional',  // A'lo
            $this->target_excellent && $actual >= $this->target_excellent => 'excellent',
            $this->target_good && $actual >= $this->target_good => 'good',
            $actual >= $this->target_min => 'meets',      // Maqsadga yetdi
            $percent >= 80 => 'close',         // Yaqin
            $percent >= 50 => 'developing',    // Rivojlanmoqda
            default => 'needs_improvement',     // Yaxshilash kerak
        };
    }

    /**
     * Performance darajasi labelini olish
     */
    public static function getPerformanceLevelLabel(string $level): string
    {
        return match ($level) {
            'exceptional' => 'A\'lo natija',
            'excellent' => 'Juda yaxshi',
            'good' => 'Yaxshi',
            'meets' => 'Maqsadga yetdi',
            'close' => 'Yaqin',
            'developing' => 'Rivojlanmoqda',
            'needs_improvement' => 'Yaxshilash kerak',
            default => 'Noma\'lum',
        };
    }
}
