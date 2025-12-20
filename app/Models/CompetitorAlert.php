<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class CompetitorAlert extends Model
{
    use BelongsToBusiness;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'competitor_id',
        'business_id',
        'type',
        'severity',
        'title',
        'message',
        'activity_id',
        'data',
        'status',
        'read_at',
        'archived_at',
        'notification_sent',
        'notification_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Get the competitor that owns the alert.
     */
    public function competitor()
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Get the related activity (if any).
     */
    public function activity()
    {
        return $this->belongsTo(CompetitorActivity::class, 'activity_id');
    }

    /**
     * Scope for unread alerts
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope for read alerts
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Archive alert
     */
    public function archive()
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    /**
     * Mark notification as sent
     */
    public function markNotificationSent()
    {
        $this->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
        ]);
    }
}
