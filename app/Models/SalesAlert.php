<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesAlert extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Alert turlari
     */
    public const TYPES = [
        'lead_followup' => [
            'name' => 'Lead follow-up',
            'description' => 'Lid bilan bog\'lanish eslatmasi',
            'icon' => 'user-plus',
        ],
        'kpi_warning' => [
            'name' => 'KPI ogohlantirish',
            'description' => 'Past KPI haqida ogohlantirish',
            'icon' => 'chart-line',
        ],
        'target_reminder' => [
            'name' => 'Maqsad eslatmasi',
            'description' => 'Kunlik/haftalik maqsad eslatmasi',
            'icon' => 'target',
        ],
        'penalty_warning' => [
            'name' => 'Jarima ogohlantirishii',
            'description' => 'Yaqinlashayotgan jarima haqida',
            'icon' => 'alert-triangle',
        ],
        'daily_summary' => [
            'name' => 'Kunlik xulosa',
            'description' => 'Kunlik vazifalar va rejalar',
            'icon' => 'calendar',
        ],
        'achievement' => [
            'name' => 'Yutuq',
            'description' => 'Yangi yutuq olindi',
            'icon' => 'award',
        ],
        'streak_warning' => [
            'name' => 'Streak ogohlantirish',
            'description' => 'Streak yo\'qotish xavfi',
            'icon' => 'flame',
        ],
        'leaderboard_change' => [
            'name' => 'Reyting o\'zgarishi',
            'description' => 'Leaderboard pozitsiya o\'zgarishi',
            'icon' => 'trending-up',
        ],
    ];

    /**
     * Priority darajalari
     */
    public const PRIORITIES = [
        'low' => ['name' => 'Past', 'color' => 'gray'],
        'medium' => ['name' => 'O\'rta', 'color' => 'blue'],
        'high' => ['name' => 'Yuqori', 'color' => 'orange'],
        'urgent' => ['name' => 'Shoshilinch', 'color' => 'red'],
    ];

    /**
     * Status turlari
     */
    public const STATUSES = [
        'unread' => 'O\'qilmagan',
        'read' => 'O\'qilgan',
        'dismissed' => 'Yopilgan',
        'actioned' => 'Bajarilgan',
    ];

    /**
     * Kanallar
     */
    public const CHANNELS = [
        'app' => 'Ilova ichida',
        'telegram' => 'Telegram',
        'email' => 'Email',
        'push' => 'Push notification',
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'priority',
        'title',
        'message',
        'data',
        'alertable_type',
        'alertable_id',
        'status',
        'read_at',
        'scheduled_at',
        'expires_at',
        'channels',
        'sent_via',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'sent_via' => 'array',
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'unread',
        'priority' => 'medium',
    ];

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bog'liq model (polymorphic)
     */
    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Foydalanuvchi bo'yicha filter
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id'); // Umumiy alertlar
        });
    }

    /**
     * O'qilmagan alertlar
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'unread');
    }

    /**
     * Faol alertlar (yopilmagan va muddati o'tmagan)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'dismissed')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Ko'rsatilishi kerak alertlar
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('scheduled_at')
              ->orWhere('scheduled_at', '<=', now());
        });
    }

    /**
     * Priority bo'yicha filter
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Alert turi bo'yicha filter
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Bugungi alertlar
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Shoshilinch alertlar
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Alert turi ma'lumotlarini olish
     */
    public function getTypeInfoAttribute(): array
    {
        return self::TYPES[$this->type] ?? [
            'name' => $this->type,
            'description' => '',
            'icon' => 'bell',
        ];
    }

    /**
     * Priority ma'lumotlarini olish
     */
    public function getPriorityInfoAttribute(): array
    {
        return self::PRIORITIES[$this->priority] ?? [
            'name' => $this->priority,
            'color' => 'gray',
        ];
    }

    /**
     * O'qilganmi?
     */
    public function isRead(): bool
    {
        return $this->status === 'read' || $this->status === 'actioned';
    }

    /**
     * Muddati o'tganmi?
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Ko'rsatilishi kerakmi?
     */
    public function shouldShow(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }

        return $this->status !== 'dismissed';
    }

    /**
     * O'qilgan deb belgilash
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Yopilgan deb belgilash
     */
    public function dismiss(): void
    {
        $this->update(['status' => 'dismissed']);
    }

    /**
     * Bajarilgan deb belgilash
     */
    public function markAsActioned(): void
    {
        $this->update([
            'status' => 'actioned',
            'read_at' => $this->read_at ?? now(),
        ]);
    }

    /**
     * Kanal orqali yuborilganini belgilash
     */
    public function markSentVia(string $channel): void
    {
        $sentVia = $this->sent_via ?? [];
        if (! in_array($channel, $sentVia)) {
            $sentVia[] = $channel;
            $this->update(['sent_via' => $sentVia]);
        }
    }
}
