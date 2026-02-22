<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\Store\StoreOrder;
use App\Models\Store\StorePaymentTransaction;
use App\Models\Store\TelegramStore;
use App\Services\Store\StoreOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Store Payme Webhook Controller.
 *
 * Handles Payme JSON-RPC merchant API for store orders.
 * Follows the same pattern as App\Services\Billing\PaymeService
 * but works with StorePaymentTransaction and StoreOrder models.
 *
 * @see https://developer.payme.uz/guides/merchant-api
 */
class StorePaymeWebhookController extends Controller
{
    // Payme Error Codes
    public const ERROR_INVALID_AMOUNT = -31001;
    public const ERROR_INVALID_ACCOUNT = -31003;
    public const ERROR_CANNOT_PERFORM = -31008;
    public const ERROR_INVALID_STATE = -31007;
    public const ERROR_ORDER_NOT_FOUND = -31050;
    public const ERROR_TRANSACTION_NOT_FOUND = -31003;
    public const ERROR_ALREADY_DONE = -31060;
    public const ERROR_UNKNOWN = -31099;

    // Transaction states (Payme protocol)
    public const STATE_CREATED = 1;
    public const STATE_COMPLETED = 2;
    public const STATE_CANCELLED = -1;
    public const STATE_CANCELLED_AFTER_COMPLETE = -2;

    // Cancel reasons
    public const REASON_UNKNOWN = 0;
    public const REASON_WRONG_AMOUNT = 1;
    public const REASON_ORDER_CANCELLED = 2;
    public const REASON_TIMEOUT = 3;
    public const REASON_REFUND = 4;

    protected string $logChannel = 'daily';

    public function __construct(
        protected StoreOrderService $orderService
    ) {}

    /**
     * Handle incoming Payme JSON-RPC request.
     */
    public function handle(Request $request, TelegramStore $store): JsonResponse
    {
        // Verify Basic Auth credentials against store's Payme merchant key
        if (! $this->verifyAuth($request, $store)) {
            return response()->json([
                'error' => [
                    'code' => -32504,
                    'message' => ['ru' => 'Unauthorized', 'uz' => 'Unauthorized', 'en' => 'Unauthorized'],
                ],
            ], 401);
        }

        $body = $request->all();
        $method = $body['method'] ?? null;
        $params = $body['params'] ?? [];
        $id = $body['id'] ?? null;

        $this->log("Incoming: {$method}", $params);

        $result = match ($method) {
            'CheckPerformTransaction' => $this->checkPerformTransaction($store, $params),
            'CreateTransaction' => $this->createTransaction($store, $params),
            'PerformTransaction' => $this->performTransaction($store, $params),
            'CancelTransaction' => $this->cancelTransaction($store, $params),
            'CheckTransaction' => $this->checkTransaction($store, $params),
            'GetStatement' => $this->getStatement($store, $params),
            default => $this->error(self::ERROR_UNKNOWN, "Unknown method: {$method}"),
        };

        // Add JSON-RPC id to response
        $result['id'] = $id;

        return response()->json($result);
    }

    /**
     * Verify Payme Basic Auth.
     */
    protected function verifyAuth(Request $request, TelegramStore $store): bool
    {
        $business = $store->business;

        $account = PaymentAccount::where('business_id', $business->id)
            ->where('provider', 'payme')
            ->active()
            ->first();

        if (! $account) {
            return false;
        }

        $authHeader = $request->header('Authorization', '');
        if (! str_starts_with($authHeader, 'Basic ')) {
            return false;
        }

        $decoded = base64_decode(substr($authHeader, 6));
        $parts = explode(':', $decoded, 2);

        if (count($parts) !== 2) {
            return false;
        }

        // Payme sends: "Paycom:{merchant_key}"
        return $parts[1] === $account->merchant_key;
    }

    /**
     * CheckPerformTransaction — Verify if order can be paid.
     */
    protected function checkPerformTransaction(TelegramStore $store, array $params): array
    {
        $this->log('CheckPerformTransaction', $params);

        try {
            $account = $params['account'] ?? [];
            $orderId = $account['order_id'] ?? null;
            $amountInTiyin = $params['amount'] ?? 0;

            if (! $orderId) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'order_id is required');
            }

            $order = StoreOrder::where('id', $orderId)
                ->where('store_id', $store->id)
                ->first();

            if (! $order) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'Order not found');
            }

            // Amount check (tiyin)
            $expectedAmount = (int) ($order->total * 100);
            if ((int) $amountInTiyin !== $expectedAmount) {
                return $this->error(
                    self::ERROR_INVALID_AMOUNT,
                    "Invalid amount. Expected: {$expectedAmount}, Got: {$amountInTiyin}"
                );
            }

            // Check order status
            if ($order->isPaid()) {
                return $this->error(self::ERROR_ALREADY_DONE, 'Order already paid');
            }

            if (in_array($order->status, [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Order cancelled');
            }

            return $this->success([
                'allow' => true,
                'additional' => [
                    'order_id' => $orderId,
                    'order_number' => $order->order_number,
                ],
            ]);

        } catch (\Exception $e) {
            $this->logError('CheckPerformTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * CreateTransaction — Create payment transaction.
     */
    protected function createTransaction(TelegramStore $store, array $params): array
    {
        $this->log('CreateTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;
            $account = $params['account'] ?? [];
            $orderId = $account['order_id'] ?? null;
            $amountInTiyin = $params['amount'] ?? 0;
            $paymeTime = $params['time'] ?? $this->currentTimeMs();

            if (! $orderId || ! $paymeId) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'order_id and id are required');
            }

            $order = StoreOrder::where('id', $orderId)
                ->where('store_id', $store->id)
                ->first();

            if (! $order) {
                return $this->error(self::ERROR_ORDER_NOT_FOUND, 'Order not found');
            }

            // Amount check
            $expectedAmount = (int) ($order->total * 100);
            if ((int) $amountInTiyin !== $expectedAmount) {
                return $this->error(self::ERROR_INVALID_AMOUNT, 'Invalid amount');
            }

            // Check for existing transaction with this payme_id
            $existing = StorePaymentTransaction::where('provider_transaction_id', $paymeId)
                ->where('store_id', $store->id)
                ->first();

            if ($existing) {
                $state = $this->getTransactionState($existing);

                if ($state === self::STATE_CREATED) {
                    return $this->success($this->buildCreateResponse($existing));
                }

                if ($state === self::STATE_COMPLETED) {
                    return $this->error(self::ERROR_ALREADY_DONE, 'Transaction already completed');
                }

                if ($state < 0) {
                    return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction cancelled');
                }
            }

            // Check order status
            if ($order->isPaid()) {
                return $this->error(self::ERROR_ALREADY_DONE, 'Order already paid');
            }

            if (in_array($order->status, [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Order cancelled');
            }

            // Create or update transaction
            return DB::transaction(function () use ($store, $order, $paymeId, $paymeTime) {
                $createTime = $this->currentTimeMs();

                $transaction = StorePaymentTransaction::updateOrCreate(
                    ['order_id' => $order->id, 'provider' => 'payme'],
                    [
                        'store_id' => $store->id,
                        'provider_transaction_id' => $paymeId,
                        'amount' => $order->total,
                        'status' => StorePaymentTransaction::STATUS_PROCESSING,
                        'metadata' => [
                            'payme_time' => $paymeTime,
                            'create_time' => $createTime,
                            'state' => self::STATE_CREATED,
                        ],
                    ]
                );

                $this->log('CreateTransaction: Success', [
                    'order_id' => $order->id,
                    'payme_id' => $paymeId,
                ]);

                return $this->success($this->buildCreateResponse($transaction));
            });

        } catch (\Exception $e) {
            $this->logError('CreateTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * PerformTransaction — Confirm payment.
     */
    protected function performTransaction(TelegramStore $store, array $params): array
    {
        $this->log('PerformTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;

            if (! $paymeId) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction ID required');
            }

            $transaction = StorePaymentTransaction::where('provider_transaction_id', $paymeId)
                ->where('store_id', $store->id)
                ->first();

            if (! $transaction) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
            }

            $state = $this->getTransactionState($transaction);

            // Already completed - idempotency
            if ($state === self::STATE_COMPLETED) {
                return $this->success($this->buildPerformResponse($transaction));
            }

            // Cancelled
            if ($state < 0) {
                return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction cancelled');
            }

            // Must be in created state
            if ($state !== self::STATE_CREATED) {
                return $this->error(self::ERROR_INVALID_STATE, 'Invalid transaction state');
            }

            // Check timeout (12 hours)
            $createTime = data_get($transaction->metadata, 'create_time', 0);
            if ($createTime > 0 && ($this->currentTimeMs() - $createTime) > 43200000) {
                $this->cancelTransactionRecord($transaction, self::REASON_TIMEOUT);

                return $this->error(self::ERROR_CANNOT_PERFORM, 'Transaction timeout');
            }

            return DB::transaction(function () use ($transaction) {
                $performTime = $this->currentTimeMs();

                // Update transaction
                $metadata = $transaction->metadata ?? [];
                $metadata['perform_time'] = $performTime;
                $metadata['state'] = self::STATE_COMPLETED;

                $transaction->update([
                    'status' => StorePaymentTransaction::STATUS_COMPLETED,
                    'paid_at' => now(),
                    'metadata' => $metadata,
                ]);

                // Mark order as paid
                $order = $transaction->order;
                if ($order) {
                    $this->orderService->handlePaymentCompleted($order, 'payme', $transaction->provider_transaction_id);
                }

                $this->log('PerformTransaction: Success', [
                    'order_id' => $transaction->order_id,
                    'amount' => $transaction->amount,
                ]);

                return $this->success($this->buildPerformResponse($transaction));
            });

        } catch (\Exception $e) {
            $this->logError('PerformTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * CancelTransaction — Cancel payment.
     */
    protected function cancelTransaction(TelegramStore $store, array $params): array
    {
        $this->log('CancelTransaction', $params);

        try {
            $paymeId = $params['id'] ?? null;
            $reason = $params['reason'] ?? self::REASON_UNKNOWN;

            if (! $paymeId) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction ID required');
            }

            $transaction = StorePaymentTransaction::where('provider_transaction_id', $paymeId)
                ->where('store_id', $store->id)
                ->first();

            if (! $transaction) {
                return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
            }

            $state = $this->getTransactionState($transaction);

            // Already cancelled
            if ($state < 0) {
                return $this->success($this->buildCancelResponse($transaction));
            }

            return DB::transaction(function () use ($transaction, $reason, $state) {
                $newState = $state === self::STATE_COMPLETED
                    ? self::STATE_CANCELLED_AFTER_COMPLETE
                    : self::STATE_CANCELLED;

                $this->cancelTransactionRecord($transaction, $reason, $newState);

                // Cancel the order if possible
                $order = $transaction->order;
                if ($order && $order->isCancellable()) {
                    $this->orderService->updateStatus(
                        $order,
                        StoreOrder::STATUS_CANCELLED,
                        'Payme to\'lov bekor qilindi',
                    );
                }

                $this->log('CancelTransaction: Success', [
                    'order_id' => $transaction->order_id,
                    'reason' => $reason,
                ]);

                return $this->success($this->buildCancelResponse($transaction->fresh()));
            });

        } catch (\Exception $e) {
            $this->logError('CancelTransaction', $e);
            return $this->error(self::ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * CheckTransaction — Get transaction state.
     */
    protected function checkTransaction(TelegramStore $store, array $params): array
    {
        $paymeId = $params['id'] ?? null;

        if (! $paymeId) {
            return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction ID required');
        }

        $transaction = StorePaymentTransaction::where('provider_transaction_id', $paymeId)
            ->where('store_id', $store->id)
            ->first();

        if (! $transaction) {
            return $this->error(self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
        }

        return $this->success([
            'create_time' => (int) data_get($transaction->metadata, 'create_time', 0),
            'perform_time' => (int) data_get($transaction->metadata, 'perform_time', 0),
            'cancel_time' => (int) data_get($transaction->metadata, 'cancel_time', 0),
            'transaction' => (string) $transaction->id,
            'state' => $this->getTransactionState($transaction),
            'reason' => data_get($transaction->metadata, 'reason'),
        ]);
    }

    /**
     * GetStatement — List transactions for a period.
     */
    protected function getStatement(TelegramStore $store, array $params): array
    {
        $from = $params['from'] ?? 0;
        $to = $params['to'] ?? $this->currentTimeMs();

        $transactions = StorePaymentTransaction::where('store_id', $store->id)
            ->where('provider', 'payme')
            ->whereNotNull('provider_transaction_id')
            ->get()
            ->filter(function ($t) use ($from, $to) {
                $createTime = data_get($t->metadata, 'create_time', 0);
                return $createTime >= $from && $createTime <= $to;
            })
            ->map(function ($t) {
                return [
                    'id' => $t->provider_transaction_id,
                    'time' => (int) data_get($t->metadata, 'payme_time', 0),
                    'amount' => (int) ($t->amount * 100),
                    'account' => ['order_id' => $t->order_id],
                    'create_time' => (int) data_get($t->metadata, 'create_time', 0),
                    'perform_time' => (int) data_get($t->metadata, 'perform_time', 0),
                    'cancel_time' => (int) data_get($t->metadata, 'cancel_time', 0),
                    'transaction' => (string) $t->id,
                    'state' => $this->getTransactionState($t),
                    'reason' => data_get($t->metadata, 'reason'),
                ];
            })
            ->values();

        return $this->success(['transactions' => $transactions->toArray()]);
    }

    // ========== HELPERS ==========

    protected function getTransactionState(StorePaymentTransaction $transaction): int
    {
        return (int) data_get($transaction->metadata, 'state', self::STATE_CREATED);
    }

    protected function cancelTransactionRecord(StorePaymentTransaction $transaction, int $reason, ?int $newState = null): void
    {
        $newState = $newState ?? self::STATE_CANCELLED;

        $metadata = $transaction->metadata ?? [];
        $metadata['cancel_time'] = $this->currentTimeMs();
        $metadata['state'] = $newState;
        $metadata['reason'] = $reason;

        $transaction->update([
            'status' => StorePaymentTransaction::STATUS_CANCELLED,
            'metadata' => $metadata,
        ]);
    }

    protected function buildCreateResponse(StorePaymentTransaction $transaction): array
    {
        return [
            'create_time' => (int) data_get($transaction->metadata, 'create_time', 0),
            'transaction' => (string) $transaction->id,
            'state' => self::STATE_CREATED,
        ];
    }

    protected function buildPerformResponse(StorePaymentTransaction $transaction): array
    {
        return [
            'transaction' => (string) $transaction->id,
            'perform_time' => (int) data_get($transaction->metadata, 'perform_time', 0),
            'state' => self::STATE_COMPLETED,
        ];
    }

    protected function buildCancelResponse(StorePaymentTransaction $transaction): array
    {
        return [
            'transaction' => (string) $transaction->id,
            'cancel_time' => (int) data_get($transaction->metadata, 'cancel_time', 0),
            'state' => $this->getTransactionState($transaction),
            'reason' => data_get($transaction->metadata, 'reason'),
        ];
    }

    protected function currentTimeMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    protected function success(array $result): array
    {
        return ['result' => $result];
    }

    protected function error(int $code, ?string $message = null): array
    {
        return [
            'error' => [
                'code' => $code,
                'message' => [
                    'ru' => $message ?? 'Unknown error',
                    'uz' => $message ?? 'Noma\'lum xatolik',
                    'en' => $message ?? 'Unknown error',
                ],
            ],
        ];
    }

    protected function log(string $method, array $data = []): void
    {
        Log::channel($this->logChannel)->info("[StorePayme] {$method}", $data);
    }

    protected function logError(string $method, \Exception $e): void
    {
        Log::channel($this->logChannel)->error("[StorePayme] {$method} Error", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
