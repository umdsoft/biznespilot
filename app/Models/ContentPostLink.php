<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ContentPostLink extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'content_post_id',
        'business_id',
        'platform',
        'external_id',
        'external_url',
        'views',
        'likes',
        'comments',
        'shares',
        'saves',
        'reach',
        'forwards',
        'engagement_rate',
        'synced_at',
        'sync_status',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'engagement_rate' => 'decimal:4',
    ];

    // Relationships
    public function contentPost()
    {
        return $this->belongsTo(ContentPost::class);
    }

    // Scopes
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeNeedsSync($query)
    {
        return $query->where('sync_status', '!=', 'failed')
            ->whereNotNull('external_url')
            ->where(function ($q) {
                $q->whereNull('synced_at')
                    ->orWhere('synced_at', '<', now()->subHours(6));
            });
    }

    // Helpers
    public function needsSync(): bool
    {
        if ($this->sync_status === 'failed' || !$this->external_url) {
            return false;
        }

        return !$this->synced_at || $this->synced_at->lt(now()->subHours(6));
    }

    public function markSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'synced_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['sync_status' => 'failed']);
    }
}
