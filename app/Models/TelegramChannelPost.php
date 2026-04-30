<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TelegramChannelPost — Kanalga chiqarilgan har bir post.
 *
 * `channel_post` webhook update'i kelishi bilan yaratiladi.
 * Views/reactions kumulyativ — har sync'da yangilanadi.
 */
class TelegramChannelPost extends Model
{
    use HasFactory, HasUuid;

    public const TYPE_TEXT = 'text';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_VIDEO = 'video';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_ANIMATION = 'animation';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_VOICE = 'voice';

    public const TYPE_POLL = 'poll';

    public const TYPE_LOCATION = 'location';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'telegram_channel_id',
        'message_id',
        'posted_at',
        'content_type',
        'media_count',
        'text_preview',
        'media_url',
        'views',
        'reactions_count',
        'forwards_count',
        'replies_count',
        'views_delta_24h',
        'reactions_delta_24h',
        'last_snapshot_at',
        'raw_payload',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'posted_at' => 'datetime',
        'last_snapshot_at' => 'datetime',
        'views' => 'integer',
        'reactions_count' => 'integer',
        'forwards_count' => 'integer',
        'replies_count' => 'integer',
        'media_count' => 'integer',
        'views_delta_24h' => 'integer',
        'reactions_delta_24h' => 'integer',
        'raw_payload' => 'array',
    ];

    // =================== Relations ===================

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class, 'telegram_channel_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(TelegramChannelPostSnapshot::class);
    }

    // =================== Scopes ===================

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('posted_at', $date);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('posted_at', '>=', now()->subDays($days));
    }

    // =================== Helpers ===================

    public function getEngagementAttribute(): int
    {
        return $this->reactions_count + $this->forwards_count + $this->replies_count;
    }

    public function engagementRate(): float
    {
        if ($this->views === 0) {
            return 0.0;
        }

        return round(($this->engagement / $this->views) * 100, 2);
    }

    public function telegramLink(): ?string
    {
        $username = $this->channel?->chat_username;
        if ($username) {
            return 'https://t.me/'.ltrim($username, '@').'/'.$this->message_id;
        }

        // Private channel — use c/<internal_id>/<message_id>
        $chatId = $this->channel?->telegram_chat_id;
        if ($chatId && str_starts_with((string) $chatId, '-100')) {
            $internalId = substr((string) $chatId, 4);

            return "https://t.me/c/{$internalId}/{$this->message_id}";
        }

        return null;
    }
}
