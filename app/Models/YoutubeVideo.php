<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeVideo extends Model
{
    use HasUuid;

    protected $fillable = [
        'youtube_channel_id',
        'video_id',
        'title',
        'description',
        'thumbnail_url',
        'published_at',
        'privacy_status',
        'is_short',
        'view_count',
        'like_count',
        'comment_count',
        'engagement_rate',
        'stats_updated_at',
        'raw_payload',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'stats_updated_at' => 'datetime',
        'is_short' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'engagement_rate' => 'decimal:4',
        'raw_payload' => 'array',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(YoutubeChannel::class, 'youtube_channel_id');
    }

    public function getPublicUrlAttribute(): string
    {
        return "https://www.youtube.com/watch?v={$this->video_id}";
    }

    public function getShortUrlAttribute(): string
    {
        return "https://youtu.be/{$this->video_id}";
    }
}
