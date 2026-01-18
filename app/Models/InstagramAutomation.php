<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstagramAutomation extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'status',
        'type',
        'is_ai_enabled',
        'settings',
        'flow_data',
        'is_flow_based',
        'trigger_count',
        'conversion_count',
    ];

    protected $casts = [
        'settings' => 'array',
        'flow_data' => 'array',
        'is_ai_enabled' => 'boolean',
        'is_flow_based' => 'boolean',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';

    const STATUS_PAUSED = 'paused';

    const STATUS_DRAFT = 'draft';

    // Type constants
    const TYPE_KEYWORD = 'keyword';

    const TYPE_COMMENT = 'comment';

    const TYPE_STORY_MENTION = 'story_mention';

    const TYPE_STORY_REPLY = 'story_reply';

    const TYPE_DM = 'dm';

    const TYPE_WELCOME = 'welcome';

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'account_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(InstagramAutomationTrigger::class, 'automation_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(InstagramAutomationAction::class, 'automation_id')->orderBy('order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InstagramAutomationLog::class, 'automation_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class, 'current_automation_id');
    }

    public function flowNodes(): HasMany
    {
        return $this->hasMany(InstagramFlowNode::class, 'automation_id');
    }

    public function flowEdges(): HasMany
    {
        return $this->hasMany(InstagramFlowEdge::class, 'automation_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->trigger_count === 0) {
            return 0;
        }

        return round(($this->conversion_count / $this->trigger_count) * 100, 2);
    }

    public function incrementTriggerCount(): void
    {
        $this->increment('trigger_count');
    }

    public function incrementConversionCount(): void
    {
        $this->increment('conversion_count');
    }
}
