<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramDmStat extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'instagram_account_id',
        'business_id',
        'date',
        'total_conversations',
        'new_conversations',
        'messages_received',
        'messages_sent',
        'source_media_id',
        'dm_from_post',
        'dm_from_reel',
        'dm_from_story',
        'dm_from_profile',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'metadata' => 'array',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function getTotalDmFromContentAttribute(): int
    {
        return $this->dm_from_post + $this->dm_from_reel + $this->dm_from_story;
    }

    public function getResponseRateAttribute(): float
    {
        if ($this->messages_received <= 0) {
            return 0;
        }

        return round(($this->messages_sent / $this->messages_received) * 100, 1);
    }
}
