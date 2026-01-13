<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    use HasUuid;

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'action',
        'comments',
        'actioned_at',
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // ==================== Accessors ====================

    public function getActionLabelAttribute(): string
    {
        $labels = [
            'approved' => 'Tasdiqlangan',
            'rejected' => 'Rad etilgan',
            'requested_changes' => 'O\'zgartirish so\'ralgan',
        ];

        return $labels[$this->action] ?? $this->action;
    }
}
