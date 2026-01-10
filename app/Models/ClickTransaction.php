<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickTransaction extends Model
{
    use HasUuid;

    // Click Error Codes
    public const ERROR_SUCCESS = 0;
    public const ERROR_SIGN_CHECK_FAILED = -1;
    public const ERROR_INVALID_AMOUNT = -2;
    public const ERROR_ACTION_NOT_FOUND = -3;
    public const ERROR_ALREADY_DONE = -4;
    public const ERROR_USER_NOT_FOUND = -5;
    public const ERROR_TRANSACTION_NOT_FOUND = -6;
    public const ERROR_BAD_REQUEST = -8;
    public const ERROR_TRANSACTION_CANCELLED = -9;

    public const ERROR_MESSAGES = [
        self::ERROR_SUCCESS => 'Success',
        self::ERROR_SIGN_CHECK_FAILED => 'SIGN CHECK FAILED!',
        self::ERROR_INVALID_AMOUNT => 'Incorrect parameter amount',
        self::ERROR_ACTION_NOT_FOUND => 'Action not found',
        self::ERROR_ALREADY_DONE => 'Already done',
        self::ERROR_USER_NOT_FOUND => 'User does not exist',
        self::ERROR_TRANSACTION_NOT_FOUND => 'Transaction does not exist',
        self::ERROR_BAD_REQUEST => 'Bad request',
        self::ERROR_TRANSACTION_CANCELLED => 'Transaction cancelled',
    ];

    protected $fillable = [
        'payment_transaction_id',
        'click_trans_id',
        'click_paydoc_id',
        'merchant_prepare_id',
        'error_code',
        'error_note',
    ];

    protected $casts = [
        'click_trans_id' => 'integer',
        'click_paydoc_id' => 'integer',
        'error_code' => 'integer',
    ];

    // ==================== Relationships ====================

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    // ==================== Accessors ====================

    public function getIsSuccessAttribute(): bool
    {
        return $this->error_code === self::ERROR_SUCCESS;
    }

    public function getErrorMessageAttribute(): string
    {
        return self::ERROR_MESSAGES[$this->error_code] ?? 'Unknown error';
    }

    // ==================== Methods ====================

    public static function getErrorMessage(int $code): string
    {
        return self::ERROR_MESSAGES[$code] ?? 'Unknown error';
    }
}
