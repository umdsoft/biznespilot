<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramBot extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'bot_token',
        'bot_username',
        'bot_first_name',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'is_verified',
        'verified_at',
        'settings',
        'default_funnel_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'bot_token',
        'webhook_secret',
    ];

    // Encrypt bot_token
    public function setBotTokenAttribute($value): void
    {
        $this->attributes['bot_token'] = encrypt($value);
    }

    public function getBotTokenAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Relations
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function funnels(): HasMany
    {
        return $this->hasMany(TelegramFunnel::class);
    }

    public function activeFunnels(): HasMany
    {
        return $this->hasMany(TelegramFunnel::class)->where('is_active', true);
    }

    public function defaultFunnel(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnel::class, 'default_funnel_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(TelegramTrigger::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(TelegramUser::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(TelegramConversation::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(TelegramBroadcast::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(TelegramDailyStat::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Helpers
    public function getSettingValue(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSettingValue(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    public function getWelcomeMessage(): string
    {
        return $this->getSettingValue('welcome_message', 'Assalomu alaykum! 👋');
    }

    public function getFallbackMessage(): string
    {
        return $this->getSettingValue('fallback_message', 'Tushunmadim, iltimos tanlang:');
    }

    public function getDefaultLanguage(): string
    {
        return $this->getSettingValue('default_language', 'uz');
    }

    public function hasTypingAction(): bool
    {
        return $this->getSettingValue('typing_action', true);
    }

    public function getTypingDelay(): int
    {
        return (int) $this->getSettingValue('typing_delay_ms', 500);
    }
}
