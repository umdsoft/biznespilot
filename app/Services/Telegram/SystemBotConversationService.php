<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\Business;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SystemBotConversationService - State Machine for Telegram Bot Conversations
 *
 * Handles multi-step conversations like task assignment using Cache-based state.
 * Each user's conversation state is tracked independently using their chat_id.
 */
class SystemBotConversationService
{
    // Conversation States
    public const STATE_IDLE = 'idle';
    public const STATE_AWAITING_EMPLOYEE = 'awaiting_employee';
    public const STATE_AWAITING_TASK_TEXT = 'awaiting_task_text';
    public const STATE_AWAITING_DUE_DATE = 'awaiting_due_date';
    public const STATE_AWAITING_DUE_TIME = 'awaiting_due_time';
    public const STATE_AWAITING_SETTINGS = 'awaiting_settings';

    // Cache TTL (30 minutes for conversation state)
    private const STATE_TTL = 1800;

    private SystemBotService $botService;

    public function __construct(SystemBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Get current conversation state for a chat.
     */
    public function getState(string $chatId): string
    {
        return Cache::get($this->stateKey($chatId), self::STATE_IDLE);
    }

    /**
     * Set conversation state.
     */
    public function setState(string $chatId, string $state): void
    {
        Cache::put($this->stateKey($chatId), $state, self::STATE_TTL);
    }

    /**
     * Clear conversation state.
     */
    public function clearState(string $chatId): void
    {
        Cache::forget($this->stateKey($chatId));
        Cache::forget($this->dataKey($chatId));
    }

    /**
     * Store conversation data (selected employee, business, etc.).
     */
    public function setData(string $chatId, array $data): void
    {
        $existing = $this->getData($chatId);
        Cache::put($this->dataKey($chatId), array_merge($existing, $data), self::STATE_TTL);
    }

    /**
     * Get conversation data.
     */
    public function getData(string $chatId): array
    {
        return Cache::get($this->dataKey($chatId), []);
    }

    /**
     * Handle incoming message based on current state.
     *
     * @return array{handled: bool, response?: string}
     */
    public function handleIncomingMessage(string $chatId, string $text, ?User $user): array
    {
        $state = $this->getState($chatId);

        Log::debug('SystemBot: Handling message in state', [
            'chat_id' => $chatId,
            'state' => $state,
            'text' => substr($text, 0, 50),
        ]);

        // Handle based on current state
        return match ($state) {
            self::STATE_AWAITING_TASK_TEXT => $this->handleTaskTextInput($chatId, $text, $user),
            default => ['handled' => false],
        };
    }

    /**
     * Start task assignment flow.
     */
    public function startTaskAssignment(string $chatId, User $user): void
    {
        // Get user's current business
        $business = $user->currentBusiness;

        if (!$business) {
            $this->botService->sendMessage($chatId, $this->getNoBusinessMessage());
            return;
        }

        // Get active employees (users of this business)
        $employees = $business->users()
            ->where('users.id', '!=', $user->id)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            $this->botService->sendMessage($chatId, $this->getNoEmployeesMessage());
            return;
        }

        // Store business context
        $this->setData($chatId, [
            'business_id' => $business->id,
            'assigner_id' => $user->id,
        ]);

        // Build employee selection keyboard
        $keyboard = $this->buildEmployeeKeyboard($employees);

        // Send employee selection message
        $this->botService->sendMessageWithInlineKeyboard(
            $chatId,
            $this->getSelectEmployeeMessage(),
            $keyboard
        );

        $this->setState($chatId, self::STATE_AWAITING_EMPLOYEE);
    }

    /**
     * Handle employee selection callback.
     */
    public function handleEmployeeSelection(string $chatId, string $employeeId, User $user): void
    {
        $data = $this->getData($chatId);

        if ($employeeId === 'all') {
            // Assign to all team
            $this->setData($chatId, [
                'assignee_id' => null,
                'assignee_type' => 'team',
                'assignee_name' => 'Jamoa (Hammaga)',
            ]);
            $assigneeName = "Jamoa (Hammaga)";
        } else {
            // Assign to specific employee
            $employee = User::find($employeeId);
            if (!$employee) {
                $this->botService->sendMessage($chatId, "Xodim topilmadi.");
                $this->clearState($chatId);
                return;
            }

            $this->setData($chatId, [
                'assignee_id' => $employeeId,
                'assignee_type' => 'user',
                'assignee_name' => $employee->name,
            ]);
            $assigneeName = $employee->name;
        }

        // Ask for task text
        $this->botService->sendMessage(
            $chatId,
            $this->getAskTaskTextMessage($assigneeName)
        );

        $this->setState($chatId, self::STATE_AWAITING_TASK_TEXT);
    }

    /**
     * Handle task text input.
     */
    private function handleTaskTextInput(string $chatId, string $text, ?User $user): array
    {
        if (!$user) {
            $this->clearState($chatId);
            return ['handled' => true];
        }

        $data = $this->getData($chatId);

        if (empty($data['business_id'])) {
            $this->clearState($chatId);
            return ['handled' => true];
        }

        // Save task text and ask for due date
        $this->setData($chatId, ['task_text' => $text]);

        // Show date selection keyboard
        $keyboard = $this->buildDateSelectionKeyboard();
        $this->botService->sendMessageWithInlineKeyboard(
            $chatId,
            $this->getSelectDueDateMessage($text),
            $keyboard
        );

        $this->setState($chatId, self::STATE_AWAITING_DUE_DATE);

        return ['handled' => true];
    }

    /**
     * Handle due date selection callback.
     */
    public function handleDueDateSelection(string $chatId, string $dateKey, User $user): void
    {
        // Parse date based on key
        $selectedDate = match ($dateKey) {
            'today' => now()->startOfDay(),
            'tomorrow' => now()->addDay()->startOfDay(),
            'in_2_days' => now()->addDays(2)->startOfDay(),
            'in_3_days' => now()->addDays(3)->startOfDay(),
            'next_week' => now()->addWeek()->startOfDay(),
            default => now()->addDay()->startOfDay(),
        };

        $this->setData($chatId, [
            'due_date_key' => $dateKey,
            'due_date' => $selectedDate->toDateString(),
        ]);

        // Show time selection keyboard
        $isToday = $dateKey === 'today';
        $keyboard = $this->buildTimeSelectionKeyboard($isToday);

        $dateLabel = $this->getDateLabel($dateKey);
        $this->botService->sendMessageWithInlineKeyboard(
            $chatId,
            $this->getSelectDueTimeMessage($dateLabel),
            $keyboard
        );

        $this->setState($chatId, self::STATE_AWAITING_DUE_TIME);
    }

    /**
     * Show date selection keyboard (for going back).
     */
    public function showDateSelection(string $chatId, User $user): void
    {
        $data = $this->getData($chatId);
        $taskText = $data['task_text'] ?? 'Vazifa';

        $keyboard = $this->buildDateSelectionKeyboard();
        $this->botService->sendMessageWithInlineKeyboard(
            $chatId,
            $this->getSelectDueDateMessage($taskText),
            $keyboard
        );

        $this->setState($chatId, self::STATE_AWAITING_DUE_DATE);
    }

    /**
     * Handle due time selection callback.
     */
    public function handleDueTimeSelection(string $chatId, string $timeKey, User $user): void
    {
        $data = $this->getData($chatId);

        if (empty($data['business_id']) || empty($data['task_text'])) {
            $this->clearState($chatId);
            $this->botService->sendMessage($chatId, "Xatolik yuz berdi. Qaytadan urinib ko'ring.");
            return;
        }

        // Parse time
        $hour = (int) $timeKey;
        $dueDate = now()->parse($data['due_date'])->setTime($hour, 0, 0);

        // Validate not in the past
        if ($dueDate->isPast()) {
            $this->botService->sendMessage(
                $chatId,
                "⚠️ O'tgan vaqtni tanlab bo'lmaydi!\n\nIltimos, boshqa vaqt tanlang."
            );
            // Re-show time selection
            $isToday = ($data['due_date_key'] ?? '') === 'today';
            $keyboard = $this->buildTimeSelectionKeyboard($isToday);
            $dateLabel = $this->getDateLabel($data['due_date_key'] ?? 'today');
            $this->botService->sendMessageWithInlineKeyboard(
                $chatId,
                $this->getSelectDueTimeMessage($dateLabel),
                $keyboard
            );
            return;
        }

        // Create the task with selected date/time
        $task = $this->createTask($data, $data['task_text'], $user, $dueDate);

        if ($task) {
            // Notify assignee if specific user
            if (!empty($data['assignee_id'])) {
                $this->notifyAssignee($task, $data['assignee_id']);
            }

            // Send confirmation
            $assigneeName = $data['assignee_name'] ?? 'Jamoa';
            $this->botService->sendMessage(
                $chatId,
                $this->getTaskCreatedMessage($task->title, $assigneeName, $dueDate)
            );
        } else {
            $this->botService->sendMessage($chatId, "Vazifa yaratishda xatolik yuz berdi.");
        }

        // Clear state and return to main menu
        $this->clearState($chatId);
        $this->showMainMenu($chatId, $user);
    }

    /**
     * Create task from bot input.
     */
    private function createTask(array $data, string $text, User $user, ?Carbon $dueDate = null): ?Task
    {
        try {
            $task = Task::create([
                'business_id' => $data['business_id'],
                'title' => $text,
                'description' => "📱 Telegram orqali yaratilgan vazifa",
                'status' => 'pending',
                'priority' => 'medium',
                'type' => 'task',
                'user_id' => $user->id,
                'assigned_to' => $data['assignee_id'] ?? null,
                'due_date' => $dueDate ?? now()->addDay(),
            ]);

            Log::info('SystemBot: Task created via Telegram', [
                'task_id' => $task->id,
                'business_id' => $data['business_id'],
                'assignee_id' => $data['assignee_id'] ?? 'team',
                'due_date' => $task->due_date?->toDateTimeString(),
            ]);

            return $task;
        } catch (\Exception $e) {
            Log::error('SystemBot: Failed to create task', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return null;
        }
    }

    /**
     * Notify assignee about new task.
     */
    private function notifyAssignee(Task $task, string $assigneeId): void
    {
        $assignee = User::find($assigneeId);

        if (!$assignee || !$assignee->telegram_chat_id) {
            return;
        }

        $message = $this->getNewTaskNotificationMessage($task);
        $this->botService->sendMessage($assignee->telegram_chat_id, $message);
    }

    /**
     * Show main menu.
     */
    public function showMainMenu(string $chatId, ?User $user): void
    {
        $this->botService->sendMainMenu($chatId, $user);
    }

    /**
     * Handle daily status request.
     */
    public function handleDailyStatus(string $chatId, User $user): void
    {
        $business = $user->currentBusiness;

        if (!$business) {
            $this->botService->sendMessage($chatId, $this->getNoBusinessMessage());
            return;
        }

        // Get today's stats
        $stats = $this->getDailyStats($business);

        $this->botService->sendMessage($chatId, $this->formatDailyStatusMessage($stats, $business));
    }

    /**
     * Handle cash/kassa request.
     */
    public function handleCashStatus(string $chatId, User $user): void
    {
        $business = $user->currentBusiness;

        if (!$business) {
            $this->botService->sendMessage($chatId, $this->getNoBusinessMessage());
            return;
        }

        $cashStats = $this->getCashStats($business);

        $this->botService->sendMessage($chatId, $this->formatCashStatusMessage($cashStats, $business));
    }

    /**
     * Handle settings menu.
     */
    public function handleSettings(string $chatId, User $user): void
    {
        $keyboard = [
            [
                ['text' => $user->receive_daily_reports ? '🔔 Kunlik hisobot: Yoqilgan' : '🔕 Kunlik hisobot: O\'chirilgan', 'callback_data' => 'toggle_daily_reports'],
            ],
            [
                ['text' => '🔙 Orqaga', 'callback_data' => 'back_to_menu'],
            ],
        ];

        $this->botService->sendMessageWithInlineKeyboard(
            $chatId,
            $this->getSettingsMessage($user),
            $keyboard
        );
    }

    /**
     * Toggle daily reports setting.
     */
    public function toggleDailyReports(string $chatId, User $user): void
    {
        $user->update([
            'receive_daily_reports' => !$user->receive_daily_reports,
        ]);

        $status = $user->receive_daily_reports ? 'yoqildi' : "o'chirildi";
        $this->botService->sendMessage($chatId, "✅ Kunlik hisobot {$status}!");

        // Show settings again
        $this->handleSettings($chatId, $user->fresh());
    }

    // ============================================
    // STATISTICS MENU
    // ============================================

    /**
     * Show statistics menu with inline keyboard.
     */
    public function showStatisticsMenu(string $chatId, User $user): void
    {
        $keyboard = [
            [
                ['text' => '💰 Savdo', 'callback_data' => 'stats_sales'],
                ['text' => '📢 Marketing', 'callback_data' => 'stats_marketing'],
            ],
            [
                ['text' => '🏆 Xodimlar', 'callback_data' => 'stats_employees'],
            ],
            [
                ['text' => '🔙 Orqaga', 'callback_data' => 'back_to_menu'],
            ],
        ];

        $this->botService->sendMessageWithInlineKeyboard(
            $chatId,
            "📊 <b>STATISTIKA</b>\n\nQaysi hisobotni ko'rmoqchisiz?",
            $keyboard
        );
    }

    /**
     * Handle sales statistics request.
     */
    public function handleSalesStats(string $chatId, User $user): void
    {
        $business = $user->currentBusiness;

        if (!$business) {
            $this->botService->sendMessage($chatId, $this->getNoBusinessMessage());
            return;
        }

        $statisticsService = app(\App\Services\Reports\StatisticsService::class);
        $report = $statisticsService->generateSalesReport($business);

        $this->botService->sendMessage($chatId, $report);
        $this->showStatisticsMenu($chatId, $user);
    }

    /**
     * Handle marketing statistics request.
     */
    public function handleMarketingStats(string $chatId, User $user): void
    {
        $business = $user->currentBusiness;

        if (!$business) {
            $this->botService->sendMessage($chatId, $this->getNoBusinessMessage());
            return;
        }

        $statisticsService = app(\App\Services\Reports\StatisticsService::class);
        $report = $statisticsService->generateMarketingReport($business);

        $this->botService->sendMessage($chatId, $report);
        $this->showStatisticsMenu($chatId, $user);
    }

    /**
     * Handle employee leaderboard request.
     */
    public function handleEmployeeLeaderboard(string $chatId, User $user): void
    {
        $business = $user->currentBusiness;

        if (!$business) {
            $this->botService->sendMessage($chatId, $this->getNoBusinessMessage());
            return;
        }

        $statisticsService = app(\App\Services\Reports\StatisticsService::class);
        $report = $statisticsService->generateEmployeeLeaderboard($business);

        $this->botService->sendMessage($chatId, $report);
        $this->showStatisticsMenu($chatId, $user);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function stateKey(string $chatId): string
    {
        return "telegram_conv_state_{$chatId}";
    }

    private function dataKey(string $chatId): string
    {
        return "telegram_conv_data_{$chatId}";
    }

    private function buildEmployeeKeyboard($employees): array
    {
        $keyboard = [];

        foreach ($employees as $employee) {
            $keyboard[] = [
                ['text' => "👤 {$employee->name}", 'callback_data' => "assign_emp_{$employee->id}"],
            ];
        }

        // Add "Assign to Team" option
        $keyboard[] = [
            ['text' => '👥 Jamoaga (Hammaga)', 'callback_data' => 'assign_emp_all'],
        ];

        // Add cancel option
        $keyboard[] = [
            ['text' => '❌ Bekor qilish', 'callback_data' => 'cancel_task'],
        ];

        return $keyboard;
    }

    /**
     * Build date selection inline keyboard.
     */
    private function buildDateSelectionKeyboard(): array
    {
        $today = now();

        return [
            [
                ['text' => '📅 Bugun (' . $today->format('d.m') . ')', 'callback_data' => 'due_date_today'],
                ['text' => '📅 Ertaga (' . $today->addDay()->format('d.m') . ')', 'callback_data' => 'due_date_tomorrow'],
            ],
            [
                ['text' => '📅 2 kundan keyin', 'callback_data' => 'due_date_in_2_days'],
                ['text' => '📅 3 kundan keyin', 'callback_data' => 'due_date_in_3_days'],
            ],
            [
                ['text' => '📅 Keyingi hafta', 'callback_data' => 'due_date_next_week'],
            ],
            [
                ['text' => '❌ Bekor qilish', 'callback_data' => 'cancel_task'],
            ],
        ];
    }

    /**
     * Build time selection inline keyboard.
     * If isToday, filter out past hours.
     */
    private function buildTimeSelectionKeyboard(bool $isToday = false): array
    {
        $currentHour = (int) now()->format('H');
        $keyboard = [];

        // Define time slots
        $timeSlots = [
            ['09', '10', '11', '12'],
            ['13', '14', '15', '16'],
            ['17', '18', '19', '20'],
        ];

        foreach ($timeSlots as $row) {
            $rowButtons = [];
            foreach ($row as $hour) {
                $hourInt = (int) $hour;
                // Skip past hours if today
                if ($isToday && $hourInt <= $currentHour) {
                    continue;
                }
                $rowButtons[] = [
                    'text' => "🕐 {$hour}:00",
                    'callback_data' => "due_time_{$hour}",
                ];
            }
            if (!empty($rowButtons)) {
                $keyboard[] = $rowButtons;
            }
        }

        // If no valid times today, show message option
        if (empty($keyboard)) {
            $keyboard[] = [
                ['text' => '⚠️ Bugungi vaqtlar tugadi', 'callback_data' => 'no_time_available'],
            ];
        }

        // Add cancel option
        $keyboard[] = [
            ['text' => '🔙 Sanani o\'zgartirish', 'callback_data' => 'change_due_date'],
            ['text' => '❌ Bekor qilish', 'callback_data' => 'cancel_task'],
        ];

        return $keyboard;
    }

    /**
     * Get date label for display.
     */
    private function getDateLabel(string $dateKey): string
    {
        return match ($dateKey) {
            'today' => 'Bugun (' . now()->format('d.m') . ')',
            'tomorrow' => 'Ertaga (' . now()->addDay()->format('d.m') . ')',
            'in_2_days' => now()->addDays(2)->format('d.m.Y'),
            'in_3_days' => now()->addDays(3)->format('d.m.Y'),
            'next_week' => 'Keyingi hafta (' . now()->addWeek()->format('d.m') . ')',
            default => now()->addDay()->format('d.m.Y'),
        };
    }

    private function getDailyStats(Business $business): array
    {
        $today = now()->startOfDay();

        return [
            'leads_count' => $business->leads()->whereDate('created_at', $today)->count(),
            'orders_count' => $business->orders()->whereDate('created_at', $today)->count(),
            'orders_total' => $business->orders()->whereDate('created_at', $today)->sum('total'),
            'tasks_completed' => $business->tasks()->whereDate('completed_at', $today)->count(),
            'tasks_pending' => $business->tasks()->where('status', 'pending')->count(),
            'new_customers' => $business->customers()->whereDate('created_at', $today)->count(),
        ];
    }

    private function getCashStats(Business $business): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Use orders for income tracking (paid orders = income)
        $todayIncome = $business->orders()
            ->whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total');

        $monthIncome = $business->orders()
            ->where('created_at', '>=', $month)
            ->where('payment_status', 'paid')
            ->sum('total');

        $lastMonthIncome = $business->orders()
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->where('payment_status', 'paid')
            ->sum('total');

        $pendingPayments = $business->orders()
            ->where('payment_status', 'pending')
            ->sum('total');

        // Get last payment info
        $lastPayment = $business->orders()
            ->where('payment_status', 'paid')
            ->latest()
            ->first();

        return [
            'today_income' => $todayIncome,
            'month_income' => $monthIncome,
            'last_month_income' => $lastMonthIncome,
            'pending_payments' => $pendingPayments,
            'last_payment' => $lastPayment,
        ];
    }

    // ============================================
    // MESSAGE TEMPLATES
    // ============================================

    private function getNoBusinessMessage(): string
    {
        return "❌ Biznes topilmadi.\n\nIltimos, avval BiznesPilot da biznes yarating.";
    }

    private function getNoEmployeesMessage(): string
    {
        return "👥 Xodimlar topilmadi.\n\nAvval BiznesPilot ga xodimlarni qo'shing.";
    }

    private function getSelectEmployeeMessage(): string
    {
        return "👤 <b>Kimga yuklaymiz?</b>\n\nXodimni tanlang:";
    }

    private function getAskTaskTextMessage(string $assigneeName): string
    {
        return "📝 <b>Vazifa: {$assigneeName}</b>\n\nVazifani yozing:";
    }

    private function getSelectDueDateMessage(string $taskText): string
    {
        return "📝 <b>Vazifa:</b> {$taskText}\n\n"
            . "📅 <b>Qachon bajarilishi kerak?</b>\n\n"
            . "Sanani tanlang:";
    }

    private function getSelectDueTimeMessage(string $dateLabel): string
    {
        return "📅 <b>Sana:</b> {$dateLabel}\n\n"
            . "🕐 <b>Soatni tanlang:</b>";
    }

    private function getTaskCreatedMessage(string $title, string $assigneeName, ?Carbon $dueDate = null): string
    {
        $dueDateStr = $dueDate ? $dueDate->format('d.m.Y H:i') : 'Belgilanmagan';

        return "✅ <b>Vazifa yaratildi!</b>\n\n"
            . "📝 {$title}\n"
            . "👤 {$assigneeName} ga yuklandi\n"
            . "📅 Muddat: {$dueDateStr}\n\n"
            . "💪 <i>Omad!</i>";
    }

    private function getNewTaskNotificationMessage(Task $task): string
    {
        $creatorName = $task->creator?->name ?? 'Admin';

        return "📋 <b>Yangi vazifa!</b>\n\n"
            . "📝 {$task->title}\n"
            . "👨‍💼 Yuklagan: {$creatorName}\n"
            . "📅 Muddat: " . ($task->due_date?->format('d.m.Y H:i') ?? 'Belgilanmagan') . "\n\n"
            . "💪 <i>Omad!</i>";
    }

    private function getSettingsMessage(User $user): string
    {
        $dailyStatus = $user->receive_daily_reports ? '🟢 Yoqilgan' : '🔴 O\'chirilgan';

        return "⚙️ <b>Sozlamalar</b>\n\n"
            . "📊 Kunlik hisobot: {$dailyStatus}\n\n"
            . "Sozlamalarni o'zgartirish uchun tugmalarni bosing:";
    }

    private function formatDailyStatusMessage(array $stats, Business $business): string
    {
        $currency = "so'm";

        return "📊 <b>Bugungi holat: {$business->name}</b>\n\n"
            . "📅 " . now()->format('d.m.Y') . "\n\n"
            . "━━━━━━━━━━━━━━━\n"
            . "📥 Yangi lidlar: <b>{$stats['leads_count']}</b>\n"
            . "🛒 Buyurtmalar: <b>{$stats['orders_count']}</b>\n"
            . "💰 Savdo: <b>" . number_format($stats['orders_total'], 0, '.', ' ') . "</b> {$currency}\n"
            . "━━━━━━━━━━━━━━━\n"
            . "✅ Bajarilgan vazifalar: <b>{$stats['tasks_completed']}</b>\n"
            . "⏳ Kutilayotgan vazifalar: <b>{$stats['tasks_pending']}</b>\n"
            . "👥 Yangi mijozlar: <b>{$stats['new_customers']}</b>\n"
            . "━━━━━━━━━━━━━━━\n\n"
            . "🤖 <i>BiznesPilot - Sizning raqamli menejeringiz</i>";
    }

    private function formatCashStatusMessage(array $stats, Business $business): string
    {
        $currency = "so'm";

        // Compare with last month
        $monthDiff = $stats['month_income'] - $stats['last_month_income'];
        $monthEmoji = $monthDiff >= 0 ? '📈' : '📉';
        $monthSign = $monthDiff >= 0 ? '+' : '';

        $message = "💰 <b>KASSA</b>\n"
            . "🏢 {$business->name}\n\n"
            . "━━━ <b>BUGUN</b> ━━━\n"
            . "💚 Tushum: <b>" . number_format($stats['today_income'], 0, '.', ' ') . "</b> {$currency}\n\n"
            . "━━━ <b>BU OY</b> ━━━\n"
            . "💚 Tushum: <b>" . number_format($stats['month_income'], 0, '.', ' ') . "</b> {$currency}\n"
            . "{$monthEmoji} O'tgan oyga: <b>{$monthSign}" . number_format($monthDiff, 0, '.', ' ') . "</b> {$currency}\n\n";

        // Pending payments
        if ($stats['pending_payments'] > 0) {
            $message .= "━━━━━━━━━━━━━━━\n"
                . "⏳ Kutilayotgan: <b>" . number_format($stats['pending_payments'], 0, '.', ' ') . "</b> {$currency}\n\n";
        }

        // Last payment
        if ($stats['last_payment']) {
            $lastPaymentTime = $stats['last_payment']->created_at->format('H:i');
            $lastPaymentAmount = number_format($stats['last_payment']->total, 0, '.', ' ');
            $customerName = $stats['last_payment']->customer?->name ?? 'Mijoz';
            $message .= "💸 <i>Oxirgi: {$lastPaymentTime} da +{$lastPaymentAmount} ({$customerName})</i>";
        }

        return $message;
    }
}
