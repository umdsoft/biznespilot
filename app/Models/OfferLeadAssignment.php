<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OfferLeadAssignment extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'offer_id',
        'lead_id',
        'business_id',
        'assigned_by',
        'telegram_user_id',
        'status',
        'channel',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'first_viewed_at',
        'last_viewed_at',
        'clicked_at',
        'converted_at',
        'expires_at',
        'view_count',
        'click_count',
        'share_count',
        'offered_price',
        'final_price',
        'discount_amount',
        'discount_code',
        'metadata',
        'utm_data',
        'notes',
        'rejection_reason',
        'tracking_code',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
        'expires_at' => 'datetime',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'share_count' => 'integer',
        'offered_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'metadata' => 'array',
        'utm_data' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_VIEWED = 'viewed';
    const STATUS_CLICKED = 'clicked';
    const STATUS_INTERESTED = 'interested';
    const STATUS_CONVERTED = 'converted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    // Channel constants
    const CHANNEL_TELEGRAM = 'telegram';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_MANUAL = 'manual';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tracking_code)) {
                $model->tracking_code = self::generateTrackingCode();
            }
        });
    }

    /**
     * Generate unique tracking code
     */
    public static function generateTrackingCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::where('tracking_code', $code)->exists());

        return $code;
    }

    // Relationships

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    // Status helpers

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_VIEWED,
            self::STATUS_CLICKED,
            self::STATUS_INTERESTED,
            self::STATUS_CONVERTED,
        ]);
    }

    public function isViewed(): bool
    {
        return in_array($this->status, [
            self::STATUS_VIEWED,
            self::STATUS_CLICKED,
            self::STATUS_INTERESTED,
            self::STATUS_CONVERTED,
        ]);
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    // Status transitions

    public function markAsSent(): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return $this;
    }

    public function markAsDelivered(): self
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        return $this;
    }

    public function markAsViewed(): self
    {
        $isFirstView = !$this->first_viewed_at;

        $this->update([
            'status' => self::STATUS_VIEWED,
            'first_viewed_at' => $isFirstView ? now() : $this->first_viewed_at,
            'last_viewed_at' => now(),
            'view_count' => $this->view_count + 1,
        ]);

        return $this;
    }

    public function markAsClicked(): self
    {
        $isFirstClick = !$this->clicked_at;

        $this->update([
            'status' => self::STATUS_CLICKED,
            'clicked_at' => $isFirstClick ? now() : $this->clicked_at,
            'click_count' => $this->click_count + 1,
        ]);

        return $this;
    }

    public function markAsInterested(): self
    {
        $this->update([
            'status' => self::STATUS_INTERESTED,
        ]);

        return $this;
    }

    public function markAsConverted(?float $finalPrice = null): self
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_at' => now(),
            'final_price' => $finalPrice ?? $this->offered_price,
        ]);

        return $this;
    }

    public function markAsRejected(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    public function markAsExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    // URL generation

    public function getPublicUrl(): string
    {
        return route('offers.public.view', $this->tracking_code);
    }

    public function getTelegramShareUrl(): string
    {
        $text = urlencode($this->offer->name . "\n\n" . $this->getPublicUrl());
        return "https://t.me/share/url?url=" . urlencode($this->getPublicUrl()) . "&text=" . urlencode($this->offer->name);
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_VIEWED,
            self::STATUS_CLICKED,
            self::STATUS_INTERESTED,
            self::STATUS_CONVERTED,
        ]);
    }

    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
            self::STATUS_CONVERTED,
        ])->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeScheduledForNow($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    // Metrics

    public function getConversionTime(): ?int
    {
        if (!$this->converted_at || !$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffInMinutes($this->converted_at);
    }

    public function getViewToClickRate(): float
    {
        if ($this->view_count === 0) {
            return 0;
        }

        return round(($this->click_count / $this->view_count) * 100, 2);
    }

    // Status labels (O'zbekcha)

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_SENT => 'Yuborildi',
            self::STATUS_DELIVERED => 'Yetkazildi',
            self::STATUS_VIEWED => "Ko'rildi",
            self::STATUS_CLICKED => 'Bosildi',
            self::STATUS_INTERESTED => 'Qiziqdi',
            self::STATUS_CONVERTED => 'Sotib oldi',
            self::STATUS_REJECTED => 'Rad etdi',
            self::STATUS_EXPIRED => 'Muddati tugadi',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    public static function getChannelLabels(): array
    {
        return [
            self::CHANNEL_TELEGRAM => 'Telegram',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_MANUAL => "Qo'lda",
        ];
    }

    public function getChannelLabel(): string
    {
        return self::getChannelLabels()[$this->channel] ?? $this->channel;
    }
}
