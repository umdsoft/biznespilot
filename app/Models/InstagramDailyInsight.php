<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramDailyInsight extends Model
{
    use HasUuid;

    protected $fillable = [
        'account_id',
        'insight_date',
        'impressions',
        'reach',
        'profile_views',
        'website_clicks',
        'email_contacts',
        'follower_count',
        'new_followers',
        'unfollowers',
        'audience_demographics',
    ];

    protected $casts = [
        'insight_date' => 'date',
        'audience_demographics' => 'array',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'account_id');
    }

    public function getTotalActionsAttribute(): int
    {
        return ($this->website_clicks ?? 0) + ($this->email_contacts ?? 0);
    }

    public function getNetFollowersAttribute(): int
    {
        return ($this->new_followers ?? 0) - ($this->unfollowers ?? 0);
    }
}
