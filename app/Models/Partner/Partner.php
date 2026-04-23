<?php

namespace App\Models\Partner;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Partner — hamkorlik dasturining asosiy aktori.
 *
 * Har bir partner biznespilot userga bog'langan. Partner biznespilot
 * panelga o'z account'i bilan kiradi va /partner prefix ostida o'z
 * dashboard'iga ega bo'ladi.
 */
class Partner extends Model
{
    use HasUuids, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_TERMINATED = 'terminated';

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_AGENCY = 'agency';
    public const TYPE_INFLUENCER = 'influencer';
    public const TYPE_INTEGRATOR = 'integrator';

    protected $fillable = [
        'user_id', 'code', 'status', 'tier', 'partner_type',
        'custom_year_one_rate', 'custom_lifetime_rate',
        'full_name', 'phone', 'telegram_id', 'company_name', 'inn_stir',
        'bank_name', 'bank_account', 'preferred_payout_method',
        'agreement_signed_at', 'agreement_version',
        'referrals_count_cached', 'active_referrals_count_cached',
        'lifetime_earned_cached', 'available_balance_cached',
        'admin_notes',
    ];

    protected $casts = [
        'custom_year_one_rate' => 'decimal:4',
        'custom_lifetime_rate' => 'decimal:4',
        'agreement_signed_at' => 'datetime',
        'referrals_count_cached' => 'integer',
        'active_referrals_count_cached' => 'integer',
        'lifetime_earned_cached' => 'decimal:2',
        'available_balance_cached' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->code)) {
                $p->code = self::generateUniqueCode($p->full_name ?? 'PARTNER');
            }
        });
    }

    public static function generateUniqueCode(string $base = 'PARTNER'): string
    {
        $slug = strtoupper(Str::slug(Str::limit($base, 8, ''), ''));
        $slug = preg_replace('/[^A-Z0-9]/', '', $slug) ?: 'PARTNER';

        do {
            $code = $slug . rand(1000, 9999);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    // ========== RELATIONSHIPS ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PartnerReferral::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(PartnerPayout::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(PartnerClick::class);
    }

    // ========== COMMISSION RATE RESOLUTION ==========

    /**
     * Birinchi to'lov uchun stavka — custom override bo'lmasa, tier stavkasi.
     *
     * DB column nomi year_one_rate bo'lib qoladi (schema migration qilmaymiz),
     * lekin semantika o'zgardi: endi birinchi to'lov uchun ishlatiladi.
     */
    public function getEffectiveFirstPaymentRate(): float
    {
        if ($this->custom_year_one_rate !== null) {
            return (float) $this->custom_year_one_rate;
        }

        $rule = PartnerTierRule::where('tier', $this->tier)->first();
        return (float) ($rule?->year_one_rate ?? 0.10);
    }

    /**
     * @deprecated 2026-04-23 — use getEffectiveFirstPaymentRate().
     *             Eski chaqiriqlar uchun qoldirilgan.
     */
    public function getEffectiveYearOneRate(): float
    {
        return $this->getEffectiveFirstPaymentRate();
    }

    /**
     * Joriy lifetime stavka — custom override bo'lmasa, tier stavkasi.
     */
    public function getEffectiveLifetimeRate(): float
    {
        if ($this->custom_lifetime_rate !== null) {
            return (float) $this->custom_lifetime_rate;
        }

        $rule = PartnerTierRule::where('tier', $this->tier)->first();
        return (float) ($rule?->lifetime_rate ?? 0.05);
    }

    // ========== HELPERS ==========

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getReferralLink(): string
    {
        return rtrim(config('app.url'), '/') . '/refer/' . $this->code;
    }
}
