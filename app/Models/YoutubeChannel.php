<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YoutubeChannel extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'connected_by_user_id',
        'channel_id',
        'uploads_playlist_id',
        'handle',
        'title',
        'description',
        'thumbnail_url',
        'country',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'subscriber_count',
        'view_count',
        'video_count',
        'connected_at',
        'disconnected_at',
        'last_synced_at',
        'is_active',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'subscriber_count' => 'integer',
        'view_count' => 'integer',
        'video_count' => 'integer',
    ];

    public function videos(): HasMany
    {
        return $this->hasMany(YoutubeVideo::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
