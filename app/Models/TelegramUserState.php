<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramUserState extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'telegram_user_id',
        'current_funnel_id',
        'current_step_id',
        'collected_data',
        'waiting_for',
        'last_message_id',
        'last_message_chat_id',
        'context',
        'expires_at',
    ];

    protected $casts = [
        'collected_data' => 'array',
        'context' => 'array',
        'expires_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public function currentFunnel(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnel::class, 'current_funnel_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'current_step_id');
    }

    // Helpers
    public function isInFunnel(): bool
    {
        return $this->current_funnel_id !== null;
    }

    public function isWaitingForInput(): bool
    {
        return $this->waiting_for !== 'none';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getCollectedValue(string $key, $default = null)
    {
        return data_get($this->collected_data, $key, $default);
    }

    public function setCollectedValue(string $key, $value): void
    {
        $data = $this->collected_data ?? [];
        $data[$key] = $value;
        $this->collected_data = $data;
        $this->save();
    }

    public function getContextValue(string $key, $default = null)
    {
        return data_get($this->context, $key, $default);
    }

    public function setContextValue(string $key, $value): void
    {
        $context = $this->context ?? [];
        $context[$key] = $value;
        $this->context = $context;
        $this->save();
    }

    public function reset(): void
    {
        $this->update([
            'current_funnel_id' => null,
            'current_step_id' => null,
            'collected_data' => null,
            'waiting_for' => 'none',
            'context' => null,
            'expires_at' => null,
        ]);
    }

    public function moveTo(TelegramFunnelStep $step): void
    {
        $this->update([
            'current_funnel_id' => $step->funnel_id,
            'current_step_id' => $step->id,
            'waiting_for' => $step->input_type,
        ]);
    }
}
