<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramBusinessConnection extends Model
{
    use BelongsToBusiness, HasUuid;

    public const AI_MODE_AUTO = 'auto';       // AI always replies

    public const AI_MODE_HYBRID = 'hybrid';   // AI suggests, owner can edit

    public const AI_MODE_MANUAL = 'manual';   // Only log, owner replies

    public const AI_MODES = [
        self::AI_MODE_AUTO => 'Avtomatik (AI)',
        self::AI_MODE_HYBRID => 'Aralash',
        self::AI_MODE_MANUAL => "Faqat qo'lda",
    ];

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'connection_id',
        'telegram_user_id',
        'owner_first_name',
        'owner_last_name',
        'owner_username',
        'user_chat_id',
        'can_reply',
        'rights',
        'is_enabled',
        'ai_auto_reply',
        'ai_mode',
        'settings',
        'persona_prompt',
        'sales_script_id',
        'primary_offer_id',
        'auto_create_lead',
        'lead_initial_stage',
        'knowledge_base',
        'connected_at',
        'disconnected_at',
        'last_activity_at',
    ];

    protected $casts = [
        'can_reply' => 'boolean',
        'is_enabled' => 'boolean',
        'ai_auto_reply' => 'boolean',
        'auto_create_lead' => 'boolean',
        'rights' => 'array',
        'settings' => 'array',
        'knowledge_base' => 'array',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function telegramBot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(TelegramConversation::class, 'business_connection_id', 'connection_id');
    }

    public function salesScript(): BelongsTo
    {
        return $this->belongsTo(SalesScript::class);
    }

    public function primaryOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'primary_offer_id');
    }

    // ==================== Scopes ====================

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->whereNull('disconnected_at');
    }

    public function scopeCanReply($query)
    {
        return $query->where('can_reply', true);
    }

    // ==================== Helpers ====================

    public function isActive(): bool
    {
        return $this->is_enabled && $this->disconnected_at === null && $this->can_reply;
    }

    public function shouldAIReply(): bool
    {
        return $this->isActive()
            && $this->ai_auto_reply
            && in_array($this->ai_mode, [self::AI_MODE_AUTO, self::AI_MODE_HYBRID], true);
    }

    public function getOwnerFullNameAttribute(): string
    {
        return trim(($this->owner_first_name ?? '').' '.($this->owner_last_name ?? '')) ?: ('@'.$this->owner_username);
    }

    /**
     * Get setting by key with default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Check if current time is within working hours (if configured).
     */
    public function isWithinWorkingHours(): bool
    {
        $hours = $this->getSetting('working_hours');
        if (! $hours || ! ($hours['enabled'] ?? false)) {
            return true; // no restriction
        }

        $now = now();
        $start = $hours['start'] ?? '09:00';
        $end = $hours['end'] ?? '18:00';
        $currentTime = $now->format('H:i');

        return $currentTime >= $start && $currentTime <= $end;
    }
}
