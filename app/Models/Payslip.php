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

    const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_PENDING => 'Kutilmoqda',
        self::PAYMENT_STATUS_PAID => "To'landi",
        self::PAYMENT_STATUS_FAILED => 'Xatolik',
    ];

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
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getPaymentMethodLabelAttribute(): ?string
    {
        $labels = [
            'bank_transfer' => 'Bank O\'tkazmasi',
            'cash' => 'Naqd Pul',
            'check' => 'Chek',
        ];

        return $labels[$this->payment_method] ?? $this->payment_method;
    }

    public function getAttendancePercentageAttribute(): float
    {
        if ($this->working_days == 0) {
            return 0;
        }

        return round(($this->days_worked / $this->working_days) * 100, 2);
    }

    // ==================== Methods ====================

    public function calculateSalary(): void
    {
        $proRatedSalary = $this->working_days > 0
            ? ($this->base_salary / $this->working_days) * $this->days_worked
            : $this->base_salary;

        $totalAllowances = collect($this->allowances)->sum('amount') ?? 0;
        $totalDeductions = collect($this->deductions)->sum('amount') ?? 0;

        $grossSalary = $proRatedSalary + $totalAllowances + $this->overtime_amount;
        $netSalary = $grossSalary - $totalDeductions;

        $this->update([
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'gross_salary' => $grossSalary,
            'net_salary' => $netSalary,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => self::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
