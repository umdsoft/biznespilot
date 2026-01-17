<?php

namespace App\Http\Controllers;

use App\Models\PbxAccount;
use App\Services\OnlinePbxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PbxWebhookController extends Controller
{
    protected OnlinePbxService $pbxService;

    public function __construct(OnlinePbxService $pbxService)
    {
        $this->pbxService = $pbxService;
    }

    /**
     * Handle incoming OnlinePBX webhook
     * URL: /api/webhooks/pbx/onlinepbx
     */
    public function handleOnlinePbx(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('OnlinePBX webhook received', [
                'ip' => $request->ip(),
                'event' => $data['event'] ?? $data['type'] ?? $data['state'] ?? 'unknown',
                'call_id' => $data['call_id'] ?? $data['uuid'] ?? null,
            ]);

            // Verify webhook signature if configured
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('OnlinePBX webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            // Process the webhook
            $this->pbxService->handleWebhook($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('OnlinePBX webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle OnlinePBX webhook with business ID
     * URL: /api/webhooks/pbx/onlinepbx/{businessId}
     */
    public function handleOnlinePbxWithBusiness(Request $request, $businessId)
    {
        try {
            $data = $request->all();
            $data['business_id'] = $businessId;

            Log::info('OnlinePBX webhook received (with business)', [
                'business_id' => $businessId,
                'ip' => $request->ip(),
                'event' => $data['event'] ?? $data['type'] ?? $data['state'] ?? 'unknown',
            ]);

            // Find PBX account for this business
            $pbxAccount = PbxAccount::where('business_id', $businessId)
                ->where('is_active', true)
                ->first();

            if (!$pbxAccount) {
                Log::warning('OnlinePBX webhook: No active PBX account for business', [
                    'business_id' => $businessId,
                ]);
                return response()->json(['error' => 'No active PBX account'], 404);
            }

            // Set account and process
            $this->pbxService->setAccount($pbxAccount);
            $this->pbxService->handleWebhook($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('OnlinePBX webhook error', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Verify webhook signature (if OnlinePBX provides one)
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        // OnlinePBX can send a signature in headers
        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-PBX-Signature')
            ?? null;

        if (!$signature) {
            // Allow if no signature (for backwards compatibility)
            // In production, consider requiring signatures
            return true;
        }

        // Get configured webhook secret
        $secret = config('services.onlinepbx.webhook_secret');

        if (!$secret) {
            Log::warning('OnlinePBX webhook secret not configured');
            return true; // Allow if not configured
        }

        // Verify signature (adjust based on OnlinePBX documentation)
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Test endpoint for webhook verification
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'OnlinePBX webhook endpoint is working',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Manually sync call history from OnlinePBX
     * This fetches calls from PBX API for calls that might have missed webhooks
     */
    public function syncCallHistory(Request $request)
    {
        try {
            $user = $request->user();
            $business = $user?->currentBusiness;

            if (!$business) {
                return response()->json(['error' => 'Business not found'], 404);
            }

            // Find active PBX account for this business
            $pbxAccount = PbxAccount::where('business_id', $business->id)
                ->where('is_active', true)
                ->first();

            if (!$pbxAccount) {
                return response()->json([
                    'success' => false,
                    'error' => 'OnlinePBX hisobi topilmadi',
                ], 404);
            }

            // Parse date from request (default: last 24 hours)
            $dateFrom = $request->date_from
                ? \Carbon\Carbon::parse($request->date_from)
                : \Carbon\Carbon::now()->subDay();

            // Sync call history
            $this->pbxService->setAccount($pbxAccount);
            $result = $this->pbxService->syncCallHistory($dateFrom);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Sinxronlash xatosi',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "Qo'ng'iroqlar sinxronlandi",
                'synced' => $result['synced'],
                'created' => $result['created'],
                'updated' => $result['updated'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('PBX sync call history error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Sinxronlash xatosi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Link orphan call logs to leads
     * This fixes existing data where call logs weren't properly linked to leads
     */
    public function linkOrphanCalls(Request $request)
    {
        try {
            $user = $request->user();
            $business = $user?->currentBusiness;

            if (!$business) {
                return response()->json(['error' => 'Business not found'], 404);
            }

            // Link orphan call logs to leads
            $result = $this->pbxService->linkOrphanCallLogs($business->id);

            return response()->json([
                'success' => true,
                'message' => "Qo'ng'iroqlar lidlarga bog'landi",
                'linked' => $result['linked'],
                'failed' => $result['failed'],
                'total' => $result['total'],
            ]);

        } catch (\Exception $e) {
            Log::error('PBX link orphan calls error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Bog\'lash xatosi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
