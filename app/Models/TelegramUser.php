<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TelegramUser extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'phone',
        'email',
        'is_blocked',
        'is_subscribed',
        'lead_id',
        'tags',
        'custom_data',
        'first_interaction_at',
        'last_interaction_at',
        'total_messages',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_data' => 'array',
        'is_blocked' => 'boolean',
        'is_subscribed' => 'boolean',
        'first_interaction_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'total_messages' => 'integer',
    ];

    // Relations
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function state(): HasOne
    {
        return $this->hasOne(TelegramUserState::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(TelegramConversation::class);
    }

    public function activeConversation(): HasOne
    {
        return $this->hasOne(TelegramConversation::class)
            ->where('status', 'active')
            ->latest();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('is_subscribed', true);
    }

    public function scopeWithPhone($query)
    {
        return $query->whereNotNull('phone');
    }

    public function scopeRecentlyActive($query, int $days = 30)
    {
        return $query->where('last_interaction_at', '>=', now()->subDays($days));
    }

    // Helpers
    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getDisplayName(): string
    {
        $name = $this->getFullName();
        if ($name) return $name;
        if ($this->username) return '@' . $this->username;
        return 'User #' . $this->telegram_id;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        $this->tags = array_values($tags);
        $this->save();
    }

    public function setCustomData(string $key, $value): void
    {
        $data = $this->custom_data ?? [];
        $data[$key] = $value;
        $this->custom_data = $data;
        $this->save();
    }

    public function getCustomData(string $key, $default = null)
    {
        return data_get($this->custom_data, $key, $default);
    }

    public function incrementMessages(): void
    {
        $this->increment('total_messages');
        $this->update(['last_interaction_at' => now()]);
    }

    public function markActive(): void
    {
        $this->update([
            'last_interaction_at' => now(),
            'is_blocked' => false,
        ]);

        if (!$this->first_interaction_at) {
            $this->update(['first_interaction_at' => now()]);
        }
    }

    public function markBlocked(): void
    {
        $this->update(['is_blocked' => true]);
    }

    public function markUnblocked(): void
    {
        $this->update(['is_blocked' => false]);
    }
}
