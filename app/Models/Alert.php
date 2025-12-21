<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{
    use BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'alert_rule_id',
        'type',
        'alert_type',
        'alert_category',
        'title',
        'message',
        'metric_code',
        'condition',
        'threshold_value',
        'threshold_percent',
        'comparison_period',
        'current_value',
        'previous_value',
        'change_percent',
        'action_suggestion',
        'severity',
        'status',
        'is_read',
        'is_dismissed',
        'action_url',
        'action_label',
        'metadata',
        'triggered_at',
        'read_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolution_note',
        'snoozed_until',
        'notify_in_app',
        'notify_email',
        'notify_telegram',
        'notify_sms',
        'cooldown_hours',
        'last_triggered_at',
        'is_active',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'threshold_percent' => 'decimal:2',
        'current_value' => 'decimal:2',
        'previous_value' => 'decimal:2',
        'change_percent' => 'decimal:2',
        'metadata' => 'array',
        'triggered_at' => 'datetime',
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'snoozed_until' => 'datetime',
        'last_triggered_at' => 'datetime',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'notify_in_app' => 'boolean',
        'notify_email' => 'boolean',
        'notify_telegram' => 'boolean',
        'notify_sms' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['new', 'acknowledged']);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeNotSnoozed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('snoozed_until')
              ->orWhere('snoozed_until', '<', now());
        });
    }

    public function getMessage($locale = 'uz')
    {
        return $locale === 'en' && $this->message_en
            ? $this->message_en
            : $this->message_uz;
    }

    public function acknowledge(User $user)
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    public function resolve($note = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_note' => $note,
        ]);
    }

    public function snooze($hours)
    {
        $this->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addHours($hours),
        ]);
    }

    public function dismiss()
    {
        $this->update([
            'status' => 'dismissed',
        ]);
    }

    public function isCritical()
    {
        return $this->severity === 'critical';
    }

    public function isHigh()
    {
        return $this->severity === 'high';
    }

    public function getSeverityColor()
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            'info' => 'gray',
            default => 'gray',
        };
    }
}
