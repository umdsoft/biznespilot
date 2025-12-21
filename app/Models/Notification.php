<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'channel',
        'title',
        'message',
        'action_url',
        'action_text',
        'related_type',
        'related_id',
        'priority',
        'read_at',
        'clicked_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
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

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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

    public function isRead()
    {
        return !is_null($this->read_at);
    }

    public function getTypeIcon()
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

    public function getTypeColor()
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

    public function getPriorityColor()
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
