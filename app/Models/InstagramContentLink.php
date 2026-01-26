<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InstagramContentLink - Kontent Reja va Instagram Post Bog'lovchisi
 *
 * Bu model content_calendar (rejadagi post) va haqiqiy Instagram postlar
 * o'rtasidagi "ko'prik" vazifasini bajaradi.
 *
 * Flow:
 * 1. Kontent reja yaratiladi (content_calendar)
 * 2. Post Instagramga yuklanadi
 * 3. Bu modelda bog'lanish yaratiladi
 * 4. Statistika muntazam sinxronlanadi
 *
 * @property string $id
 * @property string $business_id
 * @property string|null $instagram_account_id
 * @property string|null $content_calendar_id
 * @property string|null $content_idea_id
 * @property string $instagram_media_id
 * @property string $media_type
 * @property string|null $permalink
 * @property string|null $thumbnail_url
 * @property string|null $caption
 * @property \Carbon\Carbon|null $posted_at
 * @property string|null $shortcode
 * @property int $views
 * @property int $likes
 * @property int $comments
 * @property int $shares
 * @property int $saves
 * @property int $reach
 * @property int $impressions
 * @property int $plays
 * @property int $replays
 * @property float $engagement_rate
 * @property float $save_rate
 * @property float $share_rate
 * @property int $performance_score
 * @property bool $is_top_performer
 * @property string $sync_status
 * @property \Carbon\Carbon|null $last_synced_at
 * @property string|null $sync_error
 * @property int $sync_attempts
 * @property string $link_type
 * @property string|null $match_method
 * @property float|null $match_confidence
 */
class InstagramContentLink extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * Media turlari
     */
    public const MEDIA_TYPE_POST = 'post';
    public const MEDIA_TYPE_REEL = 'reel';
    public const MEDIA_TYPE_STORY = 'story';
    public const MEDIA_TYPE_CAROUSEL = 'carousel';
    public const MEDIA_TYPE_IGTV = 'igtv';

    /**
     * Sync statuslari
     */
    public const SYNC_STATUS_PENDING = 'pending';
    public const SYNC_STATUS_SYNCED = 'synced';
    public const SYNC_STATUS_FAILED = 'failed';

    /**
     * Link turlari
     */
    public const LINK_TYPE_AUTO = 'auto';
    public const LINK_TYPE_MANUAL = 'manual';

    /**
     * Match metodlari
     */
    public const MATCH_METHOD_EXACT = 'exact';
    public const MATCH_METHOD_FUZZY = 'fuzzy';
    public const MATCH_METHOD_DATE = 'date';
    public const MATCH_METHOD_MANUAL = 'manual';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'instagram_account_id',
        'content_calendar_id',
        'content_idea_id',
        'instagram_media_id',
        'media_type',
        'permalink',
        'thumbnail_url',
        'caption',
        'posted_at',
        'shortcode',
        // Statistics
        'views',
        'likes',
        'comments',
        'shares',
        'saves',
        'reach',
        'impressions',
        'plays',
        'replays',
        // Calculated metrics
        'engagement_rate',
        'save_rate',
        'share_rate',
        // Performance
        'performance_score',
        'is_top_performer',
        // Sync
        'sync_status',
        'last_synced_at',
        'sync_error',
        'sync_attempts',
        // Linking
        'link_type',
        'match_method',
        'match_confidence',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'posted_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'views' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'saves' => 'integer',
        'reach' => 'integer',
        'impressions' => 'integer',
        'plays' => 'integer',
        'replays' => 'integer',
        'engagement_rate' => 'decimal:4',
        'save_rate' => 'decimal:4',
        'share_rate' => 'decimal:4',
        'performance_score' => 'integer',
        'is_top_performer' => 'boolean',
        'sync_attempts' => 'integer',
        'match_confidence' => 'decimal:2',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Instagram account bog'lanishi
     */
    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    /**
     * Content Calendar bog'lanishi
     */
    public function contentCalendar(): BelongsTo
    {
        return $this->belongsTo(ContentCalendar::class);
    }

    /**
     * Content Idea bog'lanishi
     */
    public function contentIdea(): BelongsTo
    {
        return $this->belongsTo(ContentIdea::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Faqat sinxronlangan postlar
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', self::SYNC_STATUS_SYNCED);
    }

    /**
     * Scope: Sinxronlanish kutilayotgan
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', self::SYNC_STATUS_PENDING);
    }

    /**
     * Scope: Sinxronlash xatosi bo'lganlar
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', self::SYNC_STATUS_FAILED);
    }

    /**
     * Scope: Top performerlar
     */
    public function scopeTopPerformers($query)
    {
        return $query->where('is_top_performer', true);
    }

    /**
     * Scope: Media turi bo'yicha
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('media_type', $type);
    }

    /**
     * Scope: Reels
     */
    public function scopeReels($query)
    {
        return $query->where('media_type', self::MEDIA_TYPE_REEL);
    }

    /**
     * Scope: Stories
     */
    public function scopeStories($query)
    {
        return $query->where('media_type', self::MEDIA_TYPE_STORY);
    }

    /**
     * Scope: Sinxronlash kerak bo'lganlar (24 soatdan ortiq)
     */
    public function scopeNeedsSync($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours(24));
        })->where('sync_status', '!=', self::SYNC_STATUS_FAILED)
          ->orWhere(function ($q) {
              $q->where('sync_status', self::SYNC_STATUS_FAILED)
                ->where('sync_attempts', '<', 3);
          });
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Engagement metrikalarini hisoblash
     */
    public function calculateEngagementMetrics(int $followerCount = 0): void
    {
        // Total engagement
        $totalEngagement = $this->likes + $this->comments + $this->shares + $this->saves;

        // Engagement rate (reach asosida)
        if ($this->reach > 0) {
            $this->engagement_rate = ($totalEngagement / $this->reach) * 100;
        }

        // Save rate
        if ($this->reach > 0) {
            $this->save_rate = ($this->saves / $this->reach) * 100;
        }

        // Share rate
        if ($this->reach > 0) {
            $this->share_rate = ($this->shares / $this->reach) * 100;
        }

        // Performance score (0-100)
        $this->performance_score = $this->calculatePerformanceScore($followerCount);

        $this->save();
    }

    /**
     * Performance score hisoblash
     */
    protected function calculatePerformanceScore(int $followerCount): int
    {
        $score = 0;

        // Engagement rate (max 40 ball)
        if ($this->engagement_rate >= 10) {
            $score += 40;
        } elseif ($this->engagement_rate >= 5) {
            $score += 30;
        } elseif ($this->engagement_rate >= 3) {
            $score += 20;
        } elseif ($this->engagement_rate >= 1) {
            $score += 10;
        }

        // Save rate (max 25 ball)
        if ($this->save_rate >= 5) {
            $score += 25;
        } elseif ($this->save_rate >= 2) {
            $score += 15;
        } elseif ($this->save_rate >= 1) {
            $score += 8;
        }

        // Share rate (max 20 ball)
        if ($this->share_rate >= 3) {
            $score += 20;
        } elseif ($this->share_rate >= 1) {
            $score += 12;
        } elseif ($this->share_rate >= 0.5) {
            $score += 5;
        }

        // Comments to likes ratio (max 15 ball)
        if ($this->likes > 0) {
            $commentsRatio = ($this->comments / $this->likes) * 100;
            if ($commentsRatio >= 10) {
                $score += 15;
            } elseif ($commentsRatio >= 5) {
                $score += 10;
            } elseif ($commentsRatio >= 2) {
                $score += 5;
            }
        }

        return min(100, $score);
    }

    /**
     * Sync xatosini qayd etish
     */
    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => self::SYNC_STATUS_FAILED,
            'sync_error' => $error,
            'sync_attempts' => $this->sync_attempts + 1,
        ]);
    }

    /**
     * Sync muvaffaqiyatli
     */
    public function markSynced(): void
    {
        $this->update([
            'sync_status' => self::SYNC_STATUS_SYNCED,
            'last_synced_at' => now(),
            'sync_error' => null,
        ]);
    }

    /**
     * Top performer sifatida belgilash
     */
    public function markAsTopPerformer(): void
    {
        $this->update(['is_top_performer' => true]);
    }

    /**
     * Instagram post URL ni olish
     */
    public function getInstagramUrlAttribute(): ?string
    {
        if ($this->permalink) {
            return $this->permalink;
        }

        if ($this->shortcode) {
            return "https://www.instagram.com/p/{$this->shortcode}/";
        }

        return null;
    }

    /**
     * Statistika summary
     */
    public function getStatsSummaryAttribute(): array
    {
        return [
            'views' => $this->views,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'shares' => $this->shares,
            'saves' => $this->saves,
            'reach' => $this->reach,
            'impressions' => $this->impressions,
            'engagement_rate' => round($this->engagement_rate, 2) . '%',
            'performance_score' => $this->performance_score,
            'is_top_performer' => $this->is_top_performer,
        ];
    }

    /**
     * Sync holati haqida ma'lumot
     */
    public function getSyncInfoAttribute(): array
    {
        return [
            'status' => $this->sync_status,
            'last_synced' => $this->last_synced_at?->diffForHumans(),
            'attempts' => $this->sync_attempts,
            'error' => $this->sync_error,
            'needs_sync' => $this->requiresSync(),
        ];
    }

    /**
     * Sinxronlash kerakmi? (Instance method - renamed from needsSync to avoid scope conflict)
     */
    public function requiresSync(): bool
    {
        if ($this->sync_status === self::SYNC_STATUS_FAILED && $this->sync_attempts >= 3) {
            return false;
        }

        if (! $this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->lt(now()->subHours(24));
    }
}
