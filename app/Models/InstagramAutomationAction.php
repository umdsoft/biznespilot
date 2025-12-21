<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAutomationAction extends Model
{
    protected $fillable = [
        'automation_id',
        'order',
        'action_type',
        'message_template',
        'buttons',
        'media',
        'condition_rules',
        'delay_seconds',
        'webhook_url',
        'settings',
    ];

    protected $casts = [
        'buttons' => 'array',
        'media' => 'array',
        'condition_rules' => 'array',
        'settings' => 'array',
    ];

    // Action type constants
    const TYPE_SEND_DM = 'send_dm';
    const TYPE_SEND_DM_WITH_BUTTONS = 'send_dm_with_buttons';
    const TYPE_SEND_MEDIA = 'send_media';
    const TYPE_SEND_VOICE = 'send_voice';
    const TYPE_ADD_TAG = 'add_tag';
    const TYPE_REMOVE_TAG = 'remove_tag';
    const TYPE_DELAY = 'delay';
    const TYPE_CONDITION = 'condition';
    const TYPE_AI_RESPONSE = 'ai_response';
    const TYPE_COLLECT_DATA = 'collect_data';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_REPLY_COMMENT = 'reply_comment';

    public function automation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function parseTemplate(array $variables = []): string
    {
        $message = $this->message_template ?? '';

        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    public function getDelayFormatted(): string
    {
        if (!$this->delay_seconds) {
            return '';
        }

        if ($this->delay_seconds < 60) {
            return $this->delay_seconds . ' soniya';
        } elseif ($this->delay_seconds < 3600) {
            return round($this->delay_seconds / 60) . ' daqiqa';
        } else {
            return round($this->delay_seconds / 3600, 1) . ' soat';
        }
    }

    public static function getActionTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_SEND_DM => 'DM yuborish',
            self::TYPE_SEND_DM_WITH_BUTTONS => 'Tugmali DM',
            self::TYPE_SEND_MEDIA => 'Media yuborish',
            self::TYPE_SEND_VOICE => 'Ovozli xabar',
            self::TYPE_ADD_TAG => 'Tag qo\'shish',
            self::TYPE_REMOVE_TAG => 'Tag olib tashlash',
            self::TYPE_DELAY => 'Kutish',
            self::TYPE_CONDITION => 'Shart',
            self::TYPE_AI_RESPONSE => 'AI javob',
            self::TYPE_COLLECT_DATA => 'Ma\'lumot yig\'ish',
            self::TYPE_WEBHOOK => 'Webhook',
            self::TYPE_REPLY_COMMENT => 'Commentga javob',
            default => $type,
        };
    }

    public static function getActionTypeIcon(string $type): string
    {
        return match ($type) {
            self::TYPE_SEND_DM => 'chat',
            self::TYPE_SEND_DM_WITH_BUTTONS => 'squares',
            self::TYPE_SEND_MEDIA => 'photo',
            self::TYPE_SEND_VOICE => 'microphone',
            self::TYPE_ADD_TAG => 'tag',
            self::TYPE_REMOVE_TAG => 'x-mark',
            self::TYPE_DELAY => 'clock',
            self::TYPE_CONDITION => 'arrows-split',
            self::TYPE_AI_RESPONSE => 'sparkles',
            self::TYPE_COLLECT_DATA => 'clipboard',
            self::TYPE_WEBHOOK => 'globe',
            self::TYPE_REPLY_COMMENT => 'reply',
            default => 'cog',
        };
    }
}
