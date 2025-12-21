<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAutomationLog extends Model
{
    use HasUuid;
    protected $fillable = [
        'automation_id',
        'conversation_id',
        'trigger_type',
        'trigger_value',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_TRIGGERED = 'triggered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';

    public function automation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InstagramConversation::class, 'conversation_id');
    }

    public static function logTrigger(
        InstagramAutomation $automation,
        string $triggerType,
        ?string $triggerValue = null,
        ?InstagramConversation $conversation = null,
        array $metadata = []
    ): self {
        return self::create([
            'automation_id' => $automation->id,
            'conversation_id' => $conversation?->id,
            'trigger_type' => $triggerType,
            'trigger_value' => $triggerValue,
            'status' => self::STATUS_TRIGGERED,
            'metadata' => $metadata,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    public function markSkipped(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason,
        ]);
    }
}
