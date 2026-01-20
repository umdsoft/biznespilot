<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesLeaderboardRecord extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Rekord turlari
     */
    public const RECORD_TYPES = [
        'highest_daily_score' => 'Eng yuqori kunlik ball',
        'highest_weekly_score' => 'Eng yuqori haftalik ball',
        'highest_monthly_score' => 'Eng yuqori oylik ball',
        'most_leads_day' => 'Eng ko\'p lid (kunlik)',
        'most_leads_week' => 'Eng ko\'p lid (haftalik)',
        'most_leads_month' => 'Eng ko\'p lid (oylik)',
        'highest_revenue_day' => 'Eng yuqori sotuv (kunlik)',
        'highest_revenue_week' => 'Eng yuqori sotuv (haftalik)',
        'highest_revenue_month' => 'Eng yuqori sotuv (oylik)',
        'most_calls_day' => 'Eng ko\'p qo\'ng\'iroq (kunlik)',
        'longest_streak' => 'Eng uzun streak',
        'fastest_first_sale' => 'Eng tez birinchi sotuv',
        'highest_conversion_rate' => 'Eng yuqori konversiya',
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'record_type',
        'record_period',
        'record_value',
        'achieved_at',
        'context_data',
        'is_current_record',
        'broken_by',
        'broken_at',
    ];

    protected $casts = [
        'record_value' => 'decimal:2',
        'achieved_at' => 'date',
        'context_data' => 'array',
        'is_current_record' => 'boolean',
        'broken_at' => 'datetime',
    ];

    /**
     * Rekord egasi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rekordni sindirgani
     */
    public function brokenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'broken_by');
    }

    /**
     * Hozirgi rekordlar
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current_record', true);
    }

    /**
     * Rekord turi bo'yicha
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('record_type', $type);
    }

    /**
     * Foydalanuvchi bo'yicha
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Rekord turi labelini olish
     */
    public function getTypeLabelAttribute(): string
    {
        return self::RECORD_TYPES[$this->record_type] ?? $this->record_type;
    }

    /**
     * Formatlangan qiymat
     */
    public function getFormattedValueAttribute(): string
    {
        // Rekord turiga qarab formatlash
        if (str_contains($this->record_type, 'revenue')) {
            return number_format($this->record_value, 0, '.', ' ').' so\'m';
        }

        if (str_contains($this->record_type, 'rate')) {
            return number_format($this->record_value, 1).'%';
        }

        if (str_contains($this->record_type, 'streak')) {
            return (int) $this->record_value.' kun';
        }

        return number_format($this->record_value, 0);
    }

    /**
     * Yangi rekord tekshirish va yaratish
     */
    public static function checkAndCreateRecord(
        string $businessId,
        string $userId,
        string $recordType,
        string $recordPeriod,
        float $value,
        array $contextData = []
    ): ?self {
        // Hozirgi rekordni topish
        $currentRecord = self::forBusiness($businessId)
            ->forType($recordType)
            ->where('record_period', $recordPeriod)
            ->current()
            ->first();

        // Rekord mavjud bo'lsa va yangi qiymat kattaroq bo'lsa
        if ($currentRecord) {
            if ($value <= $currentRecord->record_value) {
                return null; // Yangi rekord emas
            }

            // Eski rekordni yangilash
            $currentRecord->update([
                'is_current_record' => false,
                'broken_by' => $userId,
                'broken_at' => now(),
            ]);
        }

        // Yangi rekord yaratish
        return self::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'record_type' => $recordType,
            'record_period' => $recordPeriod,
            'record_value' => $value,
            'achieved_at' => now(),
            'context_data' => $contextData,
            'is_current_record' => true,
        ]);
    }

    /**
     * Biznes uchun barcha hozirgi rekordlar
     */
    public static function getAllCurrentRecords(string $businessId): array
    {
        $records = self::forBusiness($businessId)
            ->current()
            ->with('user:id,name')
            ->get();

        $grouped = [];
        foreach (self::RECORD_TYPES as $type => $label) {
            $record = $records->firstWhere('record_type', $type);
            $grouped[$type] = [
                'label' => $label,
                'record' => $record,
            ];
        }

        return $grouped;
    }
}
