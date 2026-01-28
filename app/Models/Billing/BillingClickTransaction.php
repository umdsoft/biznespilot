<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BillingClickTransaction - Click tranzaksiya holati
 *
 * Click Flow:
 * 1. Prepare (action=0) - Tranzaksiya validatsiyasi
 * 2. Complete (action=1) - Tranzaksiya yakunlash
 *
 * Error Codes:
 * - 0: Success
 * - -1: Sign check failed
 * - -2: Incorrect amount
 * - -3: Transaction not found
 * - -4: Already paid
 * - -5: Order not found
 * - -6: Transaction cancelled
 * - -7: Payment failed
 * - -8: General error
 * - -9: Unknown error
 *
 * @property int $id
 * @property int $billing_transaction_id
 * @property int|null $click_trans_id
 * @property int|null $click_paydoc_id
 * @property string|null $merchant_trans_id
 * @property string|null $merchant_prepare_id
 * @property int|null $action
 * @property int $error_code
 * @property string|null $error_note
 * @property string|null $sign_string
 * @property string|null $sign_time
 * @property array|null $requests_log
 */
class BillingClickTransaction extends Model
{
    protected $table = 'billing_click_transactions';

    protected $fillable = [
        'billing_transaction_id',
        'click_trans_id',
        'click_paydoc_id',
        'merchant_trans_id',
        'merchant_prepare_id',
        'action',
        'error_code',
        'error_note',
        'sign_string',
        'sign_time',
        'requests_log',
    ];

    protected $casts = [
        'click_trans_id' => 'integer',
        'click_paydoc_id' => 'integer',
        'action' => 'integer',
        'error_code' => 'integer',
        'requests_log' => 'array',
    ];

    // Action types
    public const ACTION_PREPARE = 0;
    public const ACTION_COMPLETE = 1;

    // Error codes
    public const ERROR_SUCCESS = 0;
    public const ERROR_SIGN_CHECK_FAILED = -1;
    public const ERROR_INCORRECT_AMOUNT = -2;
    public const ERROR_TRANSACTION_NOT_FOUND = -3;
    public const ERROR_ALREADY_PAID = -4;
    public const ERROR_ORDER_NOT_FOUND = -5;
    public const ERROR_TRANSACTION_CANCELLED = -6;
    public const ERROR_PAYMENT_FAILED = -7;
    public const ERROR_GENERAL = -8;
    public const ERROR_UNKNOWN = -9;

    /**
     * Relationship to main transaction
     */
    public function billingTransaction(): BelongsTo
    {
        return $this->belongsTo(BillingTransaction::class);
    }

    // ============================================================
    // STATE HELPERS
    // ============================================================

    public function isPrepared(): bool
    {
        return $this->action === self::ACTION_PREPARE && $this->error_code === self::ERROR_SUCCESS;
    }

    public function isCompleted(): bool
    {
        return $this->action === self::ACTION_COMPLETE && $this->error_code === self::ERROR_SUCCESS;
    }

    public function hasError(): bool
    {
        return $this->error_code !== self::ERROR_SUCCESS;
    }

    // ============================================================
    // SIGNATURE VERIFICATION
    // ============================================================

    /**
     * Generate sign string for verification
     *
     * For Prepare:
     * md5(click_trans_id + service_id + secret_key + merchant_trans_id + amount + action + sign_time)
     *
     * For Complete:
     * md5(click_trans_id + service_id + secret_key + merchant_trans_id + merchant_prepare_id + amount + action + sign_time)
     */
    public static function generateSignString(
        int $clickTransId,
        int $serviceId,
        string $secretKey,
        string $merchantTransId,
        float $amount,
        int $action,
        string $signTime,
        ?string $merchantPrepareId = null
    ): string {
        if ($action === self::ACTION_COMPLETE && $merchantPrepareId) {
            return md5(
                $clickTransId .
                $serviceId .
                $secretKey .
                $merchantTransId .
                $merchantPrepareId .
                $amount .
                $action .
                $signTime
            );
        }

        return md5(
            $clickTransId .
            $serviceId .
            $secretKey .
            $merchantTransId .
            $amount .
            $action .
            $signTime
        );
    }

    /**
     * Verify incoming sign
     */
    public function verifySign(
        string $incomingSign,
        int $clickTransId,
        int $serviceId,
        string $secretKey,
        string $merchantTransId,
        float $amount,
        int $action,
        string $signTime,
        ?string $merchantPrepareId = null
    ): bool {
        $expectedSign = self::generateSignString(
            $clickTransId,
            $serviceId,
            $secretKey,
            $merchantTransId,
            $amount,
            $action,
            $signTime,
            $merchantPrepareId
        );

        return $incomingSign === $expectedSign;
    }

    // ============================================================
    // STATE TRANSITIONS
    // ============================================================

    public function markAsPrepared(int $clickTransId, string $merchantPrepareId): void
    {
        $this->update([
            'action' => self::ACTION_PREPARE,
            'click_trans_id' => $clickTransId,
            'merchant_prepare_id' => $merchantPrepareId,
            'error_code' => self::ERROR_SUCCESS,
        ]);
    }

    public function markAsCompleted(int $clickPaydocId = null): void
    {
        $this->update([
            'action' => self::ACTION_COMPLETE,
            'click_paydoc_id' => $clickPaydocId,
            'error_code' => self::ERROR_SUCCESS,
        ]);
    }

    public function markAsError(int $errorCode, string $errorNote = null): void
    {
        $this->update([
            'error_code' => $errorCode,
            'error_note' => $errorNote ?? $this->getErrorMessage($errorCode),
        ]);
    }

    // ============================================================
    // ERROR MESSAGES
    // ============================================================

    public function getErrorMessage(int $code): string
    {
        return config("billing.click_errors.{$code}")
            ?? config('billing.messages.system_error');
    }

    // ============================================================
    // LOGGING
    // ============================================================

    public function logRequest(string $action, array $params, array $response): void
    {
        $log = $this->requests_log ?? [];
        $log[] = [
            'action' => $action,
            'params' => $params,
            'response' => $response,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->update(['requests_log' => $log]);
    }

    // ============================================================
    // RESPONSE BUILDERS
    // ============================================================

    /**
     * Build Click response for Prepare
     */
    public function buildPrepareResponse(): array
    {
        return [
            'click_trans_id' => $this->click_trans_id,
            'merchant_trans_id' => $this->merchant_trans_id,
            'merchant_prepare_id' => $this->merchant_prepare_id,
            'error' => $this->error_code,
            'error_note' => $this->error_note ?? $this->getErrorMessage($this->error_code),
        ];
    }

    /**
     * Build Click response for Complete
     */
    public function buildCompleteResponse(): array
    {
        return [
            'click_trans_id' => $this->click_trans_id,
            'merchant_trans_id' => $this->merchant_trans_id,
            'merchant_confirm_id' => $this->billing_transaction_id,
            'error' => $this->error_code,
            'error_note' => $this->error_note ?? $this->getErrorMessage($this->error_code),
        ];
    }

    /**
     * Build error response
     */
    public static function buildErrorResponse(
        int $clickTransId,
        string $merchantTransId,
        int $errorCode,
        string $errorNote = null
    ): array {
        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'error' => $errorCode,
            'error_note' => $errorNote ?? config("billing.click_errors.{$errorCode}"),
        ];
    }
}
