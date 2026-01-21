<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use BelongsToBusiness, HasUuid;

    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_FAILED = 'failed';

    protected $fillable = [
        'business_id',
        'payroll_cycle_id',
        'user_id',
        'salary_structure_id',
        'base_salary',
        'working_days',
        'days_worked',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'overtime_amount',
        'allowances',
        'total_allowances',
        'deductions',
        'total_deductions',
        'gross_salary',
        'net_salary',
        'payment_method',
        'payment_status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'paid_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    // ==================== Accessors ====================

    public function getPaymentStatusLabelAttribute(): string
    {
        $labels = [
            self::PAYMENT_STATUS_PENDING => 'Kutilmoqda',
            self::PAYMENT_STATUS_PAID => "To'landi",
            self::PAYMENT_STATUS_FAILED => 'Xatolik',
        ];

        return $labels[$this->payment_status] ?? $this->payment_status;
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'yellow',
            self::PAYMENT_STATUS_PAID => 'green',
            self::PAYMENT_STATUS_FAILED => 'red',
            default => 'gray',
        };
    }

    // ==================== Scopes ====================

    public function scopePending($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
