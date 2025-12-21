<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAd extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'ad_account_id',
        'adset_id',
        'campaign_id',
        'meta_adset_id',
        'meta_campaign_id',
        'business_id',
        'meta_ad_id',
        'name',
        'status',
        'effective_status',
        'creative_id',
        'creative_data',
        'creative_thumbnail_url',
        'creative_body',
        'creative_title',
        'creative_link_url',
        'creative_call_to_action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'creative_data' => 'array',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class, 'ad_account_id');
    }

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(MetaAdSet::class, 'adset_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MetaCampaign::class, 'campaign_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getCallToActionLabelAttribute(): string
    {
        return match ($this->creative_call_to_action) {
            'SHOP_NOW' => 'Shop Now',
            'LEARN_MORE' => 'Learn More',
            'SIGN_UP' => 'Sign Up',
            'BOOK_TRAVEL' => 'Book Now',
            'CONTACT_US' => 'Contact Us',
            'DOWNLOAD' => 'Download',
            'GET_OFFER' => 'Get Offer',
            'GET_QUOTE' => 'Get Quote',
            'SUBSCRIBE' => 'Subscribe',
            'WATCH_MORE' => 'Watch More',
            default => $this->creative_call_to_action ?? 'Learn More',
        };
    }
}
