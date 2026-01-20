<?php

namespace App\Jobs\System;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\ContentGeneration;
use App\Models\ContentIdeaUsage;
use App\Models\MetaCampaignInsight;
use App\Models\GoogleAdsCampaignInsight;
use App\Models\TelegramMessage;
use App\Models\ChatbotConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Eski va keraksiz datalarni tozalash
 * Har kuni tunda (03:00) ishga tushadi
 */
class DataCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1800; // 30 daqiqa
    public int $backoff = 300;

    protected array $stats = [
        'notifications' => 0,
        'notification_deliveries' => 0,
        'activity_logs' => 0,
        'content_generations' => 0,
        'telegram_messages' => 0,
        'campaign_insights' => 0,
        'temp_files' => 0,
        'failed_jobs' => 0,
    ];

    public function handle(): void
    {
        Log::info('DataCleanupJob started');

        $this->cleanOldNotifications();
        $this->cleanOldNotificationDeliveries();
        $this->cleanOldActivityLogs();
        $this->cleanOldContentGenerations();
        $this->cleanOldTelegramMessages();
        $this->cleanOldCampaignInsights();
        $this->cleanTempFiles();
        $this->cleanFailedJobs();
        $this->optimizeTables();

        Log::info('DataCleanupJob completed', $this->stats);
    }

    /**
     * 30 kundan eski o'qilgan notificationlarni o'chirish
     */
    protected function cleanOldNotifications(): void
    {
        try {
            $deleted = DB::table('in_app_notifications')
                ->where('created_at', '<', now()->subDays(30))
                ->where('is_read', true)
                ->delete();

            $this->stats['notifications'] = $deleted;
            Log::info("Cleaned {$deleted} old notifications");
        } catch (\Exception $e) {
            Log::warning('Failed to clean notifications: ' . $e->getMessage());
        }
    }

    /**
     * 90 kundan eski notification delivery loglarni o'chirish
     */
    protected function cleanOldNotificationDeliveries(): void
    {
        try {
            $deleted = NotificationDelivery::where('created_at', '<', now()->subDays(90))
                ->delete();

            $this->stats['notification_deliveries'] = $deleted;
            Log::info("Cleaned {$deleted} old notification deliveries");
        } catch (\Exception $e) {
            Log::warning('Failed to clean notification deliveries: ' . $e->getMessage());
        }
    }

    /**
     * 90 kundan eski activity loglarni o'chirish
     */
    protected function cleanOldActivityLogs(): void
    {
        try {
            // activity_log table mavjudligini tekshirish
            if (DB::getSchemaBuilder()->hasTable('activity_log')) {
                $deleted = DB::table('activity_log')
                    ->where('created_at', '<', now()->subDays(90))
                    ->delete();

                $this->stats['activity_logs'] = $deleted;
                Log::info("Cleaned {$deleted} old activity logs");
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean activity logs: ' . $e->getMessage());
        }
    }

    /**
     * 60 kundan eski failed content generationlarni o'chirish
     */
    protected function cleanOldContentGenerations(): void
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('content_generations')) {
                $deleted = DB::table('content_generations')
                    ->where('created_at', '<', now()->subDays(60))
                    ->where('status', 'failed')
                    ->delete();

                $this->stats['content_generations'] = $deleted;
                Log::info("Cleaned {$deleted} old failed content generations");
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean content generations: ' . $e->getMessage());
        }
    }

    /**
     * 60 kundan eski telegram xabarlarni o'chirish
     */
    protected function cleanOldTelegramMessages(): void
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('telegram_messages')) {
                $deleted = DB::table('telegram_messages')
                    ->where('created_at', '<', now()->subDays(60))
                    ->delete();

                $this->stats['telegram_messages'] = $deleted;
                Log::info("Cleaned {$deleted} old telegram messages");
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean telegram messages: ' . $e->getMessage());
        }
    }

    /**
     * 180 kundan eski campaign insightlarni o'chirish (agregatsiya qilib)
     */
    protected function cleanOldCampaignInsights(): void
    {
        $deletedMeta = 0;
        $deletedGoogle = 0;

        try {
            // Meta insights
            if (DB::getSchemaBuilder()->hasTable('meta_campaign_insights')) {
                $deletedMeta = DB::table('meta_campaign_insights')
                    ->where('date', '<', now()->subDays(180))
                    ->delete();
            }

            // Google Ads insights
            if (DB::getSchemaBuilder()->hasTable('google_ads_campaign_insights')) {
                $deletedGoogle = DB::table('google_ads_campaign_insights')
                    ->where('date', '<', now()->subDays(180))
                    ->delete();
            }

            $this->stats['campaign_insights'] = $deletedMeta + $deletedGoogle;
            Log::info("Cleaned {$this->stats['campaign_insights']} old campaign insights");
        } catch (\Exception $e) {
            Log::warning('Failed to clean campaign insights: ' . $e->getMessage());
        }
    }

    /**
     * Temp fayllarni tozalash
     */
    protected function cleanTempFiles(): void
    {
        try {
            $tempDirs = ['temp', 'exports', 'imports/processed'];
            $deleted = 0;

            foreach ($tempDirs as $dir) {
                if (Storage::disk('local')->exists($dir)) {
                    $files = Storage::disk('local')->files($dir);

                    foreach ($files as $file) {
                        $lastModified = Storage::disk('local')->lastModified($file);

                        // 7 kundan eski fayllarni o'chirish
                        if ($lastModified < now()->subDays(7)->timestamp) {
                            Storage::disk('local')->delete($file);
                            $deleted++;
                        }
                    }
                }
            }

            $this->stats['temp_files'] = $deleted;
            Log::info("Cleaned {$deleted} temp files");
        } catch (\Exception $e) {
            Log::warning('Failed to clean temp files: ' . $e->getMessage());
        }
    }

    /**
     * 7 kundan eski failed joblarni o'chirish
     */
    protected function cleanFailedJobs(): void
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $deleted = DB::table('failed_jobs')
                    ->where('failed_at', '<', now()->subDays(7))
                    ->delete();

                $this->stats['failed_jobs'] = $deleted;
                Log::info("Cleaned {$deleted} old failed jobs");
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean failed jobs: ' . $e->getMessage());
        }
    }

    /**
     * Jadvallarni optimizatsiya qilish
     */
    protected function optimizeTables(): void
    {
        try {
            $tables = [
                'in_app_notifications',
                'notification_deliveries',
                'activity_log',
                'telegram_messages',
                'meta_campaign_insights',
                'google_ads_campaign_insights',
            ];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    // MySQL uchun OPTIMIZE
                    if (config('database.default') === 'mysql') {
                        DB::statement("OPTIMIZE TABLE {$table}");
                    }
                }
            }

            Log::info('Tables optimized');
        } catch (\Exception $e) {
            Log::warning('Failed to optimize tables: ' . $e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DataCleanupJob failed', [
            'error' => $exception->getMessage(),
            'stats' => $this->stats,
        ]);
    }
}
