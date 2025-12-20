<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiInsight extends Model
{
    use BelongsToBusiness, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'type',
        'title',
        'content',
        'priority',
        'sentiment',
        'is_read',
        'is_actionable',
        'action_taken',
        'data',
        'generated_at',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'is_actionable' => 'boolean',
        'data' => 'array',
        'generated_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Mark insight as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Record action taken on insight.
     */
    public function recordAction(string $action): void
    {
        $this->update([
            'action_taken' => $action,
        ]);
    }

    /**
     * Scope for unread insights.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for actionable insights.
     */
    public function scopeActionable($query)
    {
        return $query->where('is_actionable', true);
    }

    /**
     * Scope by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for urgent insights.
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope for high priority insights.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['urgent', 'high']);
    }

    /**
     * Get priority badge color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get sentiment badge color.
     */
    public function getSentimentColorAttribute(): string
    {
        return match($this->sentiment) {
            'positive' => 'green',
            'neutral' => 'gray',
            'negative' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'marketing' => '📊',
            'sales' => '💰',
            'content' => '📝',
            'product' => '📦',
            'customer' => '👥',
            'competitor' => '🎯',
            'general' => 'ℹ️',
            default => 'ℹ️',
        };
    }
}
