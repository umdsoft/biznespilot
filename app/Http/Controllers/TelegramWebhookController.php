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
}
