<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Business;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Reports\InsightEngineService;
use App\Services\ReportingService;
use App\Services\Telegram\SystemBotService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateDailyBriefJob
 *
 * Har kuni ertalab 07:00 da barcha bizneslar uchun
 * System Bot orqali Business Owner larga kunlik brief yuboradi.
 *
 * "Dual Bot Strategy":
 * - System Bot: BiznesPilot adminlarga xabar yuborish (bu job)
 * - Tenant Bot: Bizneslar o'z mijozlari bilan aloqa (Flow Builder)
 *
 * Brief tarkibi (InsightEngineService):
 * 1. Bleeding Money - yo'qotilgan imkoniyatlar
 * 2. Marketing Truth - marketing ROI
 * 3. Actionable Tasks - shoshilinch vazifalar
 * 4. Quick Wins - tezkor yutuqlar
 */
class GenerateDailyBriefJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 600;

    public function __construct(
        public ?Business $business = null,
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::yesterday();
    }

    public function handle(
        InsightEngineService $insightEngine,
        SystemBotService $systemBot,
        ReportingService $reportingService,
        NotificationService $notificationService
    ): void {
        // Check if System Bot is configured
        if (!$systemBot->isConfigured()) {
            Log::warning('GenerateDailyBriefJob: System Bot not configured, skipping Telegram delivery');
        }

        if ($this->business) {
            // Single business mode
            $this->generateForBusiness(
                $insightEngine,
                $systemBot,
                $reportingService,
                $notificationService,
                $this->business
            );
        } else {
            // All active businesses mode
            $this->generateForAllBusinesses($insightEngine, $systemBot);
        }
    }

    /**
     * Generate and send brief for a single business.
     */
    protected function generateForBusiness(
        InsightEngineService $insightEngine,
        SystemBotService $systemBot,
        ReportingService $reportingService,
        NotificationService $notificationService,
        Business $business
    ): void {
        try {
            // 1. Generate Telegram brief using InsightEngineService
            $telegramBrief = $insightEngine->generateDailyBrief($business->id, $this->date);

            // 2. Send to linked Telegram users via System Bot
            $sentCount = $this->sendToBusinessOwners($business, $telegramBrief, $systemBot);

            // 3. Also generate standard report for dashboard (optional)
            try {
                $report = $reportingService->generateDailyBrief($business, $this->date);
                if ($report) {
                    // In-app notification only
                    $notificationService->send(
                        $business,
                        null,
                        'report',
                        'Kunlik brief tayyor',
                        'Kechagi kun uchun ertalabki brief yaratildi.',
                        [
                            'icon' => 'document-chart-bar',
                            'action_url' => "/dashboard/reports/{$report->id}",
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Failed to generate standard report, but Telegram brief was sent', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Daily brief generated and sent', [
                'business_id' => $business->id,
                'date' => $this->date->format('Y-m-d'),
                'telegram_sent_count' => $sentCount,
                'brief_length' => strlen($telegramBrief),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate daily brief', [
                'business_id' => $business->id,
                'date' => $this->date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate and send briefs for all active businesses.
     */
    protected function generateForAllBusinesses(
        InsightEngineService $insightEngine,
        SystemBotService $systemBot
    ): void {
        $businesses = Business::where('status', 'active')->get();
        $successCount = 0;
        $failCount = 0;
        $totalSent = 0;

        foreach ($businesses as $business) {
            try {
                // Generate brief
                $telegramBrief = $insightEngine->generateDailyBrief($business->id, $this->date);

                // Send to business owners via System Bot
                $sentCount = $this->sendToBusinessOwners($business, $telegramBrief, $systemBot);

                if ($sentCount > 0) {
                    $successCount++;
                    $totalSent += $sentCount;
                }

            } catch (\Exception $e) {
                $failCount++;
                Log::error('Failed to generate daily brief for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::info('Daily briefs generated for all businesses', [
            'date' => $this->date->format('Y-m-d'),
            'total_businesses' => $businesses->count(),
            'success' => $successCount,
            'failed' => $failCount,
            'total_messages_sent' => $totalSent,
        ]);
    }

    /**
     * Send brief to business owners who have linked their Telegram.
     *
     * CRITICAL: Uses users.telegram_chat_id (System Bot)
     * NOT UserNotificationSetting or TelegramBot (those are for Tenant Bots)
     */
    protected function sendToBusinessOwners(
        Business $business,
        string $brief,
        SystemBotService $systemBot
    ): int {
        $sentCount = 0;

        // Get all users of this business who have linked their Telegram AND want daily reports
        $users = $business->users()
            ->whereNotNull('telegram_chat_id')
            ->where('receive_daily_reports', true)
            ->get();

        if ($users->isEmpty()) {
            Log::debug('No users with linked Telegram for business', [
                'business_id' => $business->id,
            ]);

            return 0;
        }

        foreach ($users as $user) {
            $success = $systemBot->sendDailyBrief($user, $brief);

            if ($success) {
                $sentCount++;
                Log::debug('Daily brief sent to user', [
                    'user_id' => $user->id,
                    'business_id' => $business->id,
                ]);
            } else {
                Log::warning('Failed to send daily brief to user', [
                    'user_id' => $user->id,
                    'chat_id' => $user->telegram_chat_id,
                ]);
            }
        }

        return $sentCount;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateDailyBriefJob failed', [
            'business_id' => $this->business?->id,
            'date' => $this->date?->format('Y-m-d'),
            'error' => $exception->getMessage(),
        ]);
    }
}
