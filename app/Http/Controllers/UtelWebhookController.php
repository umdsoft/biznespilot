<?php

namespace App\Http\Controllers;

use App\Models\UtelAccount;
use App\Services\UtelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtelWebhookController extends Controller
{
    protected UtelService $utelService;

    public function __construct(UtelService $utelService)
    {
        $this->utelService = $utelService;
    }

    /**
     * Handle incoming UTEL webhook
     * URL: /api/webhooks/utel
     */
    public function handle(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('UTEL webhook received', [
                'ip' => $request->ip(),
                'event' => $data['event'] ?? $data['type'] ?? 'unknown',
                'call_id' => $data['call_id'] ?? $data['id'] ?? null,
            ]);

            // Process the webhook
            $this->utelService->handleWebhook($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('UTEL webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle UTEL webhook with business ID
     * URL: /api/webhooks/utel/{businessId}
     */
    public function handleWithBusiness(Request $request, $businessId)
    {
        try {
            $data = $request->all();
            $data['business_id'] = $businessId;

            Log::info('UTEL webhook received (with business)', [
                'business_id' => $businessId,
                'ip' => $request->ip(),
                'event' => $data['event'] ?? $data['type'] ?? 'unknown',
            ]);

            // Find UTEL account for this business
            $account = UtelAccount::where('business_id', $businessId)
                ->where('is_active', true)
                ->first();

            if (!$account) {
                Log::warning('UTEL webhook: No active account for business', [
                    'business_id' => $businessId,
                ]);
                return response()->json(['error' => 'No active UTEL account'], 404);
            }

            // Verify webhook signature if secret is set
            if ($account->webhook_secret) {
                $signature = $request->header('X-Utel-Signature') ?? $request->header('X-Webhook-Signature');
                if ($signature) {
                    $expectedSignature = hash_hmac('sha256', $request->getContent(), $account->webhook_secret);
                    if (!hash_equals($expectedSignature, $signature)) {
                        Log::warning('UTEL webhook: Invalid signature', [
                            'business_id' => $businessId,
                        ]);
                        return response()->json(['error' => 'Invalid signature'], 401);
                    }
                }
            }

            // Set account and process
            $this->utelService->setAccount($account);
            $this->utelService->handleWebhook($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('UTEL webhook error', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Test endpoint for webhook verification
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'UTEL webhook endpoint is working',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
