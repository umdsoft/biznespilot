<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Deliverable extends Model
{
    use HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'agent',
        'type',
        'title',
        'data',
        'preview',
        'status',
        'approved_at',
        'completed_at',
        'user_feedback',
        'conversation_id',
    ];

    protected $casts = [
        'data' => 'array',
        'preview' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeForBusiness($query, string $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(?string $feedback = null): void
    {
        $this->update([
            'status' => 'rejected',
            'user_feedback' => $feedback,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
