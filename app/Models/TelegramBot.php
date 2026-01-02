<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramBot extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'bot_token',
        'bot_username',
        'bot_name',
        'bot_id',
        'description',
        'total_users',
        'active_users',
        'total_messages',
        'messages_today',
        'is_active',
        'webhook_url',
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
        'bot_token',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(TelegramUser::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(TelegramBroadcast::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(TelegramAutomation::class);
    }

    public function getActiveUserRateAttribute(): float
    {
        if ($this->total_users <= 0) {
            return 0;
        }

        return round(($this->active_users / $this->total_users) * 100, 2);
    }
}
