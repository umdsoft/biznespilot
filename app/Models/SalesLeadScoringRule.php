<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesLeadScoringRule extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Shart turlari
     */
    public const CONDITIONS = [
        'not_null' => 'Bo\'sh emas',
        'is_null' => 'Bo\'sh',
        'equals' => 'Teng',
        'not_equals' => 'Teng emas',
        'greater_than' => 'Katta',
        'less_than' => 'Kichik',
        'greater_or_equal' => 'Katta yoki teng',
        'less_or_equal' => 'Kichik yoki teng',
        'contains' => 'O\'z ichiga oladi',
        'not_contains' => 'O\'z ichiga olmaydi',
        'starts_with' => 'Boshlanadi',
        'ends_with' => 'Tugaydi',
    ];

    /**
     * Kategoriyalar
     */
    public const CATEGORIES = [
        'completeness' => [
            'name' => 'To\'liqlik',
            'description' => 'Ma\'lumotlar to\'liqligi',
            'color' => 'blue',
        ],
        'value' => [
            'name' => 'Qiymat',
            'description' => 'Potensial qiymat',
            'color' => 'green',
        ],
        'engagement' => [
            'name' => 'Faollik',
            'description' => 'Mijoz faolligi',
            'color' => 'purple',
        ],
        'source' => [
            'name' => 'Manba',
            'description' => 'Lead manbasi',
            'color' => 'yellow',
        ],
        'negative' => [
            'name' => 'Salbiy',
            'description' => 'Ball kamaytiruvchi',
            'color' => 'red',
        ],
    ];

    /**
     * Default qoidalar - barcha bizneslar uchun standart
     */
    public const DEFAULT_RULES = [
        // ========== COMPLETENESS (Ma'lumot to'liqligi) - 35 ball ==========
        [
            'name' => 'Telefon raqami mavjud',
            'description' => 'Lid telefon raqami bilan kelgan - aloqa qilish mumkin',
            'field' => 'phone',
            'condition' => 'not_null',
            'points' => 15,
            'category' => 'completeness',
        ],
        [
            'name' => 'Email mavjud',
            'description' => 'Email orqali marketing va follow-up yuborish mumkin',
            'field' => 'email',
            'condition' => 'not_null',
            'points' => 10,
            'category' => 'completeness',
        ],
        [
            'name' => 'Kompaniya nomi bor',
            'description' => 'B2B lid - kompaniya ma\'lumoti mavjud',
            'field' => 'company',
            'condition' => 'not_null',
            'points' => 10,
            'category' => 'completeness',
        ],

        // ========== VALUE (Potensial qiymat) - 30 ball ==========
        [
            'name' => 'Katta deal (50M+)',
            'description' => 'Taxminiy qiymat 50 million so\'mdan yuqori',
            'field' => 'estimated_value',
            'condition' => 'greater_than',
            'value' => '50000000',
            'value_type' => 'number',
            'points' => 20,
            'category' => 'value',
        ],
        [
            'name' => 'O\'rta deal (10M+)',
            'description' => 'Taxminiy qiymat 10 million so\'mdan yuqori',
            'field' => 'estimated_value',
            'condition' => 'greater_than',
            'value' => '10000000',
            'value_type' => 'number',
            'points' => 10,
            'category' => 'value',
        ],

        // ========== SOURCE (Manba sifati) - 25 ball ==========
        [
            'name' => 'Tavsiya orqali kelgan',
            'description' => 'Referral lid - yuqori konversiya ehtimoli',
            'field' => 'source.code',
            'condition' => 'equals',
            'value' => 'referral',
            'points' => 25,
            'category' => 'source',
        ],
        [
            'name' => 'Organik manba',
            'description' => 'Reklama orqali emas, o\'zi topib kelgan',
            'field' => 'source.code',
            'condition' => 'equals',
            'value' => 'organic',
            'points' => 15,
            'category' => 'source',
        ],

        // ========== ENGAGEMENT (Faollik) - 25 ball ==========
        [
            'name' => 'Faollik mavjud',
            'description' => 'Lid bilan aloqa bo\'lgan (qo\'ng\'iroq, uchrashuv)',
            'field' => 'activities_count',
            'condition' => 'greater_than',
            'value' => '0',
            'value_type' => 'number',
            'points' => 15,
            'category' => 'engagement',
        ],
        [
            'name' => 'Ko\'p faollik (3+)',
            'description' => '3 va undan ko\'p faollik - jiddiy qiziqish',
            'field' => 'activities_count',
            'condition' => 'greater_than',
            'value' => '2',
            'value_type' => 'number',
            'points' => 10,
            'category' => 'engagement',
        ],

        // ========== NEGATIVE (Salbiy signallar) - minus ball ==========
        [
            'name' => '3+ kun javobsiz',
            'description' => '3 kundan ko\'p vaqt o\'tdi, aloqa yo\'q',
            'field' => 'days_without_contact',
            'condition' => 'greater_than',
            'value' => '2',
            'value_type' => 'number',
            'points' => -10,
            'category' => 'negative',
        ],
        [
            'name' => '7+ kun javobsiz',
            'description' => 'Hafta davomida aloqa yo\'q - e\'tibor kerak',
            'field' => 'days_without_contact',
            'condition' => 'greater_than',
            'value' => '6',
            'value_type' => 'number',
            'points' => -15,
            'category' => 'negative',
        ],
        [
            'name' => '14+ kun javobsiz',
            'description' => 'Ikki hafta aloqa yo\'q - lid sovumoqda',
            'field' => 'days_without_contact',
            'condition' => 'greater_than',
            'value' => '13',
            'value_type' => 'number',
            'points' => -20,
            'category' => 'negative',
        ],
        [
            'name' => 'Yo\'qotilgan lid',
            'description' => 'Lid yo\'qotilgan deb belgilangan',
            'field' => 'lost_reason',
            'condition' => 'not_null',
            'points' => -50,
            'category' => 'negative',
        ],
    ];

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'field',
        'condition',
        'value',
        'value_type',
        'points',
        'is_active',
        'priority',
        'category',
    ];

    protected $casts = [
        'points' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Faol qoidalar
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
     * Priority bo'yicha tartiblash
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('category');
    }

    /**
     * Kategoriya ma'lumotlarini olish
     */
    public function getCategoryInfoAttribute(): array
    {
        return self::CATEGORIES[$this->category] ?? [
            'name' => $this->category,
            'description' => '',
            'color' => 'gray',
        ];
    }

    /**
     * Lead uchun qoidani baholash
     */
    public function evaluate(Lead $lead): int
    {
        $fieldValue = $this->getLeadFieldValue($lead, $this->field);
        $conditionValue = $this->castValue($this->value, $this->value_type);

        $matches = match ($this->condition) {
            'not_null' => ! is_null($fieldValue) && $fieldValue !== '',
            'is_null' => is_null($fieldValue) || $fieldValue === '',
            'equals' => $fieldValue == $conditionValue,
            'not_equals' => $fieldValue != $conditionValue,
            'greater_than' => is_numeric($fieldValue) && $fieldValue > $conditionValue,
            'less_than' => is_numeric($fieldValue) && $fieldValue < $conditionValue,
            'greater_or_equal' => is_numeric($fieldValue) && $fieldValue >= $conditionValue,
            'less_or_equal' => is_numeric($fieldValue) && $fieldValue <= $conditionValue,
            'contains' => is_string($fieldValue) && str_contains($fieldValue, $conditionValue ?? ''),
            'not_contains' => is_string($fieldValue) && ! str_contains($fieldValue, $conditionValue ?? ''),
            'starts_with' => is_string($fieldValue) && str_starts_with($fieldValue, $conditionValue ?? ''),
            'ends_with' => is_string($fieldValue) && str_ends_with($fieldValue, $conditionValue ?? ''),
            default => false,
        };

        return $matches ? $this->points : 0;
    }

    /**
     * Lead maydon qiymatini olish
     */
    protected function getLeadFieldValue(Lead $lead, string $field)
    {
        // Dot notation support (source.code, etc.)
        if (str_contains($field, '.')) {
            return data_get($lead, $field);
        }

        // Hisoblangan maydonlar
        return match ($field) {
            'activities_count' => $lead->activities()->count(),
            'days_without_contact' => $this->getDaysWithoutContact($lead),
            default => $lead->{$field} ?? null,
        };
    }

    /**
     * Oxirgi aloqadan beri o'tgan kunlar
     */
    protected function getDaysWithoutContact(Lead $lead): int
    {
        if ($lead->last_contacted_at) {
            return (int) $lead->last_contacted_at->diffInDays(now());
        }

        return (int) $lead->created_at->diffInDays(now());
    }

    /**
     * Qiymatni cast qilish
     */
    protected function castValue($value, string $type)
    {
        if (is_null($value)) {
            return null;
        }

        return match ($type) {
            'number' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => (string) $value,
        };
    }

    /**
     * Biznes uchun default qoidalarni yaratish
     */
    public static function initializeForBusiness(string $businessId): void
    {
        foreach (self::DEFAULT_RULES as $index => $ruleData) {
            self::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'name' => $ruleData['name'],
                ],
                [
                    'description' => $ruleData['description'] ?? null,
                    'field' => $ruleData['field'],
                    'condition' => $ruleData['condition'],
                    'value' => $ruleData['value'] ?? null,
                    'value_type' => $ruleData['value_type'] ?? 'string',
                    'points' => $ruleData['points'],
                    'category' => $ruleData['category'],
                    'priority' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Jami maksimal ball
     */
    public static function getMaxPossibleScore(): int
    {
        return collect(self::DEFAULT_RULES)
            ->where('points', '>', 0)
            ->sum('points');
    }
}
