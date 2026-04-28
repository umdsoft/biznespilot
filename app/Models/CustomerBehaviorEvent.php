<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerBehaviorEvent — mijoz xatti-harakatlari log'i.
 *
 * Marketing tahlili uchun yig'ib boriladi:
 *  - viewed_product, clicked_button, opened_card
 *  - asked_question (savol berdi)
 *  - added_to_cart, abandoned_cart
 *  - completed_purchase, cancelled_order
 *  - opened_message (broadcast'dan)
 *
 * Bu jadval CustomerProfileBuilder uchun manba bo'lib xizmat qiladi.
 * 90 kun saqlanadi, keyin avtomatik tozalanadi.
 */
class CustomerBehaviorEvent extends Model
{
    use BelongsToBusiness, HasUuids;

    public $timestamps = false; // faqat created_at — useCurrent

    protected $fillable = [
        'business_id',
        'telegram_user_id',
        'event_type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    // Asosiy event turlari
    public const EVENT_VIEWED_PRODUCT = 'viewed_product';
    public const EVENT_CLICKED_RECOMMENDATION = 'clicked_recommendation';
    public const EVENT_REJECTED_RECOMMENDATION = 'rejected_recommendation';
    public const EVENT_ADDED_TO_CART = 'added_to_cart';
    public const EVENT_ABANDONED_CART = 'abandoned_cart';
    public const EVENT_COMPLETED_PURCHASE = 'completed_purchase';
    public const EVENT_ASKED_QUESTION = 'asked_question';
    public const EVENT_ANSWERED_SURVEY = 'answered_survey';
    public const EVENT_OBJECTION_RAISED = 'objection_raised';

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    /**
     * Yangi event qaydlash — markaziy nuqta.
     */
    public static function track(string $businessId, string $userId, string $eventType, array $payload = []): self
    {
        return self::create([
            'business_id' => $businessId,
            'telegram_user_id' => $userId,
            'event_type' => $eventType,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
