<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingWebhookLog;
use App\Services\Billing\ClickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ClickMerchantController - Click Merchant API Controller
 *
 * Bu controller Click dan keluvchi so'rovlarni qabul qiladi.
 * Click ikkita endpoint ishlatadi: Prepare va Complete
 *
 * Endpoints:
 * - POST /api/billing/click/prepare
 * - POST /api/billing/click/complete
 *
 * @see https://docs.click.uz/
 */
class ClickMerchantController extends Controller
{
    protected ClickService $clickService;
    protected string $logChannel = 'billing';

    public function __construct(ClickService $clickService)
    {
        $this->clickService = $clickService;
    }

    /**
     * Prepare - Tranzaksiya validatsiyasi
     *
     * Click bu metoddi to'lovdan oldin chaqiradi.
     * Biz buyurtmani tekshiramiz va merchant_prepare_id qaytaramiz.
     */
    public function prepare(Request $request): JsonResponse
    {
        // Request logga yozish
        $webhookLog = $this->logWebhook($request, 'prepare');

        try {
            $params = $request->all();

            Log::channel($this->logChannel)->info('[Click] Prepare request', [
                'click_trans_id' => $params['click_trans_id'] ?? null,
                'merchant_trans_id' => $params['merchant_trans_id'] ?? null,
            ]);

            // Service orqali prepare
            $result = $this->clickService->prepare($params);

            // Success/Error holatini aniqlash
            $isSuccessful = ($result['error'] ?? -1) === 0;
            $transactionId = $this->findTransactionId($params['merchant_trans_id'] ?? null);
            $errorMessage = !$isSuccessful ? ($result['error_note'] ?? null) : null;

            $webhookLog->logResponse($result, 200, $isSuccessful, $transactionId, $errorMessage);

            Log::channel($this->logChannel)->info('[Click] Prepare response', [
                'success' => $isSuccessful,
                'error' => $result['error'] ?? 0,
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::channel($this->logChannel)->error('[Click] Prepare exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $response = [
                'click_trans_id' => $request->input('click_trans_id', 0),
                'merchant_trans_id' => $request->input('merchant_trans_id', ''),
                'error' => -9,
                'error_note' => 'System error',
            ];

            $webhookLog->logResponse($response, 500, false, null, $e->getMessage());

            return response()->json($response);
        }
    }

    /**
     * Complete - Tranzaksiyani yakunlash
     *
     * Click bu metoddi to'lov muvaffaqiyatli bo'lganda chaqiradi.
     */
    public function complete(Request $request): JsonResponse
    {
        // Request logga yozish
        $webhookLog = $this->logWebhook($request, 'complete');

        try {
            $params = $request->all();

            Log::channel($this->logChannel)->info('[Click] Complete request', [
                'click_trans_id' => $params['click_trans_id'] ?? null,
                'merchant_trans_id' => $params['merchant_trans_id'] ?? null,
                'click_paydoc_id' => $params['click_paydoc_id'] ?? null,
            ]);

            // Service orqali complete
            $result = $this->clickService->complete($params);

            // Success/Error holatini aniqlash
            $isSuccessful = ($result['error'] ?? -1) === 0;
            $transactionId = $this->findTransactionId($params['merchant_trans_id'] ?? null);
            $errorMessage = !$isSuccessful ? ($result['error_note'] ?? null) : null;

            $webhookLog->logResponse($result, 200, $isSuccessful, $transactionId, $errorMessage);

            Log::channel($this->logChannel)->info('[Click] Complete response', [
                'success' => $isSuccessful,
                'error' => $result['error'] ?? 0,
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::channel($this->logChannel)->error('[Click] Complete exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $response = [
                'click_trans_id' => $request->input('click_trans_id', 0),
                'merchant_trans_id' => $request->input('merchant_trans_id', ''),
                'error' => -9,
                'error_note' => 'System error',
            ];

            $webhookLog->logResponse($response, 500, false, null, $e->getMessage());

            return response()->json($response);
        }
    }

    /**
     * Log webhook request
     */
    protected function logWebhook(Request $request, string $action): BillingWebhookLog
    {
        return BillingWebhookLog::logRequest(
            'click',
            null,
            $action,
            $request->headers->all(),
            $request->all(),
            $request->ip()
        );
    }

    /**
     * Find transaction ID by order_id
     */
    protected function findTransactionId(?string $orderId): ?int
    {
        if (!$orderId) {
            return null;
        }

        $transaction = \App\Models\Billing\BillingTransaction::where('order_id', $orderId)->first();
        return $transaction?->id;
    }
}
