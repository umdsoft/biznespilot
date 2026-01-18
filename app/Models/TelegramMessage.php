<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'telegram_message_id',
        'telegram_chat_id',
        'direction',
        'sender_type',
        'operator_id',
        'content_type',
        'content',
        'keyboard',
        'funnel_id',
        'step_id',
        'is_read',
    ];

    protected $casts = [
        'content' => 'array',
        'keyboard' => 'array',
        'is_read' => 'boolean',
        'telegram_message_id' => 'integer',
        'telegram_chat_id' => 'integer',
    ];

    // Relations
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TelegramConversation::class, 'conversation_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnel::class, 'funnel_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'step_id');
    }

    // Scopes
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeFromUser($query)
    {
        return $query->where('sender_type', 'user');
    }

    public function scopeFromBot($query)
    {
        return $query->where('sender_type', 'bot');
    }

    public function scopeFromOperator($query)
    {
        return $query->where('sender_type', 'operator');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    // Helpers
    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    public function isFromBot(): bool
    {
        return $this->sender_type === 'bot';
    }

    public function isFromOperator(): bool
    {
        return $this->sender_type === 'operator';
    }

    public function isText(): bool
    {
        return $this->content_type === 'text';
    }

    public function isPhoto(): bool
    {
        return $this->content_type === 'photo';
    }

    public function isVideo(): bool
    {
        return $this->content_type === 'video';
    }

    public function isDocument(): bool
    {
        return $this->content_type === 'document';
    }

    public function isVoice(): bool
    {
        return $this->content_type === 'voice';
    }

    public function isCallback(): bool
    {
        return $this->content_type === 'callback_query';
    }

    public function isCommand(): bool
    {
        return $this->content_type === 'command';
    }

    public function isMedia(): bool
    {
        return in_array($this->content_type, ['photo', 'video', 'document', 'voice', 'audio', 'sticker']);
    }

    public function getText(): ?string
    {
        return data_get($this->content, 'text');
    }

    public function getCaption(): ?string
    {
        return data_get($this->content, 'caption');
    }

    public function getFileId(): ?string
    {
        return data_get($this->content, 'file_id');
    }

    public function getCallbackData(): ?string
    {
        return data_get($this->content, 'callback_data');
    }

    public function getCommand(): ?string
    {
        if ($this->content_type !== 'command') {
            return null;
        }

        return data_get($this->content, 'command');
    }

    public function getCommandArgs(): ?string
    {
        if ($this->content_type !== 'command') {
            return null;
        }

        return data_get($this->content, 'args');
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true]);
        }
    }

    public function hasKeyboard(): bool
    {
        return ! empty($this->keyboard);
    }
}
