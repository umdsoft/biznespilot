<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Meta/Google konversiyalarini haqiqiy sotuv bilan solishtirish uchun model
 */
class ConversionReconciliation extends Model
{
    use BelongsToBusiness, HasUuid;

    public const PLATFORMS = [
        'meta' => 'Meta (Facebook/Instagram)',
        'google' => 'Google Ads',
        'tiktok' => 'TikTok Ads',
        'yandex' => 'Yandex Direct',
    ];

    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'matched' => 'Mos keldi',
        'discrepancy' => 'Farq bor',
        'investigated' => 'Tekshirildi',
    ];

    protected $fillable = [
        'business_id',
        'reconciliation_date',
        'platform',
        'platform_campaign_id',
        'platform_adset_id',
        'platform_conversions',
        'platform_conversion_value',
        'actual_conversions',
        'actual_conversion_value',
        'conversion_discrepancy',
        'value_discrepancy',
        'discrepancy_percent',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'platform_conversions' => 'integer',
        'platform_conversion_value' => 'decimal:2',
        'actual_conversions' => 'integer',
        'actual_conversion_value' => 'decimal:2',
        'conversion_discrepancy' => 'integer',
        'value_discrepancy' => 'decimal:2',
        'discrepancy_percent' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Discrepancy ni hisoblash
     */
    public function calculateDiscrepancy(): void
    {
        $this->conversion_discrepancy = $this->platform_conversions - $this->actual_conversions;
        $this->value_discrepancy = $this->platform_conversion_value - $this->actual_conversion_value;

        if ($this->platform_conversions > 0) {
            $this->discrepancy_percent = abs($this->conversion_discrepancy / $this->platform_conversions) * 100;
        } else {
            $this->discrepancy_percent = 0;
        }

        // Auto-set status
        if ($this->discrepancy_percent <= 5) {
            $this->status = 'matched';
        } elseif ($this->discrepancy_percent <= 20) {
            $this->status = 'discrepancy';
        } else {
            $this->status = 'pending';
        }
    }

    /**
     * Platform nomi
     */
    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'matched' => 'green',
            'discrepancy' => 'yellow',
            'investigated' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Scope: Platform bo'yicha
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope: Sana oraligi
     */
    public function scopeForDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('reconciliation_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Farqi borlar
     */
    public function scopeWithDiscrepancy(Builder $query): Builder
    {
        return $query->where('discrepancy_percent', '>', 5);
    }

    /**
     * Scope: Tekshirilmagan
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
