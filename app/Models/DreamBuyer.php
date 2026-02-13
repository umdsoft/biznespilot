<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class DreamBuyer extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    protected static function booted(): void
    {
        $clearCache = function (DreamBuyer $dreamBuyer) {
            Cache::forget("dream_buyer_ctx:{$dreamBuyer->business_id}");
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'data',
        'age_range',
        'gender',
        'location',
        'occupation',
        'income_level',
        'interests',
        'pain_points',
        'goals',
        'objections',
        'buying_triggers',
        'preferred_channels',
        'priority',
        'is_primary',
        'is_active',
        'avatar_summary',
        // 9 ta savol - "Sell Like Crazy" framework (bazadagi ustunlar)
        'q1_who_are_they',
        'q2_what_do_they_want',
        'q3_where_do_they_hang_out',
        'q4_what_keeps_them_up',
        'q5_what_are_they_afraid_of',
        'q6_what_are_they_frustrated_with',
        'q7_what_trends_affect_them',
        'q8_what_do_they_secretly_want',
        'q9_how_do_they_make_decisions',
        // Semantik ustunlar (migratsiya bilan qo'shilgan)
        'info_sources',
        'language_style',
        'communication_preferences',
        'daily_routine',
        'happiness_triggers',
        // Accessor/mutator orqali mapping (fillable da bo'lishi kerak)
        'where_spend_time',
        'frustrations',
        'dreams',
        'fears',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * where_spend_time → q3_where_do_they_hang_out
     */
    protected function whereSpendTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['q3_where_do_they_hang_out'] ?? null,
            set: fn ($value) => ['q3_where_do_they_hang_out' => $value],
        );
    }

    /**
     * frustrations → q6_what_are_they_frustrated_with
     */
    protected function frustrations(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['q6_what_are_they_frustrated_with'] ?? null,
            set: fn ($value) => ['q6_what_are_they_frustrated_with' => $value],
        );
    }

    /**
     * dreams → q8_what_do_they_secretly_want
     */
    protected function dreams(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['q8_what_do_they_secretly_want'] ?? null,
            set: fn ($value) => ['q8_what_do_they_secretly_want' => $value],
        );
    }

    /**
     * fears → q5_what_are_they_afraid_of
     */
    protected function fears(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['q5_what_are_they_afraid_of'] ?? null,
            set: fn ($value) => ['q5_what_are_they_afraid_of' => $value],
        );
    }

    /**
     * Get the CustDev survey linked to this Dream Buyer
     */
    public function survey(): HasOne
    {
        return $this->hasOne(CustdevSurvey::class, 'dream_buyer_id');
    }
}
