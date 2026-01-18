<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgAssignment extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'org_position_id',
        'user_id',
        'business_id',
        'assigned_date',
        'end_date',
        'is_active',
        'is_primary',
        'performance_summary',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'performance_summary' => 'array',
    ];

    // ==================== Relationships ====================

    public function orgPosition(): BelongsTo
    {
        return $this->belongsTo(OrgPosition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPosition($query, $positionId)
    {
        return $query->where('org_position_id', $positionId);
    }

    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    // ==================== Helper Methods ====================

    public function getIsCurrentAttribute(): bool
    {
        return $this->is_active &&
               ($this->end_date === null || $this->end_date >= now());
    }

    public function getDurationInDaysAttribute(): int
    {
        $endDate = $this->end_date ?? now();

        return $this->assigned_date->diffInDays($endDate);
    }
}
