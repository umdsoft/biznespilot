<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerNeedProfile — har conversation uchun AI tomonidan to'plangan
 * ehtiyoj profili. Marketing va Sotuv mutaxassislari ko'radi.
 *
 * Telegram Sales Bot ishlash davomida har xabarda yangilanib boradi:
 *  - mijoz nima izlayotganini AI extract qiladi
 *  - byudjet, o'lcham, brand tarafni saqlaydi
 *  - tavsiya qilingan/rad etilgan mahsulotlarni eslab qoladi
 */
class CustomerNeedProfile extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'telegram_user_id',
        'conversation_id',
        'primary_intent',
        'use_case',
        'constraints',
        'viewed_products',
        'rejected_products',
        'recommended_products',
        'info_completeness',
        'ready_to_buy',
        'blockers',
        'current_state',
    ];

    protected $casts = [
        'constraints' => 'array',
        'viewed_products' => 'array',
        'rejected_products' => 'array',
        'recommended_products' => 'array',
        'info_completeness' => 'decimal:2',
        'ready_to_buy' => 'boolean',
        'blockers' => 'array',
    ];

    public const STATE_GREETING = 'greeting';
    public const STATE_DISCOVERY = 'discovery';
    public const STATE_RECOMMEND = 'recommend';
    public const STATE_OBJECTION = 'objection';
    public const STATE_CHECKOUT = 'checkout';
    public const STATE_POST_SALE = 'post_sale';

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TelegramConversation::class);
    }

    /**
     * Tavsiya berish uchun yetarli ma'lumot to'planganmi?
     */
    public function isReadyForRecommendation(): bool
    {
        return (float) $this->info_completeness >= 0.6;
    }

    /**
     * Lead yaratish uchun yetarli ma'lumot to'planganmi?
     */
    public function isReadyForLead(): bool
    {
        return (float) $this->info_completeness >= 0.5
            && ! empty($this->primary_intent);
    }
}
