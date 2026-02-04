<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Business;
use App\Models\Task;
use App\Services\Telegram\SystemBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CheckStagnantTasksJob - The "Whip" for stagnant tasks
 *
 * Checks for tasks that are overdue by more than 1 hour
 * and sends alerts to business owners via Telegram.
 *
 * Run hourly via scheduler.
 */
class CheckStagnantTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(SystemBotService $systemBot): void
    {
        Log::info('CheckStagnantTasksJob: Starting stagnant tasks check');

        // Get all active businesses (chunked to avoid memory issues)
        $businesses = Business::where('status', 'active')->get();

        $totalAlerts = 0;

        foreach ($businesses as $business) {
            $alerts = $this->checkBusinessTasks($business, $systemBot);
            $totalAlerts += $alerts;
        }

        Log::info('CheckStagnantTasksJob: Completed', [
            'total_alerts' => $totalAlerts,
            'businesses_checked' => $businesses->count(),
        ]);
    }

    /**
     * Check stagnant tasks for a specific business.
     */
    protected function checkBusinessTasks(Business $business, SystemBotService $systemBot): int
    {
        $alertCount = 0;

        // Find tasks that are:
        // 1. Not completed
        // 2. Have a due_date that is more than 1 hour past
        // 3. Haven't been alerted about recently (using stagnant_alert_sent_at)
        $stagnantTasks = Task::where('business_id', $business->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->subHour())
            ->where(function ($query) {
                $query->whereNull('stagnant_alert_sent_at')
                    ->orWhere('stagnant_alert_sent_at', '<', now()->subHours(4)); // Re-alert after 4 hours
            })
            ->with(['assignee', 'creator'])
            ->get();

        if ($stagnantTasks->isEmpty()) {
            return 0;
        }

        // Get business owners/managers who have Telegram linked
        $managers = $business->users()
            ->whereNotNull('telegram_chat_id')
            ->where('receive_daily_reports', true)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['owner', 'admin', 'manager']);
            })
            ->get();

        if ($managers->isEmpty()) {
            Log::debug('CheckStagnantTasksJob: No managers with Telegram for business', [
                'business_id' => $business->id,
            ]);
            return 0;
        }

        foreach ($stagnantTasks as $task) {
            $hoursOverdue = (int) now()->diffInHours($task->due_date);
            $employeeName = $task->assignee?->name ?? 'Belgilanmagan';

            // Send alert to each manager
            foreach ($managers as $manager) {
                // Skip if the manager is the assignee
                if ($task->assigned_to && $task->assigned_to === $manager->id) {
                    continue;
                }

                $sent = $systemBot->sendStagnantTaskAlert(
                    $manager,
                    $task->id,
                    $task->title,
                    $employeeName,
                    $hoursOverdue
                );

                if ($sent) {
                    $alertCount++;
                }
            }

            // Mark task as alerted
            $task->update(['stagnant_alert_sent_at' => now()]);

            Log::info('CheckStagnantTasksJob: Stagnant task alert sent', [
                'task_id' => $task->id,
                'business_id' => $business->id,
                'hours_overdue' => $hoursOverdue,
            ]);
        }

        return $alertCount;
    }
}
