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
 * Store Click Webhook Controller.
 *
 * Handles Click Prepare/Complete protocol for store orders.
 * Follows the same pattern as App\Services\Billing\ClickService
 * but works with StorePaymentTransaction and StoreOrder models.
 *
 * @see https://docs.click.uz/
 */
class StoreClickWebhookController extends Controller
{
    // Click Error Codes
    public const ERROR_SUCCESS = 0;
    public const ERROR_SIGN_CHECK_FAILED = -1;
    public const ERROR_INCORRECT_AMOUNT = -2;
    public const ERROR_ACTION_NOT_FOUND = -3;
    public const ERROR_ALREADY_PAID = -4;
    public const ERROR_ORDER_NOT_FOUND = -5;
    public const ERROR_TRANSACTION_NOT_FOUND = -6;
    public const ERROR_PAYMENT_FAILED = -7;
    public const ERROR_TRANSACTION_CANCELLED = -9;
    public const ERROR_UNKNOWN = -8;

    protected string $logChannel = 'daily';

    public function __construct(
        protected StoreOrderService $orderService
    ) {}

    /**
     * POST /click/{store}/prepare — Click Prepare endpoint.
     *
     * Validates the order and creates a transaction record.
     */
    public function prepare(Request $request, TelegramStore $store): JsonResponse
    {
        $params = $request->all();
        $this->log('Prepare', $params);

        try {
            $clickTransId = (int) ($params['click_trans_id'] ?? 0);
            $serviceId = (int) ($params['service_id'] ?? 0);
            $merchantTransId = $params['merchant_trans_id'] ?? ''; // Our order_id
            $amount = (float) ($params['amount'] ?? 0);
            $action = (int) ($params['action'] ?? 0);
            $signTime = $params['sign_time'] ?? '';
            $signString = $params['sign_string'] ?? '';
            $error = (int) ($params['error'] ?? 0);
            $errorNote = $params['error_note'] ?? '';

            // Click-side error
            if ($error !== 0) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, $error, $errorNote)
                );
            }

            // Get merchant credentials
            $account = $this->getClickAccount($store);
            if (! $account) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_UNKNOWN, 'Click not configured')
                );
            }

            // Verify signature
            if (! $this->verifySignature($clickTransId, $serviceId, $merchantTransId, $amount, $action, $signTime, $signString, null, $account)) {
                $this->log('Prepare: Sign check failed', ['click_trans_id' => $clickTransId]);

                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_SIGN_CHECK_FAILED, 'Sign check failed')
                );
            }

            // Find order
            $order = StoreOrder::where('id', $merchantTransId)
                ->where('store_id', $store->id)
                ->first();

            if (! $order) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_ORDER_NOT_FOUND, 'Order not found')
                );
            }

            // Amount check — integer tiyin compare to avoid float precision bugs
            $expectedTiyin = (int) round((float) $order->total * 100);
            $actualTiyin = (int) round($amount * 100);
            if ($expectedTiyin !== $actualTiyin) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_INCORRECT_AMOUNT, 'Incorrect amount')
                );
            }

            // Order status check
            if ($order->isPaid()) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_ALREADY_PAID, 'Order already paid')
                );
            }

            if (in_array($order->status, [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_TRANSACTION_CANCELLED, 'Order cancelled')
                );
            }

            // Idempotency check
            $existing = StorePaymentTransaction::where('store_id', $store->id)
                ->where('provider', 'click')
                ->where('metadata->click_trans_id', $clickTransId)
                ->first();

            if ($existing && $existing->status === StorePaymentTransaction::STATUS_PROCESSING) {
                return response()->json($this->buildPrepareResponse($existing, $clickTransId, $merchantTransId));
            }

            // Create transaction
            return DB::transaction(function () use ($store, $order, $clickTransId, $merchantTransId, $signString, $signTime) {
                $transaction = StorePaymentTransaction::updateOrCreate(
                    ['order_id' => $order->id, 'provider' => 'click'],
                    [
                        'store_id' => $store->id,
                        'amount' => $order->total,
                        'status' => StorePaymentTransaction::STATUS_PROCESSING,
                        'metadata' => [
                            'click_trans_id' => $clickTransId,
                            'merchant_trans_id' => $merchantTransId,
                            'merchant_prepare_id' => (string) $order->id,
                            'sign_string' => $signString,
                            'sign_time' => $signTime,
                            'action' => 0, // prepare
                        ],
                    ]
                );

                $this->log('Prepare: Success', [
                    'order_id' => $merchantTransId,
                    'click_trans_id' => $clickTransId,
                ]);

                return response()->json($this->buildPrepareResponse($transaction, $clickTransId, $merchantTransId));
            });

        } catch (\Exception $e) {
            $this->logError('Prepare', $e);

            return response()->json(
                $this->buildResponse(
                    $params['click_trans_id'] ?? 0,
                    $params['merchant_trans_id'] ?? '',
                    self::ERROR_UNKNOWN,
                    'System error'
                )
            );
        }
    }

    /**
     * POST /click/{store}/complete — Click Complete endpoint.
     *
     * Confirms payment and activates the order.
     */
    public function complete(Request $request, TelegramStore $store): JsonResponse
    {
        $params = $request->all();
        $this->log('Complete', $params);

        try {
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

            // Click-side error
            if ($error !== 0) {
                $this->handleClickError($store, $merchantTransId, $error, $errorNote);

                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, $error, $errorNote)
                );
            }

            // Get merchant credentials
            $account = $this->getClickAccount($store);
            if (! $account) {
                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_UNKNOWN, 'Click not configured')
                );
            }

            // Verify signature (with merchant_prepare_id for complete)
            if (! $this->verifySignature($clickTransId, $serviceId, $merchantTransId, $amount, $action, $signTime, $signString, $merchantPrepareId, $account)) {
                $this->log('Complete: Sign check failed', ['click_trans_id' => $clickTransId]);

                return response()->json(
                    $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_SIGN_CHECK_FAILED, 'Sign check failed')
                );
            }

            // Everything under a row lock — concurrent Click retries serialize,
            // preventing double markPaid / double notification.
            return DB::transaction(function () use ($store, $clickTransId, $clickPaydocId, $merchantTransId, $amount) {
                $transaction = StorePaymentTransaction::where('store_id', $store->id)
                    ->where('provider', 'click')
                    ->where('metadata->click_trans_id', $clickTransId)
                    ->lockForUpdate()
                    ->first();

                if (! $transaction) {
                    return response()->json(
                        $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found')
                    );
                }

                $order = $transaction->order;

                // Already paid - idempotency
                if ($order && $order->isPaid()) {
                    return response()->json($this->buildCompleteResponse($transaction, $clickTransId, $merchantTransId));
                }

                if ($order && in_array($order->status, [StoreOrder::STATUS_CANCELLED, StoreOrder::STATUS_REFUNDED])) {
                    return response()->json(
                        $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_TRANSACTION_CANCELLED, 'Order cancelled')
                    );
                }

                // Transaction must be in processing (prepared) state
                if ($transaction->status !== StorePaymentTransaction::STATUS_PROCESSING) {
                    return response()->json(
                        $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_PAYMENT_FAILED, 'Transaction not prepared')
                    );
                }

                // Amount check — integer tiyin compare to avoid float precision bugs
                if ($order) {
                    $expectedTiyin = (int) round((float) $order->total * 100);
                    $actualTiyin = (int) round($amount * 100);
                    if ($expectedTiyin !== $actualTiyin) {
                        return response()->json(
                            $this->buildResponse($clickTransId, $merchantTransId, self::ERROR_INCORRECT_AMOUNT, 'Amount mismatch')
                        );
                    }
                }

                $metadata = $transaction->metadata ?? [];
                $metadata['click_paydoc_id'] = $clickPaydocId;
                $metadata['action'] = 1; // complete

                $transaction->update([
                    'status' => StorePaymentTransaction::STATUS_COMPLETED,
                    'paid_at' => now(),
                    'metadata' => $metadata,
                ]);

                if ($order) {
                    $this->orderService->handlePaymentCompleted(
                        $order,
                        'click',
                        (string) $clickTransId
                    );
                }

                $this->log('Complete: Success', [
                    'order_id' => $merchantTransId,
                    'click_paydoc_id' => $clickPaydocId,
                ]);

                return response()->json($this->buildCompleteResponse($transaction, $clickTransId, $merchantTransId));
            });

        } catch (\Exception $e) {
            $this->logError('Complete', $e);

            return response()->json(
                $this->buildResponse(
                    $params['click_trans_id'] ?? 0,
                    $params['merchant_trans_id'] ?? '',
                    self::ERROR_UNKNOWN,
                    'System error'
                )
            );
        }
    }

    // ========== SIGNATURE VERIFICATION ==========

    /**
     * Verify Click HMAC signature.
     */
    protected function verifySignature(
        int $clickTransId,
        int $serviceId,
        string $merchantTransId,
        float $amount,
        int $action,
        string $signTime,
        string $signString,
        ?string $merchantPrepareId,
        PaymentAccount $account
    ): bool {
        $secretKey = $account->secret_key;
        $configServiceId = (int) $account->service_id;

        // Service ID check
        if ($serviceId !== $configServiceId) {
            return false;
        }

        // Build expected sign string
        $parts = [
            $clickTransId,
            $serviceId,
            $secretKey,
            $merchantTransId,
        ];

        if ($merchantPrepareId !== null) {
            $parts[] = $merchantPrepareId;
        }

        $parts[] = $amount;
        $parts[] = $action;
        $parts[] = $signTime;

        $expectedSign = md5(implode('', $parts));

        // Timing-safe comparison — Click secret_key must not be brute-forceable
        // via HMAC string side-channel.
        return hash_equals($expectedSign, (string) $signString);
    }

    // ========== ERROR HANDLING ==========

    /**
     * Handle Click error (payment cancelled by user, etc.).
     */
    protected function handleClickError(TelegramStore $store, string $merchantTransId, int $error, string $errorNote): void
    {
        $order = StoreOrder::where('id', $merchantTransId)
            ->where('store_id', $store->id)
            ->first();

        if ($order && $order->isCancellable()) {
            $transaction = StorePaymentTransaction::where('order_id', $order->id)
                ->where('provider', 'click')
                ->first();

            if ($transaction) {
                $metadata = $transaction->metadata ?? [];
                $metadata['error_code'] = $error;
                $metadata['error_note'] = $errorNote;

                $transaction->update([
                    'status' => StorePaymentTransaction::STATUS_FAILED,
                    'metadata' => $metadata,
                ]);
            }

            $this->log('HandleClickError: Transaction failed', [
                'order_id' => $merchantTransId,
                'error' => $error,
            ]);
        }
    }

    // ========== HELPERS ==========

    protected function getClickAccount(TelegramStore $store): ?PaymentAccount
    {
        return PaymentAccount::where('business_id', $store->business_id)
            ->where('provider', 'click')
            ->active()
            ->first();
    }

    protected function buildResponse(int $clickTransId, string $merchantTransId, int $error, ?string $errorNote = null): array
    {
        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'error' => $error,
            'error_note' => $errorNote ?? ($error === 0 ? 'Success' : 'Error'),
        ];
    }

    protected function buildPrepareResponse(StorePaymentTransaction $transaction, int $clickTransId, string $merchantTransId): array
    {
        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_prepare_id' => (string) $transaction->order_id,
            'error' => self::ERROR_SUCCESS,
            'error_note' => 'Success',
        ];
    }

    protected function buildCompleteResponse(StorePaymentTransaction $transaction, int $clickTransId, string $merchantTransId): array
    {
        return [
            'click_trans_id' => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'merchant_confirm_id' => (string) $transaction->order_id,
            'error' => self::ERROR_SUCCESS,
            'error_note' => 'Success',
        ];
    }

    protected function log(string $method, array $data = []): void
    {
        Log::channel($this->logChannel)->info("[StoreClick] {$method}", $data);
    }

    protected function logError(string $method, \Exception $e): void
    {
        Log::channel($this->logChannel)->error("[StoreClick] {$method} Error", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
