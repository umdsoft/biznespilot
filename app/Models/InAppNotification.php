<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InAppNotification extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'priority',
        'title',
        'message',
        'action_url',
        'action_label',
        'icon',
        'related_type',
        'related_id',
        'read_at',
        'clicked_at',
        'is_active',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsClicked()
    {
        $this->update([
            'read_at' => $this->read_at ?? now(),
            'clicked_at' => now(),
        ]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'alert' => 'bell-alert',
            'insight' => 'light-bulb',
            'report' => 'document-chart-bar',
            'system' => 'cog',
            'celebration' => 'trophy',
            default => 'bell',
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'alert' => 'red',
            'insight' => 'blue',
            'report' => 'purple',
            'system' => 'gray',
            'celebration' => 'yellow',
            default => 'gray',
        };
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }
}
