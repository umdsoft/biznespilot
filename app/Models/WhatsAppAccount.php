<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppAccount extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'integration_id',
        'phone_number',
        'phone_number_id',
        'display_name',
        'business_account_id',
        'profile_picture_url',
        'about',
        'total_contacts',
        'total_messages',
        'messages_sent',
        'messages_delivered',
        'messages_read',
        'delivered_rate',
        'read_rate',
        'is_active',
        'access_token',
        'webhook_url',
        'last_synced_at',
        'disconnected_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivered_rate' => 'float',
        'read_rate' => 'float',
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

    public function contacts(): HasMany
    {
        return $this->hasMany(WhatsAppContact::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsAppTemplate::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcast::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(WhatsAppAutomation::class);
    }

    public function updateDeliveryRate(): void
    {
        if ($this->messages_sent > 0) {
            $this->delivered_rate = ($this->messages_delivered / $this->messages_sent) * 100;
        }

        if ($this->messages_delivered > 0) {
            $this->read_rate = ($this->messages_read / $this->messages_delivered) * 100;
        }

        $this->save();
    }
}
