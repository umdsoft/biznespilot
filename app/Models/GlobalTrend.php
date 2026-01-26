<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * GlobalTrend - Search Trends Data
 *
 * Stores global trends data per niche/region.
 * "Fetch Once, Serve Many" - cached for 7 days.
 *
 * @property string $id
 * @property string $niche
 * @property string $region_code
 * @property string $platform
 * @property \Carbon\Carbon $trend_date
 * @property array|null $data_json
 * @property array|null $top_keywords
 * @property array|null $rising_keywords
 * @property int $total_keywords
 * @property string $language
 * @property string|null $data_source
 * @property float $api_cost
 * @property bool $is_processed
 * @property \Carbon\Carbon|null $fetched_at
 * @property \Carbon\Carbon|null $expires_at
 */
class GlobalTrend extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'niche',
        'region_code',
        'platform',
        'trend_date',
        'data_json',
        'top_keywords',
        'rising_keywords',
        'total_keywords',
        'language',
        'data_source',
        'api_cost',
        'is_processed',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'data_json' => 'array',
        'top_keywords' => 'array',
        'rising_keywords' => 'array',
        'total_keywords' => 'integer',
        'api_cost' => 'decimal:4',
        'is_processed' => 'boolean',
        'trend_date' => 'date',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeForNiche($query, string $niche)
    {
        return $query->where('niche', $niche);
    }

    public function scopeForRegion($query, string $regionCode)
    {
        return $query->where('region_code', $regionCode);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeFresh($query, int $days = 7)
    {
        return $query->where('fetched_at', '>=', now()->subDays($days));
    }

    public function scopeStale($query, int $days = 7)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('fetched_at')
                ->orWhere('fetched_at', '<', now()->subDays($days));
        });
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getIsFreshAttribute(): bool
    {
        return $this->fetched_at && $this->fetched_at->diffInDays(now()) < 7;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function markAsProcessed(): void
    {
        $this->update([
            'is_processed' => true,
            'fetched_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public static function getAvailableNiches(): array
    {
        return [
            'business' => "Biznes va tadbirkorlik",
            'fashion' => "Moda va kiyim",
            'food' => "Ovqat va restoran",
            'beauty' => "Go'zallik va kosmetika",
            'tech' => "Texnologiya",
            'education' => "Ta'lim",
            'travel' => "Sayohat",
            'fitness' => "Sport va fitness",
            'real_estate' => "Ko'chmas mulk",
            'auto' => "Avtomobillar",
        ];
    }

    public static function getAvailablePlatforms(): array
    {
        return [
            'google' => 'Google Trends',
            'tiktok' => 'TikTok',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
        ];
    }
}
