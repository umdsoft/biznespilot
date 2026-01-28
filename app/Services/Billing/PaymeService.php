<?php

namespace App\Services\Billing;

use App\Events\PaymentSuccessEvent;
use App\Models\Billing\BillingPaymeTransaction;
use App\Models\Billing\BillingTransaction;
use App\Models\Business;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymeService - Payme Merchant API Logikasi
 *
 * Bu servis Payme JSON-RPC protokolini boshqaradi.
 * Barcha metodlar Payme Merchant API hujjatlariga mos.
 *
 * MUHIM: Payme summani tiyinda (1 so'm = 100 tiyin) yuboradi!
 *
 * @see https://developer.payme.uz/guides/merchant-api
 */
class PaymeService
{
    /**
     * Payme Error Codes
     */
    public const ERROR_INVALID_AMOUNT = -31001;
    public const ERROR_INVALID_ACCOUNT = -31003;
    public const ERROR_CANNOT_PERFORM = -31008;
    public const ERROR_INVALID_STATE = -31007;
    public const ERROR_ORDER_NOT_FOUND = -31005;
    public const ERROR_TRANSACTION_NOT_FOUND = -31003;
    public const ERROR_ALREADY_DONE = -31060;
    public const ERROR_UNKNOWN = -31050;

    protected string $logChannel = 'billing';

    /**
     * CheckPerformTransaction - Tranzaksiya bajarilishi mumkinligini tekshirish
     *
     * Payme bu metoddi to'lovdan oldin chaqiradi.
     * Biz buyurtma mavjudligi va summa to'g'riligini tekshiramiz.
     */
    public function checkPerformTransaction(array $params): array
    {
        $this->log('CheckPerformTransaction', $params);

        try {
            // Account ma'lumotlarini olish
            $account = $params['account'] ?? [];
            $orderId = $account['order_id'] ?? null;
            $amountInTiyin = $params['amount'] ?? 0;

            // Order ID majburiy
            if (!$orderId) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'order_id is required');
            }

            // Tranzaksiyani topish
            $transaction = BillingTransaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'Order not found');
            }

            // Summa tekshiruvi (tiyinda)
            $expectedAmount = $transaction->getAmountInTiyin();
            if ((int) $amountInTiyin !== $expectedAmount) {
                return $this->error(
                    self::ERROR_INVALID_AMOUNT,
                    "Invalid amount. Expected: {$expectedAmount}, Got: {$amountInTiyin}"
                );
            }

            // Tranzaksiya holati tekshiruvi
            if ($transaction->isPaid()) {
                return $this->error(self::ERROR_ALREADY_DONE, 'Order already paid');
            }

            if ($transaction->isCancelled()) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Order cancelled');
            }

            if ($transaction->isExpired()) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Order expired');
            }

            // Business va Plan mavjudligini tekshirish
            $business = Business::find($transaction->business_id);
            $plan = Plan::find($transaction->plan_id);

            if (!$business || !$plan) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'Business or Plan not found');
            }

            $this->log('CheckPerformTransaction: Success', ['order_id' => $orderId]);

            return $this->success([
                'allow' => true,
                'additional' => [
                    'order_id' => $orderId,
                    'plan_name' => $plan->name,
                    'business_name' => $business->name,
                ],
            ]);

        } catch (\Exception $e) {
            $this->logError('CheckPerformTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * CreateTransaction - Tranzaksiya yaratish
     *
     * Payme bu metoddi to'lov jarayonini boshlash uchun chaqiradi.
     * IDEMPOTENCY: Agar tranzaksiya allaqachon mavjud bo'lsa, uni qaytarish.
     */
    public function createTransaction(array $params): array
    {
        $this->log('CreateTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;
            $account = $params['account'] ?? [];
            $orderId = $account['order_id'] ?? null;
            $amountInTiyin = $params['amount'] ?? 0;
            $paymeTime = $params['time'] ?? $this->getCurrentTime();

            if (!$orderId || !$paymeId) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'order_id and id are required');
            }

            // Main tranzaksiyani topish
            $transaction = BillingTransaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'Order not found');
            }

            // Summa tekshiruvi
            if ((int) $amountInTiyin !== $transaction->getAmountInTiyin()) {
                return $this->error(self::ERROR_INVALID_AMOUNT, 'Invalid amount');
            }

            // IDEMPOTENCY: Agar bu payme_id bilan tranzaksiya bor bo'lsa
            $existingPayme = BillingPaymeTransaction::where('payme_id', $paymeId)->first();

            if ($existingPayme) {
                // Timeout tekshiruvi
                if ($existingPayme->isExpired()) {
                    return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction timeout');
                }

                // Holat to'g'ri bo'lsa, eskisini qaytarish
                if ($existingPayme->isCreated()) {
                    return $this->success($existingPayme->buildCreateResponse());
                }

                if ($existingPayme->isCompleted()) {
                    return $this->error(self::ERROR_ALREADY_DONE, 'Transaction already completed');
                }

                if ($existingPayme->isCancelled()) {
                    return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction cancelled');
                }
            }

            // Agar boshqa payme tranzaksiya bu order uchun mavjud bo'lsa
            $existingForOrder = BillingPaymeTransaction::where('billing_transaction_id', $transaction->id)
                ->whereNotNull('payme_id')
                ->first();

            if ($existingForOrder && $existingForOrder->payme_id !== $paymeId) {
                // Timeout tekshiruvi
                if (!$existingForOrder->isExpired()) {
                    return $this->error(self::ERROR_CANNOT_PERFORM, 'Another transaction in progress');
                }
                // Expired bo'lsa, eski transactionni cancel qilamiz
                $existingForOrder->markAsCancelled(BillingPaymeTransaction::REASON_TIMEOUT);
            }

            // Tranzaksiya holati tekshiruvi
            if ($transaction->isPaid()) {
                return $this->error(self::ERROR_ALREADY_DONE, 'Order already paid');
            }

            if ($transaction->isCancelled() || $transaction->isFailed()) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Order cannot be processed');
            }

            if ($transaction->isExpired()) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Order expired');
            }

            // DB transaction ichida yaratish
            return DB::transaction(function () use ($transaction, $paymeId, $paymeTime) {
                $createTime = $this->getCurrentTime();

                // Payme tranzaksiya yaratish yoki yangilash
                $paymeTransaction = BillingPaymeTransaction::updateOrCreate(
                    ['billing_transaction_id' => $transaction->id],
                    [
                        'payme_id' => $paymeId,
                        'payme_time' => $paymeTime,
                        'state' => BillingPaymeTransaction::STATE_CREATED,
                        'create_time' => $createTime,
                    ]
                );

                // Main tranzaksiyani waiting ga o'tkazish
                $transaction->update([
                    'status' => BillingTransaction::STATUS_WAITING,
                    'provider_transaction_id' => $paymeId,
                ]);

                $this->log('CreateTransaction: Success', [
                    'order_id' => $transaction->order_id,
                    'payme_id' => $paymeId,
                ]);

                return $this->success($paymeTransaction->buildCreateResponse());
            });

        } catch (\Exception $e) {
            $this->logError('CreateTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * PerformTransaction - Tranzaksiyani bajarish (to'lovni tasdiqlash)
     *
     * Bu eng muhim metod! To'lov muvaffaqiyatli bo'lganda chaqiriladi.
     */
    public function performTransaction(array $params): array
    {
        $this->log('PerformTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;

            if (!$paymeId) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction ID required');
            }

            // Payme tranzaksiyani topish
            $paymeTransaction = BillingPaymeTransaction::where('payme_id', $paymeId)->first();

            if (!$paymeTransaction) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
            }

            $transaction = $paymeTransaction->billingTransaction;

            // Agar allaqachon bajarilgan bo'lsa
            if ($paymeTransaction->isCompleted()) {
                return $this->success($paymeTransaction->buildPerformResponse());
            }

            // Bekor qilingan bo'lsa
            if ($paymeTransaction->isCancelled()) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction cancelled');
            }

            // Faqat "created" holatida perform qilish mumkin
            if (!$paymeTransaction->canPerform()) {
                return $this->error(self::ERROR_INVALID_STATE, 'Invalid transaction state');
            }

            // Timeout tekshiruvi
            if ($paymeTransaction->isExpired()) {
                // Expired tranzaksiyani cancel qilish
                $paymeTransaction->markAsCancelled(BillingPaymeTransaction::REASON_TIMEOUT);
                $transaction->markAsCancelled('Transaction timeout', self::ERROR_CANNOT_PERFORM);

                return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction timeout');
            }

            // DB transaction ichida bajarish
            return DB::transaction(function () use ($paymeTransaction, $transaction) {
                // Payme tranzaksiyani completed qilish
                $paymeTransaction->markAsCompleted();

                // Main tranzaksiyani paid qilish
                $transaction->markAsPaid();

                // Event dispatch - bu obunani aktivlashtiradi
                event(new PaymentSuccessEvent($transaction));

                $this->log('PerformTransaction: Success', [
                    'order_id' => $transaction->order_id,
                    'amount' => $transaction->amount,
                ]);

                return $this->success($paymeTransaction->buildPerformResponse());
            });

        } catch (\Exception $e) {
            $this->logError('PerformTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * CancelTransaction - Tranzaksiyani bekor qilish
     */
    public function cancelTransaction(array $params): array
    {
        $this->log('CancelTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;
            $reason = $params['reason'] ?? BillingPaymeTransaction::REASON_UNKNOWN;

            if (!$paymeId) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction ID required');
            }

            $paymeTransaction = BillingPaymeTransaction::where('payme_id', $paymeId)->first();

            if (!$paymeTransaction) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
            }

            $transaction = $paymeTransaction->billingTransaction;

            // Agar allaqachon bekor qilingan bo'lsa
            if ($paymeTransaction->isCancelled()) {
                return $this->success($paymeTransaction->buildCancelResponse());
            }

            // Bekor qilish mumkinligini tekshirish
            if (!$paymeTransaction->canCancel()) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Cannot cancel transaction');
            }

            return DB::transaction(function () use ($paymeTransaction, $transaction, $reason) {
                // Payme tranzaksiyani cancel qilish
                $paymeTransaction->markAsCancelled($reason);

                // Main tranzaksiyani cancel qilish
                $cancelReason = $this->getCancelReasonText($reason);
                $transaction->markAsCancelled($cancelReason);

                // Agar to'lov bo'lgan bo'lsa (refund holati)
                if ($paymeTransaction->state === BillingPaymeTransaction::STATE_CANCELLED_AFTER_COMPLETE) {
                    // TODO: Obunani to'xtatish yoki pulni qaytarish logikasi
                    // Bu yerda RefundEvent dispatch qilish mumkin
                    $this->log('CancelTransaction: Refund required', [
                        'order_id' => $transaction->order_id,
                        'amount' => $transaction->amount,
                    ]);
                }

                $this->log('CancelTransaction: Success', [
                    'order_id' => $transaction->order_id,
                    'reason' => $reason,
                ]);

                return $this->success($paymeTransaction->buildCancelResponse());
            });

        } catch (\Exception $e) {
            $this->logError('CancelTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * CheckTransaction - Tranzaksiya holatini tekshirish
     */
    public function checkTransaction(array $params): array
    {
        $this->log('CheckTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;

            if (!$paymeId) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction ID required');
            }

            $paymeTransaction = BillingPaymeTransaction::where('payme_id', $paymeId)->first();

            if (!$paymeTransaction) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
            }

            return $this->success($paymeTransaction->buildCheckTransactionResponse());

        } catch (\Exception $e) {
            $this->logError('CheckTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * GetStatement - Tranzaksiyalar ro'yxati (reporting uchun)
     */
    public function getStatement(array $params): array
    {
        $this->log('GetStatement', $params);

        try {
            $from = $params['from'] ?? 0;
            $to = $params['to'] ?? $this->getCurrentTime();

            $transactions = BillingPaymeTransaction::whereBetween('create_time', [$from, $to])
                ->whereNotNull('payme_id')
                ->with('billingTransaction')
                ->get()
                ->map(function ($pt) {
                    $t = $pt->billingTransaction;
                    return [
                        'id' => $pt->payme_id,
                        'time' => $pt->payme_time,
                        'amount' => $t->getAmountInTiyin(),
                        'account' => [
                            'order_id' => $t->order_id,
                        ],
                        'create_time' => $pt->create_time,
                        'perform_time' => $pt->perform_time ?? 0,
                        'cancel_time' => $pt->cancel_time ?? 0,
                        'transaction' => (string) $t->id,
                        'state' => $pt->state,
                        'reason' => $pt->reason,
                    ];
                });

            return $this->success(['transactions' => $transactions->toArray()]);

        } catch (\Exception $e) {
            $this->logError('GetStatement', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get current time in milliseconds (Payme format)
     */
    protected function getCurrentTime(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Build success response
     */
    protected function success(array $result): array
    {
        return [
            'result' => $result,
        ];
    }

    /**
     * Build error response
     */
    protected function error(int $code, string $message = null): array
    {
        return [
            'error' => [
                'code' => $code,
                'message' => [
                    'ru' => $message ?? config("billing.payme_errors.{$code}"),
                    'uz' => $message ?? config("billing.payme_errors.{$code}"),
                    'en' => $message ?? config("billing.payme_errors.{$code}"),
                ],
            ],
        ];
    }

    /**
     * Get cancel reason text
     */
    protected function getCancelReasonText(int $reason): string
    {
        return match ($reason) {
            BillingPaymeTransaction::REASON_WRONG_AMOUNT => 'Wrong amount',
            BillingPaymeTransaction::REASON_ORDER_CANCELLED => 'Order cancelled',
            BillingPaymeTransaction::REASON_TIMEOUT => 'Transaction timeout',
            BillingPaymeTransaction::REASON_REFUND => 'Refund requested',
            default => 'Unknown reason',
        };
    }

    /**
     * Log info
     */
    protected function log(string $method, array $data = []): void
    {
        Log::channel($this->logChannel)->info("[Payme] {$method}", $data);
    }

    /**
     * Log error
     */
    protected function logError(string $method, \Exception $e): void
    {
        Log::channel($this->logChannel)->error("[Payme] {$method} Error", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
