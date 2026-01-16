<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstagramAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'instagram_id',
        'username',
        'name',
        'biography',
        'profile_picture_url',
        'website',
        'followers_count',
        'follows_count',
        'media_count',
        'is_primary',
        'is_active',
        'access_token',
        'last_sync_at',
        'last_synced_at',
        'disconnected_at',
        'posts_count',
        'engagement_rate',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'access_token',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(InstagramMedia::class, 'account_id');
    }

    public function dailyInsights(): HasMany
    {
        return $this->hasMany(InstagramDailyInsight::class, 'account_id');
    }

    public function audience(): HasOne
    {
        return $this->hasOne(InstagramAudience::class, 'account_id');
    }

    public function dmStats(): HasMany
    {
        return $this->hasMany(InstagramDmStat::class, 'account_id');
    }

    public function hashtagStats(): HasMany
    {
        return $this->hasMany(InstagramHashtagStat::class, 'account_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(InstagramSyncLog::class, 'account_id');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(InstagramAutomation::class, 'account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class, 'account_id');
    }

    public function quickReplies(): HasMany
    {
        return $this->hasMany(InstagramQuickReply::class, 'account_id');
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(InstagramBroadcast::class, 'account_id');
    }

    public function reels(): HasMany
    {
        return $this->media()->where('media_product_type', 'REELS');
    }

    public function posts(): HasMany
    {
        return $this->media()->where('media_product_type', 'FEED');
    }

    public function stories(): HasMany
    {
        return $this->media()->where('media_product_type', 'STORY');
    }

    public function getEngagementRateAttribute(): float
    {
        if ($this->followers_count <= 0) {
            return 0;
        }

        $avgEngagement = $this->media()
            ->where('posted_at', '>=', now()->subDays(30))
            ->avg(\DB::raw('like_count + comments_count'));

        return round(($avgEngagement / $this->followers_count) * 100, 2);
    }
}
