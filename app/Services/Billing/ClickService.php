<?php

namespace App\Services\Billing;

use App\Events\PaymentSuccessEvent;
use App\Models\Billing\BillingClickTransaction;
use App\Models\Billing\BillingTransaction;
use App\Models\Business;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ClickService - Click Merchant API Logikasi
 *
 * Bu servis Click "Prepare" va "Complete" protokolini boshqaradi.
 * MD5 imzo tekshiruvi bilan ishlaydi.
 *
 * Click Flow:
 * 1. User Click orqali to'lov qiladi
 * 2. Click "Prepare" so'rovi yuboradi (validatsiya)
 * 3. Biz merchant_prepare_id qaytaramiz
 * 4. Click "Complete" so'rovi yuboradi (to'lov tasdiqlash)
 * 5. Biz obunani aktivlashtiramiz
 *
 * @see https://docs.click.uz/
 */
class ClickService
{
    protected string $logChannel = 'billing';

    /**
     * Prepare - Tranzaksiya validatsiyasi
     *
     * Click bu metoddi to'lovdan oldin chaqiradi.
     * Biz imzani tekshiramiz va buyurtmani validatsiya qilamiz.
     */
    public function prepare(array $params): array
    {
        $this->log('Prepare', $params);

        try {
            // Parametrlarni olish
            $clickTransId = (int) ($params['click_trans_id'] ?? 0);
            $serviceId = (int) ($params['service_id'] ?? 0);
            $merchantTransId = $params['merchant_trans_id'] ?? ''; // Bizning order_id
            $amount = (float) ($params['amount'] ?? 0);
            $action = (int) ($params['action'] ?? 0);
            $signTime = $params['sign_time'] ?? '';
            $signString = $params['sign_string'] ?? '';
            $error = (int) ($params['error'] ?? 0);
            $errorNote = $params['error_note'] ?? '';

            // Click dan kelgan xatolik
            if ($error !== 0) {
                return $this->buildResponse($clickTransId, $merchantTransId, $error, $errorNote);
            }

            // Imzo tekshiruvi
            if (!$this->verifySignature($clickTransId, $serviceId, $merchantTransId, $amount, $action, $signTime, $signString)) {
                $this->log('Prepare: Sign check failed', [
                    'click_trans_id' => $clickTransId,
                    'merchant_trans_id' => $merchantTransId,
                ]);
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_SIGN_CHECK_FAILED,
                    'Sign check failed'
                );
            }

            // Tranzaksiyani topish
            $transaction = BillingTransaction::where('order_id', $merchantTransId)->first();

            if (!$transaction) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_ORDER_NOT_FOUND,
                    'Order not found'
                );
            }

            // Summa tekshiruvi
            if ((float) $transaction->amount !== $amount) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_INCORRECT_AMOUNT,
                    'Incorrect amount'
                );
            }

            // Tranzaksiya holati tekshiruvi
            if ($transaction->isPaid()) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_ALREADY_PAID,
                    'Order already paid'
                );
            }

            if ($transaction->isCancelled()) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_TRANSACTION_CANCELLED,
                    'Order cancelled'
                );
            }

            if ($transaction->isExpired()) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_TRANSACTION_CANCELLED,
                    'Order expired'
                );
            }

            // Business va Plan mavjudligini tekshirish
            $business = Business::find($transaction->business_id);
            $plan = Plan::find($transaction->plan_id);

            if (!$business || !$plan) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_ORDER_NOT_FOUND,
                    'Business or Plan not found'
                );
            }

            // Agar bu click_trans_id bilan tranzaksiya allaqachon mavjud bo'lsa
            $existingClick = BillingClickTransaction::where('click_trans_id', $clickTransId)->first();
            if ($existingClick && $existingClick->isPrepared()) {
                // Idempotency - eskisini qaytarish
                return $existingClick->buildPrepareResponse();
            }

            // DB transaction ichida yaratish
            return DB::transaction(function () use ($transaction, $clickTransId, $merchantTransId, $signString, $signTime) {
                // Click tranzaksiya yaratish yoki yangilash
                $clickTransaction = BillingClickTransaction::updateOrCreate(
                    ['billing_transaction_id' => $transaction->id],
                    [
                        'click_trans_id' => $clickTransId,
                        'merchant_trans_id' => $merchantTransId,
                        'merchant_prepare_id' => (string) $transaction->id,
                        'action' => BillingClickTransaction::ACTION_PREPARE,
                        'error_code' => BillingClickTransaction::ERROR_SUCCESS,
                        'sign_string' => $signString,
                        'sign_time' => $signTime,
                    ]
                );

                // Main tranzaksiyani waiting ga o'tkazish
                $transaction->update([
                    'status' => BillingTransaction::STATUS_WAITING,
                    'provider_transaction_id' => (string) $clickTransId,
                ]);

                $this->log('Prepare: Success', [
                    'order_id' => $merchantTransId,
                    'click_trans_id' => $clickTransId,
                ]);

                return $clickTransaction->buildPrepareResponse();
            });

        } catch (\Exception $e) {
            $this->logError('Prepare', $e);
            return $this->buildResponse(
                $params['click_trans_id'] ?? 0,
                $params['merchant_trans_id'] ?? '',
                BillingClickTransaction::ERROR_UNKNOWN,
                'System error'
            );
        }
    }

    /**
     * Complete - Tranzaksiyani yakunlash
     *
     * Click bu metoddi to'lov muvaffaqiyatli bo'lganda chaqiradi.
     */
    public function complete(array $params): array
    {
        $this->log('Complete', $params);

        try {
            // Parametrlarni olish
            $clickTransId = (int) ($params['click_trans_id'] ?? 0);
            $serviceId = (int) ($params['service_id'] ?? 0);
            $clickPaydocId = (int) ($params['click_paydoc_id'] ?? 0);
            $merchantTransId = $params['merchant_trans_id'] ?? '';
            $merchantPrepareId = $params['merchant_prepare_id'] ?? '';
            $amount = (float) ($params['amount'] ?? 0);
            $action = (int) ($params['action'] ?? 1);
            $signTime = $params['sign_time'] ?? '';
            $signString = $params['sign_string'] ?? '';
            $error = (int) ($params['error'] ?? 0);
            $errorNote = $params['error_note'] ?? '';

            // Click dan kelgan xatolik
            if ($error !== 0) {
                // Click xatosi - tranzaksiyani cancel qilish kerak
                $this->handleClickError($merchantTransId, $error, $errorNote);
                return $this->buildResponse($clickTransId, $merchantTransId, $error, $errorNote);
            }

            // Imzo tekshiruvi (complete uchun merchant_prepare_id ham kerak)
            if (!$this->verifySignature($clickTransId, $serviceId, $merchantTransId, $amount, $action, $signTime, $signString, $merchantPrepareId)) {
                $this->log('Complete: Sign check failed', [
                    'click_trans_id' => $clickTransId,
                    'merchant_trans_id' => $merchantTransId,
                ]);
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_SIGN_CHECK_FAILED,
                    'Sign check failed'
                );
            }

            // Click tranzaksiyani topish
            $clickTransaction = BillingClickTransaction::where('click_trans_id', $clickTransId)->first();

            if (!$clickTransaction) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_TRANSACTION_NOT_FOUND,
                    'Transaction not found'
                );
            }

            $transaction = $clickTransaction->billingTransaction;

            // Tranzaksiya holati tekshiruvi
            if ($transaction->isPaid()) {
                // Idempotency - allaqachon to'langan
                return $clickTransaction->buildCompleteResponse();
            }

            if ($transaction->isCancelled()) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_TRANSACTION_CANCELLED,
                    'Transaction cancelled'
                );
            }

            // Prepare bo'lmagan tranzaksiya uchun complete qilib bo'lmaydi
            if (!$clickTransaction->isPrepared()) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_PAYMENT_FAILED,
                    'Transaction not prepared'
                );
            }

            // Summa tekshiruvi
            if ((float) $transaction->amount !== $amount) {
                return $this->buildResponse(
                    $clickTransId,
                    $merchantTransId,
                    BillingClickTransaction::ERROR_INCORRECT_AMOUNT,
                    'Amount mismatch'
                );
            }

            // DB transaction ichida bajarish
            return DB::transaction(function () use ($clickTransaction, $transaction, $clickPaydocId) {
                // Click tranzaksiyani completed qilish
                $clickTransaction->markAsCompleted($clickPaydocId);

                // Main tranzaksiyani paid qilish
                $transaction->markAsPaid();

                // Event dispatch - bu obunani aktivlashtiradi
                event(new PaymentSuccessEvent($transaction));

                $this->log('Complete: Success', [
                    'order_id' => $transaction->order_id,
                    'amount' => $transaction->amount,
                    'click_paydoc_id' => $clickPaydocId,
                ]);

                return $clickTransaction->buildCompleteResponse();
            });

        } catch (\Exception $e) {
            $this->logError('Complete', $e);
            return $this->buildResponse(
                $params['click_trans_id'] ?? 0,
                $params['merchant_trans_id'] ?? '',
                BillingClickTransaction::ERROR_UNKNOWN,
                'System error'
            );
        }
    }

    // ============================================================
    // SIGNATURE VERIFICATION
    // ============================================================

    /**
     * Verify Click signature
     */
    protected function verifySignature(
        int $clickTransId,
        int $serviceId,
        string $merchantTransId,
        float $amount,
        int $action,
        string $signTime,
        string $signString,
        ?string $merchantPrepareId = null
    ): bool {
        $secretKey = config('billing.click.secret_key');
        $configServiceId = (int) config('billing.click.service_id');

        // Service ID tekshiruvi
        if ($serviceId !== $configServiceId) {
            $this->log('Signature: Invalid service_id', [
                'expected' => $configServiceId,
                'received' => $serviceId,
            ]);
            return false;
        }

        // Sign string generatsiya
        $expectedSign = BillingClickTransaction::generateSignString(
            $clickTransId,
            $serviceId,
            $secretKey,
            $merchantTransId,
            $amount,
            $action,
            $signTime,
            $merchantPrepareId
        );

        if ($signString !== $expectedSign) {
            $this->log('Signature: Mismatch', [
                'expected' => $expectedSign,
                'received' => $signString,
            ]);
            return false;
        }

        return true;
    }

    // ============================================================
    // ERROR HANDLING
    // ============================================================

    /**
     * Handle Click error (e.g., payment cancelled by user)
     */
    protected function handleClickError(string $merchantTransId, int $error, string $errorNote): void
    {
        $transaction = BillingTransaction::where('order_id', $merchantTransId)->first();

        if ($transaction && $transaction->canBeCancelled()) {
            $transaction->markAsCancelled("Click error: {$errorNote}", $error);

            $clickTransaction = $transaction->clickTransaction;
            if ($clickTransaction) {
                $clickTransaction->markAsError($error, $errorNote);
            }

            $this->log('HandleClickError: Transaction cancelled', [
                'order_id' => $merchantTransId,
                'error' => $error,
                'error_note' => $errorNote,
            ]);
        }
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Build Click response
     */
    protected function buildResponse(
        int $clickTransId,
        string $merchantTransId,
        int $error,
        string $errorNote = null
    ): array {
        return BillingClickTransaction::buildErrorResponse(
            $clickTransId,
            $merchantTransId,
            $error,
            $errorNote
        );
    }

    /**
     * Log info
     */
    protected function log(string $method, array $data = []): void
    {
        Log::channel($this->logChannel)->info("[Click] {$method}", $data);
    }

    /**
     * Log error
     */
    protected function logError(string $method, \Exception $e): void
    {
        Log::channel($this->logChannel)->error("[Click] {$method} Error", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
