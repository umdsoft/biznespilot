<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesKpiDailySnapshot extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'kpi_setting_id',
        'snapshot_date',
        'actual_value',
        'target_value',
        'achievement_percent',
        'score',
        'calculation_details',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'actual_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'achievement_percent' => 'decimal:2',
        'score' => 'integer',
        'calculation_details' => 'array',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * KPI sozlamasi
     */
    public function kpiSetting(): BelongsTo
    {
        return $this->belongsTo(SalesKpiSetting::class, 'kpi_setting_id');
    }

    /**
     * Sana bo'yicha filter
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('snapshot_date', $date->format('Y-m-d'));
    }

    /**
     * Sana oralig'i bo'yicha filter
     */
    public function scopeForDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('snapshot_date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * Foydalanuvchi bo'yicha filter
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * KPI turi bo'yicha filter
     */
    public function scopeForKpi(Builder $query, string $kpiSettingId): Builder
    {
        return $query->where('kpi_setting_id', $kpiSettingId);
    }

    /**
     * Bugungi snapshotlar
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->forDate(now());
    }

    /**
     * Shu hafta snapshotlari
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->forDateRange(now()->startOfWeek(), now()->endOfWeek());
    }

    /**
     * Shu oy snapshotlari
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->forDateRange(now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Maqsadga yetdimi?
     */
    public function isTargetMet(): bool
    {
        return $this->achievement_percent >= 100;
    }

    /**
     * Progress color
     */
    public function getProgressColorAttribute(): string
    {
        return match (true) {
            $this->achievement_percent >= 120 => 'blue',
            $this->achievement_percent >= 100 => 'green',
            $this->achievement_percent >= 80 => 'yellow',
            $this->achievement_percent >= 50 => 'orange',
            default => 'red',
        };
    }

    /**
     * Trend hisoblash (oxirgi 7 kun)
     */
    public static function getTrend(string $businessId, string $userId, string $kpiSettingId, int $days = 7): array
    {
        $snapshots = static::forBusiness($businessId)
            ->forUser($userId)
            ->forKpi($kpiSettingId)
            ->forDateRange(now()->subDays($days - 1), now())
            ->orderBy('snapshot_date')
            ->get(['snapshot_date', 'actual_value', 'achievement_percent', 'score']);

        return $snapshots->map(fn ($s) => [
            'date' => $s->snapshot_date->format('d.m'),
            'value' => $s->actual_value,
            'percent' => $s->achievement_percent,
            'score' => $s->score,
        ])->toArray();
    }

    /**
     * Foydalanuvchi uchun kunlik umumiy ball
     */
    public static function getDailyOverallScore(string $businessId, string $userId, Carbon $date): float
    {
        $snapshots = static::forBusiness($businessId)
            ->forUser($userId)
            ->forDate($date)
            ->with('kpiSetting:id,weight')
            ->get();

        if ($snapshots->isEmpty()) {
            return 0;
        }

        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($snapshots as $snapshot) {
            $weight = $snapshot->kpiSetting->weight ?? 0;
            $totalWeight += $weight;
            $weightedScore += $snapshot->score * ($weight / 100);
        }

        return $totalWeight > 0 ? round($weightedScore / ($totalWeight / 100), 1) : 0;
    }
}
