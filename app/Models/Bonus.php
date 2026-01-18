<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bonus extends Model
{
    use BelongsToBusiness, HasUuid;

    const TYPE_PERFORMANCE = 'performance';

    const TYPE_ANNUAL = 'annual';

    const TYPE_SPOT = 'spot';

    const TYPE_REFERRAL = 'referral';

    const TYPES = [
        self::TYPE_PERFORMANCE => 'Ish Samaradorligi',
        self::TYPE_ANNUAL => 'Yillik',
        self::TYPE_SPOT => 'Bir Martalik',
        self::TYPE_REFERRAL => 'Tavsiya',
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'title',
        'description',
        'amount',
        'granted_date',
        'payroll_cycle_id',
        'is_paid',
        'paid_at',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'granted_date' => 'date',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==================== Accessors ====================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    // ==================== Scopes ====================

    public function scopePending($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
