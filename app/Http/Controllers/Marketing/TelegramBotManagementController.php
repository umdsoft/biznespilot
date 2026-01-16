<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Services\Telegram\TelegramApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TelegramBotManagementController extends Controller
{
    /**
     * List all bots for business
     */
    public function index(Request $request): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bots = TelegramBot::where('business_id', $business->id)
            ->withCount(['users', 'funnels', 'conversations'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($bot) => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
                'is_active' => $bot->is_active,
                'is_verified' => $bot->is_verified,
                'verified_at' => $bot->verified_at?->format('d.m.Y H:i'),
                'users_count' => $bot->users_count,
                'funnels_count' => $bot->funnels_count,
                'conversations_count' => $bot->conversations_count,
                'created_at' => $bot->created_at->format('d.m.Y'),
            ]);

        return Inertia::render('Marketing/Telegram/Bots/Index', [
            'bots' => $bots,
        ]);
    }

    /**
     * Show bot creation form
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Marketing/Telegram/Bots/Create');
    }

    /**
     * Store new bot
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'bot_token' => 'required|string|regex:/^\d+:[\w-]+$/',
        ]);

        $business = $request->user()->currentBusiness;

        // Verify bot token with Telegram API
        $tempBot = new TelegramBot(['bot_token' => $request->bot_token]);
        $api = new TelegramApiService($tempBot);
        $result = $api->getMe();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Bot tokeni noto\'g\'ri yoki bot topilmadi',
            ], 400);
        }

        $botInfo = $result['result'];

        // Check if bot already exists
        $existingBot = TelegramBot::where('bot_username', $botInfo['username'])->first();
        if ($existingBot) {
            return response()->json([
                'success' => false,
                'message' => 'Bu bot allaqachon tizimda mavjud',
            ], 400);
        }

        // Create bot
        $bot = TelegramBot::create([
            'business_id' => $business->id,
            'bot_token' => $request->bot_token,
            'bot_username' => $botInfo['username'],
            'bot_first_name' => $botInfo['first_name'],
            'webhook_secret' => Str::random(64),
            'is_active' => true,
            'is_verified' => false,
            'settings' => [
                'welcome_message' => 'Assalomu alaykum! 👋',
                'fallback_message' => 'Tushunmadim, iltimos qaytadan urinib ko\'ring.',
                'default_language' => 'uz',
                'typing_action' => true,
                'typing_delay_ms' => 500,
            ],
        ]);

        // Avtomatik webhook o'rnatish (APP_URL dan foydalanib)
        $webhookUrl = config('app.url') . '/webhooks/telegram-funnel/' . $bot->id;
        $webhookResult = $api->setWebhook($webhookUrl, $bot->webhook_secret);

        $webhookMessage = '';
        if ($webhookResult['success']) {
            $bot->update([
                'webhook_url' => $webhookUrl,
                'is_verified' => true,
                'verified_at' => now(),
            ]);
            $webhookMessage = ' Webhook avtomatik ulandi.';
        } else {
            $webhookMessage = ' Lekin webhook ulanmadi - keyinroq qayta urinib ko\'ring.';
        }

        return response()->json([
            'success' => true,
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
            ],
            'webhook_connected' => $webhookResult['success'],
            'message' => 'Bot muvaffaqiyatli qo\'shildi.' . $webhookMessage,
        ]);
    }

    /**
     * Show bot details
     */
    public function show(Request $request, string $id): InertiaResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $id)
            ->withCount(['users', 'funnels', 'activeFunnels', 'triggers', 'conversations', 'broadcasts'])
            ->firstOrFail();

        // Get recent stats
        $recentStats = $bot->dailyStats()
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get()
            ->map(fn($stat) => [
                'date' => $stat->date->format('d.m'),
                'new_users' => $stat->new_users,
                'messages_in' => $stat->messages_in,
                'messages_out' => $stat->messages_out,
                'leads_captured' => $stat->leads_captured,
            ]);

        // Get active funnels for default funnel selector
        $funnels = $bot->funnels()
            ->where('is_active', true)
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
            ]);

        return Inertia::render('Marketing/Telegram/Bots/Show', [
            'bot' => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
                'is_active' => $bot->is_active,
                'is_verified' => $bot->is_verified,
                'verified_at' => $bot->verified_at?->format('d.m.Y H:i'),
                'webhook_url' => $bot->webhook_url,
                'settings' => $bot->settings,
                'default_funnel_id' => $bot->default_funnel_id,
                'users_count' => $bot->users_count,
                'funnels_count' => $bot->funnels_count,
                'active_funnels_count' => $bot->active_funnels_count,
                'triggers_count' => $bot->triggers_count,
                'conversations_count' => $bot->conversations_count,
                'broadcasts_count' => $bot->broadcasts_count,
                'created_at' => $bot->created_at->format('d.m.Y H:i'),
            ],
            'funnels' => $funnels,
            'recentStats' => $recentStats,
        ]);
    }

    /**
     * Update bot settings
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'settings' => 'sometimes|array',
            'settings.welcome_message' => 'sometimes|string|max:4096',
            'settings.fallback_message' => 'sometimes|string|max:4096',
            'settings.help_message' => 'sometimes|string|max:4096',
            'settings.handoff_message' => 'sometimes|string|max:4096',
            'settings.default_language' => 'sometimes|string|in:uz,ru,en',
            'settings.typing_action' => 'sometimes|boolean',
            'settings.typing_delay_ms' => 'sometimes|integer|min:0|max:5000',
            'is_active' => 'sometimes|boolean',
            'default_funnel_id' => 'sometimes|nullable|uuid|exists:telegram_funnels,id',
        ]);

        if ($request->has('settings')) {
            $currentSettings = $bot->settings ?? [];
            $bot->settings = array_merge($currentSettings, $request->settings);
        }

        if ($request->has('is_active')) {
            $bot->is_active = $request->is_active;
        }

        if ($request->has('default_funnel_id')) {
            $bot->default_funnel_id = $request->default_funnel_id;
        }

        $bot->save();

        return response()->json([
            'success' => true,
            'message' => 'Bot sozlamalari yangilandi',
        ]);
    }

    /**
     * Toggle bot active status
     */
    public function toggleActive(Request $request, string $id): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $bot->update(['is_active' => !$bot->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $bot->is_active,
            'message' => $bot->is_active ? 'Bot faollashtirildi' : 'Bot o\'chirildi',
        ]);
    }

    /**
     * Delete bot
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Delete webhook first
        try {
            $api = new TelegramApiService($bot);
            $api->deleteWebhook();
        } catch (\Exception $e) {
            // Ignore webhook deletion errors
        }

        $bot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bot o\'chirildi',
        ]);
    }

    /**
     * Verify and setup webhook
     */
    public function setupWebhook(Request $request, string $id): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Generate webhook URL (APP_URL dan foydalanib)
        $webhookUrl = config('app.url') . '/webhooks/telegram-funnel/' . $bot->id;

        // Set webhook via Telegram API
        $api = new TelegramApiService($bot);
        $result = $api->setWebhook($webhookUrl, $bot->webhook_secret);

        if ($result['success']) {
            $bot->update([
                'webhook_url' => $webhookUrl,
                'is_verified' => true,
                'verified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'webhook_url' => $webhookUrl,
                'message' => 'Webhook muvaffaqiyatli o\'rnatildi',
            ]);
        }

        // Xatolik sababini aniqlash
        $errorDescription = $result['description'] ?? 'Noma\'lum xatolik';
        $errorMessage = 'Webhook o\'rnatishda xatolik: ' . $errorDescription;

        // Umumiy xatoliklar uchun yaxshiroq xabarlar
        if (str_contains($errorDescription, 'HTTPS')) {
            $errorMessage = 'Telegram faqat HTTPS URL qabul qiladi. APP_URL sozlamasini https:// bilan boshlang.';
        } elseif (str_contains($errorDescription, 'certificate')) {
            $errorMessage = 'SSL sertifikati bilan muammo. Haqiqiy SSL sertifikati o\'rnatilganligini tekshiring.';
        } elseif (str_contains($errorDescription, 'Connection') || str_contains($errorDescription, 'resolve')) {
            $errorMessage = 'Telegram serveringizga ulana olmadi. APP_URL ochiq internetdan kirish mumkin ekanligini tekshiring.';
        } elseif (str_contains($errorDescription, 'wrong response')) {
            $errorMessage = 'Server noto\'g\'ri javob qaytardi. Route va controller to\'g\'ri sozlanganligini tekshiring.';
        }

        return response()->json([
            'success' => false,
            'error' => $errorMessage,
            'technical_error' => $errorDescription,
            'webhook_url' => $webhookUrl,
        ], 400);
    }

    /**
     * Get bot statistics
     */
    public function stats(Request $request, string $id): JsonResponse
    {
        $business = $request->user()->currentBusiness;

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $days = $request->input('days', 30);

        $stats = $bot->dailyStats()
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        $summary = [
            'total_users' => $bot->users()->count(),
            'active_users' => $bot->users()->where('is_blocked', false)->count(),
            'blocked_users' => $bot->users()->where('is_blocked', true)->count(),
            'total_conversations' => $bot->conversations()->count(),
            'active_conversations' => $bot->conversations()->whereIn('status', ['active', 'handoff'])->count(),
            'total_leads' => $stats->sum('leads_captured'),
            'total_messages_in' => $stats->sum('messages_in'),
            'total_messages_out' => $stats->sum('messages_out'),
            'new_users_period' => $stats->sum('new_users'),
        ];

        $daily = $stats->map(fn($stat) => [
            'date' => $stat->date->format('Y-m-d'),
            'new_users' => $stat->new_users,
            'active_users' => $stat->active_users,
            'messages_in' => $stat->messages_in,
            'messages_out' => $stat->messages_out,
            'leads_captured' => $stat->leads_captured,
            'handoffs' => $stat->handoffs,
        ]);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'daily' => $daily,
        ]);
    }
}
