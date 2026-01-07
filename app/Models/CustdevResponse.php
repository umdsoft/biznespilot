<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustdevResponse extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'survey_id',
        'respondent_name',
        'respondent_phone',
        'respondent_region',
        'ip_address',
        'user_agent',
        'device_type',
        'status',
        'current_question',
        'time_spent',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Check if response is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get formatted time spent
     */
    public function getFormattedTimeSpent(): string
    {
        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        if ($minutes > 0) {
            return "{$minutes} daqiqa {$seconds} soniya";
        }

        return "{$seconds} soniya";
    }

    /**
     * Get device type icon
     */
    public function getDeviceIcon(): string
    {
        return match($this->device_type) {
            'mobile' => '📱',
            'tablet' => '📱',
            'desktop' => '💻',
            default => '🖥️',
        };
    }

    // Relationships

    public function survey(): BelongsTo
    {
        return $this->belongsTo(CustdevSurvey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CustdevAnswer::class, 'response_id');
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
