<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DreamBuyer extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

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
        'priority',
        'is_primary',
        // 9 ta savol - "Sell Like Crazy" framework
        'where_spend_time',        // 1. Qayerda vaqt o'tkazadi?
        'info_sources',            // 2. Ma'lumot olish uchun qayerga murojaat qiladi?
        'frustrations',            // 3. Eng katta frustratsiyalari va qiyinchiliklari?
        'dreams',                  // 4. Orzulari va umidlari?
        'fears',                   // 5. Eng katta qo'rquvlari?
        'communication_preferences', // 6. Qaysi kommunikatsiya shaklini afzal ko'radi?
        'language_style',          // 7. Qanday til va jargon ishlatadi?
        'daily_routine',           // 8. Kundalik hayoti qanday?
        'happiness_triggers',      // 9. Nima uni baxtli qiladi?
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_primary' => 'boolean',
    ];

    /**
     * Get the CustDev survey linked to this Dream Buyer
     */
    public function survey(): HasOne
    {
        return $this->hasOne(CustdevSurvey::class, 'dream_buyer_id');
    }
}
