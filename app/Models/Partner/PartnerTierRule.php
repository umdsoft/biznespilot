<?php

namespace App\Models\Partner;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Commission tier konfiguratsiyasi — admin boshqaradigan stavkalar jadvali.
 */
class PartnerTierRule extends Model
{
    use HasUuids;

    public const TIER_BRONZE = 'bronze';
    public const TIER_SILVER = 'silver';
    public const TIER_GOLD = 'gold';
    public const TIER_PLATINUM = 'platinum';

    protected $fillable = [
        'tier', 'name', 'icon',
        'year_one_rate', 'lifetime_rate',
        'min_active_referrals', 'min_monthly_volume_uzs',
        'perks', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'year_one_rate' => 'decimal:4',
        'lifetime_rate' => 'decimal:4',
        'min_active_referrals' => 'integer',
        'min_monthly_volume_uzs' => 'decimal:2',
        'perks' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function ordered()
    {
        return static::where('is_active', true)->orderBy('sort_order')->get();
    }
}
