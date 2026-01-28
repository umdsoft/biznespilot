<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingWebhookLog;
use App\Services\Billing\PaymeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymeMerchantController - Payme Merchant API Controller
 *
 * Bu controller Payme dan keluvchi JSON-RPC so'rovlarni qabul qiladi.
 * Barcha metodlar PaymeService orqali bajariladi.
 *
 * Endpoint: POST /api/billing/payme
 * Auth: Basic Auth (PaymeBasicAuth middleware)
 *
 * Available Methods:
 * - CheckPerformTransaction
 * - CreateTransaction
 * - PerformTransaction
 * - CancelTransaction
 * - CheckTransaction
 * - GetStatement
 *
 * @see https://developer.payme.uz/guides/merchant-api
 */
class PaymeMerchantController extends Controller
{
    protected PaymeService $paymeService;
    protected string $logChannel = 'billing';

    public function __construct(PaymeService $paymeService)
    {
        $this->paymeService = $paymeService;
    }

    /**
     * Handle Payme JSON-RPC request
     */
    public function handle(Request $request): JsonResponse
    {
        // Request logga yozish
        $webhookLog = $this->logWebhook($request);

        try {
            // JSON-RPC so'rovini olish
            $data = $request->json()->all();

            $id = $data['id'] ?? null;
            $method = $data['method'] ?? null;
            $params = $data['params'] ?? [];

            Log::channel($this->logChannel)->info('[Payme] Incoming request', [
                'method' => $method,
                'id' => $id,
            ]);

            // Metod mavjudligini tekshirish
            if (!$method) {
                $response = $this->jsonRpcError($id, -32600, 'Invalid Request');
                $webhookLog->logResponse($response, 200, false, null, 'Missing method');
                return response()->json($response);
            }

            // Metodni chaqirish
            $result = match ($method) {
                'CheckPerformTransaction' => $this->paymeService->checkPerformTransaction($params),
                'CreateTransaction' => $this->paymeService->createTransaction($params),
                'PerformTransaction' => $this->paymeService->performTransaction($params),
                'CancelTransaction' => $this->paymeService->cancelTransaction($params),
                'CheckTransaction' => $this->paymeService->checkTransaction($params),
                'GetStatement' => $this->paymeService->getStatement($params),
                default => $this->jsonRpcError($id, -32601, 'Method not found'),
            };

            // Response yaratish
            $response = isset($result['error'])
                ? $this->jsonRpcError($id, $result['error']['code'], $result['error']['message'])
                : $this->jsonRpcSuccess($id, $result['result']);

            // Success/Error holatini aniqlash
            $isSuccessful = !isset($result['error']);
            $transactionId = $this->extractTransactionId($params, $result);
            $errorMessage = isset($result['error']) ? json_encode($result['error']['message']) : null;

            $webhookLog->logResponse($response, 200, $isSuccessful, $transactionId, $errorMessage);

            Log::channel($this->logChannel)->info('[Payme] Response', [
                'method' => $method,
                'success' => $isSuccessful,
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::channel($this->logChannel)->error('[Payme] Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $response = $this->jsonRpcError(null, -32603, 'Internal error');
            $webhookLog->logResponse($response, 500, false, null, $e->getMessage());

            return response()->json($response);
        }
    }

    /**
     * Build JSON-RPC success response
     */
    protected function jsonRpcSuccess($id, $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * Build JSON-RPC error response
     */
    protected function jsonRpcError($id, int $code, $message): array
    {
        // Message formatini tekshirish
        if (!is_array($message)) {
            $message = [
                'ru' => $message,
                'uz' => $message,
                'en' => $message,
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * Log webhook request
     */
    protected function logWebhook(Request $request): BillingWebhookLog
    {
        $data = $request->json()->all();

        return BillingWebhookLog::logRequest(
            'payme',
            $data['method'] ?? null,
            null,
            $request->headers->all(),
            $data,
            $request->ip()
        );
    }

    /**
     * Extract transaction ID from params or result
     */
    protected function extractTransactionId(array $params, array $result): ?int
    {
        // Result dan
        if (isset($result['result']['transaction'])) {
            return (int) $result['result']['transaction'];
        }

        // Params account dan order_id olish va topish
        if (isset($params['account']['order_id'])) {
            $transaction = \App\Models\Billing\BillingTransaction::where('order_id', $params['account']['order_id'])->first();
            return $transaction?->id;
        }

        return null;
    }
}
