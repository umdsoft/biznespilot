<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramMessage extends Model
{
    use HasUuid;
    protected $fillable = [
        'conversation_id',
        'automation_id',
        'instagram_message_id',
        'direction',
        'message_type',
        'content',
        'media_data',
        'is_automated',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'media_data' => 'array',
        'is_automated' => 'boolean',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    // Direction constants
    const DIRECTION_INCOMING = 'incoming';
    const DIRECTION_OUTGOING = 'outgoing';

    // Message type constants
    const TYPE_TEXT = 'text';
    const TYPE_MEDIA = 'media';
    const TYPE_VOICE = 'voice';
    const TYPE_STORY_MENTION = 'story_mention';
    const TYPE_STORY_REPLY = 'story_reply';
    const TYPE_REACTION = 'reaction';
    const TYPE_UNSUPPORTED = 'unsupported';

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InstagramConversation::class, 'conversation_id');
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', self::DIRECTION_INCOMING);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', self::DIRECTION_OUTGOING);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false)->incoming();
    }

    public function isIncoming(): bool
    {
        return $this->direction === self::DIRECTION_INCOMING;
    }

    public function isOutgoing(): bool
    {
        return $this->direction === self::DIRECTION_OUTGOING;
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true]);
        }
    }

    public function getPreviewAttribute(): string
    {
        return match ($this->message_type) {
            self::TYPE_TEXT => mb_substr($this->content ?? '', 0, 50) . (mb_strlen($this->content ?? '') > 50 ? '...' : ''),
            self::TYPE_MEDIA => 'Media yuborildi',
            self::TYPE_VOICE => 'Ovozli xabar',
            self::TYPE_STORY_MENTION => 'Story\'da mention',
            self::TYPE_STORY_REPLY => 'Story\'ga javob',
            self::TYPE_REACTION => 'Reaktsiya',
            default => 'Xabar',
        };
    }

    public function getTimeAgoAttribute(): string
    {
        if (!$this->sent_at) {
            return '';
        }

        $diff = now()->diff($this->sent_at);

        if ($diff->d > 0) {
            return $diff->d . ' kun oldin';
        } elseif ($diff->h > 0) {
            return $diff->h . ' soat oldin';
        } elseif ($diff->i > 0) {
            return $diff->i . ' daqiqa oldin';
        } else {
            return 'Hozirgina';
        }
    }
}
