<?php

namespace App\Models\Partner;

use App\Models\Business;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[\Illuminate\Database\Eloquent\Attributes\ObservedBy([\App\Observers\PartnerReferralObserver::class])]
class PartnerReferral extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ATTRIBUTED = 'attributed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CHURNED = 'churned';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'partner_id', 'business_id',
        'referred_via', 'ref_code_snapshot',
        'utm_source', 'utm_medium', 'utm_campaign',
        'attributed_at', 'first_payment_at', 'year_one_ends_at', 'churned_at',
        'status', 'lifetime_commission_earned',
    ];

    protected $casts = [
        'attributed_at' => 'datetime',
        'first_payment_at' => 'datetime',
        'year_one_ends_at' => 'datetime',
        'churned_at' => 'datetime',
        'lifetime_commission_earned' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class, 'referral_id');
    }

    /**
     * Birinchi to'lov yoziladimi? (commission rate tanlovi uchun).
     *
     * Yangi model: 1-to'lov = yuqori stavka, keyingi har bir to'lov =
     * lifetime stavka. Time-based emas, commission count asosida.
     */
    public function hasFirstPaymentRecorded(): bool
    {
        return $this->first_payment_at !== null;
    }

    /**
     * @deprecated 2026-04-23 — time-based year-one logic o'rniga endi
     *             birinchi to'lov count asosida.
     */
    public function isInYearOne(): bool
    {
        return ! $this->hasFirstPaymentRecorded();
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ATTRIBUTED, self::STATUS_ACTIVE]);
    }
}
