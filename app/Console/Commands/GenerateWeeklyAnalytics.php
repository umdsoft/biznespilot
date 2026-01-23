<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyAnalyticsNotificationJob;
use App\Models\Business;
use App\Services\WeeklyAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:weekly
                            {--business= : Specific business ID to generate for}
                            {--with-ai : Also generate AI analysis}
                            {--with-notify : Send notifications to business owners}
                            {--notify-channels=mail,telegram : Notification channels (mail, telegram)}
                            {--week= : Specific week start date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Haftalik analitika hisobotlarini yaratish';

    /**
     * Execute the console command.
     */
    public function handle(WeeklyAnalyticsService $analyticsService): int
    {
        $this->info('Haftalik analitika hisobotlarini yaratish boshlanmoqda...');

        $businessId = $this->option('business');
        $withAi = $this->option('with-ai');
        $withNotify = $this->option('with-notify');
        $notifyChannels = explode(',', $this->option('notify-channels'));
        $weekStart = $this->option('week');

        // Parse week start if provided
        $weekStartDate = null;
        if ($weekStart) {
            try {
                $weekStartDate = \Carbon\Carbon::parse($weekStart)->startOfWeek();
                $this->info("Tanlangan hafta: {$weekStartDate->format('Y-m-d')}");
            } catch (\Exception $e) {
                $this->error("Noto'g'ri sana formati: {$weekStart}");

                return self::FAILURE;
            }
        }

        // Get businesses
        if ($businessId) {
            $businesses = Business::where('id', $businessId)->get();
            if ($businesses->isEmpty()) {
                $this->error("Biznes topilmadi: {$businessId}");

                return self::FAILURE;
            }
        } else {
            // Only active businesses with leads
            $businesses = Business::whereHas('leads')->get();
        }

        $this->info("Jami {$businesses->count()} ta biznes topildi.");

        if ($businesses->isEmpty()) {
            $this->info('Hech qanday biznes topilmadi.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($businesses->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $aiCount = 0;
        $notifyCount = 0;

        foreach ($businesses as $business) {
            try {
                // Generate weekly report
                $analytics = $analyticsService->generateWeeklyReport($business, $weekStartDate);

                if ($analytics->wasRecentlyCreated) {
                    $successCount++;
                    Log::info('Weekly analytics generated', [
                        'business_id' => $business->id,
                        'business_name' => $business->name,
                        'week_start' => $analytics->week_start->format('Y-m-d'),
                    ]);
                }

                // Generate AI analysis if requested
                if ($withAi && ! $analytics->hasAiAnalysis()) {
                    try {
                        $analyticsService->generateAiAnalysis($analytics);
                        $aiCount++;
                        Log::info('AI analysis generated', [
                            'business_id' => $business->id,
                            'analytics_id' => $analytics->id,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('AI analysis failed', [
                            'business_id' => $business->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Send notifications if requested
                if ($withNotify) {
                    try {
                        SendWeeklyAnalyticsNotificationJob::dispatch($analytics, $notifyChannels);
                        $notifyCount++;
                    } catch (\Exception $e) {
                        Log::warning('Notification dispatch failed', [
                            'business_id' => $business->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to generate weekly analytics', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
                $this->newLine();
                $this->error("Xatolik ({$business->name}): {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Yaratildi: {$successCount} ta hisobot");

        if ($aiCount > 0) {
            $this->info("AI tahlil: {$aiCount} ta");
        }

        if ($notifyCount > 0) {
            $this->info("Xabarnomalar: {$notifyCount} ta");
        }

        if ($errorCount > 0) {
            $this->warn("Xatoliklar: {$errorCount}");
        }

        $this->info('Tayyor!');

        return self::SUCCESS;
    }
}
