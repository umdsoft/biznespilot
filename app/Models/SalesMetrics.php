<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesMetrics extends Model
{
    use HasUuids;

    protected $table = 'sales_metrics';

    protected $fillable = [
        'business_id',
        'monthly_lead_volume',
        'lead_sources',
        'lead_quality',
        'monthly_sales_volume',
        'avg_deal_size',
        'sales_cycle',
        'sales_team_type',
        'sales_tools',
        'sales_challenges',
        'additional_data',
    ];

    protected $casts = [
        'lead_sources' => 'array',
        'sales_tools' => 'array',
        'additional_data' => 'array',
    ];

    // Lead hajmi opsiyalari
    public const LEAD_VOLUME_RANGES = [
        '0_10' => '0-10 (Kam)',
        '10_50' => '10-50 (O\'rtacha)',
        '50_200' => '50-200 (Ko\'p)',
        '200_plus' => '200+ (Juda ko\'p)',
    ];

    // Sotuv hajmi opsiyalari
    public const SALES_VOLUME_RANGES = [
        '0_10' => '0-10 (Kam)',
        '10_50' => '10-50 (O\'rtacha)',
        '50_100' => '50-100 (Ko\'p)',
        '100_plus' => '100+ (Juda ko\'p)',
    ];

    // Lead manbalari
    public const LEAD_SOURCES = [
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'facebook' => 'Facebook',
        'website' => 'Web-sayt',
        'referral' => 'Tavsiya',
        'cold_calls' => 'Sovuq qo\'ng\'iroq',
        'ads' => 'Reklama',
        'offline' => 'Oflayn',
        'other' => 'Boshqa',
    ];

    // Lead sifati
    public const LEAD_QUALITY_OPTIONS = [
        'low' => 'Past',
        'medium' => 'O\'rtacha',
        'high' => 'Yuqori',
    ];

    // Sotuv davri
    public const SALES_CYCLE_OPTIONS = [
        'same_day' => 'Bir kunda',
        '1_3_days' => '1-3 kun',
        '1_week' => 'Bir hafta',
        '2_weeks' => '2 hafta',
        '1_month' => 'Bir oy',
        'more_month' => 'Bir oydan ko\'p',
    ];

    // Sotuv jamoasi turi
    public const SALES_TEAM_TYPES = [
        'owner_only' => 'Faqat men o\'zim',
        'small_team' => '1-3 sotuvchi',
        'medium_team' => '4-10 sotuvchi',
        'large_team' => '10+ sotuvchi',
    ];

    // Sotuv vositalari
    public const SALES_TOOLS = [
        'excel' => 'Excel/Sheets',
        'crm' => 'CRM tizimi',
        'telegram_bot' => 'Telegram bot',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Telefon',
        'none' => 'Hech narsa',
    ];

    /**
     * Biznes bilan bog'lanish
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Tarix yozuvlari
     */
    public function history(): HasMany
    {
        return $this->hasMany(SalesMetricsHistory::class, 'sales_metrics_id')
            ->orderBy('recorded_at', 'desc');
    }

    /**
     * Tarixga saqlash
     */
    public function saveToHistory(string $changeType = 'update', ?string $note = null): SalesMetricsHistory
    {
        return SalesMetricsHistory::create([
            'business_id' => $this->business_id,
            'sales_metrics_id' => $this->id,
            'monthly_lead_volume' => $this->monthly_lead_volume,
            'lead_sources' => $this->lead_sources,
            'lead_quality' => $this->lead_quality,
            'monthly_sales_volume' => $this->monthly_sales_volume,
            'avg_deal_size' => $this->avg_deal_size,
            'sales_cycle' => $this->sales_cycle,
            'sales_team_type' => $this->sales_team_type,
            'sales_tools' => $this->sales_tools,
            'sales_challenges' => $this->sales_challenges,
            'additional_data' => $this->additional_data,
            'recorded_at' => now(),
            'change_type' => $changeType,
            'note' => $note,
        ]);
    }

    /**
     * Lead hajmi labelini olish
     */
    public function getLeadVolumeLabelAttribute(): ?string
    {
        return self::LEAD_VOLUME_RANGES[$this->monthly_lead_volume] ?? null;
    }

    /**
     * Sotuv hajmi labelini olish
     */
    public function getSalesVolumeLabelAttribute(): ?string
    {
        return self::SALES_VOLUME_RANGES[$this->monthly_sales_volume] ?? null;
    }

    /**
     * Lead sifati labelini olish
     */
    public function getLeadQualityLabelAttribute(): ?string
    {
        return self::LEAD_QUALITY_OPTIONS[$this->lead_quality] ?? null;
    }

    /**
     * Sotuv davri labelini olish
     */
    public function getSalesCycleLabelAttribute(): ?string
    {
        return self::SALES_CYCLE_OPTIONS[$this->sales_cycle] ?? null;
    }

    /**
     * Sotuv jamoasi labelini olish
     */
    public function getSalesTeamLabelAttribute(): ?string
    {
        return self::SALES_TEAM_TYPES[$this->sales_team_type] ?? null;
    }

    /**
     * Ma'lumotlar to'ldirilganmi
     */
    public function hasData(): bool
    {
        return !empty($this->monthly_lead_volume) ||
               !empty($this->lead_sources) ||
               !empty($this->monthly_sales_volume);
    }

    /**
     * To'liqlik foizi
     */
    public function getCompletionPercentAttribute(): int
    {
        $fields = [
            'monthly_lead_volume',
            'lead_sources',
            'lead_quality',
            'monthly_sales_volume',
            'avg_deal_size',
            'sales_cycle',
            'sales_team_type',
            'sales_tools',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            $value = $this->$field;
            if (!empty($value) && (!is_array($value) || count($value) > 0)) {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }
}
