<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
        'pending_days',
        'available_days',
        'carried_forward',
    ];

    protected $casts = [
        'total_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'available_days' => 'decimal:2',
        'carried_forward' => 'decimal:2',
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

    // ==================== Scopes ====================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    // ==================== Methods ====================

    /**
     * Recalculate available days
     */
    public function recalculate(): void
    {
        $this->available_days = $this->total_days - $this->used_days - $this->pending_days;
        $this->save();
    }

    /**
     * Deduct days when leave is used
     */
    public function deductDays(float $days): void
    {
        $this->used_days += $days;
        $this->recalculate();
    }

    /**
     * Add pending days when leave is requested
     */
    public function addPendingDays(float $days): void
    {
        $this->pending_days += $days;
        $this->recalculate();
    }

    /**
     * Remove pending days when request is cancelled/rejected
     */
    public function removePendingDays(float $days): void
    {
        $this->pending_days = max(0, $this->pending_days - $days);
        $this->recalculate();
    }

    /**
     * Convert pending to used when leave is approved
     */
    public function approvePendingDays(float $days): void
    {
        $this->pending_days = max(0, $this->pending_days - $days);
        $this->used_days += $days;
        $this->recalculate();
    }

    /**
     * Get or create balance for user, leave type and year
     */
    public static function getOrCreate($businessId, $userId, $leaveTypeId, $year = null): self
    {
        $year = $year ?? now()->year;

        $balance = self::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            $leaveType = LeaveType::find($leaveTypeId);

            $balance = self::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
                'total_days' => $leaveType->default_days_per_year ?? 0,
                'used_days' => 0,
                'pending_days' => 0,
                'available_days' => $leaveType->default_days_per_year ?? 0,
                'carried_forward' => 0,
            ]);
        }

        return $balance;
    }

    /**
     * Initialize balances for new user
     */
    public static function initializeForUser($businessId, $userId, $year = null): void
    {
        $year = $year ?? now()->year;

        $leaveTypes = LeaveType::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        foreach ($leaveTypes as $leaveType) {
            self::getOrCreate($businessId, $userId, $leaveType->id, $year);
        }
    }

    /**
     * Carry forward unused balances to next year
     */
    public static function carryForwardBalances($businessId, $fromYear): void
    {
        $balances = self::where('business_id', $businessId)
            ->where('year', $fromYear)
            ->with('leaveType')
            ->get();

        foreach ($balances as $balance) {
            if ($balance->leaveType->carry_forward && $balance->available_days > 0) {
                $carryForwardDays = min(
                    $balance->available_days,
                    $balance->leaveType->max_carry_forward_days
                );

                $nextYearBalance = self::getOrCreate(
                    $businessId,
                    $balance->user_id,
                    $balance->leave_type_id,
                    $fromYear + 1
                );

                $nextYearBalance->carried_forward = $carryForwardDays;
                $nextYearBalance->total_days += $carryForwardDays;
                $nextYearBalance->recalculate();
            }
        }
    }
}
