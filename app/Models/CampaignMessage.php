<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignMessage extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'type',
        'subject',
        'content',
        'media',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'sent_at',
    ];

    protected $casts = [
        'media' => 'array',
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
