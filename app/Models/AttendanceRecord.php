<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'date',
        'check_in',
        'check_out',
        'work_hours',
        'status',
        'notes',
        'location',
        'ip_address',
        'source',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'work_hours' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Scopes ====================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
            ->whereMonth('date', now()->month);
    }

    // ==================== Accessors ====================

    public function getIsCheckedInAttribute(): bool
    {
        return $this->check_in !== null && $this->check_out === null;
    }

    public function getIsCheckedOutAttribute(): bool
    {
        return $this->check_in !== null && $this->check_out !== null;
    }

    public function getIsLateAttribute(): bool
    {
        if (! $this->check_in) {
            return false;
        }

        $settings = AttendanceSetting::where('business_id', $this->business_id)->first();
        if (! $settings) {
            return false;
        }

        $workStartTime = Carbon::parse($this->check_in->format('Y-m-d').' '.$settings->work_start_time);
        $checkInTime = $this->check_in;
        $lateThreshold = $settings->late_threshold_minutes;

        return $checkInTime->diffInMinutes($workStartTime, false) > $lateThreshold;
    }

    public function getFormattedCheckInAttribute(): ?string
    {
        return $this->check_in ? $this->check_in->format('H:i') : null;
    }

    public function getFormattedCheckOutAttribute(): ?string
    {
        return $this->check_out ? $this->check_out->format('H:i') : null;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'present' => 'Ishda',
            'absent' => 'Yo\'q',
            'late' => 'Kechikkan',
            'half_day' => 'Yarim kun',
            'wfh' => 'Uydan ish',
            'leave' => 'Ta\'til',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'present' => 'green',
            'absent' => 'red',
            'late' => 'yellow',
            'half_day' => 'orange',
            'wfh' => 'blue',
            'leave' => 'purple',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    // ==================== Methods ====================

    /**
     * Check in the user
     */
    public function checkIn(?string $location = null, ?string $ipAddress = null): void
    {
        $this->update([
            'check_in' => now(),
            'location' => $location,
            'ip_address' => $ipAddress,
            'status' => $this->determineStatus(now()),
        ]);
    }

    /**
     * Check out the user
     */
    public function checkOut(): void
    {
        if (! $this->check_in) {
            throw new \Exception('Cannot check out without checking in first');
        }

        $checkOut = now();
        $workHours = $checkOut->diffInMinutes($this->check_in) / 60;

        $this->update([
            'check_out' => $checkOut,
            'work_hours' => round($workHours, 2),
        ]);

        // Update status if needed (e.g., half day)
        if ($workHours < 4) {
            $this->update(['status' => 'half_day']);
        }
    }

    /**
     * Determine status based on check-in time
     */
    protected function determineStatus(Carbon $checkInTime): string
    {
        $settings = AttendanceSetting::where('business_id', $this->business_id)->first();
        if (! $settings) {
            return 'present';
        }

        $workStartTime = Carbon::parse($checkInTime->format('Y-m-d').' '.$settings->work_start_time);
        $lateThreshold = $settings->late_threshold_minutes;

        $minutesLate = $checkInTime->diffInMinutes($workStartTime, false);

        if ($minutesLate > $lateThreshold) {
            return 'late';
        }

        return 'present';
    }

    /**
     * Calculate work hours between check-in and check-out
     */
    public function calculateWorkHours(): float
    {
        if (! $this->check_in || ! $this->check_out) {
            return 0;
        }

        return round($this->check_out->diffInMinutes($this->check_in) / 60, 2);
    }

    /**
     * Mark as absent
     */
    public function markAsAbsent(?string $reason = null): void
    {
        $this->update([
            'status' => 'absent',
            'notes' => $reason,
        ]);
    }

    /**
     * Mark as work from home
     */
    public function markAsWFH(?string $notes = null): void
    {
        $this->update([
            'status' => 'wfh',
            'notes' => $notes,
        ]);
    }

    /**
     * Get today's attendance for user
     */
    public static function getTodayAttendance($businessId, $userId): ?self
    {
        return self::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('date', today())
            ->first();
    }

    /**
     * Create or get today's attendance
     */
    public static function getOrCreateToday($businessId, $userId): self
    {
        return self::firstOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'date' => today(),
            ],
            [
                'status' => 'absent',
            ]
        );
    }
}
