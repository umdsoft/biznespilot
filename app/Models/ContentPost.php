<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentPost extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'channel_id',
        'user_id',
        'platform',
        'title',
        'content',
        'type',
        'content_type',
        'format',
        'status',
        'scheduled_at',
        'published_at',
        'external_id',
        'external_url',
        'media',
        'metrics',
        'ai_suggestions',
        'hashtags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'media' => 'array',
        'metrics' => 'array',
        'hashtags' => 'array',
        'ai_suggestions' => 'array',
    ];

    /**
     * Get the marketing channel for the content post.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    /**
     * Get the user who created the content post.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ContentPostLink::class);
    }

    public function linkForPlatform(string $platform): ?ContentPostLink
    {
        return $this->links->firstWhere('platform', $platform);
    }
}
