<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramFunnel extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_bot_id',
        'name',
        'slug',
        'type',
        'is_active',
        'priority',
        'description',
        'settings',
        'first_step_id',
        'completion_message',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
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

    public function steps(): HasMany
    {
        return $this->hasMany(TelegramFunnelStep::class, 'funnel_id')->orderBy('order');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(TelegramTrigger::class, 'funnel_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(TelegramConversation::class, 'started_funnel_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function getFirstStep(): ?TelegramFunnelStep
    {
        return $this->steps()->orderBy('order')->first();
    }

    public function firstStep(): ?TelegramFunnelStep
    {
        // Check if first_step_id is set
        if ($this->first_step_id) {
            $step = $this->steps()->where('id', $this->first_step_id)->first();
            if ($step) {
                return $step;
            }
        }

        // Fallback to first step by order
        return $this->getFirstStep();
    }

    public function getStartStep(): ?TelegramFunnelStep
    {
        $startStepId = data_get($this->settings, 'start_step');
        if ($startStepId) {
            return $this->steps()->where('id', $startStepId)->first();
        }

        return $this->getFirstStep();
    }

    public function getStepBySlug(string $slug): ?TelegramFunnelStep
    {
        return $this->steps()->where('slug', $slug)->first();
    }

    public function getOnCompleteAction(): string
    {
        return data_get($this->settings, 'on_complete_action', 'reset');
    }

    public function getOnCompleteFunnelId(): ?string
    {
        return data_get($this->settings, 'on_complete_funnel_id');
    }
}
