<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BillingPaymeTransaction - Payme tranzaksiya holati
 *
 * Payme State Machine:
 * - State 1: Tranzaksiya yaratildi (created)
 * - State 2: Tranzaksiya bajarildi (completed)
 * - State -1: Yaratilgandan keyin bekor qilindi
 * - State -2: Bajarilgandan keyin bekor qilindi (refund)
 *
 * Cancel Reasons:
 * - 1: Unknown error
 * - 2: Wrong amount
 * - 3: Order cancelled by merchant
 * - 4: Transaction timeout
 * - 5: Refund by merchant
 *
 * @property int $id
 * @property int $billing_transaction_id
 * @property string|null $payme_id
 * @property int|null $payme_time
 * @property int $state
 * @property int|null $create_time
 * @property int|null $perform_time
 * @property int|null $cancel_time
 * @property int|null $reason
 * @property array|null $requests_log
 */
class BillingPaymeTransaction extends Model
{
    protected $table = 'billing_payme_transactions';

    protected $fillable = [
        'billing_transaction_id',
        'payme_id',
        'payme_time',
        'state',
        'create_time',
        'perform_time',
        'cancel_time',
        'reason',
        'requests_log',
    ];

    protected $casts = [
        'payme_time' => 'integer',
        'state' => 'integer',
        'create_time' => 'integer',
        'perform_time' => 'integer',
        'cancel_time' => 'integer',
        'reason' => 'integer',
        'requests_log' => 'array',
    ];

    // Payme States
    public const STATE_CREATED = 1;
    public const STATE_COMPLETED = 2;
    public const STATE_CANCELLED_AFTER_CREATE = -1;
    public const STATE_CANCELLED_AFTER_COMPLETE = -2;

    // Cancel Reasons
    public const REASON_UNKNOWN = 1;
    public const REASON_WRONG_AMOUNT = 2;
    public const REASON_ORDER_CANCELLED = 3;
    public const REASON_TIMEOUT = 4;
    public const REASON_REFUND = 5;

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

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function isCancelledAfterCreate(): bool
    {
        return $this->state === self::STATE_CANCELLED_AFTER_CREATE;
    }

    public function isCancelledAfterComplete(): bool
    {
        return $this->state === self::STATE_CANCELLED_AFTER_COMPLETE;
    }

    public function isCancelled(): bool
    {
        return in_array($this->state, [
            self::STATE_CANCELLED_AFTER_CREATE,
            self::STATE_CANCELLED_AFTER_COMPLETE,
        ]);
    }

    public function canPerform(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    public function canCancel(): bool
    {
        return in_array($this->state, [
            self::STATE_CREATED,
            self::STATE_COMPLETED,
        ]);
    }

    // ============================================================
    // STATE TRANSITIONS
    // ============================================================

    public function markAsCompleted(): void
    {
        $this->update([
            'state' => self::STATE_COMPLETED,
            'perform_time' => $this->getCurrentTime(),
        ]);
    }

    public function markAsCancelled(int $reason = self::REASON_UNKNOWN): void
    {
        $newState = $this->state === self::STATE_COMPLETED
            ? self::STATE_CANCELLED_AFTER_COMPLETE
            : self::STATE_CANCELLED_AFTER_CREATE;

        $this->update([
            'state' => $newState,
            'cancel_time' => $this->getCurrentTime(),
            'reason' => $reason,
        ]);
    }

    // ============================================================
    // TIME HELPERS
    // ============================================================

    /**
     * Get current time in Payme format (milliseconds)
     */
    public function getCurrentTime(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired(): bool
    {
        if ($this->state !== self::STATE_CREATED) {
            return false;
        }

        $timeout = config('billing.payme.timeout.create_transaction', 43200000);
        $createTime = $this->create_time ?? $this->payme_time;

        if (!$createTime) {
            return false;
        }

        return ($this->getCurrentTime() - $createTime) > $timeout;
    }

    // ============================================================
    // LOGGING
    // ============================================================

    public function logRequest(string $method, array $params, array $response): void
    {
        $log = $this->requests_log ?? [];
        $log[] = [
            'method' => $method,
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
     * Build Payme response for CheckPerformTransaction
     */
    public function buildCheckResponse(bool $allow = true, array $additional = []): array
    {
        return [
            'allow' => $allow,
            'additional' => $additional,
        ];
    }

    /**
     * Build Payme response for CreateTransaction
     */
    public function buildCreateResponse(): array
    {
        return [
            'create_time' => $this->create_time,
            'transaction' => (string) $this->billing_transaction_id,
            'state' => $this->state,
        ];
    }

    /**
     * Build Payme response for PerformTransaction
     */
    public function buildPerformResponse(): array
    {
        return [
            'transaction' => (string) $this->billing_transaction_id,
            'perform_time' => $this->perform_time,
            'state' => $this->state,
        ];
    }

    /**
     * Build Payme response for CancelTransaction
     */
    public function buildCancelResponse(): array
    {
        return [
            'transaction' => (string) $this->billing_transaction_id,
            'cancel_time' => $this->cancel_time,
            'state' => $this->state,
        ];
    }

    /**
     * Build Payme response for CheckTransaction
     */
    public function buildCheckTransactionResponse(): array
    {
        return [
            'create_time' => $this->create_time,
            'perform_time' => $this->perform_time,
            'cancel_time' => $this->cancel_time,
            'transaction' => (string) $this->billing_transaction_id,
            'state' => $this->state,
            'reason' => $this->reason,
        ];
    }
}
