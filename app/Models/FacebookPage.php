<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'facebook_page_id',
        'page_name',
        'page_username',
        'category',
        'about',
        'profile_picture_url',
        'cover_photo_url',
        'website',
        'fan_count',
        'posts_count',
        'page_impressions',
        'page_engaged_users',
        'is_active',
        'access_token',
        'last_synced_at',
        'disconnected_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(FacebookPageInsight::class);
    }

    public function getEngagementRateAttribute(): float
    {
        if ($this->fan_count <= 0) {
            return 0;
        }

        return round(($this->page_engaged_users / $this->fan_count) * 100, 2);
    }
}
