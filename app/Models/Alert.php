<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{
    use BelongsToBusiness, SoftDeletes;

    /**
     * The actual columns in the alerts table from migration:
     * id, business_id, type, category, title, message, severity, status,
     * data, action_url, is_read, read_at, dismissed_at, created_at, updated_at, deleted_at
     */
    protected $fillable = [
        'business_id',
        'type',
        'category',
        'title',
        'message',
        'severity',
        'status',
        'data',
        'action_url',
        'is_read',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['new', 'active']);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Since snoozed_until doesn't exist in DB, return all (not snoozed)
     */
    public function scopeNotSnoozed($query)
    {
        return $query;
    }

    public function getMessage($locale = 'uz')
    {
        return $this->message;
    }

    public function dismiss()
    {
        $this->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
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
