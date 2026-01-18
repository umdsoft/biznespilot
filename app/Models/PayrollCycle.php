<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollCycle extends Model
{
    use BelongsToBusiness, HasUuid;

    const STATUS_DRAFT = 'draft';

    const STATUS_PROCESSING = 'processing';

    const STATUS_APPROVED = 'approved';

    const STATUS_PAID = 'paid';

    const STATUSES = [
        self::STATUS_DRAFT => 'Qoralama',
        self::STATUS_PROCESSING => 'Jarayonda',
        self::STATUS_APPROVED => 'Tasdiqlandi',
        self::STATUS_PAID => "To'landi",
    ];

    protected $fillable = [
        'business_id',
        'period',
        'start_date',
        'end_date',
        'payment_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(Bonus::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_PAID => 'purple',
            default => 'gray',
        };
    }

    public function getEmployeeCountAttribute(): int
    {
        return $this->payslips()->count();
    }

    // ==================== Scopes ====================

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    // ==================== Methods ====================

    public function calculateTotals(): void
    {
        $payslips = $this->payslips;

        $this->update([
            'total_gross' => $payslips->sum('gross_salary'),
            'total_deductions' => $payslips->sum('total_deductions'),
            'total_net' => $payslips->sum('net_salary'),
        ]);
    }

    public function approve($userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid($userId): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'processed_by' => $userId,
            'processed_at' => now(),
        ]);

        // Mark all payslips as paid
        $this->payslips()->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
