<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'in_app_notifications';

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'action_url',
        'action_text',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_read' => 'boolean',
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
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsClicked()
    {
        $this->update([
            'is_read' => true,
            'read_at' => $this->read_at ?? now(),
        ]);
    }

    public function isRead()
    {
        return $this->is_read;
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

}
