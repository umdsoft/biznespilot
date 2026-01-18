<?php

namespace App\Services;

use App\Models\ClickTransaction;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickService
{
    protected ?PaymentAccount $account = null;

    // Click Actions
    public const ACTION_PREPARE = 0;

    public const ACTION_COMPLETE = 1;

    /**
     * Set the payment account
     */
    public function setAccount(PaymentAccount $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Generate payment URL for Click
     */
    public function generatePaymentUrl(PaymentTransaction $transaction, ?string $returnUrl = null): string
    {
        if (! $this->account) {
            throw new \Exception('Payment account not configured');
        }

        // Click payment URL format
        $params = http_build_query([
            'service_id' => $this->account->service_id,
            'merchant_id' => $this->account->merchant_user_id,
            'amount' => $transaction->amount,
            'transaction_param' => $transaction->order_id,
            'return_url' => $returnUrl ?? url('/'),
        ]);

        return PaymentAccount::CLICK_CHECKOUT_URL.'?'.$params;
    }

    /**
     * Create invoice via Click API
     */
    public function createInvoice(PaymentTransaction $transaction, string $phoneNumber): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'Account not configured'];
        }

        try {
            $timestamp = time();
            $digest = sha1($timestamp.$this->account->secret_key);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Auth' => "{$this->account->merchant_user_id}:{$digest}:{$timestamp}",
            ])->post(PaymentAccount::CLICK_API_URL.'/invoice/create', [
                'service_id' => (int) $this->account->service_id,
                'amount' => (float) $transaction->amount,
                'phone_number' => $phoneNumber,
                'merchant_trans_id' => $transaction->order_id,
            ]);

            $data = $response->json();

            if (isset($data['error_code']) && $data['error_code'] === 0) {
                return [
                    'success' => true,
                    'invoice_id' => $data['invoice_id'] ?? null,
                    'payment_url' => $this->generatePaymentUrl($transaction),
                ];
            }

            return [
                'success' => false,
                'error' => $data['error_note'] ?? 'Unknown error',
                'error_code' => $data['error_code'] ?? -1,
            ];
        } catch (\Exception $e) {
            Log::error('Click create invoice failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Click webhook (SHOP API)
     */
    public function handleWebhook(Request $request): array
    {
        $data = $request->all();

        Log::info('Click webhook received', $data);

        // Get required fields
        $clickTransId = $data['click_trans_id'] ?? null;
        $serviceId = $data['service_id'] ?? null;
        $merchantTransId = $data['merchant_trans_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $action = (int) ($data['action'] ?? -1);
        $signTime = $data['sign_time'] ?? null;
        $signString = $data['sign_string'] ?? null;
        $error = (int) ($data['error'] ?? 0);

        // Find account by service_id
        $this->account = PaymentAccount::where('service_id', $serviceId)
            ->where('provider', PaymentAccount::PROVIDER_CLICK)
            ->where('is_active', true)
            ->first();

        if (! $this->account) {
            return $this->errorResponse(
                ClickTransaction::ERROR_USER_NOT_FOUND,
                $clickTransId,
                $merchantTransId
            );
        }

        // Verify signature
        if (! $this->verifySignature($data)) {
            return $this->errorResponse(
                ClickTransaction::ERROR_SIGN_CHECK_FAILED,
                $clickTransId,
                $merchantTransId
            );
        }

        // Find transaction
        $transaction = PaymentTransaction::findByOrderId($merchantTransId);

        if (! $transaction) {
            return $this->errorResponse(
                ClickTransaction::ERROR_USER_NOT_FOUND,
                $clickTransId,
                $merchantTransId
            );
        }

        // Check amount
        if ((float) $transaction->amount !== (float) $amount) {
            return $this->errorResponse(
                ClickTransaction::ERROR_INVALID_AMOUNT,
                $clickTransId,
                $merchantTransId
            );
        }

        // Handle by action
        return match ($action) {
            self::ACTION_PREPARE => $this->handlePrepare($data, $transaction),
            self::ACTION_COMPLETE => $this->handleComplete($data, $transaction),
            default => $this->errorResponse(
                ClickTransaction::ERROR_ACTION_NOT_FOUND,
                $clickTransId,
                $merchantTransId
            ),
        };
    }

    /**
     * Handle Prepare action (validation)
     */
    protected function handlePrepare(array $data, PaymentTransaction $transaction): array
    {
        $clickTransId = $data['click_trans_id'];

        // Check if already processed
        if ($transaction->is_paid) {
            return $this->errorResponse(
                ClickTransaction::ERROR_ALREADY_DONE,
                $clickTransId,
                $transaction->order_id
            );
        }

        // Create Click transaction record
        $clickTransaction = ClickTransaction::create([
            'payment_transaction_id' => $transaction->id,
            'click_trans_id' => $clickTransId,
            'merchant_prepare_id' => $transaction->id,
            'error_code' => ClickTransaction::ERROR_SUCCESS,
        ]);

        // Update main transaction
        $transaction->markAsProcessing((string) $clickTransId);

        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $transaction->order_id,
            'merchant_prepare_id' => $transaction->id,
            'error' => ClickTransaction::ERROR_SUCCESS,
            'error_note' => 'Success',
        ];
    }

    /**
     * Handle Complete action (finalization)
     */
    protected function handleComplete(array $data, PaymentTransaction $transaction): array
    {
        $clickTransId = $data['click_trans_id'];
        $merchantPrepareId = $data['merchant_prepare_id'] ?? null;
        $error = (int) ($data['error'] ?? 0);

        // Find Click transaction
        $clickTransaction = ClickTransaction::where('click_trans_id', $clickTransId)->first();

        if (! $clickTransaction) {
            return $this->errorResponse(
                ClickTransaction::ERROR_TRANSACTION_NOT_FOUND,
                $clickTransId,
                $transaction->order_id
            );
        }

        // Check if cancelled by Click
        if ($error < 0) {
            $clickTransaction->update([
                'error_code' => ClickTransaction::ERROR_TRANSACTION_CANCELLED,
                'error_note' => 'Cancelled by Click',
            ]);
            $transaction->markAsCancelled('Cancelled by Click');

            return $this->errorResponse(
                ClickTransaction::ERROR_TRANSACTION_CANCELLED,
                $clickTransId,
                $transaction->order_id
            );
        }

        // Check if already completed
        if ($transaction->is_paid) {
            return $this->errorResponse(
                ClickTransaction::ERROR_ALREADY_DONE,
                $clickTransId,
                $transaction->order_id
            );
        }

        // Complete transaction
        $clickTransaction->update([
            'click_paydoc_id' => $data['click_paydoc_id'] ?? null,
            'error_code' => ClickTransaction::ERROR_SUCCESS,
        ]);

        $transaction->markAsCompleted();

        Log::info('Click payment completed', [
            'order_id' => $transaction->order_id,
            'amount' => $transaction->amount,
            'lead_id' => $transaction->lead_id,
        ]);

        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $transaction->order_id,
            'merchant_confirm_id' => $transaction->id,
            'error' => ClickTransaction::ERROR_SUCCESS,
            'error_note' => 'Success',
        ];
    }

    /**
     * Verify Click signature
     */
    protected function verifySignature(array $data): bool
    {
        $clickTransId = $data['click_trans_id'] ?? '';
        $serviceId = $data['service_id'] ?? '';
        $merchantTransId = $data['merchant_trans_id'] ?? '';
        $merchantPrepareId = $data['merchant_prepare_id'] ?? '';
        $amount = $data['amount'] ?? '';
        $action = $data['action'] ?? '';
        $signTime = $data['sign_time'] ?? '';
        $signString = $data['sign_string'] ?? '';

        // Build sign string based on action
        $action = (int) $action;
        if ($action === self::ACTION_PREPARE) {
            $toSign = $clickTransId.$serviceId.$this->account->secret_key.
                $merchantTransId.$amount.$action.$signTime;
        } else {
            $toSign = $clickTransId.$serviceId.$this->account->secret_key.
                $merchantTransId.$merchantPrepareId.$amount.$action.$signTime;
        }

        $expectedSign = md5($toSign);

        return $expectedSign === $signString;
    }

    /**
     * Error response
     */
    protected function errorResponse(int $errorCode, $clickTransId, string $merchantTransId): array
    {
        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'error' => $errorCode,
            'error_note' => ClickTransaction::getErrorMessage($errorCode),
        ];
    }

    /**
     * Check payment status via Click API
     */
    public function checkPaymentStatus(string $paymentId): array
    {
        if (! $this->account) {
            return ['success' => false, 'error' => 'Account not configured'];
        }

        try {
            $timestamp = time();
            $digest = sha1($timestamp.$this->account->secret_key);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Auth' => "{$this->account->merchant_user_id}:{$digest}:{$timestamp}",
            ])->get(PaymentAccount::CLICK_API_URL."/payment/status/{$this->account->service_id}/{$paymentId}");

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
