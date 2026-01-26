<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ViralContent - TrendSee Module
 *
 * Stores viral Instagram Reels for analysis and business inspiration.
 *
 * @property string $id
 * @property string|null $business_id
 * @property string $platform
 * @property string $platform_id
 * @property string|null $platform_username
 * @property string $niche
 * @property string|null $caption
 * @property string|null $video_url
 * @property string|null $thumbnail_url
 * @property string|null $permalink
 * @property array|null $metrics_json
 * @property int $play_count
 * @property int $like_count
 * @property int $comment_count
 * @property array|null $ai_analysis_json
 * @property int|null $hook_score
 * @property string|null $ai_summary
 * @property string|null $music_title
 * @property string|null $music_artist
 * @property bool $is_processed
 * @property bool $is_super_viral
 * @property bool $alert_sent
 * @property \Carbon\Carbon|null $fetched_at
 * @property \Carbon\Carbon|null $analyzed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ViralContent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'platform',
        'platform_id',
        'platform_username',
        'niche',
        'caption',
        'video_url',
        'thumbnail_url',
        'permalink',
        'metrics_json',
        'play_count',
        'like_count',
        'comment_count',
        'ai_analysis_json',
        'hook_score',
        'ai_summary',
        'music_title',
        'music_artist',
        'is_processed',
        'is_super_viral',
        'alert_sent',
        'fetched_at',
        'analyzed_at',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'ai_analysis_json' => 'array',
        'play_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'hook_score' => 'integer',
        'is_processed' => 'boolean',
        'is_super_viral' => 'boolean',
        'alert_sent' => 'boolean',
        'fetched_at' => 'datetime',
        'analyzed_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeSuperViral($query)
    {
        return $query->where('is_super_viral', true);
    }

    public function scopeByNiche($query, string $niche)
    {
        return $query->where('niche', $niche);
    }

    public function scopePendingAlert($query)
    {
        return $query->where('is_super_viral', true)
            ->where('alert_sent', false);
    }

    public function scopeViral($query, int $minViews = 50000)
    {
        return $query->where('play_count', '>=', $minViews);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getFormattedPlayCountAttribute(): string
    {
        $count = $this->play_count;

        if ($count >= 1000000) {
            return number_format($count / 1000000, 1) . 'M';
        }

        if ($count >= 1000) {
            return number_format($count / 1000, 1) . 'K';
        }

        return number_format($count);
    }

    public function getCaptionSummaryAttribute(): string
    {
        if (empty($this->caption)) {
            return 'Caption yo\'q';
        }

        $summary = mb_substr($this->caption, 0, 100);

        return mb_strlen($this->caption) > 100 ? $summary . '...' : $summary;
    }

    public function getViralLevelAttribute(): string
    {
        return match (true) {
            $this->play_count >= 1000000 => 'mega_viral',
            $this->play_count >= 500000 => 'super_viral',
            $this->play_count >= 100000 => 'viral',
            $this->play_count >= 50000 => 'trending',
            default => 'normal',
        };
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function markAsProcessed(): void
    {
        $this->update([
            'is_processed' => true,
            'analyzed_at' => now(),
        ]);
    }

    public function markAlertSent(): void
    {
        $this->update(['alert_sent' => true]);
    }

    public function checkSuperViral(int $threshold = 500000): void
    {
        if ($this->play_count >= $threshold && !$this->is_super_viral) {
            $this->update(['is_super_viral' => true]);
        }
    }
}
