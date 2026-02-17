<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    protected $fillable = [
        'business_id',
        'channel_id',
        'name',
        'type', // broadcast, drip, trigger, email, social
        'description',
        'status', // draft, active, paused, completed
        'budget',
        'starts_at',
        'ends_at',
        'target_audience',
        'settings',
        'metrics',
    ];

    protected $casts = [
        'settings' => 'array',
        'target_audience' => 'array',
        'metrics' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'budget' => 'decimal:2',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(CampaignMessage::class);
    }

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }
}
