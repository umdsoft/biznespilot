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
 * TelegramChannel — System Bot tomonidan kuzatiladigan kanal.
 *
 * User BiznesPilot System Bot'ni o'z Telegram kanaliga admin qilib qo'shganda
 * yaratiladi. Statistik ma'lumotlar shu model orqali yig'iladi va har kuni
 * linked user'ga digest sifatida yuboriladi.
 */
class TelegramChannel extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_ADMIN = 'administrator';

    public const STATUS_LEFT = 'left';

    public const STATUS_KICKED = 'kicked';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_MEMBER = 'member';

    public const TYPE_CHANNEL = 'channel';

    public const TYPE_SUPERGROUP = 'supergroup';

    protected $fillable = [
        'business_id',
        'connected_by_user_id',
        'telegram_chat_id',
        'chat_username',
        'title',
        'description',
        'photo_url',
        'invite_link',
        'type',
        'subscriber_count',
        'admin_count',
        'admin_status',
        'admin_rights',
        'connected_at',
        'disconnected_at',
        'last_synced_at',
        'is_active',
    ];

    protected $casts = [
        'telegram_chat_id' => 'integer',
        'subscriber_count' => 'integer',
        'admin_count' => 'integer',
        'admin_rights' => 'array',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // =================== Relations ===================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(TelegramChannelPost::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(TelegramChannelDailyStat::class);
    }

    // =================== Scopes ===================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('admin_status', self::STATUS_ADMIN);
    }

    public function scopeForBusiness(Builder $query, string $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    // =================== Helpers ===================

    public function isTracked(): bool
    {
        return $this->is_active && $this->admin_status === self::STATUS_ADMIN;
    }

    public function publicLink(): ?string
    {
        if ($this->chat_username) {
            return 'https://t.me/'.ltrim($this->chat_username, '@');
        }

        return $this->invite_link;
    }

    public function displayName(): string
    {
        if ($this->chat_username) {
            return '@'.ltrim($this->chat_username, '@');
        }

        return $this->title;
    }

    public function markDisconnected(string $status = self::STATUS_LEFT): void
    {
        $this->update([
            'admin_status' => $status,
            'is_active' => false,
            'disconnected_at' => now(),
        ]);
    }

    public function markReconnected(array $rights = []): void
    {
        $this->update([
            'admin_status' => self::STATUS_ADMIN,
            'is_active' => true,
            'admin_rights' => $rights,
            'connected_at' => $this->connected_at ?? now(),
            'disconnected_at' => null,
        ]);
    }
}
