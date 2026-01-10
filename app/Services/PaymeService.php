<?php

namespace App\Services;

use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Models\PaymeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymeService
{
    protected ?PaymentAccount $account = null;

    // Payme Error Codes
    public const ERROR_INTERNAL_SYSTEM = -32400;
    public const ERROR_INSUFFICIENT_PRIVILEGE = -32504;
    public const ERROR_INVALID_JSON_RPC_OBJECT = -32600;
    public const ERROR_METHOD_NOT_FOUND = -32601;
    public const ERROR_INVALID_AMOUNT = -31001;
    public const ERROR_TRANSACTION_NOT_FOUND = -31003;
    public const ERROR_INVALID_ACCOUNT = -31050;
    public const ERROR_COULD_NOT_CANCEL = -31007;
    public const ERROR_COULD_NOT_PERFORM = -31008;

    /**
     * Set the payment account
     */
    public function setAccount(PaymentAccount $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Generate payment URL for Payme
     */
    public function generatePaymentUrl(PaymentTransaction $transaction, ?string $returnUrl = null): string
    {
        if (!$this->account) {
            throw new \Exception('Payment account not configured');
        }

        // Build params
        $params = [
            'm' => $this->account->merchant_id,
            'ac.order_id' => $transaction->order_id,
            'a' => $transaction->amount_in_tiyin,
        ];

        // Add return URL if provided
        if ($returnUrl) {
            $params['c'] = $returnUrl;
        }

        // Add language
        $params['l'] = 'uz';

        // Build params string
        $paramsString = implode(';', array_map(
            fn($key, $value) => "{$key}={$value}",
            array_keys($params),
            array_values($params)
        ));

        // Base64 encode
        $encoded = base64_encode($paramsString);

        // Get checkout URL based on test mode
        $checkoutUrl = $this->account->is_test_mode
            ? PaymentAccount::PAYME_TEST_CHECKOUT_URL
            : PaymentAccount::PAYME_CHECKOUT_URL;

        return "{$checkoutUrl}/{$encoded}";
    }

    /**
     * Handle Payme Merchant API webhook
     */
    public function handleWebhook(Request $request): array
    {
        $body = $request->all();

        Log::info('Payme webhook received', $body);

        // Validate JSON-RPC format
        if (!isset($body['method']) || !isset($body['params'])) {
            return $this->errorResponse(self::ERROR_INVALID_JSON_RPC_OBJECT, 'Invalid JSON-RPC object', $body['id'] ?? null);
        }

        $method = $body['method'];
        $params = $body['params'];
        $id = $body['id'] ?? null;

        // Authenticate request
        if (!$this->authenticate($request)) {
            return $this->errorResponse(self::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege', $id);
        }

        // Route to appropriate method
        return match ($method) {
            'CheckPerformTransaction' => $this->checkPerformTransaction($params, $id),
            'CreateTransaction' => $this->createTransaction($params, $id),
            'PerformTransaction' => $this->performTransaction($params, $id),
            'CancelTransaction' => $this->cancelTransaction($params, $id),
            'CheckTransaction' => $this->checkTransaction($params, $id),
            'GetStatement' => $this->getStatement($params, $id),
            default => $this->errorResponse(self::ERROR_METHOD_NOT_FOUND, 'Method not found', $id),
        };
    }

    /**
     * Authenticate Payme request
     */
    protected function authenticate(Request $request): bool
    {
        $auth = $request->header('Authorization');

        if (!$auth || !str_starts_with($auth, 'Basic ')) {
            return false;
        }

        $credentials = base64_decode(substr($auth, 6));
        [$login, $key] = explode(':', $credentials, 2);

        // Find account by merchant_id
        $this->account = PaymentAccount::where('merchant_id', $login)
            ->where('provider', PaymentAccount::PROVIDER_PAYME)
            ->where('is_active', true)
            ->first();

        if (!$this->account) {
            return false;
        }

        return $this->account->merchant_key === $key;
    }

    /**
     * CheckPerformTransaction - Check if transaction can be performed
     */
    protected function checkPerformTransaction(array $params, ?string $id): array
    {
        $orderId = $params['account']['order_id'] ?? null;
        $amount = $params['amount'] ?? 0;

        if (!$orderId) {
            return $this->errorResponse(self::ERROR_INVALID_ACCOUNT, 'Order not found', $id);
        }

        $transaction = PaymentTransaction::findByOrderId($orderId);

        if (!$transaction) {
            return $this->errorResponse(self::ERROR_INVALID_ACCOUNT, 'Order not found', $id);
        }

        // Check amount
        if ($transaction->amount_in_tiyin !== (int) $amount) {
            return $this->errorResponse(self::ERROR_INVALID_AMOUNT, 'Invalid amount', $id);
        }

        // Check if already paid
        if ($transaction->is_paid) {
            return $this->errorResponse(self::ERROR_COULD_NOT_PERFORM, 'Already paid', $id);
        }

        return $this->successResponse(['allow' => true], $id);
    }

    /**
     * CreateTransaction - Create new transaction
     */
    protected function createTransaction(array $params, ?string $id): array
    {
        $paymeTransactionId = $params['id'] ?? null;
        $orderId = $params['account']['order_id'] ?? null;
        $amount = $params['amount'] ?? 0;
        $time = $params['time'] ?? null;

        if (!$orderId) {
            return $this->errorResponse(self::ERROR_INVALID_ACCOUNT, 'Order not found', $id);
        }

        $transaction = PaymentTransaction::findByOrderId($orderId);

        if (!$transaction) {
            return $this->errorResponse(self::ERROR_INVALID_ACCOUNT, 'Order not found', $id);
        }

        // Check amount
        if ($transaction->amount_in_tiyin !== (int) $amount) {
            return $this->errorResponse(self::ERROR_INVALID_AMOUNT, 'Invalid amount', $id);
        }

        // Check if Payme transaction already exists
        $paymeTransaction = PaymeTransaction::where('payme_transaction_id', $paymeTransactionId)->first();

        if ($paymeTransaction) {
            // Return existing transaction
            return $this->successResponse([
                'create_time' => $paymeTransaction->create_time,
                'transaction' => $transaction->order_id,
                'state' => $paymeTransaction->state,
            ], $id);
        }

        // Check if transaction can be created
        if ($transaction->is_paid) {
            return $this->errorResponse(self::ERROR_COULD_NOT_PERFORM, 'Already paid', $id);
        }

        // Create Payme transaction record
        $createTime = $this->getMilliseconds();
        $paymeTransaction = PaymeTransaction::create([
            'payment_transaction_id' => $transaction->id,
            'payme_transaction_id' => $paymeTransactionId,
            'payme_time' => $time,
            'state' => PaymeTransaction::STATE_CREATED,
            'create_time' => $createTime,
        ]);

        // Update main transaction
        $transaction->markAsProcessing($paymeTransactionId);

        return $this->successResponse([
            'create_time' => $createTime,
            'transaction' => $transaction->order_id,
            'state' => PaymeTransaction::STATE_CREATED,
        ], $id);
    }

    /**
     * PerformTransaction - Complete the transaction
     */
    protected function performTransaction(array $params, ?string $id): array
    {
        $paymeTransactionId = $params['id'] ?? null;

        $paymeTransaction = PaymeTransaction::where('payme_transaction_id', $paymeTransactionId)->first();

        if (!$paymeTransaction) {
            return $this->errorResponse(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found', $id);
        }

        $transaction = $paymeTransaction->paymentTransaction;

        // If already completed
        if ($paymeTransaction->state === PaymeTransaction::STATE_COMPLETED) {
            return $this->successResponse([
                'transaction' => $transaction->order_id,
                'perform_time' => $paymeTransaction->perform_time,
                'state' => $paymeTransaction->state,
            ], $id);
        }

        // If cancelled
        if ($paymeTransaction->isCancelled()) {
            return $this->errorResponse(self::ERROR_COULD_NOT_PERFORM, 'Transaction cancelled', $id);
        }

        // Complete transaction
        $performTime = $this->getMilliseconds();
        $paymeTransaction->update([
            'state' => PaymeTransaction::STATE_COMPLETED,
            'perform_time' => $performTime,
        ]);

        // Mark main transaction as completed
        $transaction->markAsCompleted();

        Log::info('Payme payment completed', [
            'order_id' => $transaction->order_id,
            'amount' => $transaction->amount,
            'lead_id' => $transaction->lead_id,
        ]);

        return $this->successResponse([
            'transaction' => $transaction->order_id,
            'perform_time' => $performTime,
            'state' => PaymeTransaction::STATE_COMPLETED,
        ], $id);
    }

    /**
     * CancelTransaction - Cancel the transaction
     */
    protected function cancelTransaction(array $params, ?string $id): array
    {
        $paymeTransactionId = $params['id'] ?? null;
        $reason = $params['reason'] ?? null;

        $paymeTransaction = PaymeTransaction::where('payme_transaction_id', $paymeTransactionId)->first();

        if (!$paymeTransaction) {
            return $this->errorResponse(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found', $id);
        }

        $transaction = $paymeTransaction->paymentTransaction;

        // If already cancelled
        if ($paymeTransaction->isCancelled()) {
            return $this->successResponse([
                'transaction' => $transaction->order_id,
                'cancel_time' => $paymeTransaction->cancel_time,
                'state' => $paymeTransaction->state,
            ], $id);
        }

        // Determine cancel state
        $cancelTime = $this->getMilliseconds();
        $newState = $paymeTransaction->state === PaymeTransaction::STATE_COMPLETED
            ? PaymeTransaction::STATE_CANCELLED_AFTER_COMPLETE
            : PaymeTransaction::STATE_CANCELLED;

        $paymeTransaction->update([
            'state' => $newState,
            'reason' => $reason,
            'cancel_time' => $cancelTime,
        ]);

        // Cancel main transaction
        $transaction->markAsCancelled("Payme cancel reason: {$reason}");

        return $this->successResponse([
            'transaction' => $transaction->order_id,
            'cancel_time' => $cancelTime,
            'state' => $newState,
        ], $id);
    }

    /**
     * CheckTransaction - Check transaction status
     */
    protected function checkTransaction(array $params, ?string $id): array
    {
        $paymeTransactionId = $params['id'] ?? null;

        $paymeTransaction = PaymeTransaction::where('payme_transaction_id', $paymeTransactionId)->first();

        if (!$paymeTransaction) {
            return $this->errorResponse(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found', $id);
        }

        $transaction = $paymeTransaction->paymentTransaction;

        return $this->successResponse([
            'create_time' => $paymeTransaction->create_time,
            'perform_time' => $paymeTransaction->perform_time ?? 0,
            'cancel_time' => $paymeTransaction->cancel_time ?? 0,
            'transaction' => $transaction->order_id,
            'state' => $paymeTransaction->state,
            'reason' => $paymeTransaction->reason,
        ], $id);
    }

    /**
     * GetStatement - Get transactions list
     */
    protected function getStatement(array $params, ?string $id): array
    {
        $from = $params['from'] ?? 0;
        $to = $params['to'] ?? $this->getMilliseconds();

        $transactions = PaymeTransaction::whereBetween('create_time', [$from, $to])
            ->whereHas('paymentTransaction', function ($q) {
                $q->where('business_id', $this->account->business_id);
            })
            ->with('paymentTransaction')
            ->get()
            ->map(function ($pt) {
                $t = $pt->paymentTransaction;
                return [
                    'id' => $pt->payme_transaction_id,
                    'time' => $pt->payme_time,
                    'amount' => $t->amount_in_tiyin,
                    'account' => ['order_id' => $t->order_id],
                    'create_time' => $pt->create_time,
                    'perform_time' => $pt->perform_time ?? 0,
                    'cancel_time' => $pt->cancel_time ?? 0,
                    'transaction' => $t->order_id,
                    'state' => $pt->state,
                    'reason' => $pt->reason,
                ];
            });

        return $this->successResponse(['transactions' => $transactions->toArray()], $id);
    }

    /**
     * Success response
     */
    protected function successResponse(array $result, ?string $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * Error response
     */
    protected function errorResponse(int $code, string $message, ?string $id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => [
                    'ru' => $message,
                    'uz' => $message,
                    'en' => $message,
                ],
            ],
        ];
    }

    /**
     * Get current time in milliseconds
     */
    protected function getMilliseconds(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
