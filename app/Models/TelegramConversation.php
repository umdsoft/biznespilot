<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramConversation extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_user_id',
        'telegram_bot_id',
        'status',
        'assigned_operator_id',
        'handoff_at',
        'handoff_reason',
        'started_funnel_id',
        'lead_id',
        'tags',
        'started_at',
        'last_message_at',
        'closed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'started_at' => 'datetime',
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'handoff_at' => 'datetime',
    ];

    // Relations
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function assignedOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function startedFunnel(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnel::class, 'started_funnel_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class, 'conversation_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHandoff($query)
    {
        return $query->where('status', 'handoff');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeNeedsOperator($query)
    {
        return $query->where('status', 'handoff')
            ->whereNull('assigned_operator_id');
    }

    public function scopeAssignedTo($query, $operatorId)
    {
        return $query->where('assigned_operator_id', $operatorId);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isHandoff(): bool
    {
        return $this->status === 'handoff';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function hasOperator(): bool
    {
        return $this->assigned_operator_id !== null;
    }

    public function requestHandoff(?string $reason = null): void
    {
        $this->update([
            'status' => 'handoff',
            'handoff_at' => now(),
            'handoff_reason' => $reason,
        ]);
    }

    public function assignOperator(User $operator): void
    {
        $this->update([
            'assigned_operator_id' => $operator->id,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'active',
            'closed_at' => null,
            'assigned_operator_id' => null,
        ]);
    }

    public function updateLastMessageAt(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addTag(string $tag): void
    {
        if (! $this->hasTag($tag)) {
            $tags = $this->tags ?? [];
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = array_filter($this->tags ?? [], fn ($t) => $t !== $tag);
        $this->update(['tags' => array_values($tags)]);
    }

    public function getUnreadMessagesCount(): int
    {
        return $this->messages()
            ->where('direction', 'incoming')
            ->where('is_read', false)
            ->count();
    }

    public function markAllAsRead(): void
    {
        $this->messages()
            ->where('direction', 'incoming')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
