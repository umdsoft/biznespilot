<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'notes',
        'status',
        'approved_by',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'emergency_contact',
        'emergency_phone',
        'attachments',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'attachments' => 'array',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(LeaveCalendarEvent::class);
    }

    // ==================== Scopes ====================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'approved')
            ->where('start_date', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Kutilmoqda',
            'approved' => 'Tasdiqlangan',
            'rejected' => 'Rad etilgan',
            'cancelled' => 'Bekor qilingan',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->is_approved && $this->start_date->isFuture();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->is_approved
            && $this->start_date->isPast()
            && $this->end_date->isFuture();
    }

    // ==================== Methods ====================

    /**
     * Calculate working days between dates
     */
    public static function calculateWorkingDays(Carbon $startDate, Carbon $endDate): float
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $workingDays = 0;

        foreach ($period as $date) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if (!in_array($date->dayOfWeek, [0, 6])) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Approve leave request
     */
    public function approve($approverId, $comments = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        // Create approval record
        LeaveApproval::create([
            'leave_request_id' => $this->id,
            'approver_id' => $approverId,
            'action' => 'approved',
            'comments' => $comments,
            'actioned_at' => now(),
        ]);

        // Update leave balance
        $balance = LeaveBalance::getOrCreate(
            $this->business_id,
            $this->user_id,
            $this->leave_type_id,
            $this->start_date->year
        );

        $balance->approvePendingDays($this->total_days);

        // Create calendar events
        $this->createCalendarEvents();

        return true;
    }

    /**
     * Reject leave request
     */
    public function reject($approverId, $reason, $comments = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        // Create approval record
        LeaveApproval::create([
            'leave_request_id' => $this->id,
            'approver_id' => $approverId,
            'action' => 'rejected',
            'comments' => $comments,
            'actioned_at' => now(),
        ]);

        // Remove pending days from balance
        $balance = LeaveBalance::getOrCreate(
            $this->business_id,
            $this->user_id,
            $this->leave_type_id,
            $this->start_date->year
        );

        $balance->removePendingDays($this->total_days);

        return true;
    }

    /**
     * Cancel leave request
     */
    public function cancel(): bool
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        $wasPending = $this->status === 'pending';

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Update leave balance
        $balance = LeaveBalance::getOrCreate(
            $this->business_id,
            $this->user_id,
            $this->leave_type_id,
            $this->start_date->year
        );

        if ($wasPending) {
            $balance->removePendingDays($this->total_days);
        } else {
            // If was approved, return the days
            $balance->used_days = max(0, $balance->used_days - $this->total_days);
            $balance->recalculate();
        }

        // Delete calendar events
        $this->calendarEvents()->delete();

        return true;
    }

    /**
     * Create calendar events for approved leave
     */
    protected function createCalendarEvents(): void
    {
        $period = CarbonPeriod::create($this->start_date, $this->end_date);

        foreach ($period as $date) {
            // Skip weekends
            if (!in_array($date->dayOfWeek, [0, 6])) {
                LeaveCalendarEvent::create([
                    'business_id' => $this->business_id,
                    'leave_request_id' => $this->id,
                    'user_id' => $this->user_id,
                    'event_date' => $date,
                    'is_full_day' => true,
                ]);
            }
        }
    }

    /**
     * Check if dates overlap with existing approved leaves
     */
    public static function hasOverlap($businessId, $userId, Carbon $startDate, Carbon $endDate, $excludeRequestId = null): bool
    {
        $query = self::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->dateRange($startDate, $endDate);

        if ($excludeRequestId) {
            $query->where('id', '!=', $excludeRequestId);
        }

        return $query->exists();
    }
}
