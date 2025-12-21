<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramDailyInsight extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'instagram_account_id',
        'business_id',
        'date',
        'impressions',
        'reach',
        'profile_views',
        'website_clicks',
        'email_contacts',
        'phone_call_clicks',
        'text_message_clicks',
        'get_directions_clicks',
        'follower_count',
        'followers_gained',
        'followers_lost',
        'online_followers',
        'audience_city',
        'audience_country',
        'audience_gender_age',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'online_followers' => 'array',
        'audience_city' => 'array',
        'audience_country' => 'array',
        'audience_gender_age' => 'array',
        'metadata' => 'array',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function getTotalActionsAttribute(): int
    {
        return $this->website_clicks
            + $this->email_contacts
            + $this->phone_call_clicks
            + $this->text_message_clicks
            + $this->get_directions_clicks;
    }

    public function getNetFollowersAttribute(): int
    {
        return $this->followers_gained - $this->followers_lost;
    }
}
