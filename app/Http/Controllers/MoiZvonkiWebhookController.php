<?php

namespace App\Http\Controllers;

use App\Models\MoiZvonkiAccount;
use App\Services\MoiZvonkiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoiZvonkiWebhookController extends Controller
{
    protected MoiZvonkiService $moiZvonkiService;

    public function __construct(MoiZvonkiService $moiZvonkiService)
    {
        $this->moiZvonkiService = $moiZvonkiService;
    }

    /**
     * Handle incoming MoiZvonki webhook
     * URL: /api/webhooks/moizvonki
     */
    public function handle(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('MoiZvonki webhook received', [
                'ip' => $request->ip(),
                'event' => $data['type'] ?? $data['event'] ?? 'unknown',
                'call_id' => $data['call_id'] ?? $data['id'] ?? null,
            ]);

            // Process the webhook
            $this->moiZvonkiService->handleWebhook($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('MoiZvonki webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle MoiZvonki webhook with business ID
     * URL: /api/webhooks/moizvonki/{businessId}
     */
    public function handleWithBusiness(Request $request, $businessId)
    {
        try {
            $data = $request->all();
            $data['business_id'] = $businessId;

            Log::info('MoiZvonki webhook received (with business)', [
                'business_id' => $businessId,
                'ip' => $request->ip(),
                'event' => $data['type'] ?? $data['event'] ?? 'unknown',
            ]);

            // Find MoiZvonki account for this business
            $account = MoiZvonkiAccount::where('business_id', $businessId)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                Log::warning('MoiZvonki webhook: No active account for business', [
                    'business_id' => $businessId,
                ]);

                return response()->json(['error' => 'No active MoiZvonki account'], 404);
            }

            // Set account and process
            $this->moiZvonkiService->setAccount($account);
            $this->moiZvonkiService->handleWebhook($data);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('MoiZvonki webhook error', [
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
            'message' => 'MoiZvonki webhook endpoint is working',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
