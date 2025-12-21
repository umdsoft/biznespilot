<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasUuid;
    protected $fillable = [
        'business_id',
        'name',
        'type', // broadcast, drip, trigger
        'channel', // whatsapp, instagram, all
        'message_template',
        'target_audience',
        'schedule_type', // immediate, scheduled
        'scheduled_at',
        'status', // draft, active, paused, completed
        'settings',
        'sent_count',
        'failed_count',
        'completed_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'target_audience' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CampaignMessage::class);
    }
}
