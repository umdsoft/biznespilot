<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstagramAccount extends Model
{
    use BelongsToBusiness;

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
        'last_sync_at',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(InstagramMedia::class);
    }

    public function dailyInsights(): HasMany
    {
        return $this->hasMany(InstagramDailyInsight::class);
    }

    public function audience(): HasOne
    {
        return $this->hasOne(InstagramAudience::class);
    }

    public function dmStats(): HasMany
    {
        return $this->hasMany(InstagramDmStat::class);
    }

    public function hashtagStats(): HasMany
    {
        return $this->hasMany(InstagramHashtagStat::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(InstagramSyncLog::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(InstagramAutomation::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class);
    }

    public function quickReplies(): HasMany
    {
        return $this->hasMany(InstagramQuickReply::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(InstagramBroadcast::class);
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
