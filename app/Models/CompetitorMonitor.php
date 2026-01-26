<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompetitorMonitor - Hybrid Spy Module
 *
 * Stores competitor data with "Internal vs External" logic.
 * If target is OUR client -> use internal data (Cost = $0).
 * If external -> use RapidAPI (Cost = $$$).
 *
 * @property string $id
 * @property string $target_url
 * @property string $target_type
 * @property string|null $target_username
 * @property string|null $target_domain
 * @property string|null $internal_user_id
 * @property string|null $internal_business_id
 * @property bool $is_internal
 * @property array|null $stats_json
 * @property int|null $followers_count
 * @property float|null $engagement_rate
 * @property int|null $posts_count
 * @property int|null $avg_likes
 * @property int|null $avg_comments
 * @property array|null $growth_json
 * @property float|null $weekly_growth_rate
 * @property float|null $monthly_growth_rate
 * @property array|null $content_analysis_json
 * @property array|null $top_hashtags
 * @property string|null $posting_frequency
 * @property string|null $data_source
 * @property float $api_cost
 * @property int $api_calls_count
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_scraped_at
 * @property \Carbon\Carbon|null $expires_at
 */
class CompetitorMonitor extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'target_url',
        'target_type',
        'target_username',
        'target_domain',
        'internal_user_id',
        'internal_business_id',
        'is_internal',
        'stats_json',
        'followers_count',
        'engagement_rate',
        'posts_count',
        'avg_likes',
        'avg_comments',
        'growth_json',
        'weekly_growth_rate',
        'monthly_growth_rate',
        'content_analysis_json',
        'top_hashtags',
        'posting_frequency',
        'data_source',
        'api_cost',
        'api_calls_count',
        'is_active',
        'last_scraped_at',
        'expires_at',
    ];

    protected $casts = [
        'stats_json' => 'array',
        'growth_json' => 'array',
        'content_analysis_json' => 'array',
        'top_hashtags' => 'array',
        'is_internal' => 'boolean',
        'is_active' => 'boolean',
        'followers_count' => 'integer',
        'posts_count' => 'integer',
        'avg_likes' => 'integer',
        'avg_comments' => 'integer',
        'engagement_rate' => 'decimal:2',
        'weekly_growth_rate' => 'decimal:2',
        'monthly_growth_rate' => 'decimal:2',
        'api_cost' => 'decimal:4',
        'api_calls_count' => 'integer',
        'last_scraped_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function internalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'internal_user_id');
    }

    public function internalBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'internal_business_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('target_type', $type);
    }

    public function scopeFresh($query, int $days = 14)
    {
        return $query->where('last_scraped_at', '>=', now()->subDays($days));
    }

    public function scopeStale($query, int $days = 14)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('last_scraped_at')
                ->orWhere('last_scraped_at', '<', now()->subDays($days));
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getIsFreshAttribute(): bool
    {
        return $this->last_scraped_at && $this->last_scraped_at->diffInDays(now()) < 14;
    }

    public function getIsStaleAttribute(): bool
    {
        return !$this->is_fresh;
    }

    public function getFormattedFollowersAttribute(): string
    {
        $count = $this->followers_count ?? 0;

        if ($count >= 1000000) {
            return number_format($count / 1000000, 1) . 'M';
        }

        if ($count >= 1000) {
            return number_format($count / 1000, 1) . 'K';
        }

        return number_format($count);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function markAsScraped(string $source = 'external'): void
    {
        $this->increment('api_calls_count');
        $this->update([
            'data_source' => $source,
            'last_scraped_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);
    }

    public function matchToInternal(User $user, ?Business $business = null): void
    {
        $this->update([
            'internal_user_id' => $user->id,
            'internal_business_id' => $business?->id,
            'is_internal' => true,
            'data_source' => 'internal',
        ]);
    }

    public static function findByUrl(string $url): ?self
    {
        return self::where('target_url', $url)->first();
    }

    public static function findByUsername(string $username, string $type = 'instagram'): ?self
    {
        return self::where('target_username', $username)
            ->where('target_type', $type)
            ->first();
    }

    /**
     * Extract username from Instagram URL.
     */
    public static function extractInstagramUsername(string $url): ?string
    {
        if (preg_match('/instagram\.com\/([^\/\?]+)/', $url, $matches)) {
            $username = $matches[1];
            // Exclude special paths
            if (!in_array($username, ['p', 'reel', 'stories', 'explore', 'direct', 'accounts'])) {
                return $username;
            }
        }

        return null;
    }

    /**
     * Extract domain from URL.
     */
    public static function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;

        if ($host) {
            // Remove www. prefix
            return preg_replace('/^www\./', '', $host);
        }

        return null;
    }
}
