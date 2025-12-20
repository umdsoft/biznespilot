<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Services\FacebookMessengerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    protected FacebookMessengerService $facebookService;

    public function __construct(FacebookMessengerService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * Handle incoming Facebook Messenger webhook
     */
    public function handle(Request $request, $businessId)
    {
        try {
            // Facebook webhook verification (GET request)
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            // Validate business
            $business = Business::findOrFail($businessId);

            // Validate config
            $config = ChatbotConfig::where('business_id', $business->id)->first();

            if (!$config || !$config->facebook_enabled || !$config->facebook_access_token) {
                Log::warning('Facebook webhook received but bot not configured', [
                    'business_id' => $businessId,
                ]);

                return response()->json(['error' => 'Bot not configured'], 400);
            }

            // Get webhook data
            $data = $request->all();

            Log::info('Facebook webhook received', [
                'business_id' => $businessId,
                'object' => $data['object'] ?? null,
            ]);

            // Facebook webhooks come in this format
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $result = $this->facebookService->handleWebhook($entry, $business);

                    if (!$result['success']) {
                        Log::warning('Facebook webhook processing failed', [
                            'business_id' => $businessId,
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);
                    }
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Facebook webhook error', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Verify webhook (Facebook webhook verification)
     */
    private function verifyWebhook(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        // The verify token should be set in your .env file
        $verifyToken = config('services.facebook.webhook_verify_token', 'biznespilot_webhook_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Facebook webhook verified');
            return response($challenge, 200);
        }

        Log::warning('Facebook webhook verification failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Forbidden', 403);
    }
}
