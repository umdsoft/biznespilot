<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramFunnelStep extends Model
{
    use HasUuids;

    protected $fillable = [
        'funnel_id',
        'name',
        'slug',
        'order',
        'step_type',
        'next_step_id',
        'content',
        'keyboard',
        'input_type',
        'input_field',
        'validation',
        'actions',
        'action_type',
        'action_config',
        'condition',
        'condition_true_step_id',
        'condition_false_step_id',
        'transitions',
        'edit_previous_message',
        'delete_user_message',
        'delay_ms',
        'position_x',
        'position_y',
        // Marketing features
        'subscribe_check',
        'subscribe_true_step_id',
        'subscribe_false_step_id',
        'quiz',
        'ab_test',
        'tag',
        'trigger',
    ];

    protected $casts = [
        'content' => 'array',
        'keyboard' => 'array',
        'validation' => 'array',
        'actions' => 'array',
        'action_config' => 'array',
        'condition' => 'array',
        'transitions' => 'array',
        'edit_previous_message' => 'boolean',
        'delete_user_message' => 'boolean',
        'order' => 'integer',
        'delay_ms' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        // Marketing features
        'subscribe_check' => 'array',
        'quiz' => 'array',
        'ab_test' => 'array',
        'tag' => 'array',
        'trigger' => 'array',
    ];

    // Relations
    public function funnel(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnel::class, 'funnel_id');
    }

    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'next_step_id');
    }

    public function conditionTrueStep(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'condition_true_step_id');
    }

    public function conditionFalseStep(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'condition_false_step_id');
    }

    public function subscribeTrueStep(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'subscribe_true_step_id');
    }

    public function subscribeFalseStep(): BelongsTo
    {
        return $this->belongsTo(TelegramFunnelStep::class, 'subscribe_false_step_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(TelegramTrigger::class, 'step_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class, 'step_id');
    }

    // Content helpers
    public function getContent(): array
    {
        return $this->content ?? ['type' => 'text', 'text' => ''];
    }

    public function getText(): string
    {
        return data_get($this->content, 'text', '');
    }

    public function getParseMode(): ?string
    {
        return data_get($this->content, 'parse_mode');
    }

    public function hasMedia(): bool
    {
        return !empty(data_get($this->content, 'media.type'));
    }

    public function getMediaType(): ?string
    {
        return data_get($this->content, 'media.type');
    }

    public function getMediaUrl(): ?string
    {
        return data_get($this->content, 'media.url');
    }

    public function getMediaFileId(): ?string
    {
        return data_get($this->content, 'media.file_id');
    }

    public function getMediaCaption(): ?string
    {
        return data_get($this->content, 'media.caption');
    }

    // Keyboard helpers
    public function hasKeyboard(): bool
    {
        return !empty($this->keyboard);
    }

    public function getKeyboardType(): ?string
    {
        return data_get($this->keyboard, 'type');
    }

    public function getKeyboardButtons(): array
    {
        return data_get($this->keyboard, 'buttons', []);
    }

    public function isResizeKeyboard(): bool
    {
        return data_get($this->keyboard, 'resize_keyboard', true);
    }

    public function isOneTimeKeyboard(): bool
    {
        return data_get($this->keyboard, 'one_time_keyboard', false);
    }

    // Validation helpers
    public function isRequired(): bool
    {
        return data_get($this->validation, 'required', false);
    }

    public function getValidationErrorMessage(): string
    {
        return data_get($this->validation, 'error_message', 'Noto\'g\'ri format');
    }

    // Transition helpers
    public function getDefaultTransition(): ?string
    {
        return data_get($this->transitions, 'default');
    }

    public function getTransitionConditions(): array
    {
        return data_get($this->transitions, 'conditions', []);
    }

    // Navigation
    public function getNextStep(): ?TelegramFunnelStep
    {
        return $this->funnel->steps()
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();
    }

    public function getPreviousStep(): ?TelegramFunnelStep
    {
        return $this->funnel->steps()
            ->where('order', '<', $this->order)
            ->orderByDesc('order')
            ->first();
    }
}
