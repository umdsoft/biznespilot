<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TelegramDailyStat extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'date',
        'new_users',
        'active_users',
        'blocked_users',
        'unblocked_users',
        'messages_in',
        'messages_out',
        'conversations_started',
        'conversations_closed',
        'handoffs',
        'leads_captured',
        'funnel_stats',
        'trigger_stats',
    ];

    protected $casts = [
        'date' => 'date',
        'funnel_stats' => 'array',
        'trigger_stats' => 'array',
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

    // Scopes
    public function scopeForBot($query, $botId)
    {
        return $query->where('telegram_bot_id', $botId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date instanceof Carbon ? $date->toDateString() : $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForLastDays($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days)->toDateString());
    }

    // Static helpers
    public static function getOrCreateForToday(TelegramBot $bot): self
    {
        return self::firstOrCreate([
            'telegram_bot_id' => $bot->id,
            'date' => now()->toDateString(),
        ], [
            'business_id' => $bot->business_id,
        ]);
    }

    // Increment helpers
    public function incrementNewUsers(int $count = 1): void
    {
        $this->increment('new_users', $count);
    }

    public function incrementActiveUsers(int $count = 1): void
    {
        $this->increment('active_users', $count);
    }

    public function incrementBlockedUsers(int $count = 1): void
    {
        $this->increment('blocked_users', $count);
    }

    public function incrementUnblockedUsers(int $count = 1): void
    {
        $this->increment('unblocked_users', $count);
    }

    public function incrementMessagesIn(int $count = 1): void
    {
        $this->increment('messages_in', $count);
    }

    public function incrementMessagesOut(int $count = 1): void
    {
        $this->increment('messages_out', $count);
    }

    public function incrementConversationsStarted(int $count = 1): void
    {
        $this->increment('conversations_started', $count);
    }

    public function incrementConversationsClosed(int $count = 1): void
    {
        $this->increment('conversations_closed', $count);
    }

    public function incrementHandoffs(int $count = 1): void
    {
        $this->increment('handoffs', $count);
    }

    public function incrementLeadsCaptured(int $count = 1): void
    {
        $this->increment('leads_captured', $count);
    }

    // Funnel stats
    public function incrementFunnelStat(string $funnelId, string $key, int $count = 1): void
    {
        $stats = $this->funnel_stats ?? [];
        if (! isset($stats[$funnelId])) {
            $stats[$funnelId] = [];
        }
        $stats[$funnelId][$key] = ($stats[$funnelId][$key] ?? 0) + $count;
        $this->update(['funnel_stats' => $stats]);
    }

    public function getFunnelStat(string $funnelId, string $key, $default = 0)
    {
        return data_get($this->funnel_stats, "{$funnelId}.{$key}", $default);
    }

    // Trigger stats
    public function incrementTriggerStat(string $triggerId, int $count = 1): void
    {
        $stats = $this->trigger_stats ?? [];
        $stats[$triggerId] = ($stats[$triggerId] ?? 0) + $count;
        $this->update(['trigger_stats' => $stats]);
    }

    public function getTriggerStat(string $triggerId, $default = 0)
    {
        return data_get($this->trigger_stats, $triggerId, $default);
    }

    // Calculated stats
    public function getTotalMessages(): int
    {
        return $this->messages_in + $this->messages_out;
    }

    public function getUserGrowth(): int
    {
        return $this->new_users - $this->blocked_users + $this->unblocked_users;
    }

    public function getConversationDelta(): int
    {
        return $this->conversations_started - $this->conversations_closed;
    }

    // Aggregate stats (static methods)
    public static function getSummaryForPeriod(TelegramBot $bot, Carbon $startDate, Carbon $endDate): array
    {
        $stats = self::forBot($bot->id)
            ->forDateRange($startDate, $endDate)
            ->get();

        return [
            'new_users' => $stats->sum('new_users'),
            'active_users' => $stats->max('active_users') ?? 0,
            'blocked_users' => $stats->sum('blocked_users'),
            'messages_in' => $stats->sum('messages_in'),
            'messages_out' => $stats->sum('messages_out'),
            'conversations_started' => $stats->sum('conversations_started'),
            'conversations_closed' => $stats->sum('conversations_closed'),
            'handoffs' => $stats->sum('handoffs'),
            'leads_captured' => $stats->sum('leads_captured'),
            'days' => $stats->count(),
        ];
    }

    public static function getDailyTrend(TelegramBot $bot, int $days = 30): array
    {
        return self::forBot($bot->id)
            ->forLastDays($days)
            ->orderBy('date')
            ->get()
            ->map(fn ($stat) => [
                'date' => $stat->date->format('Y-m-d'),
                'new_users' => $stat->new_users,
                'active_users' => $stat->active_users,
                'messages_in' => $stat->messages_in,
                'messages_out' => $stat->messages_out,
                'leads' => $stat->leads_captured,
            ])
            ->toArray();
    }
}
