<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramConversation extends Model
{
    use HasUuid;

    protected $fillable = [
        'account_id',
        'conversation_id', // Instagram API conversation ID
        'participant_id',
        'participant_username',
        'participant_name',
        'profile_picture_url',
        'status',
        'current_automation_id',
        'current_step',
        'collected_data',
        'tags',
        'last_message_at',
        'is_bot_active',
        'needs_human',
        'profile_synced_at', // User Profile Sync timestamp
    ];

    protected $casts = [
        'collected_data' => 'array',
        'tags' => 'array',
        'last_message_at' => 'datetime',
        'is_bot_active' => 'boolean',
        'needs_human' => 'boolean',
        'profile_synced_at' => 'datetime',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';

    const STATUS_WAITING = 'waiting';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_BLOCKED = 'blocked';

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'account_id');
    }

    public function currentAutomation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'current_automation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InstagramMessage::class, 'conversation_id')->orderBy('sent_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(InstagramMessage::class, 'conversation_id')->latestOfMany('sent_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeNeedsHuman($query)
    {
        return $query->where('needs_human', true);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (! in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_filter($tags, fn ($t) => $t !== $tag));
        $this->update(['tags' => $tags]);
    }

    public function setCollectedData(string $key, mixed $value): void
    {
        $data = $this->collected_data ?? [];
        $data[$key] = $value;
        $this->update(['collected_data' => $data]);
    }

    public function getCollectedData(string $key, mixed $default = null): mixed
    {
        return $this->collected_data[$key] ?? $default;
    }

    public function startAutomation(InstagramAutomation $automation): void
    {
        $this->update([
            'current_automation_id' => $automation->id,
            'current_step' => 0,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function advanceStep(): void
    {
        $this->increment('current_step');
    }

    public function endAutomation(): void
    {
        $this->update([
            'current_automation_id' => null,
            'current_step' => 0,
        ]);
    }

    public function requestHuman(): void
    {
        $this->update(['needs_human' => true]);
    }

    public function resolveHuman(): void
    {
        $this->update(['needs_human' => false]);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->participant_name ?? $this->participant_username ?? 'Foydalanuvchi';
    }

    /**
     * Get business_id through the Instagram Account relationship.
     */
    public function getBusinessIdAttribute(): ?string
    {
        return $this->instagramAccount?->business_id;
    }
}
