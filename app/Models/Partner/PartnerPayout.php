<?php

namespace App\Models\Partner;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerPayout extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';       // partner tomonidan so'ralgan
    public const STATUS_APPROVED = 'approved';     // admin tasdiqlagan
    public const STATUS_PROCESSING = 'processing'; // bankka yuborilgan
    public const STATUS_PAID = 'paid';             // tugadi
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const MIN_PAYOUT_UZS = 200000;

    protected $fillable = [
        'partner_id', 'total_amount', 'commissions_count',
        'status', 'payout_method', 'payout_reference', 'payout_details',
        'approved_by', 'approved_at', 'processed_at', 'paid_at',
        'failure_reason', 'note',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'commissions_count' => 'integer',
        'payout_details' => 'array',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class, 'payout_id');
    }
}
