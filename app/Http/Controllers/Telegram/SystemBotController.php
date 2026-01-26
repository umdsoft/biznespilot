<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\Telegram\SystemBotConversationService;
use App\Services\Telegram\SystemBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SystemBotController - BiznesPilot System Bot Webhook Handler
 *
 * "Dual Bot Strategy":
 * Bu controller faqat System Bot uchun ishlaydi.
 * Tenant Bot lar uchun TelegramWebhookController ishlatiladi.
 *
 * Asosiy vazifalar:
 * 1. /start {token} - Foydalanuvchi Telegram hisobini ulash
 * 2. Menu buttons - Bugungi Holat, Kassa, Vazifa Berish, Sozlamalar
 * 3. Inline callbacks - Employee selection, task nudging
 * 4. Text input - Task creation
 */
class SystemBotController extends Controller
{
    public function __construct(
        protected SystemBotService $systemBot,
        protected SystemBotConversationService $conversationService
    ) {}

    /**
     * Handle incoming webhook from Telegram.
     */
    public function webhook(Request $request): JsonResponse
    {
        // Verify webhook secret if configured
        if (!$this->verifyWebhookSecret($request)) {
            Log::warning('SystemBot: Invalid webhook secret');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $update = $request->all();

        Log::debug('SystemBot: Webhook received', [
            'update_id' => $update['update_id'] ?? null,
        ]);

        // Process callback query (inline button clicks)
        if (isset($update['callback_query'])) {
            $this->processCallbackQuery($update['callback_query']);
            return response()->json(['ok' => true]);
        }

        // Process message
        if (isset($update['message'])) {
            $this->processMessage($update['message']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Process incoming message.
     */
    protected function processMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim($message['text'] ?? '');
        $from = $message['from'] ?? [];

        if (empty($chatId) || empty($text)) {
            return;
        }

        Log::debug('SystemBot: Processing message', [
            'chat_id' => $chatId,
            'text' => $text,
            'from' => $from['username'] ?? $from['first_name'] ?? 'unknown',
        ]);

        // Get linked user
        $user = User::where('telegram_chat_id', $chatId)->first();

        // Handle commands (starts with /)
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text, $user);
            return;
        }

        // Check if user is in a conversation state (e.g., entering task text)
        $result = $this->conversationService->handleIncomingMessage($chatId, $text, $user);
        if ($result['handled']) {
            return;
        }

        // Handle menu button presses
        if ($this->handleMenuButton($chatId, $text, $user)) {
            return;
        }

        // For non-command messages, show help
        $this->systemBot->sendMessage($chatId, $this->getHelpMessage());
    }

    /**
     * Handle menu button presses.
     */
    protected function handleMenuButton(string $chatId, string $text, ?User $user): bool
    {
        if (!$user) {
            $this->systemBot->sendMessage(
                $chatId,
                "❌ Telegram hisobingiz ulanmagan.\n\n"
                . "Ulash uchun BiznesPilot dashboardidan \"Telegram ulash\" tugmasini bosing."
            );
            return true;
        }

        return match ($text) {
            '📊 Statistika' => $this->handleStatistics($chatId, $user),
            '💰 Kassa' => $this->handleCashStatus($chatId, $user),
            '📝 Vazifa Berish' => $this->handleTaskAssignment($chatId, $user),
            '⚙️ Sozlamalar' => $this->handleSettings($chatId, $user),
            default => false,
        };
    }

    /**
     * Handle statistics menu request.
     */
    protected function handleStatistics(string $chatId, User $user): bool
    {
        $this->conversationService->showStatisticsMenu($chatId, $user);
        return true;
    }

    /**
     * Handle cash status request.
     */
    protected function handleCashStatus(string $chatId, User $user): bool
    {
        $this->conversationService->handleCashStatus($chatId, $user);
        return true;
    }

    /**
     * Handle task assignment start.
     */
    protected function handleTaskAssignment(string $chatId, User $user): bool
    {
        $this->conversationService->startTaskAssignment($chatId, $user);
        return true;
    }

    /**
     * Handle settings menu.
     */
    protected function handleSettings(string $chatId, User $user): bool
    {
        $this->conversationService->handleSettings($chatId, $user);
        return true;
    }

    /**
     * Process callback query (inline button clicks).
     */
    protected function processCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'];
        $chatId = (string) ($callbackQuery['message']['chat']['id'] ?? '');
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $data = $callbackQuery['data'] ?? '';

        Log::debug('SystemBot: Processing callback query', [
            'chat_id' => $chatId,
            'data' => $data,
        ]);

        // Answer the callback query first (removes loading state)
        $this->systemBot->answerCallbackQuery($callbackId);

        // Get linked user
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            return;
        }

        // Route callback data
        if (str_starts_with($data, 'assign_emp_')) {
            $employeeId = str_replace('assign_emp_', '', $data);
            $this->conversationService->handleEmployeeSelection($chatId, $employeeId, $user);
        } elseif (str_starts_with($data, 'due_date_')) {
            $dateKey = str_replace('due_date_', '', $data);
            $this->conversationService->handleDueDateSelection($chatId, $dateKey, $user);
        } elseif (str_starts_with($data, 'due_time_')) {
            $timeKey = str_replace('due_time_', '', $data);
            $this->conversationService->handleDueTimeSelection($chatId, $timeKey, $user);
        } elseif ($data === 'change_due_date') {
            // Go back to date selection
            $this->conversationService->showDateSelection($chatId, $user);
        } elseif ($data === 'no_time_available') {
            // Bugungi vaqtlar tugadi - go back to date selection
            $this->systemBot->sendMessage($chatId, "⏰ Bugungi vaqtlar tugadi. Boshqa sanani tanlang.");
            $this->conversationService->showDateSelection($chatId, $user);
        } elseif ($data === 'cancel_task') {
            $this->conversationService->clearState($chatId);
            $this->systemBot->sendMessage($chatId, "❌ Vazifa yaratish bekor qilindi.");
            $this->conversationService->showMainMenu($chatId, $user);
        } elseif ($data === 'toggle_daily_reports') {
            $this->conversationService->toggleDailyReports($chatId, $user);
        } elseif ($data === 'stats_sales') {
            $this->conversationService->handleSalesStats($chatId, $user);
        } elseif ($data === 'stats_marketing') {
            $this->conversationService->handleMarketingStats($chatId, $user);
        } elseif ($data === 'stats_employees') {
            $this->conversationService->handleEmployeeLeaderboard($chatId, $user);
        } elseif ($data === 'back_to_menu') {
            $this->conversationService->showMainMenu($chatId, $user);
        } elseif (str_starts_with($data, 'nudge_task_')) {
            $taskId = str_replace('nudge_task_', '', $data);
            $this->handleNudgeTask($chatId, $taskId, $user, $messageId);
        } elseif (str_starts_with($data, 'ignore_task_')) {
            $taskId = str_replace('ignore_task_', '', $data);
            $this->handleIgnoreTask($chatId, $taskId, $messageId);
        } elseif ($data === 'share_record') {
            $this->handleShareRecord($chatId, $user);
        }
    }

    /**
     * Handle nudge task callback.
     */
    protected function handleNudgeTask(string $chatId, string $taskId, User $user, ?int $messageId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->assignee) {
            $this->systemBot->sendMessage($chatId, "❌ Vazifa yoki xodim topilmadi.");
            return;
        }

        // Send nudge to employee
        $this->systemBot->sendNudgeToEmployee($task->assignee, $task->title, $user->name);

        // Update the message to show nudge was sent
        if ($messageId) {
            $this->systemBot->editMessageText(
                $chatId,
                $messageId,
                "✅ <b>Eslatma yuborildi!</b>\n\n"
                . "📝 \"{$task->title}\"\n"
                . "👨‍💼 {$task->assignee->name} ga eslatma yuborildi."
            );
        }

        Log::info('SystemBot: Nudge sent to employee', [
            'task_id' => $taskId,
            'employee_id' => $task->assignee->id,
            'manager_id' => $user->id,
        ]);
    }

    /**
     * Handle ignore task callback.
     */
    protected function handleIgnoreTask(string $chatId, string $taskId, ?int $messageId): void
    {
        if ($messageId) {
            $this->systemBot->editMessageText(
                $chatId,
                $messageId,
                "✅ Vazifa e'tiborsiz qoldirildi."
            );
        }
    }

    /**
     * Handle share record callback.
     */
    protected function handleShareRecord(string $chatId, User $user): void
    {
        $business = $user->currentBusiness;
        $businessName = $business?->name ?? 'BiznesPilot';

        $shareText = "🏆 {$businessName} yangi rekordni yangiladi!\n\n"
            . "💪 BiznesPilot bilan biznesni boshqarish oson!\n\n"
            . "👉 biznespilot.uz";

        $this->systemBot->sendMessage(
            $chatId,
            "📢 <b>Do'stlaringizga ulashing!</b>\n\n"
            . "Quyidagi matnni nusxalang va do'stlaringizga yuboring:\n\n"
            . "<code>{$shareText}</code>"
        );
    }

    /**
     * Handle bot commands.
     */
    protected function handleCommand(string $chatId, string $text, ?User $user): void
    {
        // Parse command and arguments
        $parts = explode(' ', $text, 2);
        $command = strtolower($parts[0]);
        $argument = $parts[1] ?? null;

        match ($command) {
            '/start' => $this->handleStart($chatId, $argument, $user),
            '/unlink' => $this->handleUnlink($chatId),
            '/help' => $this->handleHelp($chatId),
            '/status' => $this->handleStatus($chatId),
            '/menu' => $this->handleMenu($chatId, $user),
            default => $this->handleUnknownCommand($chatId, $command),
        };
    }

    /**
     * Handle /start command.
     */
    protected function handleStart(string $chatId, ?string $token, ?User $existingUser): void
    {
        // If user already linked and no token, just show menu
        if ($existingUser && empty($token)) {
            $this->conversationService->showMainMenu($chatId, $existingUser);
            return;
        }

        $result = $this->systemBot->handleStartCommand($chatId, $token);

        Log::info('SystemBot: Start command processed', [
            'chat_id' => $chatId,
            'has_token' => !empty($token),
            'success' => $result['success'],
        ]);
    }

    /**
     * Handle /menu command.
     */
    protected function handleMenu(string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->systemBot->sendMessage(
                $chatId,
                "❌ Telegram hisobingiz ulanmagan.\n\n"
                . "Ulash uchun BiznesPilot dashboardidan \"Telegram ulash\" tugmasini bosing."
            );
            return;
        }

        $this->conversationService->showMainMenu($chatId, $user);
    }

    /**
     * Handle /unlink command.
     */
    protected function handleUnlink(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->systemBot->sendMessage(
                $chatId,
                "❌ Bu Telegram hisob hech qanday BiznesPilot foydalanuvchisiga ulanmagan."
            );
            return;
        }

        // Unlink user
        $user->unlinkTelegram();

        $this->systemBot->sendMessage($chatId, $this->systemBot->getUnlinkMessage());

        Log::info('SystemBot: User unlinked Telegram', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
        ]);
    }

    /**
     * Handle /help command.
     */
    protected function handleHelp(string $chatId): void
    {
        $this->systemBot->sendMessage($chatId, $this->getHelpMessage());
    }

    /**
     * Handle /status command.
     */
    protected function handleStatus(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->systemBot->sendMessage(
                $chatId,
                "📊 <b>Status:</b> Ulanmagan\n\n"
                . "Bu Telegram hisob hech qanday BiznesPilot foydalanuvchisiga ulanmagan.\n\n"
                . "Ulash uchun BiznesPilot dashboardidan \"Telegram ulash\" tugmasini bosing."
            );
            return;
        }

        $linkedAt = $user->telegram_linked_at?->format('d.m.Y H:i') ?? "Noma'lum";
        $businessCount = $user->businesses()->count();
        $currentBusiness = $user->currentBusiness;
        $dailyReports = $user->receive_daily_reports ? '🟢 Yoqilgan' : '🔴 O\'chirilgan';

        $this->systemBot->sendMessage(
            $chatId,
            "📊 <b>Status:</b> Ulangan ✅\n\n"
            . "👤 <b>Foydalanuvchi:</b> {$user->name}\n"
            . "📧 <b>Email:</b> {$user->email}\n"
            . "🏢 <b>Bizneslar:</b> {$businessCount} ta\n"
            . "🏬 <b>Joriy biznes:</b> " . ($currentBusiness?->name ?? 'Tanlanmagan') . "\n"
            . "📅 <b>Ulangan:</b> {$linkedAt}\n"
            . "📬 <b>Kunlik hisobot:</b> {$dailyReports}\n\n"
            . "🌅 Har kuni 07:00 da kunlik brief olasiz."
        );
    }

    /**
     * Handle unknown command.
     */
    protected function handleUnknownCommand(string $chatId, string $command): void
    {
        $this->systemBot->sendMessage(
            $chatId,
            "❓ Noma'lum buyruq: <code>{$command}</code>\n\n"
            . "Yordam uchun /help buyrug'ini yuboring."
        );
    }

    /**
     * Get help message.
     */
    protected function getHelpMessage(): string
    {
        return "📚 <b>BiznesPilot - Raqamli Menejer</b>\n\n"
            . "Bu bot sizga biznesingizni boshqarishda yordam beradi.\n\n"
            . "<b>🎛 Menu tugmalari:</b>\n"
            . "📊 Bugungi Holat - Kunlik statistika\n"
            . "💰 Kassa - Kirim-chiqim\n"
            . "📝 Vazifa Berish - Xodimga vazifa yuklash\n"
            . "⚙️ Sozlamalar - Bot sozlamalari\n\n"
            . "<b>⌨️ Buyruqlar:</b>\n"
            . "/start - Botni ishga tushirish\n"
            . "/menu - Menyuni ko'rsatish\n"
            . "/status - Hisob holatini ko'rish\n"
            . "/unlink - Telegram hisobini uzish\n"
            . "/help - Yordam xabari\n\n"
            . "<b>🔔 Avtomatik bildirishnomalar:</b>\n"
            . "• 🌅 Har kuni 07:00 da kunlik brief\n"
            . "• 🤑 Real-time to'lov ogohlantirishlari\n"
            . "• 🐢 Kechikkan vazifalar eslatmasi\n\n"
            . "🤖 <i>BiznesPilot - Biznesingiz uchun aqlli yordamchi</i>";
    }

    /**
     * Verify webhook secret token.
     */
    protected function verifyWebhookSecret(Request $request): bool
    {
        $secret = config('services.telegram.webhook_secret');

        // If no secret configured, allow all requests
        if (empty($secret)) {
            return true;
        }

        // Telegram sends secret in X-Telegram-Bot-Api-Secret-Token header
        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        return $headerSecret === $secret;
    }

    /**
     * Setup webhook (admin endpoint).
     */
    public function setupWebhook(Request $request): JsonResponse
    {
        $webhookUrl = $request->input('webhook_url')
            ?? route('telegram.system-bot.webhook');

        $result = $this->systemBot->setWebhook($webhookUrl);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook set successfully',
                'webhook_url' => $webhookUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to set webhook',
        ], 500);
    }

    /**
     * Get bot info (admin endpoint).
     */
    public function info(): JsonResponse
    {
        $botInfo = $this->systemBot->getBotInfo();

        if (!$botInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Bot not configured or unreachable',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'bot' => $botInfo,
        ]);
    }
}
