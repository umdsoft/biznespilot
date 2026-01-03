<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ChatbotConfig;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle incoming Telegram webhook
     */
    public function handle(Request $request, $businessId)
    {
        try {
            // SECURITY: Verify Telegram webhook secret token
            if (!$this->verifySecretToken($request, $businessId)) {
                Log::warning('Telegram webhook secret token verification failed', [
                    'business_id' => $businessId,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Validate business
            $business = Business::findOrFail($businessId);

            // Validate config
            $config = ChatbotConfig::where('business_id', $business->id)->first();

            if (!$config || !$config->telegram_enabled || !$config->telegram_bot_token) {
                Log::warning('Telegram webhook received but bot not configured', [
                    'business_id' => $businessId,
                ]);

                return response()->json(['error' => 'Bot not configured'], 400);
            }

            // Get webhook update
            $update = $request->all();

            Log::info('Telegram webhook received', [
                'business_id' => $businessId,
                'update_id' => $update['update_id'] ?? null,
            ]);

            // Process the update
            $result = $this->telegramService->handleWebhook($update, $business);

            if ($result['success']) {
                return response()->json(['ok' => true]);
            }

            Log::warning('Telegram webhook processing failed', [
                'business_id' => $businessId,
                'message' => $result['message'] ?? 'Unknown error',
            ]);

            return response()->json(['ok' => false], 500);

        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => false], 500);
        }
    }

    /**
     * Verify webhook (for Telegram webhook verification)
     */
    public function verify(Request $request, $businessId)
    {
        return response()->json(['ok' => true]);
    }

    /**
     * Verify Telegram webhook secret token
     *
     * SECURITY: Telegram sends X-Telegram-Bot-Api-Secret-Token header
     * when you set secret_token in setWebhook API call
     */
    private function verifySecretToken(Request $request, $businessId): bool
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (!$secretToken) {
            // Allow if no secret token header (for backwards compatibility)
            // In production, you should return false here after setting secret_token
            Log::warning('Telegram webhook received without secret token header');
            return true; // Change to false in production after setup
        }

        // Get the expected secret token for this business
        $config = ChatbotConfig::where('business_id', $businessId)->first();

        if (!$config || !$config->telegram_webhook_secret) {
            // If business doesn't have a secret configured, use global secret
            $expectedSecret = config('services.telegram.webhook_secret');

            if (!$expectedSecret) {
                Log::warning('Telegram webhook secret not configured');
                return false;
            }
        } else {
            $expectedSecret = $config->telegram_webhook_secret;
        }

        return hash_equals($expectedSecret, $secretToken);
    }
}
