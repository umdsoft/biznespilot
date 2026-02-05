<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Models\FacebookPage;
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
     * Universal webhook handler - Meta faqat bitta URL ga webhook yuboradi
     * entry.id (Facebook Page ID) orqali qaysi biznesga tegishli ekanini aniqlaydi
     *
     * Facebook Developer → Webhooks → Callback URL: https://domain.com/webhooks/facebook
     */
    public function handleUniversal(Request $request)
    {
        try {
            // Facebook webhook verification (GET request)
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            // SECURITY: Verify webhook signature (HMAC-SHA256)
            if (! $this->verifySignature($request)) {
                Log::warning('Facebook webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->all();

            Log::info('Facebook universal webhook received', [
                'object' => $data['object'] ?? null,
                'entry_count' => isset($data['entry']) ? count($data['entry']) : 0,
            ]);

            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $this->processEntry($entry);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Facebook universal webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Legacy: business-specific webhook handler (backward compatibility)
     */
    public function handle(Request $request, $businessId)
    {
        try {
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            if (! $this->verifySignature($request)) {
                Log::warning('Facebook webhook signature verification failed', [
                    'business_id' => $businessId,
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $business = Business::findOrFail($businessId);
            $data = $request->all();

            Log::info('Facebook webhook received (legacy route)', [
                'business_id' => $businessId,
                'object' => $data['object'] ?? null,
            ]);

            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $this->processEntry($entry, $business);
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
     * Process a single webhook entry
     * Facebook Page ID (entry.id) orqali FacebookPage va Business ni topadi
     *
     * @param  array  $entry  Webhook entry data
     * @param  Business|null  $business  Legacy route dan kelsa, business beriladi
     */
    private function processEntry(array $entry, ?Business $business = null): void
    {
        $pageId = $entry['id'] ?? null;

        // 1. FacebookPage ni topish (entry.id = Facebook Page ID)
        $facebookPage = null;
        if ($pageId) {
            $facebookPage = FacebookPage::where('facebook_page_id', $pageId)->first();
        }

        // 2. Business ni aniqlash
        if (! $business && $facebookPage) {
            $business = $facebookPage->business;
        }

        // 3. Agar hech narsa topilmasa — log qilib skip
        if (! $business) {
            Log::warning('Facebook webhook: unknown page_id, no matching business', [
                'page_id' => $pageId,
                'entry_keys' => array_keys($entry),
            ]);

            return;
        }

        // Validate config
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (! $config || ! $config->facebook_enabled || ! $config->facebook_access_token) {
            Log::warning('Facebook webhook received but bot not configured', [
                'business_id' => $business->id,
                'page_id' => $pageId,
            ]);

            return;
        }

        Log::info('Facebook webhook entry processing', [
            'page_id' => $pageId,
            'business_id' => $business->id,
        ]);

        $result = $this->facebookService->handleWebhook($entry, $business);

        if (! $result['success']) {
            Log::warning('Facebook webhook processing failed', [
                'business_id' => $business->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
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

    /**
     * Verify Facebook webhook signature (HMAC-SHA256)
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = config('services.facebook.app_secret');

        // Development muhitda skip
        if (app()->environment('local', 'development')) {
            Log::info('Facebook webhook signature verification skipped (development mode)');

            return true;
        }

        if (! $signature) {
            Log::warning('Facebook webhook received without signature header');

            return true;
        }

        if (! $appSecret) {
            Log::warning('Facebook app secret not configured');

            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
