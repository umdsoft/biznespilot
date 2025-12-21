<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class CompetitorAlert extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'competitor_id',
        'activity_id',
        'type',
        'alert_type',
        'severity',
        'title',
        'message',
        'description',
        'source_url',
        'detected_changes',
        'old_value',
        'new_value',
        'old_price',
        'new_price',
        'price_change_percent',
        'product_name',
        'campaign_name',
        'change_percent',
        'data',
        'action_recommendation',
        'requires_action',
        'action_taken_at',
        'action_notes',
        'status',
        'new_status',
        'is_important',
        'is_active',
        'read_at',
        'archived_at',
        'notification_sent',
        'notification_sent_at',
        'detected_at',
        'screenshot_path',
    ];

    protected $casts = [
        'detected_changes' => 'array',
        'data' => 'array',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'price_change_percent' => 'decimal:2',
        'change_percent' => 'decimal:2',
        'requires_action' => 'boolean',
        'is_important' => 'boolean',
        'is_active' => 'boolean',
        'notification_sent' => 'boolean',
        'detected_at' => 'datetime',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'action_taken_at' => 'datetime',
        'notification_sent_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function competitor()
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeUnviewed($query)
    {
        return $query->whereIn('status', ['new']);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('detected_at', '>=', now()->subDays($days));
    }

    public function markViewed()
    {
        if ($this->status === 'new') {
            $this->update(['status' => 'viewed']);
        }
    }

    public function markActed()
    {
        $this->update(['status' => 'acted']);
    }

    public function dismiss()
    {
        $this->update(['status' => 'dismissed']);
    }

    public function getAlertTypeName()
    {
        return match ($this->alert_type) {
            'price_change' => 'Narx o\'zgarishi',
            'new_product' => 'Yangi mahsulot',
            'campaign' => 'Yangi kampaniya',
            'followers_surge' => 'Followerlar o\'sishi',
            'content_viral' => 'Viral kontent',
            'promotion' => 'Aksiya/Chegirma',
            default => $this->alert_type,
        };
    }

    public function getAlertTypeIcon()
    {
        return match ($this->alert_type) {
            'price_change' => 'currency-dollar',
            'new_product' => 'cube',
            'campaign' => 'megaphone',
            'followers_surge' => 'user-group',
            'content_viral' => 'fire',
            'promotion' => 'gift',
            default => 'bell',
        };
    }

    public function getSeverityColor()
    {
        return match ($this->severity) {
            'high' => 'red',
            'medium' => 'orange',
            'low' => 'yellow',
            default => 'gray',
        };
    }
}
